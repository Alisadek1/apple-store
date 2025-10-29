<?php
/**
 * Security Manager for Diagnostic Tools
 * 
 * Provides comprehensive security validation and access control
 * for administrative diagnostic and repair operations
 */

class SecurityManager {
    
    private $db;
    private $rate_limits = [
        'diagnostic_test' => ['limit' => 10, 'window' => 300], // 10 requests per 5 minutes
        'repair_operation' => ['limit' => 3, 'window' => 600], // 3 requests per 10 minutes
        'bulk_analysis' => ['limit' => 5, 'window' => 900],    // 5 requests per 15 minutes
        'hash_generation' => ['limit' => 20, 'window' => 300]  // 20 requests per 5 minutes
    ];
    
    public function __construct() {
        $this->db = getDB();
        $this->initializeSecurityTables();
    }
    
    /**
     * Initialize security-related database tables
     */
    private function initializeSecurityTables() {
        try {
            // Create rate limiting table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS security_rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    user_id INT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    request_count INT DEFAULT 1,
                    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip_action (ip_address, action_type),
                    INDEX idx_user_action (user_id, action_type),
                    INDEX idx_window_start (window_start)
                )
            ");
            
            // Create security audit log table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS security_audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    resource VARCHAR(255) NULL,
                    status ENUM('allowed', 'denied', 'suspicious') NOT NULL,
                    reason VARCHAR(255) NULL,
                    user_agent TEXT NULL,
                    session_id VARCHAR(128) NULL,
                    csrf_token_valid BOOLEAN NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_action (action),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                )
            ");
            
            // Create CSRF token storage table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS csrf_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    token_hash VARCHAR(128) NOT NULL UNIQUE,
                    user_id INT NOT NULL,
                    session_id VARCHAR(128) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    used_at TIMESTAMP NULL,
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_user_session (user_id, session_id),
                    INDEX idx_expires_at (expires_at)
                )
            ");
            
        } catch (Exception $e) {
            error_log("Security Manager initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate admin access for diagnostic tools
     */
    public function validateAdminAccess($required_action = null) {
        $validation_result = [
            'valid' => false,
            'user_id' => null,
            'session_valid' => false,
            'admin_role' => false,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'errors' => []
        ];
        
        // Check if session is started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Validate session
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            $validation_result['errors'][] = 'No valid session found';
            $this->logSecurityEvent(null, 'admin_access_attempt', null, 'denied', 'No valid session');
            return $validation_result;
        }
        
        $validation_result['user_id'] = $_SESSION['user_id'];
        $validation_result['session_valid'] = true;
        
        // Validate admin role
        if ($_SESSION['role'] !== 'admin') {
            $validation_result['errors'][] = 'Insufficient privileges - admin role required';
            $this->logSecurityEvent($_SESSION['user_id'], 'admin_access_attempt', $required_action, 'denied', 'Non-admin user');
            return $validation_result;
        }
        
        $validation_result['admin_role'] = true;
        
        // Validate session integrity
        if (!$this->validateSessionIntegrity()) {
            $validation_result['errors'][] = 'Session integrity check failed';
            $this->logSecurityEvent($_SESSION['user_id'], 'admin_access_attempt', $required_action, 'denied', 'Session integrity failure');
            return $validation_result;
        }
        
        // Check for session hijacking indicators
        if ($this->detectSessionHijacking()) {
            $validation_result['errors'][] = 'Suspicious session activity detected';
            $this->logSecurityEvent($_SESSION['user_id'], 'admin_access_attempt', $required_action, 'suspicious', 'Possible session hijacking');
            return $validation_result;
        }
        
        $validation_result['valid'] = true;
        $this->logSecurityEvent($_SESSION['user_id'], 'admin_access_granted', $required_action, 'allowed', 'Valid admin access');
        
        return $validation_result;
    }
    
    /**
     * Generate and validate CSRF tokens for diagnostic operations
     */
    public function generateCSRFToken($action, $expires_in_minutes = 30) {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User session required for CSRF token generation');
        }
        
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + ($expires_in_minutes * 60));
        
        try {
            // Clean up expired tokens
            $this->cleanupExpiredCSRFTokens();
            
            // Store token
            $stmt = $this->db->prepare("
                INSERT INTO csrf_tokens (token_hash, user_id, session_id, action, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $token_hash,
                $_SESSION['user_id'],
                session_id(),
                $action,
                $expires_at
            ]);
            
            return $token;
            
        } catch (Exception $e) {
            error_log("CSRF token generation failed: " . $e->getMessage());
            throw new Exception('Failed to generate CSRF token');
        }
    }
    
    /**
     * Validate CSRF token for diagnostic operations
     */
    public function validateCSRFToken($token, $action) {
        if (!$token || !$action) {
            return false;
        }
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $token_hash = hash('sha256', $token);
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, used_at, expires_at 
                FROM csrf_tokens 
                WHERE token_hash = ? 
                AND user_id = ? 
                AND session_id = ? 
                AND action = ?
                AND expires_at > NOW()
                AND used_at IS NULL
            ");
            
            $stmt->execute([
                $token_hash,
                $_SESSION['user_id'],
                session_id(),
                $action
            ]);
            
            $token_record = $stmt->fetch();
            
            if (!$token_record) {
                $this->logSecurityEvent($_SESSION['user_id'], 'csrf_validation_failed', $action, 'denied', 'Invalid or expired CSRF token');
                return false;
            }
            
            // Mark token as used
            $update_stmt = $this->db->prepare("UPDATE csrf_tokens SET used_at = NOW() WHERE id = ?");
            $update_stmt->execute([$token_record['id']]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("CSRF token validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Implement rate limiting for security testing features
     */
    public function checkRateLimit($action_type) {
        if (!isset($this->rate_limits[$action_type])) {
            return ['allowed' => true, 'remaining' => 999];
        }
        
        $limit_config = $this->rate_limits[$action_type];
        $ip_address = $this->getClientIP();
        $user_id = $_SESSION['user_id'] ?? null;
        $window_start = date('Y-m-d H:i:s', time() - $limit_config['window']);
        
        try {
            // Clean up old rate limit records
            $this->db->prepare("DELETE FROM security_rate_limits WHERE window_start < ?")->execute([$window_start]);
            
            // Check current rate limit
            $stmt = $this->db->prepare("
                SELECT request_count, window_start 
                FROM security_rate_limits 
                WHERE ip_address = ? 
                AND action_type = ? 
                AND window_start >= ?
            ");
            
            $stmt->execute([$ip_address, $action_type, $window_start]);
            $current_limit = $stmt->fetch();
            
            if ($current_limit) {
                if ($current_limit['request_count'] >= $limit_config['limit']) {
                    $this->logSecurityEvent($user_id, 'rate_limit_exceeded', $action_type, 'denied', 
                        "Rate limit exceeded: {$current_limit['request_count']}/{$limit_config['limit']}");
                    
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'reset_time' => strtotime($current_limit['window_start']) + $limit_config['window'],
                        'message' => "Rate limit exceeded. Try again in " . 
                                   ceil((strtotime($current_limit['window_start']) + $limit_config['window'] - time()) / 60) . " minutes."
                    ];
                }
                
                // Update request count
                $this->db->prepare("
                    UPDATE security_rate_limits 
                    SET request_count = request_count + 1, last_request = NOW() 
                    WHERE ip_address = ? AND action_type = ? AND window_start >= ?
                ")->execute([$ip_address, $action_type, $window_start]);
                
                $remaining = $limit_config['limit'] - $current_limit['request_count'] - 1;
                
            } else {
                // Create new rate limit record
                $this->db->prepare("
                    INSERT INTO security_rate_limits (ip_address, user_id, action_type, request_count, window_start)
                    VALUES (?, ?, ?, 1, NOW())
                ")->execute([$ip_address, $user_id, $action_type]);
                
                $remaining = $limit_config['limit'] - 1;
            }
            
            return [
                'allowed' => true,
                'remaining' => $remaining,
                'limit' => $limit_config['limit'],
                'window' => $limit_config['window']
            ];
            
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            // Allow request on error to avoid blocking legitimate users
            return ['allowed' => true, 'remaining' => 999];
        }
    }
    
    /**
     * Validate session integrity
     */
    private function validateSessionIntegrity() {
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Validate session fingerprint
        $current_fingerprint = $this->generateSessionFingerprint();
        if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $current_fingerprint) {
            return false;
        }
        
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $current_fingerprint;
        }
        
        return true;
    }
    
    /**
     * Detect potential session hijacking
     */
    private function detectSessionHijacking() {
        $suspicious_indicators = 0;
        
        // Check for rapid IP changes
        if (isset($_SESSION['last_ip'])) {
            if ($_SESSION['last_ip'] !== $this->getClientIP()) {
                $suspicious_indicators++;
            }
        } else {
            $_SESSION['last_ip'] = $this->getClientIP();
        }
        
        // Check for user agent changes
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $current_ua) {
                $suspicious_indicators++;
            }
        } else {
            $_SESSION['user_agent'] = $current_ua;
        }
        
        // Check for unusual request patterns
        if (!isset($_SESSION['request_count'])) {
            $_SESSION['request_count'] = 0;
            $_SESSION['request_window_start'] = time();
        }
        
        $_SESSION['request_count']++;
        
        // Reset counter every 5 minutes
        if (time() - $_SESSION['request_window_start'] > 300) {
            $_SESSION['request_count'] = 1;
            $_SESSION['request_window_start'] = time();
        }
        
        // Flag if more than 100 requests in 5 minutes
        if ($_SESSION['request_count'] > 100) {
            $suspicious_indicators++;
        }
        
        return $suspicious_indicators >= 2;
    }
    
    /**
     * Generate session fingerprint
     */
    private function generateSessionFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $this->getClientIP()
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($user_id, $action, $resource, $status, $reason) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO security_audit_log 
                (user_id, ip_address, action, resource, status, reason, user_agent, session_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $this->getClientIP(),
                $action,
                $resource,
                $status,
                $reason,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                session_id()
            ]);
            
        } catch (Exception $e) {
            error_log("Security event logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up expired CSRF tokens
     */
    private function cleanupExpiredCSRFTokens() {
        try {
            $this->db->prepare("DELETE FROM csrf_tokens WHERE expires_at < NOW()")->execute();
        } catch (Exception $e) {
            error_log("CSRF token cleanup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get security statistics for monitoring
     */
    public function getSecurityStats($hours = 24) {
        try {
            $since = date('Y-m-d H:i:s', time() - ($hours * 3600));
            
            $stmt = $this->db->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(DISTINCT user_id) as unique_users
                FROM security_audit_log 
                WHERE created_at >= ?
                GROUP BY status
            ");
            
            $stmt->execute([$since]);
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get top suspicious IPs
            $suspicious_ips_stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as event_count,
                    MAX(created_at) as last_event
                FROM security_audit_log 
                WHERE created_at >= ? AND status IN ('denied', 'suspicious')
                GROUP BY ip_address
                ORDER BY event_count DESC
                LIMIT 10
            ");
            
            $suspicious_ips_stmt->execute([$since]);
            $suspicious_ips = $suspicious_ips_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'period_hours' => $hours,
                'stats_by_status' => $stats,
                'suspicious_ips' => $suspicious_ips
            ];
            
        } catch (Exception $e) {
            error_log("Security stats retrieval failed: " . $e->getMessage());
            return ['error' => 'Failed to retrieve security statistics'];
        }
    }
    
    /**
     * Block suspicious IP addresses
     */
    public function blockSuspiciousIP($ip_address, $reason, $duration_hours = 24) {
        try {
            // This would typically integrate with a firewall or web server configuration
            // For now, we'll log the block request
            $this->logSecurityEvent(null, 'ip_block_requested', $ip_address, 'denied', 
                "Block requested: {$reason} (Duration: {$duration_hours}h)");
            
            return true;
            
        } catch (Exception $e) {
            error_log("IP blocking failed: " . $e->getMessage());
            return false;
        }
    }
}
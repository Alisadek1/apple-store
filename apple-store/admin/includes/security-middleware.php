<?php
/**
 * Security Middleware for Admin Functions
 * 
 * Provides session validation and security checks for all administrative functions
 */

require_once __DIR__ . '/../../includes/security-manager.php';

class AdminSecurityMiddleware {
    
    private $security;
    private $required_permissions = [
        'password_diagnostics' => ['admin'],
        'admin_diagnostics' => ['admin'],
        'auth_monitoring' => ['admin'],
        'user_management' => ['admin'],
        'system_settings' => ['admin'],
        'repair_operations' => ['admin']
    ];
    
    public function __construct() {
        $this->security = new SecurityManager();
    }
    
    /**
     * Validate admin session and permissions
     */
    public function validateAdminSession($required_permission = null) {
        // Start session if not already started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Basic admin access validation
        $access_validation = $this->security->validateAdminAccess($required_permission);
        if (!$access_validation['valid']) {
            $this->handleAccessDenied($access_validation['errors']);
        }
        
        // Additional permission checks
        if ($required_permission && !$this->hasPermission($required_permission)) {
            $this->handleAccessDenied(['Insufficient permissions for: ' . $required_permission]);
        }
        
        // Session regeneration for security (every 15 minutes)
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 900) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        return $access_validation;
    }
    
    /**
     * Check if user has specific permission
     */
    private function hasPermission($permission) {
        if (!isset($this->required_permissions[$permission])) {
            return true; // Allow if permission not defined
        }
        
        $required_roles = $this->required_permissions[$permission];
        $user_role = $_SESSION['role'] ?? '';
        
        return in_array($user_role, $required_roles);
    }
    
    /**
     * Handle access denied scenarios
     */
    private function handleAccessDenied($errors) {
        // Log the access attempt
        error_log("Admin access denied: " . implode(', ', $errors) . 
                 " - IP: " . $this->getClientIP() . 
                 " - User: " . ($_SESSION['user_id'] ?? 'unknown'));
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Access denied',
                'redirect' => ADMIN_URL . '/login.php'
            ]);
            exit;
        }
        
        // Regular HTTP request
        http_response_code(403);
        header('Location: ' . ADMIN_URL . '/login.php?error=access_denied');
        exit;
    }
    
    /**
     * Validate CSRF token for admin operations
     */
    public function validateCSRFToken($token, $action) {
        return $this->security->validateCSRFToken($token, $action);
    }
    
    /**
     * Generate CSRF token for admin operations
     */
    public function generateCSRFToken($action, $expires_in_minutes = 30) {
        return $this->security->generateCSRFToken($action, $expires_in_minutes);
    }
    
    /**
     * Check rate limits for admin operations
     */
    public function checkRateLimit($action_type) {
        return $this->security->checkRateLimit($action_type);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
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
    public function logSecurityEvent($action, $resource, $status, $reason) {
        $user_id = $_SESSION['user_id'] ?? null;
        // This would call the security manager's logging method
        // For now, we'll use error_log
        error_log("Security Event - User: {$user_id}, Action: {$action}, Resource: {$resource}, Status: {$status}, Reason: {$reason}");
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStats($hours = 24) {
        return $this->security->getSecurityStats($hours);
    }
    
    /**
     * Validate file upload security
     */
    public function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Invalid file upload'];
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['valid' => false, 'error' => 'File too large (max 5MB)'];
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        if (!isset($allowed_mimes[$file_extension]) || 
            $mime_type !== $allowed_mimes[$file_extension]) {
            return ['valid' => false, 'error' => 'File type mismatch'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data, $type = 'string') {
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT);
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($data, FILTER_VALIDATE_URL);
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            case 'string':
            default:
                return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Generate secure random token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// Global middleware instance
$adminSecurity = new AdminSecurityMiddleware();

/**
 * Helper function to validate admin access
 */
function validateAdminAccess($permission = null) {
    global $adminSecurity;
    return $adminSecurity->validateAdminSession($permission);
}

/**
 * Helper function to generate CSRF token
 */
function generateAdminCSRFToken($action, $expires_in_minutes = 30) {
    global $adminSecurity;
    return $adminSecurity->generateCSRFToken($action, $expires_in_minutes);
}

/**
 * Helper function to validate CSRF token
 */
function validateAdminCSRFToken($token, $action) {
    global $adminSecurity;
    return $adminSecurity->validateCSRFToken($token, $action);
}

/**
 * Helper function to check rate limits
 */
function checkAdminRateLimit($action_type) {
    global $adminSecurity;
    return $adminSecurity->checkRateLimit($action_type);
}
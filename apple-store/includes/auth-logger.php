<?php
/**
 * Comprehensive Authentication Error Logging System
 * 
 * Provides secure logging with error categorization, monitoring capabilities,
 * and sensitive data masking for authentication failures and security events.
 */

/**
 * Authentication error categories
 */
define('AUTH_ERROR_CATEGORIES', [
    'FORMAT' => 'AUTH_HASH_FORMAT_INVALID',
    'VERIFICATION' => 'AUTH_VERIFICATION_FAILED', 
    'DATABASE' => 'AUTH_DATABASE_ENCODING',
    'CORRUPTION' => 'AUTH_HASH_CORRUPTED',
    'LOGIN_FAILED' => 'AUTH_LOGIN_FAILED',
    'LOGIN_SUCCESS' => 'AUTH_LOGIN_SUCCESS',
    'SECURITY' => 'AUTH_SECURITY_EVENT',
    'REPAIR' => 'AUTH_HASH_REPAIR',
    'SYSTEM' => 'AUTH_SYSTEM_ERROR'
]);

/**
 * Log levels for authentication events
 */
define('AUTH_LOG_LEVELS', [
    'DEBUG' => 0,
    'INFO' => 1,
    'WARNING' => 2,
    'ERROR' => 3,
    'CRITICAL' => 4
]);

/**
 * Enhanced authentication event logger with comprehensive error categorization
 * 
 * @param string $event_type Type of authentication event (use AUTH_ERROR_CATEGORIES constants)
 * @param int|null $user_id User ID (if available)
 * @param array $details Additional details (sensitive data will be masked)
 * @param string $level Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
 * @param array $context Additional context information
 */
function logAuthenticationEvent($event_type, $user_id = null, $details = [], $level = 'INFO', $context = []) {
    // Create comprehensive log entry
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'category' => categorizeAuthEvent($event_type),
        'level' => $level,
        'user_id' => $user_id,
        'session_id' => session_id() ?: 'no_session',
        'ip_address' => getClientIpAddress(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ];
    
    // Add context information
    if (!empty($context)) {
        $log_entry['context'] = $context;
    }
    
    // Mask sensitive information in details
    $masked_details = maskSensitiveData($details);
    $log_entry['details'] = $masked_details;
    
    // Add additional metadata based on event type
    $log_entry = enrichLogEntry($log_entry, $event_type, $details);
    
    // Write to appropriate log files
    writeToLogFiles($log_entry);
    
    // Store in database for monitoring (if enabled)
    storeLogInDatabase($log_entry);
    
    // Check for monitoring alerts
    checkMonitoringThresholds($log_entry);
}

/**
 * Categorize authentication event type
 * 
 * @param string $event_type Event type
 * @return string Category
 */
function categorizeAuthEvent($event_type) {
    $category_map = [
        'AUTH_HASH_FORMAT_INVALID' => 'FORMAT',
        'AUTH_VERIFICATION_FAILED' => 'VERIFICATION',
        'AUTH_DATABASE_ENCODING' => 'DATABASE',
        'AUTH_HASH_CORRUPTED' => 'CORRUPTION',
        'LOGIN_ATTEMPT' => 'LOGIN_FAILED',
        'LOGIN_FAILED_VALIDATION' => 'LOGIN_FAILED',
        'LOGIN_FAILED_USER_NOT_FOUND' => 'LOGIN_FAILED',
        'LOGIN_FAILED_AUTH' => 'LOGIN_FAILED',
        'LOGIN_SUCCESS' => 'LOGIN_SUCCESS',
        'LOGIN_SUCCESS_FALLBACK' => 'LOGIN_SUCCESS',
        'HASH_REPAIR_SUCCESS' => 'REPAIR',
        'HASH_REPAIR_FAILED' => 'REPAIR',
        'HASH_ROLLBACK_SUCCESS' => 'REPAIR',
        'HASH_ROLLBACK_FAILED' => 'REPAIR',
        'EMERGENCY_ACCESS_USED' => 'SECURITY',
        'HASH_WHITESPACE_REPAIRED' => 'REPAIR'
    ];
    
    return $category_map[$event_type] ?? 'SYSTEM';
}

/**
 * Mask sensitive data in log details
 * 
 * @param array $details Original details
 * @return array Masked details
 */
function maskSensitiveData($details) {
    $masked = $details;
    
    // Mask password hashes - show only first 10 characters
    if (isset($masked['hash'])) {
        $masked['hash'] = substr($masked['hash'], 0, 10) . '...[masked]';
    }
    
    if (isset($masked['old_hash'])) {
        $masked['old_hash'] = substr($masked['old_hash'], 0, 10) . '...[masked]';
    }
    
    if (isset($masked['new_hash'])) {
        $masked['new_hash'] = substr($masked['new_hash'], 0, 10) . '...[masked]';
    }
    
    // Completely mask passwords
    if (isset($masked['password'])) {
        $masked['password'] = '[masked]';
    }
    
    if (isset($masked['new_password'])) {
        $masked['new_password'] = '[masked]';
    }
    
    // Mask email addresses partially (keep domain visible for debugging)
    if (isset($masked['email'])) {
        $email_parts = explode('@', $masked['email']);
        if (count($email_parts) === 2) {
            $username = $email_parts[0];
            $domain = $email_parts[1];
            $masked_username = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
            $masked['email'] = $masked_username . '@' . $domain;
        }
    }
    
    // Mask IP addresses partially (keep network portion)
    if (isset($masked['ip_address']) && filter_var($masked['ip_address'], FILTER_VALIDATE_IP)) {
        $ip_parts = explode('.', $masked['ip_address']);
        if (count($ip_parts) === 4) {
            $masked['ip_address'] = $ip_parts[0] . '.' . $ip_parts[1] . '.***.' . $ip_parts[3];
        }
    }
    
    // Mask session IDs partially
    if (isset($masked['session_id']) && strlen($masked['session_id']) > 8) {
        $masked['session_id'] = substr($masked['session_id'], 0, 8) . '...[masked]';
    }
    
    return $masked;
}

/**
 * Enrich log entry with additional metadata based on event type
 * 
 * @param array $log_entry Base log entry
 * @param string $event_type Event type
 * @param array $original_details Original details before masking
 * @return array Enriched log entry
 */
function enrichLogEntry($log_entry, $event_type, $original_details) {
    // Add hash analysis for hash-related events
    if (in_array($log_entry['category'], ['FORMAT', 'VERIFICATION', 'CORRUPTION', 'REPAIR'])) {
        if (isset($original_details['hash'])) {
            $log_entry['hash_analysis'] = [
                'length' => strlen($original_details['hash']),
                'prefix' => substr($original_details['hash'], 0, 7),
                'has_whitespace' => preg_match('/\s/', $original_details['hash']) ? true : false,
                'encoding_valid' => mb_check_encoding($original_details['hash'], 'UTF-8')
            ];
        }
    }
    
    // Add failure analysis for verification failures
    if ($event_type === 'AUTH_VERIFICATION_FAILED' && isset($original_details['failure_analysis'])) {
        $log_entry['failure_summary'] = [
            'hash_length' => $original_details['failure_analysis']['hash_analysis']['hash_length'] ?? null,
            'encoding_check' => $original_details['failure_analysis']['hash_analysis']['encoding_check'] ?? null,
            'recommendations_count' => count($original_details['failure_analysis']['recommendations'] ?? [])
        ];
    }
    
    // Add database context for database-related events
    if ($log_entry['category'] === 'DATABASE') {
        $log_entry['database_context'] = [
            'php_version' => PHP_VERSION,
            'charset_functions_available' => function_exists('mb_check_encoding'),
            'password_functions_available' => function_exists('password_verify')
        ];
    }
    
    // Add security context for security events
    if ($log_entry['category'] === 'SECURITY') {
        $log_entry['security_context'] = [
            'is_https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null
        ];
    }
    
    return $log_entry;
}

/**
 * Get client IP address with proxy support
 * 
 * @return string Client IP address
 */
function getClientIpAddress() {
    $ip_headers = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (from proxies)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Write log entry to appropriate log files
 * 
 * @param array $log_entry Log entry to write
 */
function writeToLogFiles($log_entry) {
    $log_dir = __DIR__ . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Main authentication log
    $main_log_file = $log_dir . '/auth.log';
    $log_message = formatLogMessage($log_entry) . PHP_EOL;
    file_put_contents($main_log_file, $log_message, FILE_APPEND | LOCK_EX);
    
    // Category-specific logs
    $category = strtolower($log_entry['category']);
    $category_log_file = $log_dir . '/auth_' . $category . '.log';
    file_put_contents($category_log_file, $log_message, FILE_APPEND | LOCK_EX);
    
    // Error-level logs (WARNING, ERROR, CRITICAL)
    if (in_array($log_entry['level'], ['WARNING', 'ERROR', 'CRITICAL'])) {
        $error_log_file = $log_dir . '/auth_errors.log';
        file_put_contents($error_log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
    
    // Daily log rotation
    $daily_log_file = $log_dir . '/auth_' . date('Y-m-d') . '.log';
    file_put_contents($daily_log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Format log message for file output
 * 
 * @param array $log_entry Log entry
 * @return string Formatted log message
 */
function formatLogMessage($log_entry) {
    $base_info = sprintf(
        '[%s] [%s] [%s] [User:%s] [IP:%s]',
        $log_entry['timestamp'],
        $log_entry['level'],
        $log_entry['event_type'],
        $log_entry['user_id'] ?? 'N/A',
        $log_entry['ip_address']
    );
    
    // Add details as JSON for structured logging
    $details_json = json_encode($log_entry['details'], JSON_UNESCAPED_SLASHES);
    
    return $base_info . ' ' . $details_json;
}

/**
 * Store log entry in database for monitoring and analysis
 * 
 * @param array $log_entry Log entry to store
 */
function storeLogInDatabase($log_entry) {
    try {
        // Only store if database logging is enabled and for important events
        if (!shouldStoreInDatabase($log_entry)) {
            return;
        }
        
        $db = getDB();
        
        // Ensure auth_logs table exists
        createAuthLogsTable();
        
        $stmt = $db->prepare("
            INSERT INTO auth_logs 
            (timestamp, event_type, category, level, user_id, session_id, 
             ip_address, user_agent, request_uri, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $log_entry['timestamp'],
            $log_entry['event_type'],
            $log_entry['category'],
            $log_entry['level'],
            $log_entry['user_id'],
            $log_entry['session_id'],
            $log_entry['ip_address'],
            substr($log_entry['user_agent'], 0, 500), // Limit user agent length
            substr($log_entry['request_uri'], 0, 255), // Limit URI length
            json_encode($log_entry['details'])
        ]);
        
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_log("Auth logging database error: " . $e->getMessage());
    }
}

/**
 * Determine if log entry should be stored in database
 * 
 * @param array $log_entry Log entry
 * @return bool Whether to store in database
 */
function shouldStoreInDatabase($log_entry) {
    // Store all ERROR and CRITICAL level events
    if (in_array($log_entry['level'], ['ERROR', 'CRITICAL'])) {
        return true;
    }
    
    // Store all security events
    if ($log_entry['category'] === 'SECURITY') {
        return true;
    }
    
    // Store login attempts and results
    if (in_array($log_entry['category'], ['LOGIN_FAILED', 'LOGIN_SUCCESS'])) {
        return true;
    }
    
    // Store hash repair events
    if ($log_entry['category'] === 'REPAIR') {
        return true;
    }
    
    return false;
}

/**
 * Create auth_logs table if it doesn't exist
 */
function createAuthLogsTable() {
    try {
        $db = getDB();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS auth_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            category ENUM('FORMAT', 'VERIFICATION', 'DATABASE', 'CORRUPTION', 'LOGIN_FAILED', 'LOGIN_SUCCESS', 'SECURITY', 'REPAIR', 'SYSTEM') NOT NULL,
            level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            request_uri VARCHAR(255),
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_timestamp (timestamp),
            INDEX idx_event_type (event_type),
            INDEX idx_category (category),
            INDEX idx_level (level),
            INDEX idx_user_id (user_id),
            INDEX idx_ip_address (ip_address),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        
    } catch (Exception $e) {
        error_log("Failed to create auth_logs table: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Check monitoring thresholds and trigger alerts if necessary
 * 
 * @param array $log_entry Log entry to check
 */
function checkMonitoringThresholds($log_entry) {
    // Check for repeated failed login attempts
    if ($log_entry['category'] === 'LOGIN_FAILED') {
        checkFailedLoginThreshold($log_entry);
    }
    
    // Check for hash corruption patterns
    if ($log_entry['category'] === 'CORRUPTION') {
        checkCorruptionThreshold($log_entry);
    }
    
    // Check for security events
    if ($log_entry['category'] === 'SECURITY') {
        checkSecurityThreshold($log_entry);
    }
    
    // Check for system errors
    if ($log_entry['level'] === 'CRITICAL') {
        checkCriticalErrorThreshold($log_entry);
    }
}

/**
 * Check failed login attempt thresholds
 * 
 * @param array $log_entry Log entry
 */
function checkFailedLoginThreshold($log_entry) {
    try {
        $db = getDB();
        
        // Check failed attempts in last 15 minutes from same IP
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempt_count
            FROM auth_logs 
            WHERE category = 'LOGIN_FAILED' 
            AND ip_address = ? 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$log_entry['ip_address']]);
        $result = $stmt->fetch();
        
        if ($result && $result['attempt_count'] >= 5) {
            // Trigger security alert
            logAuthenticationEvent('AUTH_SECURITY_ALERT', null, [
                'alert_type' => 'multiple_failed_logins',
                'ip_address' => $log_entry['ip_address'],
                'attempt_count' => $result['attempt_count'],
                'time_window' => '15_minutes'
            ], 'CRITICAL');
        }
        
    } catch (Exception $e) {
        error_log("Failed login threshold check error: " . $e->getMessage());
    }
}

/**
 * Check hash corruption thresholds
 * 
 * @param array $log_entry Log entry
 */
function checkCorruptionThreshold($log_entry) {
    try {
        $db = getDB();
        
        // Check corruption events in last hour
        $stmt = $db->prepare("
            SELECT COUNT(*) as corruption_count
            FROM auth_logs 
            WHERE category = 'CORRUPTION' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['corruption_count'] >= 3) {
            // Trigger database integrity alert
            logAuthenticationEvent('AUTH_SYSTEM_ALERT', null, [
                'alert_type' => 'multiple_hash_corruptions',
                'corruption_count' => $result['corruption_count'],
                'time_window' => '1_hour',
                'recommendation' => 'Check database integrity and charset settings'
            ], 'CRITICAL');
        }
        
    } catch (Exception $e) {
        error_log("Corruption threshold check error: " . $e->getMessage());
    }
}

/**
 * Check security event thresholds
 * 
 * @param array $log_entry Log entry
 */
function checkSecurityThreshold($log_entry) {
    // Any security event is considered important
    if ($log_entry['event_type'] === 'EMERGENCY_ACCESS_USED') {
        // Emergency access should be rare and monitored closely
        logAuthenticationEvent('AUTH_SECURITY_ALERT', $log_entry['user_id'], [
            'alert_type' => 'emergency_access_detected',
            'original_event' => $log_entry['event_type'],
            'recommendation' => 'Review emergency access usage and disable if not needed'
        ], 'CRITICAL');
    }
}

/**
 * Check critical error thresholds
 * 
 * @param array $log_entry Log entry
 */
function checkCriticalErrorThreshold($log_entry) {
    try {
        $db = getDB();
        
        // Check critical errors in last 30 minutes
        $stmt = $db->prepare("
            SELECT COUNT(*) as error_count
            FROM auth_logs 
            WHERE level = 'CRITICAL' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['error_count'] >= 3) {
            // Log system alert (avoid infinite recursion by checking event type)
            if ($log_entry['event_type'] !== 'AUTH_SYSTEM_ALERT') {
                logAuthenticationEvent('AUTH_SYSTEM_ALERT', null, [
                    'alert_type' => 'multiple_critical_errors',
                    'error_count' => $result['error_count'],
                    'time_window' => '30_minutes',
                    'recommendation' => 'System requires immediate attention'
                ], 'CRITICAL');
            }
        }
        
    } catch (Exception $e) {
        error_log("Critical error threshold check error: " . $e->getMessage());
    }
}

/**
 * Get authentication failure statistics for monitoring
 * 
 * @param string $time_period Time period (1h, 24h, 7d, 30d)
 * @return array Statistics
 */
function getAuthFailureStats($time_period = '24h') {
    $stats = [
        'success' => false,
        'period' => $time_period,
        'total_events' => 0,
        'by_category' => [],
        'by_level' => [],
        'top_ips' => [],
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        // Convert time period to MySQL interval
        $interval_map = [
            '1h' => 'INTERVAL 1 HOUR',
            '24h' => 'INTERVAL 24 HOUR', 
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY'
        ];
        
        $interval = $interval_map[$time_period] ?? 'INTERVAL 24 HOUR';
        
        // Total events
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_count
            FROM auth_logs 
            WHERE timestamp >= DATE_SUB(NOW(), {$interval})
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['total_events'] = $result['total_count'] ?? 0;
        
        // Events by category
        $stmt = $db->prepare("
            SELECT category, COUNT(*) as count
            FROM auth_logs 
            WHERE timestamp >= DATE_SUB(NOW(), {$interval})
            GROUP BY category
            ORDER BY count DESC
        ");
        $stmt->execute();
        $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Events by level
        $stmt = $db->prepare("
            SELECT level, COUNT(*) as count
            FROM auth_logs 
            WHERE timestamp >= DATE_SUB(NOW(), {$interval})
            GROUP BY level
            ORDER BY count DESC
        ");
        $stmt->execute();
        $stats['by_level'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Top IPs with failed attempts
        $stmt = $db->prepare("
            SELECT ip_address, COUNT(*) as count
            FROM auth_logs 
            WHERE timestamp >= DATE_SUB(NOW(), {$interval})
            AND category = 'LOGIN_FAILED'
            GROUP BY ip_address
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $stats['top_ips'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['success'] = true;
        
    } catch (Exception $e) {
        $stats['error'] = $e->getMessage();
    }
    
    return $stats;
}

/**
 * Clean old log entries from database (for maintenance)
 * 
 * @param int $days_to_keep Number of days to keep logs
 * @return array Cleanup result
 */
function cleanupOldAuthLogs($days_to_keep = 90) {
    $result = [
        'success' => false,
        'deleted_count' => 0,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            DELETE FROM auth_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days_to_keep]);
        
        $result['deleted_count'] = $stmt->rowCount();
        $result['success'] = true;
        
        // Log the cleanup
        logAuthenticationEvent('AUTH_LOG_CLEANUP', null, [
            'deleted_count' => $result['deleted_count'],
            'days_kept' => $days_to_keep
        ], 'INFO');
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

?>
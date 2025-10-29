<?php
/**
 * Enhanced Authentication Functions
 * 
 * Provides secure password verification utilities with diagnostic capabilities
 * for troubleshooting authentication issues and hash validation.
 */

/**
 * Secure password verification with hash validation and diagnostic capabilities
 * 
 * @param string $password Plain text password to verify
 * @param string $hash Stored password hash
 * @param int|null $user_id Optional user ID for logging purposes
 * @return array Result array with success status and diagnostic information
 */
function verifyPasswordSecure($password, $hash, $user_id = null) {
    $result = [
        'success' => false,
        'hash_valid' => false,
        'verification_result' => false,
        'diagnostics' => [],
        'user_id' => $user_id
    ];
    
    // Step 1: Validate hash format
    $hash_validation = validateHashFormat($hash);
    $result['hash_valid'] = $hash_validation['valid'];
    $result['diagnostics']['hash_format'] = $hash_validation;
    
    if (!$hash_validation['valid']) {
        $result['diagnostics']['failure_reason'] = 'Invalid hash format';
        
        // Log hash format error with comprehensive logging
        logAuthEvent('AUTH_HASH_FORMAT_INVALID', $user_id, [
            'hash_length' => strlen($hash),
            'hash_prefix' => substr($hash, 0, 10),
            'format_issues' => $hash_validation['issues'],
            'hash' => $hash
        ], 'ERROR');
        
        return $result;
    }
    
    // Step 2: Attempt password verification
    $verification_result = password_verify($password, $hash);
    $result['verification_result'] = $verification_result;
    
    if ($verification_result) {
        $result['success'] = true;
        $result['diagnostics']['verification_status'] = 'Password verification successful';
        
        // Log successful verification
        logAuthEvent('AUTH_VERIFICATION_SUCCESS', $user_id, [
            'hash_length' => strlen($hash),
            'verification_method' => 'standard'
        ], 'DEBUG');
        
    } else {
        // Step 3: Perform detailed failure analysis
        $failure_analysis = diagnoseVerificationFailure($password, $hash);
        $result['diagnostics']['failure_analysis'] = $failure_analysis;
        $result['diagnostics']['failure_reason'] = 'Password verification failed';
        
        // Log verification failure with comprehensive details
        logAuthEvent('AUTH_VERIFICATION_FAILED', $user_id, [
            'hash' => $hash,
            'password_length' => strlen($password),
            'failure_analysis' => $failure_analysis,
            'hash_encoding_valid' => mb_check_encoding($hash, 'UTF-8'),
            'hash_has_whitespace' => preg_match('/\s/', $hash) ? true : false
        ], 'WARNING');
    }
    
    return $result;
}

/**
 * Validate bcrypt hash format compliance
 * 
 * @param string $hash Hash to validate
 * @return array Validation result with detailed information
 */
function validateHashFormat($hash) {
    $result = [
        'valid' => false,
        'length' => strlen($hash),
        'expected_length' => 60,
        'prefix' => substr($hash, 0, 7),
        'expected_prefix' => '$2y$10$',
        'issues' => []
    ];    

    // Check if hash is empty or null
    if (empty($hash)) {
        $result['issues'][] = 'Hash is empty or null';
        return $result;
    }
    
    // Check hash length (bcrypt hashes should be exactly 60 characters)
    if ($result['length'] !== 60) {
        $result['issues'][] = "Invalid length: {$result['length']} (expected 60)";
    }
    
    // Check hash prefix (should start with $2y$10$ for bcrypt)
    if ($result['prefix'] !== '$2y$10$') {
        $result['issues'][] = "Invalid prefix: '{$result['prefix']}' (expected '\$2y\$10\$')";
    }
    
    // Check for whitespace issues
    $trimmed_hash = trim($hash);
    if ($hash !== $trimmed_hash) {
        $result['issues'][] = 'Hash contains leading or trailing whitespace';
    }
    
    // Check character set (bcrypt uses base64 alphabet + ./)
    $valid_chars = '/^[\$2ya-zA-Z0-9\.\/]+$/';
    if (!preg_match($valid_chars, $hash)) {
        $result['issues'][] = 'Hash contains invalid characters';
    }
    
    // Check salt and hash portions
    if (strlen($hash) === 60) {
        $salt_portion = substr($hash, 7, 22); // Characters 7-28 (22 chars)
        $hash_portion = substr($hash, 29, 31); // Characters 29-59 (31 chars)
        
        if (strlen($salt_portion) !== 22) {
            $result['issues'][] = 'Invalid salt portion length';
        }
        
        if (strlen($hash_portion) !== 31) {
            $result['issues'][] = 'Invalid hash portion length';
        }
    }
    
    // Hash is valid if no issues found
    $result['valid'] = empty($result['issues']);
    
    return $result;
}

/**
 * Perform detailed failure analysis for password verification
 * 
 * @param string $password Plain text password
 * @param string $hash Stored hash that failed verification
 * @return array Detailed diagnostic information
 */
function diagnoseVerificationFailure($password, $hash) {
    $diagnosis = [
        'timestamp' => date('Y-m-d H:i:s'),
        'password_length' => strlen($password),
        'hash_analysis' => [],
        'environment_check' => [],
        'recommendations' => []
    ];
    
    // Analyze the hash in detail
    $diagnosis['hash_analysis'] = [
        'original_hash' => $hash,
        'trimmed_hash' => trim($hash),
        'hash_length' => strlen($hash),
        'hash_prefix' => substr($hash, 0, 10),
        'encoding_check' => mb_check_encoding($hash, 'UTF-8'),
        'contains_null_bytes' => strpos($hash, "\0") !== false
    ];
    
    // Check PHP environment
    $diagnosis['environment_check'] = [
        'php_version' => PHP_VERSION,
        'password_hash_available' => function_exists('password_hash'),
        'password_verify_available' => function_exists('password_verify'),
        'bcrypt_supported' => defined('PASSWORD_BCRYPT'),
        'default_algo' => PASSWORD_DEFAULT
    ];
    
    // Test with a known good hash for comparison
    $test_password = 'test123';
    $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
    $test_verify = password_verify($test_password, $test_hash);
    
    $diagnosis['environment_check']['test_hash_verify'] = $test_verify;
    $diagnosis['environment_check']['test_hash_generated'] = $test_hash;
    
    // Generate recommendations based on findings
    if (!$diagnosis['hash_analysis']['encoding_check']) {
        $diagnosis['recommendations'][] = 'Hash encoding issue detected - check database charset';
    }
    
    if ($diagnosis['hash_analysis']['contains_null_bytes']) {
        $diagnosis['recommendations'][] = 'Hash contains null bytes - possible database corruption';
    }
    
    if ($diagnosis['hash_analysis']['hash_length'] !== 60) {
        $diagnosis['recommendations'][] = 'Hash length incorrect - possible truncation in database';
    }
    
    if ($diagnosis['hash_analysis']['original_hash'] !== $diagnosis['hash_analysis']['trimmed_hash']) {
        $diagnosis['recommendations'][] = 'Hash has whitespace - trim before verification';
    }
    
    if (!$diagnosis['environment_check']['test_hash_verify']) {
        $diagnosis['recommendations'][] = 'PHP password functions not working correctly';
    } else {
        $diagnosis['recommendations'][] = 'PHP environment OK - issue likely with stored hash';
    }
    
    return $diagnosis;
}

/**
 * Validate database column specifications for password storage
 * 
 * @return array Column validation result with detailed information
 */
function validatePasswordColumnSpecs() {
    $result = [
        'valid' => false,
        'column_info' => null,
        'issues' => [],
        'recommendations' => []
    ];
    
    try {
        $db = getDB();
        
        // Get column specifications
        $stmt = $db->query("SHOW FULL COLUMNS FROM users WHERE Field = 'password'");
        $column_info = $stmt->fetch();
        
        if (!$column_info) {
            $result['issues'][] = 'Password column not found in users table';
            return $result;
        }
        
        $result['column_info'] = $column_info;
        
        // Validate column type and length
        $type = strtoupper($column_info['Type']);
        if (!preg_match('/VARCHAR\((\d+)\)/', $type, $matches)) {
            $result['issues'][] = 'Password column is not VARCHAR type';
        } else {
            $length = (int)$matches[1];
            if ($length < 60) {
                $result['issues'][] = "VARCHAR length ({$length}) is insufficient for bcrypt hashes (minimum 60)";
                $result['recommendations'][] = "ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL";
            }
        }
        
        // Validate collation
        $collation = $column_info['Collation'];
        if ($collation && !in_array($collation, ['utf8mb4_unicode_ci', 'utf8mb4_general_ci', 'utf8_unicode_ci'])) {
            $result['issues'][] = "Collation '{$collation}' may cause encoding issues";
            $result['recommendations'][] = "ALTER TABLE users MODIFY password VARCHAR(255) COLLATE utf8mb4_unicode_ci";
        }
        
        // Check if column allows NULL
        if ($column_info['Null'] === 'YES') {
            $result['issues'][] = 'Password column allows NULL values';
            $result['recommendations'][] = "ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL";
        }
        
        // Get database charset and collation
        $db_charset = $db->query("SELECT @@character_set_database")->fetchColumn();
        $db_collation = $db->query("SELECT @@collation_database")->fetchColumn();
        
        $result['database_charset'] = $db_charset;
        $result['database_collation'] = $db_collation;
        
        // Validate database charset
        if (!in_array($db_charset, ['utf8mb4', 'utf8'])) {
            $result['issues'][] = "Database charset '{$db_charset}' may cause encoding issues";
            $result['recommendations'][] = "Consider using utf8mb4 charset for better Unicode support";
        }
        
        $result['valid'] = empty($result['issues']);
        
    } catch (Exception $e) {
        $result['issues'][] = 'Database error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Retrieve password hash with encoding and whitespace validation
 * 
 * @param int $user_id User ID to retrieve hash for
 * @return array Hash retrieval result with validation information
 */
function retrieveHashWithValidation($user_id) {
    $result = [
        'success' => false,
        'hash' => null,
        'raw_hash' => null,
        'encoding_issues' => [],
        'whitespace_issues' => [],
        'corruption_detected' => false,
        'user_info' => null
    ];
    
    try {
        $db = getDB();
        
        // Retrieve hash with additional metadata
        $stmt = $db->prepare("
            SELECT id, email, password, 
                   LENGTH(password) as hash_length,
                   CHAR_LENGTH(password) as char_length,
                   HEX(password) as hex_representation
            FROM users WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            $result['error'] = 'User not found';
            
            // Log database retrieval failure
            logAuthEvent('AUTH_DATABASE_ERROR', $user_id, [
                'error' => 'User not found',
                'operation' => 'hash_retrieval'
            ], 'WARNING');
            
            return $result;
        }
        
        $result['user_info'] = [
            'id' => $user_data['id'],
            'email' => $user_data['email']
        ];
        
        $raw_hash = $user_data['password'];
        $result['raw_hash'] = $raw_hash;
        
        // Check for encoding issues
        if (!mb_check_encoding($raw_hash, 'UTF-8')) {
            $result['encoding_issues'][] = 'Hash contains invalid UTF-8 sequences';
        }
        
        // Check for null bytes
        if (strpos($raw_hash, "\0") !== false) {
            $result['encoding_issues'][] = 'Hash contains null bytes';
            $result['corruption_detected'] = true;
        }
        
        // Check for whitespace issues
        $trimmed_hash = trim($raw_hash);
        if ($raw_hash !== $trimmed_hash) {
            $result['whitespace_issues'][] = 'Hash has leading or trailing whitespace';
            $result['whitespace_issues'][] = 'Original length: ' . strlen($raw_hash) . ', Trimmed length: ' . strlen($trimmed_hash);
        }
        
        // Check for internal whitespace
        if (preg_match('/\s/', $raw_hash)) {
            $result['whitespace_issues'][] = 'Hash contains internal whitespace characters';
        }
        
        // Check length consistency
        if ($user_data['hash_length'] !== $user_data['char_length']) {
            $result['encoding_issues'][] = 'Byte length differs from character length - possible encoding issue';
        }
        
        // Detect truncation
        if (strlen($raw_hash) < 60 && strlen($raw_hash) > 0) {
            $result['corruption_detected'] = true;
            $result['encoding_issues'][] = 'Hash appears truncated (length: ' . strlen($raw_hash) . ')';
        }
        
        // Use trimmed hash for further processing
        $result['hash'] = $trimmed_hash;
        $result['success'] = true;
        
        // Additional corruption checks
        $format_validation = validateHashFormat($trimmed_hash);
        if (!$format_validation['valid']) {
            $result['corruption_detected'] = true;
        }
        
        // Log database encoding issues if detected
        if (!empty($result['encoding_issues']) || !empty($result['whitespace_issues'])) {
            logAuthEvent('AUTH_DATABASE_ENCODING', $user_id, [
                'email' => $user_data['email'],
                'encoding_issues' => $result['encoding_issues'],
                'whitespace_issues' => $result['whitespace_issues'],
                'corruption_detected' => $result['corruption_detected'],
                'hash_length' => strlen($raw_hash),
                'char_length' => $user_data['char_length'],
                'hash' => $raw_hash
            ], $result['corruption_detected'] ? 'ERROR' : 'WARNING');
        }
        
    } catch (Exception $e) {
        $result['error'] = 'Database error: ' . $e->getMessage();
        
        // Log database error
        logAuthEvent('AUTH_DATABASE_ERROR', $user_id, [
            'error' => $e->getMessage(),
            'operation' => 'hash_retrieval',
            'sql_state' => $e->getCode()
        ], 'ERROR');
    }
    
    return $result;
}

/**
 * Detect hash truncation and corruption issues
 * 
 * @param string $hash Hash to analyze for corruption
 * @return array Corruption analysis result
 */
function detectHashCorruption($hash) {
    $analysis = [
        'is_corrupted' => false,
        'corruption_types' => [],
        'severity' => 'none', // none, minor, major, critical
        'repair_possible' => false,
        'details' => []
    ];
    
    // Check for obvious truncation
    $hash_length = strlen($hash);
    if ($hash_length < 60 && $hash_length > 0) {
        $analysis['is_corrupted'] = true;
        $analysis['corruption_types'][] = 'truncation';
        $analysis['severity'] = 'critical';
        $analysis['details'][] = "Hash truncated to {$hash_length} characters (expected 60)";
    }
    
    // Check for format corruption
    $format_validation = validateHashFormat($hash);
    if (!$format_validation['valid']) {
        $analysis['is_corrupted'] = true;
        $analysis['corruption_types'][] = 'format_corruption';
        
        foreach ($format_validation['issues'] as $issue) {
            $analysis['details'][] = $issue;
            
            // Determine severity based on issue type
            if (strpos($issue, 'length') !== false) {
                $analysis['severity'] = 'critical';
            } elseif (strpos($issue, 'prefix') !== false) {
                $analysis['severity'] = 'major';
            } elseif (strpos($issue, 'whitespace') !== false) {
                $analysis['severity'] = 'minor';
                $analysis['repair_possible'] = true;
            }
        }
    }
    
    // Check for character corruption
    if (!empty($hash)) {
        // Check for invalid characters in bcrypt hash
        $valid_chars = '/^[\$2ya-zA-Z0-9\.\/]+$/';
        if (!preg_match($valid_chars, $hash)) {
            $analysis['is_corrupted'] = true;
            $analysis['corruption_types'][] = 'character_corruption';
            $analysis['severity'] = 'major';
            $analysis['details'][] = 'Hash contains invalid characters for bcrypt format';
        }
        
        // Check for encoding issues
        if (!mb_check_encoding($hash, 'UTF-8')) {
            $analysis['is_corrupted'] = true;
            $analysis['corruption_types'][] = 'encoding_corruption';
            $analysis['severity'] = 'major';
            $analysis['details'][] = 'Hash contains invalid UTF-8 encoding';
        }
        
        // Check for null bytes
        if (strpos($hash, "\0") !== false) {
            $analysis['is_corrupted'] = true;
            $analysis['corruption_types'][] = 'null_byte_corruption';
            $analysis['severity'] = 'critical';
            $analysis['details'][] = 'Hash contains null bytes';
        }
    }
    
    // Determine if repair is possible
    if (in_array('minor', [$analysis['severity']]) || 
        (in_array('whitespace', $analysis['corruption_types']) && count($analysis['corruption_types']) === 1)) {
        $analysis['repair_possible'] = true;
    }
    
    // Log corruption detection with comprehensive details
    if ($analysis['is_corrupted']) {
        logAuthEvent('AUTH_HASH_CORRUPTED', null, [
            'hash' => $hash,
            'corruption_types' => $analysis['corruption_types'],
            'severity' => $analysis['severity'],
            'repair_possible' => $analysis['repair_possible'],
            'details' => $analysis['details'],
            'hash_length' => $hash_length
        ], $analysis['severity'] === 'critical' ? 'CRITICAL' : 'ERROR');
    }
    
    return $analysis;
}

/**
 * Validate charset and collation for password storage
 * 
 * @return array Charset and collation validation result
 */
function validateDatabaseCharsetCollation() {
    $result = [
        'valid' => false,
        'database_info' => [],
        'table_info' => [],
        'column_info' => [],
        'issues' => [],
        'recommendations' => []
    ];
    
    try {
        $db = getDB();
        
        // Get database charset and collation
        $result['database_info'] = [
            'charset' => $db->query("SELECT @@character_set_database")->fetchColumn(),
            'collation' => $db->query("SELECT @@collation_database")->fetchColumn()
        ];
        
        // Get table charset and collation
        $stmt = $db->query("SHOW TABLE STATUS WHERE Name = 'users'");
        $table_status = $stmt->fetch();
        if ($table_status) {
            $result['table_info'] = [
                'charset' => $table_status['Collation'] ? explode('_', $table_status['Collation'])[0] : null,
                'collation' => $table_status['Collation']
            ];
        }
        
        // Get password column charset and collation
        $stmt = $db->query("SHOW FULL COLUMNS FROM users WHERE Field = 'password'");
        $column_info = $stmt->fetch();
        if ($column_info) {
            $result['column_info'] = [
                'collation' => $column_info['Collation'],
                'charset' => $column_info['Collation'] ? explode('_', $column_info['Collation'])[0] : null
            ];
        }
        
        // Validate database charset
        $db_charset = $result['database_info']['charset'];
        if (!in_array($db_charset, ['utf8mb4', 'utf8'])) {
            $result['issues'][] = "Database charset '{$db_charset}' may cause encoding issues";
            $result['recommendations'][] = "Consider using utf8mb4 charset";
        }
        
        // Validate table charset
        $table_charset = $result['table_info']['charset'];
        if ($table_charset && $table_charset !== $db_charset) {
            $result['issues'][] = "Table charset '{$table_charset}' differs from database charset '{$db_charset}'";
        }
        
        // Validate column charset
        $column_charset = $result['column_info']['charset'];
        if ($column_charset && $column_charset !== $table_charset) {
            $result['issues'][] = "Password column charset '{$column_charset}' differs from table charset '{$table_charset}'";
        }
        
        // Validate collations
        $recommended_collations = ['utf8mb4_unicode_ci', 'utf8mb4_general_ci', 'utf8_unicode_ci', 'utf8_general_ci'];
        
        $column_collation = $result['column_info']['collation'];
        if ($column_collation && !in_array($column_collation, $recommended_collations)) {
            $result['issues'][] = "Password column collation '{$column_collation}' may cause issues";
            $result['recommendations'][] = "Use utf8mb4_unicode_ci collation for better compatibility";
        }
        
        $result['valid'] = empty($result['issues']);
        
    } catch (Exception $e) {
        $result['issues'][] = 'Database error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Comprehensive database integrity check for password storage
 * 
 * @return array Complete integrity check result
 */
function performDatabaseIntegrityCheck() {
    $result = [
        'overall_status' => 'unknown',
        'column_validation' => null,
        'charset_validation' => null,
        'user_hash_analysis' => [],
        'corruption_summary' => [
            'total_users' => 0,
            'corrupted_hashes' => 0,
            'repairable_issues' => 0,
            'critical_issues' => 0
        ],
        'recommendations' => []
    ];
    
    try {
        // Validate column specifications
        $result['column_validation'] = validatePasswordColumnSpecs();
        
        // Validate charset and collation
        $result['charset_validation'] = validateDatabaseCharsetCollation();
        
        // Analyze user password hashes
        $db = getDB();
        $stmt = $db->query("SELECT id, email FROM users ORDER BY id LIMIT 50");
        $users = $stmt->fetchAll();
        
        $result['corruption_summary']['total_users'] = count($users);
        
        foreach ($users as $user) {
            $hash_retrieval = retrieveHashWithValidation($user['id']);
            $corruption_analysis = null;
            
            if ($hash_retrieval['success'] && $hash_retrieval['hash']) {
                $corruption_analysis = detectHashCorruption($hash_retrieval['hash']);
            }
            
            $user_analysis = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'hash_retrieval' => $hash_retrieval,
                'corruption_analysis' => $corruption_analysis
            ];
            
            // Update corruption summary
            if ($corruption_analysis && $corruption_analysis['is_corrupted']) {
                $result['corruption_summary']['corrupted_hashes']++;
                
                if ($corruption_analysis['repair_possible']) {
                    $result['corruption_summary']['repairable_issues']++;
                }
                
                if ($corruption_analysis['severity'] === 'critical') {
                    $result['corruption_summary']['critical_issues']++;
                }
            }
            
            $result['user_hash_analysis'][] = $user_analysis;
        }
        
        // Compile overall recommendations
        if (!$result['column_validation']['valid']) {
            $result['recommendations'] = array_merge($result['recommendations'], $result['column_validation']['recommendations']);
        }
        
        if (!$result['charset_validation']['valid']) {
            $result['recommendations'] = array_merge($result['recommendations'], $result['charset_validation']['recommendations']);
        }
        
        if ($result['corruption_summary']['corrupted_hashes'] > 0) {
            $result['recommendations'][] = "Found {$result['corruption_summary']['corrupted_hashes']} corrupted password hashes requiring attention";
        }
        
        // Determine overall status
        if ($result['corruption_summary']['critical_issues'] > 0) {
            $result['overall_status'] = 'critical';
        } elseif ($result['corruption_summary']['corrupted_hashes'] > 0 || !$result['column_validation']['valid'] || !$result['charset_validation']['valid']) {
            $result['overall_status'] = 'warning';
        } else {
            $result['overall_status'] = 'good';
        }
        
    } catch (Exception $e) {
        $result['overall_status'] = 'error';
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Repair corrupted password hash with comprehensive backup and audit trail
 * 
 * @param int $user_id User ID to repair hash for
 * @param string|null $new_password New password to hash (if known)
 * @param array $options Repair options (force_repair, backup_location, etc.)
 * @return array Repair result with success status and details
 */
function repairCorruptedHash($user_id, $new_password = null, $options = []) {
    $result = [
        'success' => false,
        'action_taken' => 'none',
        'backup_created' => false,
        'backup_id' => null,
        'new_hash' => null,
        'old_hash' => null,
        'rollback_available' => false,
        'audit_trail_id' => null,
        'error' => null,
        'warnings' => []
    ];
    
    // Validate inputs
    if (!$new_password) {
        $result['error'] = 'Cannot repair hash without knowing the password';
        return $result;
    }
    
    if (!is_numeric($user_id) || $user_id <= 0) {
        $result['error'] = 'Invalid user ID provided';
        return $result;
    }
    
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Step 1: Retrieve current user data
        $stmt = $db->prepare("SELECT id, email, password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_data = $stmt->fetch();
        
        if (!$current_data) {
            $result['error'] = 'User not found';
            $db->rollback();
            return $result;
        }
        
        $result['old_hash'] = $current_data['password'];
        
        // Step 2: Analyze current hash corruption
        $corruption_analysis = detectHashCorruption($current_data['password']);
        if (!$corruption_analysis['is_corrupted'] && !($options['force_repair'] ?? false)) {
            $result['error'] = 'Hash does not appear corrupted. Use force_repair option to proceed anyway.';
            $result['warnings'][] = 'Current hash appears valid';
            $db->rollback();
            return $result;
        }
        
        // Step 3: Create backup before making changes
        $backup_result = createHashBackup($user_id, $current_data, $corruption_analysis);
        if (!$backup_result['success']) {
            $result['error'] = 'Failed to create backup: ' . $backup_result['error'];
            $db->rollback();
            return $result;
        }
        
        $result['backup_created'] = true;
        $result['backup_id'] = $backup_result['backup_id'];
        $result['rollback_available'] = true;
        
        // Step 4: Generate new secure hash
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Validate the new hash was generated correctly
        if (!password_verify($new_password, $new_hash)) {
            $result['error'] = 'Failed to generate valid password hash';
            $db->rollback();
            return $result;
        }
        
        // Step 5: Update user password
        $update_stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $update_success = $update_stmt->execute([$new_hash, $user_id]);
        
        if (!$update_success) {
            $result['error'] = 'Failed to update password in database';
            $db->rollback();
            return $result;
        }
        
        // Step 6: Create audit trail entry
        $audit_result = createHashRepairAuditEntry($user_id, $current_data, $new_hash, $backup_result['backup_id'], $corruption_analysis);
        if ($audit_result['success']) {
            $result['audit_trail_id'] = $audit_result['audit_id'];
        } else {
            $result['warnings'][] = 'Failed to create audit trail: ' . $audit_result['error'];
        }
        
        // Step 7: Verify the repair was successful
        $verification_result = password_verify($new_password, $new_hash);
        if (!$verification_result) {
            $result['error'] = 'Hash repair verification failed';
            $db->rollback();
            return $result;
        }
        
        $db->commit();
        
        $result['success'] = true;
        $result['action_taken'] = 'hash_regenerated';
        $result['new_hash'] = $new_hash;
        
        // Log the successful repair
        logAuthEvent('HASH_REPAIR_SUCCESS', $user_id, [
            'backup_id' => $result['backup_id'],
            'audit_trail_id' => $result['audit_trail_id'],
            'corruption_types' => $corruption_analysis['corruption_types'] ?? []
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        $result['error'] = 'Database error during repair: ' . $e->getMessage();
        
        // Log the failed repair attempt
        logAuthEvent('HASH_REPAIR_FAILED', $user_id, [
            'error' => $e->getMessage(),
            'backup_created' => $result['backup_created']
        ]);
    }
    
    return $result;
}

/**
 * Create backup of user data before hash modification
 * 
 * @param int $user_id User ID to backup
 * @param array $user_data Current user data
 * @param array $corruption_analysis Corruption analysis results
 * @return array Backup creation result
 */
function createHashBackup($user_id, $user_data, $corruption_analysis) {
    $result = [
        'success' => false,
        'backup_id' => null,
        'backup_location' => null,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        // Ensure backup table exists
        createHashBackupTable();
        
        // Create backup entry
        $backup_data = [
            'user_id' => $user_id,
            'email' => $user_data['email'],
            'original_hash' => $user_data['password'],
            'hash_length' => strlen($user_data['password']),
            'corruption_detected' => $corruption_analysis['is_corrupted'] ? 1 : 0,
            'corruption_types' => json_encode($corruption_analysis['corruption_types'] ?? []),
            'corruption_severity' => $corruption_analysis['severity'] ?? 'none',
            'backup_reason' => 'hash_repair',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id'] ?? 'system'
        ];
        
        $stmt = $db->prepare("
            INSERT INTO password_hash_backups 
            (user_id, email, original_hash, hash_length, corruption_detected, 
             corruption_types, corruption_severity, backup_reason, created_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_success = $stmt->execute([
            $backup_data['user_id'],
            $backup_data['email'],
            $backup_data['original_hash'],
            $backup_data['hash_length'],
            $backup_data['corruption_detected'],
            $backup_data['corruption_types'],
            $backup_data['corruption_severity'],
            $backup_data['backup_reason'],
            $backup_data['created_at'],
            $backup_data['created_by']
        ]);
        
        if ($insert_success) {
            $result['success'] = true;
            $result['backup_id'] = $db->lastInsertId();
            $result['backup_location'] = 'password_hash_backups table';
        } else {
            $result['error'] = 'Failed to insert backup record';
        }
        
    } catch (Exception $e) {
        $result['error'] = 'Backup creation error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Create audit trail entry for hash repair operation
 * 
 * @param int $user_id User ID
 * @param array $user_data Original user data
 * @param string $new_hash New password hash
 * @param int $backup_id Associated backup ID
 * @param array $corruption_analysis Corruption analysis results
 * @return array Audit trail creation result
 */
function createHashRepairAuditEntry($user_id, $user_data, $new_hash, $backup_id, $corruption_analysis) {
    $result = [
        'success' => false,
        'audit_id' => null,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        // Ensure audit table exists
        createHashAuditTable();
        
        // Create audit entry
        $audit_data = [
            'user_id' => $user_id,
            'email' => $user_data['email'],
            'action_type' => 'hash_repair',
            'old_hash_prefix' => substr($user_data['password'], 0, 10),
            'new_hash_prefix' => substr($new_hash, 0, 10),
            'backup_id' => $backup_id,
            'corruption_detected' => $corruption_analysis['is_corrupted'] ? 1 : 0,
            'corruption_types' => json_encode($corruption_analysis['corruption_types'] ?? []),
            'repair_reason' => 'automated_hash_repair',
            'performed_by' => $_SESSION['user_id'] ?? 'system',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $stmt = $db->prepare("
            INSERT INTO password_hash_audit 
            (user_id, email, action_type, old_hash_prefix, new_hash_prefix, backup_id,
             corruption_detected, corruption_types, repair_reason, performed_by, 
             ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_success = $stmt->execute([
            $audit_data['user_id'],
            $audit_data['email'],
            $audit_data['action_type'],
            $audit_data['old_hash_prefix'],
            $audit_data['new_hash_prefix'],
            $audit_data['backup_id'],
            $audit_data['corruption_detected'],
            $audit_data['corruption_types'],
            $audit_data['repair_reason'],
            $audit_data['performed_by'],
            $audit_data['ip_address'],
            $audit_data['user_agent'],
            $audit_data['created_at']
        ]);
        
        if ($insert_success) {
            $result['success'] = true;
            $result['audit_id'] = $db->lastInsertId();
        } else {
            $result['error'] = 'Failed to insert audit record';
        }
        
    } catch (Exception $e) {
        $result['error'] = 'Audit trail creation error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Rollback a hash repair operation using backup data
 * 
 * @param int $backup_id Backup ID to restore from
 * @param string $rollback_reason Reason for rollback
 * @return array Rollback operation result
 */
function rollbackHashRepair($backup_id, $rollback_reason = 'manual_rollback') {
    $result = [
        'success' => false,
        'user_id' => null,
        'restored_hash' => null,
        'audit_trail_id' => null,
        'error' => null
    ];
    
    if (!is_numeric($backup_id) || $backup_id <= 0) {
        $result['error'] = 'Invalid backup ID provided';
        return $result;
    }
    
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Step 1: Retrieve backup data
        $stmt = $db->prepare("SELECT * FROM password_hash_backups WHERE id = ?");
        $stmt->execute([$backup_id]);
        $backup_data = $stmt->fetch();
        
        if (!$backup_data) {
            $result['error'] = 'Backup record not found';
            $db->rollback();
            return $result;
        }
        
        // Step 2: Verify user still exists
        $user_stmt = $db->prepare("SELECT id, email FROM users WHERE id = ?");
        $user_stmt->execute([$backup_data['user_id']]);
        $current_user = $user_stmt->fetch();
        
        if (!$current_user) {
            $result['error'] = 'User no longer exists';
            $db->rollback();
            return $result;
        }
        
        // Step 3: Restore the original hash
        $restore_stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $restore_success = $restore_stmt->execute([$backup_data['original_hash'], $backup_data['user_id']]);
        
        if (!$restore_success) {
            $result['error'] = 'Failed to restore password hash';
            $db->rollback();
            return $result;
        }
        
        // Step 4: Create audit trail for rollback
        $audit_result = createRollbackAuditEntry($backup_data, $rollback_reason);
        if ($audit_result['success']) {
            $result['audit_trail_id'] = $audit_result['audit_id'];
        }
        
        // Step 5: Mark backup as used for rollback
        $mark_stmt = $db->prepare("UPDATE password_hash_backups SET rollback_performed = 1, rollback_at = NOW() WHERE id = ?");
        $mark_stmt->execute([$backup_id]);
        
        $db->commit();
        
        $result['success'] = true;
        $result['user_id'] = $backup_data['user_id'];
        $result['restored_hash'] = substr($backup_data['original_hash'], 0, 10) . '...[masked]';
        
        // Log the rollback
        logAuthEvent('HASH_ROLLBACK_SUCCESS', $backup_data['user_id'], [
            'backup_id' => $backup_id,
            'rollback_reason' => $rollback_reason,
            'audit_trail_id' => $result['audit_trail_id']
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        $result['error'] = 'Rollback error: ' . $e->getMessage();
        
        logAuthEvent('HASH_ROLLBACK_FAILED', $backup_data['user_id'] ?? null, [
            'backup_id' => $backup_id,
            'error' => $e->getMessage()
        ]);
    }
    
    return $result;
}

/**
 * Create audit trail entry for rollback operation
 * 
 * @param array $backup_data Backup data being restored
 * @param string $rollback_reason Reason for rollback
 * @return array Audit creation result
 */
function createRollbackAuditEntry($backup_data, $rollback_reason) {
    $result = [
        'success' => false,
        'audit_id' => null,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        $audit_data = [
            'user_id' => $backup_data['user_id'],
            'email' => $backup_data['email'],
            'action_type' => 'hash_rollback',
            'old_hash_prefix' => '[current_hash]',
            'new_hash_prefix' => substr($backup_data['original_hash'], 0, 10),
            'backup_id' => $backup_data['id'],
            'corruption_detected' => 0,
            'corruption_types' => '[]',
            'repair_reason' => $rollback_reason,
            'performed_by' => $_SESSION['user_id'] ?? 'system',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $stmt = $db->prepare("
            INSERT INTO password_hash_audit 
            (user_id, email, action_type, old_hash_prefix, new_hash_prefix, backup_id,
             corruption_detected, corruption_types, repair_reason, performed_by, 
             ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_success = $stmt->execute([
            $audit_data['user_id'],
            $audit_data['email'],
            $audit_data['action_type'],
            $audit_data['old_hash_prefix'],
            $audit_data['new_hash_prefix'],
            $audit_data['backup_id'],
            $audit_data['corruption_detected'],
            $audit_data['corruption_types'],
            $audit_data['repair_reason'],
            $audit_data['performed_by'],
            $audit_data['ip_address'],
            $audit_data['user_agent'],
            $audit_data['created_at']
        ]);
        
        if ($insert_success) {
            $result['success'] = true;
            $result['audit_id'] = $db->lastInsertId();
        } else {
            $result['error'] = 'Failed to insert rollback audit record';
        }
        
    } catch (Exception $e) {
        $result['error'] = 'Rollback audit creation error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Create backup table for password hashes if it doesn't exist
 */
function createHashBackupTable() {
    try {
        $db = getDB();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS password_hash_backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            original_hash VARCHAR(255) NOT NULL,
            hash_length INT NOT NULL,
            corruption_detected TINYINT(1) DEFAULT 0,
            corruption_types TEXT,
            corruption_severity ENUM('none', 'minor', 'major', 'critical') DEFAULT 'none',
            backup_reason VARCHAR(100) NOT NULL,
            rollback_performed TINYINT(1) DEFAULT 0,
            rollback_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by VARCHAR(100) NOT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_backup_reason (backup_reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        
    } catch (Exception $e) {
        error_log("Failed to create backup table: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Create audit table for password hash operations if it doesn't exist
 */
function createHashAuditTable() {
    try {
        $db = getDB();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS password_hash_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            action_type ENUM('hash_repair', 'hash_rollback', 'hash_update') NOT NULL,
            old_hash_prefix VARCHAR(20),
            new_hash_prefix VARCHAR(20),
            backup_id INT NULL,
            corruption_detected TINYINT(1) DEFAULT 0,
            corruption_types TEXT,
            repair_reason VARCHAR(255),
            performed_by VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action_type (action_type),
            INDEX idx_created_at (created_at),
            INDEX idx_performed_by (performed_by),
            FOREIGN KEY (backup_id) REFERENCES password_hash_backups(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($sql);
        
    } catch (Exception $e) {
        error_log("Failed to create audit table: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get backup history for a specific user
 * 
 * @param int $user_id User ID to get backup history for
 * @param int $limit Maximum number of records to return
 * @return array Backup history results
 */
function getHashBackupHistory($user_id, $limit = 10) {
    $result = [
        'success' => false,
        'backups' => [],
        'total_count' => 0,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        // Get backup records
        $stmt = $db->prepare("
            SELECT id, user_id, email, hash_length, corruption_detected, 
                   corruption_types, corruption_severity, backup_reason,
                   rollback_performed, rollback_at, created_at, created_by
            FROM password_hash_backups 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $backups = $stmt->fetchAll();
        
        // Get total count
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM password_hash_backups WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $total_count = $count_stmt->fetchColumn();
        
        $result['success'] = true;
        $result['backups'] = $backups;
        $result['total_count'] = $total_count;
        
    } catch (Exception $e) {
        $result['error'] = 'Failed to retrieve backup history: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Get audit trail for hash operations
 * 
 * @param int|null $user_id Optional user ID filter
 * @param int $limit Maximum number of records to return
 * @return array Audit trail results
 */
function getHashAuditTrail($user_id = null, $limit = 50) {
    $result = [
        'success' => false,
        'audit_entries' => [],
        'total_count' => 0,
        'error' => null
    ];
    
    try {
        $db = getDB();
        
        // Build query based on user filter
        $where_clause = $user_id ? "WHERE user_id = ?" : "";
        $params = $user_id ? [$user_id, $limit] : [$limit];
        
        // Get audit records
        $stmt = $db->prepare("
            SELECT id, user_id, email, action_type, old_hash_prefix, new_hash_prefix,
                   backup_id, corruption_detected, corruption_types, repair_reason,
                   performed_by, ip_address, created_at
            FROM password_hash_audit 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute($params);
        $audit_entries = $stmt->fetchAll();
        
        // Get total count
        $count_params = $user_id ? [$user_id] : [];
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM password_hash_audit {$where_clause}");
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetchColumn();
        
        $result['success'] = true;
        $result['audit_entries'] = $audit_entries;
        $result['total_count'] = $total_count;
        
    } catch (Exception $e) {
        $result['error'] = 'Failed to retrieve audit trail: ' . $e->getMessage();
    }
    
    return $result;
}

// Include comprehensive authentication logger
require_once __DIR__ . '/auth-logger.php';

/**
 * Log authentication events securely (wrapper for comprehensive logger)
 * 
 * @param string $event_type Type of authentication event
 * @param int|null $user_id User ID (if available)
 * @param array $details Additional details (sensitive data will be masked)
 * @param string $level Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
 */
function logAuthEvent($event_type, $user_id = null, $details = [], $level = 'INFO') {
    // Use the comprehensive authentication logger
    logAuthenticationEvent($event_type, $user_id, $details, $level);
}

// Note: getDB() function is already available from config/database.php
// No need to redefine it here

?>
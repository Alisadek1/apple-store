<?php
/**
 * Administrative Diagnostic Interface
 * 
 * Comprehensive web-based diagnostic interface for admin users
 * Provides real-time hash comparison, verification testing, and automated repair recommendations
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/auth-functions.php';
require_once __DIR__ . '/../../includes/security-manager.php';

$current_page = 'admin-diagnostics';
$lang = getLang();

// Initialize security manager
$security = new SecurityManager();

// Validate admin access with enhanced security
$access_validation = $security->validateAdminAccess('admin_diagnostics');
if (!$access_validation['valid']) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'error' => 'Access denied: ' . implode(', ', $access_validation['errors'])
    ]));
}

// Handle AJAX requests for real-time functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Validate CSRF token for all POST requests
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$security->validateCSRFToken($csrf_token, $_POST['action'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    // Check rate limits based on action type
    $rate_limit_action = getAdminRateLimitAction($_POST['action']);
    $rate_limit = $security->checkRateLimit($rate_limit_action);
    if (!$rate_limit['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'error' => $rate_limit['message'],
            'retry_after' => $rate_limit['reset_time'] - time()
        ]);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'real_time_verification':
            echo json_encode(handleRealTimeVerification());
            exit;
            
        case 'step_by_step_analysis':
            echo json_encode(handleStepByStepAnalysis());
            exit;
            
        case 'automated_repair_recommendation':
            echo json_encode(handleAutomatedRepairRecommendation());
            exit;
            
        case 'system_health_check':
            echo json_encode(handleSystemHealthCheck());
            exit;
            
        case 'bulk_user_analysis':
            echo json_encode(handleBulkUserAnalysis());
            exit;
            
        case 'execute_repair':
            echo json_encode(handleExecuteRepair());
            exit;
            
        case 'get_repair_status':
            echo json_encode(handleGetRepairStatus());
            exit;
    }
}

/**
 * Map admin actions to rate limit categories
 */
function getAdminRateLimitAction($action) {
    $action_mapping = [
        'real_time_verification' => 'diagnostic_test',
        'step_by_step_analysis' => 'diagnostic_test',
        'automated_repair_recommendation' => 'diagnostic_test',
        'system_health_check' => 'diagnostic_test',
        'bulk_user_analysis' => 'bulk_analysis',
        'execute_repair' => 'repair_operation',
        'get_repair_status' => 'diagnostic_test'
    ];
    
    return $action_mapping[$action] ?? 'diagnostic_test';
}

/**
 * Handle real-time password verification with live feedback
 */
function handleRealTimeVerification() {
    $password = $_POST['password'] ?? '';
    $hash = $_POST['hash'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    
    if (empty($password) || empty($hash)) {
        return ['success' => false, 'error' => 'Password and hash are required'];
    }
    
    // Perform comprehensive verification with timing
    $start_time = microtime(true);
    $result = verifyPasswordSecure($password, $hash, $user_id);
    $end_time = microtime(true);
    
    // Add performance metrics
    $result['performance'] = [
        'verification_time_ms' => round(($end_time - $start_time) * 1000, 2),
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
    ];
    
    // Add real-time status indicators
    $result['status_indicators'] = [
        'hash_format_valid' => $result['hash_valid'] ?? false,
        'verification_successful' => $result['success'] ?? false,
        'has_diagnostics' => !empty($result['diagnostics']),
        'needs_repair' => !($result['success'] ?? false) && ($result['hash_valid'] ?? false)
    ];
    
    return ['success' => true, 'data' => $result];
}

/**
 * Handle step-by-step verification analysis
 */
function handleStepByStepAnalysis() {
    $password = $_POST['password'] ?? '';
    $hash = $_POST['hash'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    
    if (empty($password) || empty($hash)) {
        return ['success' => false, 'error' => 'Password and hash are required'];
    }
    
    $steps = [];
    
    // Step 1: Hash Format Validation
    $steps[] = [
        'step' => 1,
        'title' => 'Hash Format Validation',
        'status' => 'running',
        'details' => 'Validating bcrypt hash format...'
    ];
    
    $hash_validation = validateHashFormat($hash);
    $steps[0]['status'] = $hash_validation['valid'] ? 'success' : 'error';
    $steps[0]['details'] = $hash_validation['valid'] ? 
        'Hash format is valid' : 
        'Hash format issues: ' . implode(', ', $hash_validation['issues']);
    $steps[0]['data'] = $hash_validation;
    
    // Step 2: Database Integrity Check
    $steps[] = [
        'step' => 2,
        'title' => 'Database Integrity Check',
        'status' => 'running',
        'details' => 'Checking database storage integrity...'
    ];
    
    if ($user_id) {
        $hash_retrieval = retrieveHashWithValidation($user_id);
        $steps[1]['status'] = $hash_retrieval['success'] && !$hash_retrieval['corruption_detected'] ? 'success' : 'warning';
        $steps[1]['details'] = $hash_retrieval['success'] ? 
            'Hash retrieved successfully' : 
            'Hash retrieval issues detected';
        $steps[1]['data'] = $hash_retrieval;
    } else {
        $steps[1]['status'] = 'skipped';
        $steps[1]['details'] = 'No user ID provided - skipping database check';
    }
    
    // Step 3: Password Verification
    $steps[] = [
        'step' => 3,
        'title' => 'Password Verification',
        'status' => 'running',
        'details' => 'Attempting password verification...'
    ];
    
    $verification_result = password_verify($password, $hash);
    $steps[2]['status'] = $verification_result ? 'success' : 'error';
    $steps[2]['details'] = $verification_result ? 
        'Password verification successful' : 
        'Password verification failed';
    $steps[2]['data'] = ['verification_result' => $verification_result];
    
    // Step 4: Failure Analysis (if verification failed)
    if (!$verification_result) {
        $steps[] = [
            'step' => 4,
            'title' => 'Failure Analysis',
            'status' => 'running',
            'details' => 'Analyzing verification failure...'
        ];
        
        $failure_analysis = diagnoseVerificationFailure($password, $hash);
        $steps[3]['status'] = 'info';
        $steps[3]['details'] = 'Failure analysis completed';
        $steps[3]['data'] = $failure_analysis;
    }
    
    // Step 5: Repair Recommendations
    $overall_success = $verification_result;
    if (!$overall_success) {
        $steps[] = [
            'step' => count($steps) + 1,
            'title' => 'Repair Recommendations',
            'status' => 'info',
            'details' => 'Generating repair recommendations...'
        ];
        
        $recommendations = generateRepairRecommendations($hash_validation, $user_id ? $hash_retrieval : null);
        $steps[count($steps) - 1]['data'] = $recommendations;
        $steps[count($steps) - 1]['details'] = count($recommendations['actions']) . ' recommendations generated';
    }
    
    return [
        'success' => true,
        'steps' => $steps,
        'overall_result' => $overall_success,
        'total_steps' => count($steps)
    ];
}

/**
 * Handle automated repair recommendation generation
 */
function handleAutomatedRepairRecommendation() {
    $user_id = $_POST['user_id'] ?? null;
    $hash = $_POST['hash'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!$user_id && !$hash) {
        return ['success' => false, 'error' => 'User ID or hash is required'];
    }
    
    $recommendations = [
        'priority' => 'medium',
        'confidence' => 0,
        'actions' => [],
        'risks' => [],
        'estimated_success' => 0,
        'backup_required' => true
    ];
    
    // Analyze the current situation
    if ($user_id) {
        $hash_retrieval = retrieveHashWithValidation($user_id);
        if ($hash_retrieval['success']) {
            $hash = $hash_retrieval['hash'];
        }
    }
    
    if ($hash) {
        $hash_validation = validateHashFormat($hash);
        $corruption_analysis = detectHashCorruption($hash);
        
        // Generate recommendations based on analysis
        if ($corruption_analysis['is_corrupted']) {
            $recommendations['priority'] = $corruption_analysis['severity'] === 'critical' ? 'high' : 'medium';
            
            if ($corruption_analysis['repair_possible']) {
                $recommendations['actions'][] = [
                    'type' => 'automatic_repair',
                    'description' => 'Automatically repair hash corruption',
                    'confidence' => 85,
                    'requires_password' => true
                ];
                $recommendations['estimated_success'] = 85;
            } else {
                $recommendations['actions'][] = [
                    'type' => 'manual_regeneration',
                    'description' => 'Manually regenerate password hash',
                    'confidence' => 95,
                    'requires_password' => true
                ];
                $recommendations['estimated_success'] = 95;
            }
            
            $recommendations['confidence'] = 90;
        } else if ($password && !password_verify($password, $hash)) {
            // Hash format is valid but verification fails
            $recommendations['actions'][] = [
                'type' => 'environment_check',
                'description' => 'Check PHP environment and bcrypt support',
                'confidence' => 70,
                'requires_password' => false
            ];
            
            $recommendations['actions'][] = [
                'type' => 'database_encoding_check',
                'description' => 'Verify database encoding and charset',
                'confidence' => 80,
                'requires_password' => false
            ];
            
            $recommendations['confidence'] = 60;
            $recommendations['estimated_success'] = 70;
        }
        
        // Add database integrity check recommendation
        $recommendations['actions'][] = [
            'type' => 'database_integrity_check',
            'description' => 'Perform comprehensive database integrity check',
            'confidence' => 95,
            'requires_password' => false
        ];
        
        // Add risks assessment
        if (in_array($corruption_analysis['severity'] ?? 'none', ['major', 'critical'])) {
            $recommendations['risks'][] = 'User account may be permanently inaccessible';
            $recommendations['risks'][] = 'Data corruption may affect other users';
        }
        
        $recommendations['risks'][] = 'Repair operations require system downtime';
        $recommendations['risks'][] = 'Backup creation increases storage usage';
    }
    
    return ['success' => true, 'recommendations' => $recommendations];
}

/**
 * Handle system health check
 */
function handleSystemHealthCheck() {
    $health_check = [
        'overall_status' => 'unknown',
        'components' => [],
        'issues_found' => 0,
        'critical_issues' => 0,
        'recommendations' => []
    ];
    
    // Check PHP environment
    $php_check = [
        'name' => 'PHP Environment',
        'status' => 'good',
        'details' => [],
        'issues' => []
    ];
    
    $php_check['details']['version'] = PHP_VERSION;
    $php_check['details']['password_functions'] = function_exists('password_hash') && function_exists('password_verify');
    $php_check['details']['bcrypt_support'] = defined('PASSWORD_BCRYPT');
    
    if (!$php_check['details']['password_functions']) {
        $php_check['status'] = 'critical';
        $php_check['issues'][] = 'Password functions not available';
        $health_check['critical_issues']++;
    }
    
    if (!$php_check['details']['bcrypt_support']) {
        $php_check['status'] = 'critical';
        $php_check['issues'][] = 'Bcrypt support not available';
        $health_check['critical_issues']++;
    }
    
    $health_check['components'][] = $php_check;
    
    // Check database integrity
    $db_integrity = performDatabaseIntegrityCheck();
    $db_check = [
        'name' => 'Database Integrity',
        'status' => $db_integrity['overall_status'],
        'details' => [
            'total_users' => $db_integrity['corruption_summary']['total_users'],
            'corrupted_hashes' => $db_integrity['corruption_summary']['corrupted_hashes'],
            'critical_issues' => $db_integrity['corruption_summary']['critical_issues']
        ],
        'issues' => []
    ];
    
    if ($db_integrity['corruption_summary']['critical_issues'] > 0) {
        $db_check['issues'][] = $db_integrity['corruption_summary']['critical_issues'] . ' critical hash corruption issues';
        $health_check['critical_issues'] += $db_integrity['corruption_summary']['critical_issues'];
    }
    
    if ($db_integrity['corruption_summary']['corrupted_hashes'] > 0) {
        $db_check['issues'][] = $db_integrity['corruption_summary']['corrupted_hashes'] . ' corrupted password hashes';
        $health_check['issues_found'] += $db_integrity['corruption_summary']['corrupted_hashes'];
    }
    
    $health_check['components'][] = $db_check;
    
    // Check authentication system
    $auth_check = [
        'name' => 'Authentication System',
        'status' => 'good',
        'details' => [],
        'issues' => []
    ];
    
    // Test authentication functions
    try {
        $test_password = 'test123';
        $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $test_verify = password_verify($test_password, $test_hash);
        
        $auth_check['details']['hash_generation'] = !empty($test_hash);
        $auth_check['details']['verification_working'] = $test_verify;
        
        if (!$test_verify) {
            $auth_check['status'] = 'critical';
            $auth_check['issues'][] = 'Password verification not working';
            $health_check['critical_issues']++;
        }
    } catch (Exception $e) {
        $auth_check['status'] = 'critical';
        $auth_check['issues'][] = 'Authentication system error: ' . $e->getMessage();
        $health_check['critical_issues']++;
    }
    
    $health_check['components'][] = $auth_check;
    
    // Determine overall status
    if ($health_check['critical_issues'] > 0) {
        $health_check['overall_status'] = 'critical';
    } elseif ($health_check['issues_found'] > 0) {
        $health_check['overall_status'] = 'warning';
    } else {
        $health_check['overall_status'] = 'good';
    }
    
    // Generate recommendations
    if ($health_check['critical_issues'] > 0) {
        $health_check['recommendations'][] = 'Immediate attention required for critical issues';
    }
    
    if ($db_integrity['corruption_summary']['corrupted_hashes'] > 0) {
        $health_check['recommendations'][] = 'Run bulk user analysis to identify affected accounts';
        $health_check['recommendations'][] = 'Consider implementing automated hash repair for minor issues';
    }
    
    return ['success' => true, 'health_check' => $health_check];
}

/**
 * Handle bulk user analysis
 */
function handleBulkUserAnalysis() {
    $limit = min(100, (int)($_POST['limit'] ?? 50));
    $offset = max(0, (int)($_POST['offset'] ?? 0));
    
    try {
        $db = getDB();
        
        // Get users with pagination
        $stmt = $db->prepare("SELECT id, email FROM users ORDER BY id LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll();
        
        // Get total count
        $count_stmt = $db->query("SELECT COUNT(*) FROM users");
        $total_users = $count_stmt->fetchColumn();
        
        $analysis_results = [
            'total_users' => $total_users,
            'analyzed_users' => count($users),
            'offset' => $offset,
            'limit' => $limit,
            'users' => [],
            'summary' => [
                'healthy' => 0,
                'corrupted' => 0,
                'critical' => 0,
                'repairable' => 0
            ]
        ];
        
        foreach ($users as $user) {
            $user_analysis = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'status' => 'unknown',
                'issues' => [],
                'recommendations' => []
            ];
            
            // Retrieve and analyze hash
            $hash_retrieval = retrieveHashWithValidation($user['id']);
            if ($hash_retrieval['success'] && $hash_retrieval['hash']) {
                $corruption_analysis = detectHashCorruption($hash_retrieval['hash']);
                
                if ($corruption_analysis['is_corrupted']) {
                    $user_analysis['status'] = $corruption_analysis['severity'];
                    $user_analysis['issues'] = $corruption_analysis['details'];
                    
                    if ($corruption_analysis['repair_possible']) {
                        $user_analysis['recommendations'][] = 'Automatic repair possible';
                        $analysis_results['summary']['repairable']++;
                    } else {
                        $user_analysis['recommendations'][] = 'Manual intervention required';
                    }
                    
                    if ($corruption_analysis['severity'] === 'critical') {
                        $analysis_results['summary']['critical']++;
                    }
                    
                    $analysis_results['summary']['corrupted']++;
                } else {
                    $user_analysis['status'] = 'healthy';
                    $analysis_results['summary']['healthy']++;
                }
            } else {
                $user_analysis['status'] = 'error';
                $user_analysis['issues'][] = 'Failed to retrieve hash from database';
            }
            
            $analysis_results['users'][] = $user_analysis;
        }
        
        return ['success' => true, 'analysis' => $analysis_results];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Bulk analysis failed: ' . $e->getMessage()];
    }
}

/**
 * Handle repair execution
 */
function handleExecuteRepair() {
    $user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $repair_type = $_POST['repair_type'] ?? 'regenerate';
    $force_repair = $_POST['force_repair'] ?? false;
    
    if (!$user_id || !$new_password) {
        return ['success' => false, 'error' => 'User ID and new password are required'];
    }
    
    try {
        $options = [
            'force_repair' => $force_repair,
            'backup_location' => 'database'
        ];
        
        $repair_result = repairCorruptedHash($user_id, $new_password, $options);
        
        if ($repair_result['success']) {
            return [
                'success' => true,
                'message' => 'Hash repair completed successfully',
                'details' => [
                    'action_taken' => $repair_result['action_taken'],
                    'backup_created' => $repair_result['backup_created'],
                    'backup_id' => $repair_result['backup_id'],
                    'rollback_available' => $repair_result['rollback_available']
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => $repair_result['error'],
                'warnings' => $repair_result['warnings'] ?? []
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Repair execution failed: ' . $e->getMessage()];
    }
}

/**
 * Handle repair status check
 */
function handleGetRepairStatus() {
    $user_id = $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        return ['success' => false, 'error' => 'User ID is required'];
    }
    
    try {
        // Get backup history
        $backup_history = getHashBackupHistory($user_id, 5);
        
        // Get audit trail
        $audit_trail = getHashAuditTrail($user_id, 10);
        
        // Get current hash status
        $hash_retrieval = retrieveHashWithValidation($user_id);
        $current_status = 'unknown';
        
        if ($hash_retrieval['success'] && $hash_retrieval['hash']) {
            $corruption_analysis = detectHashCorruption($hash_retrieval['hash']);
            $current_status = $corruption_analysis['is_corrupted'] ? 
                $corruption_analysis['severity'] : 'healthy';
        }
        
        return [
            'success' => true,
            'status' => [
                'current_hash_status' => $current_status,
                'backup_history' => $backup_history,
                'audit_trail' => $audit_trail,
                'last_repair' => null // Will be populated from audit trail
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Status check failed: ' . $e->getMessage()];
    }
}

/**
 * Generate repair recommendations based on analysis
 */
function generateRepairRecommendations($hash_validation, $hash_retrieval = null) {
    $recommendations = [
        'actions' => [],
        'priority' => 'medium',
        'confidence' => 0
    ];
    
    if (!$hash_validation['valid']) {
        foreach ($hash_validation['issues'] as $issue) {
            if (strpos($issue, 'whitespace') !== false) {
                $recommendations['actions'][] = [
                    'type' => 'trim_whitespace',
                    'description' => 'Remove leading/trailing whitespace from hash',
                    'confidence' => 95
                ];
            } elseif (strpos($issue, 'length') !== false) {
                $recommendations['actions'][] = [
                    'type' => 'regenerate_hash',
                    'description' => 'Regenerate hash due to length issues',
                    'confidence' => 90
                ];
            } elseif (strpos($issue, 'prefix') !== false) {
                $recommendations['actions'][] = [
                    'type' => 'regenerate_hash',
                    'description' => 'Regenerate hash due to format corruption',
                    'confidence' => 85
                ];
            }
        }
    }
    
    if ($hash_retrieval && !empty($hash_retrieval['encoding_issues'])) {
        $recommendations['actions'][] = [
            'type' => 'check_database_encoding',
            'description' => 'Verify database charset and collation settings',
            'confidence' => 80
        ];
    }
    
    return $recommendations;}


// Generate CSRF tokens for different actions
$csrf_tokens = [
    'real_time_verification' => $security->generateCSRFToken('real_time_verification'),
    'step_by_step_analysis' => $security->generateCSRFToken('step_by_step_analysis'),
    'automated_repair_recommendation' => $security->generateCSRFToken('automated_repair_recommendation'),
    'system_health_check' => $security->generateCSRFToken('system_health_check'),
    'bulk_user_analysis' => $security->generateCSRFToken('bulk_user_analysis'),
    'execute_repair' => $security->generateCSRFToken('execute_repair'),
    'get_repair_status' => $security->generateCSRFToken('get_repair_status')
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 text-gold">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $lang === 'ar' ? 'واجهة التشخيص الإدارية' : 'Administrative Diagnostic Interface'; ?>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo ADMIN_URL; ?>/index.php" class="text-gold">
                                <?php echo t('dashboard'); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?php echo $lang === 'ar' ? 'التشخيص الإداري' : 'Admin Diagnostics'; ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- System Status Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-dark-gray border-gold">
                <div class="card-header bg-black border-gold">
                    <h5 class="text-gold mb-0">
                        <i class="fas fa-heartbeat"></i>
                        <?php echo $lang === 'ar' ? 'حالة النظام' : 'System Health Status'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="systemHealthStatus">
                        <div class="text-center">
                            <div class="spinner-border text-gold" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-light-gray mt-2">
                                <?php echo $lang === 'ar' ? 'جاري فحص حالة النظام...' : 'Checking system health...'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Main Diagnostic Interface -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark-gray border-gold">
                <div class="card-header bg-black border-gold">
                    <ul class="nav nav-tabs card-header-tabs" id="diagnosticTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active bg-dark-gray text-light-gray border-gold" 
                                    id="real-time-tab" data-bs-toggle="tab" data-bs-target="#real-time" 
                                    type="button" role="tab">
                                <i class="fas fa-bolt"></i>
                                <?php echo $lang === 'ar' ? 'التحقق المباشر' : 'Real-time Verification'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="step-analysis-tab" data-bs-toggle="tab" data-bs-target="#step-analysis" 
                                    type="button" role="tab">
                                <i class="fas fa-list-ol"></i>
                                <?php echo $lang === 'ar' ? 'التحليل المتدرج' : 'Step-by-Step Analysis'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="repair-recommendations-tab" data-bs-toggle="tab" data-bs-target="#repair-recommendations" 
                                    type="button" role="tab">
                                <i class="fas fa-tools"></i>
                                <?php echo $lang === 'ar' ? 'توصيات الإصلاح' : 'Repair Recommendations'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="bulk-analysis-tab" data-bs-toggle="tab" data-bs-target="#bulk-analysis" 
                                    type="button" role="tab">
                                <i class="fas fa-users"></i>
                                <?php echo $lang === 'ar' ? 'التحليل الجماعي' : 'Bulk Analysis'; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="diagnosticTabContent">
                        
                        <!-- Real-time Verification Tab -->
                        <div class="tab-pane fade show active" id="real-time" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-bolt"></i>
                                <?php echo $lang === 'ar' ? 'التحقق المباشر من كلمة المرور' : 'Real-time Password Verification'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'اختبار فوري مع ردود فعل مباشرة' : 'Instant testing with live feedback'; ?>
                            </p>
                            
                            <form id="realTimeForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?>
                                            </label>
                                            <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="realTimePassword" name="password" placeholder="Enter password">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'الهاش' : 'Hash'; ?>
                                            </label>
                                            <input type="text" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="realTimeHash" name="hash" placeholder="Enter hash to verify">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'معرف المستخدم' : 'User ID'; ?>
                                            </label>
                                            <input type="number" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="realTimeUserId" name="user_id" placeholder="Optional">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-gold" onclick="runRealTimeVerification()">
                                        <i class="fas fa-play"></i>
                                        <?php echo $lang === 'ar' ? 'تشغيل التحقق' : 'Run Verification'; ?>
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-gold" onclick="loadProblemHash()">
                                        <i class="fas fa-bug"></i>
                                        <?php echo $lang === 'ar' ? 'تحميل الهاش المشكل' : 'Load Problem Hash'; ?>
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-gold" onclick="clearRealTimeForm()">
                                        <i class="fas fa-eraser"></i>
                                        <?php echo $lang === 'ar' ? 'مسح' : 'Clear'; ?>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Real-time Status Indicators -->
                            <div id="realTimeStatus" class="mb-3" style="display: none;">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="status-indicator" id="hashFormatStatus">
                                            <i class="fas fa-circle text-secondary"></i>
                                            <span><?php echo $lang === 'ar' ? 'تنسيق الهاش' : 'Hash Format'; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="status-indicator" id="verificationStatus">
                                            <i class="fas fa-circle text-secondary"></i>
                                            <span><?php echo $lang === 'ar' ? 'التحقق' : 'Verification'; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="status-indicator" id="diagnosticsStatus">
                                            <i class="fas fa-circle text-secondary"></i>
                                            <span><?php echo $lang === 'ar' ? 'التشخيص' : 'Diagnostics'; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="status-indicator" id="repairStatus">
                                            <i class="fas fa-circle text-secondary"></i>
                                            <span><?php echo $lang === 'ar' ? 'الإصلاح' : 'Repair Needed'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="realTimeResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج التحقق المباشر' : 'Real-time Verification Results'; ?></h6>
                                <div id="realTimeOutput"></div>
                            </div>
                        </div>
                        
                        <!-- Step-by-Step Analysis Tab -->
                        <div class="tab-pane fade" id="step-analysis" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-list-ol"></i>
                                <?php echo $lang === 'ar' ? 'التحليل المتدرج' : 'Step-by-Step Analysis'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'تحليل مفصل خطوة بخطوة لعملية التحقق' : 'Detailed step-by-step verification process analysis'; ?>
                            </p>
                            
                            <form id="stepAnalysisForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?>
                                            </label>
                                            <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="stepPassword" name="password" placeholder="Enter password">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'الهاش' : 'Hash'; ?>
                                            </label>
                                            <input type="text" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="stepHash" name="hash" placeholder="Enter hash to analyze">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'معرف المستخدم' : 'User ID'; ?>
                                            </label>
                                            <input type="number" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="stepUserId" name="user_id" placeholder="Optional">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-gold" onclick="runStepAnalysis()">
                                    <i class="fas fa-microscope"></i>
                                    <?php echo $lang === 'ar' ? 'بدء التحليل' : 'Start Analysis'; ?>
                                </button>
                            </form>
                            
                            <div id="stepAnalysisResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'خطوات التحليل' : 'Analysis Steps'; ?></h6>
                                <div id="stepAnalysisOutput"></div>
                            </div>
                        </div>
                        
                        <!-- Repair Recommendations Tab -->
                        <div class="tab-pane fade" id="repair-recommendations" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-tools"></i>
                                <?php echo $lang === 'ar' ? 'توصيات الإصلاح التلقائي' : 'Automated Repair Recommendations'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'توصيات ذكية لإصلاح مشاكل التحقق' : 'Smart recommendations for fixing verification issues'; ?>
                            </p>
                            
                            <form id="repairRecommendationForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'معرف المستخدم' : 'User ID'; ?>
                                            </label>
                                            <input type="number" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="repairUserId" name="user_id" placeholder="Enter user ID">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'الهاش (اختياري)' : 'Hash (Optional)'; ?>
                                            </label>
                                            <input type="text" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="repairHash" name="hash" placeholder="Or enter hash directly">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-light-gray">
                                        <?php echo $lang === 'ar' ? 'كلمة المرور للاختبار (اختياري)' : 'Test Password (Optional)'; ?>
                                    </label>
                                    <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                           id="repairPassword" name="password" placeholder="For verification testing">
                                </div>
                                
                                <button type="button" class="btn btn-gold" onclick="getRepairRecommendations()">
                                    <i class="fas fa-magic"></i>
                                    <?php echo $lang === 'ar' ? 'إنشاء التوصيات' : 'Generate Recommendations'; ?>
                                </button>
                            </form>
                            
                            <div id="repairRecommendationResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'توصيات الإصلاح' : 'Repair Recommendations'; ?></h6>
                                <div id="repairRecommendationOutput"></div>
                            </div>
                            
                            <!-- Repair Execution Section -->
                            <div id="repairExecutionSection" class="mt-4" style="display: none;">
                                <div class="card bg-black border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo $lang === 'ar' ? 'تنفيذ الإصلاح' : 'Execute Repair'; ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <strong><?php echo $lang === 'ar' ? 'تحذير:' : 'Warning:'; ?></strong>
                                            <?php echo $lang === 'ar' ? 'سيتم إنشاء نسخة احتياطية قبل الإصلاح' : 'A backup will be created before repair'; ?>
                                        </div>
                                        
                                        <form id="repairExecutionForm">
                                            <input type="hidden" id="executeRepairUserId" name="user_id">
                                            <div class="mb-3">
                                                <label class="form-label text-light-gray">
                                                    <?php echo $lang === 'ar' ? 'كلمة المرور الجديدة' : 'New Password'; ?>
                                                </label>
                                                <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                       id="executeRepairPassword" name="new_password" required 
                                                       placeholder="Enter new password for hash generation">
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="forceRepair" name="force_repair">
                                                <label class="form-check-label text-light-gray" for="forceRepair">
                                                    <?php echo $lang === 'ar' ? 'فرض الإصلاح حتى لو بدا الهاش صحيحاً' : 'Force repair even if hash appears valid'; ?>
                                                </label>
                                            </div>
                                            
                                            <button type="button" class="btn btn-warning" onclick="executeRepair()">
                                                <i class="fas fa-wrench"></i>
                                                <?php echo $lang === 'ar' ? 'تنفيذ الإصلاح' : 'Execute Repair'; ?>
                                            </button>
                                            
                                            <button type="button" class="btn btn-secondary" onclick="hideRepairExecution()">
                                                <?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bulk Analysis Tab -->
                        <div class="tab-pane fade" id="bulk-analysis" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-users"></i>
                                <?php echo $lang === 'ar' ? 'التحليل الجماعي للمستخدمين' : 'Bulk User Analysis'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'تحليل شامل لجميع حسابات المستخدمين' : 'Comprehensive analysis of all user accounts'; ?>
                            </p>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-light-gray">
                                        <?php echo $lang === 'ar' ? 'عدد المستخدمين لكل دفعة' : 'Users per batch'; ?>
                                    </label>
                                    <select class="form-select bg-dark-gray text-light-gray border-gold" id="bulkAnalysisLimit">
                                        <option value="25">25</option>
                                        <option value="50" selected>50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-light-gray">
                                        <?php echo $lang === 'ar' ? 'البداية من' : 'Start from'; ?>
                                    </label>
                                    <input type="number" class="form-control bg-dark-gray text-light-gray border-gold" 
                                           id="bulkAnalysisOffset" value="0" min="0">
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-gold" onclick="runBulkAnalysis()">
                                <i class="fas fa-search"></i>
                                <?php echo $lang === 'ar' ? 'بدء التحليل الجماعي' : 'Start Bulk Analysis'; ?>
                            </button>
                            
                            <div id="bulkAnalysisResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج التحليل الجماعي' : 'Bulk Analysis Results'; ?></h6>
                                <div id="bulkAnalysisOutput"></div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.3);
    margin-bottom: 8px;
}

.status-indicator i.text-success {
    color: #28a745 !important;
}

.status-indicator i.text-danger {
    color: #dc3545 !important;
}

.status-indicator i.text-warning {
    color: #ffc107 !important;
}

.status-indicator i.text-secondary {
    color: #6c757d !important;
}

.step-item {
    border-left: 3px solid #6c757d;
    padding-left: 15px;
    margin-bottom: 15px;
    position: relative;
}

.step-item.success {
    border-left-color: #28a745;
}

.step-item.error {
    border-left-color: #dc3545;
}

.step-item.warning {
    border-left-color: #ffc107;
}

.step-item.info {
    border-left-color: #17a2b8;
}

.step-item.running {
    border-left-color: #007bff;
}

.step-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 8px;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: currentColor;
}

.recommendation-item {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 10px;
}

.confidence-bar {
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 5px;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
    transition: width 0.3s ease;
}

.user-analysis-item {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 8px;
    border-left: 3px solid #6c757d;
}

.user-analysis-item.healthy {
    border-left-color: #28a745;
}

.user-analysis-item.minor {
    border-left-color: #ffc107;
}

.user-analysis-item.major {
    border-left-color: #fd7e14;
}

.user-analysis-item.critical {
    border-left-color: #dc3545;
}

.performance-metrics {
    font-size: 0.85em;
    color: #adb5bd;
    margin-top: 10px;
}
</style>

<script>
// Known problematic hash for testing
const PROBLEM_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// CSRF tokens for secure requests
const CSRF_TOKENS = <?php echo json_encode($csrf_tokens); ?>;

// Load system health status on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSystemHealthStatus();
});

// Load system health status
function loadSystemHealthStatus() {
    const formData = new FormData();
    formData.append('action', 'system_health_check');
    formData.append('csrf_token', CSRF_TOKENS['system_health_check']);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySystemHealthStatus(data.health_check);
        } else {
            showAlert('Failed to load system health status: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('System health check error:', error);
        showAlert('System health check failed', 'danger');
    });
}

// Display system health status
function displaySystemHealthStatus(healthCheck) {
    const container = document.getElementById('systemHealthStatus');
    let statusClass = 'success';
    let statusIcon = 'check-circle';
    let statusText = 'System Healthy';
    
    if (healthCheck.overall_status === 'critical') {
        statusClass = 'danger';
        statusIcon = 'times-circle';
        statusText = 'Critical Issues Detected';
    } else if (healthCheck.overall_status === 'warning') {
        statusClass = 'warning';
        statusIcon = 'exclamation-triangle';
        statusText = 'Issues Detected';
    }
    
    let html = `
        <div class="alert alert-${statusClass}">
            <h6><i class="fas fa-${statusIcon}"></i> ${statusText}</h6>
            <div class="row mt-3">
                <div class="col-md-4">
                    <strong>Total Issues:</strong> ${healthCheck.issues_found}
                </div>
                <div class="col-md-4">
                    <strong>Critical Issues:</strong> ${healthCheck.critical_issues}
                </div>
                <div class="col-md-4">
                    <strong>Components:</strong> ${healthCheck.components.length}
                </div>
            </div>
        </div>
        
        <div class="row">
    `;
    
    healthCheck.components.forEach(component => {
        let componentClass = 'success';
        let componentIcon = 'check-circle';
        
        if (component.status === 'critical') {
            componentClass = 'danger';
            componentIcon = 'times-circle';
        } else if (component.status === 'warning') {
            componentClass = 'warning';
            componentIcon = 'exclamation-triangle';
        }
        
        html += `
            <div class="col-md-4 mb-3">
                <div class="card bg-black border-${componentClass}">
                    <div class="card-body">
                        <h6 class="text-${componentClass}">
                            <i class="fas fa-${componentIcon}"></i>
                            ${component.name}
                        </h6>
                        ${component.issues.length > 0 ? 
                            '<ul class="small mb-0">' + 
                            component.issues.map(issue => `<li>${issue}</li>`).join('') + 
                            '</ul>' : 
                            '<small class="text-muted">No issues detected</small>'
                        }
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    if (healthCheck.recommendations.length > 0) {
        html += `
            <div class="mt-3">
                <h6 class="text-gold">Recommendations:</h6>
                <ul>
                    ${healthCheck.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

// Load the problematic hash for testing
function loadProblemHash() {
    document.getElementById('realTimePassword').value = 'admin123';
    document.getElementById('realTimeHash').value = PROBLEM_HASH;
    document.getElementById('realTimeUserId').value = '1';
    
    // Also load in step analysis
    document.getElementById('stepPassword').value = 'admin123';
    document.getElementById('stepHash').value = PROBLEM_HASH;
    document.getElementById('stepUserId').value = '1';
}

// Clear real-time form
function clearRealTimeForm() {
    document.getElementById('realTimePassword').value = '';
    document.getElementById('realTimeHash').value = '';
    document.getElementById('realTimeUserId').value = '';
    document.getElementById('realTimeStatus').style.display = 'none';
    document.getElementById('realTimeResults').style.display = 'none';
}

// Run real-time verification
function runRealTimeVerification() {
    const password = document.getElementById('realTimePassword').value;
    const hash = document.getElementById('realTimeHash').value;
    const userId = document.getElementById('realTimeUserId').value;
    
    if (!password || !hash) {
        showAlert('Password and hash are required', 'warning');
        return;
    }
    
    // Show status indicators
    document.getElementById('realTimeStatus').style.display = 'block';
    resetStatusIndicators();
    
    const formData = new FormData();
    formData.append('action', 'real_time_verification');
    formData.append('csrf_token', CSRF_TOKENS['real_time_verification']);
    formData.append('password', password);
    formData.append('hash', hash);
    if (userId) formData.append('user_id', userId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatusIndicators(data.data.status_indicators);
            displayRealTimeResults(data.data);
        } else {
            showAlert('Verification failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Real-time verification error:', error);
        showAlert('Real-time verification failed', 'danger');
    });
}

// Reset status indicators
function resetStatusIndicators() {
    const indicators = ['hashFormatStatus', 'verificationStatus', 'diagnosticsStatus', 'repairStatus'];
    indicators.forEach(id => {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');
        icon.className = 'fas fa-circle text-secondary';
    });
}

// Update status indicators
function updateStatusIndicators(indicators) {
    updateIndicator('hashFormatStatus', indicators.hash_format_valid);
    updateIndicator('verificationStatus', indicators.verification_successful);
    updateIndicator('diagnosticsStatus', indicators.has_diagnostics);
    updateIndicator('repairStatus', indicators.needs_repair, true); // Reverse logic for repair
}

// Update individual indicator
function updateIndicator(elementId, status, reverse = false) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('i');
    
    if (reverse) status = !status;
    
    if (status) {
        icon.className = 'fas fa-circle text-success';
    } else {
        icon.className = 'fas fa-circle text-danger';
    }
}

// Display real-time results
function displayRealTimeResults(data) {
    const container = document.getElementById('realTimeOutput');
    const resultsDiv = document.getElementById('realTimeResults');
    
    let html = `
        <div class="alert alert-${data.success ? 'success' : 'danger'}">
            <h6><i class="fas fa-${data.success ? 'check-circle' : 'times-circle'}"></i> 
            Verification ${data.success ? 'Successful' : 'Failed'}</h6>
        </div>
    `;
    
    if (data.performance) {
        html += `
            <div class="performance-metrics">
                <strong>Performance:</strong> ${data.performance.verification_time_ms}ms | 
                <strong>Memory:</strong> ${data.performance.memory_usage} | 
                <strong>PHP:</strong> ${data.performance.php_version}
            </div>
        `;
    }
    
    if (data.diagnostics) {
        html += '<div class="mt-3">';
        
        if (data.diagnostics.hash_format) {
            html += formatHashFormatDiagnostics(data.diagnostics.hash_format);
        }
        
        if (data.diagnostics.failure_analysis) {
            html += formatFailureAnalysis(data.diagnostics.failure_analysis);
        }
        
        html += '</div>';
    }
    
    container.innerHTML = html;
    resultsDiv.style.display = 'block';
}

// Format hash format diagnostics
function formatHashFormatDiagnostics(hashFormat) {
    let html = `
        <div class="card bg-black border-gold mb-3">
            <div class="card-header text-gold">Hash Format Validation</div>
            <div class="card-body">
                <p><strong>Valid:</strong> <span class="badge bg-${hashFormat.valid ? 'success' : 'danger'}">${hashFormat.valid ? 'Yes' : 'No'}</span></p>
                <p><strong>Length:</strong> ${hashFormat.length} / ${hashFormat.expected_length}</p>
                <p><strong>Prefix:</strong> ${hashFormat.prefix}</p>
    `;
    
    if (hashFormat.issues && hashFormat.issues.length > 0) {
        html += '<p><strong>Issues:</strong></p><ul>';
        hashFormat.issues.forEach(issue => {
            html += `<li class="text-danger">${issue}</li>`;
        });
        html += '</ul>';
    }
    
    html += '</div></div>';
    return html;
}

// Format failure analysis
function formatFailureAnalysis(analysis) {
    let html = `
        <div class="card bg-black border-warning mb-3">
            <div class="card-header text-warning">Failure Analysis</div>
            <div class="card-body">
                <p><strong>Timestamp:</strong> ${analysis.timestamp}</p>
                <p><strong>Password Length:</strong> ${analysis.password_length}</p>
    `;
    
    if (analysis.recommendations && analysis.recommendations.length > 0) {
        html += '<p><strong>Recommendations:</strong></p><ul>';
        analysis.recommendations.forEach(rec => {
            html += `<li class="text-warning">${rec}</li>`;
        });
        html += '</ul>';
    }
    
    html += '</div></div>';
    return html;
}

// Run step-by-step analysis
function runStepAnalysis() {
    const password = document.getElementById('stepPassword').value;
    const hash = document.getElementById('stepHash').value;
    const userId = document.getElementById('stepUserId').value;
    
    if (!password || !hash) {
        showAlert('Password and hash are required', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'step_by_step_analysis');
    formData.append('password', password);
    formData.append('hash', hash);
    if (userId) formData.append('user_id', userId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayStepAnalysisResults(data);
        } else {
            showAlert('Step analysis failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Step analysis error:', error);
        showAlert('Step analysis failed', 'danger');
    });
}

// Display step analysis results
function displayStepAnalysisResults(data) {
    const container = document.getElementById('stepAnalysisOutput');
    const resultsDiv = document.getElementById('stepAnalysisResults');
    
    let html = `
        <div class="alert alert-${data.overall_result ? 'success' : 'danger'}">
            <h6>Overall Result: ${data.overall_result ? 'Success' : 'Failed'}</h6>
            <small>Completed ${data.total_steps} steps</small>
        </div>
        
        <div class="steps-container">
    `;
    
    data.steps.forEach(step => {
        html += `
            <div class="step-item ${step.status}">
                <h6>Step ${step.step}: ${step.title}</h6>
                <p>${step.details}</p>
                ${step.data ? formatStepData(step.data) : ''}
            </div>
        `;
    });
    
    html += '</div>';
    
    container.innerHTML = html;
    resultsDiv.style.display = 'block';
}

// Format step data
function formatStepData(data) {
    if (!data || typeof data !== 'object') return '';
    
    let html = '<div class="step-data small">';
    
    // Handle different data types
    if (data.valid !== undefined) {
        // Hash validation data
        html += `<strong>Valid:</strong> ${data.valid ? 'Yes' : 'No'}`;
        if (data.issues && data.issues.length > 0) {
            html += '<br><strong>Issues:</strong> ' + data.issues.join(', ');
        }
    } else if (data.verification_result !== undefined) {
        // Verification result data
        html += `<strong>Result:</strong> ${data.verification_result ? 'Success' : 'Failed'}`;
    } else if (data.actions) {
        // Recommendations data
        html += '<strong>Recommended Actions:</strong><ul>';
        data.actions.forEach(action => {
            html += `<li>${action.description} (${action.confidence}% confidence)</li>`;
        });
        html += '</ul>';
    }
    
    html += '</div>';
    return html;
}

// Get repair recommendations
function getRepairRecommendations() {
    const userId = document.getElementById('repairUserId').value;
    const hash = document.getElementById('repairHash').value;
    const password = document.getElementById('repairPassword').value;
    
    if (!userId && !hash) {
        showAlert('User ID or hash is required', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'automated_repair_recommendation');
    if (userId) formData.append('user_id', userId);
    if (hash) formData.append('hash', hash);
    if (password) formData.append('password', password);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRepairRecommendations(data.recommendations, userId);
        } else {
            showAlert('Failed to generate recommendations: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Repair recommendations error:', error);
        showAlert('Failed to generate recommendations', 'danger');
    });
}

// Display repair recommendations
function displayRepairRecommendations(recommendations, userId) {
    const container = document.getElementById('repairRecommendationOutput');
    const resultsDiv = document.getElementById('repairRecommendationResults');
    
    let html = `
        <div class="alert alert-${recommendations.priority === 'high' ? 'danger' : recommendations.priority === 'medium' ? 'warning' : 'info'}">
            <h6>Priority: ${recommendations.priority.toUpperCase()}</h6>
            <p>Confidence: ${recommendations.confidence}% | Estimated Success: ${recommendations.estimated_success}%</p>
        </div>
    `;
    
    if (recommendations.actions.length > 0) {
        html += '<h6 class="text-gold">Recommended Actions:</h6>';
        recommendations.actions.forEach(action => {
            html += `
                <div class="recommendation-item">
                    <h6>${action.description}</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <small>Type: ${action.type}</small>
                        <small>Confidence: ${action.confidence}%</small>
                    </div>
                    <div class="confidence-bar">
                        <div class="confidence-fill" style="width: ${action.confidence}%"></div>
                    </div>
                    ${action.requires_password ? '<small class="text-warning">⚠ Requires password</small>' : ''}
                </div>
            `;
        });
        
        // Show repair execution section if applicable
        if (userId && recommendations.actions.some(action => action.requires_password)) {
            document.getElementById('executeRepairUserId').value = userId;
            document.getElementById('repairExecutionSection').style.display = 'block';
        }
    }
    
    if (recommendations.risks.length > 0) {
        html += '<h6 class="text-danger">Risks:</h6><ul>';
        recommendations.risks.forEach(risk => {
            html += `<li class="text-warning">${risk}</li>`;
        });
        html += '</ul>';
    }
    
    container.innerHTML = html;
    resultsDiv.style.display = 'block';
}

// Execute repair
function executeRepair() {
    const userId = document.getElementById('executeRepairUserId').value;
    const newPassword = document.getElementById('executeRepairPassword').value;
    const forceRepair = document.getElementById('forceRepair').checked;
    
    if (!userId || !newPassword) {
        showAlert('User ID and new password are required', 'warning');
        return;
    }
    
    if (!confirm('Are you sure you want to execute this repair? A backup will be created.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'execute_repair');
    formData.append('user_id', userId);
    formData.append('new_password', newPassword);
    formData.append('repair_type', 'regenerate');
    if (forceRepair) formData.append('force_repair', '1');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Repair executed successfully: ' + data.message, 'success');
            hideRepairExecution();
            // Clear form
            document.getElementById('executeRepairPassword').value = '';
            document.getElementById('forceRepair').checked = false;
        } else {
            showAlert('Repair failed: ' + (data.error || 'Unknown error'), 'danger');
            if (data.warnings && data.warnings.length > 0) {
                data.warnings.forEach(warning => {
                    showAlert('Warning: ' + warning, 'warning');
                });
            }
        }
    })
    .catch(error => {
        console.error('Repair execution error:', error);
        showAlert('Repair execution failed', 'danger');
    });
}

// Hide repair execution section
function hideRepairExecution() {
    document.getElementById('repairExecutionSection').style.display = 'none';
}

// Run bulk analysis
function runBulkAnalysis() {
    const limit = document.getElementById('bulkAnalysisLimit').value;
    const offset = document.getElementById('bulkAnalysisOffset').value;
    
    const formData = new FormData();
    formData.append('action', 'bulk_user_analysis');
    formData.append('limit', limit);
    formData.append('offset', offset);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayBulkAnalysisResults(data.analysis);
        } else {
            showAlert('Bulk analysis failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Bulk analysis error:', error);
        showAlert('Bulk analysis failed', 'danger');
    });
}

// Display bulk analysis results
function displayBulkAnalysisResults(analysis) {
    const container = document.getElementById('bulkAnalysisOutput');
    const resultsDiv = document.getElementById('bulkAnalysisResults');
    
    let html = `
        <div class="alert alert-info">
            <h6>Analysis Summary</h6>
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Users:</strong> ${analysis.total_users}
                </div>
                <div class="col-md-3">
                    <strong>Analyzed:</strong> ${analysis.analyzed_users}
                </div>
                <div class="col-md-3">
                    <strong>Healthy:</strong> ${analysis.summary.healthy}
                </div>
                <div class="col-md-3">
                    <strong>Issues:</strong> ${analysis.summary.corrupted}
                </div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4>${analysis.summary.healthy}</h4>
                        <small>Healthy</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h4>${analysis.summary.corrupted - analysis.summary.critical}</h4>
                        <small>Minor Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4>${analysis.summary.critical}</h4>
                        <small>Critical</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>${analysis.summary.repairable}</h4>
                        <small>Repairable</small>
                    </div>
                </div>
            </div>
        </div>
        
        <h6 class="text-gold">User Details:</h6>
        <div class="user-analysis-list">
    `;
    
    analysis.users.forEach(user => {
        html += `
            <div class="user-analysis-item ${user.status}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6>User #${user.user_id}: ${user.email}</h6>
                        <span class="badge bg-${getStatusColor(user.status)}">${user.status}</span>
                    </div>
                    <div class="text-end">
                        ${user.recommendations.length > 0 ? 
                            '<small class="text-muted">' + user.recommendations.join(', ') + '</small>' : 
                            ''
                        }
                    </div>
                </div>
                ${user.issues.length > 0 ? 
                    '<div class="mt-2"><small class="text-danger">Issues: ' + user.issues.join(', ') + '</small></div>' : 
                    ''
                }
            </div>
        `;
    });
    
    html += '</div>';
    
    // Add pagination controls if needed
    if (analysis.total_users > analysis.analyzed_users) {
        const nextOffset = analysis.offset + analysis.limit;
        html += `
            <div class="mt-3 text-center">
                <button class="btn btn-outline-gold" onclick="loadNextBatch(${nextOffset})">
                    Load Next Batch (${Math.min(analysis.limit, analysis.total_users - nextOffset)} users)
                </button>
            </div>
        `;
    }
    
    container.innerHTML = html;
    resultsDiv.style.display = 'block';
}

// Get status color for badges
function getStatusColor(status) {
    switch (status) {
        case 'healthy': return 'success';
        case 'minor': return 'warning';
        case 'major': return 'danger';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

// Load next batch for bulk analysis
function loadNextBatch(offset) {
    document.getElementById('bulkAnalysisOffset').value = offset;
    runBulkAnalysis();
}

// Secure AJAX helper function
function makeSecureRequest(action, data = {}) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('action', action);
        
        // Add CSRF token
        if (CSRF_TOKENS[action]) {
            formData.append('csrf_token', CSRF_TOKENS[action]);
        }
        
        // Add data
        for (const key in data) {
            formData.append(key, data[key]);
        }
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.status === 403) {
                showAlert('Access denied. Please refresh the page and try again.', 'danger');
                setTimeout(() => location.reload(), 2000);
                return Promise.reject(new Error('Access denied'));
            }
            if (response.status === 429) {
                return response.json().then(data => {
                    showAlert('Rate limit exceeded: ' + data.error, 'warning');
                    return Promise.reject(new Error('Rate limit exceeded'));
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success === false) {
                reject(new Error(data.error || 'Request failed'));
            } else {
                resolve(data);
            }
        })
        .catch(error => {
            console.error('Secure request error:', error);
            reject(error);
        });
    });
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
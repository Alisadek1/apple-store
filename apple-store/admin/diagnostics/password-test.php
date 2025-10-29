<?php
/**
 * Password Verification Diagnostic Tool
 * 
 * Interactive testing interface for password verification issues
 * Provides hash comparison, analysis, and repair capabilities
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/auth-functions.php';
require_once __DIR__ . '/../../includes/security-manager.php';

$current_page = 'password-test';
$lang = getLang();

// Initialize security manager
$security = new SecurityManager();

// Validate admin access
$access_validation = $security->validateAdminAccess('password_diagnostics');
if (!$access_validation['valid']) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'error' => 'Access denied: ' . implode(', ', $access_validation['errors'])
    ]));
}

// Handle AJAX requests
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
    $rate_limit_action = getRateLimitAction($_POST['action']);
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
        case 'test_verification':
            echo json_encode(handleTestVerification());
            exit;
            
        case 'analyze_hash':
            echo json_encode(handleAnalyzeHash());
            exit;
            
        case 'check_database':
            echo json_encode(handleDatabaseCheck());
            exit;
            
        case 'generate_repair_script':
            echo json_encode(handleGenerateRepairScript());
            exit;
            
        case 'test_specific_user':
            echo json_encode(handleTestSpecificUser());
            exit;
    }
}

/**
 * Map actions to rate limit categories
 */
function getRateLimitAction($action) {
    $action_mapping = [
        'test_verification' => 'diagnostic_test',
        'analyze_hash' => 'diagnostic_test',
        'check_database' => 'diagnostic_test',
        'generate_repair_script' => 'repair_operation',
        'test_specific_user' => 'diagnostic_test'
    ];
    
    return $action_mapping[$action] ?? 'diagnostic_test';
}

/**
 * Handle password verification testing
 */
function handleTestVerification() {
    $password = $_POST['password'] ?? '';
    $hash = $_POST['hash'] ?? '';
    
    if (empty($password) || empty($hash)) {
        return ['success' => false, 'error' => 'Password and hash are required'];
    }
    
    $result = verifyPasswordSecure($password, $hash);
    return ['success' => true, 'result' => $result];
}

/**
 * Handle hash analysis
 */
function handleAnalyzeHash() {
    $hash = $_POST['hash'] ?? '';
    
    if (empty($hash)) {
        return ['success' => false, 'error' => 'Hash is required'];
    }
    
    $validation = validateHashFormat($hash);
    $diagnosis = diagnoseVerificationFailure('test', $hash);
    
    return [
        'success' => true,
        'validation' => $validation,
        'diagnosis' => $diagnosis
    ];
}

/**
 * Handle database integrity check
 */
function handleDatabaseCheck() {
    try {
        // Perform comprehensive database integrity check
        $integrity_check = performDatabaseIntegrityCheck();
        
        // Format the results for the frontend
        $result = [
            'success' => true,
            'overall_status' => $integrity_check['overall_status'],
            'column_validation' => $integrity_check['column_validation'],
            'charset_validation' => $integrity_check['charset_validation'],
            'corruption_summary' => $integrity_check['corruption_summary'],
            'recommendations' => $integrity_check['recommendations']
        ];
        
        // Include detailed hash analysis for first 10 users
        $hash_analysis = [];
        $user_count = min(10, count($integrity_check['user_hash_analysis']));
        
        for ($i = 0; $i < $user_count; $i++) {
            $user_analysis = $integrity_check['user_hash_analysis'][$i];
            $hash_retrieval = $user_analysis['hash_retrieval'];
            $corruption_analysis = $user_analysis['corruption_analysis'];
            
            $formatted_analysis = [
                'user_id' => $user_analysis['user_id'],
                'email' => $user_analysis['email'],
                'hash_length' => $hash_retrieval['success'] ? strlen($hash_retrieval['hash']) : 0,
                'hash_preview' => $hash_retrieval['success'] ? substr($hash_retrieval['hash'], 0, 20) . '...' : 'N/A',
                'validation' => $hash_retrieval['success'] ? validateHashFormat($hash_retrieval['hash']) : ['valid' => false],
                'encoding_issues' => $hash_retrieval['encoding_issues'] ?? [],
                'whitespace_issues' => $hash_retrieval['whitespace_issues'] ?? [],
                'corruption_detected' => $hash_retrieval['corruption_detected'] ?? false,
                'corruption_analysis' => $corruption_analysis
            ];
            
            $hash_analysis[] = $formatted_analysis;
        }
        
        $result['hash_analysis'] = $hash_analysis;
        
        // Add legacy format for backward compatibility
        $result['column_info'] = $integrity_check['column_validation']['column_info'] ?? null;
        $result['database_charset'] = $integrity_check['charset_validation']['database_info']['charset'] ?? 'unknown';
        $result['database_collation'] = $integrity_check['charset_validation']['database_info']['collation'] ?? 'unknown';
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Handle repair script generation
 */
function handleGenerateRepairScript() {
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($user_id)) {
        return ['success' => false, 'error' => 'User ID is required'];
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $script_lines = [];
        $script_lines[] = "-- Password Hash Repair Script";
        $script_lines[] = "-- Generated: " . date('Y-m-d H:i:s');
        $script_lines[] = "-- User: {$user['email']} (ID: {$user['id']})";
        $script_lines[] = "";
        $script_lines[] = "-- Backup current hash";
        $script_lines[] = "CREATE TABLE IF NOT EXISTS password_backup_" . date('Ymd_His') . " AS";
        $script_lines[] = "SELECT id, email, password, NOW() as backup_date FROM users WHERE id = {$user['id']};";
        $script_lines[] = "";
        
        if (!empty($new_password)) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $script_lines[] = "-- Update with new hash";
            $script_lines[] = "UPDATE users SET password = '{$new_hash}' WHERE id = {$user['id']};";
        } else {
            $script_lines[] = "-- Replace with your new hash:";
            $script_lines[] = "-- UPDATE users SET password = 'NEW_HASH_HERE' WHERE id = {$user['id']};";
        }
        
        $script_lines[] = "";
        $script_lines[] = "-- Verify the update";
        $script_lines[] = "SELECT id, email, LENGTH(password) as hash_length, SUBSTRING(password, 1, 10) as hash_preview";
        $script_lines[] = "FROM users WHERE id = {$user['id']};";
        
        return [
            'success' => true,
            'script' => implode("\n", $script_lines),
            'user' => $user
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Handle testing specific user credentials
 */
function handleTestSpecificUser() {
    $user_id = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($user_id) || empty($password)) {
        return ['success' => false, 'error' => 'User ID and password are required'];
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $result = verifyPasswordSecure($password, $user['password'], $user['id']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'hash_preview' => substr($user['password'], 0, 20) . '...'
            ],
            'verification_result' => $result
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Generate CSRF tokens for different actions
$csrf_tokens = [
    'test_verification' => $security->generateCSRFToken('test_verification'),
    'analyze_hash' => $security->generateCSRFToken('analyze_hash'),
    'check_database' => $security->generateCSRFToken('check_database'),
    'generate_repair_script' => $security->generateCSRFToken('generate_repair_script'),
    'test_specific_user' => $security->generateCSRFToken('test_specific_user')
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 text-gold">
                    <i class="fas fa-shield-alt"></i>
                    <?php echo $lang === 'ar' ? 'أداة تشخيص كلمات المرور' : 'Password Diagnostic Tool'; ?>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo ADMIN_URL; ?>/index.php" class="text-gold">
                                <?php echo t('dashboard'); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?php echo $lang === 'ar' ? 'تشخيص كلمات المرور' : 'Password Diagnostics'; ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Test Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark-gray border-gold">
                <div class="card-header bg-black border-gold">
                    <ul class="nav nav-tabs card-header-tabs" id="diagnosticTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active bg-dark-gray text-light-gray border-gold" 
                                    id="verification-tab" data-bs-toggle="tab" data-bs-target="#verification" 
                                    type="button" role="tab">
                                <i class="fas fa-key"></i>
                                <?php echo $lang === 'ar' ? 'اختبار التحقق' : 'Verification Test'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" 
                                    type="button" role="tab">
                                <i class="fas fa-search"></i>
                                <?php echo $lang === 'ar' ? 'تحليل الهاش' : 'Hash Analysis'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="database-tab" data-bs-toggle="tab" data-bs-target="#database" 
                                    type="button" role="tab">
                                <i class="fas fa-database"></i>
                                <?php echo $lang === 'ar' ? 'فحص قاعدة البيانات' : 'Database Check'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="repair-tab" data-bs-toggle="tab" data-bs-target="#repair" 
                                    type="button" role="tab">
                                <i class="fas fa-tools"></i>
                                <?php echo $lang === 'ar' ? 'إصلاح' : 'Repair'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-dark-gray text-light-gray border-gold" 
                                    id="user-test-tab" data-bs-toggle="tab" data-bs-target="#user-test" 
                                    type="button" role="tab">
                                <i class="fas fa-user-check"></i>
                                <?php echo $lang === 'ar' ? 'اختبار المستخدم' : 'User Test'; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="diagnosticTabContent">
                        
                        <!-- Verification Test Tab -->
                        <div class="tab-pane fade show active" id="verification" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-key"></i>
                                <?php echo $lang === 'ar' ? 'اختبار التحقق من كلمة المرور' : 'Password Verification Test'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'اختبر التحقق من كلمة المرور مقابل هاش محدد' : 'Test password verification against a specific hash'; ?>
                            </p>
                            
                            <form id="verificationForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?>
                                            </label>
                                            <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="testPassword" name="password" placeholder="Enter password to test">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'الهاش المخزن' : 'Stored Hash'; ?>
                                            </label>
                                            <input type="text" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="testHash" name="hash" placeholder="Enter hash to test against">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-gold">
                                    <i class="fas fa-play"></i>
                                    <?php echo $lang === 'ar' ? 'تشغيل الاختبار' : 'Run Test'; ?>
                                </button>
                                
                                <button type="button" class="btn btn-outline-gold" onclick="loadProblemHash()">
                                    <i class="fas fa-bug"></i>
                                    <?php echo $lang === 'ar' ? 'تحميل الهاش المشكل' : 'Load Problem Hash'; ?>
                                </button>
                            </form>
                            
                            <div id="verificationResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج الاختبار' : 'Test Results'; ?></h6>
                                <div id="verificationOutput"></div>
                            </div>
                        </div>
                        
                        <!-- Hash Analysis Tab -->
                        <div class="tab-pane fade" id="analysis" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-search"></i>
                                <?php echo $lang === 'ar' ? 'تحليل الهاش' : 'Hash Analysis'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'تحليل تفصيلي لتنسيق وصحة الهاش' : 'Detailed analysis of hash format and validity'; ?>
                            </p>
                            
                            <form id="analysisForm">
                                <div class="mb-3">
                                    <label class="form-label text-light-gray">
                                        <?php echo $lang === 'ar' ? 'الهاش للتحليل' : 'Hash to Analyze'; ?>
                                    </label>
                                    <textarea class="form-control bg-dark-gray text-light-gray border-gold" 
                                              id="analysisHash" name="hash" rows="3" 
                                              placeholder="Enter hash for detailed analysis"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-gold">
                                    <i class="fas fa-microscope"></i>
                                    <?php echo $lang === 'ar' ? 'تحليل' : 'Analyze'; ?>
                                </button>
                            </form>
                            
                            <div id="analysisResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج التحليل' : 'Analysis Results'; ?></h6>
                                <div id="analysisOutput"></div>
                            </div>
                        </div>
                        
                        <!-- Database Check Tab -->
                        <div class="tab-pane fade" id="database" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-database"></i>
                                <?php echo $lang === 'ar' ? 'فحص قاعدة البيانات' : 'Database Integrity Check'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'فحص مواصفات العمود وسلامة البيانات' : 'Check column specifications and data integrity'; ?>
                            </p>
                            
                            <button type="button" class="btn btn-gold" onclick="runDatabaseCheck()">
                                <i class="fas fa-search"></i>
                                <?php echo $lang === 'ar' ? 'تشغيل فحص قاعدة البيانات' : 'Run Database Check'; ?>
                            </button>
                            
                            <div id="databaseResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج فحص قاعدة البيانات' : 'Database Check Results'; ?></h6>
                                <div id="databaseOutput"></div>
                            </div>
                        </div>
                        
                        <!-- Repair Tab -->
                        <div class="tab-pane fade" id="repair" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-tools"></i>
                                <?php echo $lang === 'ar' ? 'إنشاء سكريبت الإصلاح' : 'Generate Repair Script'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'إنشاء سكريبت SQL لإصلاح الهاش التالف' : 'Generate SQL script to repair corrupted hash'; ?>
                            </p>
                            
                            <form id="repairForm">
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
                                                <?php echo $lang === 'ar' ? 'كلمة المرور الجديدة (اختياري)' : 'New Password (Optional)'; ?>
                                            </label>
                                            <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="repairPassword" name="new_password" placeholder="Leave empty for manual hash">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-gold">
                                    <i class="fas fa-code"></i>
                                    <?php echo $lang === 'ar' ? 'إنشاء سكريبت' : 'Generate Script'; ?>
                                </button>
                            </form>
                            
                            <div id="repairResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'سكريبت الإصلاح' : 'Repair Script'; ?></h6>
                                <div id="repairOutput"></div>
                            </div>
                        </div>
                        
                        <!-- User Test Tab -->
                        <div class="tab-pane fade" id="user-test" role="tabpanel">
                            <h5 class="text-gold mb-3">
                                <i class="fas fa-user-check"></i>
                                <?php echo $lang === 'ar' ? 'اختبار مستخدم محدد' : 'Test Specific User'; ?>
                            </h5>
                            <p class="text-light-gray">
                                <?php echo $lang === 'ar' ? 'اختبر بيانات اعتماد مستخدم محدد' : 'Test specific user credentials'; ?>
                            </p>
                            
                            <form id="userTestForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'معرف المستخدم' : 'User ID'; ?>
                                            </label>
                                            <input type="number" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="userTestId" name="user_id" placeholder="Enter user ID">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-light-gray">
                                                <?php echo $lang === 'ar' ? 'كلمة المرور' : 'Password'; ?>
                                            </label>
                                            <input type="password" class="form-control bg-dark-gray text-light-gray border-gold" 
                                                   id="userTestPassword" name="password" placeholder="Enter password to test">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-gold">
                                    <i class="fas fa-user-check"></i>
                                    <?php echo $lang === 'ar' ? 'اختبار المستخدم' : 'Test User'; ?>
                                </button>
                                
                                <button type="button" class="btn btn-outline-gold" onclick="loadAdminUser()">
                                    <i class="fas fa-user-shield"></i>
                                    <?php echo $lang === 'ar' ? 'تحميل المدير' : 'Load Admin'; ?>
                                </button>
                            </form>
                            
                            <div id="userTestResults" class="mt-4" style="display: none;">
                                <h6 class="text-gold"><?php echo $lang === 'ar' ? 'نتائج اختبار المستخدم' : 'User Test Results'; ?></h6>
                                <div id="userTestOutput"></div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Known problematic hash for testing
const PROBLEM_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// CSRF tokens for secure requests
const CSRF_TOKENS = <?php echo json_encode($csrf_tokens); ?>;

// Load the problematic hash for testing
function loadProblemHash() {
    document.getElementById('testPassword').value = 'admin123';
    document.getElementById('testHash').value = PROBLEM_HASH;
}

// Load admin user for testing
function loadAdminUser() {
    document.getElementById('userTestId').value = '1';
    document.getElementById('userTestPassword').value = 'admin123';
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

// Format diagnostic results as HTML
function formatDiagnosticResults(data) {
    let html = '<div class="diagnostic-results">';
    
    if (data.result) {
        const result = data.result;
        
        // Success/Failure indicator
        html += `<div class="alert alert-${result.success ? 'success' : 'danger'}">`;
        html += `<h6><i class="fas fa-${result.success ? 'check-circle' : 'times-circle'}"></i> `;
        html += `Verification ${result.success ? 'Successful' : 'Failed'}</h6>`;
        html += `</div>`;
        
        // Hash validation
        if (result.diagnostics.hash_format) {
            html += '<div class="card bg-dark-gray border-gold mb-3">';
            html += '<div class="card-header bg-black text-gold">Hash Format Validation</div>';
            html += '<div class="card-body">';
            
            const hashFormat = result.diagnostics.hash_format;
            html += `<p><strong>Valid:</strong> <span class="badge bg-${hashFormat.valid ? 'success' : 'danger'}">${hashFormat.valid ? 'Yes' : 'No'}</span></p>`;
            html += `<p><strong>Length:</strong> ${hashFormat.length} / ${hashFormat.expected_length}</p>`;
            html += `<p><strong>Prefix:</strong> ${hashFormat.prefix}</p>`;
            
            if (hashFormat.issues && hashFormat.issues.length > 0) {
                html += '<p><strong>Issues:</strong></p><ul>';
                hashFormat.issues.forEach(issue => {
                    html += `<li class="text-danger">${issue}</li>`;
                });
                html += '</ul>';
            }
            
            html += '</div></div>';
        }
        
        // Failure analysis
        if (result.diagnostics.failure_analysis) {
            html += '<div class="card bg-dark-gray border-gold mb-3">';
            html += '<div class="card-header bg-black text-gold">Failure Analysis</div>';
            html += '<div class="card-body">';
            
            const analysis = result.diagnostics.failure_analysis;
            html += `<p><strong>Timestamp:</strong> ${analysis.timestamp}</p>`;
            html += `<p><strong>Password Length:</strong> ${analysis.password_length}</p>`;
            
            if (analysis.recommendations && analysis.recommendations.length > 0) {
                html += '<p><strong>Recommendations:</strong></p><ul>';
                analysis.recommendations.forEach(rec => {
                    html += `<li class="text-warning">${rec}</li>`;
                });
                html += '</ul>';
            }
            
            html += '</div></div>';
        }
    }
    
    html += '</div>';
    return html;
}

// Format analysis results as HTML
function formatAnalysisResults(data) {
    let html = '<div class="analysis-results">';
    
    if (data.validation) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Hash Validation</div>';
        html += '<div class="card-body">';
        
        const validation = data.validation;
        html += `<p><strong>Valid:</strong> <span class="badge bg-${validation.valid ? 'success' : 'danger'}">${validation.valid ? 'Yes' : 'No'}</span></p>`;
        html += `<p><strong>Length:</strong> ${validation.length} / ${validation.expected_length}</p>`;
        html += `<p><strong>Prefix:</strong> ${validation.prefix}</p>`;
        
        if (validation.issues && validation.issues.length > 0) {
            html += '<p><strong>Issues:</strong></p><ul>';
            validation.issues.forEach(issue => {
                html += `<li class="text-danger">${issue}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div></div>';
    }
    
    if (data.diagnosis) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Detailed Diagnosis</div>';
        html += '<div class="card-body">';
        
        const diagnosis = data.diagnosis;
        
        // Hash analysis
        if (diagnosis.hash_analysis) {
            html += '<h6 class="text-gold">Hash Analysis</h6>';
            const hashAnalysis = diagnosis.hash_analysis;
            html += `<p><strong>Hash Length:</strong> ${hashAnalysis.hash_length}</p>`;
            html += `<p><strong>Hash Prefix:</strong> ${hashAnalysis.hash_prefix}</p>`;
            html += `<p><strong>UTF-8 Encoding:</strong> <span class="badge bg-${hashAnalysis.encoding_check ? 'success' : 'danger'}">${hashAnalysis.encoding_check ? 'Valid' : 'Invalid'}</span></p>`;
            html += `<p><strong>Contains Null Bytes:</strong> <span class="badge bg-${hashAnalysis.contains_null_bytes ? 'danger' : 'success'}">${hashAnalysis.contains_null_bytes ? 'Yes' : 'No'}</span></p>`;
        }
        
        // Environment check
        if (diagnosis.environment_check) {
            html += '<h6 class="text-gold mt-3">Environment Check</h6>';
            const envCheck = diagnosis.environment_check;
            html += `<p><strong>PHP Version:</strong> ${envCheck.php_version}</p>`;
            html += `<p><strong>Password Functions:</strong> <span class="badge bg-${envCheck.password_hash_available && envCheck.password_verify_available ? 'success' : 'danger'}">${envCheck.password_hash_available && envCheck.password_verify_available ? 'Available' : 'Missing'}</span></p>`;
            html += `<p><strong>Test Hash Verification:</strong> <span class="badge bg-${envCheck.test_hash_verify ? 'success' : 'danger'}">${envCheck.test_hash_verify ? 'Working' : 'Failed'}</span></p>`;
        }
        
        // Recommendations
        if (diagnosis.recommendations && diagnosis.recommendations.length > 0) {
            html += '<h6 class="text-gold mt-3">Recommendations</h6><ul>';
            diagnosis.recommendations.forEach(rec => {
                html += `<li class="text-warning">${rec}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div></div>';
    }
    
    html += '</div>';
    return html;
}

// Format database results as HTML
function formatDatabaseResults(data) {
    let html = '<div class="database-results">';
    
    // Overall status indicator
    if (data.overall_status) {
        const statusClass = data.overall_status === 'good' ? 'success' : 
                           data.overall_status === 'warning' ? 'warning' : 'danger';
        const statusIcon = data.overall_status === 'good' ? 'check-circle' : 
                          data.overall_status === 'warning' ? 'exclamation-triangle' : 'times-circle';
        
        html += `<div class="alert alert-${statusClass} mb-3">`;
        html += `<h6><i class="fas fa-${statusIcon}"></i> Overall Database Status: ${data.overall_status.toUpperCase()}</h6>`;
        html += `</div>`;
    }
    
    // Column validation results
    if (data.column_validation) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Password Column Validation</div>';
        html += '<div class="card-body">';
        
        const colVal = data.column_validation;
        html += `<p><strong>Status:</strong> <span class="badge bg-${colVal.valid ? 'success' : 'danger'}">${colVal.valid ? 'Valid' : 'Issues Found'}</span></p>`;
        
        if (colVal.column_info) {
            const col = colVal.column_info;
            html += `<p><strong>Type:</strong> ${col.Type}</p>`;
            html += `<p><strong>Null:</strong> ${col.Null}</p>`;
            html += `<p><strong>Default:</strong> ${col.Default || 'None'}</p>`;
            html += `<p><strong>Collation:</strong> ${col.Collation || 'None'}</p>`;
        }
        
        if (colVal.issues && colVal.issues.length > 0) {
            html += '<p><strong>Issues:</strong></p><ul>';
            colVal.issues.forEach(issue => {
                html += `<li class="text-danger">${issue}</li>`;
            });
            html += '</ul>';
        }
        
        if (colVal.recommendations && colVal.recommendations.length > 0) {
            html += '<p><strong>Recommendations:</strong></p><ul>';
            colVal.recommendations.forEach(rec => {
                html += `<li class="text-warning">${rec}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div></div>';
    }
    
    // Charset validation results
    if (data.charset_validation) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Charset & Collation Validation</div>';
        html += '<div class="card-body">';
        
        const charVal = data.charset_validation;
        html += `<p><strong>Status:</strong> <span class="badge bg-${charVal.valid ? 'success' : 'danger'}">${charVal.valid ? 'Valid' : 'Issues Found'}</span></p>`;
        
        if (charVal.database_info) {
            html += `<p><strong>Database Charset:</strong> ${charVal.database_info.charset}</p>`;
            html += `<p><strong>Database Collation:</strong> ${charVal.database_info.collation}</p>`;
        }
        
        if (charVal.column_info) {
            html += `<p><strong>Column Charset:</strong> ${charVal.column_info.charset || 'Default'}</p>`;
            html += `<p><strong>Column Collation:</strong> ${charVal.column_info.collation || 'Default'}</p>`;
        }
        
        if (charVal.issues && charVal.issues.length > 0) {
            html += '<p><strong>Issues:</strong></p><ul>';
            charVal.issues.forEach(issue => {
                html += `<li class="text-danger">${issue}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div></div>';
    }
    
    // Corruption summary
    if (data.corruption_summary) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Hash Corruption Summary</div>';
        html += '<div class="card-body">';
        
        const summary = data.corruption_summary;
        html += `<p><strong>Total Users Analyzed:</strong> ${summary.total_users}</p>`;
        html += `<p><strong>Corrupted Hashes:</strong> <span class="badge bg-${summary.corrupted_hashes > 0 ? 'danger' : 'success'}">${summary.corrupted_hashes}</span></p>`;
        html += `<p><strong>Repairable Issues:</strong> <span class="badge bg-warning">${summary.repairable_issues}</span></p>`;
        html += `<p><strong>Critical Issues:</strong> <span class="badge bg-danger">${summary.critical_issues}</span></p>`;
        
        html += '</div></div>';
    }
    
    // Detailed hash analysis
    if (data.hash_analysis && data.hash_analysis.length > 0) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Detailed Hash Analysis</div>';
        html += '<div class="card-body">';
        html += '<div class="table-responsive">';
        html += '<table class="table table-dark table-striped">';
        html += '<thead><tr><th>User ID</th><th>Email</th><th>Length</th><th>Preview</th><th>Status</th><th>Issues</th></tr></thead>';
        html += '<tbody>';
        
        data.hash_analysis.forEach(user => {
            const hasIssues = user.encoding_issues.length > 0 || user.whitespace_issues.length > 0 || user.corruption_detected;
            const statusClass = user.validation.valid && !hasIssues ? 'success' : 'danger';
            const statusText = user.validation.valid && !hasIssues ? 'Valid' : 'Issues';
            
            html += `<tr>`;
            html += `<td>${user.user_id}</td>`;
            html += `<td>${user.email}</td>`;
            html += `<td>${user.hash_length}</td>`;
            html += `<td><code>${user.hash_preview}</code></td>`;
            html += `<td><span class="badge bg-${statusClass}">${statusText}</span></td>`;
            
            // Issues column
            html += '<td>';
            if (user.encoding_issues.length > 0) {
                html += '<span class="badge bg-danger me-1">Encoding</span>';
            }
            if (user.whitespace_issues.length > 0) {
                html += '<span class="badge bg-warning me-1">Whitespace</span>';
            }
            if (user.corruption_detected) {
                html += '<span class="badge bg-danger me-1">Corrupted</span>';
            }
            if (!hasIssues && user.validation.valid) {
                html += '<span class="text-success">None</span>';
            }
            html += '</td>';
            
            html += `</tr>`;
        });
        
        html += '</tbody></table>';
        html += '</div></div></div>';
    }
    
    // Recommendations
    if (data.recommendations && data.recommendations.length > 0) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">Recommendations</div>';
        html += '<div class="card-body">';
        html += '<ul>';
        data.recommendations.forEach(rec => {
            html += `<li class="text-warning">${rec}</li>`;
        });
        html += '</ul>';
        html += '</div></div>';
    }
    
    html += '</div>';
    return html;
}

// Format repair script results
function formatRepairResults(data) {
    let html = '<div class="repair-results">';
    
    if (data.user) {
        html += '<div class="alert alert-info">';
        html += `<h6>User Information</h6>`;
        html += `<p><strong>ID:</strong> ${data.user.id}</p>`;
        html += `<p><strong>Email:</strong> ${data.user.email}</p>`;
        html += '</div>';
    }
    
    if (data.script) {
        html += '<div class="card bg-dark-gray border-gold">';
        html += '<div class="card-header bg-black text-gold d-flex justify-content-between align-items-center">';
        html += '<span>Generated SQL Script</span>';
        html += '<button class="btn btn-sm btn-outline-gold" onclick="copyScript()">Copy Script</button>';
        html += '</div>';
        html += '<div class="card-body">';
        html += `<pre id="repairScript" class="bg-black text-light-gray p-3 rounded"><code>${data.script}</code></pre>`;
        html += '</div></div>';
    }
    
    html += '</div>';
    return html;
}

// Format user test results
function formatUserTestResults(data) {
    let html = '<div class="user-test-results">';
    
    if (data.user) {
        html += '<div class="card bg-dark-gray border-gold mb-3">';
        html += '<div class="card-header bg-black text-gold">User Information</div>';
        html += '<div class="card-body">';
        html += `<p><strong>ID:</strong> ${data.user.id}</p>`;
        html += `<p><strong>Email:</strong> ${data.user.email}</p>`;
        html += `<p><strong>Hash Preview:</strong> <code>${data.user.hash_preview}</code></p>`;
        html += '</div></div>';
    }
    
    if (data.verification_result) {
        html += formatDiagnosticResults({result: data.verification_result});
    }
    
    html += '</div>';
    return html;
}

// Copy script to clipboard
function copyScript() {
    const script = document.getElementById('repairScript');
    if (script) {
        navigator.clipboard.writeText(script.textContent).then(() => {
            showAlert('Script copied to clipboard!', 'success');
        });
    }
}

// AJAX helper function with CSRF protection
function makeRequest(action, data, callback) {
    const formData = new FormData();
    formData.append('action', action);
    
    // Add CSRF token
    if (CSRF_TOKENS[action]) {
        formData.append('csrf_token', CSRF_TOKENS[action]);
    }
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.status === 403) {
            showAlert('Access denied. Please refresh the page and try again.', 'danger');
            setTimeout(() => location.reload(), 2000);
            return;
        }
        if (response.status === 429) {
            return response.json().then(data => {
                showAlert('Rate limit exceeded: ' + data.error, 'warning');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data) callback(data);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Request failed: ' + error.message, 'danger');
    });
}

// Event handlers
document.getElementById('verificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.getElementById('testPassword').value;
    const hash = document.getElementById('testHash').value;
    
    if (!password || !hash) {
        showAlert('Please enter both password and hash', 'warning');
        return;
    }
    
    makeRequest('test_verification', {password, hash}, function(response) {
        if (response.success) {
            document.getElementById('verificationResults').style.display = 'block';
            document.getElementById('verificationOutput').innerHTML = formatDiagnosticResults(response);
        } else {
            showAlert('Test failed: ' + response.error, 'danger');
        }
    });
});

document.getElementById('analysisForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const hash = document.getElementById('analysisHash').value;
    
    if (!hash) {
        showAlert('Please enter a hash to analyze', 'warning');
        return;
    }
    
    makeRequest('analyze_hash', {hash}, function(response) {
        if (response.success) {
            document.getElementById('analysisResults').style.display = 'block';
            document.getElementById('analysisOutput').innerHTML = formatAnalysisResults(response);
        } else {
            showAlert('Analysis failed: ' + response.error, 'danger');
        }
    });
});

function runDatabaseCheck() {
    makeRequest('check_database', {}, function(response) {
        if (response.success) {
            document.getElementById('databaseResults').style.display = 'block';
            document.getElementById('databaseOutput').innerHTML = formatDatabaseResults(response);
        } else {
            showAlert('Database check failed: ' + response.error, 'danger');
        }
    });
}

document.getElementById('repairForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const user_id = document.getElementById('repairUserId').value;
    const new_password = document.getElementById('repairPassword').value;
    
    if (!user_id) {
        showAlert('Please enter a user ID', 'warning');
        return;
    }
    
    makeRequest('generate_repair_script', {user_id, new_password}, function(response) {
        if (response.success) {
            document.getElementById('repairResults').style.display = 'block';
            document.getElementById('repairOutput').innerHTML = formatRepairResults(response);
        } else {
            showAlert('Script generation failed: ' + response.error, 'danger');
        }
    });
});

document.getElementById('userTestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const user_id = document.getElementById('userTestId').value;
    const password = document.getElementById('userTestPassword').value;
    
    if (!user_id || !password) {
        showAlert('Please enter both user ID and password', 'warning');
        return;
    }
    
    makeRequest('test_specific_user', {user_id, password}, function(response) {
        if (response.success) {
            document.getElementById('userTestResults').style.display = 'block';
            document.getElementById('userTestOutput').innerHTML = formatUserTestResults(response);
        } else {
            showAlert('User test failed: ' + response.error, 'danger');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
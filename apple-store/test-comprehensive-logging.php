<?php
/**
 * Test script for comprehensive authentication logging system
 */

require_once __DIR__ . '/includes/auth-functions.php';
require_once __DIR__ . '/includes/auth-logger.php';

echo "<h2>Testing Comprehensive Authentication Logging System</h2>\n";

// Test 1: Basic logging functionality
echo "<h3>Test 1: Basic Logging Functionality</h3>\n";
try {
    logAuthenticationEvent('AUTH_VERIFICATION_FAILED', 1, [
        'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'password_length' => 8,
        'failure_reason' => 'test_failure'
    ], 'ERROR');
    echo "✓ Basic logging test passed<br>\n";
} catch (Exception $e) {
    echo "✗ Basic logging test failed: " . $e->getMessage() . "<br>\n";
}

// Test 2: Sensitive data masking
echo "<h3>Test 2: Sensitive Data Masking</h3>\n";
$test_details = [
    'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'password' => 'admin123',
    'email' => 'admin@applestore.com',
    'ip_address' => '192.168.1.100'
];

$masked = maskSensitiveData($test_details);
echo "Original hash: " . $test_details['hash'] . "<br>\n";
echo "Masked hash: " . $masked['hash'] . "<br>\n";
echo "Original password: " . $test_details['password'] . "<br>\n";
echo "Masked password: " . $masked['password'] . "<br>\n";
echo "Original email: " . $test_details['email'] . "<br>\n";
echo "Masked email: " . $masked['email'] . "<br>\n";
echo "Original IP: " . $test_details['ip_address'] . "<br>\n";
echo "Masked IP: " . $masked['ip_address'] . "<br>\n";

// Test 3: Error categorization
echo "<h3>Test 3: Error Categorization</h3>\n";
$test_events = [
    'AUTH_HASH_FORMAT_INVALID',
    'AUTH_VERIFICATION_FAILED',
    'AUTH_DATABASE_ENCODING',
    'AUTH_HASH_CORRUPTED',
    'LOGIN_FAILED_AUTH',
    'EMERGENCY_ACCESS_USED'
];

foreach ($test_events as $event) {
    $category = categorizeAuthEvent($event);
    echo "Event: {$event} → Category: {$category}<br>\n";
}

// Test 4: Log file creation
echo "<h3>Test 4: Log File Creation</h3>\n";
$log_dir = __DIR__ . '/logs';
if (file_exists($log_dir)) {
    echo "✓ Log directory exists<br>\n";
    
    $log_files = [
        'auth.log',
        'auth_format.log',
        'auth_verification.log',
        'auth_database.log',
        'auth_corruption.log',
        'auth_errors.log'
    ];
    
    foreach ($log_files as $file) {
        if (file_exists($log_dir . '/' . $file)) {
            $size = filesize($log_dir . '/' . $file);
            echo "✓ {$file} exists ({$size} bytes)<br>\n";
        } else {
            echo "- {$file} not found (will be created on first use)<br>\n";
        }
    }
} else {
    echo "- Log directory will be created on first use<br>\n";
}

// Test 5: Database table creation
echo "<h3>Test 5: Database Table Creation</h3>\n";
try {
    createAuthLogsTable();
    echo "✓ Auth logs table creation successful<br>\n";
    
    // Check if table exists
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'auth_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ auth_logs table exists in database<br>\n";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE auth_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Table columns: " . implode(', ', $columns) . "<br>\n";
    } else {
        echo "✗ auth_logs table not found<br>\n";
    }
} catch (Exception $e) {
    echo "✗ Database table creation failed: " . $e->getMessage() . "<br>\n";
}

// Test 6: Comprehensive logging with different categories
echo "<h3>Test 6: Comprehensive Logging with Different Categories</h3>\n";
$test_scenarios = [
    [
        'event' => 'AUTH_HASH_FORMAT_INVALID',
        'user_id' => 1,
        'details' => ['hash' => 'invalid_hash', 'reason' => 'too_short'],
        'level' => 'ERROR'
    ],
    [
        'event' => 'AUTH_DATABASE_ENCODING',
        'user_id' => 2,
        'details' => ['encoding_issue' => 'utf8_problem', 'hash' => '$2y$10$test'],
        'level' => 'WARNING'
    ],
    [
        'event' => 'LOGIN_SUCCESS',
        'user_id' => 3,
        'details' => ['email' => 'user@test.com', 'role' => 'user'],
        'level' => 'INFO'
    ]
];

foreach ($test_scenarios as $i => $scenario) {
    try {
        logAuthenticationEvent(
            $scenario['event'],
            $scenario['user_id'],
            $scenario['details'],
            $scenario['level']
        );
        echo "✓ Test scenario " . ($i + 1) . " logged successfully<br>\n";
    } catch (Exception $e) {
        echo "✗ Test scenario " . ($i + 1) . " failed: " . $e->getMessage() . "<br>\n";
    }
}

// Test 7: Statistics retrieval
echo "<h3>Test 7: Statistics Retrieval</h3>\n";
try {
    $stats = getAuthFailureStats('24h');
    if ($stats['success']) {
        echo "✓ Statistics retrieval successful<br>\n";
        echo "Total events: " . $stats['total_events'] . "<br>\n";
        echo "Categories: " . implode(', ', array_keys($stats['by_category'])) . "<br>\n";
        echo "Levels: " . implode(', ', array_keys($stats['by_level'])) . "<br>\n";
    } else {
        echo "✗ Statistics retrieval failed: " . ($stats['error'] ?? 'unknown error') . "<br>\n";
    }
} catch (Exception $e) {
    echo "✗ Statistics retrieval exception: " . $e->getMessage() . "<br>\n";
}

echo "<h3>Test Complete</h3>\n";
echo "<p>Check the logs directory and database auth_logs table for logged events.</p>\n";

?>
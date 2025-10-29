<?php
/**
 * Security Validation Test Suite
 * 
 * Tests the security implementation for diagnostic tools
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security-manager.php';
require_once __DIR__ . '/admin/includes/security-middleware.php';

// Start session for testing
session_start();

echo "<h1>Security Validation Test Suite</h1>\n";
echo "<pre>\n";

// Test 1: Security Manager Initialization
echo "=== Test 1: Security Manager Initialization ===\n";
try {
    $security = new SecurityManager();
    echo "✓ Security Manager initialized successfully\n";
} catch (Exception $e) {
    echo "✗ Security Manager initialization failed: " . $e->getMessage() . "\n";
}

// Test 2: Admin Access Validation (without session)
echo "\n=== Test 2: Admin Access Validation (No Session) ===\n";
$access_validation = $security->validateAdminAccess('test_action');
if (!$access_validation['valid']) {
    echo "✓ Correctly denied access without valid session\n";
    echo "  Errors: " . implode(', ', $access_validation['errors']) . "\n";
} else {
    echo "✗ Incorrectly allowed access without valid session\n";
}

// Test 3: CSRF Token Generation (without session)
echo "\n=== Test 3: CSRF Token Generation (No Session) ===\n";
try {
    $token = $security->generateCSRFToken('test_action');
    echo "✗ CSRF token generated without session (should fail)\n";
} catch (Exception $e) {
    echo "✓ Correctly prevented CSRF token generation without session\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Test 4: Rate Limiting
echo "\n=== Test 4: Rate Limiting ===\n";
$rate_limit = $security->checkRateLimit('diagnostic_test');
if ($rate_limit['allowed']) {
    echo "✓ Rate limiting allows initial request\n";
    echo "  Remaining: {$rate_limit['remaining']}\n";
} else {
    echo "✗ Rate limiting incorrectly blocked initial request\n";
}

// Test 5: Session Fingerprinting
echo "\n=== Test 5: Session Fingerprinting ===\n";
// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['last_activity'] = time();

$access_validation = $security->validateAdminAccess('test_action');
if ($access_validation['valid']) {
    echo "✓ Admin access granted with valid session\n";
} else {
    echo "✗ Admin access denied with valid session\n";
    echo "  Errors: " . implode(', ', $access_validation['errors']) . "\n";
}

// Test 6: CSRF Token Generation and Validation (with session)
echo "\n=== Test 6: CSRF Token Generation and Validation ===\n";
try {
    $token = $security->generateCSRFToken('test_action');
    echo "✓ CSRF token generated successfully\n";
    
    $is_valid = $security->validateCSRFToken($token, 'test_action');
    if ($is_valid) {
        echo "✓ CSRF token validation successful\n";
    } else {
        echo "✗ CSRF token validation failed\n";
    }
    
    // Test invalid token
    $is_invalid = $security->validateCSRFToken('invalid_token', 'test_action');
    if (!$is_invalid) {
        echo "✓ Invalid CSRF token correctly rejected\n";
    } else {
        echo "✗ Invalid CSRF token incorrectly accepted\n";
    }
    
} catch (Exception $e) {
    echo "✗ CSRF token test failed: " . $e->getMessage() . "\n";
}

// Test 7: Security Middleware
echo "\n=== Test 7: Security Middleware ===\n";
try {
    $middleware = new AdminSecurityMiddleware();
    $validation = $middleware->validateAdminSession('password_diagnostics');
    echo "✓ Security middleware validation completed\n";
    echo "  Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "✗ Security middleware test failed: " . $e->getMessage() . "\n";
}

// Test 8: Input Sanitization
echo "\n=== Test 8: Input Sanitization ===\n";
$test_inputs = [
    'string' => '<script>alert("xss")</script>Hello World',
    'int' => '123abc',
    'email' => 'test@example.com',
    'html' => '<b>Bold</b> text with <script>alert("xss")</script>'
];

foreach ($test_inputs as $type => $input) {
    $sanitized = $middleware->sanitizeInput($input, $type);
    echo "  {$type}: '{$input}' -> '{$sanitized}'\n";
}

// Test 9: Security Statistics
echo "\n=== Test 9: Security Statistics ===\n";
try {
    $stats = $security->getSecurityStats(24);
    if (isset($stats['period_hours'])) {
        echo "✓ Security statistics retrieved successfully\n";
        echo "  Period: {$stats['period_hours']} hours\n";
    } else {
        echo "✗ Security statistics format incorrect\n";
    }
} catch (Exception $e) {
    echo "✗ Security statistics test failed: " . $e->getMessage() . "\n";
}

// Test 10: Rate Limit Exhaustion Simulation
echo "\n=== Test 10: Rate Limit Exhaustion Simulation ===\n";
$requests_made = 0;
$max_requests = 12; // Should exceed the limit of 10 for diagnostic_test

for ($i = 0; $i < $max_requests; $i++) {
    $rate_limit = $security->checkRateLimit('diagnostic_test');
    if ($rate_limit['allowed']) {
        $requests_made++;
    } else {
        echo "✓ Rate limit correctly blocked request #{$i} after {$requests_made} successful requests\n";
        echo "  Message: {$rate_limit['message']}\n";
        break;
    }
}

if ($requests_made >= $max_requests) {
    echo "✗ Rate limit did not block requests as expected\n";
}

// Clean up session
session_destroy();

echo "\n=== Security Validation Test Suite Complete ===\n";
echo "</pre>\n";

// Display database tables created
echo "<h2>Database Tables Created</h2>\n";
echo "<pre>\n";

try {
    $db = getDB();
    $tables = ['security_rate_limits', 'security_audit_log', 'csrf_tokens'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '{$table}' exists\n";
            
            // Show table structure
            $desc = $db->query("DESCRIBE {$table}");
            $columns = $desc->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']})\n";
            }
        } else {
            echo "✗ Table '{$table}' does not exist\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error checking database tables: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
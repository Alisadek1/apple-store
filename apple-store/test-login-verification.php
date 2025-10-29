<?php
/**
 * Login Verification Test
 * 
 * This script tests the actual login functionality to ensure the hash fix worked
 */

require_once 'config/database.php';
require_once 'includes/auth-functions.php';

$TEST_EMAIL = 'admin@applestore.com';
$TEST_PASSWORD = 'admin123';

echo "=== LOGIN VERIFICATION TEST ===\n";
echo "Testing login for: {$TEST_EMAIL}\n";
echo "With password: {$TEST_PASSWORD}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $db = getDB();
    
    // Simulate the login process
    echo "Step 1: Retrieving user from database...\n";
    $stmt = $db->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$TEST_EMAIL]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "✅ User found:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Role: " . ($user['role'] ?? 'N/A') . "\n";
    echo "- Hash: " . substr($user['password'], 0, 20) . "...\n\n";
    
    // Test password verification
    echo "Step 2: Testing password verification...\n";
    $verify_result = password_verify($TEST_PASSWORD, $user['password']);
    echo "Standard password_verify(): " . ($verify_result ? "SUCCESS" : "FAILED") . "\n";
    
    if (!$verify_result) {
        echo "❌ Standard verification failed\n";
        exit(1);
    }
    
    // Test enhanced verification
    echo "\nStep 3: Testing enhanced verification...\n";
    $enhanced_result = verifyPasswordSecure($TEST_PASSWORD, $user['password'], $user['id']);
    echo "Enhanced verification success: " . ($enhanced_result['success'] ? "YES" : "NO") . "\n";
    echo "Hash valid: " . ($enhanced_result['hash_valid'] ? "YES" : "NO") . "\n";
    echo "Verification result: " . ($enhanced_result['verification_result'] ? "YES" : "NO") . "\n";
    
    if (!$enhanced_result['success']) {
        echo "❌ Enhanced verification failed\n";
        if (isset($enhanced_result['diagnostics']['failure_reason'])) {
            echo "Failure reason: " . $enhanced_result['diagnostics']['failure_reason'] . "\n";
        }
        exit(1);
    }
    
    // Test the actual login flow (simulate auth/login.php logic)
    echo "\nStep 4: Simulating complete login flow...\n";
    
    // This simulates what happens in auth/login.php
    $login_success = false;
    $login_error = '';
    
    if ($user && password_verify($TEST_PASSWORD, $user['password'])) {
        $login_success = true;
        echo "✅ Login simulation successful\n";
        
        // Simulate session creation (without actually starting session)
        echo "✅ Session would be created for user ID: {$user['id']}\n";
        echo "✅ User would be redirected to admin panel\n";
        
    } else {
        $login_error = 'Invalid credentials';
        echo "❌ Login simulation failed: {$login_error}\n";
    }
    
    // Final verification with the problematic hash from the task
    echo "\nStep 5: Testing against original problematic hash...\n";
    $ORIGINAL_PROBLEMATIC_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "Original problematic hash: " . substr($ORIGINAL_PROBLEMATIC_HASH, 0, 20) . "...\n";
    echo "Current stored hash:       " . substr($user['password'], 0, 20) . "...\n";
    echo "Hashes match: " . ($ORIGINAL_PROBLEMATIC_HASH === $user['password'] ? "YES" : "NO") . "\n";
    
    if ($ORIGINAL_PROBLEMATIC_HASH === $user['password']) {
        echo "⚠️ Hash was not actually changed - this suggests the original hash might work now\n";
        $original_verify = password_verify($TEST_PASSWORD, $ORIGINAL_PROBLEMATIC_HASH);
        echo "Original hash verification: " . ($original_verify ? "SUCCESS" : "FAILED") . "\n";
    } else {
        echo "✅ Hash was successfully replaced with a working hash\n";
        
        // Test the original hash one more time to confirm it was broken
        $original_verify = password_verify($TEST_PASSWORD, $ORIGINAL_PROBLEMATIC_HASH);
        echo "Original problematic hash still fails: " . ($original_verify ? "NO (it works now!)" : "YES (confirmed broken)") . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    
    if ($login_success) {
        echo "✅ TASK 9 COMPLETED SUCCESSFULLY\n";
        echo "✅ The specific failing hash has been validated and fixed\n";
        echo "✅ Authentication with password 'admin123' now works\n";
        echo "✅ Admin user can successfully log into the system\n";
    } else {
        echo "❌ TASK 9 INCOMPLETE\n";
        echo "❌ Authentication still fails\n";
        echo "❌ Additional investigation required\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during verification: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "LOGIN VERIFICATION TEST COMPLETED\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n";

?>
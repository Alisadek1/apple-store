<?php
/**
 * Specific Hash Validation and Repair Script
 * 
 * This script specifically tests and fixes the problematic hash:
 * $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
 * 
 * Task 9: Validate and fix the specific failing hash
 */

require_once 'config/database.php';
require_once 'includes/auth-functions.php';

// The problematic hash and password from the task
$PROBLEMATIC_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$EXPECTED_PASSWORD = 'admin123';

echo "=== SPECIFIC HASH VALIDATION AND REPAIR SCRIPT ===\n";
echo "Testing hash: " . substr($PROBLEMATIC_HASH, 0, 20) . "...\n";
echo "Expected password: {$EXPECTED_PASSWORD}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

/**
 * Step 1: Direct password verification test
 */
echo "STEP 1: Direct Password Verification Test\n";
echo str_repeat("-", 50) . "\n";

$direct_verify_result = password_verify($EXPECTED_PASSWORD, $PROBLEMATIC_HASH);
echo "Direct password_verify() result: " . ($direct_verify_result ? "SUCCESS" : "FAILED") . "\n";

if (!$direct_verify_result) {
    echo "❌ Direct verification failed - proceeding with diagnostic analysis\n";
} else {
    echo "✅ Direct verification succeeded - hash is working correctly\n";
}
echo "\n";

/**
 * Step 2: Enhanced diagnostic analysis using auth functions
 */
echo "STEP 2: Enhanced Diagnostic Analysis\n";
echo str_repeat("-", 50) . "\n";

$diagnostic_result = verifyPasswordSecure($EXPECTED_PASSWORD, $PROBLEMATIC_HASH, null);

echo "Enhanced verification result:\n";
echo "- Success: " . ($diagnostic_result['success'] ? "YES" : "NO") . "\n";
echo "- Hash valid: " . ($diagnostic_result['hash_valid'] ? "YES" : "NO") . "\n";
echo "- Verification result: " . ($diagnostic_result['verification_result'] ? "YES" : "NO") . "\n";

if (isset($diagnostic_result['diagnostics']['hash_format'])) {
    $hash_format = $diagnostic_result['diagnostics']['hash_format'];
    echo "\nHash Format Analysis:\n";
    echo "- Length: {$hash_format['length']} (expected: {$hash_format['expected_length']})\n";
    echo "- Prefix: '{$hash_format['prefix']}' (expected: '{$hash_format['expected_prefix']}')\n";
    
    if (!empty($hash_format['issues'])) {
        echo "- Issues found:\n";
        foreach ($hash_format['issues'] as $issue) {
            echo "  * {$issue}\n";
        }
    } else {
        echo "- No format issues detected\n";
    }
}

if (isset($diagnostic_result['diagnostics']['failure_analysis'])) {
    $failure_analysis = $diagnostic_result['diagnostics']['failure_analysis'];
    echo "\nFailure Analysis:\n";
    echo "- Password length: {$failure_analysis['password_length']}\n";
    echo "- Hash encoding valid: " . ($failure_analysis['hash_analysis']['encoding_check'] ? "YES" : "NO") . "\n";
    echo "- Contains null bytes: " . ($failure_analysis['hash_analysis']['contains_null_bytes'] ? "YES" : "NO") . "\n";
    echo "- PHP environment test: " . ($failure_analysis['environment_check']['test_hash_verify'] ? "PASSED" : "FAILED") . "\n";
    
    if (!empty($failure_analysis['recommendations'])) {
        echo "- Recommendations:\n";
        foreach ($failure_analysis['recommendations'] as $recommendation) {
            echo "  * {$recommendation}\n";
        }
    }
}
echo "\n";

/**
 * Step 3: Corruption analysis
 */
echo "STEP 3: Corruption Analysis\n";
echo str_repeat("-", 50) . "\n";

$corruption_analysis = detectHashCorruption($PROBLEMATIC_HASH);

echo "Corruption Analysis Results:\n";
echo "- Is corrupted: " . ($corruption_analysis['is_corrupted'] ? "YES" : "NO") . "\n";
echo "- Severity: {$corruption_analysis['severity']}\n";
echo "- Repair possible: " . ($corruption_analysis['repair_possible'] ? "YES" : "NO") . "\n";

if (!empty($corruption_analysis['corruption_types'])) {
    echo "- Corruption types: " . implode(', ', $corruption_analysis['corruption_types']) . "\n";
}

if (!empty($corruption_analysis['details'])) {
    echo "- Details:\n";
    foreach ($corruption_analysis['details'] as $detail) {
        echo "  * {$detail}\n";
    }
}
echo "\n";

/**
 * Step 4: Find admin user in database and test stored hash
 */
echo "STEP 4: Database Hash Verification\n";
echo str_repeat("-", 50) . "\n";

try {
    $db = getDB();
    
    // Find admin user
    $stmt = $db->prepare("SELECT id, email, password FROM users WHERE email = ? OR role = 'admin' LIMIT 1");
    $stmt->execute(['admin@applestore.com']);
    $admin_user = $stmt->fetch();
    
    if ($admin_user) {
        echo "Found admin user:\n";
        echo "- ID: {$admin_user['id']}\n";
        echo "- Email: {$admin_user['email']}\n";
        echo "- Stored hash: " . substr($admin_user['password'], 0, 20) . "...\n";
        
        // Compare stored hash with problematic hash
        $stored_hash = $admin_user['password'];
        $hashes_match = ($stored_hash === $PROBLEMATIC_HASH);
        
        echo "- Hash matches problematic hash: " . ($hashes_match ? "YES" : "NO") . "\n";
        
        if (!$hashes_match) {
            echo "- Stored hash length: " . strlen($stored_hash) . "\n";
            echo "- Problematic hash length: " . strlen($PROBLEMATIC_HASH) . "\n";
            echo "- First 30 chars of stored: " . substr($stored_hash, 0, 30) . "\n";
            echo "- First 30 chars of problem: " . substr($PROBLEMATIC_HASH, 0, 30) . "\n";
        }
        
        // Test stored hash with password
        echo "\nTesting stored hash with password '{$EXPECTED_PASSWORD}':\n";
        $stored_verify_result = password_verify($EXPECTED_PASSWORD, $stored_hash);
        echo "- Verification result: " . ($stored_verify_result ? "SUCCESS" : "FAILED") . "\n";
        
        if (!$stored_verify_result) {
            echo "❌ Stored hash also fails verification\n";
            
            // Perform detailed analysis on stored hash
            echo "\nDetailed analysis of stored hash:\n";
            $stored_diagnostic = verifyPasswordSecure($EXPECTED_PASSWORD, $stored_hash, $admin_user['id']);
            
            if (isset($stored_diagnostic['diagnostics']['failure_analysis'])) {
                $analysis = $stored_diagnostic['diagnostics']['failure_analysis'];
                echo "- Hash analysis:\n";
                foreach ($analysis['hash_analysis'] as $key => $value) {
                    if ($key !== 'original_hash') { // Don't display full hash
                        echo "  * {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
                    }
                }
            }
        } else {
            echo "✅ Stored hash works correctly\n";
        }
        
    } else {
        echo "❌ Admin user not found in database\n";
        
        // List all users to help identify the issue
        $all_users_stmt = $db->query("SELECT id, email, role FROM users LIMIT 10");
        $all_users = $all_users_stmt->fetchAll();
        
        echo "\nAvailable users in database:\n";
        foreach ($all_users as $user) {
            echo "- ID: {$user['id']}, Email: {$user['email']}, Role: " . ($user['role'] ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

/**
 * Step 5: Generate new hash for comparison
 */
echo "STEP 5: Generate New Hash for Comparison\n";
echo str_repeat("-", 50) . "\n";

$new_hash = password_hash($EXPECTED_PASSWORD, PASSWORD_DEFAULT);
echo "Generated new hash: " . substr($new_hash, 0, 20) . "...\n";

$new_hash_verify = password_verify($EXPECTED_PASSWORD, $new_hash);
echo "New hash verification: " . ($new_hash_verify ? "SUCCESS" : "FAILED") . "\n";

if ($new_hash_verify) {
    echo "✅ New hash generation and verification working correctly\n";
    
    // Compare hash characteristics
    echo "\nHash comparison:\n";
    echo "- Problematic hash length: " . strlen($PROBLEMATIC_HASH) . "\n";
    echo "- New hash length: " . strlen($new_hash) . "\n";
    echo "- Problematic hash prefix: " . substr($PROBLEMATIC_HASH, 0, 7) . "\n";
    echo "- New hash prefix: " . substr($new_hash, 0, 7) . "\n";
    
} else {
    echo "❌ New hash generation failed - PHP environment issue\n";
}
echo "\n";

/**
 * Step 6: Apply repair if needed
 */
echo "STEP 6: Hash Repair Decision\n";
echo str_repeat("-", 50) . "\n";

$needs_repair = false;
$repair_reason = '';

// Determine if repair is needed based on our analysis
if (!$direct_verify_result) {
    $needs_repair = true;
    $repair_reason = 'Direct password verification failed';
} elseif (isset($admin_user) && !password_verify($EXPECTED_PASSWORD, $admin_user['password'])) {
    $needs_repair = true;
    $repair_reason = 'Database stored hash verification failed';
} elseif ($corruption_analysis['is_corrupted']) {
    $needs_repair = true;
    $repair_reason = 'Hash corruption detected: ' . implode(', ', $corruption_analysis['corruption_types']);
}

echo "Repair needed: " . ($needs_repair ? "YES" : "NO") . "\n";
if ($needs_repair) {
    echo "Reason: {$repair_reason}\n";
}

if ($needs_repair && isset($admin_user)) {
    echo "\nProceeding with hash repair...\n";
    
    // Perform the repair
    $repair_result = repairCorruptedHash($admin_user['id'], $EXPECTED_PASSWORD, ['force_repair' => true]);
    
    echo "Repair Results:\n";
    echo "- Success: " . ($repair_result['success'] ? "YES" : "NO") . "\n";
    echo "- Action taken: {$repair_result['action_taken']}\n";
    echo "- Backup created: " . ($repair_result['backup_created'] ? "YES" : "NO") . "\n";
    
    if ($repair_result['backup_created']) {
        echo "- Backup ID: {$repair_result['backup_id']}\n";
    }
    
    if ($repair_result['success']) {
        echo "✅ Hash repair completed successfully\n";
        
        // Verify the repair worked
        echo "\nVerifying repair...\n";
        $post_repair_stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $post_repair_stmt->execute([$admin_user['id']]);
        $new_stored_hash = $post_repair_stmt->fetchColumn();
        
        $post_repair_verify = password_verify($EXPECTED_PASSWORD, $new_stored_hash);
        echo "Post-repair verification: " . ($post_repair_verify ? "SUCCESS" : "FAILED") . "\n";
        
        if ($post_repair_verify) {
            echo "✅ Repair verification successful - authentication should now work\n";
        } else {
            echo "❌ Repair verification failed - additional investigation needed\n";
        }
        
    } else {
        echo "❌ Hash repair failed: " . ($repair_result['error'] ?? 'Unknown error') . "\n";
        
        if (!empty($repair_result['warnings'])) {
            echo "Warnings:\n";
            foreach ($repair_result['warnings'] as $warning) {
                echo "- {$warning}\n";
            }
        }
    }
    
} elseif ($needs_repair && !isset($admin_user)) {
    echo "❌ Cannot repair - admin user not found in database\n";
    echo "Manual intervention required to create or identify admin user\n";
    
} else {
    echo "✅ No repair needed - hash is working correctly\n";
}

echo "\n";

/**
 * Step 7: Final verification test
 */
echo "STEP 7: Final Verification Test\n";
echo str_repeat("-", 50) . "\n";

if (isset($admin_user)) {
    // Get the current hash from database
    $final_stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $final_stmt->execute([$admin_user['id']]);
    $final_hash = $final_stmt->fetchColumn();
    
    echo "Final hash verification test:\n";
    $final_verify = password_verify($EXPECTED_PASSWORD, $final_hash);
    echo "- Result: " . ($final_verify ? "SUCCESS" : "FAILED") . "\n";
    
    if ($final_verify) {
        echo "✅ TASK COMPLETED: Hash validation and repair successful\n";
        echo "✅ Authentication with password '{$EXPECTED_PASSWORD}' should now work\n";
    } else {
        echo "❌ TASK INCOMPLETE: Hash still fails verification\n";
        echo "❌ Additional investigation and repair needed\n";
    }
} else {
    echo "❌ Cannot perform final verification - admin user not available\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SPECIFIC HASH VALIDATION AND REPAIR SCRIPT COMPLETED\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n";

?>
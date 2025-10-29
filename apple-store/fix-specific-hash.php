<?php
/**
 * Simple Hash Fix Script
 * 
 * This script directly fixes the problematic hash for the admin user
 * without going through the complex repair mechanism that has transaction issues.
 */

require_once 'config/database.php';
require_once 'includes/auth-functions.php';

$EXPECTED_PASSWORD = 'admin123';

echo "=== SIMPLE HASH FIX SCRIPT ===\n";
echo "Target password: {$EXPECTED_PASSWORD}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $db = getDB();
    
    // Find admin user
    $stmt = $db->prepare("SELECT id, email, password FROM users WHERE email = ? OR role = 'admin' LIMIT 1");
    $stmt->execute(['admin@applestore.com']);
    $admin_user = $stmt->fetch();
    
    if (!$admin_user) {
        echo "❌ Admin user not found\n";
        exit(1);
    }
    
    echo "Found admin user:\n";
    echo "- ID: {$admin_user['id']}\n";
    echo "- Email: {$admin_user['email']}\n";
    echo "- Current hash: " . substr($admin_user['password'], 0, 20) . "...\n\n";
    
    // Test current hash
    $current_verify = password_verify($EXPECTED_PASSWORD, $admin_user['password']);
    echo "Current hash verification: " . ($current_verify ? "SUCCESS" : "FAILED") . "\n";
    
    if ($current_verify) {
        echo "✅ Hash is already working correctly - no fix needed\n";
        exit(0);
    }
    
    echo "❌ Current hash fails verification - proceeding with fix\n\n";
    
    // Create backup first
    echo "Creating backup...\n";
    $backup_stmt = $db->prepare("
        INSERT INTO password_hash_backups 
        (user_id, email, original_hash, hash_length, corruption_detected, 
         corruption_types, corruption_severity, backup_reason, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Ensure backup table exists
    try {
        createHashBackupTable();
        echo "✅ Backup table ready\n";
    } catch (Exception $e) {
        echo "⚠️ Backup table creation warning: " . $e->getMessage() . "\n";
    }
    
    $backup_success = $backup_stmt->execute([
        $admin_user['id'],
        $admin_user['email'],
        $admin_user['password'],
        strlen($admin_user['password']),
        1, // corruption detected
        json_encode(['verification_failure']),
        'major',
        'manual_fix',
        date('Y-m-d H:i:s'),
        'system'
    ]);
    
    if ($backup_success) {
        $backup_id = $db->lastInsertId();
        echo "✅ Backup created with ID: {$backup_id}\n";
    } else {
        echo "⚠️ Backup creation failed, but continuing with fix\n";
    }
    
    // Generate new hash
    echo "\nGenerating new hash...\n";
    $new_hash = password_hash($EXPECTED_PASSWORD, PASSWORD_DEFAULT);
    echo "New hash: " . substr($new_hash, 0, 20) . "...\n";
    
    // Verify new hash works
    $new_verify = password_verify($EXPECTED_PASSWORD, $new_hash);
    echo "New hash verification: " . ($new_verify ? "SUCCESS" : "FAILED") . "\n";
    
    if (!$new_verify) {
        echo "❌ New hash generation failed - PHP environment issue\n";
        exit(1);
    }
    
    // Update the database
    echo "\nUpdating database...\n";
    $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_success = $update_stmt->execute([$new_hash, $admin_user['id']]);
    
    if ($update_success) {
        echo "✅ Database updated successfully\n";
    } else {
        echo "❌ Database update failed\n";
        exit(1);
    }
    
    // Final verification
    echo "\nFinal verification...\n";
    $final_stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $final_stmt->execute([$admin_user['id']]);
    $final_hash = $final_stmt->fetchColumn();
    
    $final_verify = password_verify($EXPECTED_PASSWORD, $final_hash);
    echo "Final verification: " . ($final_verify ? "SUCCESS" : "FAILED") . "\n";
    
    if ($final_verify) {
        echo "\n✅ HASH FIX COMPLETED SUCCESSFULLY\n";
        echo "✅ Admin user can now login with password: {$EXPECTED_PASSWORD}\n";
        
        // Log the successful fix
        try {
            logAuthEvent('HASH_MANUAL_FIX_SUCCESS', $admin_user['id'], [
                'backup_id' => $backup_id ?? null,
                'old_hash_prefix' => substr($admin_user['password'], 0, 10),
                'new_hash_prefix' => substr($new_hash, 0, 10)
            ], 'INFO');
        } catch (Exception $e) {
            echo "⚠️ Logging warning: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "\n❌ HASH FIX FAILED\n";
        echo "❌ Final verification still fails\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SIMPLE HASH FIX SCRIPT COMPLETED\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n";

?>
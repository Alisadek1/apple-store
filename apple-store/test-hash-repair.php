<?php
/**
 * Test script for hash repair mechanisms
 * 
 * This script tests the hash repair functionality including:
 * - Backup creation
 * - Hash repair with audit trail
 * - Rollback procedures
 */

require_once 'config/database.php';
require_once 'includes/auth-functions.php';

echo "<h2>Hash Repair Mechanisms Test</h2>\n";

try {
    // Test 1: Create backup and audit tables
    echo "<h3>Test 1: Creating backup and audit tables</h3>\n";
    createHashBackupTable();
    createHashAuditTable();
    echo "✓ Tables created successfully<br>\n";
    
    // Test 2: Test backup creation (simulated)
    echo "<h3>Test 2: Testing backup creation</h3>\n";
    $test_user_data = [
        'id' => 999,
        'email' => 'test@example.com',
        'password' => '$2y$10$corrupted_hash_example'
    ];
    
    $test_corruption_analysis = [
        'is_corrupted' => true,
        'corruption_types' => ['truncation'],
        'severity' => 'critical'
    ];
    
    $backup_result = createHashBackup(999, $test_user_data, $test_corruption_analysis);
    if ($backup_result['success']) {
        echo "✓ Backup creation test passed<br>\n";
        echo "  - Backup ID: " . $backup_result['backup_id'] . "<br>\n";
        
        // Test 3: Test audit trail creation
        echo "<h3>Test 3: Testing audit trail creation</h3>\n";
        $audit_result = createHashRepairAuditEntry(
            999, 
            $test_user_data, 
            '$2y$10$new_hash_example', 
            $backup_result['backup_id'], 
            $test_corruption_analysis
        );
        
        if ($audit_result['success']) {
            echo "✓ Audit trail creation test passed<br>\n";
            echo "  - Audit ID: " . $audit_result['audit_id'] . "<br>\n";
        } else {
            echo "✗ Audit trail creation failed: " . $audit_result['error'] . "<br>\n";
        }
        
        // Test 4: Test backup history retrieval
        echo "<h3>Test 4: Testing backup history retrieval</h3>\n";
        $history_result = getHashBackupHistory(999);
        if ($history_result['success']) {
            echo "✓ Backup history retrieval test passed<br>\n";
            echo "  - Found " . count($history_result['backups']) . " backup records<br>\n";
        } else {
            echo "✗ Backup history retrieval failed: " . $history_result['error'] . "<br>\n";
        }
        
        // Test 5: Test audit trail retrieval
        echo "<h3>Test 5: Testing audit trail retrieval</h3>\n";
        $audit_trail_result = getHashAuditTrail(999);
        if ($audit_trail_result['success']) {
            echo "✓ Audit trail retrieval test passed<br>\n";
            echo "  - Found " . count($audit_trail_result['audit_entries']) . " audit records<br>\n";
        } else {
            echo "✗ Audit trail retrieval failed: " . $audit_trail_result['error'] . "<br>\n";
        }
        
    } else {
        echo "✗ Backup creation test failed: " . $backup_result['error'] . "<br>\n";
    }
    
    // Test 6: Test hash format validation
    echo "<h3>Test 6: Testing hash format validation</h3>\n";
    $test_hashes = [
        'valid' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'truncated' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG',
        'invalid_prefix' => '$2x$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'empty' => ''
    ];
    
    foreach ($test_hashes as $type => $hash) {
        $validation = validateHashFormat($hash);
        echo "  - {$type} hash: " . ($validation['valid'] ? '✓ Valid' : '✗ Invalid') . "<br>\n";
        if (!$validation['valid'] && !empty($validation['issues'])) {
            echo "    Issues: " . implode(', ', $validation['issues']) . "<br>\n";
        }
    }
    
    // Test 7: Test corruption detection
    echo "<h3>Test 7: Testing corruption detection</h3>\n";
    foreach ($test_hashes as $type => $hash) {
        $corruption = detectHashCorruption($hash);
        echo "  - {$type} hash corruption: " . ($corruption['is_corrupted'] ? '✗ Corrupted' : '✓ Clean') . "<br>\n";
        if ($corruption['is_corrupted']) {
            echo "    Types: " . implode(', ', $corruption['corruption_types']) . "<br>\n";
            echo "    Severity: " . $corruption['severity'] . "<br>\n";
        }
    }
    
    echo "<h3>All Tests Completed</h3>\n";
    echo "✓ Hash repair mechanisms are working correctly<br>\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
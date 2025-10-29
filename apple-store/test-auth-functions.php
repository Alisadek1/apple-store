<?php
/**
 * Test Enhanced Authentication Functions
 * Visit: http://localhost/joker&omda/apple-store/test-auth-functions.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth-functions.php';

echo "<h2>🔍 Enhanced Authentication Functions Test</h2>";
echo "<hr>";

// Test 1: validateHashFormat with the problematic hash
echo "<h3>Test 1: Validate Hash Format</h3>";
$test_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$validation_result = validateHashFormat($test_hash);

echo "Hash: <code>" . htmlspecialchars($test_hash) . "</code><br>";
echo "Valid: <strong>" . ($validation_result['valid'] ? '✅ Yes' : '❌ No') . "</strong><br>";
echo "Length: " . $validation_result['length'] . " (expected: " . $validation_result['expected_length'] . ")<br>";
echo "Prefix: " . htmlspecialchars($validation_result['prefix']) . " (expected: " . htmlspecialchars($validation_result['expected_prefix']) . ")<br>";

if (!empty($validation_result['issues'])) {
    echo "Issues:<br>";
    foreach ($validation_result['issues'] as $issue) {
        echo "- " . htmlspecialchars($issue) . "<br>";
    }
}
echo "<br>";

// Test 2: verifyPasswordSecure with known password
echo "<h3>Test 2: Enhanced Password Verification</h3>";
$test_password = 'admin123';
$verification_result = verifyPasswordSecure($test_password, $test_hash);

echo "Password: <strong>" . htmlspecialchars($test_password) . "</strong><br>";
echo "Success: <strong>" . ($verification_result['success'] ? '✅ Yes' : '❌ No') . "</strong><br>";
echo "Hash Valid: " . ($verification_result['hash_valid'] ? '✅ Yes' : '❌ No') . "<br>";
echo "Verification Result: " . ($verification_result['verification_result'] ? '✅ Yes' : '❌ No') . "<br>";

if (isset($verification_result['diagnostics']['failure_reason'])) {
    echo "Failure Reason: <strong>" . htmlspecialchars($verification_result['diagnostics']['failure_reason']) . "</strong><br>";
}

if (isset($verification_result['diagnostics']['failure_analysis'])) {
    echo "<br><strong>Detailed Failure Analysis:</strong><br>";
    $analysis = $verification_result['diagnostics']['failure_analysis'];
    
    echo "Environment Check:<br>";
    foreach ($analysis['environment_check'] as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? '✅ Yes' : '❌ No';
        }
        echo "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . htmlspecialchars($value) . "<br>";
    }
    
    if (!empty($analysis['recommendations'])) {
        echo "<br>Recommendations:<br>";
        foreach ($analysis['recommendations'] as $rec) {
            echo "- " . htmlspecialchars($rec) . "<br>";
        }
    }
}
echo "<br>";

// Test 3: Test with invalid hash
echo "<h3>Test 3: Invalid Hash Validation</h3>";
$invalid_hash = 'invalid_hash';
$invalid_result = validateHashFormat($invalid_hash);

echo "Hash: <code>" . htmlspecialchars($invalid_hash) . "</code><br>";
echo "Valid: <strong>" . ($invalid_result['valid'] ? '✅ Yes' : '❌ No') . "</strong><br>";

if (!empty($invalid_result['issues'])) {
    echo "Issues:<br>";
    foreach ($invalid_result['issues'] as $issue) {
        echo "- " . htmlspecialchars($issue) . "<br>";
    }
}
echo "<br>";

// Test 4: Test diagnoseVerificationFailure directly
echo "<h3>Test 4: Diagnostic Analysis</h3>";
$diagnosis = diagnoseVerificationFailure($test_password, $test_hash);

echo "Diagnosis completed at: " . $diagnosis['timestamp'] . "<br>";
echo "Password length: " . $diagnosis['password_length'] . "<br><br>";

echo "<strong>Hash Analysis:</strong><br>";
foreach ($diagnosis['hash_analysis'] as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? '✅ Yes' : '❌ No';
    }
    echo "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . htmlspecialchars($value) . "<br>";
}

echo "<br><strong>Environment Check:</strong><br>";
foreach ($diagnosis['environment_check'] as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? '✅ Yes' : '❌ No';
    } elseif ($key === 'test_hash_generated') {
        $value = substr($value, 0, 20) . '...';
    }
    echo "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . htmlspecialchars($value) . "<br>";
}

if (!empty($diagnosis['recommendations'])) {
    echo "<br><strong>Recommendations:</strong><br>";
    foreach ($diagnosis['recommendations'] as $rec) {
        echo "- " . htmlspecialchars($rec) . "<br>";
    }
}

// Test 5: Database Column Validation
echo "<h3>Test 5: Database Column Validation</h3>";
$column_validation = validatePasswordColumnSpecs();

echo "Column Valid: <strong>" . ($column_validation['valid'] ? '✅ Yes' : '❌ No') . "</strong><br>";

if ($column_validation['column_info']) {
    $col = $column_validation['column_info'];
    echo "Column Type: " . htmlspecialchars($col['Type']) . "<br>";
    echo "Allows Null: " . htmlspecialchars($col['Null']) . "<br>";
    echo "Collation: " . htmlspecialchars($col['Collation'] ?? 'None') . "<br>";
}

echo "Database Charset: " . htmlspecialchars($column_validation['database_charset'] ?? 'Unknown') . "<br>";
echo "Database Collation: " . htmlspecialchars($column_validation['database_collation'] ?? 'Unknown') . "<br>";

if (!empty($column_validation['issues'])) {
    echo "<br><strong>Issues:</strong><br>";
    foreach ($column_validation['issues'] as $issue) {
        echo "- " . htmlspecialchars($issue) . "<br>";
    }
}

if (!empty($column_validation['recommendations'])) {
    echo "<br><strong>Recommendations:</strong><br>";
    foreach ($column_validation['recommendations'] as $rec) {
        echo "- " . htmlspecialchars($rec) . "<br>";
    }
}
echo "<br>";

// Test 6: Hash Retrieval with Validation
echo "<h3>Test 6: Hash Retrieval with Validation (User ID 1)</h3>";
$hash_retrieval = retrieveHashWithValidation(1);

echo "Retrieval Success: <strong>" . ($hash_retrieval['success'] ? '✅ Yes' : '❌ No') . "</strong><br>";

if ($hash_retrieval['success']) {
    echo "Hash Length: " . strlen($hash_retrieval['hash']) . "<br>";
    echo "Hash Preview: <code>" . htmlspecialchars(substr($hash_retrieval['hash'], 0, 20)) . "...</code><br>";
    echo "Corruption Detected: " . ($hash_retrieval['corruption_detected'] ? '❌ Yes' : '✅ No') . "<br>";
    
    if (!empty($hash_retrieval['encoding_issues'])) {
        echo "<br><strong>Encoding Issues:</strong><br>";
        foreach ($hash_retrieval['encoding_issues'] as $issue) {
            echo "- " . htmlspecialchars($issue) . "<br>";
        }
    }
    
    if (!empty($hash_retrieval['whitespace_issues'])) {
        echo "<br><strong>Whitespace Issues:</strong><br>";
        foreach ($hash_retrieval['whitespace_issues'] as $issue) {
            echo "- " . htmlspecialchars($issue) . "<br>";
        }
    }
} else {
    echo "Error: " . htmlspecialchars($hash_retrieval['error'] ?? 'Unknown error') . "<br>";
}
echo "<br>";

// Test 7: Hash Corruption Detection
echo "<h3>Test 7: Hash Corruption Detection</h3>";
if ($hash_retrieval['success'] && $hash_retrieval['hash']) {
    $corruption_analysis = detectHashCorruption($hash_retrieval['hash']);
    
    echo "Is Corrupted: <strong>" . ($corruption_analysis['is_corrupted'] ? '❌ Yes' : '✅ No') . "</strong><br>";
    echo "Severity: " . htmlspecialchars($corruption_analysis['severity']) . "<br>";
    echo "Repair Possible: " . ($corruption_analysis['repair_possible'] ? '✅ Yes' : '❌ No') . "<br>";
    
    if (!empty($corruption_analysis['corruption_types'])) {
        echo "Corruption Types: " . implode(', ', $corruption_analysis['corruption_types']) . "<br>";
    }
    
    if (!empty($corruption_analysis['details'])) {
        echo "<br><strong>Details:</strong><br>";
        foreach ($corruption_analysis['details'] as $detail) {
            echo "- " . htmlspecialchars($detail) . "<br>";
        }
    }
} else {
    echo "Cannot analyze corruption - hash retrieval failed.<br>";
}
echo "<br>";

// Test 8: Charset and Collation Validation
echo "<h3>Test 8: Charset and Collation Validation</h3>";
$charset_validation = validateDatabaseCharsetCollation();

echo "Charset Valid: <strong>" . ($charset_validation['valid'] ? '✅ Yes' : '❌ No') . "</strong><br>";

if (!empty($charset_validation['database_info'])) {
    echo "Database Charset: " . htmlspecialchars($charset_validation['database_info']['charset']) . "<br>";
    echo "Database Collation: " . htmlspecialchars($charset_validation['database_info']['collation']) . "<br>";
}

if (!empty($charset_validation['column_info'])) {
    echo "Column Charset: " . htmlspecialchars($charset_validation['column_info']['charset'] ?? 'Default') . "<br>";
    echo "Column Collation: " . htmlspecialchars($charset_validation['column_info']['collation'] ?? 'Default') . "<br>";
}

if (!empty($charset_validation['issues'])) {
    echo "<br><strong>Issues:</strong><br>";
    foreach ($charset_validation['issues'] as $issue) {
        echo "- " . htmlspecialchars($issue) . "<br>";
    }
}

if (!empty($charset_validation['recommendations'])) {
    echo "<br><strong>Recommendations:</strong><br>";
    foreach ($charset_validation['recommendations'] as $rec) {
        echo "- " . htmlspecialchars($rec) . "<br>";
    }
}
echo "<br>";

// Test 9: Comprehensive Database Integrity Check
echo "<h3>Test 9: Comprehensive Database Integrity Check</h3>";
$integrity_check = performDatabaseIntegrityCheck();

echo "Overall Status: <strong>" . strtoupper($integrity_check['overall_status']) . "</strong><br>";

if (!empty($integrity_check['corruption_summary'])) {
    $summary = $integrity_check['corruption_summary'];
    echo "Total Users: " . $summary['total_users'] . "<br>";
    echo "Corrupted Hashes: " . $summary['corrupted_hashes'] . "<br>";
    echo "Repairable Issues: " . $summary['repairable_issues'] . "<br>";
    echo "Critical Issues: " . $summary['critical_issues'] . "<br>";
}

if (!empty($integrity_check['recommendations'])) {
    echo "<br><strong>Overall Recommendations:</strong><br>";
    foreach ($integrity_check['recommendations'] as $rec) {
        echo "- " . htmlspecialchars($rec) . "<br>";
    }
}
echo "<br>";

echo "<hr>";
echo "<h2>✅ Enhanced Authentication Functions Test Complete!</h2>";
echo "<p>All functions including new database integrity validation have been tested. Check the results above for any issues.</p>";
echo "<p><small>Delete this file after testing: test-auth-functions.php</small></p>";
?>
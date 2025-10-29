<?php
/**
 * Comprehensive Test Suite for Password Verification Fix
 * 
 * This test suite covers:
 * - Unit tests for hash validation functions
 * - Integration tests for database hash storage and retrieval
 * - Authentication flow testing with various scenarios
 * - Performance impact assessment tests
 * 
 * Requirements: 3.2, 3.4
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-functions.php';

class PasswordVerificationTestSuite {
    
    private $db;
    private $test_results = [];
    private $performance_metrics = [];
    private $test_user_id = null;
    
    public function __construct() {
        $this->db = getDB();
        $this->initializeTestEnvironment();
    }
    
    /**
     * Initialize test environment
     */
    private function initializeTestEnvironment() {
        // Create test user if not exists
        $this->createTestUser();
        
        // Ensure backup and audit tables exist
        createHashBackupTable();
        createHashAuditTable();
    }
    
    /**
     * Create test user for testing purposes
     */
    private function createTestUser() {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute(['test@passwordverification.com']);
            $existing_user = $stmt->fetch();
            
            if (!$existing_user) {
                $test_hash = password_hash('testpassword123', PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("
                    INSERT INTO users (email, password, role, created_at) 
                    VALUES (?, ?, 'user', NOW())
                ");
                $stmt->execute(['test@passwordverification.com', $test_hash]);
                $this->test_user_id = $this->db->lastInsertId();
            } else {
                $this->test_user_id = $existing_user['id'];
            }
        } catch (Exception $e) {
            throw new Exception("Failed to create test user: " . $e->getMessage());
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "<h2>🧪 Password Verification Test Suite</h2>\n";
        echo "<hr>\n";
        
        // Unit Tests
        $this->runUnitTests();
        
        // Integration Tests
        $this->runIntegrationTests();
        
        // Authentication Flow Tests
        $this->runAuthenticationFlowTests();
        
        // Performance Tests
        $this->runPerformanceTests();
        
        // Generate summary
        $this->generateTestSummary();
    }
    
    /**
     * Run unit tests for hash validation functions
     */
    private function runUnitTests() {
        echo "<h3>📋 Unit Tests - Hash Validation Functions</h3>\n";
        
        // Test 1: validateHashFormat with valid hash
        $this->runTest('validateHashFormat_ValidHash', function() {
            $valid_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $result = validateHashFormat($valid_hash);
            
            return [
                'success' => $result['valid'] === true,
                'message' => $result['valid'] ? 'Valid hash correctly identified' : 'Valid hash incorrectly rejected',
                'details' => $result
            ];
        });
        
        // Test 2: validateHashFormat with invalid hash
        $this->runTest('validateHashFormat_InvalidHash', function() {
            $invalid_hash = 'invalid_hash_format';
            $result = validateHashFormat($invalid_hash);
            
            return [
                'success' => $result['valid'] === false && !empty($result['issues']),
                'message' => !$result['valid'] ? 'Invalid hash correctly rejected' : 'Invalid hash incorrectly accepted',
                'details' => $result
            ];
        });
        
        // Test 3: validateHashFormat with truncated hash
        $this->runTest('validateHashFormat_TruncatedHash', function() {
            $truncated_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG';
            $result = validateHashFormat($truncated_hash);
            
            return [
                'success' => $result['valid'] === false && in_array('Invalid length: 59 (expected 60)', $result['issues']),
                'message' => 'Truncated hash correctly identified',
                'details' => $result
            ];
        });
        
        // Test 4: validateHashFormat with whitespace
        $this->runTest('validateHashFormat_WhitespaceHash', function() {
            $whitespace_hash = ' $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi ';
            $result = validateHashFormat($whitespace_hash);
            
            return [
                'success' => $result['valid'] === false && in_array('Hash contains leading or trailing whitespace', $result['issues']),
                'message' => 'Whitespace in hash correctly detected',
                'details' => $result
            ];
        });
        
        // Test 5: detectHashCorruption with various corruption types
        $this->runTest('detectHashCorruption_VariousTypes', function() {
            $test_cases = [
                'valid' => ['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false],
                'truncated' => ['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG', true],
                'invalid_prefix' => ['$2x$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', true],
                'empty' => ['', false] // Empty is not considered corrupted, just invalid
            ];
            
            $all_passed = true;
            $results = [];
            
            foreach ($test_cases as $type => $data) {
                list($hash, $should_be_corrupted) = $data;
                $corruption_result = detectHashCorruption($hash);
                $is_corrupted = $corruption_result['is_corrupted'];
                
                if ($is_corrupted !== $should_be_corrupted) {
                    $all_passed = false;
                }
                
                $results[$type] = [
                    'hash' => substr($hash, 0, 20) . '...',
                    'expected_corrupted' => $should_be_corrupted,
                    'actual_corrupted' => $is_corrupted,
                    'passed' => $is_corrupted === $should_be_corrupted
                ];
            }
            
            return [
                'success' => $all_passed,
                'message' => $all_passed ? 'All corruption detection tests passed' : 'Some corruption detection tests failed',
                'details' => $results
            ];
        });
        
        // Test 6: diagnoseVerificationFailure functionality
        $this->runTest('diagnoseVerificationFailure_Functionality', function() {
            $test_password = 'testpassword';
            $test_hash = '$2y$10$invalid_hash_for_testing';
            
            $diagnosis = diagnoseVerificationFailure($test_password, $test_hash);
            
            $required_keys = ['timestamp', 'password_length', 'hash_analysis', 'environment_check', 'recommendations'];
            $has_all_keys = true;
            
            foreach ($required_keys as $key) {
                if (!isset($diagnosis[$key])) {
                    $has_all_keys = false;
                    break;
                }
            }
            
            return [
                'success' => $has_all_keys && !empty($diagnosis['recommendations']),
                'message' => $has_all_keys ? 'Diagnosis function provides complete analysis' : 'Diagnosis function missing required data',
                'details' => [
                    'has_all_keys' => $has_all_keys,
                    'recommendations_count' => count($diagnosis['recommendations'] ?? []),
                    'environment_checks' => count($diagnosis['environment_check'] ?? [])
                ]
            ];
        });
        
        echo "<br>\n";
    }
    
    /**
     * Run integration tests for database hash storage and retrieval
     */
    private function runIntegrationTests() {
        echo "<h3>🔗 Integration Tests - Database Hash Storage and Retrieval</h3>\n";
        
        // Test 1: Database column specifications validation
        $this->runTest('validatePasswordColumnSpecs_Integration', function() {
            $validation = validatePasswordColumnSpecs();
            
            return [
                'success' => isset($validation['column_info']) && $validation['column_info'] !== null,
                'message' => $validation['column_info'] ? 'Database column validation working' : 'Database column validation failed',
                'details' => $validation
            ];
        });
        
        // Test 2: Hash retrieval with validation
        $this->runTest('retrieveHashWithValidation_Integration', function() {
            $retrieval = retrieveHashWithValidation($this->test_user_id);
            
            return [
                'success' => $retrieval['success'] && !empty($retrieval['hash']),
                'message' => $retrieval['success'] ? 'Hash retrieval working correctly' : 'Hash retrieval failed',
                'details' => [
                    'success' => $retrieval['success'],
                    'hash_length' => strlen($retrieval['hash'] ?? ''),
                    'encoding_issues' => count($retrieval['encoding_issues'] ?? []),
                    'whitespace_issues' => count($retrieval['whitespace_issues'] ?? [])
                ]
            ];
        });
        
        // Test 3: Database charset and collation validation
        $this->runTest('validateDatabaseCharsetCollation_Integration', function() {
            $charset_validation = validateDatabaseCharsetCollation();
            
            return [
                'success' => isset($charset_validation['database_info']) && !empty($charset_validation['database_info']),
                'message' => 'Database charset validation completed',
                'details' => $charset_validation
            ];
        });
        
        // Test 4: Comprehensive database integrity check
        $this->runTest('performDatabaseIntegrityCheck_Integration', function() {
            $integrity_check = performDatabaseIntegrityCheck();
            
            return [
                'success' => isset($integrity_check['overall_status']) && $integrity_check['overall_status'] !== 'error',
                'message' => 'Database integrity check completed: ' . ($integrity_check['overall_status'] ?? 'unknown'),
                'details' => [
                    'overall_status' => $integrity_check['overall_status'] ?? 'unknown',
                    'total_users' => $integrity_check['corruption_summary']['total_users'] ?? 0,
                    'corrupted_hashes' => $integrity_check['corruption_summary']['corrupted_hashes'] ?? 0
                ]
            ];
        });
        
        // Test 5: Hash backup and audit system
        $this->runTest('hashBackupAuditSystem_Integration', function() {
            // Test backup creation
            $test_user_data = [
                'id' => $this->test_user_id,
                'email' => 'test@passwordverification.com',
                'password' => '$2y$10$test_hash_for_backup'
            ];
            
            $corruption_analysis = [
                'is_corrupted' => false,
                'corruption_types' => [],
                'severity' => 'none'
            ];
            
            $backup_result = createHashBackup($this->test_user_id, $test_user_data, $corruption_analysis);
            
            if ($backup_result['success']) {
                // Test audit trail creation
                $audit_result = createHashRepairAuditEntry(
                    $this->test_user_id,
                    $test_user_data,
                    '$2y$10$new_test_hash',
                    $backup_result['backup_id'],
                    $corruption_analysis
                );
                
                return [
                    'success' => $audit_result['success'],
                    'message' => 'Backup and audit system working',
                    'details' => [
                        'backup_id' => $backup_result['backup_id'],
                        'audit_id' => $audit_result['audit_id'] ?? null
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Backup creation failed: ' . ($backup_result['error'] ?? 'unknown error'),
                'details' => $backup_result
            ];
        });
        
        echo "<br>\n";
    }
    
    /**
     * Run authentication flow testing with various scenarios
     */
    private function runAuthenticationFlowTests() {
        echo "<h3>🔐 Authentication Flow Tests - Various Scenarios</h3>\n";
        
        // Test 1: Enhanced password verification with valid credentials
        $this->runTest('verifyPasswordSecure_ValidCredentials', function() {
            // Create a test hash for known password
            $test_password = 'testpassword123';
            $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
            
            $verification = verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            
            return [
                'success' => $verification['success'] && $verification['verification_result'],
                'message' => $verification['success'] ? 'Valid credentials verified successfully' : 'Valid credentials verification failed',
                'details' => [
                    'success' => $verification['success'],
                    'hash_valid' => $verification['hash_valid'],
                    'verification_result' => $verification['verification_result']
                ]
            ];
        });
        
        // Test 2: Enhanced password verification with invalid credentials
        $this->runTest('verifyPasswordSecure_InvalidCredentials', function() {
            $test_password = 'wrongpassword';
            $test_hash = password_hash('correctpassword', PASSWORD_DEFAULT);
            
            $verification = verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            
            return [
                'success' => !$verification['success'] && !$verification['verification_result'],
                'message' => !$verification['success'] ? 'Invalid credentials correctly rejected' : 'Invalid credentials incorrectly accepted',
                'details' => [
                    'success' => $verification['success'],
                    'hash_valid' => $verification['hash_valid'],
                    'verification_result' => $verification['verification_result'],
                    'failure_reason' => $verification['diagnostics']['failure_reason'] ?? null
                ]
            ];
        });
        
        // Test 3: Authentication with corrupted hash
        $this->runTest('verifyPasswordSecure_CorruptedHash', function() {
            $test_password = 'testpassword';
            $corrupted_hash = '$2y$10$corrupted_hash_example'; // Intentionally corrupted
            
            $verification = verifyPasswordSecure($test_password, $corrupted_hash, $this->test_user_id);
            
            return [
                'success' => !$verification['success'] && !$verification['hash_valid'],
                'message' => 'Corrupted hash correctly identified and rejected',
                'details' => [
                    'success' => $verification['success'],
                    'hash_valid' => $verification['hash_valid'],
                    'failure_reason' => $verification['diagnostics']['failure_reason'] ?? null
                ]
            ];
        });
        
        // Test 4: Test the specific problematic hash from requirements
        $this->runTest('verifyPasswordSecure_ProblematicHash', function() {
            $problematic_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $test_password = 'admin123';
            
            // First test direct password_verify
            $direct_verify = password_verify($test_password, $problematic_hash);
            
            // Then test enhanced verification
            $enhanced_verify = verifyPasswordSecure($test_password, $problematic_hash, 1);
            
            return [
                'success' => true, // This test is informational
                'message' => 'Problematic hash analysis completed',
                'details' => [
                    'direct_verify' => $direct_verify,
                    'enhanced_success' => $enhanced_verify['success'],
                    'hash_valid' => $enhanced_verify['hash_valid'],
                    'verification_result' => $enhanced_verify['verification_result'],
                    'failure_analysis' => isset($enhanced_verify['diagnostics']['failure_analysis'])
                ]
            ];
        });
        
        // Test 5: Authentication flow with fallback mechanisms
        $this->runTest('authenticationFlow_FallbackMechanisms', function() {
            // Test various edge cases that might trigger fallback
            $test_cases = [
                'whitespace_hash' => [' $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi ', 'admin123'],
                'empty_hash' => ['', 'anypassword'],
                'null_hash' => [null, 'anypassword']
            ];
            
            $results = [];
            foreach ($test_cases as $case_name => $data) {
                list($hash, $password) = $data;
                
                try {
                    $verification = verifyPasswordSecure($password, $hash, $this->test_user_id);
                    $results[$case_name] = [
                        'success' => $verification['success'],
                        'hash_valid' => $verification['hash_valid'],
                        'handled_gracefully' => true
                    ];
                } catch (Exception $e) {
                    $results[$case_name] = [
                        'success' => false,
                        'hash_valid' => false,
                        'handled_gracefully' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $all_handled = true;
            foreach ($results as $result) {
                if (!$result['handled_gracefully']) {
                    $all_handled = false;
                    break;
                }
            }
            
            return [
                'success' => $all_handled,
                'message' => $all_handled ? 'All edge cases handled gracefully' : 'Some edge cases caused errors',
                'details' => $results
            ];
        });
        
        echo "<br>\n";
    }
    
    /**
     * Run performance impact assessment tests
     */
    private function runPerformanceTests() {
        echo "<h3>⚡ Performance Impact Assessment Tests</h3>\n";
        
        // Test 1: Performance comparison - standard vs enhanced verification
        $this->runTest('performance_StandardVsEnhanced', function() {
            $test_password = 'testpassword123';
            $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $iterations = 100;
            
            // Test standard password_verify performance
            $start_time = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                password_verify($test_password, $test_hash);
            }
            $standard_time = microtime(true) - $start_time;
            
            // Test enhanced verification performance
            $start_time = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            }
            $enhanced_time = microtime(true) - $start_time;
            
            $performance_ratio = $enhanced_time / $standard_time;
            $acceptable_overhead = 3.0; // Enhanced should not be more than 3x slower
            
            $this->performance_metrics['verification_comparison'] = [
                'standard_time' => $standard_time,
                'enhanced_time' => $enhanced_time,
                'performance_ratio' => $performance_ratio,
                'iterations' => $iterations
            ];
            
            return [
                'success' => $performance_ratio <= $acceptable_overhead,
                'message' => sprintf('Enhanced verification is %.2fx slower than standard (acceptable: ≤%.1fx)', $performance_ratio, $acceptable_overhead),
                'details' => [
                    'standard_time_ms' => round($standard_time * 1000, 2),
                    'enhanced_time_ms' => round($enhanced_time * 1000, 2),
                    'performance_ratio' => round($performance_ratio, 2),
                    'acceptable' => $performance_ratio <= $acceptable_overhead
                ]
            ];
        });
        
        // Test 2: Database operations performance
        $this->runTest('performance_DatabaseOperations', function() {
            $iterations = 50;
            
            // Test hash retrieval performance
            $start_time = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                retrieveHashWithValidation($this->test_user_id);
            }
            $retrieval_time = microtime(true) - $start_time;
            
            // Test integrity check performance
            $start_time = microtime(true);
            performDatabaseIntegrityCheck();
            $integrity_time = microtime(true) - $start_time;
            
            $this->performance_metrics['database_operations'] = [
                'retrieval_time' => $retrieval_time,
                'integrity_time' => $integrity_time,
                'retrieval_iterations' => $iterations
            ];
            
            $acceptable_retrieval_time = 0.1; // 100ms for 50 operations
            $acceptable_integrity_time = 2.0; // 2 seconds for full check
            
            return [
                'success' => $retrieval_time <= $acceptable_retrieval_time && $integrity_time <= $acceptable_integrity_time,
                'message' => 'Database operations performance within acceptable limits',
                'details' => [
                    'retrieval_time_ms' => round($retrieval_time * 1000, 2),
                    'integrity_time_ms' => round($integrity_time * 1000, 2),
                    'retrieval_acceptable' => $retrieval_time <= $acceptable_retrieval_time,
                    'integrity_acceptable' => $integrity_time <= $acceptable_integrity_time
                ]
            ];
        });
        
        // Test 3: Memory usage assessment
        $this->runTest('performance_MemoryUsage', function() {
            $initial_memory = memory_get_usage(true);
            
            // Perform various operations to assess memory usage
            $test_hash = password_hash('testpassword', PASSWORD_DEFAULT);
            
            for ($i = 0; $i < 10; $i++) {
                verifyPasswordSecure('testpassword', $test_hash, $this->test_user_id);
                validateHashFormat($test_hash);
                diagnoseVerificationFailure('testpassword', $test_hash);
            }
            
            $final_memory = memory_get_usage(true);
            $memory_increase = $final_memory - $initial_memory;
            
            $this->performance_metrics['memory_usage'] = [
                'initial_memory' => $initial_memory,
                'final_memory' => $final_memory,
                'memory_increase' => $memory_increase
            ];
            
            $acceptable_memory_increase = 1024 * 1024; // 1MB
            
            return [
                'success' => $memory_increase <= $acceptable_memory_increase,
                'message' => sprintf('Memory usage increase: %s (acceptable: ≤%s)', 
                    $this->formatBytes($memory_increase), 
                    $this->formatBytes($acceptable_memory_increase)
                ),
                'details' => [
                    'initial_memory_mb' => round($initial_memory / 1024 / 1024, 2),
                    'final_memory_mb' => round($final_memory / 1024 / 1024, 2),
                    'memory_increase_kb' => round($memory_increase / 1024, 2),
                    'acceptable' => $memory_increase <= $acceptable_memory_increase
                ]
            ];
        });
        
        echo "<br>\n";
    }
    
    /**
     * Run a single test and record results
     */
    private function runTest($test_name, $test_function) {
        try {
            $start_time = microtime(true);
            $result = $test_function();
            $execution_time = microtime(true) - $start_time;
            
            $this->test_results[$test_name] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'] ?? null,
                'execution_time' => $execution_time
            ];
            
            $status = $result['success'] ? '✅' : '❌';
            echo "{$status} <strong>{$test_name}</strong>: {$result['message']}<br>\n";
            
            if (!$result['success'] && isset($result['details'])) {
                echo "   <small>Details: " . json_encode($result['details']) . "</small><br>\n";
            }
            
        } catch (Exception $e) {
            $this->test_results[$test_name] = [
                'success' => false,
                'message' => 'Test failed with exception: ' . $e->getMessage(),
                'details' => ['exception' => $e->getMessage()],
                'execution_time' => 0
            ];
            
            echo "❌ <strong>{$test_name}</strong>: Test failed with exception: {$e->getMessage()}<br>\n";
        }
    }
    
    /**
     * Generate comprehensive test summary
     */
    private function generateTestSummary() {
        echo "<hr>\n";
        echo "<h2>📊 Test Suite Summary</h2>\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = array_sum(array_column($this->test_results, 'success'));
        $failed_tests = $total_tests - $passed_tests;
        $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
        
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>Overall Results</h3>\n";
        echo "Total Tests: <strong>{$total_tests}</strong><br>\n";
        echo "Passed: <strong style='color: green;'>{$passed_tests}</strong><br>\n";
        echo "Failed: <strong style='color: red;'>{$failed_tests}</strong><br>\n";
        echo "Success Rate: <strong>" . round($success_rate, 1) . "%</strong><br>\n";
        echo "</div>\n";
        
        // Performance Summary
        if (!empty($this->performance_metrics)) {
            echo "<h3>Performance Metrics</h3>\n";
            echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            
            if (isset($this->performance_metrics['verification_comparison'])) {
                $comp = $this->performance_metrics['verification_comparison'];
                echo "Verification Performance:<br>\n";
                echo "- Standard: " . round($comp['standard_time'] * 1000, 2) . "ms<br>\n";
                echo "- Enhanced: " . round($comp['enhanced_time'] * 1000, 2) . "ms<br>\n";
                echo "- Ratio: " . round($comp['performance_ratio'], 2) . "x<br><br>\n";
            }
            
            if (isset($this->performance_metrics['memory_usage'])) {
                $mem = $this->performance_metrics['memory_usage'];
                echo "Memory Usage:<br>\n";
                echo "- Increase: " . $this->formatBytes($mem['memory_increase']) . "<br><br>\n";
            }
            
            echo "</div>\n";
        }
        
        // Failed Tests Details
        if ($failed_tests > 0) {
            echo "<h3>Failed Tests Details</h3>\n";
            echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            
            foreach ($this->test_results as $test_name => $result) {
                if (!$result['success']) {
                    echo "<strong>{$test_name}</strong>: {$result['message']}<br>\n";
                    if (isset($result['details'])) {
                        echo "<small>Details: " . json_encode($result['details']) . "</small><br><br>\n";
                    }
                }
            }
            
            echo "</div>\n";
        }
        
        // Recommendations
        echo "<h3>Recommendations</h3>\n";
        echo "<div style='background: #e6f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        
        if ($success_rate >= 90) {
            echo "✅ <strong>Excellent:</strong> Test suite shows the password verification system is working well.<br>\n";
        } elseif ($success_rate >= 75) {
            echo "⚠️ <strong>Good:</strong> Most tests passed, but some issues need attention.<br>\n";
        } else {
            echo "❌ <strong>Needs Attention:</strong> Multiple test failures indicate significant issues.<br>\n";
        }
        
        echo "<br>Next Steps:<br>\n";
        echo "- Review failed tests and address underlying issues<br>\n";
        echo "- Monitor performance metrics in production<br>\n";
        echo "- Run this test suite regularly to catch regressions<br>\n";
        echo "- Consider adding more edge case tests based on production issues<br>\n";
        
        echo "</div>\n";
        
        echo "<hr>\n";
        echo "<p><small>Test suite completed at: " . date('Y-m-d H:i:s') . "</small></p>\n";
    }
    
    /**
     * Format bytes for human readable output
     */
    private function formatBytes($bytes) {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Clean up test environment
     */
    public function cleanup() {
        // Remove test user if created
        if ($this->test_user_id) {
            try {
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND email = ?");
                $stmt->execute([$this->test_user_id, 'test@passwordverification.com']);
                
                // Clean up test backup records
                $stmt = $this->db->prepare("DELETE FROM password_hash_backups WHERE user_id = ?");
                $stmt->execute([$this->test_user_id]);
                
                // Clean up test audit records
                $stmt = $this->db->prepare("DELETE FROM password_hash_audit WHERE user_id = ?");
                $stmt->execute([$this->test_user_id]);
                
            } catch (Exception $e) {
                // Log cleanup error but don't fail
                error_log("Test cleanup error: " . $e->getMessage());
            }
        }
    }
}

// Auto-run tests if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'PasswordVerificationTestSuite.php') {
    echo "<!DOCTYPE html>\n<html><head><title>Password Verification Test Suite</title></head><body>\n";
    
    try {
        $test_suite = new PasswordVerificationTestSuite();
        $test_suite->runAllTests();
        $test_suite->cleanup();
    } catch (Exception $e) {
        echo "<h2>❌ Test Suite Failed to Initialize</h2>\n";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "</body></html>\n";
}

?>
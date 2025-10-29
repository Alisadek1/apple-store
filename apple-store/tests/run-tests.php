<?php
/**
 * Command Line Test Runner for Password Verification Test Suite
 * 
 * Usage: php tests/run-tests.php [options]
 * Options:
 *   --verbose    Show detailed output
 *   --json       Output results in JSON format
 *   --performance-only  Run only performance tests
 *   --unit-only  Run only unit tests
 */

// Set up command line environment
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Parse command line arguments
$options = getopt('', ['verbose', 'json', 'performance-only', 'unit-only', 'help']);

if (isset($options['help'])) {
    echo "Password Verification Test Suite - Command Line Runner\n\n";
    echo "Usage: php tests/run-tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --verbose         Show detailed output\n";
    echo "  --json           Output results in JSON format\n";
    echo "  --performance-only Run only performance tests\n";
    echo "  --unit-only      Run only unit tests\n";
    echo "  --help           Show this help message\n\n";
    exit(0);
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-functions.php';

/**
 * Command Line Test Suite Runner
 */
class CLIPasswordVerificationTestSuite {
    
    private $db;
    private $test_results = [];
    private $performance_metrics = [];
    private $test_user_id = null;
    private $verbose = false;
    private $json_output = false;
    
    public function __construct($options = []) {
        $this->verbose = isset($options['verbose']);
        $this->json_output = isset($options['json']);
        
        $this->db = getDB();
        $this->initializeTestEnvironment();
    }
    
    /**
     * Initialize test environment
     */
    private function initializeTestEnvironment() {
        $this->createTestUser();
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
     * Run tests based on options
     */
    public function runTests($options = []) {
        if (!$this->json_output) {
            echo "Password Verification Test Suite\n";
            echo str_repeat("=", 50) . "\n\n";
        }
        
        $start_time = microtime(true);
        
        if (isset($options['unit-only'])) {
            $this->runUnitTests();
        } elseif (isset($options['performance-only'])) {
            $this->runPerformanceTests();
        } else {
            // Run all tests
            $this->runUnitTests();
            $this->runIntegrationTests();
            $this->runAuthenticationFlowTests();
            $this->runPerformanceTests();
        }
        
        $total_time = microtime(true) - $start_time;
        
        if ($this->json_output) {
            $this->outputJSON($total_time);
        } else {
            $this->outputSummary($total_time);
        }
    }
    
    /**
     * Run unit tests
     */
    private function runUnitTests() {
        if (!$this->json_output) {
            echo "Running Unit Tests...\n";
            echo str_repeat("-", 30) . "\n";
        }
        
        // Test validateHashFormat with valid hash
        $this->runTest('validateHashFormat_ValidHash', function() {
            $valid_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $result = validateHashFormat($valid_hash);
            return [
                'success' => $result['valid'] === true,
                'message' => $result['valid'] ? 'Valid hash correctly identified' : 'Valid hash incorrectly rejected'
            ];
        });
        
        // Test validateHashFormat with invalid hash
        $this->runTest('validateHashFormat_InvalidHash', function() {
            $invalid_hash = 'invalid_hash_format';
            $result = validateHashFormat($invalid_hash);
            return [
                'success' => $result['valid'] === false && !empty($result['issues']),
                'message' => !$result['valid'] ? 'Invalid hash correctly rejected' : 'Invalid hash incorrectly accepted'
            ];
        });
        
        // Test detectHashCorruption
        $this->runTest('detectHashCorruption_Functionality', function() {
            $test_cases = [
                ['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false],
                ['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG', true],
                ['invalid_hash', true]
            ];
            
            $all_passed = true;
            foreach ($test_cases as $data) {
                list($hash, $should_be_corrupted) = $data;
                $corruption_result = detectHashCorruption($hash);
                if ($corruption_result['is_corrupted'] !== $should_be_corrupted) {
                    $all_passed = false;
                    break;
                }
            }
            
            return [
                'success' => $all_passed,
                'message' => $all_passed ? 'Corruption detection working correctly' : 'Corruption detection failed'
            ];
        });
        
        // Test diagnoseVerificationFailure
        $this->runTest('diagnoseVerificationFailure_Functionality', function() {
            $diagnosis = diagnoseVerificationFailure('testpassword', '$2y$10$invalid_hash');
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
                'message' => $has_all_keys ? 'Diagnosis function working correctly' : 'Diagnosis function missing required data'
            ];
        });
        
        if (!$this->json_output) {
            echo "\n";
        }
    }
    
    /**
     * Run integration tests
     */
    private function runIntegrationTests() {
        if (!$this->json_output) {
            echo "Running Integration Tests...\n";
            echo str_repeat("-", 30) . "\n";
        }
        
        // Test database column validation
        $this->runTest('validatePasswordColumnSpecs_Integration', function() {
            $validation = validatePasswordColumnSpecs();
            return [
                'success' => isset($validation['column_info']) && $validation['column_info'] !== null,
                'message' => $validation['column_info'] ? 'Database column validation working' : 'Database column validation failed'
            ];
        });
        
        // Test hash retrieval
        $this->runTest('retrieveHashWithValidation_Integration', function() {
            $retrieval = retrieveHashWithValidation($this->test_user_id);
            return [
                'success' => $retrieval['success'] && !empty($retrieval['hash']),
                'message' => $retrieval['success'] ? 'Hash retrieval working correctly' : 'Hash retrieval failed'
            ];
        });
        
        // Test database integrity check
        $this->runTest('performDatabaseIntegrityCheck_Integration', function() {
            $integrity_check = performDatabaseIntegrityCheck();
            return [
                'success' => isset($integrity_check['overall_status']) && $integrity_check['overall_status'] !== 'error',
                'message' => 'Database integrity check completed: ' . ($integrity_check['overall_status'] ?? 'unknown')
            ];
        });
        
        if (!$this->json_output) {
            echo "\n";
        }
    }
    
    /**
     * Run authentication flow tests
     */
    private function runAuthenticationFlowTests() {
        if (!$this->json_output) {
            echo "Running Authentication Flow Tests...\n";
            echo str_repeat("-", 30) . "\n";
        }
        
        // Test valid credentials
        $this->runTest('verifyPasswordSecure_ValidCredentials', function() {
            $test_password = 'testpassword123';
            $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $verification = verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            
            return [
                'success' => $verification['success'] && $verification['verification_result'],
                'message' => $verification['success'] ? 'Valid credentials verified successfully' : 'Valid credentials verification failed'
            ];
        });
        
        // Test invalid credentials
        $this->runTest('verifyPasswordSecure_InvalidCredentials', function() {
            $test_password = 'wrongpassword';
            $test_hash = password_hash('correctpassword', PASSWORD_DEFAULT);
            $verification = verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            
            return [
                'success' => !$verification['success'] && !$verification['verification_result'],
                'message' => !$verification['success'] ? 'Invalid credentials correctly rejected' : 'Invalid credentials incorrectly accepted'
            ];
        });
        
        // Test problematic hash
        $this->runTest('verifyPasswordSecure_ProblematicHash', function() {
            $problematic_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $test_password = 'admin123';
            
            $direct_verify = password_verify($test_password, $problematic_hash);
            $enhanced_verify = verifyPasswordSecure($test_password, $problematic_hash, 1);
            
            return [
                'success' => true, // This test is informational
                'message' => sprintf('Problematic hash - Direct: %s, Enhanced: %s', 
                    $direct_verify ? 'PASS' : 'FAIL',
                    $enhanced_verify['success'] ? 'PASS' : 'FAIL'
                )
            ];
        });
        
        if (!$this->json_output) {
            echo "\n";
        }
    }
    
    /**
     * Run performance tests
     */
    private function runPerformanceTests() {
        if (!$this->json_output) {
            echo "Running Performance Tests...\n";
            echo str_repeat("-", 30) . "\n";
        }
        
        // Performance comparison test
        $this->runTest('performance_StandardVsEnhanced', function() {
            $test_password = 'testpassword123';
            $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $iterations = 50; // Reduced for CLI
            
            // Standard verification
            $start_time = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                password_verify($test_password, $test_hash);
            }
            $standard_time = microtime(true) - $start_time;
            
            // Enhanced verification
            $start_time = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                verifyPasswordSecure($test_password, $test_hash, $this->test_user_id);
            }
            $enhanced_time = microtime(true) - $start_time;
            
            $performance_ratio = $enhanced_time / $standard_time;
            $acceptable_overhead = 3.0;
            
            $this->performance_metrics['verification_comparison'] = [
                'standard_time' => $standard_time,
                'enhanced_time' => $enhanced_time,
                'performance_ratio' => $performance_ratio,
                'iterations' => $iterations
            ];
            
            return [
                'success' => $performance_ratio <= $acceptable_overhead,
                'message' => sprintf('Enhanced verification is %.2fx slower (acceptable: ≤%.1fx)', $performance_ratio, $acceptable_overhead)
            ];
        });
        
        // Memory usage test
        $this->runTest('performance_MemoryUsage', function() {
            $initial_memory = memory_get_usage(true);
            
            $test_hash = password_hash('testpassword', PASSWORD_DEFAULT);
            for ($i = 0; $i < 5; $i++) {
                verifyPasswordSecure('testpassword', $test_hash, $this->test_user_id);
                validateHashFormat($test_hash);
            }
            
            $final_memory = memory_get_usage(true);
            $memory_increase = $final_memory - $initial_memory;
            $acceptable_memory_increase = 512 * 1024; // 512KB
            
            $this->performance_metrics['memory_usage'] = [
                'memory_increase' => $memory_increase
            ];
            
            return [
                'success' => $memory_increase <= $acceptable_memory_increase,
                'message' => sprintf('Memory increase: %s (acceptable: ≤%s)', 
                    $this->formatBytes($memory_increase), 
                    $this->formatBytes($acceptable_memory_increase)
                )
            ];
        });
        
        if (!$this->json_output) {
            echo "\n";
        }
    }
    
    /**
     * Run a single test
     */
    private function runTest($test_name, $test_function) {
        try {
            $start_time = microtime(true);
            $result = $test_function();
            $execution_time = microtime(true) - $start_time;
            
            $this->test_results[$test_name] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'execution_time' => $execution_time
            ];
            
            if (!$this->json_output) {
                $status = $result['success'] ? '[PASS]' : '[FAIL]';
                echo sprintf("%-8s %s: %s\n", $status, $test_name, $result['message']);
                
                if ($this->verbose && !$result['success']) {
                    echo "         Details: " . json_encode($result['details'] ?? []) . "\n";
                }
            }
            
        } catch (Exception $e) {
            $this->test_results[$test_name] = [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'execution_time' => 0
            ];
            
            if (!$this->json_output) {
                echo sprintf("[ERROR] %s: Exception - %s\n", $test_name, $e->getMessage());
            }
        }
    }
    
    /**
     * Output results in JSON format
     */
    private function outputJSON($total_time) {
        $total_tests = count($this->test_results);
        $passed_tests = array_sum(array_column($this->test_results, 'success'));
        
        $output = [
            'summary' => [
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $total_tests - $passed_tests,
                'success_rate' => $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0,
                'total_execution_time' => $total_time
            ],
            'test_results' => $this->test_results,
            'performance_metrics' => $this->performance_metrics,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
    }
    
    /**
     * Output summary in text format
     */
    private function outputSummary($total_time) {
        $total_tests = count($this->test_results);
        $passed_tests = array_sum(array_column($this->test_results, 'success'));
        $failed_tests = $total_tests - $passed_tests;
        $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
        
        echo str_repeat("=", 50) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        echo sprintf("Total Tests:    %d\n", $total_tests);
        echo sprintf("Passed:         %d\n", $passed_tests);
        echo sprintf("Failed:         %d\n", $failed_tests);
        echo sprintf("Success Rate:   %.1f%%\n", $success_rate);
        echo sprintf("Execution Time: %.3f seconds\n", $total_time);
        echo "\n";
        
        if ($failed_tests > 0) {
            echo "FAILED TESTS:\n";
            echo str_repeat("-", 30) . "\n";
            foreach ($this->test_results as $test_name => $result) {
                if (!$result['success']) {
                    echo sprintf("- %s: %s\n", $test_name, $result['message']);
                }
            }
            echo "\n";
        }
        
        if (!empty($this->performance_metrics)) {
            echo "PERFORMANCE METRICS:\n";
            echo str_repeat("-", 30) . "\n";
            
            if (isset($this->performance_metrics['verification_comparison'])) {
                $comp = $this->performance_metrics['verification_comparison'];
                echo sprintf("Verification Ratio: %.2fx\n", $comp['performance_ratio']);
            }
            
            if (isset($this->performance_metrics['memory_usage'])) {
                $mem = $this->performance_metrics['memory_usage'];
                echo sprintf("Memory Increase: %s\n", $this->formatBytes($mem['memory_increase']));
            }
            echo "\n";
        }
        
        echo "Status: " . ($success_rate >= 90 ? "EXCELLENT" : ($success_rate >= 75 ? "GOOD" : "NEEDS ATTENTION")) . "\n";
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
        if ($this->test_user_id) {
            try {
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND email = ?");
                $stmt->execute([$this->test_user_id, 'test@passwordverification.com']);
                
                $stmt = $this->db->prepare("DELETE FROM password_hash_backups WHERE user_id = ?");
                $stmt->execute([$this->test_user_id]);
                
                $stmt = $this->db->prepare("DELETE FROM password_hash_audit WHERE user_id = ?");
                $stmt->execute([$this->test_user_id]);
                
            } catch (Exception $e) {
                if (!$this->json_output) {
                    echo "Warning: Test cleanup failed - " . $e->getMessage() . "\n";
                }
            }
        }
    }
}

// Run the tests
try {
    $test_suite = new CLIPasswordVerificationTestSuite($options);
    $test_suite->runTests($options);
    $test_suite->cleanup();
    
    // Exit with appropriate code
    $total_tests = count($test_suite->test_results ?? []);
    $passed_tests = array_sum(array_column($test_suite->test_results ?? [], 'success'));
    $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
    
    exit($success_rate >= 90 ? 0 : 1);
    
} catch (Exception $e) {
    if (isset($options['json'])) {
        echo json_encode(['error' => $e->getMessage()]) . "\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    exit(2);
}

?>
<?php
/**
 * Unit Tests for Hash Validation Functions
 * 
 * Focused unit tests for core hash validation functionality
 * Requirements: 3.2, 3.4
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth-functions.php';

class HashValidationUnitTests {
    
    private $test_results = [];
    
    /**
     * Run all unit tests
     */
    public function runAllTests() {
        echo "<h2>🔬 Hash Validation Unit Tests</h2>\n";
        echo "<hr>\n";
        
        $this->testValidateHashFormat();
        $this->testDetectHashCorruption();
        $this->testDiagnoseVerificationFailure();
        $this->testVerifyPasswordSecure();
        
        $this->generateSummary();
    }
    
    /**
     * Test validateHashFormat function
     */
    private function testValidateHashFormat() {
        echo "<h3>Testing validateHashFormat()</h3>\n";
        
        $test_cases = [
            // Valid cases
            'valid_hash' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'expected_valid' => true,
                'description' => 'Standard valid bcrypt hash'
            ],
            
            // Invalid cases
            'empty_hash' => [
                'hash' => '',
                'expected_valid' => false,
                'description' => 'Empty hash string'
            ],
            'null_hash' => [
                'hash' => null,
                'expected_valid' => false,
                'description' => 'Null hash value'
            ],
            'short_hash' => [
                'hash' => '$2y$10$short',
                'expected_valid' => false,
                'description' => 'Hash too short'
            ],
            'long_hash' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi_extra',
                'expected_valid' => false,
                'description' => 'Hash too long'
            ],
            'wrong_prefix' => [
                'hash' => '$2x$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'expected_valid' => false,
                'description' => 'Wrong algorithm prefix'
            ],
            'whitespace_hash' => [
                'hash' => ' $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi ',
                'expected_valid' => false,
                'description' => 'Hash with leading/trailing whitespace'
            ],
            'invalid_chars' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/ig@',
                'expected_valid' => false,
                'description' => 'Hash with invalid characters'
            ]
        ];
        
        foreach ($test_cases as $test_name => $test_case) {
            $result = validateHashFormat($test_case['hash']);
            $passed = $result['valid'] === $test_case['expected_valid'];
            
            $this->recordTest("validateHashFormat_{$test_name}", $passed, $test_case['description']);
            
            $status = $passed ? '✅' : '❌';
            echo "{$status} {$test_case['description']}: " . ($passed ? 'PASS' : 'FAIL') . "<br>\n";
            
            if (!$passed) {
                echo "   Expected: " . ($test_case['expected_valid'] ? 'valid' : 'invalid') . 
                     ", Got: " . ($result['valid'] ? 'valid' : 'invalid') . "<br>\n";
                if (!empty($result['issues'])) {
                    echo "   Issues: " . implode(', ', $result['issues']) . "<br>\n";
                }
            }
        }
        echo "<br>\n";
    }
    
    /**
     * Test detectHashCorruption function
     */
    private function testDetectHashCorruption() {
        echo "<h3>Testing detectHashCorruption()</h3>\n";
        
        $test_cases = [
            'valid_hash' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'expected_corrupted' => false,
                'description' => 'Valid hash should not be detected as corrupted'
            ],
            'truncated_hash' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG',
                'expected_corrupted' => true,
                'description' => 'Truncated hash should be detected as corrupted'
            ],
            'wrong_prefix' => [
                'hash' => '$2x$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'expected_corrupted' => true,
                'description' => 'Wrong prefix should be detected as corruption'
            ],
            'invalid_chars' => [
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/ig@',
                'expected_corrupted' => true,
                'description' => 'Invalid characters should be detected as corruption'
            ],
            'empty_hash' => [
                'hash' => '',
                'expected_corrupted' => false,
                'description' => 'Empty hash should not be considered corrupted (just invalid)'
            ]
        ];
        
        foreach ($test_cases as $test_name => $test_case) {
            $result = detectHashCorruption($test_case['hash']);
            $passed = $result['is_corrupted'] === $test_case['expected_corrupted'];
            
            $this->recordTest("detectHashCorruption_{$test_name}", $passed, $test_case['description']);
            
            $status = $passed ? '✅' : '❌';
            echo "{$status} {$test_case['description']}: " . ($passed ? 'PASS' : 'FAIL') . "<br>\n";
            
            if (!$passed) {
                echo "   Expected corrupted: " . ($test_case['expected_corrupted'] ? 'yes' : 'no') . 
                     ", Got: " . ($result['is_corrupted'] ? 'yes' : 'no') . "<br>\n";
                if (!empty($result['corruption_types'])) {
                    echo "   Corruption types: " . implode(', ', $result['corruption_types']) . "<br>\n";
                }
            }
        }
        echo "<br>\n";
    }
    
    /**
     * Test diagnoseVerificationFailure function
     */
    private function testDiagnoseVerificationFailure() {
        echo "<h3>Testing diagnoseVerificationFailure()</h3>\n";
        
        $test_cases = [
            'basic_diagnosis' => [
                'password' => 'testpassword',
                'hash' => '$2y$10$invalid_hash_for_testing',
                'description' => 'Basic diagnosis functionality'
            ],
            'empty_password' => [
                'password' => '',
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'description' => 'Diagnosis with empty password'
            ],
            'long_password' => [
                'password' => str_repeat('a', 100),
                'hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'description' => 'Diagnosis with very long password'
            ]
        ];
        
        foreach ($test_cases as $test_name => $test_case) {
            $result = diagnoseVerificationFailure($test_case['password'], $test_case['hash']);
            
            // Check required fields
            $required_fields = ['timestamp', 'password_length', 'hash_analysis', 'environment_check', 'recommendations'];
            $has_all_fields = true;
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($result[$field])) {
                    $has_all_fields = false;
                    $missing_fields[] = $field;
                }
            }
            
            // Check that password length is correct
            $correct_length = $result['password_length'] === strlen($test_case['password']);
            
            // Check that recommendations are provided
            $has_recommendations = !empty($result['recommendations']);
            
            $passed = $has_all_fields && $correct_length && $has_recommendations;
            
            $this->recordTest("diagnoseVerificationFailure_{$test_name}", $passed, $test_case['description']);
            
            $status = $passed ? '✅' : '❌';
            echo "{$status} {$test_case['description']}: " . ($passed ? 'PASS' : 'FAIL') . "<br>\n";
            
            if (!$passed) {
                if (!$has_all_fields) {
                    echo "   Missing fields: " . implode(', ', $missing_fields) . "<br>\n";
                }
                if (!$correct_length) {
                    echo "   Password length mismatch: expected " . strlen($test_case['password']) . 
                         ", got " . ($result['password_length'] ?? 'null') . "<br>\n";
                }
                if (!$has_recommendations) {
                    echo "   No recommendations provided<br>\n";
                }
            }
        }
        echo "<br>\n";
    }
    
    /**
     * Test verifyPasswordSecure function
     */
    private function testVerifyPasswordSecure() {
        echo "<h3>Testing verifyPasswordSecure()</h3>\n";
        
        // Generate a known good hash for testing
        $test_password = 'testpassword123';
        $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
        
        $test_cases = [
            'valid_credentials' => [
                'password' => $test_password,
                'hash' => $test_hash,
                'expected_success' => true,
                'description' => 'Valid password and hash should succeed'
            ],
            'invalid_password' => [
                'password' => 'wrongpassword',
                'hash' => $test_hash,
                'expected_success' => false,
                'description' => 'Wrong password should fail'
            ],
            'invalid_hash' => [
                'password' => $test_password,
                'hash' => 'invalid_hash',
                'expected_success' => false,
                'description' => 'Invalid hash should fail'
            ],
            'empty_password' => [
                'password' => '',
                'hash' => $test_hash,
                'expected_success' => false,
                'description' => 'Empty password should fail'
            ],
            'empty_hash' => [
                'password' => $test_password,
                'hash' => '',
                'expected_success' => false,
                'description' => 'Empty hash should fail'
            ]
        ];
        
        foreach ($test_cases as $test_name => $test_case) {
            $result = verifyPasswordSecure($test_case['password'], $test_case['hash'], 999);
            
            // Check required fields
            $required_fields = ['success', 'hash_valid', 'verification_result', 'diagnostics'];
            $has_all_fields = true;
            
            foreach ($required_fields as $field) {
                if (!isset($result[$field])) {
                    $has_all_fields = false;
                    break;
                }
            }
            
            $correct_result = $result['success'] === $test_case['expected_success'];
            $passed = $has_all_fields && $correct_result;
            
            $this->recordTest("verifyPasswordSecure_{$test_name}", $passed, $test_case['description']);
            
            $status = $passed ? '✅' : '❌';
            echo "{$status} {$test_case['description']}: " . ($passed ? 'PASS' : 'FAIL') . "<br>\n";
            
            if (!$passed) {
                if (!$has_all_fields) {
                    echo "   Missing required fields in result<br>\n";
                }
                if (!$correct_result) {
                    echo "   Expected success: " . ($test_case['expected_success'] ? 'true' : 'false') . 
                         ", Got: " . ($result['success'] ? 'true' : 'false') . "<br>\n";
                }
            }
        }
        echo "<br>\n";
    }
    
    /**
     * Record test result
     */
    private function recordTest($test_name, $passed, $description) {
        $this->test_results[$test_name] = [
            'passed' => $passed,
            'description' => $description
        ];
    }
    
    /**
     * Generate test summary
     */
    private function generateSummary() {
        echo "<hr>\n";
        echo "<h2>📊 Unit Test Summary</h2>\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = array_sum(array_column($this->test_results, 'passed'));
        $failed_tests = $total_tests - $passed_tests;
        $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
        
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>Results</h3>\n";
        echo "Total Tests: <strong>{$total_tests}</strong><br>\n";
        echo "Passed: <strong style='color: green;'>{$passed_tests}</strong><br>\n";
        echo "Failed: <strong style='color: red;'>{$failed_tests}</strong><br>\n";
        echo "Success Rate: <strong>" . round($success_rate, 1) . "%</strong><br>\n";
        echo "</div>\n";
        
        if ($failed_tests > 0) {
            echo "<h3>Failed Tests</h3>\n";
            echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            
            foreach ($this->test_results as $test_name => $result) {
                if (!$result['passed']) {
                    echo "<strong>{$test_name}</strong>: {$result['description']}<br>\n";
                }
            }
            
            echo "</div>\n";
        }
        
        echo "<div style='background: #e6f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>Status</h3>\n";
        
        if ($success_rate >= 95) {
            echo "✅ <strong>Excellent:</strong> All core hash validation functions are working correctly.<br>\n";
        } elseif ($success_rate >= 80) {
            echo "⚠️ <strong>Good:</strong> Most functions working, but some issues need attention.<br>\n";
        } else {
            echo "❌ <strong>Critical:</strong> Multiple function failures detected. Immediate attention required.<br>\n";
        }
        
        echo "</div>\n";
    }
}

// Auto-run tests if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'HashValidationUnitTests.php') {
    echo "<!DOCTYPE html>\n<html><head><title>Hash Validation Unit Tests</title></head><body>\n";
    
    try {
        $unit_tests = new HashValidationUnitTests();
        $unit_tests->runAllTests();
    } catch (Exception $e) {
        echo "<h2>❌ Unit Tests Failed to Initialize</h2>\n";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "</body></html>\n";
}

?>
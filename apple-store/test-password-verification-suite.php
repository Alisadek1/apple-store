<?php
/**
 * Password Verification Test Suite Runner
 * 
 * Simple web interface to run the comprehensive test suite
 * Visit: http://localhost/joker&omda/apple-store/test-password-verification-suite.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the test suite
require_once __DIR__ . '/tests/PasswordVerificationTestSuite.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Verification Test Suite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Password Verification Test Suite</h1>
        <p>This comprehensive test suite validates the password verification fix implementation.</p>
        
        <div class="test-section">
            <h3>Test Coverage:</h3>
            <ul>
                <li><strong>Unit Tests:</strong> Hash validation functions</li>
                <li><strong>Integration Tests:</strong> Database hash storage and retrieval</li>
                <li><strong>Authentication Flow Tests:</strong> Various scenarios including edge cases</li>
                <li><strong>Performance Tests:</strong> Impact assessment and benchmarks</li>
            </ul>
        </div>
        
        <hr>
        
        <?php
        try {
            echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>🚀 Running Test Suite...</h3>";
            echo "<p>Please wait while all tests are executed...</p>";
            echo "</div>";
            
            // Run the test suite
            $test_suite = new PasswordVerificationTestSuite();
            $test_suite->runAllTests();
            $test_suite->cleanup();
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Test Suite Completed Successfully</h3>";
            echo "<p>All tests have been executed. Review the results above for any issues that need attention.</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>❌ Test Suite Failed</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please check your database connection and ensure all required files are present.</p>";
            echo "</div>";
        }
        ?>
        
        <div class="footer">
            <p><small>Delete this file after testing: test-password-verification-suite.php</small></p>
            <p><small>Test suite completed at: <?php echo date('Y-m-d H:i:s'); ?></small></p>
        </div>
    </div>
</body>
</html>
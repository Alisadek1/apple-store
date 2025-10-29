<?php
// Test password verification
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'admin123';

echo "<h2>Password Verification Test</h2>";
echo "Testing password: <strong>" . htmlspecialchars($password) . "</strong><br>";
echo "Stored hash: <code>" . htmlspecialchars($hash) . "</code><br>";
echo "Hash length: " . strlen($hash) . " characters<br><br>";

// Test 1: Verify the password
$verify = password_verify($password, $hash);
echo "1. password_verify result: <strong>" . ($verify ? '✅ SUCCESS' : '❌ FAILED') . "</strong><br>";

// Test 2: Create a new hash from the same password
$new_hash = password_hash($password, PASSWORD_BCRYPT);
echo "2. New hash created: <code>" . htmlspecialchars($new_hash) . "</code><br>";
echo "   New hash length: " . strlen($new_hash) . " characters<br><br>";

// Test 3: Verify the new hash
$verify_new = password_verify($password, $new_hash);
echo "3. Verify new hash: <strong>" . ($verify_new ? '✅ SUCCESS' : '❌ FAILED') . "</strong><br>";

// Test 4: Direct string comparison
echo "<br><h3>Hash Comparison</h3>";
echo "Original hash: <code>" . htmlspecialchars($hash) . "</code><br>";
echo "New hash:      <code>" . htmlspecialchars($new_hash) . "</code><br>";
echo "Hashes " . ($hash === $new_hash ? "match exactly" : "are different") . "<br>";

// Test 5: Check PHP version
echo "<br><h3>PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Bcrypt support: " . (defined('PASSWORD_BCRYPT') ? '✅ Enabled' : '❌ Not available');
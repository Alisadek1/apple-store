<?php
// Debug Login Test
// Visit: http://localhost/joker&omda/apple-store/test-login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>🔍 Login Debug Test</h2>";
echo "<hr>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
try {
    $db = getDB();
    echo "✅ Database connected successfully!<br><br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br><br>";
    exit;
}

// Test 2: Check if users table exists
echo "<h3>Test 2: Check Users Table</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users table exists with " . $result['count'] . " users<br><br>";
} catch (Exception $e) {
    echo "❌ Users table error: " . $e->getMessage() . "<br><br>";
    exit;
}

// Test 3: Check if admin user exists
echo "<h3>Test 3: Check Admin User</h3>";
$email = 'admin@applestore.com';
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "✅ Admin user found!<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Name: " . $user['name'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password Hash: " . substr($user['password'], 0, 30) . "...<br><br>";
} else {
    echo "❌ Admin user NOT found!<br>";
    echo "Email searched: " . $email . "<br><br>";
    
    echo "<strong>Fix:</strong> Run this SQL in phpMyAdmin:<br>";
    echo "<pre>";
    echo "DELETE FROM users WHERE email = 'admin@applestore.com';\n";
    echo "INSERT INTO users (name, email, password, role) VALUES \n";
    echo "('Admin', 'admin@applestore.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');";
    echo "</pre><br>";
    exit;
}

// Test 4: Test password verification
echo "<h3>Test 4: Password Verification</h3>";
$test_password = 'admin123';
$stored_hash = $user['password'];

echo "Testing password: <strong>" . $test_password . "</strong><br>";
echo "Against hash: " . substr($stored_hash, 0, 30) . "...<br>";

if (password_verify($test_password, $stored_hash)) {
    echo "✅ Password verification SUCCESSFUL!<br>";
    echo "The password 'admin123' matches the stored hash.<br><br>";
} else {
    echo "❌ Password verification FAILED!<br>";
    echo "The password 'admin123' does NOT match the stored hash.<br><br>";
    
    echo "<strong>Fix:</strong> The password hash is incorrect. Run this SQL:<br>";
    echo "<pre>";
    echo "UPDATE users \n";
    echo "SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' \n";
    echo "WHERE email = 'admin@applestore.com';";
    echo "</pre><br>";
    exit;
}

// Test 5: Generate new password hash
echo "<h3>Test 5: Generate New Password Hash</h3>";
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "New hash for 'admin123': <br>";
echo "<code>" . $new_hash . "</code><br><br>";

echo "If you want to use this new hash, run:<br>";
echo "<pre>";
echo "UPDATE users \n";
echo "SET password = '" . $new_hash . "' \n";
echo "WHERE email = 'admin@applestore.com';";
echo "</pre><br>";

// Test 6: Test login simulation
echo "<h3>Test 6: Login Simulation</h3>";
echo "Simulating login with:<br>";
echo "Email: admin@applestore.com<br>";
echo "Password: admin123<br><br>";

$login_email = 'admin@applestore.com';
$login_password = 'admin123';

$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$login_email]);
$login_user = $stmt->fetch();

if ($login_user && password_verify($login_password, $login_user['password'])) {
    echo "✅ <strong style='color: green;'>LOGIN WOULD SUCCEED!</strong><br>";
    echo "User would be logged in as: " . $login_user['name'] . " (" . $login_user['role'] . ")<br><br>";
    
    echo "<hr>";
    echo "<h2>✅ All Tests Passed!</h2>";
    echo "<p>Your login should work. Try again at:</p>";
    echo "<a href='http://localhost/joker&omda/apple-store/admin/' style='color: #D4AF37; font-size: 18px;'>";
    echo "http://localhost/joker&omda/apple-store/admin/</a><br><br>";
    echo "<p><strong>Credentials:</strong></p>";
    echo "Email: admin@applestore.com<br>";
    echo "Password: admin123<br>";
} else {
    echo "❌ <strong style='color: red;'>LOGIN WOULD FAIL!</strong><br>";
    echo "There is a problem with the password hash.<br><br>";
    
    echo "<strong>Solution:</strong> Run this SQL in phpMyAdmin:<br>";
    echo "<pre>";
    echo "UPDATE users \n";
    echo "SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' \n";
    echo "WHERE email = 'admin@applestore.com';";
    echo "</pre>";
}

echo "<hr>";
echo "<p><small>Delete this file after testing: test-login.php</small></p>";
?>

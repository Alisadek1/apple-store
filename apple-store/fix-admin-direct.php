<?php
/**
 * Direct PHP Fix for Admin Password
 * This bypasses phpMyAdmin and fixes the password directly
 * Visit: http://localhost/joker&omda/apple-store/fix-admin-direct.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>🔧 Direct Admin Password Fix</h1>";
echo "<hr>";

try {
    $db = getDB();
    
    // The correct password hash for 'admin123'
    $correct_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "<h3>Step 1: Check Current Password</h3>";
    $stmt = $db->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->execute(['admin@applestore.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Current password hash length: " . strlen($user['password']) . " characters<br>";
        echo "Current hash: " . htmlspecialchars($user['password']) . "<br><br>";
    } else {
        echo "❌ Admin user not found!<br><br>";
    }
    
    echo "<h3>Step 2: Delete Old Admin</h3>";
    $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute(['admin@applestore.com']);
    echo "✅ Old admin deleted<br><br>";
    
    echo "<h3>Step 3: Insert New Admin with Correct Password</h3>";
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute(['Admin', 'admin@applestore.com', $correct_hash, 'admin']);
    
    if ($result) {
        echo "✅ New admin user created<br><br>";
    } else {
        echo "❌ Failed to create admin<br><br>";
        print_r($stmt->errorInfo());
    }
    
    echo "<h3>Step 4: Verify New Password</h3>";
    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute(['admin@applestore.com']);
    $new_user = $stmt->fetch();
    
    if ($new_user) {
        echo "✅ Admin user found<br>";
        echo "ID: " . $new_user['id'] . "<br>";
        echo "Name: " . $new_user['name'] . "<br>";
        echo "Email: " . $new_user['email'] . "<br>";
        echo "Role: " . $new_user['role'] . "<br>";
        echo "Password hash length: " . strlen($new_user['password']) . " characters<br>";
        echo "Password hash: " . htmlspecialchars($new_user['password']) . "<br><br>";
        
        if (strlen($new_user['password']) === 60) {
            echo "✅ <strong style='color: green;'>Password hash is correct length (60 chars)</strong><br><br>";
        } else {
            echo "❌ <strong style='color: red;'>Password hash is WRONG length (" . strlen($new_user['password']) . " chars)</strong><br><br>";
        }
    }
    
    echo "<h3>Step 5: Test Password Verification</h3>";
    $test_password = 'admin123';
    
    if (password_verify($test_password, $new_user['password'])) {
        echo "✅ <strong style='color: green; font-size: 20px;'>PASSWORD VERIFICATION SUCCESSFUL!</strong><br><br>";
        echo "<hr>";
        echo "<h2>🎉 SUCCESS! You can now login!</h2>";
        echo "<p><a href='admin/login.php' style='color: #D4AF37; font-size: 18px;'>Go to Admin Login</a></p>";
        echo "<p><strong>Credentials:</strong><br>";
        echo "Email: admin@applestore.com<br>";
        echo "Password: admin123</p>";
        echo "<hr>";
        echo "<p><small>Delete this file after successful login: fix-admin-direct.php</small></p>";
    } else {
        echo "❌ <strong style='color: red;'>PASSWORD VERIFICATION FAILED!</strong><br><br>";
        echo "Something is still wrong. The hash in database:<br>";
        echo "<code>" . htmlspecialchars($new_user['password']) . "</code><br><br>";
        echo "Expected hash:<br>";
        echo "<code>" . htmlspecialchars($correct_hash) . "</code><br><br>";
        
        if ($new_user['password'] !== $correct_hash) {
            echo "❌ The hashes don't match! Database is truncating the password.<br>";
            echo "This is a serious database configuration issue.<br><br>";
            
            echo "<strong>Possible solutions:</strong><br>";
            echo "1. Check MySQL max_allowed_packet setting<br>";
            echo "2. Check if there are any triggers on the users table<br>";
            echo "3. Try recreating the database from scratch<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
}
?>

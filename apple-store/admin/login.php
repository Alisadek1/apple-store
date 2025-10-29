<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: ' . ADMIN_URL . '/index.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Apple Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: #1a1a1a;
            border: 2px solid #D4AF37;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(212, 175, 55, 0.3);
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #D4AF37;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #cccccc;
            font-size: 14px;
        }
        .form-label {
            color: #D4AF37;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-control {
            background: #000000;
            border: 1px solid #D4AF37;
            color: #ffffff;
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus {
            background: #000000;
            border-color: #D4AF37;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        .btn-login {
            background: #D4AF37;
            color: #000000;
            border: none;
            padding: 12px;
            font-weight: bold;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #B8941F;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }
        .alert {
            border-radius: 8px;
            border: 1px solid #dc3545;
            background: rgba(220, 53, 69, 0.1);
            color: #ff6b6b;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #D4AF37;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link a:hover {
            color: #B8941F;
        }
        .icon-wrapper {
            background: #D4AF37;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-wrapper i {
            color: #000000;
            font-size: 40px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon-wrapper">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Login</h1>
            <p>Apple Store Management Panel</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="admin@applestore.com" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>

        <div class="back-link">
            <a href="<?php echo SITE_URL; ?>">
                <i class="fas fa-arrow-left me-2"></i>Back to Store
            </a>
        </div>

        <hr style="border-color: #D4AF37; margin: 30px 0;">

        <div style="background: rgba(212, 175, 55, 0.1); padding: 15px; border-radius: 8px; border-left: 3px solid #D4AF37;">
            <p style="color: #D4AF37; margin: 0; font-size: 13px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Default Credentials:</strong><br>
                Email: admin@applestore.com<br>
                Password: admin123
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

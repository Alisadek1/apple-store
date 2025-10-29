<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', t('required_fields'));
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', t('invalid_email'));
    } elseif ($password !== $confirm_password) {
        setFlash('error', t('password_mismatch'));
    } else {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            setFlash('error', t('email_exists'));
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            
            if ($stmt->execute([$name, $email, $phone, $hashed_password])) {
                setFlash('success', t('register_success'));
                redirect(SITE_URL . '/auth/login.php');
            } else {
                setFlash('error', t('error'));
            }
        }
    }
}
?>

<section class="section" style="margin-top: 100px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="contact-form" data-aos="fade-up">
                    <h2 class="text-center mb-4"><?php echo t('sign_up'); ?></h2>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo t('full_name'); ?></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label"><?php echo t('phone'); ?></label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo t('password'); ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-gold w-100 mb-3">
                            <?php echo t('sign_up'); ?>
                        </button>
                        
                        <p class="text-center text-light-gray mb-0">
                            <?php echo t('have_account'); ?>
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-gold">
                                <?php echo t('sign_in'); ?>
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

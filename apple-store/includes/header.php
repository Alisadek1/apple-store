<?php
// Start output buffering to prevent header issues
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$lang = getLang();
$is_rtl = ($lang === 'ar');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('store_name_' . $lang) ?? 'Apple Store'; ?></title>
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <?php if ($is_rtl): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body class="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
            <div class="logo-container me-2">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.jpeg" alt="Apple Store" height="50" class="logo-img">
            </div>
            <span class="text-gold fw-bold"><?php echo getSetting('store_name_' . $lang) ?? 'Apple Store'; ?></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/index.php">
                        <?php echo t('home'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'shop' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/shop.php">
                        <?php echo t('shop'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'about' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/about.php">
                        <?php echo t('about'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'contact' ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/contact.php">
                        <?php echo t('contact'); ?>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <!-- Language Toggle -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/switch-lang.php">
                        <i class="fas fa-language"></i>
                        <?php echo $lang === 'en' ? 'العربية' : 'English'; ?>
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>/index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <?php echo t('dashboard'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <?php echo t('logout'); ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i>
                            <?php echo t('login'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- WhatsApp Floating Button -->
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('whatsapp_number')); ?>" 
   class="whatsapp-float" target="_blank" title="WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<?php
// Display flash messages
$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show flash-message" role="alert">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

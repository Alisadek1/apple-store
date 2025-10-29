<?php
// Start output buffering to prevent header issues
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$lang = getLang();
$is_rtl = ($lang === 'ar');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('admin_panel'); ?> - <?php echo getSetting('store_name_' . $lang); ?></title>
    
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
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/admin.css">
</head>
<body class="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?> admin-body">

<!-- Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-gold fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo ADMIN_URL; ?>/index.php">
            <i class="fab fa-apple"></i>
            <?php echo t('admin_panel'); ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <?php echo $lang === 'ar' ? 'زيارة الموقع' : 'Visit Site'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/switch-lang.php">
                        <i class="fas fa-language"></i>
                        <?php echo $lang === 'en' ? 'العربية' : 'English'; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?php echo $_SESSION['user_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end bg-dark-gray border-gold">
                        <li>
                            <a class="dropdown-menu-item text-light-gray" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                <?php echo t('logout'); ?>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar bg-dark-gray border-end border-gold">
    <div class="sidebar-header">
        <h4 class="text-gold"><?php echo t('dashboard'); ?></h4>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span><?php echo t('dashboard'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/products.php">
                <i class="fas fa-box"></i>
                <span><?php echo t('manage_products'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/categories.php">
                <i class="fas fa-tags"></i>
                <span><?php echo t('manage_categories'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/orders.php">
                <i class="fas fa-shopping-cart"></i>
                <span><?php echo t('manage_orders'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/users.php">
                <i class="fas fa-users"></i>
                <span><?php echo t('manage_users'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'reviews' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/reviews.php">
                <i class="fas fa-star"></i>
                <span><?php echo t('manage_reviews'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'contacts' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/contacts.php">
                <i class="fas fa-envelope"></i>
                <span><?php echo t('manage_contacts'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/settings.php">
                <i class="fas fa-cog"></i>
                <span><?php echo t('settings'); ?></span>
            </a>
        </li>
        <li class="<?php echo $current_page === 'password-test' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>/diagnostics/password-test.php">
                <i class="fas fa-shield-alt"></i>
                <span><?php echo $lang === 'ar' ? 'تشخيص كلمات المرور' : 'Password Diagnostics'; ?></span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php
    // Display flash messages
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

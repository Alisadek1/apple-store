<?php
// Configuration File
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'apple_store');

define('SITE_URL', 'http://localhost/joker&omda/apple-store');
define('ADMIN_URL', SITE_URL . '/admin');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour

// Upload settings
define('UPLOAD_PATH', __DIR__ . '/../assets/images/products/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);

// Default language
define('DEFAULT_LANG', 'en');

// Development mode - set to true for debugging authentication issues
define('DEVELOPMENT_MODE', true);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = DEFAULT_LANG;
}

// Timezone
date_default_timezone_set('Africa/Cairo');

<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

switchLang();

// Redirect back to previous page
$redirect_url = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/index.php';
header("Location: " . $redirect_url);
exit();

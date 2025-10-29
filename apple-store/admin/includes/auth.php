<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/lang.php';
require_once __DIR__ . '/security-middleware.php';

// Enhanced admin access validation with security middleware
validateAdminAccess();

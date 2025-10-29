<?php
// Helper Functions

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect function
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        // If headers are already sent, use JavaScript redirect
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit();
    }
}

// Get current language
function getLang() {
    return $_SESSION['lang'] ?? 'en';
}

// Switch language
function switchLang() {
    $_SESSION['lang'] = ($_SESSION['lang'] === 'en') ? 'ar' : 'en';
}

// Format price
function formatPrice($price, $max_price = null) {
    $lang = getLang();
    if ($max_price && $max_price > $price) {
        return number_format($price, 0) . ' - ' . number_format($max_price, 0) . ' ' . ($lang === 'en' ? 'EGP' : 'ج.م');
    }
    return number_format($price, 0) . ' ' . ($lang === 'en' ? 'EGP' : 'ج.م');
}

// Format date
function formatDate($date) {
    $lang = getLang();
    $timestamp = strtotime($date);
    if ($lang === 'ar') {
        return date('Y/m/d', $timestamp);
    }
    return date('M d, Y', $timestamp);
}

// Get user data
function getUserData($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Upload image
function uploadImage($file, $folder = 'products') {
    $target_dir = __DIR__ . "/../assets/images/{$folder}/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file["size"] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Get setting value
function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

// Update setting
function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Generate WhatsApp link
function generateWhatsAppLink($order_id) {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("SELECT o.*, GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as items
                          FROM orders o
                          LEFT JOIN order_items oi ON o.id = oi.order_id
                          WHERE o.id = ?
                          GROUP BY o.id");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) return null;
    
    $whatsapp_number = getSetting('whatsapp_number');
    $lang = getLang();
    
    if ($lang === 'ar') {
        $message = "مرحباً، أود تأكيد طلبي:\n\n";
        $message .= "رقم الطلب: {$order['id']}\n";
        $message .= "الاسم: {$order['buyer_name']}\n";
        $message .= "الهاتف: {$order['buyer_phone']}\n";
        $message .= "المنتجات: {$order['items']}\n";
        $message .= "الإجمالي: " . formatPrice($order['total']) . "\n";
        $message .= "نوع الدفع: " . ($order['payment_type'] === 'deposit' ? 'عربون' : 'كامل');
    } else {
        $message = "Hello, I would like to confirm my order:\n\n";
        $message .= "Order ID: {$order['id']}\n";
        $message .= "Name: {$order['buyer_name']}\n";
        $message .= "Phone: {$order['buyer_phone']}\n";
        $message .= "Products: {$order['items']}\n";
        $message .= "Total: " . formatPrice($order['total']) . "\n";
        $message .= "Payment Type: " . ($order['payment_type'] === 'deposit' ? 'Deposit' : 'Full');
    }
    
    return "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp_number) . "?text=" . urlencode($message);
}

// Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Pagination helper
function paginate($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}

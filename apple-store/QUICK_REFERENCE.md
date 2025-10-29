# 🚀 Quick Reference Guide

Fast reference for common tasks in the Apple Store E-Commerce system.

---

## 📍 Important URLs

```
Frontend:  http://localhost/joker&omda/apple-store/
Admin:     http://localhost/joker&omda/apple-store/admin/
Database:  http://localhost/phpmyadmin
```

---

## 🔑 Default Credentials

```
Email:    admin@applestore.com
Password: admin123
```

⚠️ **Change immediately after first login!**

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `config/config.php` | Site configuration |
| `config/database.php` | Database connection |
| `includes/functions.php` | Helper functions |
| `includes/lang.php` | Translations |
| `database/schema.sql` | Database structure |

---

## 🗄️ Database Quick Commands

### Backup Database
```bash
mysqldump -u root -p apple_store > backup.sql
```

### Restore Database
```bash
mysql -u root -p apple_store < backup.sql
```

### Reset Admin Password
```sql
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@applestore.com';
-- Password: admin123
```

### View All Orders
```sql
SELECT o.id, o.buyer_name, o.total, o.status, o.created_at 
FROM orders o 
ORDER BY o.created_at DESC;
```

### Count Products by Category
```sql
SELECT c.name_en, COUNT(p.id) as count 
FROM categories c 
LEFT JOIN products p ON c.id = p.category_id 
GROUP BY c.id;
```

---

## 🎨 Common Tasks

### Add New Product (Admin)
1. Login to admin panel
2. Go to **Manage Products**
3. Click **Add New**
4. Fill form (both EN & AR)
5. Upload image
6. Set price and stock
7. Click **Save**

### Process Order (Admin)
1. Go to **Manage Orders**
2. Click eye icon to view order
3. Update status dropdown
4. Click **Save**
5. Click WhatsApp button to contact customer

### Add New Category (Admin)
1. Go to **Manage Categories**
2. Click **Add New**
3. Enter name (EN & AR)
4. Enter Font Awesome icon class
5. Click **Save**

### Approve Review (Admin)
1. Go to **Manage Reviews**
2. Find pending review
3. Click green checkmark
4. Review appears on product page

### Update Store Settings (Admin)
1. Go to **Settings**
2. Update store information
3. Update WhatsApp number
4. Click **Save**

---

## 🌐 Language System

### Switch Language
```php
// In any page
<a href="<?php echo SITE_URL; ?>/switch-lang.php">
    <?php echo $lang === 'en' ? 'العربية' : 'English'; ?>
</a>
```

### Add Translation
```php
// In includes/lang.php
$translations = [
    'en' => [
        'new_key' => 'English Text',
    ],
    'ar' => [
        'new_key' => 'النص العربي',
    ]
];

// Use in templates
<?php echo t('new_key'); ?>
```

### Check Current Language
```php
$lang = getLang(); // Returns 'en' or 'ar'
$is_rtl = ($lang === 'ar'); // Returns true/false
```

---

## 🔧 Configuration

### Update Site URL
```php
// config/config.php
define('SITE_URL', 'http://localhost/joker&omda/apple-store');
```

### Update Database Credentials
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'apple_store');
```

### Update Upload Limit
```php
// config/config.php
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
```

---

## 🎨 Styling

### Theme Colors
```css
--black: #000000;
--gold: #D4AF37;
--dark-gold: #B8941F;
--light-gray: #CCCCCC;
--dark-gray: #1A1A1A;
```

### Add Custom CSS
```html
<!-- In includes/header.php, before </head> -->
<style>
.custom-class {
    /* Your styles */
}
</style>
```

### Change Font
```php
// In includes/header.php
<link href="https://fonts.googleapis.com/css2?family=YourFont:wght@300;400;600;700&display=swap" rel="stylesheet">
```

```css
/* In assets/css/style.css */
body {
    font-family: 'YourFont', sans-serif;
}
```

---

## 📱 WhatsApp Integration

### Update WhatsApp Number
1. Admin → **Settings**
2. Update **WhatsApp Number**
3. Format: `+201234567890`
4. Click **Save**

### Test WhatsApp Link
```php
// Generate link for order #1
$link = generateWhatsAppLink(1);
echo $link;
```

### Custom WhatsApp Message
```php
// In includes/functions.php, modify generateWhatsAppLink()
$message = "Your custom message here";
$message .= "\nOrder ID: {$order['id']}";
```

---

## 🐛 Debugging

### Enable Error Display
```php
// Add to config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Database Connection
```php
// Create test.php in root
<?php
require_once 'config/database.php';
$db = getDB();
echo "Connected successfully!";
?>
```

### View Session Data
```php
// Add to any page
<?php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

### Check File Permissions
```bash
# Windows
icacls assets\images\products

# Linux/Mac
ls -la assets/images/products/
```

---

## 🔒 Security Tasks

### Change Admin Password
```php
// Create change_password.php
<?php
$new_password = 'your_new_password';
$hash = password_hash($new_password, PASSWORD_DEFAULT);
echo $hash;
// Copy hash and update in database
?>
```

### Clear Sessions
```php
// Create clear_sessions.php
<?php
session_start();
session_destroy();
echo "Sessions cleared!";
?>
```

### Check Failed Logins
```sql
-- Add logging table first, then:
SELECT * FROM login_attempts 
WHERE success = 0 
ORDER BY attempted_at DESC 
LIMIT 20;
```

---

## 📊 Common Queries

### Today's Sales
```sql
SELECT SUM(total) as today_sales 
FROM orders 
WHERE DATE(created_at) = CURDATE() 
AND status != 'cancelled';
```

### Top Selling Products
```sql
SELECT p.name_en, SUM(oi.quantity) as sold 
FROM products p 
JOIN order_items oi ON p.id = oi.product_id 
JOIN orders o ON oi.order_id = o.id 
WHERE o.status != 'cancelled' 
GROUP BY p.id 
ORDER BY sold DESC 
LIMIT 10;
```

### Guest Orders
```sql
SELECT * FROM orders 
WHERE user_id IS NULL 
ORDER BY created_at DESC;
```

### Low Stock Products
```sql
SELECT name_en, name_ar, stock 
FROM products 
WHERE stock < 5 
ORDER BY stock ASC;
```

---

## 🚀 Performance

### Clear Browser Cache
```
Ctrl + Shift + Delete (Windows)
Cmd + Shift + Delete (Mac)
```

### Optimize Database
```sql
OPTIMIZE TABLE users, products, orders, order_items, categories, reviews, contacts, settings;
```

### Check Table Sizes
```sql
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'apple_store'
ORDER BY (data_length + index_length) DESC;
```

---

## 📦 Backup & Restore

### Quick Backup
```bash
# Database
mysqldump -u root -p apple_store > backup_$(date +%Y%m%d).sql

# Files
tar -czf backup_files_$(date +%Y%m%d).tar.gz apple-store/
```

### Quick Restore
```bash
# Database
mysql -u root -p apple_store < backup_20251028.sql

# Files
tar -xzf backup_files_20251028.tar.gz
```

---

## 🔄 Updates

### Update Product Price
```sql
UPDATE products 
SET price = 50000 
WHERE id = 1;
```

### Update Order Status
```sql
UPDATE orders 
SET status = 'completed' 
WHERE id = 1;
```

### Bulk Update Stock
```sql
UPDATE products 
SET stock = stock + 10 
WHERE category_id = 1;
```

---

## 📞 Quick Contacts

| Purpose | Contact |
|---------|---------|
| Technical Support | info@applestore.com |
| Security Issues | security@applestore.com |
| WhatsApp | +201234567890 |

---

## 🎯 Keyboard Shortcuts

### Admin Dashboard
- `Ctrl + S` - Save form
- `Esc` - Close modal
- `Ctrl + F` - Search in DataTable

### Browser
- `F5` - Refresh page
- `Ctrl + Shift + R` - Hard refresh
- `F12` - Open developer tools

---

## 📱 Testing

### Test Responsive Design
```
Chrome DevTools → Toggle Device Toolbar (Ctrl+Shift+M)
```

### Test on Mobile
```
Find IP: ipconfig (Windows) or ifconfig (Mac/Linux)
Access: http://YOUR_IP/joker&omda/apple-store/
```

### Test Email
```php
// Create test_email.php
<?php
$to = "test@example.com";
$subject = "Test Email";
$message = "This is a test";
$headers = "From: noreply@applestore.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent!";
} else {
    echo "Email failed!";
}
?>
```

---

## 🆘 Common Issues

### Issue: Blank Page
**Solution**: Enable error display in config.php

### Issue: Images Not Showing
**Solution**: Check file permissions (777 on uploads folder)

### Issue: Database Connection Error
**Solution**: Verify credentials in config/config.php

### Issue: RTL Not Working
**Solution**: Clear cache, check Bootstrap RTL CSS loading

### Issue: WhatsApp Link Not Working
**Solution**: Update number in Settings, check format

---

## 📚 Documentation Links

- **Full Documentation**: README.md
- **Installation Guide**: INSTALLATION.md
- **Code Review**: MCP_REVIEW.md
- **Security Policy**: SECURITY.md
- **Changelog**: CHANGELOG.md

---

**Last Updated**: October 28, 2025  
**Version**: 1.0.0

**Need more help?** Check the full documentation files!

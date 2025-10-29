# 🚀 Apple Store E-Commerce - Installation Guide

Complete step-by-step installation guide for the Apple Store E-Commerce system.

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache or Nginx
- **XAMPP/WAMP**: (Recommended for local development)

---

## 🔧 Installation Steps

### Step 1: Download/Clone the Project

Place the project in your web server directory:

```
c:/xampp/htdocs/joker&omda/apple-store/
```

Or for Linux/Mac:
```
/var/www/html/apple-store/
```

### Step 2: Create Database

1. Open **phpMyAdmin** in your browser:
   ```
   http://localhost/phpmyadmin
   ```

2. Click on **"New"** in the left sidebar

3. Create a database named: `apple_store`
   - Collation: `utf8mb4_unicode_ci`

4. Click on the newly created database

5. Go to **"Import"** tab

6. Click **"Choose File"** and select:
   ```
   apple-store/database/schema.sql
   ```

7. Click **"Go"** at the bottom

8. You should see a success message with all tables created

### Step 3: Configure Database Connection

1. Open the file:
   ```
   apple-store/config/config.php
   ```

2. Update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Your MySQL password
   define('DB_NAME', 'apple_store');
   ```

3. Update the site URL if needed:
   ```php
   define('SITE_URL', 'http://localhost/joker&omda/apple-store');
   ```

### Step 4: Set File Permissions

Make sure the uploads directory is writable:

**Windows (XAMPP):**
- Right-click on `assets/images/products/` folder
- Properties → Security → Edit
- Give "Full Control" to "Users"

**Linux/Mac:**
```bash
chmod -R 777 apple-store/assets/images/products/
```

### Step 5: Verify PHP Configuration

1. Check your `php.ini` file for these settings:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

2. Restart Apache after making changes

### Step 6: Access the Website

**Frontend:**
```
http://localhost/joker&omda/apple-store/
```

**Admin Panel:**
```
http://localhost/joker&omda/apple-store/admin/
```

**Default Admin Credentials:**
- Email: `admin@applestore.com`
- Password: `admin123`

⚠️ **IMPORTANT**: Change the admin password immediately after first login!

---

## 🎨 Post-Installation Configuration

### 1. Update Store Settings

1. Login to admin panel
2. Go to **Settings**
3. Update:
   - Store name (English & Arabic)
   - WhatsApp number
   - Email address
   - Store address
   - Local governorate

### 2. Add Products

1. Go to **Manage Products**
2. Click **"Add New"**
3. Fill in product details:
   - Name (English & Arabic)
   - Category
   - Description
   - Price (or price range)
   - Upload image
   - Set stock quantity
   - Mark as featured (optional)

### 3. Manage Categories

1. Go to **Manage Categories**
2. Default categories are already created:
   - iPhone
   - iPad
   - MacBook
   - Accessories
3. Edit or add new categories as needed

### 4. Test Order Flow

1. Visit the frontend
2. Browse products
3. Click "Buy Now"
4. Fill order form
5. Verify WhatsApp redirect works
6. Check order appears in admin panel

---

## 🔐 Security Recommendations

### 1. Change Admin Password

```sql
-- Run this in phpMyAdmin SQL tab
UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASHED_PASSWORD' 
WHERE email = 'admin@applestore.com';
```

Or use the PHP password hasher:
```php
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>
```

### 2. Update Database Credentials

- Use a strong MySQL password
- Create a dedicated MySQL user for the application
- Grant only necessary permissions

### 3. Secure File Permissions

**Production Server:**
```bash
# Files
find apple-store -type f -exec chmod 644 {} \;

# Directories
find apple-store -type d -exec chmod 755 {} \;

# Upload directory
chmod 777 apple-store/assets/images/products/
```

### 4. Enable HTTPS

- Install SSL certificate
- Update `SITE_URL` in `config/config.php` to use `https://`

---

## 🐛 Troubleshooting

### Issue: Database Connection Error

**Solution:**
1. Verify MySQL is running
2. Check database credentials in `config/config.php`
3. Ensure database `apple_store` exists
4. Check MySQL user has proper permissions

### Issue: Images Not Uploading

**Solution:**
1. Check folder permissions: `chmod 777 assets/images/products/`
2. Verify `upload_max_filesize` in php.ini
3. Ensure folder exists and is writable

### Issue: Blank Page / White Screen

**Solution:**
1. Enable error reporting in `config/config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check Apache error logs
3. Verify all required PHP extensions are installed

### Issue: RTL Layout Not Working

**Solution:**
1. Clear browser cache
2. Verify Bootstrap RTL CSS is loading
3. Check language session is set correctly

### Issue: WhatsApp Link Not Working

**Solution:**
1. Update WhatsApp number in Settings
2. Format: `+201234567890` (with country code)
3. Test the generated link manually

---

## 📱 Mobile Testing

Test the website on mobile devices:

1. Find your local IP address:
   - Windows: `ipconfig`
   - Mac/Linux: `ifconfig`

2. Access from mobile on same network:
   ```
   http://YOUR_IP_ADDRESS/joker&omda/apple-store/
   ```

---

## 🚀 Deployment to Production

### 1. Prepare Files

1. Remove development files:
   ```bash
   rm -rf .git
   rm INSTALLATION.md
   ```

2. Update `config/config.php`:
   ```php
   define('SITE_URL', 'https://yourdomain.com');
   ```

### 2. Upload to Server

Use FTP/SFTP to upload all files to your web server

### 3. Import Database

1. Export local database from phpMyAdmin
2. Import to production database
3. Update connection settings

### 4. Set Permissions

```bash
chmod -R 755 /path/to/apple-store
chmod -R 777 /path/to/apple-store/assets/images/products
```

### 5. Configure Web Server

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 6. Enable SSL

```bash
# Using Let's Encrypt
sudo certbot --apache -d yourdomain.com
```

---

## 📊 Database Backup

### Manual Backup

```bash
mysqldump -u root -p apple_store > backup_$(date +%Y%m%d).sql
```

### Automated Backup (Cron Job)

```bash
# Add to crontab (daily at 2 AM)
0 2 * * * mysqldump -u root -p'password' apple_store > /backups/apple_store_$(date +\%Y\%m\%d).sql
```

---

## 🔄 Updates & Maintenance

### Regular Tasks

1. **Daily:**
   - Check pending orders
   - Respond to contact messages
   - Approve reviews

2. **Weekly:**
   - Update product stock
   - Check system logs
   - Database backup

3. **Monthly:**
   - Review analytics
   - Update product prices
   - Clean old data

---

## 📞 Support

If you encounter any issues:

1. Check the **MCP_REVIEW.md** for known issues
2. Review **README.md** for feature documentation
3. Check Apache/PHP error logs
4. Contact: info@applestore.com

---

## ✅ Installation Checklist

- [ ] XAMPP/WAMP installed and running
- [ ] Database created (`apple_store`)
- [ ] SQL schema imported successfully
- [ ] Database credentials configured
- [ ] File permissions set correctly
- [ ] Frontend accessible
- [ ] Admin panel accessible
- [ ] Admin login working
- [ ] Store settings updated
- [ ] WhatsApp number configured
- [ ] Test product added
- [ ] Test order placed
- [ ] WhatsApp redirect working
- [ ] Email configured (optional)
- [ ] SSL certificate installed (production)
- [ ] Backup strategy implemented

---

**Installation Complete! 🎉**

Your Apple Store E-Commerce system is now ready to use.

Visit: `http://localhost/joker&omda/apple-store/`

Admin: `http://localhost/joker&omda/apple-store/admin/`

---

**Need Help?** Refer to README.md for detailed documentation.

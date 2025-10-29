# 🚀 Production Deployment Guide

Complete guide for deploying the Apple Store E-Commerce system to a production server.

---

## 📋 Pre-Deployment Checklist

### Development Environment
- [ ] All features tested locally
- [ ] Testing checklist completed
- [ ] Code reviewed
- [ ] Documentation updated
- [ ] Database optimized
- [ ] Backup created

### Production Requirements
- [ ] Server access (SSH/FTP)
- [ ] Domain name configured
- [ ] SSL certificate ready
- [ ] Database server access
- [ ] Email server configured (optional)

---

## 🖥️ Server Requirements

### Minimum Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher (with mod_rewrite)
- **Disk Space**: 500 MB minimum
- **RAM**: 512 MB minimum
- **Bandwidth**: Unlimited recommended

### Recommended Requirements
- **PHP**: 8.0+
- **MySQL**: 8.0+
- **Apache**: 2.4+ or Nginx 1.18+
- **Disk Space**: 2 GB+
- **RAM**: 2 GB+
- **SSL**: Let's Encrypt or commercial certificate

### PHP Extensions Required
```
- PDO
- PDO_MySQL
- mbstring
- openssl
- fileinfo
- gd or imagick
- json
- session
```

### Apache Modules Required
```
- mod_rewrite
- mod_headers
- mod_expires
- mod_deflate
```

---

## 📦 Step 1: Prepare Files

### 1.1 Clean Development Files

Remove development-only files:

```bash
# Navigate to project directory
cd /path/to/apple-store

# Remove git files (if using git)
rm -rf .git
rm .gitignore

# Remove development docs (optional)
rm TESTING_CHECKLIST.md
rm DEPLOYMENT_GUIDE.md
```

### 1.2 Update Configuration

Edit `config/config.php`:

```php
<?php
// Production Configuration

// Database
define('DB_HOST', 'your_production_host');
define('DB_USER', 'your_production_user');
define('DB_PASS', 'your_strong_password');
define('DB_NAME', 'your_production_db');

// Site URL (with HTTPS)
define('SITE_URL', 'https://yourdomain.com');
define('ADMIN_URL', SITE_URL . '/admin');

// Security
define('SESSION_LIFETIME', 3600);

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/logs/php-error.log');

// Upload settings
define('UPLOAD_PATH', __DIR__ . '/../assets/images/products/');
define('MAX_FILE_SIZE', 5242880);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);

// Default language
define('DEFAULT_LANG', 'en');

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
```

### 1.3 Update .htaccess

Edit `.htaccess` for production:

```apache
# Production .htaccess

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove www (optional)
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Disable Directory Browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "(config\.php|database\.php|\.sql|\.md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
</IfModule>
```

### 1.4 Create Archive

```bash
# Create deployment archive
tar -czf apple-store-production.tar.gz apple-store/

# Or use zip
zip -r apple-store-production.zip apple-store/
```

---

## 🗄️ Step 2: Setup Production Database

### 2.1 Create Database

```sql
-- Login to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE apple_store_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user
CREATE USER 'applestore_user'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON apple_store_prod.* TO 'applestore_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### 2.2 Import Schema

```bash
# Import database schema
mysql -u applestore_user -p apple_store_prod < database/schema.sql
```

### 2.3 Update Admin Password

```sql
-- Login to database
mysql -u applestore_user -p apple_store_prod

-- Generate new password hash (use PHP)
-- php -r "echo password_hash('your_new_secure_password', PASSWORD_DEFAULT);"

-- Update admin password
UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASH_HERE' 
WHERE email = 'admin@applestore.com';

-- Verify
SELECT id, email, role FROM users WHERE role = 'admin';

-- Exit
EXIT;
```

---

## 📤 Step 3: Upload Files

### Option A: FTP/SFTP

```bash
# Using FileZilla or similar FTP client
# 1. Connect to server
# 2. Navigate to public_html or www directory
# 3. Upload apple-store folder
# 4. Ensure all files transferred successfully
```

### Option B: SCP (Secure Copy)

```bash
# Upload to server
scp apple-store-production.tar.gz user@your-server.com:/path/to/web/

# SSH into server
ssh user@your-server.com

# Extract files
cd /path/to/web/
tar -xzf apple-store-production.tar.gz

# Remove archive
rm apple-store-production.tar.gz
```

### Option C: Git Deployment

```bash
# On server
cd /path/to/web/
git clone https://your-repo.git apple-store
cd apple-store

# Checkout production branch
git checkout production
```

---

## 🔐 Step 4: Set File Permissions

### Linux/Unix Servers

```bash
# Navigate to project directory
cd /path/to/apple-store

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make upload directory writable
chmod 777 assets/images/products/

# Protect config files
chmod 600 config/config.php
chmod 600 config/database.php

# Set ownership (replace 'www-data' with your web server user)
chown -R www-data:www-data /path/to/apple-store
```

### Windows Servers

```powershell
# Right-click on folders
# Properties → Security → Edit
# Give appropriate permissions to IIS_IUSRS or IUSR
```

---

## 🔒 Step 5: Configure SSL Certificate

### Option A: Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
# Test renewal
sudo certbot renew --dry-run
```

### Option B: Commercial SSL

1. Purchase SSL certificate
2. Generate CSR on server
3. Submit CSR to certificate authority
4. Download certificate files
5. Install on server

```apache
# Apache SSL Configuration
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /path/to/apple-store
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/ca_bundle.crt
</VirtualHost>
```

---

## ⚙️ Step 6: Configure Web Server

### Apache Configuration

Create virtual host file:

```apache
# /etc/apache2/sites-available/applestore.conf

<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /path/to/apple-store
    
    <Directory /path/to/apple-store>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/applestore_error.log
    CustomLog ${APACHE_LOG_DIR}/applestore_access.log combined
    
    # Redirect to HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =yourdomain.com [OR]
    RewriteCond %{SERVER_NAME} =www.yourdomain.com
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /path/to/apple-store
    
    <Directory /path/to/apple-store>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/applestore_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/applestore_ssl_access.log combined
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/ca_bundle.crt
</VirtualHost>
```

Enable site and restart Apache:

```bash
# Enable site
sudo a2ensite applestore.conf

# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/applestore

server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /path/to/apple-store;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Logging
    access_log /var/log/nginx/applestore_access.log;
    error_log /var/log/nginx/applestore_error.log;
    
    # PHP Processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(sql|md|txt)$ {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable and restart:

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/applestore /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## 📧 Step 7: Configure Email (Optional)

### PHP mail() Function

Edit `php.ini`:

```ini
[mail function]
SMTP = smtp.your-provider.com
smtp_port = 587
sendmail_from = noreply@yourdomain.com
```

### SMTP Configuration

Install PHPMailer (if needed):

```bash
composer require phpmailer/phpmailer
```

---

## 🔄 Step 8: Setup Automated Backups

### Database Backup Script

Create `/root/scripts/backup-db.sh`:

```bash
#!/bin/bash

# Configuration
DB_NAME="apple_store_prod"
DB_USER="applestore_user"
DB_PASS="your_password"
BACKUP_DIR="/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/apple_store_$DATE.sql.gz

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: apple_store_$DATE.sql.gz"
```

Make executable:

```bash
chmod +x /root/scripts/backup-db.sh
```

### Files Backup Script

Create `/root/scripts/backup-files.sh`:

```bash
#!/bin/bash

# Configuration
SOURCE_DIR="/path/to/apple-store"
BACKUP_DIR="/backups/files"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup files
tar -czf $BACKUP_DIR/apple_store_files_$DATE.tar.gz $SOURCE_DIR

# Delete backups older than 7 days
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: apple_store_files_$DATE.tar.gz"
```

Make executable:

```bash
chmod +x /root/scripts/backup-files.sh
```

### Setup Cron Jobs

```bash
# Edit crontab
crontab -e

# Add backup jobs
# Database backup daily at 2 AM
0 2 * * * /root/scripts/backup-db.sh >> /var/log/db-backup.log 2>&1

# Files backup weekly on Sunday at 3 AM
0 3 * * 0 /root/scripts/backup-files.sh >> /var/log/files-backup.log 2>&1
```

---

## 📊 Step 9: Setup Monitoring

### Log Monitoring

Create `/root/scripts/check-errors.sh`:

```bash
#!/bin/bash

ERROR_LOG="/var/log/apache2/applestore_error.log"
ALERT_EMAIL="admin@yourdomain.com"

# Check for errors in last hour
ERRORS=$(grep -i "error" $ERROR_LOG | tail -100)

if [ ! -z "$ERRORS" ]; then
    echo "$ERRORS" | mail -s "Apple Store Errors Detected" $ALERT_EMAIL
fi
```

### Uptime Monitoring

Use services like:
- UptimeRobot (free)
- Pingdom
- StatusCake
- New Relic

---

## ✅ Step 10: Post-Deployment Verification

### Checklist

- [ ] Website loads via HTTPS
- [ ] SSL certificate valid
- [ ] Database connection works
- [ ] Admin login works
- [ ] Products display correctly
- [ ] Orders can be placed
- [ ] WhatsApp integration works
- [ ] Language switching works
- [ ] Images load correctly
- [ ] Forms submit successfully
- [ ] Email sending works (if configured)
- [ ] Error logging works
- [ ] Backups running
- [ ] Monitoring active

### Test URLs

```
https://yourdomain.com/
https://yourdomain.com/shop.php
https://yourdomain.com/admin/
https://yourdomain.com/contact.php
```

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

**Solutions:**
1. Check Apache error log
2. Verify .htaccess syntax
3. Check file permissions
4. Enable error display temporarily

### Issue: Database Connection Error

**Solutions:**
1. Verify database credentials
2. Check MySQL is running
3. Verify user permissions
4. Check firewall rules

### Issue: Images Not Loading

**Solutions:**
1. Check file permissions (777 on uploads)
2. Verify image paths in database
3. Check .htaccess rules
4. Verify Apache modules enabled

### Issue: SSL Not Working

**Solutions:**
1. Verify certificate installation
2. Check virtual host configuration
3. Restart web server
4. Check firewall (port 443)

---

## 📞 Support

**Technical Issues**: info@applestore.com  
**Security Issues**: security@applestore.com  
**Emergency**: +201234567890

---

## 📝 Deployment Checklist

- [ ] Files uploaded
- [ ] Database created and imported
- [ ] Configuration updated
- [ ] File permissions set
- [ ] SSL certificate installed
- [ ] Web server configured
- [ ] Admin password changed
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Testing completed
- [ ] Documentation updated
- [ ] Team notified

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Version**: 1.0.0  
**Status**: _______________

---

**🎉 Congratulations! Your Apple Store E-Commerce system is now live!**

**Last Updated**: October 28, 2025

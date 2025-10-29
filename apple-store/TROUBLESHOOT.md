# 🔧 Complete Troubleshooting Guide

## Current Issue: Cannot Login to Admin Panel

---

## ✅ Solution: Use the Test & Fix Tools

I've created diagnostic tools to help you. Follow these steps:

### **Step 1: Run Login Diagnostic**

Visit this URL in your browser:
```
http://localhost/joker&omda/apple-store/test-login.php
```

This will:
- ✅ Test database connection
- ✅ Check if admin user exists
- ✅ Verify password hash
- ✅ Simulate login
- ✅ Show you exactly what's wrong

### **Step 2: Use New Admin Login Page**

I've created a dedicated admin login page. Try it:
```
http://localhost/joker&omda/apple-store/admin/login.php
```

**Credentials:**
- Email: `admin@applestore.com`
- Password: `admin123`

---

## 🎯 Quick Fixes

### Fix 1: Reset Admin User (Most Common)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on **"apple_store"** database
3. Click **"SQL"** tab
4. Paste this code:

```sql
-- Delete and recreate admin user
DELETE FROM users WHERE email = 'admin@applestore.com';

INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@applestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Verify it worked
SELECT id, name, email, role FROM users WHERE email = 'admin@applestore.com';
```

5. Click **"Go"**
6. You should see the admin user in results
7. Try logging in again

---

### Fix 2: Check Database Exists

1. Open phpMyAdmin
2. Look for **"apple_store"** in left sidebar
3. If it doesn't exist:
   - Click **"New"**
   - Name: `apple_store`
   - Collation: `utf8mb4_unicode_ci`
   - Click **"Create"**
4. Then import schema:
   - Click on the database
   - Click **"Import"**
   - Choose: `database/schema.sql`
   - Click **"Go"**

---

### Fix 3: Verify XAMPP is Running

1. Open **XAMPP Control Panel**
2. Check these are **green/running**:
   - ✅ Apache
   - ✅ MySQL
3. If not, click **"Start"** for each

---

### Fix 4: Check Configuration

Open: `c:\xampp\htdocs\joker&omda\apple-store\config\config.php`

Verify these lines:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for default XAMPP
define('DB_NAME', 'apple_store');
```

---

## 🔍 Diagnostic Checklist

Run through this checklist:

- [ ] XAMPP Apache is running (green)
- [ ] XAMPP MySQL is running (green)
- [ ] Database "apple_store" exists in phpMyAdmin
- [ ] Database has 8 tables (users, products, orders, etc.)
- [ ] Admin user exists in users table
- [ ] Config file has correct database credentials
- [ ] Using correct URL: `http://localhost/joker&omda/apple-store/admin/login.php`
- [ ] Typing email correctly: `admin@applestore.com` (no spaces)
- [ ] Typing password correctly: `admin123` (all lowercase)

---

## 🆘 Still Not Working?

### Try the Test Page

Visit: `http://localhost/joker&omda/apple-store/test-login.php`

This will show you EXACTLY what's wrong and give you the SQL to fix it.

### Check Apache Error Log

1. Open XAMPP Control Panel
2. Click **"Logs"** button next to Apache
3. Look for any PHP errors
4. Share the error message if you need help

### Verify Database Import

1. Open phpMyAdmin
2. Click on **"apple_store"** database
3. You should see these 8 tables:
   - categories
   - contacts
   - order_items
   - orders
   - products
   - reviews
   - settings
   - users
4. Click on **"users"** table
5. Click **"Browse"**
6. You should see at least 1 row (the admin)

---

## 📱 Alternative: Create Admin Manually

If nothing works, create admin user manually:

1. Open phpMyAdmin
2. Click on **"apple_store"** database
3. Click on **"users"** table
4. Click **"Insert"** tab
5. Fill in:
   - **name**: Admin
   - **email**: admin@applestore.com
   - **password**: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
   - **role**: admin
   - Leave other fields as default
6. Click **"Go"**

---

## 🎯 Test URLs

After fixing, test these:

| What | URL |
|------|-----|
| Test Login | http://localhost/joker&omda/apple-store/test-login.php |
| Admin Login | http://localhost/joker&omda/apple-store/admin/login.php |
| Admin Dashboard | http://localhost/joker&omda/apple-store/admin/ |
| Frontend | http://localhost/joker&omda/apple-store/ |
| phpMyAdmin | http://localhost/phpmyadmin |

---

## ✅ Success Indicators

You'll know it's working when:

1. **test-login.php** shows all green checkmarks ✅
2. **admin/login.php** accepts your credentials
3. You see the dashboard with:
   - Statistics cards
   - Charts
   - Recent orders table
   - Black & gold theme

---

## 🔐 Security Note

After you successfully login:

1. Change the admin password immediately
2. Delete these test files:
   - `test-login.php`
   - `database/fix-admin.sql`

---

## 📞 Common Mistakes

❌ **Wrong URL**: `http://localhost/apple-store/admin/`  
✅ **Correct**: `http://localhost/joker&omda/apple-store/admin/login.php`

❌ **Wrong Email**: `admin@apple-store.com` (has dash)  
✅ **Correct**: `admin@applestore.com` (no dash)

❌ **Wrong Password**: `Admin123` (capital A)  
✅ **Correct**: `admin123` (all lowercase)

❌ **Wrong Database**: `applestore` (no underscore)  
✅ **Correct**: `apple_store` (with underscore)

---

## 🎉 Next Steps After Login

Once you successfully login:

1. **Update Settings**
   - Go to Settings page
   - Update store name
   - Update WhatsApp number
   - Update email

2. **Add Products**
   - Go to Manage Products
   - Click "Add New"
   - Upload product images
   - Fill in details

3. **Test Features**
   - Browse products on frontend
   - Test language switching
   - Test order placement
   - Test WhatsApp integration

---

**Last Updated**: October 28, 2025  
**Status**: Active Troubleshooting

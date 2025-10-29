# ⚡ Quick Start - Local Testing

## 🎯 5-Minute Setup Guide

Follow these steps to test the Apple Store locally on your machine.

---

## ✅ Step 1: Start XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - ✅ Apache
   - ✅ MySQL

Wait until both show **green** status.

---

## ✅ Step 2: Create Database

### Option A: Using phpMyAdmin (Recommended)

1. Open browser and go to: `http://localhost/phpmyadmin`

2. Click **"New"** in the left sidebar

3. Database name: `apple_store`

4. Collation: `utf8mb4_unicode_ci`

5. Click **"Create"**

6. Click on the **"apple_store"** database you just created

7. Click **"Import"** tab at the top

8. Click **"Choose File"** button

9. Navigate to and select:
   ```
   c:\xampp\htdocs\joker&omda\apple-store\database\schema.sql
   ```

10. Scroll down and click **"Go"** button

11. Wait for success message: ✅ "Import has been successfully finished"

### Option B: Using Command Line

```bash
# Open Command Prompt
cd c:\xampp\mysql\bin

# Login to MySQL
mysql -u root -p

# Press Enter (no password by default)

# Run these commands:
CREATE DATABASE apple_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apple_store;
SOURCE c:/xampp/htdocs/joker&omda/apple-store/database/schema.sql;
EXIT;
```

---

## ✅ Step 3: Verify Installation

Open browser and test these URLs:

### Frontend (Customer Side)
```
http://localhost/joker&omda/apple-store/
```

**You should see:**
- Black & gold themed landing page
- Hero section
- Product categories
- Featured products

### Admin Panel
```
http://localhost/joker&omda/apple-store/admin/
```

**Login with:**
- Email: `admin@applestore.com`
- Password: `admin123`

**You should see:**
- Dashboard with statistics
- Charts
- Recent orders table

---

## ✅ Step 4: Test Key Features

### Test 1: Browse Products
1. Go to: `http://localhost/joker&omda/apple-store/shop.php`
2. You should see sample products (iPhone, iPad, MacBook, etc.)
3. Try the filters (category, price, search)

### Test 2: View Product Details
1. Click on any product
2. You should see:
   - Product image
   - Description
   - Price
   - "Order Now" button

### Test 3: Language Switching
1. Click the language toggle (EN ↔ العربية)
2. Page should switch to Arabic with RTL layout
3. Switch back to English

### Test 4: Admin Dashboard
1. Login to admin: `http://localhost/joker&omda/apple-store/admin/`
2. Check the dashboard statistics
3. View charts (should show data)

### Test 5: Add a Product
1. In admin, go to **Manage Products**
2. Click **"Add New"**
3. Fill in the form:
   - Name (English): Test Product
   - Name (Arabic): منتج تجريبي
   - Category: Select one
   - Price: 1000
   - Stock: 10
4. Click **Save**
5. Product should appear in the table

---

## 🐛 Troubleshooting

### Issue: "Database connection error"

**Solution:**
1. Make sure MySQL is running in XAMPP
2. Check database name is `apple_store`
3. Verify in `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'apple_store');
   ```

### Issue: "Page not found" or blank page

**Solution:**
1. Verify Apache is running
2. Check the URL is correct:
   ```
   http://localhost/joker&omda/apple-store/
   ```
3. Make sure files are in the correct directory:
   ```
   c:\xampp\htdocs\joker&omda\apple-store\
   ```

### Issue: Images not showing

**Solution:**
1. Right-click on folder: `c:\xampp\htdocs\joker&omda\apple-store\assets\images\products\`
2. Properties → Security → Edit
3. Give "Full Control" to "Users"
4. Click Apply

### Issue: "Cannot login to admin"

**Solution:**
1. Make sure database was imported correctly
2. Check if users table has data:
   - Go to phpMyAdmin
   - Select `apple_store` database
   - Click on `users` table
   - You should see an admin user
3. Try credentials again:
   - Email: `admin@applestore.com`
   - Password: `admin123`

### Issue: Arabic text shows as "???"

**Solution:**
1. Database charset issue
2. Re-import database with UTF8MB4
3. Or run this in phpMyAdmin SQL tab:
   ```sql
   ALTER DATABASE apple_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## 📱 Test on Mobile

1. Find your computer's IP address:
   ```
   ipconfig
   ```
   Look for "IPv4 Address" (e.g., 192.168.1.100)

2. On your phone (connected to same WiFi):
   ```
   http://YOUR_IP/joker&omda/apple-store/
   ```

3. Test responsive design and mobile features

---

## ✅ Quick Test Checklist

- [ ] XAMPP Apache running
- [ ] XAMPP MySQL running
- [ ] Database created
- [ ] Schema imported
- [ ] Frontend loads
- [ ] Admin login works
- [ ] Products display
- [ ] Language switch works
- [ ] Filters work
- [ ] Admin dashboard shows data
- [ ] Can add product
- [ ] Can view orders
- [ ] WhatsApp button appears

---

## 🎉 Success!

If all tests pass, your Apple Store is working perfectly!

### Next Steps:

1. **Customize Settings**
   - Admin → Settings
   - Update store name
   - Update WhatsApp number
   - Update email

2. **Add Real Products**
   - Admin → Manage Products
   - Add your actual products
   - Upload product images

3. **Test Order Flow**
   - Browse products
   - Click "Order Now"
   - Fill form
   - Test WhatsApp redirect

4. **Explore Admin Features**
   - View orders
   - Manage categories
   - Moderate reviews
   - Check contacts

---

## 📞 Need Help?

- **Documentation**: Check README.md
- **Quick Reference**: Check QUICK_REFERENCE.md
- **Installation**: Check INSTALLATION.md
- **All Docs**: Check INDEX.md

---

## 🔗 Quick Links

| Page | URL |
|------|-----|
| Home | http://localhost/joker&omda/apple-store/ |
| Shop | http://localhost/joker&omda/apple-store/shop.php |
| About | http://localhost/joker&omda/apple-store/about.php |
| Contact | http://localhost/joker&omda/apple-store/contact.php |
| Admin | http://localhost/joker&omda/apple-store/admin/ |
| phpMyAdmin | http://localhost/phpmyadmin |

---

**Happy Testing! 🚀**

Last Updated: October 28, 2025

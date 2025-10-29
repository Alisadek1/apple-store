# 🔧 Fix Admin Login Issue

## Problem: "Invalid credentials" when trying to login

---

## ✅ Solution 1: Run Fix Script (Easiest)

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`

2. Click on **"apple_store"** database in the left sidebar

3. Click **"SQL"** tab at the top

4. Copy and paste this code:

```sql
-- Delete existing admin if any
DELETE FROM users WHERE email = 'admin@applestore.com';

-- Insert admin user with correct password
-- Password: admin123
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@applestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Verify
SELECT id, name, email, role FROM users WHERE email = 'admin@applestore.com';
```

5. Click **"Go"** button

6. You should see the admin user in the results

7. Now try logging in again:
   - Email: `admin@applestore.com`
   - Password: `admin123`

---

## ✅ Solution 2: Import Fix Script

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`

2. Click on **"apple_store"** database

3. Click **"Import"** tab

4. Click **"Choose File"**

5. Select: `c:\xampp\htdocs\joker&omda\apple-store\database\fix-admin.sql`

6. Click **"Go"**

7. Try logging in again

---

## ✅ Solution 3: Check Database

### Verify database exists:

1. Open phpMyAdmin
2. Look for **"apple_store"** in the left sidebar
3. If it doesn't exist, create it:
   - Click "New"
   - Name: `apple_store`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

### Verify tables exist:

1. Click on **"apple_store"** database
2. You should see 8 tables:
   - categories
   - contacts
   - order_items
   - orders
   - products
   - reviews
   - settings
   - users

3. If tables don't exist, import the schema:
   - Click "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"

### Verify admin user exists:

1. Click on **"apple_store"** database
2. Click on **"users"** table
3. Click **"Browse"** tab
4. You should see an admin user with:
   - email: `admin@applestore.com`
   - role: `admin`

5. If no admin user, run Solution 1 above

---

## ✅ Solution 4: Check Configuration

Open: `c:\xampp\htdocs\joker&omda\apple-store\config\config.php`

Verify these settings:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for default XAMPP
define('DB_NAME', 'apple_store');
```

If different, update them to match above.

---

## ✅ Solution 5: Test Database Connection

Create a test file to verify connection:

1. Create: `c:\xampp\htdocs\joker&omda\apple-store\test-db.php`

2. Add this code:

```php
<?php
require_once 'config/database.php';

try {
    $db = getDB();
    echo "✅ Database connected successfully!<br><br>";
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@applestore.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found!<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Role: " . $admin['role'] . "<br>";
        echo "Password hash: " . substr($admin['password'], 0, 20) . "...<br>";
    } else {
        echo "❌ Admin user NOT found!<br>";
        echo "Run the fix script in phpMyAdmin.";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
```

3. Visit: `http://localhost/joker&omda/apple-store/test-db.php`

4. Check the results

---

## ✅ Solution 6: Clear Browser Cache

Sometimes browser cache causes issues:

1. Press `Ctrl + Shift + Delete`
2. Select "Cached images and files"
3. Click "Clear data"
4. Try logging in again

---

## ✅ Solution 7: Check Login Code

If nothing works, let's verify the login is working:

Open: `c:\xampp\htdocs\joker&omda\apple-store\auth\login.php`

Add this debug code after line 10:

```php
// Debug mode - remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Try logging in and check for any error messages.

---

## 🎯 Quick Test

After applying any solution, test with:

**URL**: `http://localhost/joker&omda/apple-store/admin/`

**Credentials**:
- Email: `admin@applestore.com`
- Password: `admin123`

**Expected**: Dashboard with statistics and charts

---

## 🆘 Still Not Working?

### Check these:

1. **MySQL running?**
   - Open XAMPP Control Panel
   - MySQL should show green "Running"

2. **Apache running?**
   - Apache should show green "Running"

3. **Correct URL?**
   - Must be: `http://localhost/joker&omda/apple-store/admin/`
   - Not: `http://localhost/apple-store/admin/`

4. **Database imported?**
   - Check phpMyAdmin
   - Database "apple_store" should exist
   - Should have 8 tables

5. **Typing correctly?**
   - Email: `admin@applestore.com` (no spaces)
   - Password: `admin123` (lowercase, no spaces)

---

## 📞 Common Mistakes

❌ **Wrong email**: `admin@apple-store.com` (has dash)  
✅ **Correct**: `admin@applestore.com` (no dash)

❌ **Wrong password**: `Admin123` (capital A)  
✅ **Correct**: `admin123` (all lowercase)

❌ **Wrong database**: `applestore` (no underscore)  
✅ **Correct**: `apple_store` (with underscore)

---

## ✅ Success Checklist

After fixing, you should be able to:

- [ ] Login to admin panel
- [ ] See dashboard with statistics
- [ ] View charts
- [ ] Navigate to Products page
- [ ] Navigate to Orders page
- [ ] Navigate to Settings page

---

**If you've tried all solutions and still can't login, there may be a deeper issue. Check the Apache error logs in XAMPP.**

Last Updated: October 28, 2025

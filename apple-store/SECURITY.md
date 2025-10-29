# Security Policy

## 🔒 Security Overview

The Apple Store E-Commerce system implements multiple layers of security to protect user data and prevent common web vulnerabilities.

---

## ✅ Implemented Security Measures

### 1. Authentication & Authorization

#### Password Security
- **Hashing Algorithm**: bcrypt (PASSWORD_DEFAULT)
- **Salt**: Automatically generated per password
- **Cost Factor**: Default (currently 10)

```php
// Password hashing
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Password verification
password_verify($input_password, $stored_hash);
```

#### Session Management
- PHP sessions with secure configuration
- Session data stored server-side
- User ID and role stored in session
- Automatic session timeout

#### Role-Based Access Control
- **Admin**: Full access to admin panel
- **User**: Customer access to frontend
- **Guest**: Limited access, can place orders

### 2. SQL Injection Prevention

All database queries use **PDO prepared statements**:

```php
// Safe query
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

**Never** use string concatenation for SQL queries.

### 3. Cross-Site Scripting (XSS) Prevention

All user input is sanitized before output:

```php
// Sanitization function
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Usage
echo sanitize($user_input);
```

### 4. Cross-Site Request Forgery (CSRF)

CSRF tokens are generated (implementation in progress):

```php
// Generate token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}
```

### 5. File Upload Security

File uploads are validated:

```php
// Validation checks
- File extension whitelist
- File size limit (5MB)
- MIME type validation (recommended)
- Unique filename generation
- Secure storage location
```

**Allowed Extensions**: jpg, jpeg, png, gif, webp

### 6. Input Validation

All form inputs are validated:
- Required field checks
- Email format validation
- Data type validation
- Length restrictions
- Whitelist validation for enums

### 7. HTTP Security Headers

Configured in `.htaccess`:

```apache
# Prevent clickjacking
Header set X-Frame-Options "SAMEORIGIN"

# XSS Protection
Header set X-XSS-Protection "1; mode=block"

# Prevent MIME sniffing
Header set X-Content-Type-Options "nosniff"

# Referrer Policy
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### 8. Directory Protection

- Directory browsing disabled
- Config files protected
- Hidden files blocked
- Sensitive directories restricted

---

## ⚠️ Known Security Considerations

### 1. CSRF Protection (In Progress)
**Status**: Token generation implemented, form integration pending  
**Priority**: High  
**Action Required**: Add CSRF tokens to all forms

### 2. Rate Limiting (Not Implemented)
**Status**: Not implemented  
**Priority**: High  
**Risk**: Brute force attacks on login  
**Recommendation**: Implement login attempt tracking

### 3. Session Fixation (Needs Improvement)
**Status**: Basic session management  
**Priority**: Medium  
**Action Required**: Add session regeneration after login

```php
// Add after successful login
session_regenerate_id(true);
```

### 4. File Upload MIME Validation
**Status**: Extension-based only  
**Priority**: Medium  
**Action Required**: Add MIME type verification

```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
```

### 5. Email Validation
**Status**: Basic filter_var validation  
**Priority**: Low  
**Recommendation**: Consider DNS MX record validation

---

## 🚨 Reporting Security Vulnerabilities

If you discover a security vulnerability, please report it responsibly:

### DO NOT:
- ❌ Open a public issue
- ❌ Disclose publicly before fix
- ❌ Exploit the vulnerability

### DO:
- ✅ Email: security@applestore.com
- ✅ Provide detailed description
- ✅ Include steps to reproduce
- ✅ Wait for acknowledgment

### Response Timeline:
- **24 hours**: Initial acknowledgment
- **7 days**: Preliminary assessment
- **30 days**: Fix implementation (if valid)

---

## 🔐 Security Best Practices

### For Administrators

1. **Change Default Credentials**
   ```sql
   UPDATE users 
   SET password = '$2y$10$NEW_HASH' 
   WHERE email = 'admin@applestore.com';
   ```

2. **Use Strong Passwords**
   - Minimum 12 characters
   - Mix of uppercase, lowercase, numbers, symbols
   - No dictionary words
   - Unique per account

3. **Keep Software Updated**
   - PHP version
   - MySQL version
   - Dependencies
   - Server software

4. **Regular Backups**
   - Daily database backups
   - Weekly full backups
   - Store backups securely
   - Test restoration process

5. **Monitor Logs**
   - Check error logs daily
   - Review access logs
   - Monitor failed login attempts
   - Track suspicious activity

6. **Secure File Permissions**
   ```bash
   # Files
   find . -type f -exec chmod 644 {} \;
   
   # Directories
   find . -type d -exec chmod 755 {} \;
   
   # Upload directory
   chmod 777 assets/images/products/
   ```

7. **Enable HTTPS**
   - Install SSL certificate
   - Force HTTPS redirect
   - Update SITE_URL in config

### For Developers

1. **Never Commit Sensitive Data**
   - No passwords in code
   - No API keys in repository
   - Use environment variables
   - Check .gitignore

2. **Validate All Input**
   - Server-side validation
   - Client-side as enhancement only
   - Whitelist over blacklist
   - Sanitize output

3. **Use Prepared Statements**
   - Always use PDO
   - Never concatenate SQL
   - Bind parameters properly

4. **Implement Proper Error Handling**
   - Don't expose stack traces
   - Log errors securely
   - Show generic messages to users

5. **Keep Dependencies Updated**
   - Check for security updates
   - Review changelogs
   - Test before updating

---

## 🛡️ Security Checklist

### Before Production Deployment

- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable HTTPS
- [ ] Configure error logging
- [ ] Disable error display
- [ ] Set secure file permissions
- [ ] Review .htaccess security headers
- [ ] Test all forms for XSS
- [ ] Test SQL injection prevention
- [ ] Verify file upload restrictions
- [ ] Enable CSRF protection
- [ ] Implement rate limiting
- [ ] Configure backup strategy
- [ ] Set up monitoring
- [ ] Review all user inputs
- [ ] Test authentication flow
- [ ] Verify session security
- [ ] Check for exposed config files
- [ ] Test admin access restrictions
- [ ] Review database permissions

### Regular Security Audits

**Monthly:**
- [ ] Review access logs
- [ ] Check failed login attempts
- [ ] Update dependencies
- [ ] Test backup restoration
- [ ] Review user permissions

**Quarterly:**
- [ ] Security penetration testing
- [ ] Code review
- [ ] Update security policies
- [ ] Train staff on security

**Annually:**
- [ ] Full security audit
- [ ] Update disaster recovery plan
- [ ] Review compliance requirements
- [ ] Update security documentation

---

## 📚 Security Resources

### OWASP Top 10 (2021)
1. Broken Access Control
2. Cryptographic Failures
3. Injection
4. Insecure Design
5. Security Misconfiguration
6. Vulnerable Components
7. Authentication Failures
8. Software & Data Integrity
9. Logging & Monitoring Failures
10. Server-Side Request Forgery

### Recommended Reading
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [MySQL Security Guidelines](https://dev.mysql.com/doc/refman/8.0/en/security.html)

---

## 🔄 Security Update History

### Version 1.0.0 (2025-10-28)
- Initial security implementation
- Password hashing with bcrypt
- SQL injection prevention
- XSS protection
- Basic session management
- File upload validation
- Security headers

---

## 📞 Security Contact

**Email**: security@applestore.com  
**Response Time**: Within 24 hours  
**PGP Key**: Available upon request

---

## ⚖️ Responsible Disclosure

We appreciate security researchers who report vulnerabilities responsibly. We commit to:

1. Acknowledging receipt within 24 hours
2. Providing regular updates on progress
3. Crediting researchers (if desired)
4. Not pursuing legal action for good-faith research

---

**Last Updated**: October 28, 2025  
**Version**: 1.0.0  
**Next Review**: January 28, 2026

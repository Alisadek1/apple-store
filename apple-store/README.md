# 🍎 Apple Store - Luxury E-Commerce Website

A premium Apple Store e-commerce website built with PHP, MySQL, and Bootstrap featuring a luxury black & gold theme.

## ✨ Features

### 🎬 **Hero Section**
- **Background video** with auto-replay functionality
- **Logo integration** behind store title
- **Smooth animations** with AOS (Animate On Scroll)
- **Responsive design** for all devices

### 🏷️ **Branding**
- **Consistent logo** throughout navbar, footer, and hero
- **JPEG logo support** with optimized rendering
- **Hover effects** and smooth transitions
- **Gold & black luxury theme**

### 🔐 **Security Features**
- **Admin-only access** for diagnostic tools
- **CSRF protection** for all operations
- **Rate limiting** for security testing
- **Session validation** with integrity checks
- **Comprehensive audit logging**

### 🛠️ **Admin Tools**
- **Password verification diagnostics**
- **Hash analysis and repair tools**
- **Database integrity checking**
- **Authentication monitoring**
- **Security audit dashboard**

### 🌐 **Multi-language Support**
- **English & Arabic** language support
- **RTL layout** for Arabic users
- **Dynamic language switching**
- **Localized content**

## 🚀 **Quick Start**

### **Requirements**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### **Installation**
1. Clone the repository
```bash
git clone https://github.com/yourusername/apple-store.git
cd apple-store
```

2. Configure database settings in `config/database.php`

3. Import database schema (if available)

4. Set up web server to point to the project directory

5. Access the website in your browser

## 📁 **Project Structure**

```
apple-store/
├── admin/                  # Admin panel and tools
│   ├── diagnostics/       # Password verification tools
│   ├── includes/          # Admin-specific includes
│   └── assets/           # Admin CSS/JS
├── assets/               # Public assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Logo and images
│   └── videos/          # Background videos
├── auth/                # Authentication system
├── config/              # Configuration files
├── includes/            # Core includes and functions
├── docs/                # Documentation
└── tests/               # Test files
```

## 🎨 **Design Features**

### **Color Scheme**
- **Primary:** Black (#000000)
- **Accent:** Gold (#D4AF37)
- **Text:** Light Gray (#CCCCCC)
- **Background:** Dark Gray (#1A1A1A)

### **Typography**
- **Font Family:** Cairo (Arabic & English support)
- **Weights:** 300, 400, 600, 700, 900

### **Animations**
- **AOS (Animate On Scroll)** for smooth reveals
- **Custom CSS animations** for logo and video
- **Hover effects** throughout the interface

## 🔧 **Configuration**

### **Media Files**
- **Logo:** Place your logo as `assets/images/logo.jpeg`
- **Video:** Place your showcase video as `assets/videos/apple-showcase.mp4`

### **Database**
Update `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

## 🛡️ **Security Features**

### **Admin Security**
- Multi-layer access validation
- Session integrity checking
- CSRF token protection
- Rate limiting on sensitive operations

### **Password Security**
- Comprehensive hash validation
- Corruption detection and repair
- Secure password verification
- Audit logging for all operations

## 📱 **Responsive Design**

- **Mobile-first** approach
- **Breakpoints:** 576px, 768px, 992px, 1200px
- **Optimized performance** on all devices
- **Touch-friendly** interface

## 🧪 **Testing**

### **Available Test Files**
- `test-homepage-enhancements.php` - Homepage feature testing
- `test-logo-visibility.html` - Logo visibility testing
- `check-media-files.php` - Media file validation
- `test-security-validation.php` - Security system testing

### **Running Tests**
Open test files in your browser to verify functionality.

## 📚 **Documentation**

Comprehensive documentation available in the `docs/` directory:
- **HOMEPAGE_ENHANCEMENTS.md** - Homepage feature guide
- **SECURITY_IMPLEMENTATION.md** - Security system documentation
- **VIDEO_LOGO_UPDATE.md** - Media integration guide

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 **Acknowledgments**

- **Bootstrap** for responsive framework
- **Font Awesome** for icons
- **AOS** for scroll animations
- **Cairo Font** for typography

## 📞 **Support**

For support and questions, please open an issue in the GitHub repository.

---

**Built with ❤️ for luxury Apple Store experience**
# 🚀 Getting Started with FitZone User Management System

Welcome to FitZone Fitness Center! This guide will help you get the user management and admin functionality up and running quickly.

## 📋 Quick Setup Checklist

### ✅ Prerequisites
- [ ] Web server (Apache/Nginx) running
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ or MariaDB 10.3+ running
- [ ] Required PHP extensions: `pdo`, `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`

### ✅ Installation Steps

#### 1. **Download & Extract**
```bash
# Extract FitZone files to your web directory
cd /var/www/html/
# or wherever your web files are located
```

#### 2. **Database Setup**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE fitzone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p fitzone_db < database/fitzone.sql

# Verify installation
mysql -u root -p -e "USE fitzone_db; SHOW TABLES;"
```

#### 3. **Set Permissions**
```bash
# Set directory permissions
chmod -R 755 .
chmod -R 775 uploads/
chown -R www-data:www-data uploads/ # For Apache on Ubuntu/Debian
```

#### 4. **Quick Setup Check**
Visit: `http://your-domain/setup.php`

This will automatically check:
- ✅ PHP requirements
- ✅ File permissions
- ✅ Database connection
- ✅ System components

## 🏃‍♂️ Quick Start (5 Minutes)

### Method 1: Web Setup
1. **Visit setup page:** `http://your-domain/setup.php`
2. **Follow the checklist** - fix any red ❌ items
3. **Click "Run Full System Test"** to verify everything works
4. **Open Admin Dashboard** and start managing users!

### Method 2: Command Line Setup
```bash
# Navigate to FitZone directory
cd /path/to/FitZone-Fitness-Center/

# Test the system
php test-user-management.php

# If all tests pass, you're ready to go!
```

## 👨‍💼 Admin Dashboard Access

### Default Login
- **URL:** `http://your-domain/admin-dashboard.html`
- **Email:** `admin@fitzonecenter.com`
- **Password:** `admin123`

### ⚠️ SECURITY: Change Default Password Immediately!

## 🎯 What You Can Do Now

### User Management
- ✅ **Create Users** - Add members, trainers, staff, admins
- ✅ **Edit Profiles** - Update user information and roles
- ✅ **Reset Passwords** - Help users who forgot passwords
- ✅ **Manage Status** - Activate/deactivate user accounts
- ✅ **View Statistics** - See user growth and activity

### Permission System
- ✅ **Role-based Access** - 4 user roles with different permissions
- ✅ **25+ Permissions** - Granular control over system access
- ✅ **Security Logging** - Track all admin actions

### Security Features
- ✅ **Password Validation** - Strong password requirements
- ✅ **Rate Limiting** - Prevent brute force attacks
- ✅ **Input Sanitization** - Protect against XSS/injection
- ✅ **Audit Trail** - Complete activity logging

## 🧪 Testing Your Installation

### Automated Testing
```bash
# Run comprehensive test suite
php test-user-management.php

# Or visit in browser:
http://your-domain/test-user-management.php
```

This tests:
- Database connectivity
- User CRUD operations
- Permission system
- Security features
- Data validation

### Manual Testing
1. **Visit admin dashboard** → Should load without errors
2. **View users list** → Should show existing users
3. **Create test user** → Should create successfully
4. **Edit user** → Should save changes
5. **Delete test user** → Should remove from system

## 🔧 Common Issues & Solutions

### ❌ "Database connection failed"
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in php/config/database.php
# Default: localhost, fitzone_db, root, (no password)
```

### ❌ "Permission denied" errors
```bash
# Fix file permissions
sudo chown -R www-data:www-data /path/to/fitzone/
chmod -R 755 /path/to/fitzone/
chmod -R 775 /path/to/fitzone/uploads/
```

### ❌ "PHP extension missing"
```bash
# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php-mysql php-curl php-mbstring php-json

# Restart web server
sudo systemctl restart apache2
```

### ❌ Admin dashboard shows mock data
The dashboard uses mock data for demonstration. To connect to real data:
1. Update the API endpoints in `js/admin-dashboard.js`
2. Ensure `php/admin/users.php` is accessible
3. Check browser console for any JavaScript errors

## 🎉 You're Ready!

Once setup is complete:

1. **🗑️ Delete setup files** - Remove `setup.php` and `test-user-management.php` for security
2. **🔒 Change admin password** - Update the default admin credentials
3. **👥 Add your team** - Create accounts for staff and trainers
4. **📊 Explore features** - Check out the user management capabilities
5. **📚 Read documentation** - See `documentation/` folder for detailed guides

## 📞 Need Help?

- **📖 Full Installation Guide:** `documentation/INSTALLATION.md`
- **🔧 Troubleshooting:** Check the installation guide's troubleshooting section
- **🧪 System Test:** Run `test-user-management.php` to diagnose issues

---

**🎊 Welcome to FitZone!** Your user management system is ready to help you build an amazing fitness community.

### Quick Links
- 🏠 [Main Website](index.html)
- 👨‍💼 [Admin Dashboard](admin-dashboard.html)
- 🧪 [System Test](test-user-management.php)
- ⚙️ [Setup Check](setup.php)

**Remember:** Delete `setup.php` after installation for security! 🔒
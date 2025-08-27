# FitZone Fitness Center - Installation Guide

This guide will walk you through the complete installation process for the FitZone Fitness Center web application.

## 📋 Prerequisites

Before installing FitZone, ensure your system meets the following requirements:

### System Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Disk Space**: Minimum 500MB free space
- **Memory**: 128MB PHP memory limit (256MB recommended)

### Required PHP Extensions
```
- pdo
- pdo_mysql
- curl
- json
- mbstring
- openssl
- gd (for image processing)
- zip
- xml
```

Check your PHP configuration:
```bash
php -m | grep -E "pdo|mysql|curl|json|mbstring|openssl|gd"
```

## 🛠️ Installation Methods

### Method 1: Manual Installation

#### Step 1: Download and Extract

1. Download the FitZone source code
2. Extract to your web server directory:

```bash
# For Apache (Ubuntu/Debian)
sudo unzip fitzone.zip -d /var/www/html/

# For Apache (CentOS/RHEL)
sudo unzip fitzone.zip -d /var/www/html/

# For custom directory
unzip fitzone.zip -d /path/to/your/webroot/
```

#### Step 2: Set File Permissions

```bash
cd /var/www/html/FitZone-Fitness-Center

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set writable directories
chmod 755 uploads/
chmod 755 uploads/profile-pics/
chmod 755 images/
chown -R www-data:www-data uploads/
```

#### Step 3: Configure Web Server

**For Apache:**

Create virtual host file `/etc/apache2/sites-available/fitzone.conf`:

```apache
<VirtualHost *:80>
    ServerName fitzone.local
    DocumentRoot /var/www/html/FitZone-Fitness-Center
    
    <Directory /var/www/html/FitZone-Fitness-Center>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # PHP configuration
    php_admin_value upload_max_filesize 10M
    php_admin_value post_max_size 10M
    php_admin_value memory_limit 256M
    
    ErrorLog ${APACHE_LOG_DIR}/fitzone_error.log
    CustomLog ${APACHE_LOG_DIR}/fitzone_access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite fitzone.conf
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

**For Nginx:**

Create configuration file `/etc/nginx/sites-available/fitzone`:

```nginx
server {
    listen 80;
    server_name fitzone.local;
    root /var/www/html/FitZone-Fitness-Center;
    index index.html index.php;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(sql|md)$ {
        deny all;
    }
    
    # Static file caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/fitzone /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Method 2: Docker Installation

#### Step 1: Create Docker Compose File

Create `docker-compose.yml`:

```yaml
version: '3.8'
services:
  web:
    image: php:7.4-apache
    container_name: fitzone_web
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=fitzone_db
      - DB_USERNAME=fitzone_user
      - DB_PASSWORD=secure_password

  db:
    image: mysql:8.0
    container_name: fitzone_db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: fitzone_db
      MYSQL_USER: fitzone_user
      MYSQL_PASSWORD: secure_password
    volumes:
      - db_data:/var/lib/mysql
      - ./database/fitzone.sql:/docker-entrypoint-initdb.d/fitzone.sql
    ports:
      - "3306:3306"

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: fitzone_pma
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: root_password
    ports:
      - "8080:80"
    depends_on:
      - db

volumes:
  db_data:
```

#### Step 2: Start Docker Services

```bash
docker-compose up -d
```

## 🗄️ Database Setup

### Step 1: Create Database

Connect to MySQL:
```bash
mysql -u root -p
```

Create database and user:
```sql
CREATE DATABASE fitzone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'fitzone_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON fitzone_db.* TO 'fitzone_user'@'localhost';
FLUSH PRIVILEGES;

USE fitzone_db;
```

### Step 2: Import Database Schema

```bash
mysql -u fitzone_user -p fitzone_db < database/fitzone.sql
```

### Step 3: Verify Installation

```sql
USE fitzone_db;
SHOW TABLES;
SELECT COUNT(*) FROM users;
```

You should see several tables created and at least one admin user.

## ⚙️ Configuration

### Step 1: Environment Configuration

Create `.env` file in the root directory:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=fitzone_db
DB_USERNAME=fitzone_user
DB_PASSWORD=secure_password
DB_PORT=3306

# Application Environment
ENVIRONMENT=production
TIMEZONE=America/New_York
SITE_URL=https://fitzone.local

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@fitzonecenter.com
MAIL_FROM_NAME="FitZone Fitness Center"

# Security Settings
SESSION_TIMEOUT=3600
CSRF_TOKEN_EXPIRE=7200
MAX_LOGIN_ATTEMPTS=5
RATE_LIMIT_WINDOW=300

# File Upload Settings
MAX_FILE_SIZE=5242880
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif,webp
```

### Step 2: PHP Configuration

Create or modify `php/config/config.php`:

```php
<?php
// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'fitzone_db');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// Application configuration
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'UTC');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>
```

### Step 3: Security Configuration

Create `.htaccess` file in root directory:

```apache
# FitZone Fitness Center - Security Configuration

# Enable rewrite engine
RewriteEngine On

# Prevent access to sensitive files
<FilesMatch "\.(sql|md|env|log|ini)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent access to directories
Options -Indexes

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# PHP Security
php_flag session.cookie_httponly on
php_flag session.cookie_secure on
php_flag session.use_strict_mode on

# File upload restrictions
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    <Directory "uploads">
        Order Allow,Deny
        Deny from all
    </Directory>
</FilesMatch>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>
```

## 🧪 Testing Installation

### Step 1: Basic Functionality Test

1. Visit your FitZone URL (e.g., http://fitzone.local)
2. Navigate through the main pages
3. Test the registration form
4. Try logging in with the admin account

### Step 2: User Management System Test

**Run the comprehensive test suite:**

```bash
# Navigate to your FitZone directory
cd /path/to/FitZone-Fitness-Center

# Run the complete user management test
php test-user-management.php
```

**Or test via web browser:**
Visit: `http://your-domain/test-user-management.php`

This will test:
- ✅ Database connectivity
- ✅ User management operations
- ✅ Permission system
- ✅ Security features
- ✅ Validation systems

### Step 3: Database Connection Test

Create `test-db.php` (remove after testing):

```php
<?php
require_once 'php/config/database.php';

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful\n";
    
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users");
    echo "✅ Found " . $result['count'] . " users in database\n";
    
    echo "✅ Installation test passed!\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>
```

### Step 4: Admin Dashboard Test

1. Visit the admin dashboard: `http://your-domain/admin-dashboard.html`
2. Test user management features:
   - View users list
   - Create new user
   - Edit user details
   - Reset user password
   - Test permissions

**Note:** For full functionality, ensure the admin API endpoints are accessible at `/php/admin/users.php`

### Step 5: Email Configuration Test

Test email sending (optional):

```php
<?php
require_once 'php/includes/functions.php';

$testEmail = 'test@example.com';
$subject = 'FitZone Installation Test';
$body = 'This is a test email from FitZone installation.';

if (sendEmail($testEmail, $subject, $body)) {
    echo "✅ Email configuration working\n";
} else {
    echo "❌ Email configuration needs attention\n";
}
?>
```

## 🔧 Post-Installation Setup

### Step 1: Admin Account Setup

1. Log in to admin panel: `/admin/`
2. Default credentials: `admin@fitzonecenter.com` / `admin123`
3. **Immediately change the default password**
4. Update admin profile information

### Step 2: System Settings

Configure system settings in admin panel:
- Site name and description
- Contact information
- Operating hours
- Membership pricing
- Email templates

### Step 3: Content Setup

1. Add trainer profiles
2. Create fitness classes
3. Upload class and trainer images
4. Write initial blog posts
5. Configure newsletter settings

### Step 4: Security Hardening

1. Remove test files
2. Set up SSL certificate (recommended)
3. Configure firewall rules
4. Set up backup procedures
5. Enable monitoring and logging

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error:**
```
- Check database credentials in configuration
- Verify MySQL service is running
- Check database user permissions
```

**File Permission Issues:**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/FitZone-Fitness-Center
sudo chmod -R 755 /var/www/html/FitZone-Fitness-Center
sudo chmod -R 775 uploads/
```

**PHP Extension Missing:**
```bash
# Install required extensions (Ubuntu/Debian)
sudo apt-get install php7.4-mysql php7.4-curl php7.4-gd php7.4-mbstring

# Restart web server
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

**Email Not Working:**
- Check SMTP credentials
- Verify firewall allows outbound SMTP
- Test with a different email provider
- Check spam folder

### Log Files

Check these log files for errors:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: `/var/log/php/errors.log`
- MySQL: `/var/log/mysql/error.log`

## 📞 Support

If you encounter issues during installation:

1. Check the troubleshooting section above
2. Review the error logs
3. Verify all prerequisites are met
4. Contact support with detailed error information

---

**Congratulations!** 🎉 Your FitZone Fitness Center installation is complete. Visit your site and start building your fitness community!
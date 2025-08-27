# FitZone Fitness Center - Web Application

A comprehensive fitness center management system built with PHP, MySQL, HTML, CSS, and JavaScript.

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Usage](#usage)
- [Admin Panel](#admin-panel)
- [API Endpoints](#api-endpoints)
- [Security Features](#security-features)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Project Overview

FitZone Fitness Center is a modern web application designed to manage all aspects of a fitness center, including member registration, class scheduling, trainer management, membership plans, and more. The system provides both user-facing features and administrative tools.

### Key Objectives

- Streamline gym operations and member management
- Provide an intuitive user experience for members
- Enable efficient class and trainer scheduling
- Implement secure user authentication and data protection
- Offer comprehensive reporting and analytics

## ✨ Features

### Public Features
- **Responsive Homepage** with gym information and call-to-action sections
- **Class Directory** with search and filtering capabilities
- **Trainer Profiles** showcasing expertise and availability
- **Membership Plans** with detailed pricing and features
- **Fitness Blog** with articles and tips
- **Contact Form** with inquiry management
- **Newsletter Subscription** with email confirmation

### Member Features
- **User Registration** with email verification
- **Member Dashboard** with personalized statistics
- **Class Booking** system with waitlist management
- **Personal Training** appointment scheduling
- **Workout Tracking** with progress monitoring
- **Profile Management** with customizable settings
- **Payment History** and membership management

### Admin Features
- **User Management** with role-based access control
- **Class Management** with scheduling tools
- **Trainer Administration** with profile management
- **Content Management** for blog and announcements
- **Contact Query** handling and assignment
- **Analytics Dashboard** with key metrics
- **System Settings** and configuration

## 🛠️ Technology Stack

### Frontend
- **HTML5** - Semantic markup and structure
- **CSS3** - Modern styling with Flexbox and Grid
- **JavaScript (ES6+)** - Interactive functionality and AJAX
- **Responsive Design** - Mobile-first approach

### Backend
- **PHP 7.4+** - Server-side processing
- **MySQL 5.7+** - Database management
- **PDO** - Database abstraction layer
- **Session Management** - User authentication

### Security
- **Password Hashing** - bcrypt encryption
- **CSRF Protection** - Token-based validation
- **Rate Limiting** - Prevent abuse and spam
- **Input Sanitization** - XSS and SQL injection prevention
- **Email Verification** - Account confirmation

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependencies)

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/fitzone-fitness-center.git
cd fitzone-fitness-center
```

### Step 2: Configure Web Server

Point your web server document root to the project directory. For Apache, create a virtual host:

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/fitzone-fitness-center
    ServerName fitzone.local
    
    <Directory /path/to/fitzone-fitness-center>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 3: Set Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/profile-pics/
chmod 644 php/config/database.php
```

## 🗄️ Database Setup

### Step 1: Create Database

```sql
CREATE DATABASE fitzone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Import Schema

```bash
mysql -u username -p fitzone_db < database/fitzone.sql
```

### Step 3: Create Database User (Optional)

```sql
CREATE USER 'fitzone_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON fitzone_db.* TO 'fitzone_user'@'localhost';
FLUSH PRIVILEGES;
```

## ⚙️ Configuration

### Environment Variables

Create a `.env` file in the root directory:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=fitzone_db
DB_USERNAME=fitzone_user
DB_PASSWORD=secure_password
DB_PORT=3306

# Application Settings
ENVIRONMENT=production
TIMEZONE=America/New_York
SITE_URL=https://fitzone.local

# Email Settings
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Security Settings
SESSION_TIMEOUT=3600
MAX_LOGIN_ATTEMPTS=5
```

### Database Configuration

Update `php/config/database.php` with your database credentials if not using environment variables.

## 📁 Project Structure

```
FitZone-Fitness-Center/
├── 📄 index.html                  # Homepage
├── 📄 about.html                  # About page
├── 📄 classes.html                # Classes listing
├── 📄 trainers.html               # Trainers directory
├── 📄 membership.html             # Membership plans
├── 📄 blog.html                   # Blog/articles
├── 📄 contact.html                # Contact form
├── 📄 login.html                  # User login
├── 📄 register.html               # User registration
├── 📄 dashboard.html              # Member dashboard
├── 📁 css/
│   ├── 📄 style.css              # Main stylesheet
│   ├── 📄 responsive.css         # Mobile responsiveness
│   └── 📄 dashboard.css          # Dashboard styles
├── 📁 js/
│   ├── 📄 main.js                # Core functionality
│   ├── 📄 form-validation.js     # Form validation
│   ├── 📄 dashboard.js           # Dashboard interactions
│   └── 📄 search.js              # Search functionality
├── 📁 php/
│   ├── 📁 config/
│   │   └── 📄 database.php       # Database connection
│   ├── 📁 includes/
│   │   ├── 📄 header.php         # Common header
│   │   ├── 📄 footer.php         # Common footer
│   │   └── 📄 functions.php      # Utility functions
│   ├── 📁 auth/
│   │   ├── 📄 login.php          # Login processing
│   │   ├── 📄 register.php       # Registration processing
│   │   └── 📄 logout.php         # Logout functionality
│   ├── 📁 user/
│   │   ├── 📄 profile.php        # User profile management
│   │   └── 📄 appointments.php   # User appointments
│   ├── 📁 admin/
│   │   ├── 📄 dashboard.php      # Admin dashboard
│   │   ├── 📄 manage-users.php   # User management
│   │   ├── 📄 manage-classes.php # Class management
│   │   └── 📄 queries.php        # User queries
│   └── 📁 ajax/
│       ├── 📄 search.php         # AJAX search handler
│       ├── 📄 contact-form.php   # Contact form handler
│       └── 📄 newsletter.php     # Newsletter subscription
├── 📁 database/
│   └── 📄 fitzone.sql            # Database schema
├── 📁 images/                    # Static images
├── 📁 uploads/                   # User uploads
├── 📁 documentation/             # Project documentation
└── 📄 README.md                  # This file
```

## 💻 Usage

### For Members

1. **Registration**: Visit `/register.html` to create a new account
2. **Login**: Access your account through `/login.html`
3. **Dashboard**: View your fitness statistics and upcoming classes
4. **Book Classes**: Browse and reserve spots in fitness classes
5. **Schedule Training**: Book personal training sessions
6. **Track Progress**: Log workouts and monitor improvements

### For Trainers

1. **Profile Management**: Update specializations and availability
2. **Class Scheduling**: Create and manage fitness classes
3. **Client Appointments**: Schedule and track training sessions
4. **Progress Monitoring**: Review client achievements and goals

### For Administrators

1. **User Management**: Add, edit, and deactivate user accounts
2. **Content Management**: Update classes, trainers, and blog content
3. **System Analytics**: Monitor usage statistics and performance
4. **Support Management**: Handle contact queries and support tickets

## 🔧 Admin Panel

Access the admin panel at `/admin/` with administrator credentials.

### Default Admin Account
- **Email**: admin@fitzonecenter.com
- **Password**: admin123
- **Note**: Change the default password immediately after installation

### Admin Features
- User management with role assignment
- Class and trainer administration
- Contact query handling
- Newsletter management
- System settings and configuration
- Analytics and reporting

## 🔌 API Endpoints

### Authentication
- `POST /php/auth/login.php` - User login
- `POST /php/auth/register.php` - User registration
- `GET /php/auth/logout.php` - User logout

### Search
- `GET /php/ajax/search.php` - Search classes, trainers, and content
- Parameters: `q`, `type`, `level`, `scope`, `limit`, `page`

### Contact
- `POST /php/ajax/contact-form.php` - Submit contact form
- `POST /php/ajax/newsletter.php` - Newsletter subscription

### User Management
- `GET /php/user/profile.php` - Get user profile
- `POST /php/user/profile.php` - Update user profile
- `GET /php/user/appointments.php` - Get user appointments

## 🔒 Security Features

### Authentication & Authorization
- Secure password hashing with bcrypt
- Session-based authentication with timeout
- Role-based access control (Member, Trainer, Admin)
- Remember me functionality with secure tokens

### Input Validation & Sanitization
- Server-side validation for all forms
- XSS prevention through input sanitization
- SQL injection prevention with prepared statements
- CSRF protection with token validation

### Rate Limiting & Abuse Prevention
- Login attempt limiting with IP blocking
- Contact form rate limiting
- Search request throttling
- Failed attempt logging and monitoring

### Data Protection
- Secure cookie configuration
- Email verification for new accounts
- Personal data encryption where applicable
- Regular security logging and monitoring

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Add comments for complex logic
- Write unit tests for new features
- Ensure responsive design compatibility

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email**: support@fitzonecenter.com
- **Documentation**: `/documentation/`
- **Issues**: Create a GitHub issue

## 🔄 Version History

### v1.0.0 (Current)
- Initial release with core functionality
- User registration and authentication
- Class booking system
- Trainer management
- Admin panel
- Responsive design

---

**FitZone Fitness Center** - Transform your body, transform your life! 💪
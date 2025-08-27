# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Essential Commands

### Setup & Testing
- `php setup.php` - Run initial system setup and requirements check
- `php test-user-management.php` - Execute comprehensive user management system tests
- `mysql -u root -p fitzone_db < database/fitzone.sql` - Import database schema

### Database Operations
- Database connection test: Check `php/config/database.php` configuration
- Access MySQL: `mysql -u root -p fitzone_db`
- Import schema: `mysql -u root -p fitzone_db < database/fitzone.sql`

### No Build Process
This is a traditional PHP application with no build tools, package managers, or compilation steps. All JavaScript and CSS are directly served as static files.

## Core Architecture

### Database Layer (Singleton Pattern)
- **Database Class** (`php/config/database.php`): Singleton PDO wrapper with connection management
- **Connection Handling**: Automatic reconnection, timezone setting, error handling
- **Query Methods**: `select()`, `selectOne()`, `insert()`, `update()`, `delete()` with prepared statements

### Authentication System
- **Session-based**: Primary authentication using PHP sessions
- **Remember Me**: Token-based persistent login with `remember_tokens` table
- **Role-based Access**: 4 roles (member, trainer, staff, admin) with hierarchical permissions
- **Security**: CSRF protection, rate limiting, password hashing with bcrypt

### User Management Architecture
- **UserManager Class** (`php/includes/UserManager.php`): Complete CRUD operations for users
- **Permission System**: Role-based with granular permission checking
- **Profile System**: Separate `user_profiles` table for extended user data
- **Activity Logging**: All user actions logged in `activity_logs` table

### Frontend Architecture
- **Multi-page Application**: Traditional server-side rendered pages
- **Unified Dashboard** (`unified-dashboard.html` + `js/unified-dashboard.js`): Single-page dashboard with dynamic content loading
- **Form Validation** (`js/form-validation.js`): Client-side validation with server-side verification
- **Demo System**: Built-in demo login buttons for quick testing

### Security Implementation
- **Rate Limiting**: IP-based with configurable windows (login: 5/15min, search: 20/1min)
- **Input Sanitization**: XSS prevention via `htmlspecialchars()` and type-specific filters
- **SQL Injection Prevention**: Prepared statements throughout
- **Failed Login Tracking**: `failed_login_attempts` and `blocked_ips` tables

## File Structure Understanding

### PHP Backend Structure
```
php/
├── config/database.php          # Database singleton with PDO wrapper
├── includes/
│   ├── functions.php           # Core utility functions (auth, validation, etc.)
│   ├── UserManager.php         # User CRUD operations class
│   ├── AuthMiddleware.php      # Authentication middleware
│   ├── PermissionManager.php   # Role-based permissions
│   └── SecurityManager.php     # Security utilities
├── auth/                       # Authentication endpoints
├── admin/                      # Admin-only endpoints
├── user/                       # User profile endpoints
└── ajax/                       # AJAX handlers for forms/search
```

### Database Schema Key Tables
- `users` - Core user data with roles and membership info
- `user_profiles` - Extended profile information
- `remember_tokens` - Persistent login tokens
- `activity_logs` - User action tracking
- `rate_limits` - Rate limiting tracking
- `memberships` - Membership plan details

### Frontend Assets
- `js/unified-dashboard.js` - Modern ES6 class-based dashboard with state management
- `js/form-validation.js` - Comprehensive form handling with AJAX
- `css/dashboard.css` - Dashboard-specific styles
- `css/responsive.css` - Mobile-first responsive design

## Authentication Flow

1. **Login Process**: `php/auth/login.php` validates credentials, creates session, optionally sets remember token
2. **Session Check**: `isLoggedIn()` checks session first, then remember token
3. **Auto-login**: `checkRememberToken()` automatically logs in users with valid tokens
4. **Role Verification**: `requireAdmin()`, `userHasPermission()` for access control

## Key Functions & Classes

### Database (php/config/database.php)
- `Database::getInstance()` - Get singleton database connection
- `getDB()` - Global helper function for database access

### Authentication (php/includes/functions.php)
- `loginUser($email, $password, $rememberMe)` - Complete login process
- `isLoggedIn()` - Check authentication status
- `requireLogin()`, `requireAdmin()` - Access control middleware

### User Management (php/includes/UserManager.php)
- `getUsers($filters, $page, $limit)` - Paginated user listing with filters
- `createUser($userData, $adminId)` - Full user creation with profile and membership
- `updateUser($userId, $userData, $adminId)` - User updates with activity logging

### Security & Validation
- `validatePassword($password)` - Strong password requirements
- `checkRateLimit($action, $limit, $window)` - Rate limiting with database storage
- `generateCSRFToken()`, `verifyCSRFToken()` - CSRF protection

## Development Patterns

### Error Handling
All database operations wrapped in try-catch blocks. Errors logged via `error_log()` with context. User-facing errors are generic in production.

### Response Format
AJAX endpoints use consistent JSON responses:
```php
successResponse($message, $data);
errorResponse($message, $errors, $statusCode);
```

### Activity Logging
All significant user actions logged via:
```php
logActivity($userId, $action, $description, $metadata);
```

### Security Constants
Files use `FITZONE_ACCESS` constant to prevent direct access:
```php
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}
```

## Testing & Debugging

- `setup.php` - System requirements and setup verification
- `test-user-management.php` - Comprehensive test suite for user system
- All admin functions can be tested through `admin-dashboard.html`
- Demo login buttons available in `login.html` for quick access

## Configuration

### Database Configuration
Environment variables or direct configuration in `php/config/database.php`:
- DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD, DB_PORT

### Session Configuration
Sessions configured with security flags in `functions.php` initialization.

### File Upload Configuration
Profile pictures handled with size limits, type validation, and thumbnail generation.

## Important Notes

- This is a traditional PHP application - no modern build tools or package managers
- Database schema is comprehensive with proper foreign keys and indexing
- Security is implemented at multiple layers (input validation, rate limiting, role checks)
- The codebase follows consistent patterns for error handling and logging
- All user inputs are sanitized and validated both client-side and server-side
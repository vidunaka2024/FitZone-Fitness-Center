<?php
// FitZone Fitness Center - Authentication Middleware

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

class AuthMiddleware {
    private $db;
    private $currentUser = null;
    private $config;
    
    public function __construct() {
        $this->db = getDB();
        $this->config = [
            'session_timeout' => 3600, // 1 hour
            'remember_duration' => 2592000, // 30 days
            'require_verification' => false
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session authentication
        if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
            // Check session timeout
            if (time() - $_SESSION['login_time'] > $this->config['session_timeout']) {
                $this->logout();
                return false;
            }
            
            // Load user data
            $this->currentUser = $this->getUserById($_SESSION['user_id']);
            if ($this->currentUser && $this->currentUser['status'] === 'active') {
                // Update session time
                $_SESSION['login_time'] = time();
                return true;
            } else {
                $this->logout();
                return false;
            }
        }
        
        // Check remember me token
        if (isset($_COOKIE['remember_token'])) {
            return $this->authenticateByRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Authenticate user by remember token
     */
    private function authenticateByRememberToken($token) {
        try {
            $tokenRecord = $this->db->selectOne(
                "SELECT rt.user_id, rt.expires_at, u.* 
                 FROM remember_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'",
                [hash('sha256', $token)]
            );
            
            if ($tokenRecord) {
                // Create session
                $_SESSION['user_id'] = $tokenRecord['user_id'];
                $_SESSION['user_email'] = $tokenRecord['email'];
                $_SESSION['user_role'] = $tokenRecord['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['auth_method'] = 'remember_token';
                
                $this->currentUser = $tokenRecord;
                
                // Log auto-login
                logActivity($tokenRecord['user_id'], 'auto_login', 'Authenticated via remember token');
                
                return true;
            } else {
                // Invalid or expired token - clear cookie
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
                return false;
            }
        } catch (Exception $e) {
            logError('Remember token authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Require authentication - redirect if not authenticated
     */
    public function requireAuth($redirectUrl = 'login.html') {
        if (!$this->isAuthenticated()) {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $returnUrl = !empty($currentUrl) ? '?return=' . urlencode($currentUrl) : '';
            
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required', 'redirect' => $redirectUrl]);
                exit;
            } else {
                header("Location: {$redirectUrl}{$returnUrl}");
                exit;
            }
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($requiredRoles, $redirectUrl = 'dashboard.html') {
        $this->requireAuth();
        
        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }
        
        if (!in_array($this->currentUser['role'], $requiredRoles)) {
            if ($this->isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            } else {
                header("Location: {$redirectUrl}?error=insufficient_permissions");
                exit;
            }
        }
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireRole(['admin'], 'unified-dashboard.html');
    }
    
    /**
     * Require staff or admin access
     */
    public function requireStaff() {
        $this->requireRole(['admin', 'staff'], 'unified-dashboard.html');
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $rememberMe = false) {
        try {
            // Get user
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                $this->logFailedLogin($email, 'User not found');
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check password
            if (!password_verify($password, $user['password'])) {
                $this->logFailedLogin($email, 'Invalid password');
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check user status
            if ($user['status'] !== 'active') {
                $this->logFailedLogin($email, 'Account not active: ' . $user['status']);
                return ['success' => false, 'message' => 'Account is ' . $user['status'] . '. Please contact support.'];
            }
            
            // Check email verification if required
            if ($this->config['require_verification'] && !$user['email_verified']) {
                return ['success' => false, 'message' => 'Please verify your email address before logging in.'];
            }
            
            // Create session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            session_regenerate_id(true); // Prevent session fixation
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['auth_method'] = 'password';
            
            $this->currentUser = $user;
            
            // Handle remember me
            if ($rememberMe) {
                $this->createRememberToken($user['id']);
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log successful login
            logActivity($user['id'], 'login', 'User logged in successfully');
            
            // Clear any previous failed attempts
            $this->clearFailedAttempts($_SERVER['REMOTE_ADDR'] ?? '');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'membership_plan' => $user['membership_plan']
                ]
            ];
            
        } catch (Exception $e) {
            logError('Login error', ['email' => $email, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        // Remove remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->removeRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Log logout
        if ($userId) {
            logActivity($userId, 'logout', 'User logged out');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session for potential flash messages
        session_start();
        session_regenerate_id(true);
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->currentUser;
    }
    
    /**
     * Check if current user has role
     */
    public function hasRole($role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        if (is_array($role)) {
            return in_array($this->currentUser['role'], $role);
        }
        
        return $this->currentUser['role'] === $role;
    }
    
    /**
     * Get user dashboard URL based on role
     */
    public function getDashboardUrl() {
        if (!$this->isAuthenticated()) {
            return 'login.html';
        }
        
        switch ($this->currentUser['role']) {
            case 'admin':
            case 'staff':
                return 'unified-dashboard.html#admin-overview';
            case 'trainer':
                return 'unified-dashboard.html#trainer-overview';
            default:
                return 'unified-dashboard.html#overview';
        }
    }
    
    /**
     * Create remember token
     */
    private function createRememberToken($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + $this->config['remember_duration'];
            
            // Store hashed token in database
            $this->db->insert('remember_tokens', [
                'user_id' => $userId,
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', $expiry),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Set cookie with raw token
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
            
        } catch (Exception $e) {
            logError('Remember token creation error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Remove remember token
     */
    private function removeRememberToken($token) {
        try {
            $this->db->delete('remember_tokens', 'token = ?', [hash('sha256', $token)]);
        } catch (Exception $e) {
            logError('Remember token removal error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        try {
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?', 
                [$userId]
            );
        } catch (Exception $e) {
            logError('Last login update error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedLogin($email, $reason) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $this->db->insert('failed_login_attempts', [
                'email' => $email,
                'ip_address' => $ip,
                'attempted_at' => date('Y-m-d H:i:s'),
                'user_agent' => $userAgent,
                'reason' => $reason
            ]);
            
            logActivity(null, 'failed_login', "Failed login attempt for: {$email}", [
                'reason' => $reason,
                'ip_address' => $ip
            ]);
            
        } catch (Exception $e) {
            logError('Failed login logging error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Clear failed attempts for IP
     */
    private function clearFailedAttempts($ip) {
        try {
            $this->db->delete('failed_login_attempts', 'ip_address = ?', [$ip]);
        } catch (Exception $e) {
            logError('Clear failed attempts error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        try {
            return $this->db->selectOne(
                "SELECT * FROM users WHERE id = ? AND status = 'active'",
                [$userId]
            );
        } catch (Exception $e) {
            logError('Get user by ID error', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get user by email
     */
    private function getUserByEmail($email) {
        try {
            return $this->db->selectOne(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );
        } catch (Exception $e) {
            logError('Get user by email error', ['email' => $email, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get session data for client-side
     */
    public function getSessionData() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'user' => [
                'id' => $this->currentUser['id'],
                'name' => $this->currentUser['first_name'] . ' ' . $this->currentUser['last_name'],
                'email' => $this->currentUser['email'],
                'role' => $this->currentUser['role'],
                'membership_plan' => $this->currentUser['membership_plan'],
                'avatar' => $this->getUserAvatar($this->currentUser)
            ],
            'expires' => time() + $this->config['session_timeout']
        ];
    }
    
    /**
     * Get user avatar URL
     */
    private function getUserAvatar($user) {
        if (!empty($user['profile_picture'])) {
            $avatarPath = 'uploads/profile-pics/' . $user['profile_picture'];
            if (file_exists($avatarPath)) {
                return $avatarPath;
            }
        }
        
        return 'uploads/profile-pics/default-avatar.jpg';
    }
}

/**
 * Global auth helper functions
 */

/**
 * Get global auth instance
 */
function getAuth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new AuthMiddleware();
    }
    return $auth;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return getAuth()->isAuthenticated();
}

/**
 * Require authentication
 */
function requireAuth($redirectUrl = 'login.html') {
    return getAuth()->requireAuth($redirectUrl);
}

/**
 * Require admin role
 */
function requireAdminAuth() {
    return getAuth()->requireAdmin();
}

/**
 * Require staff role
 */
function requireStaffAuth() {
    return getAuth()->requireStaff();
}

/**
 * Get current user
 */
function getCurrentUser() {
    return getAuth()->getCurrentUser();
}

/**
 * Check user role
 */
function hasRole($role) {
    return getAuth()->hasRole($role);
}

/**
 * Login user
 */
function loginUser($email, $password, $rememberMe = false) {
    return getAuth()->login($email, $password, $rememberMe);
}

/**
 * Logout user
 */
function logoutUser() {
    return getAuth()->logout();
}

/**
 * Get dashboard URL for user
 */
function getDashboardUrl() {
    return getAuth()->getDashboardUrl();
}
?>
<?php
// FitZone Fitness Center - Utility Functions

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

// ========================================
// USER FUNCTIONS
// ========================================

/**
 * Get user by ID
 */
function getUserById($userId) {
    try {
        $db = getDB();
        return $db->selectOne(
            "SELECT * FROM users WHERE id = ? AND status = 'active'",
            [$userId]
        );
    } catch (Exception $e) {
        error_log('Error fetching user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user by email
 */
function getUserByEmail($email) {
    try {
        $db = getDB();
        return $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );
    } catch (Exception $e) {
        error_log('Error fetching user by email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create new user
 */
function createUser($data) {
    try {
        $db = getDB();
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = 'active';
        
        // Generate verification token if email verification is enabled
        if (!isset($data['email_verified'])) {
            $data['email_verified'] = 0;
            $data['verification_token'] = bin2hex(random_bytes(32));
        }
        
        return $db->insert('users', $data);
    } catch (Exception $e) {
        error_log('Error creating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update user data
 */
function updateUser($userId, $data) {
    try {
        $db = getDB();
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $db->update('users', $data, 'id = ?', [$userId]);
    } catch (Exception $e) {
        error_log('Error updating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get user avatar URL
 */
function getUserAvatar($user, $size = 'medium') {
    if (empty($user['profile_picture'])) {
        return 'uploads/profile-pics/default-avatar.jpg';
    }
    
    $avatarPath = 'uploads/profile-pics/' . $user['profile_picture'];
    
    // Check if file exists
    if (file_exists($avatarPath)) {
        return $avatarPath;
    }
    
    return 'uploads/profile-pics/default-avatar.jpg';
}

/**
 * Check if user has permission
 */
function userHasPermission($userId, $permission) {
    try {
        $user = getUserById($userId);
        if (!$user) return false;
        
        // Admin has all permissions
        if ($user['role'] === 'admin') return true;
        
        // Add specific permission checks here
        switch ($permission) {
            case 'access_dashboard':
                return in_array($user['role'], ['admin', 'member']);
            case 'manage_users':
                return $user['role'] === 'admin';
            case 'book_classes':
                return in_array($user['role'], ['admin', 'member']);
            default:
                return false;
        }
    } catch (Exception $e) {
        error_log('Error checking user permission: ' . $e->getMessage());
        return false;
    }
}

// ========================================
// AUTHENTICATION FUNCTIONS
// ========================================

/**
 * Login user
 */
function loginUser($email, $password, $rememberMe = false) {
    try {
        $user = getUserByEmail($email);
        
        if (!$user || !verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Remember me functionality
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $db = getDB();
            $db->insert('remember_tokens', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', $expiry),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Set cookie
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
        }
        
        // Update last login
        updateUser($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        
        // Log successful login
        logActivity($user['id'], 'login', 'User logged in successfully');
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Remove remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $db = getDB();
            $db->delete('remember_tokens', 'token = ?', [hash('sha256', $token)]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Log logout activity
        if ($userId) {
            logActivity($userId, 'logout', 'User logged out');
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    } catch (Exception $e) {
        error_log('Logout error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check remember me token
 */
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    try {
        $token = $_COOKIE['remember_token'];
        $db = getDB();
        
        $tokenData = $db->selectOne(
            "SELECT rt.user_id, rt.expires_at, u.* 
             FROM remember_tokens rt 
             JOIN users u ON rt.user_id = u.id 
             WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'",
            [hash('sha256', $token)]
        );
        
        if ($tokenData) {
            // Auto login user
            session_start();
            $_SESSION['user_id'] = $tokenData['user_id'];
            $_SESSION['user_email'] = $tokenData['email'];
            $_SESSION['user_role'] = $tokenData['role'];
            $_SESSION['login_time'] = time();
            
            return $tokenData;
        }
        
        // Invalid or expired token - clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        return false;
        
    } catch (Exception $e) {
        error_log('Remember token error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Check session first
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check remember me token
    return checkRememberToken() !== false;
}

/**
 * Require login
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        $returnUrl = $_SERVER['REQUEST_URI'];
        $separator = strpos($redirectTo, '?') !== false ? '&' : '?';
        header("Location: {$redirectTo}{$separator}return=" . urlencode($returnUrl));
        exit;
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// ========================================
// VALIDATION FUNCTIONS
// ========================================

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    // More reasonable password requirements to match client-side validation
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if (!preg_match('/[a-zA-Z]/', $password)) {
        $errors[] = 'Password must contain at least one letter';
    }
    
    // For passwords shorter than 8 chars, require a number
    if (strlen($password) < 8 && !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    // Remove all non-digits
    $cleaned = preg_replace('/\D/', '', $phone);
    
    // Check if it's a valid US phone number (10 digits)
    return strlen($cleaned) === 10;
}

/**
 * Sanitize input
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date)) return '';
    
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    if (empty($datetime)) return '';
    
    try {
        return date($format, strtotime($datetime));
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    if (empty($datetime)) return '';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    if ($time < 31104000) return floor($time / 2592000) . ' months ago';
    
    return floor($time / 31104000) . ' years ago';
}

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Check if file is image
 */
function isImageFile($filename) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    return in_array(getFileExtension($filename), $imageExtensions);
}

/**
 * Generate thumbnail
 */
function generateThumbnail($sourcePath, $destPath, $maxWidth = 150, $maxHeight = 150) {
    if (!file_exists($sourcePath) || !isImageFile($sourcePath)) {
        return false;
    }
    
    try {
        $imageInfo = getimagesize($sourcePath);
        $mime = $imageInfo['mime'];
        
        // Create source image
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        
        // Calculate dimensions
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = $sourceWidth * $ratio;
        $newHeight = $sourceHeight * $ratio;
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Handle transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        imagecopyresampled(
            $thumbnail, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Save thumbnail
        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($thumbnail, $destPath, 90);
                break;
            case 'image/png':
                $result = imagepng($thumbnail, $destPath);
                break;
            case 'image/gif':
                $result = imagegif($thumbnail, $destPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Thumbnail generation error: ' . $e->getMessage());
        return false;
    }
}

// ========================================
// LOGGING FUNCTIONS
// ========================================

/**
 * Log activity
 */
function logActivity($userId, $action, $description = '', $metadata = []) {
    try {
        $db = getDB();
        
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'metadata' => json_encode($metadata),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('activity_logs', $data);
    } catch (Exception $e) {
        error_log('Activity logging error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    
    error_log($logMessage);
}

// ========================================
// EMAIL FUNCTIONS
// ========================================

/**
 * Send email (basic implementation)
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    try {
        $headers = [
            'From: FitZone Fitness Center <noreply@fitzonecenter.com>',
            'Reply-To: info@fitzonecenter.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if ($isHTML) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log('Email sending error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($user) {
    $subject = 'Welcome to FitZone Fitness Center!';
    
    $body = "
    <html>
    <body>
        <h2>Welcome to FitZone, {$user['first_name']}!</h2>
        <p>Thank you for joining our fitness community. We're excited to help you on your fitness journey.</p>
        
        <h3>Your Membership Details:</h3>
        <ul>
            <li><strong>Plan:</strong> " . ucfirst($user['membership_plan']) . "</li>
            <li><strong>Start Date:</strong> " . formatDate($user['created_at']) . "</li>
        </ul>
        
        <h3>Getting Started:</h3>
        <ol>
            <li>Complete your profile in the member dashboard</li>
            <li>Book your first fitness class</li>
            <li>Schedule a tour of our facilities</li>
            <li>Meet with a personal trainer</li>
        </ol>
        
        <p>If you have any questions, please don't hesitate to contact us at info@fitzonecenter.com</p>
        
        <p>Let's get fit together!</p>
        <p>The FitZone Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($user['email'], $subject, $body, true);
}

// ========================================
// PAGINATION FUNCTIONS
// ========================================

/**
 * Calculate pagination
 */
function calculatePagination($totalItems, $itemsPerPage = 20, $currentPage = 1) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
    ];
}

/**
 * Generate pagination HTML
 */
function generatePaginationHTML($pagination, $baseUrl = '') {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $html .= "<a href=\"{$baseUrl}?page={$pagination['previous_page']}\" class=\"page-link\">Previous</a>";
    }
    
    // Page numbers
    $startPage = max(1, $pagination['current_page'] - 2);
    $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i === $pagination['current_page'] ? ' active' : '';
        $html .= "<a href=\"{$baseUrl}?page={$i}\" class=\"page-link{$active}\">{$i}</a>";
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= "<a href=\"{$baseUrl}?page={$pagination['next_page']}\" class=\"page-link\">Next</a>";
    }
    
    $html .= '</div>';
    
    return $html;
}

// ========================================
// CSRF PROTECTION
// ========================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF token input field
 */
function csrfTokenField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// ========================================
// RATE LIMITING
// ========================================

/**
 * Check rate limit
 */
function checkRateLimit($action, $limit = 5, $window = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = "rate_limit_{$action}_{$ip}";
    
    try {
        $db = getDB();
        
        // Clean old entries
        $db->delete('rate_limits', 'expires_at < NOW()');
        
        // Count recent attempts
        $count = $db->selectOne(
            "SELECT COUNT(*) as count FROM rate_limits WHERE key_name = ? AND expires_at > NOW()",
            [$key]
        )['count'] ?? 0;
        
        if ($count >= $limit) {
            return false;
        }
        
        // Record this attempt
        $db->insert('rate_limits', [
            'key_name' => $key,
            'expires_at' => date('Y-m-d H:i:s', time() + $window),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log('Rate limit error: ' . $e->getMessage());
        return true; // Fail open
    }
}

// ========================================
// JSON RESPONSE HELPER
// ========================================

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send success response
 */
function successResponse($message = 'Success', $data = []) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send error response
 */
function errorResponse($message = 'Error occurred', $errors = [], $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $statusCode);
}

?>

<?php
// Initialize error reporting based on environment
$environment = $_ENV['ENVIRONMENT'] ?? 'development';
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

// Auto-check remember me token on every page load
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    checkRememberToken();
}
?>
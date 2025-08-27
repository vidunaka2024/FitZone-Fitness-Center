<?php
// FitZone Fitness Center - Login Processing

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request is POST and AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', [], 405);
}

// Rate limiting for login attempts
if (!checkRateLimit('login', 5, 900)) { // 5 attempts per 15 minutes
    errorResponse('Too many login attempts. Please try again later.', [], 429);
}

try {
    // Get and validate input
    $email = sanitizeInput($_POST['email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (!empty($errors)) {
        errorResponse('Validation failed', $errors);
    }
    
    // Attempt login
    $loginResult = loginUser($email, $password, $rememberMe);
    
    if ($loginResult['success']) {
        // Determine redirect URL
        $redirectUrl = 'dashboard.php';
        
        // Check for return URL
        if (!empty($_POST['return_url'])) {
            $returnUrl = filter_var($_POST['return_url'], FILTER_SANITIZE_URL);
            // Validate that it's a relative URL (security)
            if (strpos($returnUrl, '/') === 0 && strpos($returnUrl, '//') === false) {
                $redirectUrl = ltrim($returnUrl, '/');
            }
        }
        
        // Clear any failed login attempts for this IP
        clearFailedAttempts($_SERVER['REMOTE_ADDR'] ?? '');
        
        successResponse('Login successful', [
            'redirect' => $redirectUrl,
            'user' => [
                'id' => $loginResult['user']['id'],
                'name' => $loginResult['user']['first_name'] . ' ' . $loginResult['user']['last_name'],
                'email' => $loginResult['user']['email'],
                'role' => $loginResult['user']['role'],
                'membership_plan' => $loginResult['user']['membership_plan']
            ]
        ]);
    } else {
        // Log failed attempt
        logFailedAttempt($email, $_SERVER['REMOTE_ADDR'] ?? '');
        
        // Return generic error message for security
        errorResponse($loginResult['message'], ['email' => 'Invalid credentials']);
    }
    
} catch (Exception $e) {
    logError('Login processing error', [
        'error' => $e->getMessage(),
        'email' => $email ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]);
    
    errorResponse('An error occurred during login. Please try again.', [], 500);
}

/**
 * Log failed login attempt
 */
function logFailedAttempt($email, $ip) {
    try {
        $db = getDB();
        
        $data = [
            'email' => $email,
            'ip_address' => $ip,
            'attempted_at' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $db->insert('failed_login_attempts', $data);
        
        // Check if this IP should be temporarily blocked
        $recentAttempts = $db->selectOne(
            "SELECT COUNT(*) as count FROM failed_login_attempts 
             WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$ip]
        )['count'] ?? 0;
        
        // Block IP if more than 10 failed attempts in an hour
        if ($recentAttempts >= 10) {
            $db->insert('blocked_ips', [
                'ip_address' => $ip,
                'reason' => 'Too many failed login attempts',
                'blocked_until' => date('Y-m-d H:i:s', time() + 3600), // Block for 1 hour
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
    } catch (Exception $e) {
        logError('Failed to log failed login attempt', ['error' => $e->getMessage()]);
    }
}

/**
 * Clear failed attempts for IP
 */
function clearFailedAttempts($ip) {
    try {
        $db = getDB();
        $db->delete('failed_login_attempts', 'ip_address = ?', [$ip]);
    } catch (Exception $e) {
        logError('Failed to clear failed attempts', ['error' => $e->getMessage()]);
    }
}

/**
 * Check if IP is blocked
 */
function isIPBlocked($ip) {
    try {
        $db = getDB();
        
        // Clean expired blocks first
        $db->delete('blocked_ips', 'blocked_until < NOW()');
        
        // Check if IP is currently blocked
        $blocked = $db->selectOne(
            "SELECT * FROM blocked_ips WHERE ip_address = ? AND blocked_until > NOW()",
            [$ip]
        );
        
        return $blocked !== false;
    } catch (Exception $e) {
        logError('Failed to check IP block status', ['error' => $e->getMessage()]);
        return false; // Fail open
    }
}

// Check IP block status before processing (moved to beginning would be better)
if (isIPBlocked($_SERVER['REMOTE_ADDR'] ?? '')) {
    errorResponse('Your IP address has been temporarily blocked due to suspicious activity.', [], 429);
}
?>
<?php
// FitZone Fitness Center - Unified Login Processing

// Define access constant and include required files
define('FITZONE_ACCESS', true);
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/AuthMiddleware.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request is POST
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
    
    // Attempt login using AuthMiddleware
    $auth = new AuthMiddleware();
    $loginResult = $auth->login($email, $password, $rememberMe);
    
    if ($loginResult['success']) {
        // Determine redirect URL based on role
        $redirectUrl = $auth->getDashboardUrl();
        
        // Check for return URL
        if (!empty($_POST['return_url'])) {
            $returnUrl = filter_var($_POST['return_url'], FILTER_SANITIZE_URL);
            // Validate that it's a relative URL (security)
            if (strpos($returnUrl, '/') === 0 && strpos($returnUrl, '//') === false) {
                $redirectUrl = ltrim($returnUrl, '/');
            }
        }
        
        successResponse('Login successful', [
            'redirect' => $redirectUrl,
            'user' => $loginResult['user'],
            'session_data' => $auth->getSessionData()
        ]);
    } else {
        // Return error message
        errorResponse($loginResult['message'], ['general' => $loginResult['message']]);
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
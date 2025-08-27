<?php
// FitZone Fitness Center - Logout Processing

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, redirect to home page
        header('Location: ../../index.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Log logout activity
    logActivity($userId, 'logout', 'User logged out', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Remove remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $db = getDB();
            
            // Remove token from database
            $db->delete('remember_tokens', 'token = ?', [hash('sha256', $token)]);
            
            // Clear the cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            
        } catch (Exception $e) {
            logError('Error removing remember token during logout', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }
    }
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear any other FitZone-specific cookies
    $fitzoneCookies = [
        'fitzone_preferences',
        'fitzone_theme',
        'fitzone_language'
    ];
    
    foreach ($fitzoneCookies as $cookieName) {
        if (isset($_COOKIE[$cookieName])) {
            setcookie($cookieName, '', time() - 3600, '/');
        }
    }
    
    // Handle AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '../../index.php'
        ]);
        exit;
    }
    
    // Determine redirect URL
    $redirectUrl = '../../index.php';
    
    // Check for custom redirect
    if (isset($_GET['redirect'])) {
        $customRedirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
        
        // Validate redirect URL (security check)
        if (isValidRedirectUrl($customRedirect)) {
            $redirectUrl = $customRedirect;
        }
    }
    
    // Add logout success message
    session_start(); // Start new session for flash message
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'You have been logged out successfully.'
    ];
    
    // Redirect
    header("Location: $redirectUrl");
    exit;
    
} catch (Exception $e) {
    logError('Logout processing error', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]);
    
    // Even if there's an error, try to clear the session and redirect
    session_destroy();
    header('Location: ../../index.php?error=logout_error');
    exit;
}

/**
 * Validate redirect URL for security
 */
function isValidRedirectUrl($url) {
    // Must be a relative URL or same domain
    $parsedUrl = parse_url($url);
    
    // If it's a relative URL, it's safe
    if (!isset($parsedUrl['host'])) {
        return strpos($url, '/') === 0 && strpos($url, '//') === false;
    }
    
    // If it has a host, it must be the same as current host
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    return $parsedUrl['host'] === $currentHost;
}

/**
 * Clean up expired sessions and tokens (maintenance function)
 */
function cleanupExpiredTokens() {
    try {
        $db = getDB();
        
        // Remove expired remember tokens
        $db->delete('remember_tokens', 'expires_at < NOW()');
        
        // Remove expired rate limit entries
        $db->delete('rate_limits', 'expires_at < NOW()');
        
        // Remove expired blocked IPs
        $db->delete('blocked_ips', 'blocked_until < NOW()');
        
        // Remove old failed login attempts (older than 24 hours)
        $db->delete('failed_login_attempts', 'attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        
    } catch (Exception $e) {
        logError('Error cleaning up expired tokens', ['error' => $e->getMessage()]);
    }
}

// Run cleanup periodically (1% chance)
if (rand(1, 100) === 1) {
    cleanupExpiredTokens();
}

/**
 * Log user session for analytics (optional)
 */
function logUserSession($userId, $action = 'logout') {
    try {
        if (!$userId) return;
        
        $sessionData = [
            'user_id' => $userId,
            'action' => $action,
            'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db = getDB();
        $db->insert('user_sessions', $sessionData);
        
    } catch (Exception $e) {
        logError('Error logging user session', ['error' => $e->getMessage()]);
    }
}

// Log the session before destroying it
if (isset($_SESSION['user_id'])) {
    logUserSession($_SESSION['user_id'], 'logout');
}
?>
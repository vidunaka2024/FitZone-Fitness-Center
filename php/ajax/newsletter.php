<?php
// FitZone Fitness Center - Newsletter Subscription Handler

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', [], 405);
}

// Rate limiting for newsletter subscriptions
if (!checkRateLimit('newsletter', 5, 300)) { // 5 subscriptions per 5 minutes
    errorResponse('Too many subscription attempts. Please try again later.', [], 429);
}

try {
    // Get and validate input
    $email = sanitizeInput($_POST['email'] ?? '', 'email');
    $source = sanitizeInput($_POST['source'] ?? 'footer');
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email address is too long';
    }
    
    if (!empty($errors)) {
        errorResponse('Validation failed', $errors);
    }
    
    // Check for spam email patterns
    if (isSpamEmail($email)) {
        // Log potential spam but don't tell the user
        logActivity(null, 'newsletter_spam_detected', 'Potential spam newsletter subscription', [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'source' => $source
        ]);
        
        // Still send success response to avoid giving feedback to spammers
        successResponse('Thank you for subscribing! Please check your email to confirm your subscription.');
    }
    
    $db = getDB();
    
    // Check if email is already subscribed
    $existing = $db->selectOne(
        "SELECT id, status, unsubscribed_at FROM newsletter_subscribers WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            successResponse('You are already subscribed to our newsletter!');
        } elseif ($existing['status'] === 'unsubscribed') {
            // Reactivate subscription
            $db->update('newsletter_subscribers', [
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'unsubscribed_at' => null,
                'source' => $source,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ], 'id = ?', [$existing['id']]);
            
            // Send reactivation email
            sendReactivationEmail($email, $firstName, $lastName);
            
            logActivity(null, 'newsletter_resubscribed', 'Newsletter subscription reactivated', [
                'email' => $email,
                'source' => $source
            ]);
            
            successResponse('Welcome back! Your newsletter subscription has been reactivated.');
        } else {
            // Handle bounced emails
            errorResponse('This email address cannot be subscribed at this time. Please contact us if you believe this is an error.');
        }
    } else {
        // Create new subscription
        $subscriptionData = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'source' => $source,
            'status' => 'pending', // Set to pending until confirmed
            'subscribed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'confirmation_token' => bin2hex(random_bytes(32))
        ];
        
        $subscriptionId = $db->insert('newsletter_subscribers', $subscriptionData);
        
        if (!$subscriptionId) {
            throw new Exception('Failed to create newsletter subscription');
        }
        
        // Send confirmation email
        sendConfirmationEmail($email, $firstName, $lastName, $subscriptionData['confirmation_token']);
        
        // Log successful subscription
        logActivity(null, 'newsletter_subscribed', 'Newsletter subscription created', [
            'subscription_id' => $subscriptionId,
            'email' => $email,
            'source' => $source,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        successResponse('Thank you for subscribing! Please check your email to confirm your subscription.', [
            'subscription_id' => $subscriptionId,
            'requires_confirmation' => true
        ]);
    }
    
} catch (Exception $e) {
    logError('Newsletter subscription error', [
        'error' => $e->getMessage(),
        'email' => $email ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]);
    
    errorResponse('Subscription failed. Please try again later.', [], 500);
}

/**
 * Check if email appears to be spam
 */
function isSpamEmail($email) {
    // Check for suspicious patterns
    $spamPatterns = [
        '/^[a-zA-Z0-9]{30,}@/', // Very long username
        '/@(tempmail|guerrillamail|mailinator|10minutemail|throwaway)/', // Temporary email services
        '/\d{10,}@/', // Username with many consecutive digits
        '/@.*\.tk$|@.*\.ml$|@.*\.ga$/', // Free/suspicious domains
    ];
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $email)) {
            return true;
        }
    }
    
    // Check against known spam email list (would be populated from spam reports)
    try {
        $db = getDB();
        $spamCheck = $db->selectOne(
            "SELECT id FROM spam_emails WHERE email = ?",
            [$email]
        );
        
        return $spamCheck !== false;
    } catch (Exception $e) {
        // If spam check fails, allow subscription (fail open)
        return false;
    }
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail($email, $firstName, $lastName, $token) {
    try {
        $confirmationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/confirm-newsletter.php?token=" . $token;
        $unsubscribeUrl = "https://" . $_SERVER['HTTP_HOST'] . "/unsubscribe.php?email=" . urlencode($email);
        
        $name = trim($firstName . ' ' . $lastName);
        $greeting = !empty($name) ? "Hi $name," : "Hello,";
        
        $subject = 'Confirm Your Newsletter Subscription - FitZone Fitness Center';
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; background: #e74c3c; color: white; padding: 30px; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
                .button { display: inline-block; background: #e74c3c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .benefits { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .benefits ul { list-style-type: none; padding: 0; }
                .benefits li { padding: 8px 0; border-bottom: 1px solid #eee; }
                .benefits li:before { content: '✓'; color: #27ae60; font-weight: bold; margin-right: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏋️‍♀️ FitZone Fitness Center</h1>
                    <h2>Welcome to Our Fitness Community!</h2>
                </div>
                
                <div class='content'>
                    <p>$greeting</p>
                    
                    <p>Thank you for subscribing to the FitZone newsletter! We're excited to help you on your fitness journey.</p>
                    
                    <p><strong>Please confirm your subscription by clicking the button below:</strong></p>
                    
                    <div style='text-align: center;'>
                        <a href='$confirmationUrl' class='button'>Confirm My Subscription</a>
                    </div>
                    
                    <div class='benefits'>
                        <h3>What you'll receive:</h3>
                        <ul>
                            <li>Weekly workout tips and routines</li>
                            <li>Nutrition advice from our experts</li>
                            <li>Exclusive member discounts and offers</li>
                            <li>Early access to new classes and programs</li>
                            <li>Success stories and motivation</li>
                            <li>Updates on gym events and challenges</li>
                        </ul>
                    </div>
                    
                    <p>If you didn't subscribe to this newsletter, you can safely ignore this email.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                    
                    <p style='text-align: center;'>
                        <strong>Follow us on social media:</strong><br>
                        <a href='#' style='color: #e74c3c; text-decoration: none; margin: 0 10px;'>Facebook</a> |
                        <a href='#' style='color: #e74c3c; text-decoration: none; margin: 0 10px;'>Instagram</a> |
                        <a href='#' style='color: #e74c3c; text-decoration: none; margin: 0 10px;'>Twitter</a> |
                        <a href='#' style='color: #e74c3c; text-decoration: none; margin: 0 10px;'>YouTube</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p><strong>FitZone Fitness Center</strong><br>
                    123 Fitness Street, Downtown District<br>
                    Cityville, ST 12345<br>
                    (555) 123-FITZ</p>
                    
                    <p style='margin-top: 15px;'>
                        <a href='$unsubscribeUrl' style='color: #bdc3c7;'>Unsubscribe</a> |
                        <a href='mailto:info@fitzonecenter.com' style='color: #bdc3c7;'>Contact Us</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($email, $subject, $body, true);
    } catch (Exception $e) {
        logError('Error sending newsletter confirmation email', [
            'error' => $e->getMessage(),
            'email' => $email
        ]);
        return false;
    }
}

/**
 * Send reactivation email
 */
function sendReactivationEmail($email, $firstName, $lastName) {
    try {
        $name = trim($firstName . ' ' . $lastName);
        $greeting = !empty($name) ? "Hi $name," : "Hello,";
        
        $subject = 'Welcome Back to FitZone Newsletter!';
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; background: #27ae60; color: white; padding: 30px; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome Back!</h1>
                    <h2>Your FitZone Newsletter is Active Again</h2>
                </div>
                
                <div class='content'>
                    <p>$greeting</p>
                    
                    <p>Great news! Your subscription to the FitZone newsletter has been reactivated. We're thrilled to have you back in our fitness community!</p>
                    
                    <p>You'll continue to receive:</p>
                    <ul>
                        <li>Expert fitness tips and workout routines</li>
                        <li>Nutrition guidance and healthy recipes</li>
                        <li>Exclusive member offers and discounts</li>
                        <li>Updates on new classes and programs</li>
                        <li>Motivational success stories</li>
                    </ul>
                    
                    <p>Thank you for being part of the FitZone family. Let's achieve your fitness goals together!</p>
                    
                    <p>Stay strong,<br>The FitZone Team</p>
                </div>
                
                <div class='footer'>
                    <p><strong>FitZone Fitness Center</strong><br>
                    123 Fitness Street, Downtown District<br>
                    Cityville, ST 12345</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($email, $subject, $body, true);
    } catch (Exception $e) {
        logError('Error sending newsletter reactivation email', [
            'error' => $e->getMessage(),
            'email' => $email
        ]);
        return false;
    }
}

/**
 * Get newsletter statistics (for admin)
 */
function getNewsletterStats() {
    try {
        $db = getDB();
        
        $stats = [
            'total_subscribers' => 0,
            'active_subscribers' => 0,
            'pending_confirmations' => 0,
            'unsubscribed' => 0,
            'bounced' => 0,
            'recent_subscriptions' => 0,
            'top_sources' => []
        ];
        
        // Total subscribers
        $result = $db->selectOne("SELECT COUNT(*) as count FROM newsletter_subscribers");
        $stats['total_subscribers'] = $result['count'] ?? 0;
        
        // Active subscribers
        $result = $db->selectOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'");
        $stats['active_subscribers'] = $result['count'] ?? 0;
        
        // Pending confirmations
        $result = $db->selectOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'pending'");
        $stats['pending_confirmations'] = $result['count'] ?? 0;
        
        // Unsubscribed
        $result = $db->selectOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'unsubscribed'");
        $stats['unsubscribed'] = $result['count'] ?? 0;
        
        // Bounced
        $result = $db->selectOne("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'bounced'");
        $stats['bounced'] = $result['count'] ?? 0;
        
        // Recent subscriptions (last 30 days)
        $result = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM newsletter_subscribers 
            WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent_subscriptions'] = $result['count'] ?? 0;
        
        // Top sources
        $sources = $db->select("
            SELECT source, COUNT(*) as count 
            FROM newsletter_subscribers 
            WHERE status = 'active'
            GROUP BY source 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $stats['top_sources'] = $sources;
        
        return $stats;
    } catch (Exception $e) {
        logError('Error getting newsletter stats', ['error' => $e->getMessage()]);
        return null;
    }
}

// Handle newsletter statistics request (for admin dashboard)
if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        errorResponse('Access denied', [], 403);
    }
    
    $stats = getNewsletterStats();
    if ($stats) {
        successResponse('Newsletter statistics retrieved', $stats);
    } else {
        errorResponse('Failed to retrieve statistics', [], 500);
    }
}
?>
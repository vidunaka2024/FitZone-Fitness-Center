<?php
// FitZone Fitness Center - Contact Form Handler

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

// Rate limiting for contact form submissions
if (!checkRateLimit('contact_form', 3, 600)) { // 3 submissions per 10 minutes
    errorResponse('Too many contact form submissions. Please try again later.', [], 429);
}

try {
    // Get and sanitize input data
    $data = [
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? '', 'email'),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'subject' => sanitizeInput($_POST['subject'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? ''),
        'newsletter' => isset($_POST['newsletter']) && $_POST['newsletter'] == '1',
        'terms' => isset($_POST['terms']) && $_POST['terms'] == '1'
    ];
    
    // Validation
    $errors = validateContactFormData($data);
    
    if (!empty($errors)) {
        errorResponse('Validation failed', $errors);
    }
    
    // Additional spam detection
    if (detectSpam($data)) {
        // Log potential spam but don't tell the user
        logActivity(null, 'spam_detected', 'Potential spam contact form submission', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'data' => $data
        ]);
        
        // Still send success response to avoid giving feedback to spammers
        successResponse('Message sent successfully! We\'ll get back to you within 24 hours.');
    }
    
    // Save contact message to database
    $db = getDB();
    
    $messageData = [
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'subject' => mapSubjectToCategory($data['subject']),
        'message' => $data['message'],
        'status' => 'new',
        'priority' => determinePriority($data['subject'], $data['message']),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $messageId = $db->insert('contact_messages', $messageData);
    
    if (!$messageId) {
        throw new Exception('Failed to save contact message');
    }
    
    // Subscribe to newsletter if requested
    if ($data['newsletter']) {
        subscribeToNewsletter($data['email'], [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'source' => 'contact_form'
        ]);
    }
    
    // Send confirmation email to user
    sendContactConfirmationEmail($data, $messageId);
    
    // Send notification email to admin
    sendContactNotificationEmail($data, $messageId);
    
    // Log successful contact form submission
    logActivity(null, 'contact_form_submitted', 'Contact form submitted successfully', [
        'message_id' => $messageId,
        'email' => $data['email'],
        'subject' => $data['subject'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    // Auto-assign to appropriate staff member if configured
    autoAssignContactMessage($messageId, $data['subject']);
    
    successResponse('Message sent successfully! We\'ll get back to you within 24 hours.', [
        'message_id' => $messageId,
        'estimated_response_time' => '24 hours'
    ]);
    
} catch (Exception $e) {
    logError('Contact form processing error', [
        'error' => $e->getMessage(),
        'email' => $data['email'] ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]);
    
    errorResponse('Failed to send message. Please try again or contact us directly.', [], 500);
}

/**
 * Validate contact form data
 */
function validateContactFormData($data) {
    $errors = [];
    
    // First name validation
    if (empty($data['first_name'])) {
        $errors['first_name'] = 'First name is required';
    } elseif (strlen($data['first_name']) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters';
    } elseif (strlen($data['first_name']) > 50) {
        $errors['first_name'] = 'First name must be less than 50 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $data['first_name'])) {
        $errors['first_name'] = 'First name contains invalid characters';
    }
    
    // Last name validation
    if (empty($data['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    } elseif (strlen($data['last_name']) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters';
    } elseif (strlen($data['last_name']) > 50) {
        $errors['last_name'] = 'Last name must be less than 50 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $data['last_name'])) {
        $errors['last_name'] = 'Last name contains invalid characters';
    }
    
    // Email validation
    if (empty($data['email'])) {
        $errors['email'] = 'Email address is required';
    } elseif (!validateEmail($data['email'])) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($data['email']) > 100) {
        $errors['email'] = 'Email address is too long';
    }
    
    // Phone validation (optional)
    if (!empty($data['phone']) && !validatePhone($data['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    // Subject validation
    if (empty($data['subject'])) {
        $errors['subject'] = 'Please select a subject';
    } elseif (!in_array($data['subject'], [
        'membership', 'personal-training', 'classes', 'facilities', 
        'complaint', 'compliment', 'other'
    ])) {
        $errors['subject'] = 'Please select a valid subject';
    }
    
    // Message validation
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    } elseif (strlen($data['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long';
    } elseif (strlen($data['message']) > 2000) {
        $errors['message'] = 'Message must be less than 2000 characters';
    }
    
    // Terms validation
    if (!$data['terms']) {
        $errors['terms'] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    return $errors;
}

/**
 * Detect spam in form submission
 */
function detectSpam($data) {
    // Check for common spam patterns
    $spamPatterns = [
        '/\b(viagra|cialis|casino|poker|loan|bitcoin|crypto)\b/i',
        '/\b(make money|work from home|earn \$|get paid)\b/i',
        '/\b(click here|visit now|limited time|act now)\b/i',
        '/https?:\/\/[^\s]+/i', // URLs in message (simple check)
    ];
    
    $messageText = $data['message'] . ' ' . $data['first_name'] . ' ' . $data['last_name'];
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $messageText)) {
            return true;
        }
    }
    
    // Check for suspicious email patterns
    $suspiciousEmailPatterns = [
        '/^[a-zA-Z0-9]{20,}@/', // Very long username
        '/@(tempmail|guerrillamail|mailinator|10minutemail)/', // Temporary email services
    ];
    
    foreach ($suspiciousEmailPatterns as $pattern) {
        if (preg_match($pattern, $data['email'])) {
            return true;
        }
    }
    
    // Check message length vs content ratio
    $messageLength = strlen($data['message']);
    $uniqueWords = count(array_unique(explode(' ', strtolower($data['message']))));
    
    if ($messageLength > 100 && $uniqueWords < 10) {
        return true; // Repetitive content
    }
    
    return false;
}

/**
 * Map subject dropdown values to readable categories
 */
function mapSubjectToCategory($subject) {
    $mapping = [
        'membership' => 'Membership Inquiry',
        'personal-training' => 'Personal Training',
        'classes' => 'Group Classes',
        'facilities' => 'Facilities Question',
        'complaint' => 'Complaint',
        'compliment' => 'Compliment',
        'other' => 'General Inquiry'
    ];
    
    return $mapping[$subject] ?? 'General Inquiry';
}

/**
 * Determine message priority based on subject and content
 */
function determinePriority($subject, $message) {
    // High priority subjects
    $highPrioritySubjects = ['complaint'];
    if (in_array($subject, $highPrioritySubjects)) {
        return 'high';
    }
    
    // Check for urgent keywords in message
    $urgentKeywords = [
        'urgent', 'emergency', 'immediately', 'asap', 'injured', 'injury',
        'accident', 'unsafe', 'broken', 'not working', 'cancel membership'
    ];
    
    $messageLower = strtolower($message);
    foreach ($urgentKeywords as $keyword) {
        if (strpos($messageLower, $keyword) !== false) {
            return 'high';
        }
    }
    
    // Medium priority for certain subjects
    $mediumPrioritySubjects = ['membership', 'personal-training'];
    if (in_array($subject, $mediumPrioritySubjects)) {
        return 'normal';
    }
    
    return 'normal';
}

/**
 * Subscribe to newsletter
 */
function subscribeToNewsletter($email, $data) {
    try {
        $db = getDB();
        
        // Check if already subscribed
        $existing = $db->selectOne(
            "SELECT id FROM newsletter_subscribers WHERE email = ?",
            [$email]
        );
        
        if (!$existing) {
            $subscriptionData = [
                'email' => $email,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'source' => $data['source'] ?? 'contact_form',
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            $db->insert('newsletter_subscribers', $subscriptionData);
        }
    } catch (Exception $e) {
        logError('Error subscribing to newsletter from contact form', ['error' => $e->getMessage()]);
        // Don't throw exception as newsletter subscription is not critical
    }
}

/**
 * Send confirmation email to user
 */
function sendContactConfirmationEmail($data, $messageId) {
    try {
        $subject = 'Message Received - FitZone Fitness Center';
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #e74c3c; text-align: center;'>Thank You for Contacting FitZone!</h2>
                
                <p>Hi {$data['first_name']},</p>
                
                <p>Thank you for reaching out to us. We have received your message and will get back to you within 24 hours.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Your Message Summary:</h3>
                    <p><strong>Reference ID:</strong> #" . str_pad($messageId, 6, '0', STR_PAD_LEFT) . "</p>
                    <p><strong>Subject:</strong> " . mapSubjectToCategory($data['subject']) . "</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <p>In the meantime, feel free to:</p>
                <ul>
                    <li>Browse our <a href='https://" . $_SERVER['HTTP_HOST'] . "/classes.php' style='color: #e74c3c;'>fitness classes</a></li>
                    <li>Learn about our <a href='https://" . $_SERVER['HTTP_HOST'] . "/membership.php' style='color: #e74c3c;'>membership plans</a></li>
                    <li>Read our <a href='https://" . $_SERVER['HTTP_HOST'] . "/blog.php' style='color: #e74c3c;'>fitness blog</a></li>
                    <li>Call us at <strong>(555) 123-FITZ</strong> for immediate assistance</li>
                </ul>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                
                <p style='text-align: center; color: #666; font-size: 14px;'>
                    <strong>FitZone Fitness Center</strong><br>
                    123 Fitness Street, Downtown District<br>
                    Cityville, ST 12345<br>
                    <a href='mailto:info@fitzonecenter.com' style='color: #e74c3c;'>info@fitzonecenter.com</a>
                </p>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($data['email'], $subject, $body, true);
    } catch (Exception $e) {
        logError('Error sending contact confirmation email', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Send notification email to admin
 */
function sendContactNotificationEmail($data, $messageId) {
    try {
        // Get admin email from settings
        $adminEmail = getSystemSetting('admin_notification_email', 'admin@fitzonecenter.com');
        
        $subject = 'New Contact Form Submission - FitZone';
        
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #e74c3c;'>New Contact Form Submission</h2>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>Submission Details:</h3>
                    <p><strong>Reference ID:</strong> #" . str_pad($messageId, 6, '0', STR_PAD_LEFT) . "</p>
                    <p><strong>Name:</strong> {$data['first_name']} {$data['last_name']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    <p><strong>Phone:</strong> " . ($data['phone'] ?: 'Not provided') . "</p>
                    <p><strong>Subject:</strong> " . mapSubjectToCategory($data['subject']) . "</p>
                    <p><strong>Priority:</strong> " . ucfirst(determinePriority($data['subject'], $data['message'])) . "</p>
                    <p><strong>Newsletter:</strong> " . ($data['newsletter'] ? 'Yes' : 'No') . "</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <div style='background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 5px;'>
                    <h4 style='color: #2c3e50; margin-top: 0;'>Message:</h4>
                    <p style='white-space: pre-line;'>" . htmlspecialchars($data['message']) . "</p>
                </div>
                
                <div style='margin: 30px 0; text-align: center;'>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/admin/queries.php?id={$messageId}' 
                       style='background: #e74c3c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        View in Admin Panel
                    </a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                
                <p style='color: #666; font-size: 12px;'>
                    <strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "<br>
                    <strong>User Agent:</strong> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
                </p>
            </div>
        </body>
        </html>
        ";
        
        return sendEmail($adminEmail, $subject, $body, true);
    } catch (Exception $e) {
        logError('Error sending contact notification email', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Auto-assign contact message to appropriate staff member
 */
function autoAssignContactMessage($messageId, $subject) {
    try {
        $db = getDB();
        
        // Define assignment rules
        $assignmentRules = [
            'membership' => getStaffByRole('membership_manager'),
            'personal-training' => getStaffByRole('training_manager'),
            'classes' => getStaffByRole('class_coordinator'),
            'facilities' => getStaffByRole('facility_manager'),
            'complaint' => getStaffByRole('customer_service_manager')
        ];
        
        if (isset($assignmentRules[$subject]) && $assignmentRules[$subject]) {
            $db->update('contact_messages', 
                ['assigned_to' => $assignmentRules[$subject]['id']],
                'id = ?',
                [$messageId]
            );
        }
    } catch (Exception $e) {
        logError('Error auto-assigning contact message', ['error' => $e->getMessage()]);
    }
}

/**
 * Get staff member by role
 */
function getStaffByRole($role) {
    try {
        $db = getDB();
        
        // This would be expanded based on your staff role system
        $roleMapping = [
            'membership_manager' => 'admin',
            'training_manager' => 'admin',
            'class_coordinator' => 'admin',
            'facility_manager' => 'admin',
            'customer_service_manager' => 'admin'
        ];
        
        $mappedRole = $roleMapping[$role] ?? 'admin';
        
        return $db->selectOne(
            "SELECT id, first_name, last_name, email FROM users 
             WHERE role = ? AND status = 'active' 
             ORDER BY last_login DESC LIMIT 1",
            [$mappedRole]
        );
    } catch (Exception $e) {
        logError('Error getting staff by role', ['error' => $e->getMessage()]);
        return null;
    }
}

/**
 * Get system setting
 */
function getSystemSetting($key, $default = null) {
    try {
        $db = getDB();
        $result = $db->selectOne(
            "SELECT setting_value FROM system_settings WHERE setting_key = ?",
            [$key]
        );
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>
<?php
// FitZone Fitness Center - Registration Processing

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

// Rate limiting for registration attempts
if (!checkRateLimit('register', 3, 600)) { // 3 attempts per 10 minutes
    errorResponse('Too many registration attempts. Please try again later.', [], 429);
}

try {
    // Get and sanitize input data
    $data = [
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? '', 'email'),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'birth_date' => sanitizeInput($_POST['birth_date'] ?? ''),
        'gender' => sanitizeInput($_POST['gender'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'membership_plan' => sanitizeInput($_POST['membership_plan'] ?? ''),
        'fitness_goals' => sanitizeInput($_POST['fitness_goals'] ?? ''),
        'newsletter' => isset($_POST['newsletter']) && $_POST['newsletter'] == '1',
        'terms' => isset($_POST['terms']) && $_POST['terms'] == '1'
    ];
    
    // Validation
    $errors = validateRegistrationData($data);
    
    if (!empty($errors)) {
        errorResponse('Validation failed', $errors);
    }
    
    // Check if email already exists
    if (getUserByEmail($data['email'])) {
        errorResponse('Registration failed', [
            'email' => 'An account with this email address already exists'
        ]);
    }
    
    // Check if phone number already exists (if provided)
    if (!empty($data['phone']) && isPhoneNumberTaken($data['phone'])) {
        errorResponse('Registration failed', [
            'phone' => 'This phone number is already registered'
        ]);
    }
    
    // Create user account
    $db = getDB();
    $db->beginTransaction();
    
    try {
        // Prepare user data
        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'password' => $data['password'], // Will be hashed in createUser function
            'membership_plan' => $data['membership_plan'],
            'role' => 'member',
            'status' => 'active',
            'email_verified' => 0,
            'newsletter_subscribed' => $data['newsletter'] ? 1 : 0,
            'terms_accepted' => 1,
            'terms_accepted_at' => date('Y-m-d H:i:s')
        ];
        
        // Add fitness goals if provided
        if (!empty($data['fitness_goals'])) {
            $userData['fitness_goals'] = $data['fitness_goals'];
        }
        
        // Create user
        $userId = createUser($userData);
        
        if (!$userId) {
            throw new Exception('Failed to create user account');
        }
        
        // Create user profile
        createUserProfile($userId, $data);
        
        // Create membership record
        createMembershipRecord($userId, $data['membership_plan']);
        
        // Subscribe to newsletter if requested
        if ($data['newsletter']) {
            subscribeToNewsletter($data['email'], [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'source' => 'registration'
            ]);
        }
        
        // Log registration activity
        logActivity($userId, 'register', 'User registered successfully', [
            'membership_plan' => $data['membership_plan'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        $db->commit();
        
        // Get the created user
        $user = getUserById($userId);
        
        // Send welcome email
        if ($user) {
            sendWelcomeEmail($user);
            
            // Send email verification if enabled
            if (!$user['email_verified']) {
                sendEmailVerification($user);
            }
        }
        
        // Auto-login the user
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_role'] = 'member';
        $_SESSION['login_time'] = time();
        
        successResponse('Registration successful! Welcome to FitZone!', [
            'redirect' => 'dashboard.php',
            'user' => [
                'id' => $userId,
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'membership_plan' => $data['membership_plan']
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Registration processing error', [
        'error' => $e->getMessage(),
        'email' => $data['email'] ?? 'N/A',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]);
    
    errorResponse('Registration failed. Please try again.', [], 500);
}

/**
 * Validate registration data
 */
function validateRegistrationData($data) {
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
    
    // Phone validation
    if (empty($data['phone'])) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!validatePhone($data['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    // Birth date validation
    if (empty($data['birth_date'])) {
        $errors['birth_date'] = 'Birth date is required';
    } else {
        $birthDate = DateTime::createFromFormat('Y-m-d', $data['birth_date']);
        if (!$birthDate) {
            $errors['birth_date'] = 'Please enter a valid birth date';
        } else {
            $age = $birthDate->diff(new DateTime())->y;
            if ($age < 16) {
                $errors['birth_date'] = 'You must be at least 16 years old to register';
            } elseif ($age > 120) {
                $errors['birth_date'] = 'Please enter a valid birth date';
            }
        }
    }
    
    // Gender validation
    if (empty($data['gender'])) {
        $errors['gender'] = 'Please select your gender';
    } elseif (!in_array($data['gender'], ['male', 'female', 'other', 'prefer-not-to-say'])) {
        $errors['gender'] = 'Please select a valid gender option';
    }
    
    // Password validation
    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    } else {
        $passwordValidation = validatePassword($data['password']);
        if ($passwordValidation !== true) {
            $errors['password'] = implode(' ', $passwordValidation);
        }
    }
    
    // Confirm password validation
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Membership plan validation
    if (empty($data['membership_plan'])) {
        $errors['membership_plan'] = 'Please select a membership plan';
    } elseif (!in_array($data['membership_plan'], ['basic', 'premium', 'elite'])) {
        $errors['membership_plan'] = 'Please select a valid membership plan';
    }
    
    // Terms validation
    if (!$data['terms']) {
        $errors['terms'] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    // Fitness goals validation (optional)
    if (!empty($data['fitness_goals']) && strlen($data['fitness_goals']) > 500) {
        $errors['fitness_goals'] = 'Fitness goals must be less than 500 characters';
    }
    
    return $errors;
}

/**
 * Check if phone number is already taken
 */
function isPhoneNumberTaken($phone) {
    try {
        $db = getDB();
        $result = $db->selectOne(
            "SELECT id FROM users WHERE phone = ? AND status = 'active'",
            [$phone]
        );
        return $result !== false;
    } catch (Exception $e) {
        logError('Error checking phone number', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Create user profile
 */
function createUserProfile($userId, $data) {
    try {
        $db = getDB();
        
        $profileData = [
            'user_id' => $userId,
            'display_name' => $data['first_name'] . ' ' . $data['last_name'],
            'bio' => '',
            'privacy_level' => 'private',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('user_profiles', $profileData);
    } catch (Exception $e) {
        logError('Error creating user profile', ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Create membership record
 */
function createMembershipRecord($userId, $plan) {
    try {
        $db = getDB();
        
        // Get plan details
        $planDetails = getMembershipPlanDetails($plan);
        
        $membershipData = [
            'user_id' => $userId,
            'plan_type' => $plan,
            'status' => 'active',
            'start_date' => date('Y-m-d'),
            'next_billing_date' => date('Y-m-d', strtotime('+1 month')),
            'monthly_fee' => $planDetails['price'],
            'auto_renew' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('memberships', $membershipData);
    } catch (Exception $e) {
        logError('Error creating membership record', ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Get membership plan details
 */
function getMembershipPlanDetails($plan) {
    $plans = [
        'basic' => ['price' => 29.00, 'name' => 'Basic Plan'],
        'premium' => ['price' => 59.00, 'name' => 'Premium Plan'],
        'elite' => ['price' => 99.00, 'name' => 'Elite Plan']
    ];
    
    return $plans[$plan] ?? $plans['basic'];
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
                'source' => $data['source'] ?? 'registration',
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            $db->insert('newsletter_subscribers', $subscriptionData);
        }
    } catch (Exception $e) {
        logError('Error subscribing to newsletter', ['error' => $e->getMessage()]);
        // Don't throw exception as newsletter subscription is not critical
    }
}

/**
 * Send email verification
 */
function sendEmailVerification($user) {
    try {
        $verificationToken = $user['verification_token'];
        $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . $verificationToken;
        
        $subject = 'Verify Your Email - FitZone Fitness Center';
        $body = "
        <html>
        <body>
            <h2>Verify Your Email Address</h2>
            <p>Hi {$user['first_name']},</p>
            <p>Thank you for registering with FitZone Fitness Center! To complete your registration, please verify your email address by clicking the link below:</p>
            
            <p><a href=\"{$verificationUrl}\" style=\"background: #e74c3c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;\">Verify Email Address</a></p>
            
            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
            <p>{$verificationUrl}</p>
            
            <p>This verification link will expire in 24 hours.</p>
            
            <p>If you didn't create an account with FitZone, please ignore this email.</p>
            
            <p>Best regards,<br>The FitZone Team</p>
        </body>
        </html>
        ";
        
        return sendEmail($user['email'], $subject, $body, true);
    } catch (Exception $e) {
        logError('Error sending email verification', ['error' => $e->getMessage()]);
        return false;
    }
}
?>
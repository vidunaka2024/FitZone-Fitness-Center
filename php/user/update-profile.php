<?php
// FitZone Fitness Center - Update User Profile
define('FITZONE_ACCESS', true);
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session and require login
session_start();
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', [], 405);
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    errorResponse('Invalid CSRF token', [], 403);
}

// Rate limiting
if (!checkRateLimit('profile_update', 5, 300)) { // 5 updates per 5 minutes
    errorResponse('Too many update requests. Please try again later.', [], 429);
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];

    // Get current user data
    $currentUser = getUserById($userId);
    if (!$currentUser) {
        errorResponse('User not found');
    }

    // Validate and sanitize input data
    $updateData = [];
    $profileData = [];
    $errors = [];

    // Basic user information
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '', 'email');
    $phone = sanitizeInput($_POST['phone'] ?? '');

    // Validate required fields
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    } else {
        $updateData['first_name'] = $firstName;
    }

    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    } else {
        $updateData['last_name'] = $lastName;
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } else if (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    } else if ($email !== $currentUser['email']) {
        // Check if email is already taken by another user
        $existingUser = $db->selectOne(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$email, $userId]
        );
        
        if ($existingUser) {
            $errors[] = 'This email address is already in use';
        } else {
            $updateData['email'] = $email;
            // If email changed, mark as unverified (optional)
            // $updateData['email_verified'] = 0;
        }
    }

    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = 'Please enter a valid phone number';
    } else {
        $updateData['phone'] = $phone;
    }

    // Profile specific fields
    $fitnessGoals = sanitizeInput($_POST['fitness_goals'] ?? '');
    if (!empty($fitnessGoals)) {
        $profileData['fitness_goals'] = $fitnessGoals;
    }

    $dateOfBirth = sanitizeInput($_POST['date_of_birth'] ?? '');
    if (!empty($dateOfBirth) && strtotime($dateOfBirth)) {
        $profileData['date_of_birth'] = $dateOfBirth;
    }

    $height = sanitizeInput($_POST['height'] ?? '');
    if (!empty($height)) {
        $profileData['height'] = $height;
    }

    $currentWeight = sanitizeInput($_POST['current_weight'] ?? '');
    if (!empty($currentWeight) && is_numeric($currentWeight)) {
        $profileData['current_weight'] = (float)$currentWeight;
    }

    $emergencyContact = sanitizeInput($_POST['emergency_contact'] ?? '');
    if (!empty($emergencyContact)) {
        $profileData['emergency_contact'] = $emergencyContact;
    }

    $emergencyPhone = sanitizeInput($_POST['emergency_phone'] ?? '');
    if (!empty($emergencyPhone)) {
        $profileData['emergency_phone'] = $emergencyPhone;
    }

    $medicalConditions = sanitizeInput($_POST['medical_conditions'] ?? '');
    if (!empty($medicalConditions)) {
        $profileData['medical_conditions'] = $medicalConditions;
    }

    if (!empty($errors)) {
        errorResponse('Validation failed', $errors);
    }

    // Begin transaction
    $db->getConnection()->beginTransaction();

    // Update basic user information if there are changes
    if (!empty($updateData)) {
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $updated = $db->update('users', $updateData, 'id = ?', [$userId]);
        if (!$updated) {
            throw new Exception('Failed to update user information');
        }
    }

    // Update or insert profile information
    if (!empty($profileData)) {
        // Check if profile exists
        $existingProfile = $db->selectOne(
            "SELECT id FROM user_profiles WHERE user_id = ?",
            [$userId]
        );

        $profileData['updated_at'] = date('Y-m-d H:i:s');

        if ($existingProfile) {
            // Update existing profile
            $updated = $db->update('user_profiles', $profileData, 'user_id = ?', [$userId]);
            if (!$updated) {
                throw new Exception('Failed to update profile information');
            }
        } else {
            // Create new profile
            $profileData['user_id'] = $userId;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            
            $profileId = $db->insert('user_profiles', $profileData);
            if (!$profileId) {
                throw new Exception('Failed to create profile information');
            }
        }
    }

    // Log the profile update
    $changedFields = array_merge(array_keys($updateData), array_keys($profileData));
    logActivity($userId, 'profile_updated', 'User updated their profile', [
        'changed_fields' => $changedFields,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $db->getConnection()->commit();

    successResponse('Profile updated successfully', [
        'updated_fields' => $changedFields
    ]);

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    
    logError('Profile update error', [
        'user_id' => $userId ?? null,
        'error' => $e->getMessage()
    ]);
    
    errorResponse('Failed to update profile. Please try again.');
}
?>
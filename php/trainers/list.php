<?php
// FitZone Fitness Center - Trainers List API

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Invalid request method', [], 405);
}

try {
    $db = getDB();
    
    // Get all active trainers with their profiles
    $trainers = $db->select("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.status,
            tp.specializations,
            tp.certifications,
            tp.experience_years,
            tp.hourly_rate,
            tp.bio,
            tp.rating,
            tp.total_reviews,
            tp.is_accepting_clients,
            u.profile_picture,
            tp.created_at
        FROM users u
        INNER JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.role = 'trainer' AND u.status = 'active'
        ORDER BY tp.rating DESC, tp.experience_years DESC
    ");
    
    // Process trainer data
    $processedTrainers = [];
    foreach ($trainers as $trainer) {
        // Parse JSON fields
        $specializations = json_decode($trainer['specializations'], true) ?? [];
        $certifications = json_decode($trainer['certifications'], true) ?? [];
        
        // Generate trainer title based on specializations
        $title = generateTrainerTitle($specializations);
        
        // Determine profile picture path
        $profilePicture = !empty($trainer['profile_picture']) 
            ? 'uploads/profile-pics/' . $trainer['profile_picture']
            : 'images/trainers/default.svg';
        
        $processedTrainers[] = [
            'id' => (int)$trainer['id'],
            'first_name' => $trainer['first_name'],
            'last_name' => $trainer['last_name'],
            'title' => $title,
            'specializations' => $specializations,
            'certifications' => $certifications,
            'experience_years' => (int)$trainer['experience_years'],
            'hourly_rate' => (float)$trainer['hourly_rate'],
            'bio' => $trainer['bio'],
            'profile_picture' => $profilePicture,
            'is_accepting_clients' => (bool)$trainer['is_accepting_clients'],
            'rating' => (float)$trainer['rating'],
            'total_reviews' => (int)$trainer['total_reviews']
        ];
    }
    
    successResponse('Trainers retrieved successfully', [
        'trainers' => $processedTrainers,
        'count' => count($processedTrainers)
    ]);
    
} catch (Exception $e) {
    logError('Trainers list error', [
        'error' => $e->getMessage()
    ]);
    
    errorResponse('Failed to retrieve trainers. Please try again.', [], 500);
}

/**
 * Generate trainer title based on specializations
 */
function generateTrainerTitle($specializations) {
    if (empty($specializations)) {
        return 'Personal Trainer';
    }
    
    // Common title mappings based on specializations
    $titleMappings = [
        'yoga' => 'Yoga & Wellness Specialist',
        'hatha yoga' => 'Yoga & Wellness Specialist',
        'vinyasa' => 'Yoga & Wellness Specialist',
        'pilates' => 'Pilates & Core Specialist',
        'crossfit' => 'CrossFit & Strength Coach',
        'powerlifting' => 'Strength & Conditioning Coach',
        'sports performance' => 'Sports Performance Specialist',
        'zumba' => 'Dance Fitness Instructor',
        'latin dance' => 'Dance Fitness Instructor',
        'cardio dance' => 'Dance Fitness Instructor',
        'hiit' => 'HIIT & Cardio Specialist',
        'spinning' => 'Cardio & Endurance Coach',
        'weight loss' => 'Weight Loss & Fitness Coach',
        'injury prevention' => 'Rehabilitation & Mobility Expert',
        'corrective exercise' => 'Rehabilitation & Mobility Expert',
        'senior fitness' => 'Senior Fitness Specialist',
        'functional movement' => 'Functional Training Coach',
        'trx' => 'Functional Training Coach',
        'bootcamp' => 'Bootcamp & Group Fitness Coach'
    ];
    
    // Find the most relevant title
    $firstSpec = strtolower($specializations[0] ?? '');
    foreach ($titleMappings as $keyword => $title) {
        if (strpos($firstSpec, $keyword) !== false) {
            return $title;
        }
    }
    
    // Default based on first specialization
    return ucfirst($firstSpec) . ' Specialist';
}
?>
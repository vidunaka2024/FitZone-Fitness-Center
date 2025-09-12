<?php
// FitZone Fitness Center - Personal Training Booking API

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Invalid request method', [], 405);
}

// Require user to be logged in
if (!isLoggedIn()) {
    errorResponse('Authentication required', [], 401);
}

// Rate limiting for training booking requests (generous for testing)
if (!checkRateLimit('training_booking', 20, 60)) { // 20 bookings per 1 minute
    errorResponse('Too many booking attempts. Please wait before trying again.', [], 429);
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    errorResponse('Invalid security token. Please refresh the page.', [], 403);
}

try {
    $userId = $_SESSION['user_id'];
    $trainerId = (int)($_POST['trainer_id'] ?? 0);
    $appointmentDate = sanitizeInput($_POST['appointment_date'] ?? '');
    $startTime = sanitizeInput($_POST['start_time'] ?? '');
    $endTime = sanitizeInput($_POST['end_time'] ?? '');
    $sessionFocus = sanitizeInput($_POST['session_focus'] ?? '');
    $appointmentType = sanitizeInput($_POST['appointment_type'] ?? 'individual');
    $location = sanitizeInput($_POST['location'] ?? 'gym_floor');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validate required fields
    if ($trainerId <= 0) {
        errorResponse('Invalid trainer selected', [], 400);
    }
    
    if (empty($appointmentDate) || !validateDate($appointmentDate)) {
        errorResponse('Invalid appointment date', [], 400);
    }
    
    if (empty($startTime) || empty($endTime)) {
        errorResponse('Start and end times are required', [], 400);
    }
    
    // Validate appointment type
    $validAppointmentTypes = ['individual', 'small_group', 'assessment', 'consultation'];
    if (!in_array($appointmentType, $validAppointmentTypes)) {
        $appointmentType = 'individual';
    }
    
    // Validate times
    if (strtotime($startTime) >= strtotime($endTime)) {
        errorResponse('End time must be after start time', [], 400);
    }
    
    // Check if appointment is in the future
    $appointmentDateTime = $appointmentDate . ' ' . $startTime;
    if (strtotime($appointmentDateTime) <= time()) {
        errorResponse('Appointment must be scheduled for a future date and time', [], 400);
    }
    
    $db = getDB();
    
    // Verify trainer exists and is active
    $trainer = $db->selectOne(
        "SELECT u.id, u.first_name, u.last_name, u.status, 
                tp.hourly_rate, tp.is_accepting_clients, tp.specializations,
                tp.availability
         FROM users u
         INNER JOIN trainer_profiles tp ON u.id = tp.user_id
         WHERE u.id = ? AND u.role = 'trainer' AND u.status = 'active'",
        [$trainerId]
    );
    
    if (!$trainer) {
        errorResponse('Trainer not found or not available for bookings', [], 404);
    }
    
    if (!$trainer['is_accepting_clients']) {
        errorResponse('This trainer is currently not accepting new clients', [], 403);
    }
    
    // Get user details for validation
    $user = getUserById($userId);
    if (!$user || $user['status'] !== 'active') {
        errorResponse('Your account is not active. Please contact support.', [], 403);
    }
    
    // Check for scheduling conflicts
    $conflictCheck = checkSchedulingConflicts($trainerId, $userId, $appointmentDate, $startTime, $endTime);
    if ($conflictCheck['hasConflict']) {
        errorResponse($conflictCheck['message'], [], 409);
    }
    
    // Check trainer availability for the requested time
    if (!isTrainerAvailable($trainerId, $appointmentDate, $startTime, $endTime)) {
        errorResponse('Trainer is not available at the requested time', [], 409);
    }
    
    // Calculate session duration and pricing
    $durationMinutes = (strtotime($endTime) - strtotime($startTime)) / 60;
    $price = calculateTrainingPrice($trainer, $appointmentType, $durationMinutes, $user['membership_plan']);
    
    // Validate minimum session duration (usually 30 minutes)
    if ($durationMinutes < 30) {
        errorResponse('Minimum session duration is 30 minutes', [], 400);
    }
    
    // Validate maximum session duration (usually 2 hours)
    if ($durationMinutes > 120) {
        errorResponse('Maximum session duration is 2 hours', [], 400);
    }
    
    // Begin transaction
    $db->getConnection()->beginTransaction();
    
    try {
        // Create the appointment
        $appointmentId = $db->insert('pt_appointments', [
            'client_id' => $userId,
            'trainer_id' => $trainerId,
            'appointment_date' => $appointmentDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_goals' => $sessionFocus,
            'session_type' => mapAppointmentTypeToSessionType($appointmentType),
            'location' => $location,
            'payment_amount' => $price,
            'status' => 'scheduled',
            'payment_status' => $price > 0 ? 'pending' : 'paid'
        ]);
        
        // Update trainer's schedule/availability if needed
        updateTrainerAvailability($trainerId, $appointmentDate, $startTime, $endTime, 'booked');
        
        // Log the booking activity
        logActivity($userId, 'training_booked', 
            'Booked personal training with ' . $trainer['first_name'] . ' ' . $trainer['last_name'], [
                'appointment_id' => $appointmentId,
                'trainer_id' => $trainerId,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $price,
                'appointment_type' => $appointmentType
            ]);
        
        // Send confirmation emails (if enabled)
        if (function_exists('sendTrainingBookingConfirmation')) {
            sendTrainingBookingConfirmation($user, $trainer, [
                'appointment_id' => $appointmentId,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'session_focus' => $sessionFocus,
                'price' => $price,
                'location' => $location
            ]);
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        
        successResponse('Personal training session booked successfully!', [
            'appointment_id' => $appointmentId,
            'trainer' => [
                'name' => $trainer['first_name'] . ' ' . $trainer['last_name'],
                'id' => $trainerId
            ],
            'appointment' => [
                'date' => date('M j, Y', strtotime($appointmentDate)),
                'start_time' => date('g:i A', strtotime($startTime)),
                'end_time' => date('g:i A', strtotime($endTime)),
                'duration_minutes' => $durationMinutes,
                'session_focus' => $sessionFocus,
                'type' => $appointmentType,
                'location' => $location
            ],
            'pricing' => [
                'price' => $price,
                'payment_required' => $price > 0
            ]
        ]);
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Training booking error', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? null,
        'trainer_id' => $trainerId ?? null
    ]);
    
    errorResponse('Booking failed. Please try again.', [], 500);
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
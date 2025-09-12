<?php
// FitZone Fitness Center - Class Booking API

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

// Rate limiting for booking requests
if (!checkRateLimit('class_booking', 10, 300)) { // 10 bookings per 5 minutes
    errorResponse('Too many booking attempts. Please wait before trying again.', [], 429);
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    errorResponse('Invalid security token. Please refresh the page.', [], 403);
}

try {
    $userId = $_SESSION['user_id'];
    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    $bookingType = sanitizeInput($_POST['booking_type'] ?? 'regular');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Log the booking attempt for debugging
    error_log("Class booking attempt - User: $userId, Schedule: $scheduleId, Type: $bookingType");
    
    // Validate required fields
    if ($scheduleId <= 0) {
        error_log("Invalid schedule ID: $scheduleId");
        errorResponse('Invalid class schedule', [], 400);
    }
    
    // Validate booking type
    $validBookingTypes = ['regular', 'drop_in', 'guest_pass'];
    if (!in_array($bookingType, $validBookingTypes)) {
        $bookingType = 'regular';
    }
    
    $db = getDB();
    
    // Get class schedule details with locks to prevent race conditions
    $schedule = $db->selectOne(
        "SELECT 
            cs.id, cs.class_id, cs.trainer_id, cs.date, cs.start_time, cs.end_time,
            cs.current_capacity, cs.wait_list_count, cs.status,
            c.name as class_name, c.max_capacity, c.type, c.difficulty_level,
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name
        FROM class_schedules cs
        INNER JOIN classes c ON cs.class_id = c.id
        INNER JOIN users t ON cs.trainer_id = t.id
        WHERE cs.id = ? AND cs.status = 'scheduled' AND cs.date >= CURDATE()
        FOR UPDATE",
        [$scheduleId]
    );
    
    if (!$schedule) {
        errorResponse('Class schedule not found or no longer available', [], 404);
    }
    
    // Check if user already has a booking for this class
    $existingBooking = $db->selectOne(
        "SELECT id, booking_status FROM class_bookings 
         WHERE user_id = ? AND schedule_id = ?",
        [$userId, $scheduleId]
    );
    
    if ($existingBooking) {
        if ($existingBooking['booking_status'] === 'confirmed') {
            errorResponse('You are already booked for this class', [], 409);
        } elseif ($existingBooking['booking_status'] === 'wait_listed') {
            errorResponse('You are already on the wait list for this class', [], 409);
        }
    }
    
    // Check if class is in the past or too close to start time
    $classDateTime = $schedule['date'] . ' ' . $schedule['start_time'];
    $classTimestamp = strtotime($classDateTime);
    $currentTimestamp = time();
    $minBookingTime = 2 * 3600; // 2 hours before class
    
    if ($classTimestamp <= ($currentTimestamp + $minBookingTime)) {
        errorResponse('Booking closes 2 hours before class start time', [], 400);
    }
    
    // Check user's membership status and booking eligibility
    $user = getUserById($userId);
    if (!$user || $user['status'] !== 'active') {
        errorResponse('Your account is not active. Please contact support.', [], 403);
    }
    
    // Calculate pricing
    $price = calculateBookingPrice($schedule, $user['membership_plan'], $bookingType);
    $paymentRequired = $price > 0;
    
    // Check if user has reached their monthly booking limit
    $bookingLimit = getUserBookingLimit($user['membership_plan']);
    if ($bookingLimit > 0) {
        $currentMonthBookings = getCurrentMonthBookings($userId);
        if ($currentMonthBookings >= $bookingLimit && $bookingType === 'regular') {
            errorResponse('You have reached your monthly booking limit. Consider upgrading your membership.', [], 403);
        }
    }
    
    // Determine booking status based on availability
    $bookingStatus = 'confirmed';
    $isWaitListed = false;
    
    if ($schedule['current_capacity'] >= $schedule['max_capacity']) {
        $bookingStatus = 'wait_listed';
        $isWaitListed = true;
    }
    
    // Begin transaction
    $db->getConnection()->beginTransaction();
    
    try {
        // Insert or update booking
        if ($existingBooking) {
            // Update existing cancelled booking
            $db->update('class_bookings', [
                'booking_status' => $bookingStatus,
                'booking_type' => $bookingType,
                'payment_required' => $paymentRequired ? 1 : 0,
                'payment_amount' => $price,
                'booking_date' => date('Y-m-d H:i:s'),
                'cancellation_date' => null,
                'notes' => $notes
            ], 'id = ?', [$existingBooking['id']]);
            
            $bookingId = $existingBooking['id'];
        } else {
            // Create new booking
            $bookingId = $db->insert('class_bookings', [
                'user_id' => $userId,
                'schedule_id' => $scheduleId,
                'booking_status' => $bookingStatus,
                'booking_type' => $bookingType,
                'payment_required' => $paymentRequired ? 1 : 0,
                'payment_amount' => $price,
                'booking_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
        }
        
        // Update schedule capacity counts
        if ($isWaitListed) {
            $db->update('class_schedules', [
                'wait_list_count' => $schedule['wait_list_count'] + 1
            ], 'id = ?', [$scheduleId]);
        } else {
            $db->update('class_schedules', [
                'current_capacity' => $schedule['current_capacity'] + 1
            ], 'id = ?', [$scheduleId]);
        }
        
        // Log the booking activity
        logActivity($userId, 'class_booked', 
            'Booked class: ' . $schedule['class_name'], [
                'schedule_id' => $scheduleId,
                'booking_id' => $bookingId,
                'booking_status' => $bookingStatus,
                'booking_type' => $bookingType,
                'price' => $price
            ]);
        
        // Send confirmation email (if enabled)
        if (function_exists('sendBookingConfirmationEmail')) {
            sendBookingConfirmationEmail($user, $schedule, [
                'booking_id' => $bookingId,
                'booking_status' => $bookingStatus,
                'price' => $price,
                'wait_listed' => $isWaitListed
            ]);
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        
        $responseMessage = $isWaitListed ? 
            'You have been added to the wait list for this class' : 
            'Class booked successfully!';
        
        successResponse($responseMessage, [
            'booking_id' => $bookingId,
            'booking_status' => $bookingStatus,
            'wait_listed' => $isWaitListed,
            'payment_required' => $paymentRequired,
            'payment_amount' => $price,
            'class_details' => [
                'name' => $schedule['class_name'],
                'date' => date('M j, Y', strtotime($schedule['date'])),
                'time' => date('g:i A', strtotime($schedule['start_time'])),
                'trainer' => $schedule['trainer_name']
            ]
        ]);
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Class booking error', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? null,
        'schedule_id' => $scheduleId ?? null
    ]);
    
    errorResponse('Booking failed. Please try again.', [], 500);
}

/**
 * Calculate booking price based on membership and booking type
 */
function calculateBookingPrice($schedule, $membershipPlan, $bookingType) {
    // Base pricing structure
    $pricing = [
        'basic' => ['regular' => 15.00, 'drop_in' => 20.00, 'guest_pass' => 25.00],
        'premium' => ['regular' => 12.00, 'drop_in' => 18.00, 'guest_pass' => 22.00],
        'elite' => ['regular' => 0.00, 'drop_in' => 10.00, 'guest_pass' => 15.00]
    ];
    
    $basePrice = $pricing[$membershipPlan][$bookingType] ?? $pricing['basic'][$bookingType];
    
    // Apply class type multipliers if needed
    $typeMultipliers = [
        'martial_arts' => 1.2,
        'dance' => 1.1,
        'mind_body' => 1.0,
        'cardio' => 1.0,
        'strength' => 1.0,
        'flexibility' => 0.9,
        'water' => 1.3
    ];
    
    $multiplier = $typeMultipliers[$schedule['type']] ?? 1.0;
    
    return round($basePrice * $multiplier, 2);
}

/**
 * Get user's monthly booking limit based on membership plan
 */
function getUserBookingLimit($membershipPlan) {
    $limits = [
        'basic' => 8,      // 8 classes per month
        'premium' => 20,   // 20 classes per month  
        'elite' => 0       // Unlimited
    ];
    
    return $limits[$membershipPlan] ?? $limits['basic'];
}

/**
 * Get current month bookings count for user
 */
function getCurrentMonthBookings($userId) {
    try {
        $db = getDB();
        $result = $db->selectOne(
            "SELECT COUNT(*) as count FROM class_bookings cb
             INNER JOIN class_schedules cs ON cb.schedule_id = cs.id
             WHERE cb.user_id = ? 
                AND cb.booking_status = 'confirmed'
                AND cb.booking_type = 'regular'
                AND YEAR(cs.date) = YEAR(CURDATE())
                AND MONTH(cs.date) = MONTH(CURDATE())",
            [$userId]
        );
        
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        logError('Error getting monthly bookings', ['error' => $e->getMessage()]);
        return 0;
    }
}
?>
<?php
// FitZone Fitness Center - User Appointments API

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

// Require user to be logged in
if (!isLoggedIn()) {
    errorResponse('Authentication required', [], 401);
}

// Rate limiting for appointments requests
if (!checkRateLimit('appointments', 30, 60)) { // 30 requests per minute
    errorResponse('Too many requests. Please slow down.', [], 429);
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get query parameters
    $type = sanitizeInput($_GET['type'] ?? 'all'); // 'classes', 'training', 'all'
    $status = sanitizeInput($_GET['status'] ?? 'all'); // 'upcoming', 'past', 'cancelled', 'all'
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    
    $db = getDB();
    $appointments = [];
    $totalCount = 0;
    
    // Get class bookings
    if ($type === 'all' || $type === 'classes') {
        $classBookings = getClassBookings($userId, $status, $limit, $offset);
        $appointments = array_merge($appointments, $classBookings);
    }
    
    // Get personal training appointments
    if ($type === 'all' || $type === 'training') {
        $trainingAppointments = getTrainingAppointments($userId, $status, $limit, $offset);
        $appointments = array_merge($appointments, $trainingAppointments);
    }
    
    // Sort appointments by date and time
    usort($appointments, function($a, $b) {
        $dateTimeA = $a['date'] . ' ' . $a['start_time'];
        $dateTimeB = $b['date'] . ' ' . $b['start_time'];
        return strtotime($dateTimeA) - strtotime($dateTimeB);
    });
    
    // Apply pagination to combined results
    $totalCount = count($appointments);
    $appointments = array_slice($appointments, $offset, $limit);
    
    // Get user statistics
    $stats = getUserAppointmentStats($userId);
    
    successResponse('Appointments retrieved successfully', [
        'appointments' => $appointments,
        'statistics' => $stats,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ],
        'filters' => [
            'type' => $type,
            'status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    logError('User appointments error', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? null
    ]);
    
    errorResponse('Failed to retrieve appointments. Please try again.', [], 500);
}

/**
 * Get class bookings for user
 */
function getClassBookings($userId, $status, $limit, $offset) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT 
                cb.id as booking_id,
                cb.booking_status,
                cb.booking_type,
                cb.booking_date,
                cb.cancellation_date,
                cb.payment_amount,
                cb.notes,
                cs.id as schedule_id,
                cs.date,
                cs.start_time,
                cs.end_time,
                cs.room,
                cs.current_capacity,
                c.id as class_id,
                c.name as title,
                c.description,
                c.type,
                c.difficulty_level,
                c.duration_minutes,
                c.max_capacity,
                c.image_url,
                CONCAT(t.first_name, ' ', t.last_name) as instructor_name,
                t.id as instructor_id,
                tp.rating as instructor_rating,
                'class' as appointment_type
            FROM class_bookings cb
            INNER JOIN class_schedules cs ON cb.schedule_id = cs.id
            INNER JOIN classes c ON cs.class_id = c.id
            INNER JOIN users t ON cs.trainer_id = t.id
            LEFT JOIN trainer_profiles tp ON t.id = tp.user_id
            WHERE cb.user_id = ?
        ";
        
        $params = [$userId];
        
        // Add status filter
        switch ($status) {
            case 'upcoming':
                $sql .= " AND cs.date >= CURDATE() AND cb.booking_status IN ('confirmed', 'wait_listed')";
                break;
            case 'past':
                $sql .= " AND (cs.date < CURDATE() OR cs.status = 'completed')";
                break;
            case 'cancelled':
                $sql .= " AND cb.booking_status = 'cancelled'";
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }
        
        $sql .= " ORDER BY cs.date DESC, cs.start_time DESC";
        
        $bookings = $db->select($sql, $params);
        
        // Process bookings
        foreach ($bookings as &$booking) {
            // Format dates and times
            $booking['formatted_date'] = date('M j, Y', strtotime($booking['date']));
            $booking['day_of_week'] = date('l', strtotime($booking['date']));
            $booking['start_time'] = date('H:i', strtotime($booking['start_time']));
            $booking['end_time'] = date('H:i', strtotime($booking['end_time']));
            
            // Determine if booking is in the past, upcoming, or current
            $bookingDateTime = strtotime($booking['date'] . ' ' . $booking['start_time']);
            $currentTime = time();
            
            if ($bookingDateTime < $currentTime) {
                $booking['time_status'] = 'past';
            } elseif ($bookingDateTime <= ($currentTime + 3600)) { // Within 1 hour
                $booking['time_status'] = 'current';
            } else {
                $booking['time_status'] = 'upcoming';
            }
            
            // Add cancellation eligibility
            $booking['can_cancel'] = canCancelBooking($booking, $currentTime);
            
            // Add check-in status if applicable (disabled until table exists)
            $booking['check_in_status'] = null; // getCheckInStatus($booking['booking_id']);
            
            // Clean up data
            unset($booking['notes']); // Don't expose internal notes
        }
        
        return $bookings;
    } catch (Exception $e) {
        logError('Error getting class bookings', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Get personal training appointments for user
 */
function getTrainingAppointments($userId, $status, $limit, $offset) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT 
                pta.id as booking_id,
                pta.status as booking_status,
                'training' as booking_type,
                pta.created_at as booking_date,
                pta.updated_at as cancellation_date,
                pta.payment_amount,
                pta.trainer_notes as notes,
                pta.session_goals as session_focus,
                pta.id as schedule_id,
                pta.appointment_date as date,
                pta.start_time,
                pta.end_time,
                pta.location as room,
                1 as current_capacity,
                1 as max_capacity,
                CONCAT('Personal Training - ', pta.session_goals) as title,
                pta.session_goals as description,
                'personal_training' as type,
                'individual' as difficulty_level,
                TIMESTAMPDIFF(MINUTE, pta.start_time, pta.end_time) as duration_minutes,
                NULL as image_url,
                CONCAT(t.first_name, ' ', t.last_name) as instructor_name,
                t.id as instructor_id,
                tp.rating as instructor_rating,
                'training' as appointment_type
            FROM pt_appointments pta
            INNER JOIN users t ON pta.trainer_id = t.id
            LEFT JOIN trainer_profiles tp ON t.id = tp.user_id
            WHERE pta.client_id = ?
        ";
        
        $params = [$userId];
        
        // Add status filter
        switch ($status) {
            case 'upcoming':
                $sql .= " AND pta.appointment_date >= CURDATE() AND pta.status IN ('confirmed', 'scheduled')";
                break;
            case 'past':
                $sql .= " AND (pta.appointment_date < CURDATE() OR pta.status = 'completed')";
                break;
            case 'cancelled':
                $sql .= " AND pta.status = 'cancelled'";
                break;
            case 'all':
            default:
                // No additional filter
                break;
        }
        
        $sql .= " ORDER BY pta.appointment_date DESC, pta.start_time DESC";
        
        $appointments = $db->select($sql, $params);
        
        // Process appointments
        foreach ($appointments as &$appointment) {
            // Format dates and times
            $appointment['formatted_date'] = date('M j, Y', strtotime($appointment['date']));
            $appointment['day_of_week'] = date('l', strtotime($appointment['date']));
            $appointment['start_time'] = date('H:i', strtotime($appointment['start_time']));
            $appointment['end_time'] = date('H:i', strtotime($appointment['end_time']));
            
            // Determine time status
            $appointmentDateTime = strtotime($appointment['date'] . ' ' . $appointment['start_time']);
            $currentTime = time();
            
            if ($appointmentDateTime < $currentTime) {
                $appointment['time_status'] = 'past';
            } elseif ($appointmentDateTime <= ($currentTime + 3600)) {
                $appointment['time_status'] = 'current';
            } else {
                $appointment['time_status'] = 'upcoming';
            }
            
            // Add cancellation eligibility
            $appointment['can_cancel'] = canCancelTrainingAppointment($appointment, $currentTime);
            
            // Training appointments don't have check-in status
            $appointment['check_in_status'] = null;
        }
        
        return $appointments;
    } catch (Exception $e) {
        logError('Error getting training appointments', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Check if booking can be cancelled
 */
function canCancelBooking($booking, $currentTime) {
    if ($booking['booking_status'] === 'cancelled') {
        return false;
    }
    
    if ($booking['time_status'] === 'past' || $booking['time_status'] === 'current') {
        return false;
    }
    
    // Check if it's more than 4 hours before class
    $bookingDateTime = strtotime($booking['date'] . ' ' . $booking['start_time']);
    $hoursUntilClass = ($bookingDateTime - $currentTime) / 3600;
    
    return $hoursUntilClass >= 4;
}

/**
 * Check if training appointment can be cancelled
 */
function canCancelTrainingAppointment($appointment, $currentTime) {
    if ($appointment['booking_status'] === 'cancelled') {
        return false;
    }
    
    if ($appointment['time_status'] === 'past' || $appointment['time_status'] === 'current') {
        return false;
    }
    
    // Check if it's more than 24 hours before appointment
    $appointmentDateTime = strtotime($appointment['date'] . ' ' . $appointment['start_time']);
    $hoursUntilAppointment = ($appointmentDateTime - $currentTime) / 3600;
    
    return $hoursUntilAppointment >= 24;
}

/**
 * Get check-in status for a booking
 */
function getCheckInStatus($bookingId) {
    try {
        $db = getDB();
        $checkIn = $db->selectOne(
            "SELECT status, checked_in_at FROM class_check_ins WHERE booking_id = ?",
            [$bookingId]
        );
        
        return $checkIn ? [
            'status' => $checkIn['status'],
            'checked_in_at' => $checkIn['checked_in_at']
        ] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get user appointment statistics
 */
function getUserAppointmentStats($userId) {
    try {
        $db = getDB();
        
        // Get class booking stats
        $classStats = $db->selectOne("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN cb.booking_status = 'confirmed' AND cs.date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_classes,
                SUM(CASE WHEN cb.booking_status = 'confirmed' AND cs.date < CURDATE() THEN 1 ELSE 0 END) as completed_classes,
                SUM(CASE WHEN cb.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_classes,
                SUM(CASE WHEN cb.booking_status = 'wait_listed' THEN 1 ELSE 0 END) as waitlisted_classes
            FROM class_bookings cb
            INNER JOIN class_schedules cs ON cb.schedule_id = cs.id
            WHERE cb.user_id = ?
        ", [$userId]);
        
        // Get training appointment stats
        $trainingStats = $db->selectOne("
            SELECT 
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status IN ('confirmed', 'scheduled') AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_training,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_training,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_training
            FROM pt_appointments 
            WHERE client_id = ?
        ", [$userId]);
        
        // Get this month's activity
        $monthlyStats = $db->selectOne("
            SELECT 
                COUNT(DISTINCT cb.id) as classes_this_month,
                COUNT(DISTINCT pta.id) as training_this_month
            FROM class_bookings cb
            LEFT JOIN class_schedules cs ON cb.schedule_id = cs.id
            RIGHT JOIN pt_appointments pta ON pta.user_id = cb.user_id
            WHERE cb.user_id = ? 
                AND cb.booking_status = 'confirmed'
                AND (
                    (YEAR(cs.date) = YEAR(CURDATE()) AND MONTH(cs.date) = MONTH(CURDATE()))
                    OR (YEAR(pta.appointment_date) = YEAR(CURDATE()) AND MONTH(pta.appointment_date) = MONTH(CURDATE()))
                )
        ", [$userId]);
        
        return [
            'classes' => [
                'total' => (int)($classStats['total_bookings'] ?? 0),
                'upcoming' => (int)($classStats['upcoming_classes'] ?? 0),
                'completed' => (int)($classStats['completed_classes'] ?? 0),
                'cancelled' => (int)($classStats['cancelled_classes'] ?? 0),
                'waitlisted' => (int)($classStats['waitlisted_classes'] ?? 0)
            ],
            'training' => [
                'total' => (int)($trainingStats['total_appointments'] ?? 0),
                'upcoming' => (int)($trainingStats['upcoming_training'] ?? 0),
                'completed' => (int)($trainingStats['completed_training'] ?? 0),
                'cancelled' => (int)($trainingStats['cancelled_training'] ?? 0)
            ],
            'monthly' => [
                'classes' => (int)($monthlyStats['classes_this_month'] ?? 0),
                'training' => (int)($monthlyStats['training_this_month'] ?? 0)
            ]
        ];
    } catch (Exception $e) {
        logError('Error getting appointment stats', ['error' => $e->getMessage()]);
        return [
            'classes' => ['total' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0, 'waitlisted' => 0],
            'training' => ['total' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0],
            'monthly' => ['classes' => 0, 'training' => 0]
        ];
    }
}
?>
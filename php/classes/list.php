<?php
// FitZone Fitness Center - Classes List API

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request method is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Invalid request method', [], 405);
}

// Rate limiting for class list requests
if (!checkRateLimit('class_list', 30, 60)) { // 30 requests per minute
    errorResponse('Too many requests. Please slow down.', [], 429);
}

try {
    // Get query parameters
    $type = sanitizeInput($_GET['type'] ?? '');
    $level = sanitizeInput($_GET['level'] ?? '');
    $date = sanitizeInput($_GET['date'] ?? '');
    $trainer_id = (int)($_GET['trainer_id'] ?? 0);
    $available_only = filter_var($_GET['available_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    
    // Build the main query
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.description,
            c.type,
            c.difficulty_level,
            c.duration_minutes,
            c.max_capacity,
            c.calories_burned_estimate,
            c.image_url,
            cs.id as schedule_id,
            cs.date,
            cs.start_time,
            cs.end_time,
            cs.room,
            cs.current_capacity,
            cs.wait_list_count,
            cs.status as schedule_status,
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name,
            t.id as trainer_id,
            tp.specializations,
            tp.rating as trainer_rating,
            (c.max_capacity - cs.current_capacity) as spots_available,
            CASE 
                WHEN cs.current_capacity >= c.max_capacity THEN 'full'
                WHEN cs.current_capacity >= (c.max_capacity * 0.8) THEN 'almost_full'
                ELSE 'available'
            END as availability_status
        FROM classes c
        INNER JOIN class_schedules cs ON c.id = cs.class_id
        INNER JOIN users t ON cs.trainer_id = t.id
        LEFT JOIN trainer_profiles tp ON t.id = tp.user_id
        WHERE c.status = 'active' 
            AND cs.status = 'scheduled'
            AND cs.date >= CURDATE()
    ";
    
    $params = [];
    
    // Add filters
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    
    if (!empty($level)) {
        $sql .= " AND (c.difficulty_level = ? OR c.difficulty_level = 'all_levels')";
        $params[] = $level;
    }
    
    if (!empty($date)) {
        $sql .= " AND cs.date = ?";
        $params[] = $date;
    }
    
    if ($trainer_id > 0) {
        $sql .= " AND cs.trainer_id = ?";
        $params[] = $trainer_id;
    }
    
    if ($available_only) {
        $sql .= " AND cs.current_capacity < c.max_capacity";
    }
    
    // Add ordering and pagination
    $sql .= " ORDER BY cs.date ASC, cs.start_time ASC, c.name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $db = getDB();
    $classes = $db->select($sql, $params);
    
    // Get total count for pagination
    $countSql = "
        SELECT COUNT(*) as total
        FROM classes c
        INNER JOIN class_schedules cs ON c.id = cs.class_id
        INNER JOIN users t ON cs.trainer_id = t.id
        WHERE c.status = 'active' 
            AND cs.status = 'scheduled'
            AND cs.date >= CURDATE()
    ";
    
    $countParams = [];
    if (!empty($type)) {
        $countSql .= " AND c.type = ?";
        $countParams[] = $type;
    }
    if (!empty($level)) {
        $countSql .= " AND (c.difficulty_level = ? OR c.difficulty_level = 'all_levels')";
        $countParams[] = $level;
    }
    if (!empty($date)) {
        $countSql .= " AND cs.date = ?";
        $countParams[] = $date;
    }
    if ($trainer_id > 0) {
        $countSql .= " AND cs.trainer_id = ?";
        $countParams[] = $trainer_id;
    }
    if ($available_only) {
        $countSql .= " AND cs.current_capacity < c.max_capacity";
    }
    
    $totalResult = $db->selectOne($countSql, $countParams);
    $totalCount = $totalResult['total'] ?? 0;
    
    // Process results
    foreach ($classes as &$class) {
        // Parse specializations
        if (!empty($class['specializations'])) {
            $class['specializations'] = json_decode($class['specializations'], true) ?: [];
        } else {
            $class['specializations'] = [];
        }
        
        // Format time
        $class['start_time'] = date('H:i', strtotime($class['start_time']));
        $class['end_time'] = date('H:i', strtotime($class['end_time']));
        
        // Format date
        $class['formatted_date'] = date('M j, Y', strtotime($class['date']));
        $class['day_of_week'] = date('l', strtotime($class['date']));
        
        // Add booking status for logged-in users
        if (isset($_SESSION['user_id'])) {
            $class['user_booking_status'] = getUserClassBookingStatus($_SESSION['user_id'], $class['schedule_id']);
        } else {
            $class['user_booking_status'] = 'not_logged_in';
        }
        
        // Calculate pricing (if applicable)
        $class['price'] = calculateClassPrice($class['id'], $_SESSION['membership_plan'] ?? 'basic');
    }
    
    // Get class types for filters
    $classTypes = getClassTypes();
    
    successResponse('Classes retrieved successfully', [
        'classes' => $classes,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ],
        'filters' => [
            'types' => $classTypes,
            'levels' => ['beginner', 'intermediate', 'advanced', 'all_levels'],
            'applied' => [
                'type' => $type,
                'level' => $level,
                'date' => $date,
                'trainer_id' => $trainer_id,
                'available_only' => $available_only
            ]
        ]
    ]);
    
} catch (Exception $e) {
    logError('Classes list error', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    errorResponse('Failed to retrieve classes. Please try again.', [], 500);
}

/**
 * Get user's booking status for a specific class schedule
 */
function getUserClassBookingStatus($userId, $scheduleId) {
    try {
        $db = getDB();
        $booking = $db->selectOne(
            "SELECT booking_status FROM class_bookings 
             WHERE user_id = ? AND schedule_id = ? 
             ORDER BY booking_date DESC LIMIT 1",
            [$userId, $scheduleId]
        );
        
        return $booking ? $booking['booking_status'] : 'not_booked';
    } catch (Exception $e) {
        logError('Error checking booking status', ['error' => $e->getMessage()]);
        return 'unknown';
    }
}

/**
 * Calculate class price based on membership plan
 */
function calculateClassPrice($classId, $membershipPlan) {
    // Default pricing structure
    $basePrices = [
        'basic' => 15.00,
        'premium' => 12.00,
        'elite' => 0.00  // Free for elite members
    ];
    
    return $basePrices[$membershipPlan] ?? $basePrices['basic'];
}

/**
 * Get available class types
 */
function getClassTypes() {
    try {
        $db = getDB();
        $types = $db->select("SELECT DISTINCT type FROM classes WHERE status = 'active' ORDER BY type");
        return array_column($types, 'type');
    } catch (Exception $e) {
        return ['cardio', 'strength', 'flexibility', 'dance', 'martial_arts', 'water', 'mind_body', 'other'];
    }
}
?>
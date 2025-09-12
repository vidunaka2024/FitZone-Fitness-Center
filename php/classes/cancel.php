<?php
// FitZone Fitness Center - Class Booking Cancellation API

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

// Rate limiting for cancellation requests
if (!checkRateLimit('class_cancellation', 15, 300)) { // 15 cancellations per 5 minutes
    errorResponse('Too many cancellation attempts. Please wait before trying again.', [], 429);
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    errorResponse('Invalid security token. Please refresh the page.', [], 403);
}

try {
    $userId = $_SESSION['user_id'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    // Validate required fields
    if ($bookingId <= 0) {
        errorResponse('Invalid booking ID', [], 400);
    }
    
    $db = getDB();
    
    // Get booking details with lock to prevent race conditions
    $booking = $db->selectOne(
        "SELECT 
            cb.id, cb.user_id, cb.schedule_id, cb.booking_status, cb.booking_type,
            cb.payment_amount, cb.booking_date,
            cs.date, cs.start_time, cs.current_capacity, cs.wait_list_count,
            c.name as class_name, c.max_capacity,
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name
        FROM class_bookings cb
        INNER JOIN class_schedules cs ON cb.schedule_id = cs.id
        INNER JOIN classes c ON cs.class_id = c.id
        INNER JOIN users t ON cs.trainer_id = t.id
        WHERE cb.id = ? AND cb.user_id = ?
        FOR UPDATE",
        [$bookingId, $userId]
    );
    
    if (!$booking) {
        errorResponse('Booking not found or you do not have permission to cancel it', [], 404);
    }
    
    // Check if booking is already cancelled
    if ($booking['booking_status'] === 'cancelled') {
        errorResponse('This booking is already cancelled', [], 409);
    }
    
    // Check if it's too late to cancel (e.g., within 4 hours of class start)
    $classDateTime = $booking['date'] . ' ' . $booking['start_time'];
    $classTimestamp = strtotime($classDateTime);
    $currentTimestamp = time();
    $minCancellationTime = 4 * 3600; // 4 hours before class
    
    if ($classTimestamp <= ($currentTimestamp + $minCancellationTime)) {
        errorResponse('Cancellation is not allowed within 4 hours of class start time', [], 400);
    }
    
    // Calculate refund amount based on cancellation policy
    $refundAmount = calculateRefundAmount($booking, $currentTimestamp, $classTimestamp);
    $refundPolicy = getRefundPolicy($booking['booking_type']);
    
    // Begin transaction
    $db->getConnection()->beginTransaction();
    
    try {
        // Update booking status
        $db->update('class_bookings', [
            'booking_status' => 'cancelled',
            'cancellation_date' => date('Y-m-d H:i:s'),
            'notes' => $booking['notes'] . "\nCancellation reason: " . $reason
        ], 'id = ?', [$bookingId]);
        
        // Update schedule capacity counts
        if ($booking['booking_status'] === 'confirmed') {
            // Decrease current capacity
            $db->update('class_schedules', [
                'current_capacity' => max(0, $booking['current_capacity'] - 1)
            ], 'id = ?', [$booking['schedule_id']]);
            
            // Move someone from wait list if available
            $waitListBooking = getNextWaitListBooking($booking['schedule_id']);
            if ($waitListBooking) {
                moveFromWaitListToConfirmed($waitListBooking, $db);
            }
            
        } elseif ($booking['booking_status'] === 'wait_listed') {
            // Decrease wait list count
            $db->update('class_schedules', [
                'wait_list_count' => max(0, $booking['wait_list_count'] - 1)
            ], 'id = ?', [$booking['schedule_id']]);
        }
        
        // Process refund if applicable
        $refundProcessed = false;
        if ($refundAmount > 0) {
            $refundProcessed = processRefund($userId, $bookingId, $refundAmount);
        }
        
        // Log the cancellation activity
        logActivity($userId, 'class_cancelled', 
            'Cancelled booking for: ' . $booking['class_name'], [
                'booking_id' => $bookingId,
                'schedule_id' => $booking['schedule_id'],
                'refund_amount' => $refundAmount,
                'refund_processed' => $refundProcessed,
                'reason' => $reason
            ]);
        
        // Send cancellation confirmation email (if enabled)
        if (function_exists('sendCancellationConfirmationEmail')) {
            sendCancellationConfirmationEmail($userId, $booking, [
                'refund_amount' => $refundAmount,
                'refund_processed' => $refundProcessed,
                'reason' => $reason
            ]);
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        
        $responseMessage = $refundAmount > 0 ? 
            'Booking cancelled successfully. Your refund of $' . number_format($refundAmount, 2) . ' will be processed within 3-5 business days.' :
            'Booking cancelled successfully.';
        
        successResponse($responseMessage, [
            'booking_id' => $bookingId,
            'refund_amount' => $refundAmount,
            'refund_processed' => $refundProcessed,
            'refund_policy' => $refundPolicy,
            'class_details' => [
                'name' => $booking['class_name'],
                'date' => date('M j, Y', strtotime($booking['date'])),
                'time' => date('g:i A', strtotime($booking['start_time'])),
                'trainer' => $booking['trainer_name']
            ]
        ]);
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Class cancellation error', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? null,
        'booking_id' => $bookingId ?? null
    ]);
    
    errorResponse('Cancellation failed. Please try again.', [], 500);
}

/**
 * Calculate refund amount based on timing and booking type
 */
function calculateRefundAmount($booking, $currentTimestamp, $classTimestamp) {
    $hoursUntilClass = ($classTimestamp - $currentTimestamp) / 3600;
    $originalAmount = $booking['payment_amount'];
    
    if ($originalAmount <= 0) {
        return 0; // No payment, no refund
    }
    
    // Refund policy based on hours until class
    if ($hoursUntilClass >= 48) {
        // 48+ hours: Full refund
        return $originalAmount;
    } elseif ($hoursUntilClass >= 24) {
        // 24-48 hours: 75% refund
        return $originalAmount * 0.75;
    } elseif ($hoursUntilClass >= 12) {
        // 12-24 hours: 50% refund
        return $originalAmount * 0.50;
    } elseif ($hoursUntilClass >= 4) {
        // 4-12 hours: 25% refund
        return $originalAmount * 0.25;
    } else {
        // Less than 4 hours: No refund (but this case is blocked above)
        return 0;
    }
}

/**
 * Get refund policy description
 */
function getRefundPolicy($bookingType) {
    return [
        'full_refund_hours' => 48,
        'partial_refund_tiers' => [
            ['hours' => 48, 'percentage' => 100],
            ['hours' => 24, 'percentage' => 75],
            ['hours' => 12, 'percentage' => 50],
            ['hours' => 4, 'percentage' => 25]
        ],
        'minimum_cancellation_hours' => 4,
        'processing_time_days' => '3-5 business days'
    ];
}

/**
 * Get next booking from wait list
 */
function getNextWaitListBooking($scheduleId) {
    try {
        $db = getDB();
        return $db->selectOne(
            "SELECT cb.id, cb.user_id, u.first_name, u.last_name, u.email
             FROM class_bookings cb
             INNER JOIN users u ON cb.user_id = u.id
             WHERE cb.schedule_id = ? AND cb.booking_status = 'wait_listed'
             ORDER BY cb.booking_date ASC
             LIMIT 1",
            [$scheduleId]
        );
    } catch (Exception $e) {
        logError('Error getting wait list booking', ['error' => $e->getMessage()]);
        return null;
    }
}

/**
 * Move booking from wait list to confirmed
 */
function moveFromWaitListToConfirmed($waitListBooking, $db) {
    try {
        // Update booking status
        $db->update('class_bookings', [
            'booking_status' => 'confirmed'
        ], 'id = ?', [$waitListBooking['id']]);
        
        // Update schedule counts
        $db->update('class_schedules', [
            'current_capacity' => 'current_capacity + 1',
            'wait_list_count' => 'wait_list_count - 1'
        ], 'id = ?', [$scheduleId]);
        
        // Log the promotion
        logActivity($waitListBooking['user_id'], 'wait_list_promoted', 
            'Moved from wait list to confirmed booking');
        
        // Send notification email (if enabled)
        if (function_exists('sendWaitListPromotionEmail')) {
            sendWaitListPromotionEmail($waitListBooking);
        }
        
        return true;
    } catch (Exception $e) {
        logError('Error moving from wait list', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Process refund (placeholder for payment gateway integration)
 */
function processRefund($userId, $bookingId, $refundAmount) {
    try {
        // This would integrate with your payment processor
        // For now, just log the refund request
        logActivity($userId, 'refund_requested', 
            'Refund requested for booking', [
                'booking_id' => $bookingId,
                'refund_amount' => $refundAmount
            ]);
        
        // In a real implementation, you would:
        // 1. Call payment gateway API to process refund
        // 2. Update user's account balance or credit
        // 3. Send refund confirmation
        
        return true; // Assume success for now
    } catch (Exception $e) {
        logError('Error processing refund', ['error' => $e->getMessage()]);
        return false;
    }
}
?>
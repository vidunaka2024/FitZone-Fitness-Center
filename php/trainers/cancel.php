<?php
// FitZone Fitness Center - Cancel Personal Training Appointment
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
if (!checkRateLimit('training_cancel', 10, 900)) { // 10 cancellations per 15 minutes
    errorResponse('Too many cancellation requests. Please try again later.', [], 429);
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = sanitizeInput($_POST['reason'] ?? '');

    if (empty($bookingId)) {
        errorResponse('Booking ID is required');
    }

    // Get appointment details
    $appointment = $db->selectOne("
        SELECT 
            pta.*,
            CONCAT(t.first_name, ' ', t.last_name) as trainer_name
        FROM pt_appointments pta 
        INNER JOIN users t ON pta.trainer_id = t.id
        WHERE pta.id = ? AND pta.user_id = ?
    ", [$bookingId, $userId]);

    if (!$appointment) {
        errorResponse('Training appointment not found or access denied');
    }

    // Check if appointment can be cancelled
    $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
    $appointmentTimestamp = strtotime($appointmentDateTime);
    $currentTimestamp = time();
    $hoursUntilAppointment = ($appointmentTimestamp - $currentTimestamp) / 3600;

    if ($appointment['status'] === 'cancelled') {
        errorResponse('This appointment is already cancelled');
    }

    if ($appointment['status'] === 'completed') {
        errorResponse('Cannot cancel a completed appointment');
    }

    if ($appointmentTimestamp <= $currentTimestamp) {
        errorResponse('Cannot cancel appointments that have already started');
    }

    // Calculate refund amount based on cancellation policy
    $refundAmount = 0;
    $refundPolicy = 'no_refund';

    if ($hoursUntilAppointment >= 24) {
        // Full refund for cancellations 24+ hours in advance
        $refundAmount = $appointment['total_price'];
        $refundPolicy = 'full_refund';
    } else if ($hoursUntilAppointment >= 4) {
        // 50% refund for cancellations 4-24 hours in advance
        $refundAmount = $appointment['total_price'] * 0.5;
        $refundPolicy = 'partial_refund';
    }
    // No refund for cancellations less than 4 hours in advance

    // Begin transaction
    $db->getConnection()->beginTransaction();

    // Update appointment status
    $updated = $db->update('pt_appointments', [
        'status' => 'cancelled',
        'cancelled_at' => date('Y-m-d H:i:s'),
        'cancellation_reason' => $reason ?: 'User cancelled',
        'refund_amount' => $refundAmount,
        'refund_policy' => $refundPolicy,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ? AND user_id = ?', [$bookingId, $userId]);

    if (!$updated) {
        throw new Exception('Failed to cancel appointment');
    }

    // Log the cancellation activity
    logActivity($userId, 'training_cancelled', "Cancelled personal training with {$appointment['trainer_name']}", [
        'appointment_id' => $bookingId,
        'trainer_id' => $appointment['trainer_id'],
        'appointment_date' => $appointment['appointment_date'],
        'refund_amount' => $refundAmount,
        'refund_policy' => $refundPolicy,
        'reason' => $reason
    ]);

    // If there's a refund, you might want to process it here
    // For now, we'll just note it in the response
    $refundMessage = '';
    if ($refundAmount > 0) {
        switch ($refundPolicy) {
            case 'full_refund':
                $refundMessage = " A full refund of $" . number_format($refundAmount, 2) . " will be processed within 3-5 business days.";
                break;
            case 'partial_refund':
                $refundMessage = " A partial refund of $" . number_format($refundAmount, 2) . " will be processed within 3-5 business days.";
                break;
        }
    }

    $db->getConnection()->commit();

    $message = "Personal training appointment with {$appointment['trainer_name']} has been cancelled successfully.{$refundMessage}";

    successResponse($message, [
        'booking_id' => $bookingId,
        'refund_amount' => $refundAmount,
        'refund_policy' => $refundPolicy
    ]);

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    
    logError('Training cancellation error', [
        'user_id' => $userId ?? null,
        'booking_id' => $bookingId ?? null,
        'error' => $e->getMessage()
    ]);
    
    errorResponse('Failed to cancel appointment. Please try again.');
}
?>
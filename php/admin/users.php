<?php
// FitZone Fitness Center - Admin User Management API

session_start();
define('FITZONE_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/UserManager.php';

// Require admin access
requireAdmin();

// Set content type for JSON responses
header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$userManager = new UserManager();
$adminId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $userManager);
            break;
            
        case 'POST':
            handlePostRequest($action, $userManager, $adminId);
            break;
            
        case 'PUT':
            handlePutRequest($action, $userManager, $adminId);
            break;
            
        case 'DELETE':
            handleDeleteRequest($action, $userManager, $adminId);
            break;
            
        default:
            errorResponse('Method not allowed', [], 405);
    }
} catch (Exception $e) {
    logError('Admin users API error', [
        'error' => $e->getMessage(),
        'action' => $action,
        'method' => $method,
        'admin_id' => $adminId
    ]);
    errorResponse('An error occurred while processing your request', [], 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $userManager) {
    switch ($action) {
        case 'list':
            getUsersList($userManager);
            break;
            
        case 'details':
            getUserDetails($userManager);
            break;
            
        case 'stats':
            getUserStats($userManager);
            break;
            
        case 'export':
            exportUsers($userManager);
            break;
            
        default:
            errorResponse('Invalid action', [], 400);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $userManager, $adminId) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        errorResponse('Invalid CSRF token', [], 403);
    }
    
    switch ($action) {
        case 'create':
            createUser($userManager, $adminId);
            break;
            
        case 'reset_password':
            resetUserPassword($userManager, $adminId);
            break;
            
        case 'restore':
            restoreUser($userManager, $adminId);
            break;
            
        case 'bulk_action':
            handleBulkAction($userManager, $adminId);
            break;
            
        default:
            errorResponse('Invalid action', [], 400);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($action, $userManager, $adminId) {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
        errorResponse('Invalid CSRF token', [], 403);
    }
    
    switch ($action) {
        case 'update':
            updateUser($userManager, $adminId, $input);
            break;
            
        default:
            errorResponse('Invalid action', [], 400);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($action, $userManager, $adminId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'delete':
            deleteUser($userManager, $adminId, $input);
            break;
            
        default:
            errorResponse('Invalid action', [], 400);
    }
}

/**
 * Get users list with filtering and pagination
 */
function getUsersList($userManager) {
    $filters = [
        'search' => sanitizeInput($_GET['search'] ?? ''),
        'role' => sanitizeInput($_GET['role'] ?? ''),
        'status' => sanitizeInput($_GET['status'] ?? ''),
        'membership_plan' => sanitizeInput($_GET['membership_plan'] ?? ''),
        'created_from' => sanitizeInput($_GET['created_from'] ?? ''),
        'created_to' => sanitizeInput($_GET['created_to'] ?? ''),
        'order_by' => sanitizeInput($_GET['order_by'] ?? 'created_at'),
        'order_dir' => strtoupper(sanitizeInput($_GET['order_dir'] ?? 'DESC'))
    ];
    
    // Validate order direction
    if (!in_array($filters['order_dir'], ['ASC', 'DESC'])) {
        $filters['order_dir'] = 'DESC';
    }
    
    // Validate order by field
    $allowedOrderFields = ['id', 'first_name', 'last_name', 'email', 'role', 'status', 'created_at', 'last_login'];
    if (!in_array($filters['order_by'], $allowedOrderFields)) {
        $filters['order_by'] = 'created_at';
    }
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20))); // Max 100 items per page
    
    $result = $userManager->getUsers($filters, $page, $limit);
    
    if ($result['success']) {
        successResponse('Users retrieved successfully', $result);
    } else {
        errorResponse($result['message'], [], 500);
    }
}

/**
 * Get user details
 */
function getUserDetails($userManager) {
    $userId = intval($_GET['id'] ?? 0);
    
    if ($userId <= 0) {
        errorResponse('Invalid user ID', [], 400);
    }
    
    $result = $userManager->getUserDetails($userId);
    
    if ($result['success']) {
        successResponse('User details retrieved successfully', $result);
    } else {
        errorResponse($result['message'], [], 404);
    }
}

/**
 * Get user statistics
 */
function getUserStats($userManager) {
    $result = $userManager->getUserStats();
    
    if ($result['success']) {
        successResponse('User statistics retrieved successfully', $result);
    } else {
        errorResponse($result['message'], [], 500);
    }
}

/**
 * Create new user
 */
function createUser($userManager, $adminId) {
    $requiredFields = ['first_name', 'last_name', 'email', 'role'];
    $userData = [];
    
    // Validate required fields
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            errorResponse("Field '{$field}' is required", [$field => 'Required'], 400);
        }
        $userData[$field] = sanitizeInput($_POST[$field]);
    }
    
    // Optional fields
    $optionalFields = [
        'password', 'phone', 'birth_date', 'gender', 'membership_plan',
        'status', 'fitness_goals', 'fitness_level', 'bio', 'website',
        'location', 'emergency_contact_name', 'emergency_contact_phone',
        'medical_conditions', 'preferred_workout_time'
    ];
    
    foreach ($optionalFields as $field) {
        if (!empty($_POST[$field])) {
            $userData[$field] = sanitizeInput($_POST[$field]);
        }
    }
    
    // Validate role
    $allowedRoles = ['member', 'trainer', 'admin', 'staff'];
    if (!in_array($userData['role'], $allowedRoles)) {
        errorResponse('Invalid role specified', ['role' => 'Invalid role'], 400);
    }
    
    // Validate membership plan if provided
    if (!empty($userData['membership_plan'])) {
        $allowedPlans = ['basic', 'premium', 'elite'];
        if (!in_array($userData['membership_plan'], $allowedPlans)) {
            errorResponse('Invalid membership plan', ['membership_plan' => 'Invalid plan'], 400);
        }
    }
    
    $result = $userManager->createUser($userData, $adminId);
    
    if ($result['success']) {
        successResponse($result['message'], ['user_id' => $result['user_id']], 201);
    } else {
        $statusCode = isset($result['errors']) ? 400 : 500;
        errorResponse($result['message'], $result['errors'] ?? [], $statusCode);
    }
}

/**
 * Update user
 */
function updateUser($userManager, $adminId, $input) {
    $userId = intval($input['user_id'] ?? 0);
    
    if ($userId <= 0) {
        errorResponse('Invalid user ID', [], 400);
    }
    
    // Remove system fields that shouldn't be updated directly
    unset($input['id'], $input['created_at'], $input['user_id'], $input['csrf_token']);
    
    // Sanitize input
    foreach ($input as $key => $value) {
        if (is_string($value)) {
            $input[$key] = sanitizeInput($value);
        }
    }
    
    // Validate role if provided
    if (!empty($input['role'])) {
        $allowedRoles = ['member', 'trainer', 'admin', 'staff'];
        if (!in_array($input['role'], $allowedRoles)) {
            errorResponse('Invalid role specified', ['role' => 'Invalid role'], 400);
        }
    }
    
    // Validate membership plan if provided
    if (!empty($input['membership_plan'])) {
        $allowedPlans = ['basic', 'premium', 'elite'];
        if (!in_array($input['membership_plan'], $allowedPlans)) {
            errorResponse('Invalid membership plan', ['membership_plan' => 'Invalid plan'], 400);
        }
    }
    
    $result = $userManager->updateUser($userId, $input, $adminId);
    
    if ($result['success']) {
        successResponse($result['message']);
    } else {
        $statusCode = isset($result['errors']) ? 400 : 500;
        errorResponse($result['message'], $result['errors'] ?? [], $statusCode);
    }
}

/**
 * Delete user
 */
function deleteUser($userManager, $adminId, $input) {
    $userId = intval($input['user_id'] ?? 0);
    $hardDelete = (bool)($input['hard_delete'] ?? false);
    
    if ($userId <= 0) {
        errorResponse('Invalid user ID', [], 400);
    }
    
    $result = $userManager->deleteUser($userId, $adminId, $hardDelete);
    
    if ($result['success']) {
        successResponse($result['message']);
    } else {
        errorResponse($result['message'], [], 400);
    }
}

/**
 * Reset user password
 */
function resetUserPassword($userManager, $adminId) {
    $userId = intval($_POST['user_id'] ?? 0);
    $sendEmail = (bool)($_POST['send_email'] ?? true);
    
    if ($userId <= 0) {
        errorResponse('Invalid user ID', [], 400);
    }
    
    $result = $userManager->resetUserPassword($userId, $adminId, $sendEmail);
    
    if ($result['success']) {
        $data = [];
        if (!$sendEmail && isset($result['new_password'])) {
            $data['new_password'] = $result['new_password'];
        }
        successResponse($result['message'], $data);
    } else {
        errorResponse($result['message'], [], 400);
    }
}

/**
 * Restore deactivated user
 */
function restoreUser($userManager, $adminId) {
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        errorResponse('Invalid user ID', [], 400);
    }
    
    $result = $userManager->restoreUser($userId, $adminId);
    
    if ($result['success']) {
        successResponse($result['message']);
    } else {
        errorResponse($result['message'], [], 400);
    }
}

/**
 * Handle bulk actions
 */
function handleBulkAction($userManager, $adminId) {
    $action = sanitizeInput($_POST['bulk_action'] ?? '');
    $userIds = $_POST['user_ids'] ?? [];
    
    if (empty($action) || empty($userIds) || !is_array($userIds)) {
        errorResponse('Invalid bulk action parameters', [], 400);
    }
    
    // Validate user IDs
    $userIds = array_map('intval', array_filter($userIds, 'is_numeric'));
    
    if (empty($userIds)) {
        errorResponse('No valid user IDs provided', [], 400);
    }
    
    $results = [];
    $errors = [];
    
    foreach ($userIds as $userId) {
        switch ($action) {
            case 'delete':
                $result = $userManager->deleteUser($userId, $adminId, false);
                break;
                
            case 'restore':
                $result = $userManager->restoreUser($userId, $adminId);
                break;
                
            case 'reset_password':
                $result = $userManager->resetUserPassword($userId, $adminId, true);
                break;
                
            default:
                errorResponse('Invalid bulk action', [], 400);
        }
        
        if ($result['success']) {
            $results[] = $userId;
        } else {
            $errors[] = ['user_id' => $userId, 'error' => $result['message']];
        }
    }
    
    $message = sprintf('%d users processed successfully', count($results));
    if (!empty($errors)) {
        $message .= sprintf(', %d errors occurred', count($errors));
    }
    
    successResponse($message, [
        'processed' => $results,
        'errors' => $errors
    ]);
}

/**
 * Export users data
 */
function exportUsers($userManager) {
    $format = sanitizeInput($_GET['format'] ?? 'csv');
    
    if (!in_array($format, ['csv', 'json'])) {
        errorResponse('Invalid export format', [], 400);
    }
    
    // Get all users (no pagination for export)
    $filters = [
        'search' => sanitizeInput($_GET['search'] ?? ''),
        'role' => sanitizeInput($_GET['role'] ?? ''),
        'status' => sanitizeInput($_GET['status'] ?? ''),
        'membership_plan' => sanitizeInput($_GET['membership_plan'] ?? ''),
    ];
    
    $result = $userManager->getUsers($filters, 1, 10000); // Large limit for export
    
    if (!$result['success']) {
        errorResponse($result['message'], [], 500);
    }
    
    $users = $result['users'];
    $filename = 'fitzone_users_' . date('Y-m-d_H-i-s');
    
    if ($format === 'csv') {
        exportCSV($users, $filename);
    } else {
        exportJSON($users, $filename);
    }
}

/**
 * Export users as CSV
 */
function exportCSV($users, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Role',
        'Status', 'Membership Plan', 'Created At', 'Last Login'
    ]);
    
    // Data rows
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['first_name'],
            $user['last_name'],
            $user['email'],
            $user['phone'] ?? '',
            $user['role'],
            $user['status'],
            $user['membership_plan'],
            $user['created_at'],
            $user['last_login'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export users as JSON
 */
function exportJSON($users, $filename) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    // Remove sensitive data
    foreach ($users as &$user) {
        unset($user['password'], $user['verification_token']);
    }
    
    echo json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'total_users' => count($users),
        'users' => $users
    ], JSON_PRETTY_PRINT);
    
    exit;
}
?>
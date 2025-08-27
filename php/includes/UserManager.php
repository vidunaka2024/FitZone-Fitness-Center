<?php
// FitZone Fitness Center - User Management Class

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all users with filtering and pagination
     */
    public function getUsers($filters = [], $page = 1, $limit = 20) {
        try {
            $conditions = ['1=1'];
            $params = [];
            
            // Build filter conditions
            if (!empty($filters['search'])) {
                $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['role'])) {
                $conditions[] = "role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['status'])) {
                $conditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['membership_plan'])) {
                $conditions[] = "membership_plan = ?";
                $params[] = $filters['membership_plan'];
            }
            
            if (!empty($filters['created_from'])) {
                $conditions[] = "created_at >= ?";
                $params[] = $filters['created_from'];
            }
            
            if (!empty($filters['created_to'])) {
                $conditions[] = "created_at <= ?";
                $params[] = $filters['created_to'] . ' 23:59:59';
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
            $totalResult = $this->db->selectOne($countSql, $params);
            $total = $totalResult['total'] ?? 0;
            
            // Calculate pagination
            $pagination = calculatePagination($total, $limit, $page);
            
            // Get users
            $orderBy = $filters['order_by'] ?? 'created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            
            $sql = "SELECT u.*, 
                           up.display_name, up.bio, up.fitness_level,
                           m.start_date as membership_start, m.status as membership_status
                    FROM users u
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    LEFT JOIN memberships m ON u.id = m.user_id AND m.status = 'active'
                    WHERE {$whereClause}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$limit} OFFSET {$pagination['offset']}";
            
            $users = $this->db->select($sql, $params);
            
            return [
                'success' => true,
                'users' => $users,
                'pagination' => $pagination
            ];
            
        } catch (Exception $e) {
            error_log('Error getting users: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve users'
            ];
        }
    }
    
    /**
     * Get user details by ID
     */
    public function getUserDetails($userId) {
        try {
            $sql = "SELECT u.*, 
                           up.display_name, up.bio, up.website, up.location,
                           up.emergency_contact_name, up.emergency_contact_phone,
                           up.medical_conditions, up.fitness_level, up.preferred_workout_time,
                           m.plan_type, m.start_date as membership_start, 
                           m.end_date as membership_end, m.status as membership_status,
                           m.monthly_fee, m.auto_renew
                    FROM users u
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    LEFT JOIN memberships m ON u.id = m.user_id AND m.status = 'active'
                    WHERE u.id = ?";
            
            $user = $this->db->selectOne($sql, [$userId]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Get recent activity
            $activitySql = "SELECT * FROM activity_logs 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT 10";
            $user['recent_activity'] = $this->db->select($activitySql, [$userId]);
            
            // Get workout stats
            $statsSql = "SELECT 
                            COUNT(*) as total_workouts,
                            SUM(duration_minutes) as total_minutes,
                            SUM(calories_burned) as total_calories,
                            AVG(difficulty_rating) as avg_difficulty
                        FROM workouts 
                        WHERE user_id = ? AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $stats = $this->db->selectOne($statsSql, [$userId]);
            $user['workout_stats'] = $stats;
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (Exception $e) {
            error_log('Error getting user details: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve user details'
            ];
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($userData, $adminId) {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ];
                }
            }
            
            // Validate email
            if (!validateEmail($userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email address'
                ];
            }
            
            // Check if email already exists
            $existingUser = getUserByEmail($userData['email']);
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Email address already exists'
                ];
            }
            
            // Generate temporary password if not provided
            if (empty($userData['password'])) {
                $userData['password'] = generateRandomString(12);
                $sendPassword = true;
            } else {
                $passwordValidation = validatePassword($userData['password']);
                if ($passwordValidation !== true) {
                    return [
                        'success' => false,
                        'message' => 'Password validation failed',
                        'errors' => $passwordValidation
                    ];
                }
                $sendPassword = false;
            }
            
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Set defaults
            $userData['status'] = $userData['status'] ?? 'active';
            $userData['membership_plan'] = $userData['membership_plan'] ?? 'basic';
            $userData['email_verified'] = 1; // Admin created users are pre-verified
            $userData['terms_accepted'] = 1;
            $userData['terms_accepted_at'] = date('Y-m-d H:i:s');
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->beginTransaction();
            
            // Insert user
            $userId = $this->db->insert('users', $userData);
            
            // Create user profile
            $profileData = [
                'user_id' => $userId,
                'display_name' => trim($userData['first_name'] . ' ' . $userData['last_name']),
                'fitness_level' => $userData['fitness_level'] ?? 'beginner',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('user_profiles', $profileData);
            
            // Create membership if member role
            if ($userData['role'] === 'member') {
                $membershipData = [
                    'user_id' => $userId,
                    'plan_type' => $userData['membership_plan'],
                    'status' => 'active',
                    'start_date' => date('Y-m-d'),
                    'monthly_fee' => $this->getMembershipPrice($userData['membership_plan']),
                    'auto_renew' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('memberships', $membershipData);
            }
            
            // Log activity
            logActivity($adminId, 'user_created', "Created user: {$userData['email']}", [
                'created_user_id' => $userId,
                'user_role' => $userData['role']
            ]);
            
            $this->db->commit();
            
            // Send welcome email
            $newUser = getUserById($userId);
            if ($newUser && $sendPassword) {
                // Send email with temporary password
                $this->sendNewUserEmail($newUser, $userData['password']);
            } elseif ($newUser) {
                sendWelcomeEmail($newUser);
            }
            
            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Error creating user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $userData, $adminId) {
        try {
            $currentUser = getUserById($userId);
            if (!$currentUser) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Validate email if changed
            if (!empty($userData['email']) && $userData['email'] !== $currentUser['email']) {
                if (!validateEmail($userData['email'])) {
                    return [
                        'success' => false,
                        'message' => 'Invalid email address'
                    ];
                }
                
                $existingUser = getUserByEmail($userData['email']);
                if ($existingUser && $existingUser['id'] != $userId) {
                    return [
                        'success' => false,
                        'message' => 'Email address already exists'
                    ];
                }
            }
            
            // Handle password change
            if (!empty($userData['password'])) {
                $passwordValidation = validatePassword($userData['password']);
                if ($passwordValidation !== true) {
                    return [
                        'success' => false,
                        'message' => 'Password validation failed',
                        'errors' => $passwordValidation
                    ];
                }
                $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            } else {
                unset($userData['password']); // Don't update password if not provided
            }
            
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->db->beginTransaction();
            
            // Update user
            $this->db->update('users', $userData, 'id = ?', [$userId]);
            
            // Update profile if profile data provided
            $profileFields = ['display_name', 'bio', 'website', 'location', 
                             'emergency_contact_name', 'emergency_contact_phone',
                             'medical_conditions', 'fitness_level', 'preferred_workout_time'];
            
            $profileData = [];
            foreach ($profileFields as $field) {
                if (isset($userData[$field])) {
                    $profileData[$field] = $userData[$field];
                }
            }
            
            if (!empty($profileData)) {
                $profileData['updated_at'] = date('Y-m-d H:i:s');
                
                // Check if profile exists
                $existingProfile = $this->db->selectOne(
                    "SELECT id FROM user_profiles WHERE user_id = ?",
                    [$userId]
                );
                
                if ($existingProfile) {
                    $this->db->update('user_profiles', $profileData, 'user_id = ?', [$userId]);
                } else {
                    $profileData['user_id'] = $userId;
                    $profileData['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('user_profiles', $profileData);
                }
            }
            
            // Log activity
            $changes = array_keys($userData);
            logActivity($adminId, 'user_updated', "Updated user: {$currentUser['email']}", [
                'updated_user_id' => $userId,
                'changed_fields' => $changes
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Error updating user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user'
            ];
        }
    }
    
    /**
     * Delete/deactivate user
     */
    public function deleteUser($userId, $adminId, $hardDelete = false) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Prevent admin from deleting themselves
            if ($userId == $adminId) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ];
            }
            
            $this->db->beginTransaction();
            
            if ($hardDelete) {
                // Hard delete - remove from database
                $this->db->delete('users', 'id = ?', [$userId]);
                $action = 'user_deleted';
                $message = "Permanently deleted user: {$user['email']}";
            } else {
                // Soft delete - set status to inactive
                $this->db->update('users', 
                    ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')],
                    'id = ?', 
                    [$userId]
                );
                $action = 'user_deactivated';
                $message = "Deactivated user: {$user['email']}";
            }
            
            // Log activity
            logActivity($adminId, $action, $message, [
                'affected_user_id' => $userId,
                'hard_delete' => $hardDelete
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => $hardDelete ? 'User deleted permanently' : 'User deactivated successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Error deleting user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete user'
            ];
        }
    }
    
    /**
     * Restore deactivated user
     */
    public function restoreUser($userId, $adminId) {
        try {
            $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            $this->db->update('users', 
                ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?', 
                [$userId]
            );
            
            // Log activity
            logActivity($adminId, 'user_restored', "Restored user: {$user['email']}", [
                'restored_user_id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'User restored successfully'
            ];
            
        } catch (Exception $e) {
            error_log('Error restoring user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore user'
            ];
        }
    }
    
    /**
     * Reset user password
     */
    public function resetUserPassword($userId, $adminId, $sendEmail = true) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Generate new password
            $newPassword = generateRandomString(12);
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $this->db->update('users', 
                ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?', 
                [$userId]
            );
            
            // Log activity
            logActivity($adminId, 'password_reset', "Reset password for user: {$user['email']}", [
                'reset_user_id' => $userId
            ]);
            
            // Send email with new password
            if ($sendEmail) {
                $this->sendPasswordResetEmail($user, $newPassword);
            }
            
            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'new_password' => $sendEmail ? null : $newPassword // Only return password if not emailing
            ];
            
        } catch (Exception $e) {
            error_log('Error resetting password: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password'
            ];
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        try {
            $stats = [];
            
            // Total users by role
            $roleSql = "SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role";
            $roleStats = $this->db->select($roleSql);
            $stats['by_role'] = array_column($roleStats, 'count', 'role');
            
            // Total users by membership plan
            $planSql = "SELECT membership_plan, COUNT(*) as count FROM users WHERE status = 'active' AND role = 'member' GROUP BY membership_plan";
            $planStats = $this->db->select($planSql);
            $stats['by_plan'] = array_column($planStats, 'count', 'membership_plan');
            
            // New users by month (last 6 months)
            $newUsersSql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                           FROM users 
                           WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                           GROUP BY month 
                           ORDER BY month";
            $newUsersStats = $this->db->select($newUsersSql);
            $stats['new_users_by_month'] = $newUsersStats;
            
            // User status breakdown
            $statusSql = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
            $statusStats = $this->db->select($statusSql);
            $stats['by_status'] = array_column($statusStats, 'count', 'status');
            
            // Active users (logged in last 30 days)
            $activeUsersSql = "SELECT COUNT(*) as count FROM users 
                              WHERE last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                              AND status = 'active'";
            $activeUsersResult = $this->db->selectOne($activeUsersSql);
            $stats['active_users'] = $activeUsersResult['count'];
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            error_log('Error getting user stats: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve user statistics'
            ];
        }
    }
    
    /**
     * Get membership price
     */
    private function getMembershipPrice($plan) {
        $prices = [
            'basic' => 29.00,
            'premium' => 59.00,
            'elite' => 99.00
        ];
        
        return $prices[$plan] ?? 29.00;
    }
    
    /**
     * Send new user email with temporary password
     */
    private function sendNewUserEmail($user, $tempPassword) {
        $subject = 'Your FitZone Account Has Been Created';
        
        $body = "
        <html>
        <body>
            <h2>Welcome to FitZone, {$user['first_name']}!</h2>
            <p>An administrator has created an account for you at FitZone Fitness Center.</p>
            
            <h3>Your Login Credentials:</h3>
            <ul>
                <li><strong>Email:</strong> {$user['email']}</li>
                <li><strong>Temporary Password:</strong> {$tempPassword}</li>
            </ul>
            
            <p><strong>Important:</strong> Please log in and change your password immediately for security reasons.</p>
            
            <p>You can log in at: <a href='#'>Login Page</a></p>
            
            <p>If you have any questions, please contact us at info@fitzonecenter.com</p>
            
            <p>Welcome to the FitZone family!</p>
            <p>The FitZone Team</p>
        </body>
        </html>
        ";
        
        return sendEmail($user['email'], $subject, $body, true);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($user, $newPassword) {
        $subject = 'Your FitZone Password Has Been Reset';
        
        $body = "
        <html>
        <body>
            <h2>Password Reset - FitZone</h2>
            <p>Hello {$user['first_name']},</p>
            <p>Your password has been reset by an administrator.</p>
            
            <h3>Your New Login Credentials:</h3>
            <ul>
                <li><strong>Email:</strong> {$user['email']}</li>
                <li><strong>New Password:</strong> {$newPassword}</li>
            </ul>
            
            <p><strong>Important:</strong> Please log in and change your password immediately for security reasons.</p>
            
            <p>You can log in at: <a href='#'>Login Page</a></p>
            
            <p>If you did not request this password reset or have any concerns, please contact us immediately at info@fitzonecenter.com</p>
            
            <p>Best regards,</p>
            <p>The FitZone Team</p>
        </body>
        </html>
        ";
        
        return sendEmail($user['email'], $subject, $body, true);
    }
}
?>
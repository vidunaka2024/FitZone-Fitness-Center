<?php
// FitZone Fitness Center - Permission Management System

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

class PermissionManager {
    private $db;
    private $permissions;
    
    public function __construct() {
        $this->db = getDB();
        $this->initializePermissions();
    }
    
    /**
     * Initialize permission definitions
     */
    private function initializePermissions() {
        $this->permissions = [
            // User Management Permissions
            'users.view' => [
                'name' => 'View Users',
                'description' => 'View user profiles and information',
                'roles' => ['admin', 'staff']
            ],
            'users.create' => [
                'name' => 'Create Users',
                'description' => 'Create new user accounts',
                'roles' => ['admin']
            ],
            'users.edit' => [
                'name' => 'Edit Users',
                'description' => 'Edit user profiles and information',
                'roles' => ['admin']
            ],
            'users.delete' => [
                'name' => 'Delete Users',
                'description' => 'Delete or deactivate user accounts',
                'roles' => ['admin']
            ],
            'users.reset_password' => [
                'name' => 'Reset User Passwords',
                'description' => 'Reset passwords for user accounts',
                'roles' => ['admin']
            ],
            'users.impersonate' => [
                'name' => 'Impersonate Users',
                'description' => 'Log in as another user',
                'roles' => ['admin']
            ],
            
            // Class Management Permissions
            'classes.view' => [
                'name' => 'View Classes',
                'description' => 'View class schedules and information',
                'roles' => ['admin', 'staff', 'trainer', 'member']
            ],
            'classes.create' => [
                'name' => 'Create Classes',
                'description' => 'Create new classes and schedules',
                'roles' => ['admin', 'staff']
            ],
            'classes.edit' => [
                'name' => 'Edit Classes',
                'description' => 'Edit class information and schedules',
                'roles' => ['admin', 'staff', 'trainer']
            ],
            'classes.delete' => [
                'name' => 'Delete Classes',
                'description' => 'Delete classes and schedules',
                'roles' => ['admin', 'staff']
            ],
            'classes.book' => [
                'name' => 'Book Classes',
                'description' => 'Book classes for members',
                'roles' => ['admin', 'staff', 'member']
            ],
            
            // Membership Management Permissions
            'memberships.view' => [
                'name' => 'View Memberships',
                'description' => 'View membership information',
                'roles' => ['admin', 'staff']
            ],
            'memberships.create' => [
                'name' => 'Create Memberships',
                'description' => 'Create new memberships',
                'roles' => ['admin', 'staff']
            ],
            'memberships.edit' => [
                'name' => 'Edit Memberships',
                'description' => 'Edit membership details',
                'roles' => ['admin', 'staff']
            ],
            'memberships.cancel' => [
                'name' => 'Cancel Memberships',
                'description' => 'Cancel or suspend memberships',
                'roles' => ['admin']
            ],
            
            // Financial Permissions
            'billing.view' => [
                'name' => 'View Billing',
                'description' => 'View billing and payment information',
                'roles' => ['admin', 'staff']
            ],
            'billing.process' => [
                'name' => 'Process Billing',
                'description' => 'Process payments and billing',
                'roles' => ['admin']
            ],
            'billing.refund' => [
                'name' => 'Process Refunds',
                'description' => 'Process refunds and adjustments',
                'roles' => ['admin']
            ],
            
            // Trainer Permissions
            'training.schedule' => [
                'name' => 'Schedule Training',
                'description' => 'Schedule personal training sessions',
                'roles' => ['admin', 'staff', 'trainer']
            ],
            'training.manage' => [
                'name' => 'Manage Training Sessions',
                'description' => 'Manage personal training sessions',
                'roles' => ['admin', 'staff', 'trainer']
            ],
            
            // Content Management Permissions
            'content.view' => [
                'name' => 'View Content',
                'description' => 'View blog posts and content',
                'roles' => ['admin', 'staff', 'trainer', 'member']
            ],
            'content.create' => [
                'name' => 'Create Content',
                'description' => 'Create blog posts and content',
                'roles' => ['admin', 'staff', 'trainer']
            ],
            'content.edit' => [
                'name' => 'Edit Content',
                'description' => 'Edit blog posts and content',
                'roles' => ['admin', 'staff', 'trainer']
            ],
            'content.publish' => [
                'name' => 'Publish Content',
                'description' => 'Publish content to the website',
                'roles' => ['admin', 'staff']
            ],
            'content.delete' => [
                'name' => 'Delete Content',
                'description' => 'Delete blog posts and content',
                'roles' => ['admin']
            ],
            
            // System Administration Permissions
            'system.settings' => [
                'name' => 'System Settings',
                'description' => 'Manage system settings and configuration',
                'roles' => ['admin']
            ],
            'system.logs' => [
                'name' => 'View System Logs',
                'description' => 'View system logs and activity',
                'roles' => ['admin']
            ],
            'system.backup' => [
                'name' => 'System Backup',
                'description' => 'Create and manage system backups',
                'roles' => ['admin']
            ],
            'system.maintenance' => [
                'name' => 'System Maintenance',
                'description' => 'Perform system maintenance tasks',
                'roles' => ['admin']
            ],
            
            // Reporting Permissions
            'reports.view' => [
                'name' => 'View Reports',
                'description' => 'View system reports and analytics',
                'roles' => ['admin', 'staff']
            ],
            'reports.export' => [
                'name' => 'Export Reports',
                'description' => 'Export reports and data',
                'roles' => ['admin', 'staff']
            ],
            
            // Communication Permissions
            'communication.send' => [
                'name' => 'Send Communications',
                'description' => 'Send emails and notifications',
                'roles' => ['admin', 'staff']
            ],
            'communication.bulk' => [
                'name' => 'Bulk Communications',
                'description' => 'Send bulk emails and notifications',
                'roles' => ['admin']
            ]
        ];
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($userId, $permission) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return false;
            }
            
            // Super admin bypass
            if ($user['role'] === 'admin' && $user['email'] === 'admin@fitzonecenter.com') {
                return true;
            }
            
            // Check if permission exists
            if (!isset($this->permissions[$permission])) {
                return false;
            }
            
            // Check if user's role has this permission
            $allowedRoles = $this->permissions[$permission]['roles'];
            return in_array($user['role'], $allowedRoles);
            
        } catch (Exception $e) {
            logError('Permission check error', [
                'user_id' => $userId,
                'permission' => $permission,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Check multiple permissions
     */
    public function hasAnyPermission($userId, $permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all permissions
     */
    public function hasAllPermissions($userId, $permissions) {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get user permissions
     */
    public function getUserPermissions($userId) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return [];
            }
            
            $userPermissions = [];
            
            foreach ($this->permissions as $permission => $details) {
                if (in_array($user['role'], $details['roles'])) {
                    $userPermissions[] = [
                        'permission' => $permission,
                        'name' => $details['name'],
                        'description' => $details['description']
                    ];
                }
            }
            
            return $userPermissions;
            
        } catch (Exception $e) {
            logError('Error getting user permissions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get permissions by role
     */
    public function getRolePermissions($role) {
        $rolePermissions = [];
        
        foreach ($this->permissions as $permission => $details) {
            if (in_array($role, $details['roles'])) {
                $rolePermissions[] = [
                    'permission' => $permission,
                    'name' => $details['name'],
                    'description' => $details['description']
                ];
            }
        }
        
        return $rolePermissions;
    }
    
    /**
     * Get all permissions
     */
    public function getAllPermissions() {
        $allPermissions = [];
        
        foreach ($this->permissions as $permission => $details) {
            $allPermissions[] = [
                'permission' => $permission,
                'name' => $details['name'],
                'description' => $details['description'],
                'roles' => $details['roles']
            ];
        }
        
        return $allPermissions;
    }
    
    /**
     * Require permission or throw exception
     */
    public function requirePermission($userId, $permission) {
        if (!$this->hasPermission($userId, $permission)) {
            throw new Exception("Access denied: Missing permission '{$permission}'");
        }
    }
    
    /**
     * Require any of the permissions
     */
    public function requireAnyPermission($userId, $permissions) {
        if (!$this->hasAnyPermission($userId, $permissions)) {
            throw new Exception("Access denied: Missing required permissions");
        }
    }
    
    /**
     * Check if user can access resource
     */
    public function canAccessResource($userId, $resourceType, $action, $resourceId = null) {
        $permission = $resourceType . '.' . $action;
        
        // Check basic permission
        if (!$this->hasPermission($userId, $permission)) {
            return false;
        }
        
        // Additional resource-specific checks
        if ($resourceId !== null) {
            return $this->checkResourceAccess($userId, $resourceType, $resourceId);
        }
        
        return true;
    }
    
    /**
     * Resource-specific access checks
     */
    private function checkResourceAccess($userId, $resourceType, $resourceId) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return false;
            }
            
            switch ($resourceType) {
                case 'users':
                    // Users can view/edit their own profile
                    if ($resourceId == $userId) {
                        return true;
                    }
                    // Admins can access all users
                    return $user['role'] === 'admin';
                    
                case 'training':
                    // Trainers can only manage their own sessions
                    if ($user['role'] === 'trainer') {
                        return $this->isTrainerSession($userId, $resourceId);
                    }
                    return true;
                    
                case 'memberships':
                    // Users can view their own membership
                    if ($user['role'] === 'member') {
                        return $this->isUserMembership($userId, $resourceId);
                    }
                    return true;
                    
                default:
                    return true;
            }
            
        } catch (Exception $e) {
            logError('Resource access check error', [
                'user_id' => $userId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Check if session belongs to trainer
     */
    private function isTrainerSession($trainerId, $sessionId) {
        try {
            $session = $this->db->selectOne(
                "SELECT trainer_id FROM pt_appointments WHERE id = ?",
                [$sessionId]
            );
            
            return $session && $session['trainer_id'] == $trainerId;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if membership belongs to user
     */
    private function isUserMembership($userId, $membershipId) {
        try {
            $membership = $this->db->selectOne(
                "SELECT user_id FROM memberships WHERE id = ?",
                [$membershipId]
            );
            
            return $membership && $membership['user_id'] == $userId;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log permission check (for audit purposes)
     */
    public function logPermissionCheck($userId, $permission, $granted, $context = []) {
        try {
            $metadata = array_merge([
                'permission' => $permission,
                'granted' => $granted,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ], $context);
            
            logActivity($userId, 'permission_check', 
                "Permission check: {$permission} - " . ($granted ? 'GRANTED' : 'DENIED'),
                $metadata
            );
            
        } catch (Exception $e) {
            logError('Failed to log permission check', [
                'user_id' => $userId,
                'permission' => $permission,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get role hierarchy
     */
    public function getRoleHierarchy() {
        return [
            'member' => 1,
            'trainer' => 2,
            'staff' => 3,
            'admin' => 4
        ];
    }
    
    /**
     * Check if role A has higher privileges than role B
     */
    public function isRoleHigher($roleA, $roleB) {
        $hierarchy = $this->getRoleHierarchy();
        $levelA = $hierarchy[$roleA] ?? 0;
        $levelB = $hierarchy[$roleB] ?? 0;
        
        return $levelA > $levelB;
    }
    
    /**
     * Get available roles for user to assign
     */
    public function getAssignableRoles($userId) {
        try {
            $user = getUserById($userId);
            if (!$user) {
                return [];
            }
            
            $hierarchy = $this->getRoleHierarchy();
            $userLevel = $hierarchy[$user['role']] ?? 0;
            
            $assignableRoles = [];
            foreach ($hierarchy as $role => $level) {
                // Users can assign roles at their level or below
                if ($level <= $userLevel) {
                    $assignableRoles[] = $role;
                }
            }
            
            return $assignableRoles;
            
        } catch (Exception $e) {
            logError('Error getting assignable roles', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Middleware function for route protection
     */
    public static function requirePermissionMiddleware($permission) {
        return function() use ($permission) {
            if (!isLoggedIn()) {
                http_response_code(401);
                jsonResponse(['error' => 'Authentication required'], 401);
            }
            
            $permissionManager = new self();
            $userId = $_SESSION['user_id'];
            
            if (!$permissionManager->hasPermission($userId, $permission)) {
                $permissionManager->logPermissionCheck($userId, $permission, false);
                http_response_code(403);
                jsonResponse(['error' => 'Access denied'], 403);
            }
            
            $permissionManager->logPermissionCheck($userId, $permission, true);
        };
    }
}

/**
 * Global helper functions for permissions
 */

/**
 * Check if current user has permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $permissionManager = new PermissionManager();
    return $permissionManager->hasPermission($_SESSION['user_id'], $permission);
}

/**
 * Require permission for current user
 */
function requirePermission($permission) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    $permissionManager = new PermissionManager();
    try {
        $permissionManager->requirePermission($_SESSION['user_id'], $permission);
    } catch (Exception $e) {
        http_response_code(403);
        die('Access denied: ' . $e->getMessage());
    }
}

/**
 * Check resource access for current user
 */
function canAccessResource($resourceType, $action, $resourceId = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $permissionManager = new PermissionManager();
    return $permissionManager->canAccessResource($_SESSION['user_id'], $resourceType, $action, $resourceId);
}
?>
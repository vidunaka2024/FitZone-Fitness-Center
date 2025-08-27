<?php
// FitZone Fitness Center - Security Management System

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

class SecurityManager {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = getDB();
        $this->config = $this->loadSecurityConfig();
    }
    
    /**
     * Load security configuration
     */
    private function loadSecurityConfig() {
        return [
            'max_login_attempts' => 5,
            'login_lockout_duration' => 900, // 15 minutes
            'password_min_length' => 8,
            'password_require_special' => true,
            'password_require_numbers' => true,
            'password_require_uppercase' => true,
            'session_timeout' => 3600, // 1 hour
            'csrf_token_lifetime' => 3600,
            'allowed_file_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'max_file_size' => 5242880, // 5MB
            'ip_whitelist' => [],
            'ip_blacklist' => [],
            'enable_2fa' => false,
            'audit_log_retention' => 90, // days
        ];
    }
    
    /**
     * Validate input data
     */
    public function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $fieldErrors = $this->validateField($field, $value, $rule);
            
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual field
     */
    private function validateField($field, $value, $rules) {
        $errors = [];
        $ruleList = explode('|', $rules);
        
        foreach ($ruleList as $rule) {
            $ruleParts = explode(':', $rule);
            $ruleName = $ruleParts[0];
            $ruleParam = $ruleParts[1] ?? null;
            
            switch ($ruleName) {
                case 'required':
                    if (empty($value)) {
                        $errors[] = ucfirst($field) . ' is required';
                    }
                    break;
                    
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = ucfirst($field) . ' must be a valid email address';
                    }
                    break;
                    
                case 'min':
                    if (!empty($value) && strlen($value) < intval($ruleParam)) {
                        $errors[] = ucfirst($field) . ' must be at least ' . $ruleParam . ' characters';
                    }
                    break;
                    
                case 'max':
                    if (!empty($value) && strlen($value) > intval($ruleParam)) {
                        $errors[] = ucfirst($field) . ' must not exceed ' . $ruleParam . ' characters';
                    }
                    break;
                    
                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[] = ucfirst($field) . ' must be a number';
                    }
                    break;
                    
                case 'alpha':
                    if (!empty($value) && !ctype_alpha($value)) {
                        $errors[] = ucfirst($field) . ' must contain only letters';
                    }
                    break;
                    
                case 'alphanumeric':
                    if (!empty($value) && !ctype_alnum($value)) {
                        $errors[] = ucfirst($field) . ' must contain only letters and numbers';
                    }
                    break;
                    
                case 'password':
                    if (!empty($value)) {
                        $passwordErrors = $this->validatePassword($value);
                        $errors = array_merge($errors, $passwordErrors);
                    }
                    break;
                    
                case 'unique':
                    if (!empty($value)) {
                        $tableParts = explode(',', $ruleParam);
                        $table = $tableParts[0];
                        $column = $tableParts[1] ?? $field;
                        $excludeId = $tableParts[2] ?? null;
                        
                        if ($this->isValueUnique($table, $column, $value, $excludeId)) {
                            $errors[] = ucfirst($field) . ' already exists';
                        }
                    }
                    break;
                    
                case 'in':
                    if (!empty($value)) {
                        $allowedValues = explode(',', $ruleParam);
                        if (!in_array($value, $allowedValues)) {
                            $errors[] = ucfirst($field) . ' must be one of: ' . implode(', ', $allowedValues);
                        }
                    }
                    break;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate password strength
     */
    private function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < $this->config['password_min_length']) {
            $errors[] = 'Password must be at least ' . $this->config['password_min_length'] . ' characters long';
        }
        
        if ($this->config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if ($this->config['password_require_numbers'] && !preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if ($this->config['password_require_special'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check for common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common. Please choose a stronger password';
        }
        
        return $errors;
    }
    
    /**
     * Check if value is unique in database
     */
    private function isValueUnique($table, $column, $value, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $result = $this->db->selectOne($sql, $params);
            return ($result['count'] ?? 0) > 0;
            
        } catch (Exception $e) {
            logError('Unique validation error', [
                'table' => $table,
                'column' => $column,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return false; // Fail open for safety
        }
    }
    
    /**
     * Check if password is in common passwords list
     */
    private function isCommonPassword($password) {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            'dragon', '1234567890', 'football', 'baseball', 'master'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        // Remove null bytes
        $data = str_replace("\0", '', $data);
        
        // Trim whitespace
        $data = trim($data);
        
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $options = []) {
        $errors = [];
        
        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'File size exceeds maximum allowed size';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'No file was uploaded';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = 'Missing temporary folder';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = 'Failed to write file to disk';
                    break;
                default:
                    $errors[] = 'Unknown file upload error';
            }
            return $errors;
        }
        
        // Check file size
        $maxSize = $options['max_size'] ?? $this->config['max_file_size'];
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . formatFileSize($maxSize);
        }
        
        // Check file extension
        $allowedExtensions = $options['allowed_extensions'] ?? $this->config['allowed_file_extensions'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx']
        ];
        
        $validMime = false;
        foreach ($allowedMimeTypes as $mime => $extensions) {
            if ($mimeType === $mime && in_array($fileExtension, $extensions)) {
                $validMime = true;
                break;
            }
        }
        
        if (!$validMime) {
            $errors[] = 'Invalid file type detected';
        }
        
        // Scan for malicious content (basic check)
        $fileContent = file_get_contents($file['tmp_name']);
        if ($this->containsMaliciousContent($fileContent)) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return $errors;
    }
    
    /**
     * Basic malicious content detection
     */
    private function containsMaliciousContent($content) {
        $maliciousPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check IP address restrictions
     */
    public function checkIpRestrictions($ip) {
        // Check blacklist
        if (!empty($this->config['ip_blacklist']) && in_array($ip, $this->config['ip_blacklist'])) {
            return false;
        }
        
        // Check whitelist (if enabled)
        if (!empty($this->config['ip_whitelist']) && !in_array($ip, $this->config['ip_whitelist'])) {
            return false;
        }
        
        // Check database blocks
        try {
            $blocked = $this->db->selectOne(
                "SELECT * FROM blocked_ips WHERE ip_address = ? AND blocked_until > NOW()",
                [$ip]
            );
            
            return $blocked === false;
            
        } catch (Exception $e) {
            logError('IP restriction check error', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
            return true; // Fail open
        }
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $severity, $details = [], $userId = null) {
        try {
            $metadata = array_merge([
                'severity' => $severity,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ], $details);
            
            // Log to activity logs
            if ($userId) {
                logActivity($userId, 'security_event', $event, $metadata);
            }
            
            // Log to security log file
            $logEntry = json_encode([
                'event' => $event,
                'severity' => $severity,
                'user_id' => $userId,
                'metadata' => $metadata
            ]);
            
            error_log("SECURITY: {$logEntry}", 3, 'logs/security.log');
            
            // Alert on high severity events
            if ($severity === 'high' || $severity === 'critical') {
                $this->sendSecurityAlert($event, $metadata);
            }
            
        } catch (Exception $e) {
            error_log('Failed to log security event: ' . $e->getMessage());
        }
    }
    
    /**
     * Send security alert
     */
    private function sendSecurityAlert($event, $metadata) {
        $subject = 'Security Alert - FitZone';
        $body = "Security Event: {$event}\n\n";
        $body .= "Details:\n" . print_r($metadata, true);
        
        // Send to admin email (in a real application)
        error_log("SECURITY ALERT: {$subject} - {$event}");
    }
    
    /**
     * Generate secure token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash sensitive data
     */
    public function hashData($data, $salt = null) {
        if ($salt === null) {
            $salt = $this->generateSecureToken(16);
        }
        
        return [
            'hash' => hash('sha256', $data . $salt),
            'salt' => $salt
        ];
    }
    
    /**
     * Verify hashed data
     */
    public function verifyHash($data, $hash, $salt) {
        return hash_equals($hash, hash('sha256', $data . $salt));
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encryptData($data, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-me';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decryptData($encryptedData, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-me';
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Clean old security logs
     */
    public function cleanOldLogs() {
        try {
            $retention = $this->config['audit_log_retention'];
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retention} days"));
            
            // Clean activity logs
            $this->db->delete(
                'activity_logs',
                'created_at < ?',
                [$cutoffDate]
            );
            
            // Clean failed login attempts
            $this->db->delete(
                'failed_login_attempts',
                'attempted_at < ?',
                [$cutoffDate]
            );
            
            // Clean expired blocks
            $this->db->delete(
                'blocked_ips',
                'blocked_until < NOW()'
            );
            
            // Clean expired rate limits
            $this->db->delete(
                'rate_limits',
                'expires_at < NOW()'
            );
            
            logActivity(null, 'system_cleanup', 'Security logs cleaned', [
                'retention_days' => $retention,
                'cutoff_date' => $cutoffDate
            ]);
            
        } catch (Exception $e) {
            logError('Failed to clean old logs', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get security configuration
     */
    public function getConfig($key = null) {
        if ($key === null) {
            return $this->config;
        }
        
        return $this->config[$key] ?? null;
    }
    
    /**
     * Update security configuration
     */
    public function updateConfig($key, $value) {
        $this->config[$key] = $value;
        
        // In a real application, this would save to database or config file
        logActivity($_SESSION['user_id'] ?? null, 'security_config_update', 
            "Updated security config: {$key}", ['new_value' => $value]);
    }
}

/**
 * Global security helper functions
 */

/**
 * Validate input with security rules
 */
function secureValidate($data, $rules) {
    $security = new SecurityManager();
    return $security->validateInput($data, $rules);
}

/**
 * Sanitize input data
 */
function secureSanitize($data) {
    $security = new SecurityManager();
    return $security->sanitizeInput($data);
}

/**
 * Log security event
 */
function logSecurityEvent($event, $severity = 'medium', $details = []) {
    $security = new SecurityManager();
    $userId = $_SESSION['user_id'] ?? null;
    $security->logSecurityEvent($event, $severity, $details, $userId);
}

/**
 * Check if current IP is allowed
 */
function isIpAllowed() {
    $security = new SecurityManager();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return $security->checkIpRestrictions($ip);
}
?>
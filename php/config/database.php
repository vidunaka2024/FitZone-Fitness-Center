<?php
// FitZone Fitness Center - Database Configuration

// Prevent direct access
if (!defined('FITZONE_ACCESS')) {
    die('Direct access not allowed');
}

class Database {
    private $host;
    private $database;
    private $username;
    private $password;
    private $port;
    private $connection;
    private static $instance = null;

    private function __construct() {
        // Database configuration
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->database = $_ENV['DB_NAME'] ?? 'fitzone_db';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? 'password';
        $this->port = $_ENV['DB_PORT'] ?? '3306';
        
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            
            // In production, don't show detailed error messages
            if ($_ENV['ENVIRONMENT'] === 'development') {
                die('Database Connection Error: ' . $e->getMessage());
            } else {
                die('Database connection failed. Please try again later.');
            }
        }
    }

    public function getConnection() {
        // Check if connection is alive
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            error_log('Database connection lost, reconnecting...');
            $this->connect();
        }

        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database Query Error: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed');
        }
    }

    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($data);
            $lastId = $conn->lastInsertId();
            
            // If lastInsertId returns 0 but we're dealing with an auto-increment table,
            // try to get the ID by querying for it
            if (!$lastId && isset($data['email'])) {
                $result = $conn->prepare("SELECT id FROM {$table} WHERE email = ? ORDER BY id DESC LIMIT 1");
                $result->execute([$data['email']]);
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $lastId = $row ? $row['id'] : 0;
            }
            
            return $lastId;
        } catch (PDOException $e) {
            error_log('Database Insert Error: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed');
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        // Convert positional WHERE parameters to named parameters
        $whereNamed = $where;
        $namedWhereParams = [];
        
        // If we have positional parameters, convert them to named
        if (!empty($whereParams) && is_array($whereParams) && array_keys($whereParams) === range(0, count($whereParams) - 1)) {
            $paramIndex = 0;
            $whereNamed = preg_replace_callback('/\?/', function($matches) use ($whereParams, &$paramIndex, &$namedWhereParams) {
                $paramName = 'where_param_' . $paramIndex;
                if (isset($whereParams[$paramIndex])) {
                    $namedWhereParams[$paramName] = $whereParams[$paramIndex];
                }
                $paramIndex++;
                return ':' . $paramName;
            }, $where);
        } else {
            $namedWhereParams = $whereParams;
        }
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereNamed}";
        
        $params = array_merge($data, $namedWhereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    public function commit() {
        return $this->getConnection()->commit();
    }

    public function rollback() {
        return $this->getConnection()->rollback();
    }

    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    // Utility methods
    public function tableExists($tableName) {
        $sql = "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = :database AND table_name = :table";
        $stmt = $this->query($sql, [
            'database' => $this->database,
            'table' => $tableName
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function columnExists($tableName, $columnName) {
        $sql = "SELECT COUNT(*) FROM information_schema.columns 
                WHERE table_schema = :database AND table_name = :table AND column_name = :column";
        $stmt = $this->query($sql, [
            'database' => $this->database,
            'table' => $tableName,
            'column' => $columnName
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function escape($value) {
        return $this->getConnection()->quote($value);
    }

    public function getServerInfo() {
        return $this->getConnection()->getAttribute(PDO::ATTR_SERVER_INFO);
    }

    public function getServerVersion() {
        return $this->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserializing
    public function __wakeup() {}
}

// Global helper function to get database instance
function getDB() {
    return Database::getInstance();
}

// Database connection test function
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $result = $db->selectOne("SELECT 1 as test, NOW() as current_datetime");
        
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'server_info' => $db->getServerInfo(),
            'server_version' => $db->getServerVersion(),
            'current_time' => $result['current_datetime']
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    $db = Database::getInstance();
    
    try {
        // Check if main tables exist, if not, run the database schema
        if (!$db->tableExists('users')) {
            error_log('Database tables not found. Please run the database schema from /database/fitzone.sql');
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Database initialization error: ' . $e->getMessage());
        return false;
    }
}

// Add error logging helper
function logDatabaseError($message, $sql = '', $params = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - Database Error: ' . $message;
    
    if (!empty($sql)) {
        $logMessage .= ' | SQL: ' . $sql;
    }
    
    if (!empty($params)) {
        $logMessage .= ' | Params: ' . json_encode($params);
    }
    
    error_log($logMessage);
}
?>
<?php

/**
 * Database Singleton Class
 * Manages PDO database connection
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Load database config from new connection.php
        require_once __DIR__ . '/../database/connection.php';
        
        // Get existing mysqli connection details
        global $connection;
        
        // Create PDO connection with same credentials
        try {
            // Database credentials from config.php constants
            $host = DB_HOST;
            $dbname = DB_NAME;
            $username = DB_USERNAME;
            $password = DB_PASSWORD;
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

<?php

/**
 * Database Singleton Class
 * Manages PDO database connection
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Load database config from existing ketnoi.php
        require_once __DIR__ . '/../hethong/ketnoi.php';
        
        // Get existing mysqli connection details
        global $ketnoi;
        
        // Create PDO connection with same credentials
        try {
            // Extract connection details from mysqli (stored in ketnoi.php)
            $host = '127.0.0.1';
            $dbname = 'cdailycodevn_nosqltrang'; // From csdl.sql
            $username = 'root'; // Common XAMPP default
            $password = ''; // Common XAMPP default
            
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

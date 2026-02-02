<?php

/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . '/kaishop_v2');

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload core classes
spl_autoload_register(function($class) {
    $paths = [
        BASE_PATH . '/core/' . $class . '.php',
        BASE_PATH . '/app/Controllers/' . $class . '.php',
        BASE_PATH . '/app/Models/' . $class . '.php',
        BASE_PATH . '/app/Services/' . $class . '.php',
        BASE_PATH . '/app/Validators/' . $class . '.php',
        BASE_PATH . '/app/Middlewares/' . $class . '.php',
        BASE_PATH . '/app/Helpers/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load helper functions
require_once BASE_PATH . '/hethong/config.php';

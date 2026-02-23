<?php

/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require EnvHelper for dynamic base URL
require_once dirname(__DIR__) . '/app/Helpers/EnvHelper.php';
EnvHelper::load(dirname(__DIR__) . '/.env');

// Base URL configuration
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . EnvHelper::get('APP_DIR', ''));

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload core classes
spl_autoload_register(function ($class) {
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

// Global Security Check (SQL Injection & XSS)
function checkMaliciousInput($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            checkMaliciousInput($value);
        }
    } else {
        $dataStr = strtolower((string) $data);
        $patterns = [
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/onload=/i',
            '/onerror=/i',
            '/javascript:/i'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dataStr)) {
                // Suspicious payload detected
                $payload = [
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'matched_pattern' => $pattern,
                    'input' => substr($data, 0, 200) // Truncate to avoid massive logs
                ];
                // Try to load Logger if available
                if (class_exists('Logger')) {
                    Logger::danger('Security', 'sql_injection_attempt', 'Phát hiện truy vấn độc hại (SQLi / XSS)', $payload);
                }

                // Optionally block request:
                // die('Security Violation Detected');
                break;
            }
        }
    }
}

// Check GET and POST globally
if (!empty($_GET)) {
    checkMaliciousInput($_GET);
}
if (!empty($_POST)) {
    checkMaliciousInput($_POST);
}

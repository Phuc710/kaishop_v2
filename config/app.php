<?php

/**
 * Application Configuration
 */

// Load env helper early for runtime configuration (APP_DIR / APP_DEBUG / APP_KEY...)
require_once dirname(__DIR__) . '/app/Helpers/EnvHelper.php';
EnvHelper::load(dirname(__DIR__) . '/.env');

// Start session with safer cookie flags
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

// Base URL configuration
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . EnvHelper::get('APP_DIR', ''));
if (!defined('APP_KEY')) {
    define('APP_KEY', (string) EnvHelper::get('APP_KEY', ''));
}

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (production-safe by default unless APP_DEBUG=1)
$appDebug = in_array(strtolower((string) EnvHelper::get('APP_DEBUG', '0')), ['1', 'true', 'yes', 'on'], true);
error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');

// Handle CORS and preflight requests (OPTIONS) for APIs and cross-origin Auth fetches
$httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($httpOrigin !== '' && !headers_sent()) {
    header("Access-Control-Allow-Origin: $httpOrigin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Baseline security headers (avoid CSP here due legacy inline scripts)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    // Important: Allow popups/redirects for Firebase Google Login (OAuth)
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// CSRF token helpers (shared across legacy + MVC views)
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 32) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['csrf_token_created_at'])) {
    $_SESSION['csrf_token_created_at'] = time();
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return (string) ($_SESSION['csrf_token'] ?? '');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_regenerate')) {
    function csrf_regenerate(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created_at'] = time();
        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_validate_request')) {
    function csrf_validate_request(): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        if ($sessionToken === '') {
            return false;
        }

        $provided = '';
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $provided = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['csrf_token'])) {
            $provided = (string) $_POST['csrf_token'];
        }

        return $provided !== '' && hash_equals($sessionToken, $provided);
    }
}

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

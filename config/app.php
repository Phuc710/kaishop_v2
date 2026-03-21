<?php

/**
 * Application Configuration
 */

// Load env helper early for runtime configuration (APP_DIR / APP_DEBUG / APP_KEY...)
require_once dirname(__DIR__) . '/app/Helpers/EnvHelper.php';
EnvHelper::load(dirname(__DIR__) . '/.env');

$appBasePath = dirname(__DIR__);

if (!defined('APP_DIR')) {
    // ─── Smart APP_DIR Detection ──────────────────────────────────────────────
    // Priority 1: Use .env value if explicitly set (not empty)
    $envAppDir = (string) EnvHelper::get('APP_DIR', '');

    if ($envAppDir !== '') {
        // Explicit override in .env — normalize: ensure leading '/', strip trailing '/'
        $envAppDir = '/' . ltrim(rtrim($envAppDir, '/'), '/');
        define('APP_DIR', $envAppDir);
    } else {
        // Auto-detect based on hostname
        $currentHost = PHP_SAPI === 'cli' ? 'cli' : strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        // Known local/dev hostnames → use /kaishop_v2 subfolder
        $localHosts = ['localhost', '127.0.0.1', '::1', 'kaishop_v2.localhost'];
        $isLocal = in_array($currentHost, $localHosts, true)
            || preg_match('/\.localhost$|\.local$|\.test$/', $currentHost);

        define('APP_DIR', $isLocal ? '/kaishop_v2' : '');
    }
}

// Start session with safer cookie flags
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    $configuredSessionPath = trim((string) ini_get('session.save_path'));
    $normalizedSessionPath = preg_replace('/^\d+;/', '', $configuredSessionPath) ?? $configuredSessionPath;
    $normalizedSessionPath = trim((string) $normalizedSessionPath);
    if ($normalizedSessionPath === '' || !is_dir($normalizedSessionPath) || !is_writable($normalizedSessionPath)) {
        $fallbackSessionPath = $appBasePath . '/storage/sessions';
        if (!is_dir($fallbackSessionPath)) {
            @mkdir($fallbackSessionPath, 0755, true);
        }
        if (is_dir($fallbackSessionPath) && is_writable($fallbackSessionPath)) {
            session_save_path($fallbackSessionPath);
        }
    }

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
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $appBasePath);
}

if (PHP_SAPI === 'cli') {
    $baseUrl = (string) EnvHelper::get('BASE_URL', 'http://localhost');
} else {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER["HTTP_HOST"] ?? 'localhost';
    $baseUrl = $protocol . "://" . $host . APP_DIR;
}
define('BASE_URL', $baseUrl);

if (!function_exists('app_request_path')) {
    function app_request_path(bool $stripLocale = false): string
    {
        $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        $appDir = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
        if ($appDir !== '' && strpos($requestPath, $appDir) === 0) {
            $requestPath = substr($requestPath, strlen($appDir));
        }
        if ($requestPath === '') {
            $requestPath = '/';
        }
        if ($stripLocale) {
            if ($requestPath === '/en') {
                return '/';
            }
            if (strpos($requestPath, '/en/') === 0) {
                $requestPath = substr($requestPath, 3);
                if ($requestPath === '') {
                    $requestPath = '/';
                }
            }
        }
        return $requestPath;
    }
}

if (!defined('APP_LOCALE')) {
    $localePath = strtolower(app_request_path(false));
    $appLocale = ($localePath === '/en' || strpos($localePath, '/en/') === 0) ? 'en' : 'vi';
    define('APP_LOCALE', $appLocale);
}

if (!defined('APP_LOCALE_PREFIX')) {
    define('APP_LOCALE_PREFIX', APP_LOCALE === 'en' ? '/en' : '');
}

if (!function_exists('app_locale')) {
    function app_locale(): string
    {
        return defined('APP_LOCALE') ? (string) APP_LOCALE : 'vi';
    }
}

if (!function_exists('app_locale_prefix')) {
    function app_locale_prefix(): string
    {
        return defined('APP_LOCALE_PREFIX') ? (string) APP_LOCALE_PREFIX : '';
    }
}

if (!function_exists('app_is_english')) {
    function app_is_english(): bool
    {
        return app_locale() === 'en';
    }
}

if (!defined('APP_KEY')) {
    define('APP_KEY', (string) EnvHelper::get('APP_KEY', ''));
}

// Set timezone (runtime-configurable via .env)
$appTimezone = trim((string) EnvHelper::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));
if ($appTimezone === '') {
    $appTimezone = 'Asia/Ho_Chi_Minh';
}
if (!@date_default_timezone_set($appTimezone)) {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
}

if (!function_exists('app_timezone')) {
    function app_timezone(): string
    {
        $tz = trim((string) EnvHelper::get('APP_TIMEZONE', date_default_timezone_get()));
        return $tz !== '' ? $tz : date_default_timezone_get();
    }
}

if (!function_exists('app_display_timezone')) {
    function app_display_timezone(): string
    {
        $tz = trim((string) EnvHelper::get('APP_DISPLAY_TIMEZONE', app_timezone()));
        return $tz !== '' ? $tz : app_timezone();
    }
}

if (!function_exists('app_db_timezone')) {
    function app_db_timezone(): string
    {
        // Transitional: allow DB timezone to differ while migrating legacy local DATETIME rows to UTC.
        $fallback = app_timezone();
        $tz = trim((string) EnvHelper::get('APP_DB_TIMEZONE', $fallback));
        return $tz !== '' ? $tz : $fallback;
    }
}

// Error reporting (production-safe by default unless APP_DEBUG=1)
$appDebug = in_array(strtolower((string) EnvHelper::get('APP_DEBUG', '0')), ['1', 'true', 'yes', 'on'], true);
error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');

// Handle CORS and preflight requests (OPTIONS) for APIs and cross-origin Auth fetches
$httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = array_filter(array_map('trim', explode(',', (string) EnvHelper::get('CORS_ORIGINS', ''))));
// Always allow same-origin and localhost for dev
if ($allowedOrigins === []) {
    $allowedOrigins = [rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/')];
}
$isAllowedOrigin = $httpOrigin !== '' && in_array(rtrim($httpOrigin, '/'), $allowedOrigins, true);
// Also allow localhost for dev convenience
if (!$isAllowedOrigin && $httpOrigin !== '' && preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $httpOrigin)) {
    $isAllowedOrigin = true;
}
if ($isAllowedOrigin && !headers_sent()) {
    header("Access-Control-Allow-Origin: $httpOrigin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept");
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code($isAllowedOrigin ? 200 : 403);
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
    $_SESSION['csrf_token_created_at'] = class_exists('TimeService') ? TimeService::instance()->nowTs() : time();
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
        $_SESSION['csrf_token_created_at'] = class_exists('TimeService') ? TimeService::instance()->nowTs() : time();
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

// HMAC helpers using APP_KEY for secure signing
if (!function_exists('hmac_sign')) {
    /**
     * Generate an HMAC-SHA256 signature for the given data using APP_KEY.
     */
    function hmac_sign(string $data): string
    {
        $key = defined('APP_KEY') ? APP_KEY : '';
        if ($key === '') {
            throw new RuntimeException('APP_KEY is not configured. Cannot sign data.');
        }
        return hash_hmac('sha256', $data, $key);
    }
}

if (!function_exists('hmac_verify')) {
    /**
     * Verify an HMAC-SHA256 signature against the given data using APP_KEY.
     */
    function hmac_verify(string $data, string $signature): bool
    {
        $key = defined('APP_KEY') ? APP_KEY : '';
        if ($key === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $data, $key);
        return hash_equals($expected, $signature);
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
        BASE_PATH . '/app/Interfaces/' . $class . '.php',
        BASE_PATH . '/app/Handlers/Inventory/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

if (!function_exists('telegram_service')) {
    /**
     * Lazy-load a shared TelegramService instance for legacy call sites.
     */
    function telegram_service()
    {
        static $service;
        static $resolved = false;

        if ($resolved) {
            return $service;
        }

        $resolved = true;
        if (!class_exists('TelegramService')) {
            $service = null;
            return null;
        }

        $instance = new TelegramService();
        $service = $instance->isConfigured() ? $instance : null;

        return $service;
    }
}

// Load helper functions
require_once BASE_PATH . '/hethong/config.php';

function normalizedRequestPathForSecurity(): string
{
    $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    $appDir = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
    if ($appDir !== '' && strpos($requestPath, $appDir) === 0) {
        $requestPath = substr($requestPath, strlen($appDir));
    }

    return $requestPath !== '' ? $requestPath : '/';
}

function isTelegramWebhookPathForSecurity(string $path): bool
{
    $normalized = '/' . ltrim($path, '/');
    $knownWebhookPaths = [
        '/api/telegram/webhook',
    ];

    // Dynamic webhook path from DB settings
    if (function_exists('get_setting')) {
        try {
            $dbPath = trim((string) get_setting('telegram_webhook_path', ''));
            if ($dbPath !== '') {
                $knownWebhookPaths[] = '/api/' . ltrim($dbPath, '/');
            }
        } catch (Throwable $e) {
            // Ignore if DB not ready
        }
    }

    if (class_exists('TelegramConfig')) {
        $segment = trim((string) TelegramConfig::WEBHOOK_PATH_SEGMENT, '/');
        if ($segment !== '') {
            $knownWebhookPaths[] = '/api/' . $segment;
            $knownWebhookPaths[] = '/api/' . $segment . '/index.php';
        }
    }

    foreach ($knownWebhookPaths as $webhookPath) {
        $prefix = rtrim($webhookPath, '/');
        if ($normalized === $prefix || strpos($normalized, $prefix . '/') === 0) {
            return true;
        }
    }

    return false;
}


function resolveInputSecurityProfile(string $path, string $method): ?array
{
    $method = strtoupper($method);
    $profiles = [
        ['prefixes' => ['/admin'], 'methods' => ['POST'], 'patterns' => 'xss,sqli'],
        ['prefixes' => ['/login', '/register', '/password-reset', '/forgot-password', '/password/security', '/api/'], 'methods' => ['POST'], 'patterns' => 'xss,sqli'],
        ['prefixes' => ['/contact', '/lien-he'], 'methods' => ['POST'], 'patterns' => 'xss,sqli'],
        ['prefixes' => ['/search', '/tim-kiem'], 'methods' => ['GET', 'POST'], 'patterns' => 'xss'],
    ];

    foreach ($profiles as $profile) {
        if (!in_array($method, $profile['methods'], true)) {
            continue;
        }

        foreach ($profile['prefixes'] as $prefix) {
            $isPrefix = substr($prefix, -1) === '/';
            if (($isPrefix && strpos($path, $prefix) === 0) || (!$isPrefix && ($path === $prefix || strpos($path, $prefix . '/') === 0))) {
                return $profile;
            }
        }
    }

    return null;
}

function maliciousInputPatterns(string $profileKey): array
{
    $groups = [
        'xss' => [
            '/<script\b[^>]*>/i',
            '/javascript\s*:/i',
            '/on(?:load|error|click|focus|mouseover|mouseenter|submit|change)\s*=/i',
        ],
        'sqli' => [
            '/\bunion\s+select\b/i',
            '/\binsert\s+into\b/i',
            '/\bdelete\s+from\b/i',
            '/\bdrop\s+table\b/i',
            '/\bupdate\b[\s\S]{0,40}\bset\b/i',
        ],
    ];

    $resolved = [];
    foreach (array_filter(array_map('trim', explode(',', $profileKey))) as $group) {
        if (!empty($groups[$group])) {
            $resolved = array_merge($resolved, $groups[$group]);
        }
    }

    return $resolved;
}

function inspectSensitiveInput($data, array $patterns, string $fieldPath = 'root'): void
{
    if (empty($patterns)) {
        return;
    }

    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $nextPath = $fieldPath . '.' . (is_string($key) || is_int($key) ? $key : 'item');
            inspectSensitiveInput($value, $patterns, $nextPath);
        }
        return;
    }

    if (!is_scalar($data) && $data !== null) {
        return;
    }

    $raw = trim((string) $data);
    if ($raw === '' || strlen($raw) < 4) {
        return;
    }

    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $raw)) {
            continue;
        }

        if (class_exists('Logger')) {
            Logger::danger('Security', 'sensitive_input_detected', 'Phat hien input nghi van tren route nhay cam', [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'field' => $fieldPath,
                'matched_pattern' => $pattern,
                'input' => substr($raw, 0, 200),
            ]);
        }
        break;
    }
}

$securityPath = normalizedRequestPathForSecurity();
$securityMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$securityProfile = resolveInputSecurityProfile($securityPath, $securityMethod);

if ($securityProfile !== null) {
    $patterns = maliciousInputPatterns((string) ($securityProfile['patterns'] ?? ''));
    if ($securityMethod === 'GET' && !empty($_GET)) {
        inspectSensitiveInput($_GET, $patterns, 'get');
    }
    if ($securityMethod === 'POST' && !empty($_POST)) {
        inspectSensitiveInput($_POST, $patterns, 'post');
    }
}

// ─── AntiFlood / Anti-Bot Protection ───────────────────
// Runs on every request: rate limit, burst detection, honeypot, IP blacklist
try {
    $shouldSkipAntiFlood = $securityMethod === 'POST' && isTelegramWebhookPathForSecurity($securityPath);
    if (!$shouldSkipAntiFlood) {
        require_once BASE_PATH . '/app/Services/AntiFloodService.php';
        $antiFlood = new AntiFloodService();
        $antiFlood->inspect($securityPath, $securityMethod);

        // Probabilistic cleanup (~1% of requests)
        if (random_int(1, 100) <= 1) {
            $antiFlood->cleanup();
        }
    }
} catch (Throwable $antiFloodError) {
    // Non-blocking: never let anti-flood crash the app
    error_log('AntiFloodService error: ' . $antiFloodError->getMessage());
}

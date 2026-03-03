<?php
// Load EnvHelper if not loaded
if (!class_exists('EnvHelper')) {
    require_once dirname(__DIR__) . '/app/Helpers/EnvHelper.php';
    EnvHelper::load(dirname(__DIR__) . '/.env');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', EnvHelper::get('DB_HOST', 'localhost'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', EnvHelper::get('DB_NAME', 'kaishop_v2'));
}
if (!defined('DB_USERNAME')) {
    // Support both DB_USER (preferred in .env) and DB_USERNAME (legacy fallback)
    $dbUser = (string) EnvHelper::get('DB_USER', '');
    if ($dbUser === '') {
        $dbUser = (string) EnvHelper::get('DB_USERNAME', 'root');
    }
    define('DB_USERNAME', $dbUser);
}
if (!defined('DB_PASSWORD')) {
    // Support both DB_PASS (preferred in .env) and DB_PASSWORD (legacy fallback)
    $dbPass = (string) EnvHelper::get('DB_PASS', '');
    if ($dbPass === '') {
        $dbPass = (string) EnvHelper::get('DB_PASSWORD', '');
    }
    define('DB_PASSWORD', $dbPass);
}

$connection = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$connection) {
    $errMsg = mysqli_connect_error();
    error_log("DB Connection failed: " . $errMsg);
    // Show friendly error instead of crashing with die()
    http_response_code(500);
    if (file_exists(dirname(__DIR__) . '/500.php')) {
        require dirname(__DIR__) . '/500.php';
    } else {
        echo '<h1>Loi he thong</h1><p>Khong the ket noi co so du lieu. Vui long thu lai sau.</p>';
    }
    exit;
}

$connection->set_charset("utf8mb4");

// For backward compatibility
$ketnoi = $connection;
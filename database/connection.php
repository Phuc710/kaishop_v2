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
    define('DB_USERNAME', EnvHelper::get('DB_USERNAME', 'root'));
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', EnvHelper::get('DB_PASSWORD', ''));
}

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

$connection->set_charset("utf8mb4");

// For backward compatibility
$ketnoi = $connection;
?>
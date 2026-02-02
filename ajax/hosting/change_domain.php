<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$hostingId = antixss($_POST['id'] ?? '');
$newDomain = antixss($_POST['domain'] ?? '');

// Check authentication
if (!isset($_SESSION['session'])) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ];
    echo json_encode($response);
    exit;
}

$sessionToken = $_SESSION['session'];

// Get user data
$userQuery = $connection->query("SELECT * FROM `users` WHERE `session` = '$sessionToken'");
$userData = $userQuery->fetch_array();

if (!$userData) {
    $response = [
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ'
    ];
    echo json_encode($response);
    exit;
}

// Validate inputs
if (empty($hostingId) || empty($newDomain)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get hosting details
$hostingQuery = $connection->query("SELECT * FROM `lich_su_mua_host` WHERE `id` = '$hostingId' AND `username` = '{$userData['username']}'");
$hostingData = $hostingQuery->fetch_array();

if (!$hostingData) {
    $response = [
        'success' => false,
        'message' => 'Hosting không tồn tại hoặc không thuộc về bạn'
    ];
    echo json_encode($response);
    exit;
}

// Update domain
$updateDomain = $connection->query("UPDATE `lich_su_mua_host` SET `domain` = '$newDomain' WHERE `id` = '$hostingId'");

if ($updateDomain) {
    $response = [
        'success' => true,
        'message' => 'Đổi tên miền thành công!'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$hostingId = antixss($_POST['id'] ?? '');
$newPassword = antixss($_POST['password'] ?? '');

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
if (empty($hostingId) || empty($newPassword)) {
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

// Update password
$updatePassword = $connection->query("UPDATE `lich_su_mua_host` SET `mk_host` = '$newPassword' WHERE `id` = '$hostingId'");

if ($updatePassword) {
    $response = [
        'success' => true,
        'message' => 'Đổi mật khẩu thành công!'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

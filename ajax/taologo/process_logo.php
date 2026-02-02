<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$logoName = antixss($_POST['tenlogo'] ?? '');
$logoStyle = antixss($_POST['kieu'] ?? '');

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
if (empty($logoName) || empty($logoStyle)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get logo package price
$packageQuery = $connection->query("SELECT * FROM `goi_taologo` WHERE `kieu` = '$logoStyle'");
$packageData = $packageQuery->fetch_array();

if (!$packageData) {
    $response = [
        'success' => false,
        'message' => 'Kiểu logo không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

$logoPrice = $packageData['gia'];

// Check balance
if ($userData['money'] < $logoPrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($logoPrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Create logo order
$currentTime = time();
$insertLogo = "INSERT INTO `history_taologo` SET
    `username` = '{$userData['username']}',
    `tenlogo` = '$logoName',
    `kieu` = '$logoStyle',
    `gia` = '$logoPrice',
    `trang_thai` = 'pending',
    `time` = '$currentTime'";

if ($connection->query($insertLogo)) {
    // Deduct money
    $newBalance = $userData['money'] - $logoPrice;
    $connection->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Đặt tạo logo thành công! Vui lòng chờ admin xử lý'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

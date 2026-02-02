<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$cardType = antixss($_POST['loaithe'] ?? '');
$cardAmount = antixss($_POST['menhgia'] ?? '');
$cardSerial = antixss($_POST['seri'] ?? '');
$cardCode = antixss($_POST['pin'] ?? '');

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
if (empty($cardType) || empty($cardAmount) || empty($cardSerial) || empty($cardCode)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin thẻ'
    ];
    echo json_encode($response);
    exit;
}

// Insert card transaction record
$currentTime = time();
$insertCard = "INSERT INTO `history_nap_the` SET
    `username` = '{$userData['username']}',
    `loaithe` = '$cardType',
    `menhgia` = '$cardAmount',
    `serial` = '$cardSerial',
    `pin` = '$cardCode',
    `trangthai` = 'pending',
    `thucnhan` = '0',
    `time` = '$currentTime'";

if ($connection->query($insertCard)) {
    $response = [
        'success' => true,
        'message' => 'Gửi thẻ thành công! Vui lòng chờ xử lý (5-15 phút)'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

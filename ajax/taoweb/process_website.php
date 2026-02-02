<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$websiteName = antixss($_POST['tenweb'] ?? '');
$templateId = antixss($_POST['mau'] ?? '');
$domainName = antixss($_POST['domain'] ?? '');

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
if (empty($websiteName) || empty($templateId) || empty($domainName)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get template details
$templateQuery = $connection->query("SELECT * FROM `ds_template` WHERE `id` = '$templateId'");
$templateData = $templateQuery->fetch_array();

if (!$templateData) {
    $response = [
        'success' => false,
        'message' => 'Template không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

$websitePrice = $templateData['gia'];

// Check balance
if ($userData['money'] < $websitePrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($websitePrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Create website order
$currentTime = time();
$insertWebsite = "INSERT INTO `history_taoweb` SET
    `username` = '{$userData['username']}',
    `tenweb` = '$websiteName',
    `template` = '{$templateData['ten']}',
    `domain` = '$domainName',
    `gia` = '$websitePrice',
    `trang_thai` = 'pending',
    `time` = '$currentTime'";

if ($connection->query($insertWebsite)) {
    // Deduct money
    $newBalance = $userData['money'] - $websitePrice;
    $connection->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Đặt tạo website thành công! Vui lòng chờ admin triển khai'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

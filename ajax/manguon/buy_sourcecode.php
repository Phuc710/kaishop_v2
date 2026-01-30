<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$sourceCodeId = antixss($_POST['code_id'] ?? '');

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
$userQuery = $ketnoi->query("SELECT * FROM `users` WHERE `session` = '$sessionToken'");
$userData = $userQuery->fetch_array();

if (!$userData) {
    $response = [
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ'
    ];
    echo json_encode($response);
    exit;
}

// Validate input
if (empty($sourceCodeId)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng chọn mã nguồn'
    ];
    echo json_encode($response);
    exit;
}

// Get source code details
$codeQuery = $ketnoi->query("SELECT * FROM `ds_manguon` WHERE `id` = '$sourceCodeId'");
$codeData = $codeQuery->fetch_array();

if (!$codeData) {
    $response = [
        'success' => false,
        'message' => 'Mã nguồn không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

$codePrice = $codeData['gia'];

// Check if user already purchased this code
$checkPurchase = $ketnoi->query("SELECT * FROM `history_muacode` WHERE `username` = '{$userData['username']}' AND `code_id` = '$sourceCodeId'");

if ($checkPurchase->num_rows > 0) {
    $response = [
        'success' => false,
        'message' => 'Bạn đã mua mã nguồn này rồi'
    ];
    echo json_encode($response);
    exit;
}

// Check balance
if ($userData['money'] < $codePrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($codePrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Create purchase record
$currentTime = time();
$insertPurchase = "INSERT INTO `history_muacode` SET
    `username` = '{$userData['username']}',
    `code_id` = '$sourceCodeId',
    `ten_code` = '{$codeData['ten']}',
    `gia` = '$codePrice',
    `link_download` = '{$codeData['link']}',
    `time` = '$currentTime'";

if ($ketnoi->query($insertPurchase)) {
    // Deduct money
    $newBalance = $userData['money'] - $codePrice;
    $ketnoi->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Mua mã nguồn thành công!',
        'download_link' => $codeData['link']
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

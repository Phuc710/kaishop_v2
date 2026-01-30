<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$domainName = antixss($_POST['domain'] ?? '');
$duration = antixss($_POST['time'] ?? '');
$extensionId = antixss($_POST['duoi'] ?? '');

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

// Validate inputs
if (empty($domainName) || empty($duration) || empty($extensionId)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get extension details
$extensionQuery = $ketnoi->query("SELECT * FROM `duoimien` WHERE `id` = '$extensionId'");
$extensionData = $extensionQuery->fetch_array();

if (!$extensionData) {
    $response = [
        'success' => false,
        'message' => 'Đuôi miền không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

// Calculate price
$pricePerYear = $extensionData['gia'];
$totalPrice = $pricePerYear * (int)$duration;

// Check if user has enough money
if ($userData['money'] < $totalPrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($totalPrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Check if domain already exists for this user
$checkDomain = $ketnoi->query("SELECT * FROM `history_domain` WHERE `username` = '{$userData['username']}' AND `domain` = '$domainName' AND `duoi` = '{$extensionData['duoi']}'");

if ($checkDomain->num_rows > 0) {
    $response = [
        'success' => false,
        'message' => 'Bạn đã mua tên miền này rồi'
    ];
    echo json_encode($response);
    exit;
}

// Calculate expiration
$currentTime = time();
$expirationTime = $currentTime + ((int)$duration * 365 * 24 * 60 * 60);

// Create domain record
$insertDomain = "INSERT INTO `history_domain` SET
    `username` = '{$userData['username']}',
    `domain` = '$domainName',
    `duoi` = '{$extensionData['duoi']}',
    `ngay_mua` = '$currentTime',
    `ngay_het_han` = '$expirationTime',
    `trang_thai` = 'Active'";

if ($ketnoi->query($insertDomain)) {
    // Deduct money
    $newBalance = $userData['money'] - $totalPrice;
    $ketnoi->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Mua tên miền thành công!'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

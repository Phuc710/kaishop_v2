<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$packageId = antixss($_POST['goihost'] ?? '');
$duration = antixss($_POST['time'] ?? '');
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
if (empty($packageId) || empty($duration) || empty($domainName)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get package details
$packageQuery = $ketnoi->query("SELECT * FROM `goihost` WHERE `id` = '$packageId'");
$packageData = $packageQuery->fetch_array();

if (!$packageData) {
    $response = [
        'success' => false,
        'message' => 'Gói hosting không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

// Calculate price
$pricePerMonth = $packageData['gia'];
$totalPrice = $pricePerMonth * (int)$duration;

// Check if user has enough money
if ($userData['money'] < $totalPrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($totalPrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Generate credentials
$hostingUsername = $userData['username'] . '_' . random('abcdefghijklmnopqrstuvwxyz0123456789', 8);
$hostingPassword = random('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 12);

// Calculate expiration
$currentTime = time();
$expirationTime = $currentTime + ((int)$duration * 30 * 24 * 60 * 60); // months to seconds

// Create hosting record
$insertHosting = "INSERT INTO `lich_su_mua_host` SET
    `username` = '{$userData['username']}',
    `goihost` = '{$packageData['ten']}',
    `domain` = '$domainName',
    `host_username` = '$hostingUsername',
    `host_password` = '$hostingPassword',
    `ngay_mua` = '$currentTime',
    `ngay_het_han` = '$expirationTime',
    `trang_thai` = 'Active'";

if ($ketnoi->query($insertHosting)) {
    // Deduct money
    $newBalance = $userData['money'] - $totalPrice;
    $ketnoi->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Mua hosting thành công!',
        'credentials' => [
            'username' => $hostingUsername,
            'password' => $hostingPassword
        ]
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

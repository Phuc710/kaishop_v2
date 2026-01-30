<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$subdomainName = antixss($_POST['subdomain'] ?? '');
$parentDomain = antixss($_POST['duoi'] ?? '');
$duration = antixss($_POST['time'] ?? '');

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
if (empty($subdomainName) || empty($parentDomain) || empty($duration)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Get subdomain package details
$packageQuery = $ketnoi->query("SELECT * FROM `ds_subdomain` WHERE `duoi` = '$parentDomain'");
$packageData = $packageQuery->fetch_array();

if (!$packageData) {
    $response = [
        'success' => false,
        'message' => 'Tên miền phụ không tồn tại'
    ];
    echo json_encode($response);
    exit;
}

// Calculate price
$pricePerMonth = $packageData['gia'];
$totalPrice = $pricePerMonth * (int)$duration;

// Check balance
if ($userData['money'] < $totalPrice) {
    $response = [
        'success' => false,
        'message' => 'Số dư không đủ. Cần ' . number_format($totalPrice) . ' VNĐ'
    ];
    echo json_encode($response);
    exit;
}

// Create full subdomain
$fullSubdomain = $subdomainName . $parentDomain;

// Check if subdomain already exists
$checkSubdomain = $ketnoi->query("SELECT * FROM `history_subdomain` WHERE `username` = '{$userData['username']}' AND `subdomain` = '$fullSubdomain'");

if ($checkSubdomain->num_rows > 0) {
    $response = [
        'success' => false,
        'message' => 'Bạn đã thuê subdomain này rồi'
    ];
    echo json_encode($response);
    exit;
}

// Calculate expiration
$currentTime = time();
$expirationTime = $currentTime + ((int)$duration * 30 * 24 * 60 * 60);

// Create subdomain record
$insertSubdomain = "INSERT INTO `history_subdomain` SET
    `username` = '{$userData['username']}',
    `subdomain` = '$fullSubdomain',
    `duoi` = '$parentDomain',
    `ngay_thue` = '$currentTime',
    `ngay_het_han` = '$expirationTime',
    `trang_thai` = 'Active'";

if ($ketnoi->query($insertSubdomain)) {
    // Deduct money
    $newBalance = $userData['money'] - $totalPrice;
    $ketnoi->query("UPDATE `users` SET `money` = '$newBalance' WHERE `username` = '{$userData['username']}'");
    
    $response = [
        'success' => true,
        'message' => 'Thuê subdomain thành công!'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Có lỗi xảy ra, vui lòng thử lại'
    ];
}

echo json_encode($response);

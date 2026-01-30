<?php
require __DIR__ . '/../../hethong/config.php';

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

// Get all domains for this user
$domainsQuery = $ketnoi->query("SELECT * FROM `history_domain` WHERE `username` = '{$userData['username']}' ORDER BY `id` DESC");

$domainsList = [];
while ($domain = $domainsQuery->fetch_array()) {
    $domainsList[] = [
        'id' => $domain['id'],
        'domain' => $domain['domain'],
        'duoi' => $domain['duoi'],
        'ngay_mua' => $domain['ngay_mua'],
        'ngay_het_han' => $domain['ngay_het_han'],
        'trang_thai' => $domain['trang_thai']
    ];
}

$response = [
    'success' => true,
    'domains' => $domainsList,
    'total' => count($domainsList)
];

echo json_encode($response);

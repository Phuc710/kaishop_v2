<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$username = antixss($_POST['username'] ?? '');
$password = antixss($_POST['password'] ?? '');

// Validate required fields
if (empty($username) || empty($password)) {
    $response = [
        'success' => false, 
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Check user exists
$userQuery = $connection->query("SELECT * FROM `users` WHERE `username` = '$username'");
$userData = $userQuery->fetch_array();

// User not found
if (empty($userData)) {
    $response = [
        'success' => false, 
        'message' => 'Thông tin đăng nhập không chính xác'
    ];
    echo json_encode($response);
    exit;
}

// Verify password (sha1(md5()))
$hashedPassword = sha1(md5($password));
if ($userData['password'] !== $hashedPassword) {
    $response = [
        'success' => false, 
        'message' => 'Mật khẩu không chính xác'
    ];
    echo json_encode($response);
    exit;
}

// Login successful - generate session
$sessionToken = random('0123456789qwertyuiopasdfghjlkzxcvbnmQEWRWROIWCJHSCNJKFBJWQ', 32);
$connection->query("UPDATE `users` SET `session` = '$sessionToken' WHERE `username` = '{$userData['username']}'");
$_SESSION['session'] = $sessionToken;

$response = [
    'success' => true,
    'message' => 'Đăng nhập thành công'
];

echo json_encode($response);

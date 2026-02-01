<?php
require __DIR__ . '/../../hethong/config.php';

// Get and sanitize input
$email = antixss($_POST['email'] ?? '');
$username = antixss($_POST['username'] ?? '');
$password = antixss($_POST['password'] ?? '');

// Check if user exists
$checkUser = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username'");
$checkEmail = $ketnoi->query("SELECT * FROM `users` WHERE `email` = '$email'");

// Validate required fields
if (empty($username) || empty($password) || empty($email)) {
    $response = [
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin'
    ];
    echo json_encode($response);
    exit;
}

// Check username format (alphanumeric only)
if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
    $response = [
        'success' => false,
        'message' => 'Tên đăng nhập không bao gồm các kí tự đặc biệt và có dấu'
    ];
    echo json_encode($response);
    exit;
}

// Check if username = password
if ($username === $password) {
    $response = [
        'success' => false,
        'message' => 'Tên Đăng Nhập Không Được Trùng Mật Khẩu!'
    ];
    echo json_encode($response);
    exit;
}

// Check email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = [
        'success' => false,
        'message' => 'Email Không Đúng Định Dạng!'
    ];
    echo json_encode($response);
    exit;
}

// Check if username already exists
if ($checkUser->num_rows > 0) {
    $response = [
        'success' => false,
        'message' => 'Tên đăng nhập đã tồn tại'
    ];
    echo json_encode($response);
    exit;
}

// Check if email already exists
if ($checkEmail->num_rows > 0) {
    $response = [
        'success' => false,
        'message' => 'Email đã được sử dụng'
    ];
    echo json_encode($response);
    exit;
}

// Hash password
$hashedPassword = sha1(md5($password));
$sessionToken = random('0123456789qwertyuiopasdfghjlkzxcvbnmQEWRWROIWCJHSCNJKFBJWQ', 32);

// Create new user
$sql = "INSERT INTO `users` SET
    `username` = '$username',
    `password` = '$hashedPassword',
    `email` = '$email',
    `session` = '$sessionToken',
    `money` = '0',
    `tong_nap` = '0',
    `level` = '0',
    `bannd` = '0',
    `time` = '" . time() . "',
    `ip` = '$ip_address'";

if ($ketnoi->query($sql)) {
    $_SESSION['session'] = $sessionToken;
    $response = [
        'success' => true,
        'message' => 'Đăng ký thành công'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Đã có lỗi xảy ra, vui lòng thử lại!'
    ];
}

echo json_encode($response);

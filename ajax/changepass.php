<?php
require_once __DIR__ . '/../hethong/config.php';

$session = $_SESSION['session'] ?? '';
$password1 = strip_tags($_POST['password1'] ?? '');
$password2 = strip_tags($_POST['password2'] ?? '');
$password3 = strip_tags($_POST['password3'] ?? '');

// Lấy thông tin user
$user = $connection->query("SELECT * FROM `users` WHERE `session` = '$session'")->fetch_array();
$username = $user['username'] ?? '';

// Kiểm tra đăng nhập
if (empty($session) || !$user) {
    $response = ['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.'];
}
// Kiểm tra thông tin nhập
elseif (empty($password1) || empty($password2) || empty($password3)) {
    $response = ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
}
// Kiểm tra độ dài mật khẩu
elseif (strlen($password2) < 8 || strlen($password2) > 20) {
    $response = ['success' => false, 'message' => 'Mật khẩu mới phải từ 8 đến 20 ký tự'];
}
// Kiểm tra khớp mật khẩu mới
elseif ($password2 !== $password3) {
    $response = ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'];
}
// Kiểm tra mật khẩu hiện tại
elseif ($user['password'] != sha1(md5($password1))) {
    $response = ['success' => false, 'message' => 'Mật khẩu hiện tại chưa chính xác'];
}
else {
    // Cập nhật mật khẩu
    $newpass = sha1(md5($password2));
    $stmt = $connection->prepare("UPDATE `users` SET `password` = ? WHERE `username` = ?");
    $stmt->bind_param("ss", $newpass, $username);
    $stmt->execute();

    sendTele("$username đã đổi mật khẩu thành công");
    $response = ['success' => true, 'message' => 'Cập nhật thành công!'];
}

echo json_encode($response);
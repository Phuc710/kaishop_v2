<?php require __DIR__ . '/../../hethong/config.php'; ?>

<?php
$password  = antixss($_POST['password']);
$otpcode   = antixss($_POST['otpcode']);

if ($otpcode == '' || $password == '') {
    $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
} else {
    // Kiểm tra OTP có tồn tại
    $stmt_check = $connection->prepare("SELECT * FROM `users` WHERE `otpcode` = ?");
    $stmt_check->bind_param("s", $otpcode);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows != 1) {
        $response = array('success' => false, 'message' => 'OTP Code không đúng '.$otpcode);
    } else {
        $new_pass = sha1(md5($password));

        // Cập nhật mật khẩu
        $stmt_update = $connection->prepare("UPDATE `users` SET `password` = ?, `otpcode` = '' WHERE `otpcode` = ?");
        $stmt_update->bind_param("ss", $new_pass, $otpcode);
        $stmt_update->execute();

        $response = array('success' => true, 'message' => 'Mật khẩu đã được cập nhật');
        $stmt_update->close();
    }

    $stmt_check->close();
}

echo json_encode($response);
?>
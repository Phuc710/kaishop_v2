<?php
require __DIR__ . '/../../hethong/config.php';

$username = antixss($_POST['username'] ?? '');

// Kiểm tra nếu username trống
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit();
}

// Xác định xem là email hay username thường
if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `email` = ?");
} else {
    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `username` = ?");
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$check = $result->fetch_assoc();
$stmt->close();

if ($check) {
    $otpcode = bin2hex(random_bytes(16)); // Mã reset

    $guitoi = $check['email'];   
    $subject = 'Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản '.$check['username'].'.';
    $bcc = 'Đặt Lại Mật Khẩu';
    $hoten = 'Hỗ trợ hệ thống';
    
    $noi_dung = '
    <p>Kính chào quý khách hàng <b>'.$check['username'].'</b>,</p>
    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu của bạn. Nếu bạn là người thực hiện yêu cầu này, hãy nhấp vào liên kết bên dưới để đặt lại mật khẩu.</p>
    <p><b>Lưu ý:</b> Nếu bạn không thực hiện yêu cầu này, vui lòng không nhấp vào liên kết và bỏ qua email này.</p>
    <p>🔗 <a href="https://'.$_SERVER['SERVER_NAME'].'/resetpass?id='.$otpcode.'" target="_blank"><b>ĐẶT LẠI MẬT KHẨU</b></a></p>
    <p>Website: <a href="https://'.$_SERVER['SERVER_NAME'].'/" target="_blank"><b>'.$_SERVER['SERVER_NAME'].'</b></a></p>
    <p>Trân trọng,<br>Hỗ trợ khách hàng</p>';

    $send_status = sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);

    if ($send_status) {
        // Update OTP an toàn
        $stmt = $connection->prepare("UPDATE `users` SET `otpcode` = ? WHERE `username` = ?");
        $stmt->bind_param("ss", $otpcode, $check['username']);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Email đặt lại mật khẩu đã được gửi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể gửi email, vui lòng thử lại']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại']);
}
?>
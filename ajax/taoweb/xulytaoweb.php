<?php
require __DIR__ . '/../../hethong/config.php';

if ($username == "") {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$id_web = strip_tags($_POST['id_web']);
$domain = strip_tags($_POST['domain']);
$user_admin = strip_tags($_POST['user_admin']);
$pass_admin = strip_tags($_POST['pass_admin']);

if (empty($id_web) || empty($domain) || empty($user_admin) || empty($pass_admin)) {
    echo json_encode(['success' => false, 'message' => 'Hãy nhập đủ thông tin.']);
    exit;
} elseif (!preg_match('/^[a-zA-Z0-9]+$/', $user_admin)) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản không được nhập dấu hoặc ký tự đặc biệt']);
    exit;
} elseif (!preg_match('/^[a-zA-Z0-9]+$/', $pass_admin)) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu không được nhập dấu hoặc ký tự đặc biệt']);
    exit;
}

// Truy vấn mẫu web bằng prepared statement
$stmt = $ketnoi->prepare("SELECT * FROM `list_mau_web` WHERE `id` = ?");
$stmt->bind_param("s", $id_web);
$stmt->execute();
$result = $stmt->get_result();
$api_site = $result->fetch_assoc();
$stmt->close();

if (!$api_site) {
    echo json_encode(['success' => false, 'message' => 'Site không tồn tại.']);
    exit;
}

if (strpos($domain, '.') === false) {
    echo json_encode(['success' => false, 'message' => 'Tên miền không hợp lệ.']);
    exit;
}

if ($user['money'] < $api_site['gia']) {
    echo json_encode(['success' => false, 'message' => 'Số dư trong tài khoản không đủ, vui lòng nạp thêm.']);
    exit;
}

$now = time();
$magd = random('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3) . $now;
$ngay_het_han = $now + (30 * 86400);

// Thêm lịch sử tạo web (prepared statement)
$stmt = $ketnoi->prepare("INSERT INTO `lich_su_tao_web` 
    (`trans_id`, `username`, `loaiweb`, `domain`, `user_admin`, `pass_admin`, `ngay_mua`, `ngay_het`, `status`, `time`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'xuly', ?)");
$stmt->bind_param("ssissssii", $magd, $username, $api_site['id'], $domain, $user_admin, $pass_admin, $now, $ngay_het_han, $now);
$success_insert = $stmt->execute();
$stmt->close();

if ($success_insert) {
    sendTele("Đã Có 1 Thành Viên Tạo Web 🛒
• Tài Khoản: $username
• Tk Admin: $user_admin
• Mk Admin: $pass_admin
• Tên Miền $domain
• Đã Tạo Trang Web {$api_site['title']}
• Mã Giao Dịch: $magd");

    // Gửi email
    $guitoi = $user['email'];
    $subject = 'Bạn đã tạo web '.$api_site['title'].' thành công';
    $bcc = 'Tạo Web Thành Công';
    $hoten = 'SERVER';
    $noi_dung = '<p>Kính chào quý khách hàng <b>'.$user['username'].'</b>,</p>
        <p>Bạn đã tạo web <b>'.$api_site['title'].'</b> thành công.</p>
        <p>Bạn có thể quản lý mã nguồn tại <a href="https://'.$_SERVER['SERVER_NAME'].'/history-tao-web" target="_blank">tại đây</a>.</p>
        <p>Tham Gia Channel Dailycode <a href="https://t.me/dailycodechannel" target="_blank">Tại Đây</a>.</p>
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi. Cảm ơn!</p>
        <p>Website: <b><a href="https://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></b></p>';

    sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);

    // Trừ tiền
    $newmoney = $user['money'] - $api_site['gia'];
    $stmt = $ketnoi->prepare("UPDATE `users` SET `money` = ? WHERE `username` = ?");
    $stmt->bind_param("is", $newmoney, $username);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể tạo web.']);
}
?>
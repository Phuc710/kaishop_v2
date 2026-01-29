<?php require __DIR__ . '/../../hethong/config.php';

$username = strip_tags($_POST['username']);
$ten_mien = strip_tags($_POST['ten_mien']);
$duoimien = strip_tags($_POST['duoimien']);
$nameserver = strip_tags($_POST['nameserver']);
$now = time();

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

// Lấy thông tin user
$stmt_user = $ketnoi->prepare("SELECT * FROM `users` WHERE `username` = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại!']);
    exit;
}

// Kiểm tra input
if (empty($ten_mien) || empty($duoimien) || empty($nameserver)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

// Lấy giá domain theo đuôi
$stmt_mien = $ketnoi->prepare("SELECT * FROM `ds_domain` WHERE `duoimien` = ?");
$stmt_mien->bind_param("s", $duoimien);
$stmt_mien->execute();
$mien_result = $stmt_mien->get_result();
$mien = $mien_result->fetch_assoc();

if (!$mien) {
    echo json_encode(['success' => false, 'message' => 'Đuôi miền không hợp lệ!']);
    exit;
}

// Kiểm tra tiền
if ($user['money'] < $mien['gia']) {
    echo json_encode(['success' => false, 'message' => 'Số dư không đủ ' . tien($mien['gia']) . 'đ']);
    exit;
}

// Gửi request kiểm tra whois
$domain = $ten_mien . $duoimien;
$url = 'https://whois.net.vn/whois.php?domain=' . urlencode($domain);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$data = curl_exec($ch);
curl_close($ch);

$st_mien = json_decode($data, true);

// Nếu domain chưa có người mua
if ($st_mien != 1) {
    $het = $now + (365 * 86400);

    // Thêm vào history_domain
    $stmt_insert = $ketnoi->prepare("INSERT INTO `history_domain` (`username`, `domain`, `duoimien`, `nameserver`, `ngay_mua`, `ngay_het`, `status`) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'xuly')");
    $stmt_insert->bind_param("ssssii", $username, $domain, $duoimien, $nameserver, $now, $het);
    $insert_success = $stmt_insert->execute();

    if ($insert_success) {
        // Trừ tiền
        $newmoney = $user['money'] - $mien['gia'];
        $stmt_update = $ketnoi->prepare("UPDATE `users` SET `money` = ? WHERE `username` = ?");
        $stmt_update->bind_param("is", $newmoney, $username);
        $stmt_update->execute();

        sendTele($username." Đã Đăng Kí Miền\n• Tên Miền: $domain\n• Nameserver: $nameserver\n• Trạng Thái: Đang Xử Lý.");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể đăng ký domain, vui lòng thử lại!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Đã có người mua miền này!']);
}
?>
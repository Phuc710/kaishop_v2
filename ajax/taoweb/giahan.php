<?php require __DIR__ . '/../../hethong/config.php'; ?>
<?php
$now = time(); 
$id_web = strip_tags($_POST['id_web']);
$giahan = intval($_POST['giahan']); // Chuyển thành số nguyên cho an toàn

if ($username == "") {
    $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện tính năng này');
    echo json_encode($response);
    exit;
}

if ($id_web == '' || $giahan == 0) {
    $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
    echo json_encode($response);
    exit;
}

// Lấy thông tin lịch sử tạo web
$stmt = $connection->prepare("SELECT * FROM `lich_su_tao_web` WHERE `id` = ?");
$stmt->bind_param("i", $id_web);
$stmt->execute();
$result = $stmt->get_result();
$api_site = $result->fetch_array();

if (!$api_site) {
    $response = array('success' => false, 'message' => 'Không tìm thấy thông tin web');
    echo json_encode($response);
    exit;
}

// Lấy thông tin mẫu web
$stmt2 = $connection->prepare("SELECT * FROM `list_mau_web` WHERE `id` = ?");
$stmt2->bind_param("i", $api_site['loaiweb']);
$stmt2->execute();
$result2 = $stmt2->get_result();
$api_loai = $result2->fetch_array();

if (!$api_loai) {
    $response = array('success' => false, 'message' => 'Không tìm thấy loại web');
    echo json_encode($response);
    exit;
}

$tongtien = $api_loai['gia_han'] * $giahan;
$days_to_add = 30 * $giahan;
$het = $api_site['ngay_het'] + ($days_to_add * 86400);

if ($username != $api_site['username']) {
    $response = array('success' => false, 'message' => 'Bạn không có quyền thực hiện yêu cầu này!');
} elseif ($user['money'] < $tongtien) {
    $response = array('success' => false, 'message' => 'Số dư trong tài khoản không đủ ' . tien($tongtien) . 'đ, vui lòng nạp thêm');
} else {
    // Cập nhật hạn sử dụng
    $stmt3 = $connection->prepare("UPDATE `lich_su_tao_web` SET `ngay_het` = ?, `status` = 'hoatdong' WHERE `id` = ?");
    $stmt3->bind_param("ii", $het, $id_web);
    $stmt3->execute();

    sendTele("Tài Khoản: ".$username." Đã Gia Hạn Web Thành Công Admin Vui Lòng Xem Xét!
• Tên miền: ".$api_site['domain']);

    // Trừ tiền
    $newmoney = $user['money'] - $tongtien;
    $stmt4 = $connection->prepare("UPDATE `users` SET `money` = ? WHERE `username` = ?");
    $stmt4->bind_param("is", $newmoney, $username);
    $stmt4->execute();

    if ($stmt3->affected_rows > 0 && $stmt4->affected_rows > 0) {
        $response = array('success' => true);
    } else {
        $response = array('success' => false, 'message' => 'Không thể gia hạn. Vui lòng thử lại.');
    }
}

echo json_encode($response);
?>
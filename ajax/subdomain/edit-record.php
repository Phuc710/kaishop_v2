<?php
require __DIR__ . '/../../hethong/config.php';

$response = array('success' => false, 'message' => 'Có lỗi xảy ra.');

// Lấy dữ liệu POST và xóa thẻ HTML
$id = strip_tags($_POST['id'] ?? '');
$type = strip_tags($_POST['type'] ?? '');
$name = strip_tags($_POST['name'] ?? '');
$content = strip_tags($_POST['content'] ?? '');

// Kiểm tra đăng nhập
if (empty($username)) {
    $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện tính năng này');
} elseif (!isset($user['username'])) {
    $response = array('success' => false, 'message' => 'Vui lòng đăng nhập');
} elseif (empty($type) || empty($name) || empty($content)) {
    $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
} else {
    $record = $connection->query("SELECT * FROM `list_record_domain` WHERE `id` = '$id'")->fetch_array();
    $mien = $connection->query("SELECT * FROM `history_domain` WHERE `id` = '".$record['id_domain']."'")->fetch_array();

    if (!empty($mien['zone_id'])) {
        // Thông tin xác thực API
        $api_key = $tozpie['api_cf'];
        $email = $tozpie['email_cf'];
        $zone_id = $mien['zone_id']; // ID của vùng chứa tên miền chính
        $record_id = $record['record_id']; // ID của bản ghi cần cập nhật

        // Tạo yêu cầu PUT để cập nhật bản ghi DNS cho tên miền phụ
        $data = array(
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => 1,
            'proxied' => false
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records/$record_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-Email: {$email}",
            "X-Auth-Key: {$api_key}",
            "Content-Type: application/json"
        ));

        // Gửi yêu cầu API đến Cloudflare
        $response_cf = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Xử lý kết quả trả về từ Cloudflare
        $result = json_decode($response_cf, true);

        if (isset($result['success']) && $result['success']) {
            // Cập nhật bản ghi trong cơ sở dữ liệu
            $stmt = $connection->prepare("UPDATE `list_record_domain` SET `content` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $content, $id);
            if ($stmt->execute()) {
                $response = array('success' => true, 'message' => 'Cập nhật thành công.');
            } else {
                $response = array('success' => false, 'message' => 'Không thể cập nhật thông tin trong cơ sở dữ liệu.');
            }
            $stmt->close();
        } else {
            // Lỗi từ Cloudflare
            $response = array('success' => false, 'message' => 'Lỗi từ Cloudflare: ' . ($result['errors'][0]['message'] ?? 'Không rõ'));
        }
    } else {
        $response = array('success' => false, 'message' => 'Không tìm thấy zone cho miền.');
    }
}

echo json_encode($response);
<?php
require __DIR__ . '/../../hethong/config.php';

$id = strip_tags($_POST['id']);
$type = strip_tags($_POST['type']);
$name = strip_tags($_POST['name']);
$content = strip_tags($_POST['content']);
$now = time();

$mien = $ketnoi->query("SELECT * FROM `history_domain` WHERE `id` = '$id' ")->fetch_array();
$loaimien = $ketnoi->query("SELECT * FROM `khodomain` WHERE `duoimien` = '".$mien['duoimien']."' ")->fetch_array();

if ($username== "") {
    $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện tính năng này');
} elseif (!$user['username']) {
    $response = array('success' => false, 'message' => 'Vui lòng đăng nhập');
} elseif ($_POST['type'] == "" || $_POST['name'] == '' || $_POST['content'] == '') {
    $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
} else {
    if ($mien['zone_id'] != "") {
        // Thông tin xác thực API
        $api_key = $tozpie['api_cf'];
        $email =  $tozpie['email_cf'];
        $zone_id = $mien['zone_id']; // ID của vùng chứa tên miền chính
        // Tạo yêu cầu POST để tạo bản ghi DNS cho tên miền phụ
        $data = array(
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => 1,
            'proxied' => false
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-Email: {$email}",
            "X-Auth-Key: {$api_key}",
            "Content-Type: application/json"
        ));

        // Gửi yêu cầu API đến Cloudflare
        $response = curl_exec($ch);
        curl_close($ch);

        // Xử lý kết quả trả về từ Cloudflare
        $result = json_decode($response, true);
        if ($result['success']) {
            $record_id = $result['result']['id'];
            $toz = $ketnoi->query("INSERT INTO `list_record_domain` SET 
            `id_domain` = '$id',
            `type` = '$type',
            `name` = '$name',
            `content` = '$content',
            `record_id` = '$record_id',
            `time` = '$now' ");
            if ($toz) {
                $response = array('success' => true);
            }
        } else {
            $response = array('success' => false, 'message' => 'Lỗi: ' . $result['errors'][0]['message']);
        }
    }
}
echo json_encode($response);
?>
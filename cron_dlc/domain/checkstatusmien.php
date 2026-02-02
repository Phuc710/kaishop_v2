<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';

$now = time();
$result_sql = mysqli_query($connection, "SELECT * FROM `history_domain` WHERE `status` = 'hoatdong'");
while ($row = mysqli_fetch_assoc($result_sql)) {
    $domain = $row['tenmien'] . $row['duoimien'];
    if ($now > $row['ngayhet']) {
        $connection->query("UPDATE `history_domain` SET `status` = 'hethan' WHERE `id` = '" . $row['id'] . "' ");
        
        // Thay thế các giá trị sau bằng thông tin API và cài đặt của bạn từ Cloudflare
        $api_key = $tozpie['api_cf'];
        $zone_id = $row['zone_id'];
        $subdomain_name = $row['tenmien']; // Tên của subdomain bạn muốn xoá

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ];

        $resource_url = 'https://api.cloudflare.com/client/v4/zones/' . $zone_id . '/dns_records';

        $data = [
            'name' => $subdomain_name,
            'type' => 'A', // Loại DNS record (ví dụ: 'A' cho địa chỉ IPv4)
        ];

        $ch = curl_init($resource_url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response !== false) {
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_status === 200) {
                // Gửi thông báo hoặc thực hiện các hành động khác sau khi xoá thành công
                sendTele("Subdomain {$subdomain_name} của tên miền {$domain} đã được xoá thành công");
            } else {
                sendTele("Lỗi xoá subdomain {$subdomain_name} của tên miền {$domain}. Mã trạng thái HTTP: {$http_status}");
            }
        } else {
            sendTele("Có lỗi xảy ra khi gửi yêu cầu API để xoá subdomain {$subdomain_name} của tên miền {$domain}");
        }

        curl_close($ch);

    } else {
        echo "Tên miền {$domain} chưa hết hạn.\n";
    }
}

?>

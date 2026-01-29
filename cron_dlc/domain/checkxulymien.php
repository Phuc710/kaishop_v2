<?php require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';?>

<?php
$result_sql = mysqli_query($ketnoi, "SELECT * FROM `history_domain` WHERE `status` = 'xuly'");
while ($row = mysqli_fetch_assoc($result_sql)) {
    if($row['nameserver']==""){
        $ketnoi->query("UPDATE `history_domain` SET `status` = 'hoatdong' WHERE `id` = '" . $row['id'] . "' ");
        echo "Tên miền {$domain} đã được trỏ đến Cloudflare.\n";
    }else{
        $zoneId = $row['zone_id'];

        // Tên miền cần kiểm tra
        $domain = $row['tenmien'] . $row['duoimien'];
    
        // Lấy địa chỉ IP của tên miền
        $domain_ip = gethostbyname($domain);
    
        // Tách nameserver thành mảng và lấy dòng đầu tiên
        $nameservers = explode("\n", $row['nameserver']);
        $first_nameserver = trim($nameservers[0]);
    
        // Lấy địa chỉ IP của dòng đầu tiên của nameserver
        $nameserver_ip = gethostbyname($first_nameserver);
    
        // Lấy tên miền của địa chỉ IP
        $nameserver_domain = gethostbyaddr($nameserver_ip);
    
        // So sánh tên miền của nameserver với nameserver trong $row['nameserver']
        if (strtolower($nameserver_domain) === strtolower($first_nameserver)) {
            $ketnoi->query("UPDATE `history_domain` SET `status` = 'dangtao' WHERE `id` = '" . $row['id'] . "' ");
            echo "Tên miền {$domain} đã được trỏ đến Cloudflare.\n";
        } else {
            echo "Tên miền {$domain} chưa được trỏ đến Cloudflare.\n";
        }
    }
}
?>
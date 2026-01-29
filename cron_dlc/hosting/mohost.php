<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';
$now = time();
?>
<!-- Khoá Host hết hạn -->
<?php
$check_host = $ketnoi->query("SELECT * FROM `lich_su_mua_host` WHERE `status` ='tamkhoa' ");
while ($host = $check_host->fetch_array()) {
    echo $host['domain'];
    $id_host =$host['id'];
    if ($now < $host['ngay_het']) {
        $id_sv = $host['server_host'];
        $sv_host = $ketnoi->query("SELECT * FROM `list_server_host` WHERE `id` = '$id_sv' ")->fetch_array();
        $tkWHM = $sv_host['tk_whm'];
        $mkWHM = $sv_host['mk_whm'];
        $linklogin = $sv_host['link_login'];
        $username1 = $host['username'];
        $user1 = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username1' ")->fetch_array();
        $query = $linklogin.':2087/json-api/unsuspendacct?api.version=1&user='.$host['tk_host'].'&reason='.urlencode('Hết hạn dịch vụ'); // khóa hosting
        $curl = curl_init(); // Create Curl Object 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Allow self-signed certs 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // Allow certs that do not match the hostname 
        curl_setopt($curl, CURLOPT_HEADER, 0); // Do not include header in output 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $header[0] = "Authorization: Basic " . base64_encode($tkWHM . ":" . $mkWHM) . "\n\r";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header); // set the username and password 
        curl_setopt($curl, CURLOPT_URL, $query); // execute the query 
        $result = curl_exec($curl); 
        curl_close($curl);
        $reghost = json_decode($result, true);
        $data = $reghost['metadata'];
        if($data['result']==1){
            $guitoi = $user1['email'];   
            $subject = 'Dịch Vụ Hosting '.$host['domain'].' Đã Được Gia Hành Và Kích Hoạt';
            $bcc = 'Gia Hạn Thành Công - DaiLyCode.Vn';
            $hoten ='DaiLyCode.Vn';
            $noi_dung = '<p>Kính chào quý khách hàng <b>'.$host['username'].'</b>,</p>
            <p>Dịch vụ hosting <b>'.$host['domain'].'</b> của bạn đã gia hạn thành công, chúng tôi đã tiến hành mở host của bạn trở lại.</p>
            <p>Bạn có thể quản lý dịch vụ tại <a href="https://'.$_SERVER['SERVER_NAME'].'/quan-ly-host/'.$host['id'].'" target="_blank">tại đây</a> để có thể sử dụng.</p>
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi. Cảm ơn!</p>
            <p>Liên hệ website: <b><a href="https://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></b></p>';
           $toz = sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);
            $ketnoi->query("UPDATE `lich_su_mua_host` SET `status` = 'hoatdong' WHERE `id` = '$id_host'");
        }
    }
}
?>
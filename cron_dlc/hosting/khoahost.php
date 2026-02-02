<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';
$now = time();
?>
<!-- Khoá Host hết hạn -->
<?php 
$check_host = $connection->query("SELECT * FROM `lich_su_mua_host` WHERE `status` ='hoatdong' ");
while ($host = $check_host->fetch_array()) {
    echo $host['domain'];
    $id_host =$host['id'];
    if ($now > $host['ngay_het']) {
        $username1 = $host['username'];
        $id_sv = $host['server_host'];
        $sv_host = $connection->query("SELECT * FROM `list_server_host` WHERE `id` = '$id_sv' ")->fetch_array();
        $tkWHM = $sv_host['tk_whm'];
        $mkWHM = $sv_host['mk_whm'];
        $linklogin = $sv_host['link_login'];
        $user1 = $connection->query("SELECT * FROM `users` WHERE `username` = '$username1' ")->fetch_array();
        $query = $linklogin.':2087/json-api/suspendacct?api.version=1&user='.$host['tk_host'].'&reason='.urlencode('Hết hạn dịch vụ'); // khóa hosting
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
        
        $guitoi = $user1['email'];   
        $subject = 'Dịch Vụ Hosting '.$host['domain'].' Hết Han';
        $bcc = 'Hết Hạn Dichj Vụ - DaiLyCode.Vn';
        $hoten ='DaiLyCode.Vn';
        $noi_dung = '<p>Kính chào quý khách hàng <b>'.$host['username'].'</b>,</p>
        <p>Dịch vụ hosting <b>'.$host['domain'].'</b> của bạn đã bị khóa bởi hệ thống vì đã hết hạn dịch vụ.</p>
        <p>Bạn có thể gia dịch vụ <a href="https://'.$_SERVER['SERVER_NAME'].'/quan-ly-host/'.$host['id'].'" target="_blank">tại đây</a> để có thể sử dụng.</p>
        <p>P/s: <b>Hosting bạn sẽ bị xóa vĩnh viễn nếu như bạn không gia hạn sau 3 ngày</b></p>
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi. Cảm ơn!</p>
        <p>Website của chúng tôi: <b><a href="https://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></b></p>';
       $toz = sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);

        $connection->query("UPDATE `lich_su_mua_host` SET `status` = 'tamkhoa' WHERE `id` = '$id_host'");
    }
}
?>
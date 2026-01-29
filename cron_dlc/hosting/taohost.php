<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';
$now = time();
?>
<!-- Khoá Host hết hạn -->
<?php
$check_host = $ketnoi->query("SELECT * FROM `lich_su_mua_host` WHERE `status` ='dangtao' ");
$hosts = $check_host->fetch_all(MYSQLI_ASSOC);

foreach ($hosts as $host) {
    $id_sv = $host['server_host'];
    $sv_host = $ketnoi->query("SELECT * FROM `list_server_host` WHERE `id` = '$id_sv' ")->fetch_array();
    $tkWHM = $sv_host['tk_whm'];
    $mkWHM = $sv_host['mk_whm'];
    $linklogin = $sv_host['link_login'];
    $user_host = $host['tk_host'];
    $pass_host = $host['mk_host'];
    $domain = $host['domain'];
    $goi_host = $host['goi_host'];
    $emailuser = $host['email'];
    $reseller = "";
    $goiChuan = $tkWHM.'_'.$goi_host;
    // Mua host rsl
    $query = $linklogin.':2087/json-api/createacct?api.version=1&username='.$user_host.'&domain='.$domain.'&plan='.$goiChuan.'&featurelist=default&password='.$pass_host.'&ip=n&cgi=1&hasshell=1&contactemail='.$emailuser.'&cpmod=jupiter'.$reseller.'&language=vi'; 
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
    if ($data['result'] == '1') {
        $check_pass = $ketnoi->query("UPDATE `lich_su_mua_host` SET `status` = 'hoatdong' WHERE `id` = '".$host['id']."' ");
    } else {
        print_r($data['reason']);
    }
}
?>

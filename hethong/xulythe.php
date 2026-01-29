<?php require_once('config.php'); ?>
<?php
    $webgachthe = $chungapi['web_gach_the'];
	$url = 'https://'.$webgachthe.'/chargingws/v2';
	$partner_id = $chungapi['partner_id']; // nhập partner_id tại nhà cung cấp 
	$partner_key = $chungapi['partner_key']; // nhập partner_key tại nhà cung cấp 
    function creatSign($partner_key, $params) {
        $data = array();
        $data['request_id'] = $params['request_id'];
        $data['code'] = $params['code'];
        $data['partner_id'] = $params['partner_id'];
        $data['serial'] = $params['serial'];
        $data['telco'] = $params['telco'];
        $data['command'] = $params['command'];
        ksort($data);
        $sign = $partner_key;
        foreach ($data as $item) {
            $sign .= $item;
        }
        return md5($sign);
    }
?>
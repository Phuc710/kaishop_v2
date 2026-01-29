<?php
require_once('hethong/config.php');
error_reporting(0);
include 'hethong/xulythe.php';
$callback_sign = md5($partner_key.$_GET['code'].$_GET['serial']);
if($_GET['callback_sign'] == $callback_sign) { 
$getdata['status'] = $_GET['status'];
$getdata['message'] = $_GET['message'];
$getdata['request_id'] = $_GET['request_id'];
$getdata['trans_id'] = $_GET['trans_id'];
$getdata['declared_value'] = $_GET['declared_value'];
$getdata['value'] = $_GET['value'];
$getdata['amount'] = $_GET['amount'];
$getdata['code'] = $_GET['code'];
$getdata['serial'] = $_GET['serial'];
$getdata['telco'] = $_GET['telco'];
$code =  $_GET['code'];  
$seri =  $_GET['serial']; 
$card = $ketnoi->query("SELECT * FROM history_nap_the WHERE `pin` = '$code' AND `seri` = '$seri'  ")->fetch_array();

if ($_GET['status'] == '1') {
        $thucnhan = $card['thucnhan'];
        
        $ketnoi->query("UPDATE history_nap_the SET `status` = 'hoantat' WHERE `pin` = '$code' AND `seri` = '$seri' ");
        sendTele($username." Nạp Thẻ Thành Công| Thực Nhận ".$thucnhan."đ");
        $ketnoi->query("UPDATE users SET `money` = `money` + '$thucnhan', `tong_nap` = `tong_nap` + '$thucnhan' WHERE `username` = '".$card['username']."' ");
    } else {
        $ketnoi->query("UPDATE `history_nap_the` SET `status` = 'thatbai' WHERE `pin` = '$code' AND `seri` = '$seri' ");
    }
}
else
{   
    // echo json_encode(array('zalo' => "https://zalo.me/0947237243", 'msg' => "WEBSITE ĐƯỢC TẠO BỞI DAILYCODE.VN", 'website' => "DAILYCODE.VN"));
}
?>
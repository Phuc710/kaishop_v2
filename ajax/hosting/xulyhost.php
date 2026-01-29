<?php
require __DIR__ . '/../../hethong/config.php';

$now = time();
$date = date('h:i d-m-Y', $now);
$days_to_add = '30';
$het = $now;
$het += $days_to_add * 24 * 60 * 60;
$giftcode = antixss($_POST['giftcode']);
$goi = antixss($_POST['goi']);
$domain = antixss($_POST['domain']);
$emailuser = antixss($_POST['email']);
$host = $ketnoi->query("SELECT * FROM `list_host` WHERE `id` = '$goi' ")->fetch_array();
$discount_data = $ketnoi->query("SELECT * FROM `gift_code` WHERE `type` = 'host' ")->fetch_array();
// Kiểm tra 
// Make sure you have $username defined before using it in the condition
if ($username == "") {
    $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện tính năng này');
} elseif ($goi == '' || $domain == '' || $emailuser == '') {
    $response = array('success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin');
} elseif (strpos($domain, '.') === false) { // Corrected the condition here
    $response = array('success' => false, 'message' => 'Tên miền bạn nhập không hợp lệ!!');
} elseif ($discount_data['soluong'] <= $discount_data['dadung'] && $giftcode != "") {
    $response = array('success' => false, 'message' => 'Mã bạn nhập đã đến giới hạn!');
} elseif ($giftcode != "" && giftcode($giftcode, 'host') == 0) {
    $response = array('success' => false, 'message' => 'Mã giảm giá không hợp lệ');
} else {
    // You should define $user and $server_host before using them here
    $sv_host = $ketnoi->query("SELECT * FROM `list_server_host` WHERE `id` = '$server_host' ")->fetch_array();
    $tongtien = $host['gia'] * (1 - (giftcode($giftcode, 'host') / 100));

    if ($user['money'] < $tongtien) {
        $response = array('success' => false, 'message' => 'Số dư không đủ ' . tien($tongtien));
    } else {
        $server_host = $host['server_host'];
        $tk_whm = $sv_host['tk_whm'];
        $mk_whm = $sv_host['mk_whm'];
        $linklogin = $sv_host['link_login'];
        $user_host = 'dlcd' . random('12345693739873973629000815282', 7);
        $pass_host = 'n' . random('0123456789qwertyuiopasdfghjlkzxcvbnmQEWRWROIWCJHSCNJKFBJWQ', 14);
        $goi_host = $host['code'];
        $tongtien = $host['gia_host'];
        $ip_host = $sv_host['ip_whm'];
        $ns1 = $sv_host['ns1'];
        $ns2 = $sv_host['ns2'];

        $sql = $ketnoi->query("INSERT INTO `lich_su_mua_host` SET 
            `username`    = '$username',
            `domain`      = '$domain',
            `goi_host`    = '$goi_host',
            `server_host` = '$server_host',
            `gia_host`    = '$tongtien',
            `tk_host`     = '$user_host',
            `mk_host`     = '$pass_host',
            `ngay_mua`    = '$now',
            `ngay_het`    = '$het',
            `status`      = 'dangtao',
            `note`        = 'hoatdong',
            `time`        = '$now' ");

        $mienan = hideName($domain);

        if (isset($sql)) {
            $newmoney = $user['money'] - $tongtien;
            $toz = $ketnoi->query("INSERT INTO `lich_su_hoat_dong` SET 
                `username` = '$username',
                `hoatdong` = 'Mua host cho " . $mienan . "',
                `gia` = '" . $tongtien . "',
                `time` = '" . $time . "' ");
            $check_money = $ketnoi->query("UPDATE `users` SET `money` = '$newmoney' WHERE `username` = '" . $username . "' ");
            if ($check_money) {
                $response = array('success' => true, 'message' => 'Mua Hosting Thành Công, Vui Lòng Đợi Vài Phút Để Kích Hoạt!');
            }
        }
    }
}

echo json_encode($response);
?>

<?php
require __DIR__ . '/../../hethong/config.php';

$id_code = antixss($_POST['id_code']);
$giftcode = antixss($_POST['giftcode']);
$check_code = $ketnoi->query("SELECT * FROM `khocode` WHERE `id` = '$id_code' ");
$user = $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$username' ")->fetch_array();
$code = $ketnoi->query("SELECT * FROM `khocode` WHERE `id` = '$id_code' ")->fetch_array();
$discount_data = $ketnoi->query("SELECT * FROM `gift_code` WHERE `type` = 'code' ")->fetch_array();
// Kiểm tra 
if ($username == "") {
  $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện!');
} elseif (!$check_code) {
  $response = array('success' => false, 'message' => 'Code không tồn tại');
} elseif($giftcode!=""&&giftcode($giftcode,'code')==0&&$code['gia']!=0) {
    $response = array('success' => false, 'message' => 'Mã giảm giá không hợp lệ');
}else {
  // Calculate the discounted price
  $discounted_price = $code['gia'] * (1 - (giftcode($giftcode,'code') / 100));

  if ($code['gia'] <= 0 && $giftcode != "") {
    $response = array('success' => false, 'message' => 'Mã nguồn có giá trị 0đ, không áp dụng mã giảm giá');
  } elseif ($user['money'] < $discounted_price) {
    $response = array('success' => false, 'message' => 'Số dư trong tài khoản không đủ, vui lòng nạp thêm');
  } elseif ($discount_data['soluong'] <= $discount_data['dadung'] && $giftcode != "") {
    $response = array('success' => false, 'message' => 'Mã bạn nhập đã đến giới hạn!');
  } else {
    $now = time();
    $magd = random('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3) . rand(1000000000, 9999999999);
    $sql = $ketnoi->query("INSERT INTO `lich_su_mua_code` SET 
        `trans_id` = '$magd',
        `username` = '$username',
        `loaicode` = '".$code['id']."',
        `status` = 'thanhcong',
        `time` = '".$now."' ");
        sendTele($username." Mua Thành Công Mã Nguồn: ".$code['title']." | Giá: ".$code['gia']."đ");
    $guitoi = $user['email'];
    $subject = 'Bạn đã mua mã nguồn '.$code['title'].' thành công';
    $bcc = 'Mua Code Thành Công';
    $hoten = 'server';
    $noi_dung = '<p>Kính chào quý khách hàng <b>'.$user['username'].'</b>,</p>
        <p>Bạn đã mua mã nguồn <b>'.$code['title'].'</b> thành công.</p>
        <p>Bạn có thể tải code <a href="https://'.$_SERVER['SERVER_NAME'].'/history-code" target="_blank">tại đây</a>.</p>
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi. Cảm ơn!</p>
        <p>Website: <b><a href="https://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></b></p>';
    
    $toz = sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);
    
    if ($sql) {
      if ($giftcode != "" && giftcode($giftcode, 'code') != 0) {
        update_code($giftcode);
      }
      $namecode = $code['title'];
      $newmoney = $user['money'] - $discounted_price;
      $check_mo = $ketnoi->query("UPDATE `khocode` SET `buy` =  `buy`+ 1 WHERE `id` = '".$code['id']."' ");
      $check_money = $ketnoi->query("UPDATE `users` SET `money` = '$newmoney' WHERE `username` = '".$username."' ");
      if ($check_money) {
        $toz = $ketnoi->query("INSERT INTO `lich_su_hoat_dong` SET 
                `username` = '$username',
                `hoatdong` = 'Mua mã nguồn',
                `gia` = '".$discounted_price."',
                `time` = '".$time."' ");
        $response = array('success' => true);
      }
    }
  }
}

// Trả về kết quả dưới dạng JSON
echo json_encode($response);
?>

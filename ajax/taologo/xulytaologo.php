<?php
require __DIR__ . '/../../hethong/config.php';

$id_code = antixss($_POST['id_code']);
$giftcode = antixss($_POST['giftcode']);
$yeucau = antixss($_POST['yeucau']);
$check_code = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$id_code' ");
$user = $connection->query("SELECT * FROM `users` WHERE `username` = '$username' ")->fetch_array();
$code = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$id_code' ")->fetch_array();
$discount_data = $connection->query("SELECT * FROM `gift_code` WHERE `type` = 'logo' ")->fetch_array();

// Kiểm tra 
if ($username == "") {
  $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện!');
} elseif (empty($yeucau)) {
  $response = array('success' => false, 'message' => 'Vui lòng nhập tên và yêu cầu tạo logo!');
} elseif (!$check_code) {
  $response = array('success' => false, 'message' => 'Code không tồn tại');
} elseif($giftcode != "" && giftcode($giftcode, 'logo') == 0 && $code['gia'] != 0) {
  $response = array('success' => false, 'message' => 'Mã giảm giá không hợp lệ');
} else {
  // Tính giá sau khi giảm
  $discounted_price = $code['gia'] * (1 - (giftcode($giftcode, 'logo') / 100));

  if ($code['gia'] <= 0 && $giftcode != "") {
    $response = array('success' => false, 'message' => 'Mã nguồn có giá trị 0đ, không áp dụng mã giảm giá');
  } elseif ($user['money'] < $discounted_price) {
    $response = array('success' => false, 'message' => 'Số dư trong tài khoản không đủ, vui lòng nạp thêm');
  } elseif ($discount_data['soluong'] <= $discount_data['dadung'] && $giftcode != "") {
    $response = array('success' => false, 'message' => 'Mã bạn nhập đã đến giới hạn!');
  } else {
    $now = time();
    $magd = random('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3) . rand(1000000000, 9999999999);

    $sql = $connection->query("INSERT INTO `lich_su_tao_logo` SET 
        `trans_id` = '$magd',
        `username` = '$username',
        `loaicode` = '".$code['id']."',
        `yeucau` = '".$yeucau."',
        `status` = 'xuly',
        `time` = '".$now."' ");

    sendTele($username." Yêu Cầu Tạo Logo: ".$yeucau);

    if ($sql) {
      if ($giftcode != "" && giftcode($giftcode, 'logo') != 0) {
        update_code($giftcode);
      }
      $newmoney = $user['money'] - $discounted_price;
      $check_money = $connection->query("UPDATE `users` SET `money` = '$newmoney' WHERE `username` = '".$username."' ");
      if ($check_money) {
        $toz = $connection->query("INSERT INTO `lich_su_hoat_dong` SET 
            `username` = '$username',
            `hoatdong` = 'Tạo logo website',
            `gia` = '".$discounted_price."',
            `time` = '".$time."' ");
        $response = array('success' => true);
      }
    }
  }
}

echo json_encode($response);
?>
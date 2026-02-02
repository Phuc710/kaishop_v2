<?php
require __DIR__ . '/../../hethong/config.php';

$type_bank = strip_tags($_POST['type_bank']);
$bank = $connection->query("SELECT * FROM `list_bank` WHERE `type` = '$type_bank' ")->fetch_array();

if ($bank) {
    $loai = $bank['loai'];
    $stk = $bank['stk'];
    $ctk = $bank['ctk'];
    $img = $bank['img'];
    
    $list_bank[] = array(
        "bank" => "$loai",
        "stk" => "$stk",
        "ctk" => "$ctk",
        "img" => "$img"
    );
} else {
    // Xử lý trường hợp không tìm thấy dữ liệu trong cơ sở dữ liệu
    $list_bank = array(); // Đảm bảo rằng $list_bank được khởi tạo là một mảng trống
}

echo json_encode($list_bank);
?>

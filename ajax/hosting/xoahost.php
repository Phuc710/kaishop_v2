<?php require __DIR__ . '/../../hethong/config.php';?>
<?php
$idhost = strip_tags($_POST['idhost']);
$host = $connection->query("SELECT * FROM `lich_su_mua_host` WHERE `id` = '$idhost' ")->fetch_array();
if($username==""){
    $response = array('success' => false, 'message' => 'Đăng nhập để thực hiện tính năng này');
}elseif ($username != $host['username']) {
   $response = array('success' => false, 'message' => 'Bạn không thể thao tác host này');
}elseif($host['status']!="hoatdong"){
   $response = array('success' => false, 'message' => 'Hãy đợi tiến trình trước đó chạy xong!');
}else{    
$check = $connection->query("UPDATE `lich_su_mua_host` SET `status` = 'xoa' WHERE `id` = '".$idhost."' ");
if (isset($check)) {
    $response = array('success' => true);
} else {
    $response = array('success' => false, 'message' => 'Có lỗi xảy ra, hãy liên hệ admin');
}
}
echo json_encode($response);
?>
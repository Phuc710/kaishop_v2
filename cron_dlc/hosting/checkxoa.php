<?php
require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';
$now = time();
?>
<!-- Khoá Host hết hạn -->
<?php
$check_host1 = $connection->query("SELECT * FROM `lich_su_mua_host` WHERE `status` ='tamkhoa' ");
while ($host1 = $check_host1->fetch_array()) {
        if(($now-$host1['ngay_het'])>259200){
            $id_host1 = $host1['id'];
            $connection->query("UPDATE `lich_su_mua_host` SET `status` = 'xoa' WHERE `id` = '$id_host1'");
        }
    }
?>
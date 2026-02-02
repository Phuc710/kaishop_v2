<?php
require __DIR__ . '/../../hethong/config.php';

$ten_mien = strip_tags($_POST['ten_mien']);
$duoi_mien = strip_tags($_POST['duoimien']);
$menhGiaList = [];

$stmt = $connection->prepare("SELECT * FROM `ds_domain` WHERE `duoimien` = ?");
$stmt->bind_param("s", $duoimien);
$stmt->execute();
$result = $stmt->get_result();
$mien = $result->fetch_assoc();

if ($mien) {
    $menhGiaList[] = array(
        "domain" => $ten_mien . $duoimien,
        "gia" => tien($mien['gia'])
    );
} else {
    $menhGiaList[] = array(
        "domain" => $ten_mien . $duoimien,
        "gia" => "Không tìm thấy giá"
    );
}

// Trả về JSON
echo json_encode($menhGiaList);
?>
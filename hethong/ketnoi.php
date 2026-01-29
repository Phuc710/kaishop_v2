<?php
session_start();
$chungapi_local = 'localhost';
$chungapi_ten = 'root';
$chungapi_matkhau = '';
$chungapi_dulieu = 'web';
$ketnoi = @mysqli_connect($chungapi_local, $chungapi_ten, $chungapi_matkhau, $chungapi_dulieu) or die("BAO TRI HE THONG");
@mysqli_set_charset($ketnoi, "utf8");
?>
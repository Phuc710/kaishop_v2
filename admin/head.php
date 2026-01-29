<?php require $_SERVER['DOCUMENT_ROOT'].'/hethong/config.php';?>
<?php
    if(empty($_SESSION['admin'])){
    sendTele($ip_address." Có Ý Định Truy Cập Trái Phép Vào Admin");
        header("Location: /");
        exit();
    }
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/fontawesome-free/css/all.min.css">
<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Tempusdominus Bootstrap 4 -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
<!-- iCheck -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
<!-- JQVMap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/jqvmap/jqvmap.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/dist/css/adminlte.min.css">
<!-- overlayScrollbars -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<!-- Daterange picker -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/daterangepicker/daterangepicker.css">
<!-- summernote -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/summernote/summernote-bs4.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/e7c05d7f02.js"></script>

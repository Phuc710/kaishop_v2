<?php require_once dirname(__DIR__) . '/hethong/config.php'; ?>
<?php
if (empty($_SESSION['admin'])) {
    $alertIp = $GLOBALS['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (function_exists('sendTele')) {
        sendTele($alertIp . ' Truy cap trai phep vao Admin');
    }
    header('Location: ' . url('/'));
    exit();
}
?>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="shortcut icon" href="<?= $chungapi['favicon'] ?? '' ?>" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/fontawesome-free/css/all.min.css"
    crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/icheck-bootstrap/icheck-bootstrap.min.css"
    crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/dist/css/adminlte.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/overlayScrollbars/css/OverlayScrollbars.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/daterangepicker/daterangepicker.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/summernote/summernote-bs4.min.css"
    crossorigin="anonymous">

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet"
    crossorigin="anonymous">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin-pages.css') ?>">
<script>
    window.KS_CSRF_TOKEN = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
</script>
<script src="<?= asset('assets/js/csrf-guard.js') ?>"></script>

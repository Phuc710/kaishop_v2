<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>403 - Truy Cập Bị Từ Chối | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Truy cập của bạn bị từ chối do không có quyền hoặc bị hạn chế bởi hệ thống <?= $chungapi['ten_web'] ?>.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">403</div>
        <h1 class="error-title">ACCESS DENIED</h1>
        <p class="error-message">Không có quyền truy cập. Vui lòng liên hệ quản trị viên</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>
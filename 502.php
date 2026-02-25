<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>502 - Máy Chủ Gặp Sự Cố | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Máy chủ đang gặp sự cố tạm thời (502 Bad Gateway). Vui lòng thử lại sau ít phút trên <?= $chungapi['ten_web'] ?>.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">502</div>
        <h1 class="error-title">BAD GATEWAY</h1>
        <p class="error-message">Máy chủ đang gặp sự cố tạm thời. Vui lòng thử lại sau.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>
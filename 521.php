<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>521 - Máy Chủ Không Phản Hồi | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Máy chủ hiện không phản hồi. Vui lòng quay lại hệ thống <?= $chungapi['ten_web'] ?> sau.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">521</div>
        <h1 class="error-title">SERVER DOWN</h1>
        <p class="error-message">Dịch vụ web đang tắt. Vui lòng chờ máy chủ khởi tạo.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>
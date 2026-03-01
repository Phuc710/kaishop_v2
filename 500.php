<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>500 - Lỗi Hệ Thống | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Hệ thống <?= $chungapi['ten_web'] ?> đang gặp lỗi nội bộ máy chủ. Vui lòng thử lại sau ít phút.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">500</div>
        <h1 class="error-title">INTERNAL SERVER ERROR</h1>
        <p class="error-message">Hệ thống đang gặp lỗi tạm thời. Vui lòng tải lại trang hoặc quay lại sau ít phút.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>

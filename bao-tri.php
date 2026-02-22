<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>Hệ Thống Đang Bảo Trì | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Hệ thống <?= $chungapi['ten_web'] ?> hiện đang được nâng cấp và bảo trì định kỳ. Vui lòng quay lại sau ít phút.">
    <meta name="robots" content="index, follow">
    <!-- Sử dụng HTTP Response Code 503 cho file php nếu có thể ở route, SEO bảo trì rất quan trọng -->
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <h1 class="error-title">MAINTENANCE</h1>
        <p class="error-message">Hệ thống đang bảo trì để phục vụ bạn tốt hơn. Vui lòng quay lại sau.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-rotate-right"></i> Tải lại trang
        </a>
    </div>
</body>

</html>
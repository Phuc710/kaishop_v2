<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php
    $headPath = (defined('HETHONG_PATH') ? HETHONG_PATH . '/head2.php' : __DIR__ . '/hethong/head2.php');
    require $headPath;
    ?>
    <title>404 - Không Tìm Thấy Trang | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop')) ?></title>
    <meta name="description" content="Trang bạn đang tìm kiếm không tồn tại hoặc đã thay đổi. Vui lòng quay lại trang chủ.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">404</div>
        <h1 class="error-title">NOT FOUND</h1>
        <p class="error-message">Trang bạn tìm không tồn tại.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>

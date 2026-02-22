<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>404 - Không Tìm Thấy Trang | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Trang bạn đang tìm kiếm không tồn tại, đã bị xóa hoặc đổi tên. Vui lòng quay lại trang chủ <?= $chungapi['ten_web'] ?>.">
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
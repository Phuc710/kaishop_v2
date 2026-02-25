<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>503 - Dịch Vụ Không Khả Dụng | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Máy chủ hiện không khả dụng do đang quá tải hoặc bảo trì. Vui lòng quay lại <?= $chungapi['ten_web'] ?> sau.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code">503</div>
        <h1 class="error-title">SERVICE UNAVAILABLE</h1>
        <p class="error-message">Máy chủ hiện không thể xử lý yêu cầu của bạn do quá tải hoặc bảo trì.</p>
        <a href="<?= url('') ?>" class="error-action">
            <i class="fa-solid fa-house"></i> Về trang chủ
        </a>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>Tài Khoản Bị Khóa | <?= $chungapi['ten_web'] ?></title>
    <meta name="description"
        content="Tài khoản của bạn trên hệ thống <?= $chungapi['ten_web'] ?> đã bị khóa do vi phạm chính sách hoặc phát hiện gian lận.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= url('chinh-sach') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
</head>

<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-code"><i class="fa-solid fa-user-lock"></i></div>
        <h1 class="error-title">BANNED</h1>
        <p class="error-message">Tài khoản của bạn đã bị khóa.</p>
        <?php if (!empty($_SESSION['banned_reason'])): ?>
            <div
                style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 12px; margin: 15px 0; border-radius: 4px; text-align: left;">
                <strong style="color: #ef4444; display: block; margin-bottom: 5px;">Lý do khóa:</strong>
                <span style="color: #4a5568; line-height: 1.5;"><?= htmlspecialchars($_SESSION['banned_reason']) ?></span>
            </div>
        <?php else: ?>
            <p class="error-message">Vui lòng liên hệ Admin nếu có nhầm lẫn.</p>
        <?php endif; ?>

        <?php session_destroy(); // Clear session after displaying the message ?>

        <a href="<?= url('chinh-sach') ?>" class="error-action mt-3">
            <i class="fa-solid fa-book-open"></i> Xem chính sách
        </a>
    </div>
</body>

</html>
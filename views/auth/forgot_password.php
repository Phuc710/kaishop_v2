<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Quên mật khẩu | ' . $siteName;
$seoDescription = 'Khôi phục mật khẩu tài khoản tại ' . $siteName . '.';
$seoKeywords = 'quên mật khẩu, khôi phục mật khẩu, ' . $siteName;
$seoRobots = 'noindex, nofollow';

$authHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocalAuthHost = $authHost === 'localhost'
    || strpos($authHost, 'localhost:') === 0
    || $authHost === '127.0.0.1'
    || strpos($authHost, '127.0.0.1:') === 0
    || $authHost === '[::1]'
    || strpos($authHost, '[::1]:') === 0;
$turnstileSiteKey = trim((string) EnvHelper::get('TURNSTILE_SITE_KEY', ''));
if ($isLocalAuthHost) {
    $turnstileSiteKey = '';
}

$GLOBALS['pageAssets'] = [
    'interactive_bundle' => false,
    'turnstile' => ($turnstileSiteKey !== ''),
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <base href="../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="<?= BASE_URL ?>/assets/js/auth-forms.js"></script>
</head>

<body class="auth-layout">
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="auth-page">
        <section class="py-5 bg-offWhite auth-page-section">
            <div class="container auth-page-container">
                <div class="rounded-3">
                    <div class="row auth-page-row">
                        <div class="col-lg-6 p-3 p-lg-5 m-auto auth-page-col">
                            <div class="login-userset">
                                <div class="login-card auth-card-white">
                                    <div class="login-heading">
                                        <h3>Quên mật khẩu?</h3>
                                        <p>Nhập tên đăng nhập hoặc email để nhận liên kết khôi phục</p>
                                    </div>

                                    <form id="forgotPasswordForm" onsubmit="forgotPassword(); return false;">
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="fa-regular fa-envelope"></i></span>
                                        <input type="text" id="username" class="form-control floating" placeholder=" " autocomplete="username email" required>
                                        <label class="focus-label">Tên đăng nhập / Email</label>
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="forgot-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <span id="btnText" class="indicator-label">Gửi yêu cầu khôi phục</span>
                                        <span id="btnLoading" class="indicator-progress" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>
                                    </form>

                                    <div class="acc-in">
                                        <p>Đã nhớ mật khẩu? <a href="<?= BASE_URL ?>/login">Đăng nhập ngay</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>


    <script>
        window.KaiAuthForgotConfig = {
            resetUrl: '<?= BASE_URL ?>/password-reset',
            verifyOtpUrl: '<?= BASE_URL ?>/password-reset/verify-otp',
            loginUrl: '<?= BASE_URL ?>/login',
            turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
            turnstileContainerId: 'forgot-turnstile'
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth-forgot-password.js"></script>


    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

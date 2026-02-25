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

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <section class="py-5 bg-offWhite">
            <div class="container">
                <div class="rounded-3">
                    <div class="row">
                        <div class="col-lg-6 p-3 p-lg-5 m-auto">
                            <div class="login-userset">
                                <div class="login-card auth-card-white">
                                    <div class="login-heading">
                                        <h3>Quên mật khẩu?</h3>
                                        <p>Nhập tên đăng nhập hoặc email để nhận liên kết khôi phục</p>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="feather-mail"></i></span>
                                        <input type="text" id="username" class="form-control floating">
                                        <label class="focus-label">Tên đăng nhập / Email</label>
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="forgot-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="button" onclick="forgotPassword()" class="btn btn-primary w-100">
                                        <span id="btnText" class="indicator-label">Gửi yêu cầu khôi phục</span>
                                        <span id="btnLoading" class="indicator-progress" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>

                                    <div id="forgot2faBox" class="auth-otp-box" style="display:none;">
                                        <p class="mb-2 text-muted" style="font-size:13px;">Tài khoản đang bật 2FA. Nhập
                                            OTP đã gửi về Gmail để nhận email khôi phục mật khẩu.</p>
                                        <div class="form-wrap form-focus mb-2">
                                            <span class="form-icon"><i
                                                    class="fa-solid fa-envelope-circle-check"></i></span>
                                            <input type="text" id="forgotOtpCode" class="form-control floating"
                                                inputmode="numeric" maxlength="6">
                                            <label class="focus-label">Mã OTP 6 số</label>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary w-100"
                                            id="forgotOtpVerifyBtn" onclick="verifyForgotOtp()">
                                            <span id="forgotOtpVerifyText">Xác minh OTP & gửi email reset</span>
                                            <span id="forgotOtpVerifyLoading" style="display:none;"><i
                                                    class="fa fa-spinner fa-spin"></i> Đang xử lý...</span>
                                        </button>
                                    </div>

                                    <div class="acc-in mt-3">
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

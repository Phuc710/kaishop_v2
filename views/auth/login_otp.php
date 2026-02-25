<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Xác minh OTP đăng nhập | ' . $siteName;
$seoDescription = 'Nhập mã OTP để hoàn tất đăng nhập tại ' . $siteName . '.';
$seoKeywords = 'otp đăng nhập, xác minh 2fa, ' . $siteName;
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

$challengeId = trim((string) ($challengeId ?? ''));

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
                <div class="row auth-page-row">
                    <div class="auth-page-col">
                        <div class="login-userset">
                            <div class="login-card auth-card-white">

                                <!-- Heading -->
                                <div class="login-heading">
                                    <h3 style="margin-bottom: 30px;">Xác minh OTP đăng nhập</h3>
                                    <p>Mã OTP 6 số đã được gửi đến email của bạn. <br>Vui lòng kiểm tra hộp thư đến (hoặc
                                        thư rác).</p>
                                </div>

                                <form id="loginOtpForm" onsubmit="verifyLoginOtpPage(); return false;">
                                    <input type="hidden" id="challenge_id"
                                        value="<?= htmlspecialchars($challengeId, ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                        <input type="text" id="loginOtpCode" class="form-control floating"
                                            inputmode="numeric" maxlength="6" minlength="6" pattern="[0-9]{6}" required
                                            autocomplete="one-time-code" placeholder=" ">
                                        <label class="focus-label">Mã OTP 6 số</label>
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="login-otp-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" id="verifyLoginOtpBtn" class="btn btn-primary w-100">
                                        <span id="verifyOtpText">Xác minh OTP</span>
                                        <span id="verifyOtpLoading" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xác minh...
                                        </span>
                                    </button>
                                </form>

                                <div class="auth-resend-row">
                                    <span class="auth-resend-row__label">Không nhận được mã?</span>
                                    <button type="button" class="auth-link-btn" id="resendLoginOtpBtn"
                                        onclick="resendLoginOtpPage()">
                                        <span id="resendOtpText">Gửi lại mã</span>
                                        <span id="resendOtpLoading" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang gửi...
                                        </span>
                                    </button>
                                    <span class="auth-resend-row__countdown" id="resendLoginOtpCountdown"></span>
                                </div>

                                <div class="acc-in">
                                    <p>Nhập sai tài khoản? <a href="<?= BASE_URL ?>/login">Đăng nhập lại</a></p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.KaiAuthLoginOtpConfig = {
            verifyOtpUrl: '<?= BASE_URL ?>/auth/2fa/verify-login',
            resendOtpUrl: '<?= BASE_URL ?>/auth/2fa/resend-login',
            homeUrl: '<?= BASE_URL ?>/',
            turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
            turnstileContainerId: 'login-otp-turnstile',
            resendCooldownSeconds: 60
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth-login-otp.js"></script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

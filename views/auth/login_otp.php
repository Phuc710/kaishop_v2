<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Xác minh OTP | ' . $siteName;
$seoDescription = 'Nhập OTP để hoàn tất đăng nhập tại ' . $siteName . '.';
$seoKeywords = 'xác minh otp, xác minh hai bước, ' . $siteName;
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

$flashMessage = trim((string) ($_GET['message'] ?? ''));
$challengeId = trim((string) ($challengeId ?? ''));
$otpEmail = trim((string) ($otpEmail ?? ''));
$otpEmailMasked = trim((string) ($otpEmailMasked ?? $otpEmail));
if ($otpEmailMasked === '') {
    $otpEmailMasked = 'địa chỉ email của bạn';
}
$otpExpiresMinutes = max(1, (int) ($otpExpiresMinutes ?? 5));
$otpExpiresSeconds = max(0, (int) ($otpExpiresSeconds ?? ($otpExpiresMinutes * 60)));

$GLOBALS['pageAssets'] = [
    'interactive_bundle' => false,
    'turnstile' => ($turnstileSiteKey !== ''),
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <base href="<?= rtrim(BASE_URL, '/') ?>/ " />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="<?= BASE_URL ?>/assets/js/auth-forms.js"></script>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="auth-page otp-mail-page">
        <section class="auth-page-section">
            <div class="container">
                <div class="rounded-3">
                    <div class="row auth-page-row">
                        <div class="col-12 col-lg-7 col-xl-6 p-3 p-lg-5 m-auto">
                            <div class="login-userset">
                                <div class="login-card auth-card-white otp-mail-card">
                                    <div class="otp-mail-icon-wrap" aria-hidden="true">
                                        <span class="otp-mail-icon">
                                            <i class="fa-regular fa-envelope"></i>
                                            <i class="fa-solid fa-check otp-mail-icon__check"></i>
                                        </span>
                                    </div>

                                    <div class="login-heading otp-mail-heading">
                                        <h3>Kiểm tra hộp thư của bạn</h3>
                                        <p>Chúng tôi đã gửi mã xác minh 6 số đến</p>
                                        <p class="otp-mail-address" id="otpEmailLabel">
                                            <?= htmlspecialchars($otpEmailMasked, ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>

                                    <input type="hidden" id="challenge_id"
                                        value="<?= htmlspecialchars($challengeId, ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="otp-mail-input-wrap">
                                        <input type="text" id="loginOtpCode" class="form-control otp-mail-input"
                                            inputmode="numeric" maxlength="6" minlength="6" pattern="[0-9]{6}"
                                            autocomplete="one-time-code" placeholder="0 0 0 0 0 0"
                                            aria-label="Nhập mã xác minh">
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="login-otp-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="button" id="verifyLoginOtpBtn"
                                        class="btn btn-primary w-100 otp-mail-verify-btn" onclick="verifyLoginOtpPage()"
                                        disabled>
                                        <span id="verifyOtpText">Xác minh Email</span>
                                        <span id="verifyOtpLoading" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xác minh...
                                        </span>
                                    </button>

                                    <p class="otp-mail-expire-note">
                                        Mã sẽ hết hạn trong <span
                                            id="otpExpireMinutes"><?= (int) $otpExpiresMinutes ?></span> phút.
                                    </p>

                                    <p class="otp-mail-resend-note">
                                        Không nhận được mã?
                                        <button type="button" id="resendLoginOtpBtn" class="auth-link-btn"
                                            onclick="resendLoginOtpPage()">Gửi lại</button>
                                        <span id="resendLoginOtpCountdown" class="otp-mail-resend-countdown"
                                            style="display:none;"></span>
                                    </p>

                                    <div class="acc-in mt-3">
                                        <p>Sai tài khoản? <a href="<?= BASE_URL ?>/login">Quay lại đăng nhập</a></p>
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
        window.KaiAuthLoginOtpConfig = {
            verifyOtpUrl: '<?= BASE_URL ?>/auth/2fa/verify-login',
            resendOtpUrl: '<?= BASE_URL ?>/auth/2fa/resend-login',
            homeUrl: '<?= BASE_URL ?>/',
            turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
            turnstileContainerId: 'login-otp-turnstile',
            resendCooldownSeconds: 30,
            initialExpiresSeconds: <?= (int) $otpExpiresSeconds ?>,
            initialExpiresMinutes: <?= (int) $otpExpiresMinutes ?>,
            maskedEmail: <?= json_encode($otpEmailMasked, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            flashMessage: <?= json_encode($flashMessage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth-login-otp.js"></script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
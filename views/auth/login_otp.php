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

$flashMessage = trim((string) ($_GET['message'] ?? ''));
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
                                    <h3>Xác minh OTP đăng nhập</h3>
                                    <p>Nhập mã OTP 6 số đã gửi về Gmail để hoàn tất đăng nhập</p>
                                </div>

                                <input type="hidden" id="challenge_id" value="<?= htmlspecialchars($challengeId, ENT_QUOTES, 'UTF-8') ?>">

                                <div class="form-wrap form-focus mb-2">
                                    <span class="form-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                    <input type="text" id="loginOtpCode" class="form-control floating"
                                           inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder=" ">
                                    <label class="focus-label">Mã OTP 6 số</label>
                                </div>

                                <?php if ($turnstileSiteKey !== ''): ?>
                                    <div class="auth-turnstile-wrap">
                                        <div id="login-otp-turnstile" class="cf-turnstile"
                                             data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                             data-theme="light"></div>
                                    </div>
                                <?php endif; ?>

                                <button type="button" id="verifyLoginOtpBtn" class="btn btn-primary w-100"
                                        onclick="verifyLoginOtpPage()">
                                    <span id="verifyOtpText">Xác minh OTP</span>
                                    <span id="verifyOtpLoading" style="display:none;">
                                        <i class="fa fa-spinner fa-spin"></i> Đang xác minh...
                                    </span>
                                </button>

                                <div class="acc-in mt-3">
                                    <p>Nhập sai tài khoản? <a href="<?= BASE_URL ?>/login">Quay lại đăng nhập</a></p>
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
        homeUrl: '<?= BASE_URL ?>/',
        turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
        turnstileContainerId: 'login-otp-turnstile',
        flashMessage: <?= json_encode($flashMessage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    };
</script>
<script src="<?= BASE_URL ?>/assets/js/auth-login-otp.js"></script>

<?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>
</html>


<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Đăng nhập | ' . $siteName;
$seoDescription = 'Đăng nhập tài khoản tại ' . $siteName . '.';
$seoKeywords = 'đăng nhập, tài khoản, ' . $siteName;
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

$firebaseConfig = [
    'apiKey' => (string) EnvHelper::get('FIREBASE_API_KEY', ''),
    'authDomain' => (string) EnvHelper::get('FIREBASE_AUTH_DOMAIN', ''),
    'projectId' => (string) EnvHelper::get('FIREBASE_PROJECT_ID', ''),
    'appId' => (string) EnvHelper::get('FIREBASE_APP_ID', ''),
];
$googleAuthEnabled = $firebaseConfig['apiKey'] !== '' && $firebaseConfig['authDomain'] !== '' && $firebaseConfig['projectId'] !== '' && $firebaseConfig['appId'] !== '';

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
    <script src="<?= BASE_URL ?>/assets/js/fingerprint.js"></script>
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
                                        <h3>Đăng nhập tài khoản</h3>
                                        <p>Nhập thông tin để truy cập tài khoản của bạn</p>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="feather-user"></i></span>
                                        <input type="text" id="username" class="form-control floating"
                                            autocomplete="username" placeholder=" ">
                                        <label class="focus-label">Tên đăng nhập</label>
                                    </div>

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon"><i class="toggle-password feather-eye-off"></i></span>
                                        <input type="password" id="password" class="pass-input form-control floating"
                                            autocomplete="current-password" placeholder=" ">
                                        <label class="focus-label">Mật khẩu</label>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="form-wrap">
                                            <label class="custom_check mb-0">Ghi nhớ đăng nhập
                                                <input type="checkbox" id="remember" name="remember">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                        <div class="form-wrap text-md-end">
                                            <a href="<?= BASE_URL ?>/password-reset" class="forgot-link">Quên mật
                                                khẩu?</a>
                                        </div>
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="login-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="button" onclick="login()" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Đăng nhập</span>
                                        <span id="button2" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>

                                    <div id="login2faBox" class="auth-otp-box" style="display:none;">
                                        <p class="mb-2 text-muted" style="font-size:13px;">Nhập mã OTP đã gửi về Gmail
                                            để hoàn tất đăng nhập.</p>
                                        <div class="form-wrap form-focus mb-2">
                                            <span class="form-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                            <input type="text" id="loginOtpCode" class="form-control floating"
                                                inputmode="numeric" maxlength="6">
                                            <label class="focus-label">Mã OTP 6 số</label>
                                        </div>
                                        <button type="button" id="verifyLoginOtpBtn"
                                            class="btn btn-outline-primary w-100" onclick="verifyLoginOtp()">
                                            <span id="verifyOtpText">Xác minh OTP</span>
                                            <span id="verifyOtpLoading" style="display:none;"><i
                                                    class="fa fa-spinner fa-spin"></i> Đang xác minh...</span>
                                        </button>
                                    </div>

                                    <?php if ($googleAuthEnabled): ?>
                                        <div class="auth-alt-divider"><span>Hoặc</span></div>
                                        <button type="button" id="googleLoginBtn" class="auth-google-btn"
                                            onclick="googleAuthLogin()">
                                            <span class="auth-google-btn__icon"><i class="fa-brands fa-google"></i></span>
                                            <span class="auth-google-btn__text">Đăng nhập với Google</span>
                                            <span class="auth-google-btn__spinner" style="display:none;"><i
                                                    class="fa fa-spinner fa-spin"></i></span>
                                        </button>
                                    <?php endif; ?>

                                    <div class="acc-in">
                                        <p>Chưa có tài khoản? <a href="<?= BASE_URL ?>/register">Tạo tài khoản</a></p>
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
        window.KaiAuthLoginConfig = {
            loginUrl: '<?= BASE_URL ?>/login',
            verifyOtpUrl: '<?= BASE_URL ?>/auth/2fa/verify-login',
            loginOtpPageUrl: '<?= BASE_URL ?>/login-otp',
            homeUrl: '<?= BASE_URL ?>/',
            turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
            turnstileContainerId: 'login-turnstile'
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth-login.js"></script>


    <?php if ($googleAuthEnabled): ?>
        <script type="module">
            import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js';
            import { getAuth, GoogleAuthProvider, signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-auth.js';

            const firebaseConfig = <?= json_encode($firebaseConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const app = initializeApp(firebaseConfig);
            const auth = getAuth(app);
            const provider = new GoogleAuthProvider();

            function setGoogleBtnLoading(isLoading) {
                const btn = document.getElementById('googleLoginBtn');
                if (!btn) return;
                btn.disabled = !!isLoading;
                const spinner = btn.querySelector('.auth-google-btn__spinner');
                const text = btn.querySelector('.auth-google-btn__text');
                if (spinner) spinner.style.display = isLoading ? 'inline-flex' : 'none';
                if (text) text.style.opacity = isLoading ? '0.75' : '1';
            }

            window.googleAuthLogin = async function () {
                const turnstileToken = getTurnstileToken('login-turnstile');
                <?php if ($turnstileSiteKey !== ''): ?>
                    if (!turnstileToken) {
                        SwalHelper.error('Vui lòng xác minh bạn là người thật.');
                        return;
                    }
                <?php endif; ?>

                try {
                    setGoogleBtnLoading(true);
                    const { fpHash, fpComponents } = await collectFingerprintData();
                    const result = await signInWithPopup(auth, provider);
                    const idToken = await result.user.getIdToken(true);

                    const params = new URLSearchParams();
                    params.set('id_token', idToken);
                    params.set('remember', getRememberMeValue());
                    if (turnstileToken) params.set('turnstile_token', turnstileToken);
                    if (fpHash) {
                        params.set('fingerprint', fpHash);
                        params.set('fp_components', fpComponents);
                    }

                    const { data } = await fetchFormJson('<?= BASE_URL ?>/auth/google', params);
                    if (data.success) {
                        if (data.requires_2fa) {
                            showLoginOtpStep(data.challenge_id, data.message || 'Đã gửi OTP đến email.');
                            return;
                        }
                        SwalHelper.successOkRedirect(data.message || 'Đăng nhập thành công.', data.redirect || '<?= BASE_URL ?>/');
                        return;
                    }
                    resetTurnstileWidget();
                    SwalHelper.error(data.message || 'Đăng nhập Google thất bại.');
                } catch (e) {
                    resetTurnstileWidget();
                    if (e && e.code !== 'auth/popup-closed-by-user') {
                        SwalHelper.error('Không thể đăng nhập Google. Vui lòng thử lại.');
                    }
                } finally {
                    setGoogleBtnLoading(false);
                }
            };
        </script>
    <?php endif; ?>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

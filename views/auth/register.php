<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$siteName = $siteConfig['ten_web'] ?? ($chungapi['ten_web'] ?? 'KaiShop');
$seoTitle = 'Đăng ký | ' . $siteName;
$seoDescription = 'Tạo tài khoản mới tại ' . $siteName . '.';
$seoKeywords = 'đăng ký, tạo tài khoản, ' . $siteName;
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
    'storageBucket' => (string) EnvHelper::get('FIREBASE_STORAGE_BUCKET', ''),
    'messagingSenderId' => (string) EnvHelper::get('FIREBASE_MESSAGING_SENDER_ID', ''),
    'appId' => (string) EnvHelper::get('FIREBASE_APP_ID', ''),
    'measurementId' => (string) EnvHelper::get('FIREBASE_MEASUREMENT_ID', ''),
];
$googleAuthEnabled = $firebaseConfig['apiKey'] !== '' && $firebaseConfig['authDomain'] !== ''
    && $firebaseConfig['projectId'] !== '' && $firebaseConfig['appId'] !== '';

$GLOBALS['pageAssets'] = [
    'interactive_bundle' => false,
    'turnstile' => ($turnstileSiteKey !== ''),
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <base href="<?= rtrim(BASE_URL, '/') ?>/" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="<?= BASE_URL ?>/assets/js/fingerprint.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/auth-forms.js"></script>
</head>

<body class="auth-layout">
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="auth-page">
        <section class="auth-page-section">
            <div class="container auth-page-container">
                <div class="row auth-page-row">
                    <div class="auth-page-col">
                        <div class="login-userset">
                            <div class="login-card auth-card-white">

                                <div class="login-heading">
                                    <h3>Tạo tài khoản mới</h3>
                                    <p>Nhập thông tin để tạo tài khoản của bạn</p>
                                </div>

                                <!-- Register form – native validation via required + reportValidity() -->
                                <form id="registerForm" onsubmit="registerAccount(); return false;">

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="fa-regular fa-user"></i></span>
                                        <input type="text" id="username" class="form-control floating" placeholder=" "
                                            autocomplete="username" required>
                                        <label class="focus-label">Tên đăng nhập</label>
                                    </div>

                                    <div class="form-wrap form-focus">
                                        <span class="form-icon"><i class="fa-regular fa-envelope"></i></span>
                                        <input type="email" id="email" class="form-control floating" placeholder=" "
                                            autocomplete="email" required>
                                        <label class="focus-label">Email</label>
                                    </div>

                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon"><i
                                                class="toggle-password fa-regular fa-eye-slash"></i></span>
                                        <input type="password" id="password" class="pass-input form-control floating"
                                            placeholder=" " autocomplete="new-password" required>
                                        <label class="focus-label">Mật khẩu</label>
                                    </div>

                                    <?php if ($turnstileSiteKey !== ''): ?>
                                        <div class="auth-turnstile-wrap">
                                            <div id="register-turnstile" class="cf-turnstile"
                                                data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>"
                                                data-theme="light"></div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Tạo tài khoản</span>
                                        <span id="button2" class="indicator-progress" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i> Đang xử lý...
                                        </span>
                                    </button>

                                </form>

                                <?php if ($googleAuthEnabled): ?>
                                    <div class="auth-alt-divider"><span>Hoặc</span></div>
                                    <button type="button" id="googleRegisterBtn" class="auth-google-btn"
                                        onclick="googleAuthRegister()">
                                        <span class="auth-google-btn__icon">
                                            <img src="<?= BASE_URL ?>/assets/images/google-icon.svg" alt="Google" width="18"
                                                height="18" style="display:block;">
                                        </span>
                                        <span class="auth-google-btn__text">Đăng ký với Google</span>
                                        <span class="auth-google-btn__spinner" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                    </button>
                                <?php endif; ?>

                                <div class="acc-in">
                                    <p>Đã có tài khoản? <a href="<?= BASE_URL ?>/login">Đăng nhập</a></p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        window.KaiAuthRegisterConfig = {
            registerUrl: '<?= BASE_URL ?>/register',
            homeUrl: '<?= BASE_URL ?>/',
            turnstileRequired: <?= $turnstileSiteKey !== '' ? 'true' : 'false' ?>,
            turnstileContainerId: 'register-turnstile'
        };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/auth-register.js"></script>

    <?php if ($googleAuthEnabled): ?>
        <script type="module">
            import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js';
            import { getAuth, GoogleAuthProvider, signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-auth.js';

            const firebaseConfig = <?= json_encode($firebaseConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const app = initializeApp(firebaseConfig);
            const auth = getAuth(app);
            const provider = new GoogleAuthProvider();
            auth.useDeviceLanguage();

            const isLocalHost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);

            function setGoogleBtnLoading(isLoading) {
                const btn = document.getElementById('googleRegisterBtn');
                if (!btn) return;
                btn.disabled = !!isLoading;
                const spinner = btn.querySelector('.auth-google-btn__spinner');
                const text = btn.querySelector('.auth-google-btn__text');
                if (spinner) spinner.style.display = isLoading ? 'inline-flex' : 'none';
                if (text) text.style.opacity = isLoading ? '0.75' : '1';
            }

            function showGoogleAuthError(e) {
                const code = e?.code || '';
                const msg = e?.message || '';
                let text = 'Đăng ký Google thất bại.';

                if (code === 'auth/unauthorized-domain') text = 'Domain chưa được phép trong Firebase. Thêm vào Authorized domains.';
                else if (code === 'auth/operation-not-allowed') text = 'Google Sign-in chưa được bật trong Firebase Authentication.';
                else if (code === 'auth/popup-blocked') text = 'Popup bị chặn — vui lòng cho phép popup và thử lại.';
                else if (msg.includes('securetoken') || msg.includes('CORS')) text = 'Lỗi CORS Firebase. Kiểm tra Authorized domains và API key.';

                SwalHelper.error(text);
            }

            async function submitFirebaseGoogleToken(idToken) {
                const { fpHash, fpComponents, deviceId } = await (
                    window.collectFingerprintData
                        ? window.collectFingerprintData()
                        : Promise.resolve({ fpHash: '', fpComponents: '', deviceId: '' })
                );

                const params = new URLSearchParams();
                params.set('id_token', idToken);
                if (fpHash) {
                    params.set('fingerprint', fpHash);
                    params.set('fp_components', fpComponents);
                }
                if (deviceId) params.set('device_id', deviceId);

                // Include Turnstile token if available
                const turnstileInput = document.querySelector('[name="cf-turnstile-response"]');
                if (turnstileInput && turnstileInput.value) {
                    params.set('turnstile_token', turnstileInput.value);
                }

                const res = await fetch('<?= BASE_URL ?>/auth/google', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.message || 'Đăng ký Google thất bại.');
                }

                window.location.href = data.redirect || '<?= BASE_URL ?>/';
            }

            // Button click -> open Google popup
            window.googleAuthRegister = async function () {
                setGoogleBtnLoading(true);
                try {
                    const result = await signInWithPopup(auth, provider);
                    const idToken = await result.user.getIdToken(true);
                    await submitFirebaseGoogleToken(idToken);
                } catch (e) {
                    if (e?.code !== 'auth/popup-closed-by-user') showGoogleAuthError(e);
                } finally {
                    setGoogleBtnLoading(false);
                }
            };
        </script>
    <?php endif; ?>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
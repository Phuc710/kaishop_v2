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
        const COOLDOWN_KEY = 'forgotpass_cooldown_until';
        const COOLDOWN_SEC = 15;
        let countdownTimer = null;
        let forgot2faChallengeId = '';

        // ── Khởi động cooldown khi tải trang (chống F5 bypass) ──────────────
        (function initCooldown() {
            const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
            const remaining = Math.ceil((until - Date.now()) / 1000);
            if (remaining > 0) startCountdown(remaining);
        })();

        function startCountdown(seconds) {
            const button = document.querySelector('button[onclick="forgotPassword()"]');
            const btnText = document.getElementById('btnText');
            clearInterval(countdownTimer);
            button.disabled = true;

            function tick() {
                btnText.textContent = `Gửi lại sau ${seconds}s`;
                if (seconds <= 0) {
                    clearInterval(countdownTimer);
                    button.disabled = false;
                    btnText.textContent = 'Gửi yêu cầu khôi phục';
                    localStorage.removeItem(COOLDOWN_KEY);
                }
                seconds--;
            }
            tick();
            if (seconds > 0) countdownTimer = setInterval(tick, 1000);
        }

        function getTurnstileToken(containerId) {
            return window.KaiAuthForms ? KaiAuthForms.getTurnstileToken(containerId) : '';
        }

        async function fetchFormJson(url, params) {
            if (window.KaiAuthForms) return KaiAuthForms.fetchFormJson(url, params);
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            });
            return { response, data: await response.json() };
        }

        function resetTurnstileWidget() {
            if (window.KaiAuthForms) return KaiAuthForms.resetTurnstile();
            try { if (window.turnstile) window.turnstile.reset(); } catch (e) { }
        }

        function showForgotOtpStep(challengeId, message) {
            forgot2faChallengeId = challengeId || '';
            const box = document.getElementById('forgot2faBox');
            if (box) box.style.display = 'block';
            const input = document.getElementById('forgotOtpCode');
            if (input) input.focus();
            if (message) SwalHelper.toast(message, 'info');
        }

        function forgotPassword() {
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const button = btnLoading.parentElement;
            const username = document.getElementById('username').value.trim();

            if (btnLoading.style.display !== 'none') return;
            if (!username) {
                SwalHelper.error('Vui lòng nhập tên đăng nhập hoặc email.');
                return;
            }

            const turnstileToken = getTurnstileToken('forgot-turnstile');
            <?php if ($turnstileSiteKey !== ''): ?>
                if (!turnstileToken) {
                    SwalHelper.error('Vui lòng xác minh bạn là người thật.');
                    return;
                }
            <?php endif; ?>

            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            button.disabled = true;

            const params = new URLSearchParams();
            params.set('username', username);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            fetchFormJson('<?= BASE_URL ?>/password-reset', params)
                .then(({ data }) => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    button.disabled = false;
                    if (data.success) {
                        if (data.requires_2fa) {
                            showForgotOtpStep(data.challenge_id, data.message || 'Đã gửi OTP đến email.');
                            return;
                        }
                        // ── Kích hoạt cooldown 15s chống spam ──
                        localStorage.setItem(COOLDOWN_KEY, Date.now() + COOLDOWN_SEC * 1000);
                        startCountdown(COOLDOWN_SEC);
                        SwalHelper.toast(data.message || 'Đã gửi email khôi phục mật khẩu! Vui lòng kiểm tra hộp thư.', 'success');
                    } else {
                        resetTurnstileWidget();
                        SwalHelper.error(data.message || 'Không thể xử lý yêu cầu.');
                    }
                })
                .catch(() => {
                    btnText.style.display = 'inline-block';
                    btnLoading.style.display = 'none';
                    button.disabled = false;
                    resetTurnstileWidget();
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }

        document.getElementById('username').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') forgotPassword();
        });

        function verifyForgotOtp() {
            const btn = document.getElementById('forgotOtpVerifyBtn');
            const text = document.getElementById('forgotOtpVerifyText');
            const loading = document.getElementById('forgotOtpVerifyLoading');
            const username = document.getElementById('username').value.trim();
            const otpCode = (document.getElementById('forgotOtpCode').value || '').trim();

            if (!forgot2faChallengeId) {
                SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng thử lại từ đầu.');
                return;
            }
            if (!username || !otpCode) {
                SwalHelper.error('Vui lòng nhập đầy đủ thông tin.');
                return;
            }

            const turnstileToken = getTurnstileToken('forgot-turnstile');
            <?php if ($turnstileSiteKey !== ''): ?>
                if (!turnstileToken) {
                    SwalHelper.error('Vui lòng xác minh bạn là người thật.');
                    return;
                }
            <?php endif; ?>

            text.style.display = 'none';
            loading.style.display = 'inline-block';
            btn.disabled = true;

            const params = new URLSearchParams();
            params.set('username', username);
            params.set('challenge_id', forgot2faChallengeId);
            params.set('otp_code', otpCode);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            fetchFormJson('<?= BASE_URL ?>/password-reset/verify-otp', params)
                .then(({ data }) => {
                    text.style.display = 'inline';
                    loading.style.display = 'none';
                    btn.disabled = false;
                    if (data.success) {
                        SwalHelper.successOkRedirect(data.message || 'Đã gửi email khôi phục mật khẩu.', '<?= BASE_URL ?>/login');
                    } else {
                        resetTurnstileWidget();
                        SwalHelper.error(data.message || 'Xác minh OTP thất bại.');
                    }
                })
                .catch(() => {
                    text.style.display = 'inline';
                    loading.style.display = 'none';
                    btn.disabled = false;
                    resetTurnstileWidget();
                    SwalHelper.error('Không thể kết nối đến máy chủ.');
                });
        }
    </script>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

(function (window) {
    'use strict';

    const cfg = window.KaiAuthLoginConfig || {};
    let login2faChallengeId = '';
    let isSubmitting = false;
    let lockUntilMs = 0;
    let lockCountdownTimer = null;
    let defaultButtonText = 'Đăng nhập';
    const LOCK_STORAGE_KEY = 'kai_login_lock_until';

    function getTurnstileToken(containerId) {
        if (window.KaiAuthForms) return window.KaiAuthForms.getTurnstileToken(containerId);
        return '';
    }

    async function fetchFormJson(url, params) {
        if (window.KaiAuthForms) return window.KaiAuthForms.fetchFormJson(url, params);
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        return { response, data: await response.json() };
    }

    function resetTurnstileWidget() {
        if (window.KaiAuthForms) return window.KaiAuthForms.resetTurnstile();
        try { if (window.turnstile) window.turnstile.reset(); } catch (e) { }
    }

    async function collectFingerprintData() {
        if (window.KaiAuthForms) return window.KaiAuthForms.collectFingerprintData();
        return { fpHash: '', fpComponents: '' };
    }

    function requireHuman(turnstileToken) {
        if (!cfg.turnstileRequired) return true;
        if (turnstileToken) return true;
        window.SwalHelper && SwalHelper.error('Vui lòng xác minh bạn là người thật.');
        return false;
    }

    function getRememberMeValue() {
        const remember = document.getElementById('remember');
        return remember && remember.checked ? '1' : '0';
    }

    function showLoginOtpStep(challengeId, message) {
        login2faChallengeId = challengeId || '';
        if (cfg.loginOtpPageUrl && login2faChallengeId) {
            const url = new URL(cfg.loginOtpPageUrl, window.location.origin);
            url.searchParams.set('challenge_id', login2faChallengeId);
            if (message) {
                url.searchParams.set('message', message);
            }
            window.location.href = url.toString();
            return;
        }

        const box = document.getElementById('login2faBox');
        if (box) box.style.display = 'block';
        const otpInput = document.getElementById('loginOtpCode');
        if (otpInput) otpInput.focus();
        if (message && window.SwalHelper) {
            SwalHelper.toast(message, 'info');
        }
    }

    function getLoginUi() {
        const form = document.getElementById('loginForm');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        const button1 = document.getElementById('button1');
        const button2 = document.getElementById('button2');
        return { submitBtn, button1, button2 };
    }

    function cacheDefaultButtonText() {
        const { button1 } = getLoginUi();
        const text = (button1 && button1.textContent ? button1.textContent : '').trim();
        if (text !== '') {
            defaultButtonText = text;
        }
    }

    function getDefaultButtonText() {
        return defaultButtonText || 'Đăng nhập';
    }

    function formatCountdown(seconds) {
        const safeSeconds = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(safeSeconds / 60);
        const secs = safeSeconds % 60;
        return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function getRemainingLockSeconds() {
        if (lockUntilMs <= 0) return 0;
        return Math.max(0, Math.ceil((lockUntilMs - Date.now()) / 1000));
    }

    function isLocked() {
        return getRemainingLockSeconds() > 0;
    }

    function persistLockUntil(ms) {
        try {
            if (ms > 0) {
                localStorage.setItem(LOCK_STORAGE_KEY, String(ms));
            } else {
                localStorage.removeItem(LOCK_STORAGE_KEY);
            }
        } catch (e) {
            // non-blocking
        }
    }

    function parseRetryAfterSeconds(data) {
        const fromRetry = Number(data && data.retry_after_seconds ? data.retry_after_seconds : 0);
        if (Number.isFinite(fromRetry) && fromRetry > 0) {
            return Math.ceil(fromRetry);
        }

        const lockoutUntil = Number(data && data.lockout_until ? data.lockout_until : 0);
        if (Number.isFinite(lockoutUntil) && lockoutUntil > 0) {
            return Math.max(0, Math.ceil(lockoutUntil - (Date.now() / 1000)));
        }

        return 0;
    }

    function setSubmitLoading(isLoading) {
        const { submitBtn, button1, button2 } = getLoginUi();
        if (!submitBtn || !button1 || !button2) return;
        if (isLocked() && !isLoading) return;

        submitBtn.disabled = !!isLoading;
        button1.style.display = isLoading ? 'none' : 'inline-block';
        button2.style.display = isLoading ? 'inline-block' : 'none';
    }

    function renderLockOnButton() {
        const { submitBtn, button1, button2 } = getLoginUi();
        if (!submitBtn || !button1 || !button2) return;

        const remaining = getRemainingLockSeconds();
        if (remaining <= 0) {
            clearLockCountdown();
            return;
        }

        submitBtn.disabled = true;
        button2.style.display = 'none';
        button1.style.display = 'inline-block';
        button1.textContent = 'Thử lại sau ' + formatCountdown(remaining);
    }

    function clearLockCountdown() {
        lockUntilMs = 0;
        persistLockUntil(0);

        if (lockCountdownTimer) {
            clearInterval(lockCountdownTimer);
            lockCountdownTimer = null;
        }

        if (isSubmitting) return;

        const { submitBtn, button1, button2 } = getLoginUi();
        if (submitBtn) submitBtn.disabled = false;
        if (button2) button2.style.display = 'none';
        if (button1) {
            button1.style.display = 'inline-block';
            button1.textContent = getDefaultButtonText();
        }
    }

    function startLockCountdown(seconds) {
        const totalSeconds = Math.max(0, Math.ceil(Number(seconds) || 0));
        if (totalSeconds <= 0) return;

        lockUntilMs = Date.now() + (totalSeconds * 1000);
        persistLockUntil(lockUntilMs);

        if (lockCountdownTimer) {
            clearInterval(lockCountdownTimer);
            lockCountdownTimer = null;
        }

        renderLockOnButton();
        lockCountdownTimer = setInterval(renderLockOnButton, 1000);
    }

    function restoreLockCountdown() {
        let stored = 0;
        try {
            stored = Number(localStorage.getItem(LOCK_STORAGE_KEY) || 0);
        } catch (e) {
            stored = 0;
        }

        if (!Number.isFinite(stored) || stored <= Date.now()) {
            clearLockCountdown();
            return;
        }

        lockUntilMs = stored;
        renderLockOnButton();
        lockCountdownTimer = setInterval(renderLockOnButton, 1000);
    }

    async function login() {
        const username = (document.getElementById('username')?.value || '').trim();
        const password = (document.getElementById('password')?.value || '').trim();

        if (isSubmitting) return;
        if (isLocked()) {
            SwalHelper.error('Bạn đã nhập sai quá số lần cho phép. Vui lòng đợi ' + formatCountdown(getRemainingLockSeconds()) + '.');
            return;
        }
        if (!username || !password) {
            SwalHelper.error('Vui lòng nhập đầy đủ thông tin.');
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-turnstile');
        if (!requireHuman(turnstileToken)) return;

        isSubmitting = true;
        setSubmitLoading(true);

        try {
            const { fpHash, fpComponents } = await collectFingerprintData();
            const params = new URLSearchParams();
            params.set('username', username);
            params.set('password', password);
            params.set('remember', getRememberMeValue());
            if (turnstileToken) params.set('turnstile_token', turnstileToken);
            if (fpHash) params.set('fingerprint', fpHash);
            if (fpComponents) params.set('fp_components', fpComponents);

            const { response, data } = await fetchFormJson(cfg.loginUrl, params);
            if (data.success) {
                if (data.requires_2fa) {
                    showLoginOtpStep(data.challenge_id, data.message || 'Đã gửi OTP đến email.');
                    return;
                }
                SwalHelper.successOkRedirect(data.message || 'Đăng nhập thành công.', cfg.homeUrl);
                return;
            }

            resetTurnstileWidget();
            if (response && response.status === 429) {
                const retryAfterSeconds = parseRetryAfterSeconds(data);
                if (retryAfterSeconds > 0) {
                    startLockCountdown(retryAfterSeconds);
                }
            }
            SwalHelper.error(data.message || 'Thông tin đăng nhập không chính xác.');
        } catch (err) {
            resetTurnstileWidget();
            console.error('[Login] Fetch error:', err);
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            isSubmitting = false;
            if (!isLocked()) {
                setSubmitLoading(false);
            }
        }
    }

    async function verifyLoginOtp() {
        const btn = document.getElementById('verifyLoginOtpBtn');
        const txt = document.getElementById('verifyOtpText');
        const loading = document.getElementById('verifyOtpLoading');
        const otpCode = (document.getElementById('loginOtpCode')?.value || '').trim();

        if (!login2faChallengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (!otpCode) {
            SwalHelper.error('Vui lòng nhập mã OTP.');
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-turnstile');
        if (!requireHuman(turnstileToken)) return;

        if (txt) txt.style.display = 'none';
        if (loading) loading.style.display = 'inline-block';
        if (btn) btn.disabled = true;

        try {
            const params = new URLSearchParams();
            params.set('challenge_id', login2faChallengeId);
            params.set('otp_code', otpCode);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            const { data } = await fetchFormJson(cfg.verifyOtpUrl, params);
            if (data.success) {
                SwalHelper.successOkRedirect(data.message || 'Đăng nhập thành công.', data.redirect || cfg.homeUrl);
                return;
            }
            resetTurnstileWidget();
            SwalHelper.error(data.message || 'Xác minh OTP thất bại.');
        } catch (err) {
            resetTurnstileWidget();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            if (txt) txt.style.display = 'inline';
            if (loading) loading.style.display = 'none';
            if (btn) btn.disabled = false;
        }
    }

    window.getTurnstileToken = getTurnstileToken;
    window.fetchFormJson = fetchFormJson;
    window.getRememberMeValue = getRememberMeValue;
    window.resetTurnstileWidget = resetTurnstileWidget;
    window.collectFingerprintData = collectFingerprintData;
    window.showLoginOtpStep = showLoginOtpStep;
    window.login = login;
    window.verifyLoginOtp = verifyLoginOtp;

    document.addEventListener('DOMContentLoaded', function () {
        cacheDefaultButtonText();
        restoreLockCountdown();

        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') login();
            });
        }
    });
})(window);

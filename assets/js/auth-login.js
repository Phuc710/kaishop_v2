(function (window) {
    'use strict';

    const cfg = window.KaiAuthLoginConfig || {};
    let login2faChallengeId = '';

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

        if (message) {
            try {
                sessionStorage.setItem('kai_auth_message', message);
            } catch (e) { }
        }

        if (cfg.loginOtpPageUrl && login2faChallengeId) {
            const url = new URL(cfg.loginOtpPageUrl, window.location.origin);
            url.searchParams.set('challenge_id', login2faChallengeId);
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

    async function login() {
        const form = document.getElementById('loginForm');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        const button1 = document.getElementById('button1');
        const button2 = document.getElementById('button2');
        const username = (document.getElementById('username')?.value || '').trim();
        const password = (document.getElementById('password')?.value || '').trim();

        if (!button1 || !button2) return;
        if (submitBtn && submitBtn.disabled) return;
        if (form && !form.reportValidity()) return;
        if (!username || !password) return;

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-turnstile');
        if (!requireHuman(turnstileToken)) return;

        button1.style.display = 'none';
        button2.style.display = 'inline-block';
        if (submitBtn) submitBtn.disabled = true;

        try {
            const { fpHash, fpComponents } = await collectFingerprintData();
            const params = new URLSearchParams();
            params.set('username', username);
            params.set('password', password);
            params.set('remember', getRememberMeValue());
            if (turnstileToken) params.set('turnstile_token', turnstileToken);
            if (fpHash) {
                params.set('fingerprint', fpHash);
                params.set('fp_components', fpComponents);
            }

            const { data } = await fetchFormJson(cfg.loginUrl, params);
            if (data.success) {
                if (data.requires_2fa) {
                    showLoginOtpStep(data.challenge_id, data.message || 'Đã gửi OTP đến email.');
                    return;
                }
                SwalHelper.successOkRedirect(data.message || 'Đăng nhập thành công.', cfg.homeUrl);
                return;
            }
            resetTurnstileWidget();
            SwalHelper.error(data.message || 'Thông tin đăng nhập không chính xác.');
        } catch (err) {
            resetTurnstileWidget();
            console.error('[Login] Fetch error:', err);
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            button1.style.display = 'inline-block';
            button2.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    async function verifyLoginOtp() {
        const form = document.getElementById('loginInlineOtpForm');
        const btn = document.getElementById('verifyLoginOtpBtn');
        const txt = document.getElementById('verifyOtpText');
        const loading = document.getElementById('verifyOtpLoading');
        const otpCode = (document.getElementById('loginOtpCode')?.value || '').trim();

        if (!login2faChallengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (form && !form.reportValidity()) return;
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
        // Native HTML5 validation handles empty fields. Keep only visual ready state.
        const loginSubmitBtn = document.querySelector('#loginForm button[type="submit"]');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        function updateLoginBtnState() {
            if (!loginSubmitBtn) return;
            const hasUser = (usernameInput?.value || '').trim().length > 0;
            const hasPass = (passwordInput?.value || '').trim().length > 0;
            loginSubmitBtn.classList.toggle('btn-ready', hasUser && hasPass);
        }

        if (usernameInput) {
            usernameInput.addEventListener('input', updateLoginBtnState);
            usernameInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    if ((passwordInput?.value || '').trim().length > 0) login();
                    else passwordInput?.focus();
                }
            });
        }
        if (passwordInput) {
            passwordInput.addEventListener('input', updateLoginBtnState);
            passwordInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') login();
            });
        }

        updateLoginBtnState();
    });
})(window);

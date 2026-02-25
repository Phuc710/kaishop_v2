(function (window) {
    'use strict';

    const cfg = window.KaiAuthLoginOtpConfig || {};

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

    function requireHuman(turnstileToken) {
        if (!cfg.turnstileRequired) return true;
        if (turnstileToken) return true;
        SwalHelper.error('Vui lòng xác minh bạn là người thật.');
        return false;
    }

    async function verifyLoginOtpPage() {
        const btn = document.getElementById('verifyLoginOtpBtn');
        const txt = document.getElementById('verifyOtpText');
        const loading = document.getElementById('verifyOtpLoading');
        const challengeId = (document.getElementById('challenge_id')?.value || '').trim();
        const otpCode = (document.getElementById('loginOtpCode')?.value || '').trim();

        if (!challengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (!otpCode) {
            SwalHelper.error('Vui lòng nhập mã OTP 6 số.');
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-otp-turnstile');
        if (!requireHuman(turnstileToken)) return;

        if (txt) txt.style.display = 'none';
        if (loading) loading.style.display = 'inline-block';
        if (btn) btn.disabled = true;

        try {
            const params = new URLSearchParams();
            params.set('challenge_id', challengeId);
            params.set('otp_code', otpCode);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            const { data } = await fetchFormJson(cfg.verifyOtpUrl, params);
            if (data.success) {
                SwalHelper.successOkRedirect(data.message || 'Xác minh OTP thành công.', data.redirect || cfg.homeUrl);
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

    window.verifyLoginOtpPage = verifyLoginOtpPage;
    window.verifyLoginOtp = verifyLoginOtpPage;

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('loginOtpCode');
        if (input) {
            input.focus();
            input.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') verifyLoginOtpPage();
            });
        }

        const message = cfg.flashMessage || '';
        if (message && window.SwalHelper) {
            SwalHelper.toast(message, 'info');
        }
    });
})(window);

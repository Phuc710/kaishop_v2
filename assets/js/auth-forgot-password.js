(function (window) {
    'use strict';

    const cfg = window.KaiAuthForgotConfig || {};
    const COOLDOWN_KEY = 'forgotpass_cooldown_until';
    const COOLDOWN_SEC = 15;
    let countdownTimer = null;
    let forgot2faChallengeId = '';

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

    function startCountdown(seconds) {
        const button = document.querySelector('#forgotPasswordForm button[type="submit"]');
        const form = document.getElementById('forgotPasswordForm');
        const btnText = document.getElementById('btnText');
        if (!button || !btnText) return;
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

    async function forgotPassword() {
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        const button = btnLoading ? btnLoading.parentElement : null;
        const username = (document.getElementById('username')?.value || '').trim();

        if (!btnText || !btnLoading || !button) return;
        if (btnLoading.style.display !== 'none') return;
        if (form && !form.reportValidity()) return;
        if (!username) {
            SwalHelper.error('Vui lòng nhập tên đăng nhập hoặc email.');
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'forgot-turnstile');
        if (!requireHuman(turnstileToken)) return;

        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-block';
        button.disabled = true;

        try {
            const params = new URLSearchParams();
            params.set('username', username);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            const { data } = await fetchFormJson(cfg.resetUrl, params);
            if (data.success) {
                localStorage.setItem(COOLDOWN_KEY, String(Date.now() + COOLDOWN_SEC * 1000));
                startCountdown(COOLDOWN_SEC);
                SwalHelper.toast(data.message || 'Đã gửi email khôi phục mật khẩu! Vui lòng kiểm tra hộp thư.', 'success');
                return;
            }
            resetTurnstileWidget();
            SwalHelper.error(data.message || 'Không thể xử lý yêu cầu.');
        } catch (err) {
            resetTurnstileWidget();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            btnText.style.display = 'inline-block';
            btnLoading.style.display = 'none';
            if (!countdownTimer) {
                button.disabled = false;
            }
        }
    }

    window.getTurnstileToken = getTurnstileToken;
    window.fetchFormJson = fetchFormJson;
    window.resetTurnstileWidget = resetTurnstileWidget;
    window.forgotPassword = forgotPassword;

    document.addEventListener('DOMContentLoaded', function () {
        const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
        const remaining = Math.ceil((until - Date.now()) / 1000);
        if (remaining > 0) {
            startCountdown(remaining);
        }
    });
})(window);

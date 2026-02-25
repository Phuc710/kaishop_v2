(function (window) {
    'use strict';

    const cfg = window.KaiAuthRegisterConfig || {};

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
        SwalHelper.error('Vui lòng xác minh bạn là người thật.');
        return false;
    }

    async function registerAccount() {
        const button1 = document.getElementById('button1');
        const button2 = document.getElementById('button2');
        const username = (document.getElementById('username')?.value || '').trim();
        const email = (document.getElementById('email')?.value || '').trim();
        const password = (document.getElementById('password')?.value || '').trim();

        if (!button1 || !button2 || button2.disabled) return;
        if (!username || !email || !password) {
            SwalHelper.error('Vui lòng nhập đầy đủ thông tin.');
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'register-turnstile');
        if (!requireHuman(turnstileToken)) return;

        button1.style.display = 'none';
        button2.style.display = 'inline-block';
        button2.disabled = true;

        try {
            const { fpHash, fpComponents } = await collectFingerprintData();
            const params = new URLSearchParams();
            params.set('username', username);
            params.set('email', email);
            params.set('password', password);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);
            if (fpHash) {
                params.set('fingerprint', fpHash);
                params.set('fp_components', fpComponents);
            }

            const { data } = await fetchFormJson(cfg.registerUrl, params);
            if (data.success) {
                SwalHelper.successOkRedirect(data.message || 'Đăng ký thành công.', cfg.homeUrl);
                return;
            }
            resetTurnstileWidget();
            SwalHelper.error(data.message || 'Đăng ký thất bại.');
        } catch (err) {
            resetTurnstileWidget();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            button1.style.display = 'inline-block';
            button2.style.display = 'none';
            button2.disabled = false;
        }
    }

    window.getTurnstileToken = getTurnstileToken;
    window.fetchFormJson = fetchFormJson;
    window.resetTurnstileWidget = resetTurnstileWidget;
    window.collectFingerprintData = collectFingerprintData;
    window.registerAccount = registerAccount;

    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') registerAccount();
            });
        }
    });
})(window);

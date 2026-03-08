(function (window) {
    'use strict';

    const cfg = window.KaiAuthLoginOtpConfig || {};
    const RESEND_COOLDOWN_KEY = 'kai_login_otp_resend_until';
    let resendTimer = null;

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

    function getChallengeId() {
        return (document.getElementById('challenge_id')?.value || '').trim();
    }

    function setChallengeId(challengeId) {
        const input = document.getElementById('challenge_id');
        if (input) input.value = (challengeId || '').trim();
    }

    function getOtpInput() {
        return document.getElementById('loginOtpCode');
    }

    function normalizeOtpInput() {
        const input = getOtpInput();
        if (!input) return '';
        const normalized = (input.value || '').replace(/\D+/g, '').slice(0, 6);
        if (input.value !== normalized) {
            input.value = normalized;
        }
        return normalized;
    }

    function updateVerifyButtonState() {
        const btn = document.getElementById('verifyLoginOtpBtn');
        if (!btn) return;
        const otpCode = normalizeOtpInput();
        btn.disabled = otpCode.length !== 6;
    }

    function setVerifyLoading(isLoading) {
        const btn = document.getElementById('verifyLoginOtpBtn');
        const text = document.getElementById('verifyOtpText');
        const loading = document.getElementById('verifyOtpLoading');
        if (text) text.style.display = isLoading ? 'none' : 'inline';
        if (loading) loading.style.display = isLoading ? 'inline-block' : 'none';
        if (btn) {
            if (isLoading) {
                btn.disabled = true;
            } else {
                updateVerifyButtonState();
            }
        }
    }

    function updateExpiryLabel(minutes) {
        const el = document.getElementById('otpExpireMinutes');
        if (!el) return;
        const safeMinutes = Math.max(1, Number(minutes) || 10);
        el.textContent = String(Math.ceil(safeMinutes));
    }

    function updateEmailLabel(maskedEmail) {
        const el = document.getElementById('otpEmailLabel');
        if (!el) return;
        const safeEmail = String(maskedEmail || '').trim();
        if (safeEmail) {
            el.textContent = safeEmail;
        }
    }

    function formatCooldown(seconds) {
        const value = Math.max(0, Number(seconds) || 0);
        return value + 's';
    }

    function getCooldownRemaining() {
        let until = 0;
        try {
            until = Number(localStorage.getItem(RESEND_COOLDOWN_KEY) || 0);
        } catch (e) {
            until = 0;
        }
        if (!Number.isFinite(until) || until <= 0) return 0;
        return Math.max(0, Math.ceil((until - Date.now()) / 1000));
    }

    function setCooldownUntil(untilMs) {
        try {
            if (untilMs > 0) {
                localStorage.setItem(RESEND_COOLDOWN_KEY, String(untilMs));
            } else {
                localStorage.removeItem(RESEND_COOLDOWN_KEY);
            }
        } catch (e) {
            // non-blocking
        }
    }

    function clearResendTimer() {
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
    }

    function renderResendState() {
        const button = document.getElementById('resendLoginOtpBtn');
        const countdown = document.getElementById('resendLoginOtpCountdown');
        if (!button || !countdown) return;

        const remaining = getCooldownRemaining();
        if (remaining <= 0) {
            button.disabled = false;
            countdown.style.display = 'none';
            countdown.textContent = '';
            clearResendTimer();
            setCooldownUntil(0);
            return;
        }

        button.disabled = true;
        countdown.style.display = 'inline';
        countdown.textContent = '(' + formatCooldown(remaining) + ')';
    }

    function startResendCooldown(seconds) {
        const safeSeconds = Math.max(1, Math.ceil(Number(seconds) || Number(cfg.resendCooldownSeconds) || 30));
        setCooldownUntil(Date.now() + safeSeconds * 1000);
        renderResendState();
        clearResendTimer();
        resendTimer = setInterval(renderResendState, 1000);
    }

    function restoreResendCooldown() {
        const remaining = getCooldownRemaining();
        if (remaining <= 0) {
            renderResendState();
            return;
        }
        renderResendState();
        clearResendTimer();
        resendTimer = setInterval(renderResendState, 1000);
    }

    async function verifyLoginOtpPage() {
        const challengeId = getChallengeId();
        const otpCode = normalizeOtpInput();

        if (!challengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (!/^\d{6}$/.test(otpCode)) {
            SwalHelper.error('Vui lòng nhập mã OTP 6 số.');
            updateVerifyButtonState();
            return;
        }

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-otp-turnstile');
        if (!requireHuman(turnstileToken)) return;

        setVerifyLoading(true);

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
            setVerifyLoading(false);
        }
    }

    async function resendLoginOtpPage() {
        const challengeId = getChallengeId();
        if (!challengeId) {
            SwalHelper.error('Thiếu phiên OTP. Vui lòng đăng nhập lại.');
            return;
        }

        const remaining = getCooldownRemaining();
        if (remaining > 0) {
            renderResendState();
            return;
        }

        const button = document.getElementById('resendLoginOtpBtn');
        if (button) button.disabled = true;

        try {
            const params = new URLSearchParams();
            params.set('challenge_id', challengeId);

            const { response, data } = await fetchFormJson(cfg.resendOtpUrl, params);
            if (data && data.success) {
                if (data.challenge_id) {
                    setChallengeId(data.challenge_id);
                }

                const input = getOtpInput();
                if (input) {
                    input.value = '';
                    input.focus();
                }
                updateVerifyButtonState();

                if (typeof data.expires_minutes !== 'undefined') {
                    updateExpiryLabel(data.expires_minutes);
                } else if (typeof data.expires_in !== 'undefined') {
                    updateExpiryLabel(Math.ceil(Number(data.expires_in || 300) / 60));
                }

                if (data.email_masked) {
                    updateEmailLabel(data.email_masked);
                }

                const cooldownSeconds = Number(data.cooldown_seconds || cfg.resendCooldownSeconds || 30);
                startResendCooldown(cooldownSeconds);

                if (window.SwalHelper) {
                    SwalHelper.toast(data.message || 'Đã gửi lại mã OTP mới.', 'success');
                }
                return;
            }

            const cooldownFromServer = Number(data && data.cooldown_seconds ? data.cooldown_seconds : 0);
            if (response && response.status === 429 && cooldownFromServer > 0) {
                startResendCooldown(cooldownFromServer);
            } else {
                renderResendState();
            }

            SwalHelper.error((data && data.message) ? data.message : 'Không thể gửi lại OTP.');
        } catch (err) {
            renderResendState();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        }
    }

    window.verifyLoginOtpPage = verifyLoginOtpPage;
    window.verifyLoginOtp = verifyLoginOtpPage;
    window.resendLoginOtpPage = resendLoginOtpPage;

    document.addEventListener('DOMContentLoaded', function () {
        const input = getOtpInput();
        if (input) {
            input.focus();
            input.addEventListener('input', updateVerifyButtonState);
            input.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') verifyLoginOtpPage();
            });
        }

        updateVerifyButtonState();
        updateExpiryLabel(cfg.initialExpiresMinutes || 5);
        updateEmailLabel(cfg.maskedEmail || '');
        restoreResendCooldown();

        const message = cfg.flashMessage || '';
        if (message && window.SwalHelper) {
            SwalHelper.toast(message, 'info');
        }
    });
})(window);

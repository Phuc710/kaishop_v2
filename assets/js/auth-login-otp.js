(function (window) {
    'use strict';

    const cfg = window.KaiAuthLoginOtpConfig || {};
    const RESEND_COOLDOWN_KEY = 'kai_login_otp_resend_cooldown_until';
    let resendCountdownTimer = null;

    /* ─── Helpers ─── */

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

    /* ─── Resend UI ─── */

    function getResendUi() {
        return {
            btn: document.getElementById('resendLoginOtpBtn'),
            countdown: document.getElementById('resendLoginOtpCountdown'),
            loadingIcon: document.getElementById('resendOtpLoading')
        };
    }

    function getResendCooldownUntil() {
        const n = parseInt(localStorage.getItem(RESEND_COOLDOWN_KEY) || '0', 10);
        return Number.isFinite(n) ? n : 0;
    }

    function syncResendCooldownUi() {
        const { btn, countdown } = getResendUi();
        if (!btn) return;

        clearInterval(resendCountdownTimer);
        resendCountdownTimer = null;

        const tick = function () {
            const until = getResendCooldownUntil();
            const remaining = Math.max(0, Math.ceil((until - Date.now()) / 1000));
            if (remaining > 0) {
                btn.disabled = true;
                if (countdown) countdown.textContent = '(' + remaining + 's)';
                return;
            }
            btn.disabled = false;
            if (countdown) countdown.textContent = '';
            localStorage.removeItem(RESEND_COOLDOWN_KEY);
            if (resendCountdownTimer) {
                clearInterval(resendCountdownTimer);
                resendCountdownTimer = null;
            }
        };

        tick();
        if (getResendCooldownUntil() > Date.now()) {
            resendCountdownTimer = setInterval(tick, 1000);
        }
    }

    function setResendCooldown(seconds) {
        const ttl = Math.max(0, Number(seconds || 0));
        if (ttl <= 0) {
            localStorage.removeItem(RESEND_COOLDOWN_KEY);
            syncResendCooldownUi();
            return;
        }
        localStorage.setItem(RESEND_COOLDOWN_KEY, String(Date.now() + ttl * 1000));
        syncResendCooldownUi();
    }


    function setupOtpInputBehavior(input) {
        if (!input) return;

        input.focus();

        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            updateVerifyBtnState();

            if (this.value.length === 6) {
                setTimeout(() => {
                    const currentVal = (document.getElementById('loginOtpCode')?.value || '').trim();
                    if (currentVal.length === 6) {
                        verifyLoginOtpPage();
                    }
                }, 300);
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyLoginOtpPage();
            }
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            this.value = pasted;
            updateVerifyBtnState();
            if (pasted.length === 6) {
                setTimeout(() => verifyLoginOtpPage(), 300);
            }
        });
    }

    function updateVerifyBtnState() {
        const input = document.getElementById('loginOtpCode');
        const btn = document.getElementById('verifyLoginOtpBtn');
        if (!input || !btn) return;
        const len = (input.value || '').trim().length;
        btn.classList.toggle('btn-ready', len === 6);
    }

    /* ─── Verify OTP ─── */

    async function verifyLoginOtpPage() {
        const form = document.getElementById('loginOtpForm');
        const btn = document.getElementById('verifyLoginOtpBtn');
        const txt = document.getElementById('verifyOtpText');
        const loading = document.getElementById('verifyOtpLoading');
        const challengeId = (document.getElementById('challenge_id')?.value || '').trim();
        const otpCode = (document.getElementById('loginOtpCode')?.value || '').trim();

        if (!challengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (form && !form.reportValidity()) return;
        if (!otpCode || otpCode.length < 6) {
            SwalHelper.error('Vui lòng nhập đủ mã OTP 6 số.');
            return;
        }
        if (btn && btn.dataset.loading === '1') return;

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-otp-turnstile');
        if (!requireHuman(turnstileToken)) return;

        if (txt) txt.style.display = 'none';
        if (loading) loading.style.display = 'inline-flex';
        if (btn) {
            btn.disabled = true;
            btn.dataset.loading = '1';
        }

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
            // Shake animation on error
            const inputEl = document.getElementById('loginOtpCode');
            if (inputEl) {
                inputEl.classList.add('input-shake');
                inputEl.select();
                setTimeout(() => inputEl.classList.remove('input-shake'), 600);
            }
            SwalHelper.error(data.message || 'Xác minh OTP thất bại.');
        } catch (err) {
            resetTurnstileWidget();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            if (txt) txt.style.display = 'inline';
            if (loading) loading.style.display = 'none';
            if (btn) {
                btn.disabled = false;
                delete btn.dataset.loading;
                updateVerifyBtnState();
            }
        }
    }

    /* ─── Resend OTP ─── */

    async function resendLoginOtpPage() {
        const challengeInput = document.getElementById('challenge_id');
        const challengeId = (challengeInput?.value || '').trim();
        const { btn, countdown, loadingIcon } = getResendUi();
        const resendText = document.getElementById('resendOtpText');

        if (!challengeId) {
            SwalHelper.error('Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.');
            return;
        }
        if (btn && btn.disabled) return;

        const turnstileToken = getTurnstileToken(cfg.turnstileContainerId || 'login-otp-turnstile');
        if (!requireHuman(turnstileToken)) return;

        // Show loading state on resend button
        if (btn) btn.disabled = true;
        if (resendText) resendText.style.display = 'none';
        if (loadingIcon) loadingIcon.style.display = 'inline-flex';
        if (countdown) countdown.textContent = '';

        try {
            const params = new URLSearchParams();
            params.set('challenge_id', challengeId);
            if (turnstileToken) params.set('turnstile_token', turnstileToken);

            const { data } = await fetchFormJson(cfg.resendOtpUrl, params);
            if (data && data.success) {
                const newChallengeId = String(data.challenge_id || '').trim();
                if (newChallengeId && challengeInput) {
                    challengeInput.value = newChallengeId;
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('challenge_id', newChallengeId);
                        url.searchParams.delete('message');
                        window.history.replaceState({}, '', url.toString());
                    } catch (e) { }
                }
                setResendCooldown(Number(data.cooldown_seconds || cfg.resendCooldownSeconds || 60));
                resetTurnstileWidget();

                // Clear OTP input for new code
                const otpInput = document.getElementById('loginOtpCode');
                if (otpInput) {
                    otpInput.value = '';
                    otpInput.focus();
                    updateVerifyBtnState();
                }

                SwalHelper.toast(data.message || 'Đã gửi lại mã OTP đến email của bạn.', 'success');
                return;
            }

            const retryAfter = Number((data && data.retry_after_seconds) || 0);
            if (retryAfter > 0) {
                setResendCooldown(retryAfter);
            } else {
                syncResendCooldownUi();
            }
            resetTurnstileWidget();
            SwalHelper.error((data && data.message) || 'Không thể gửi lại OTP.');
        } catch (err) {
            syncResendCooldownUi();
            resetTurnstileWidget();
            SwalHelper.error('Không thể kết nối đến máy chủ.');
        } finally {
            if (resendText) resendText.style.display = 'inline';
            if (loadingIcon) loadingIcon.style.display = 'none';
        }
    }

    /* ─── Exports ─── */

    window.verifyLoginOtpPage = verifyLoginOtpPage;
    window.verifyLoginOtp = verifyLoginOtpPage;
    window.resendLoginOtpPage = resendLoginOtpPage;
    window.resendLoginOtp = resendLoginOtpPage;

    /* ─── Init ─── */

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('loginOtpCode');
        setupOtpInputBehavior(input);
        updateVerifyBtnState();

        // Flash message from sessionStorage
        let message = cfg.flashMessage || '';
        try {
            const ssMessage = sessionStorage.getItem('kai_auth_message');
            if (ssMessage) {
                message = ssMessage;
                sessionStorage.removeItem('kai_auth_message');
            }
        } catch (e) { }

        if (message && window.SwalHelper) {
            SwalHelper.toast(message, 'info');
        }

        // Only resume cooldown if user previously pressed "Resend OTP".
        // Do not auto-start countdown when first landing on /login-otp.
        if (getResendCooldownUntil() > Date.now()) {
            syncResendCooldownUi();
        }
    });
})(window);

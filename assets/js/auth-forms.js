(function (window) {
    'use strict';

    if (window.KaiAuthForms) {
        return;
    }

    const DEFAULT_TIMEOUT_MS = 15000;
    const DEVICE_ID_KEY = 'kai_device_id_v1';
    let fingerprintWarmupPromise = null;

    function getTurnstileToken(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return '';
        const input = container.querySelector('input[name="cf-turnstile-response"]');
        return input ? String(input.value || '').trim() : '';
    }

    function resetTurnstile() {
        try {
            if (window.turnstile && typeof window.turnstile.reset === 'function') {
                window.turnstile.reset();
            }
        } catch (e) { }
    }

    function generateUuidV4() {
        try {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }
        } catch (e) { }
        const bytes = new Uint8Array(16);
        try {
            (window.crypto || window.msCrypto).getRandomValues(bytes);
        } catch (e) {
            for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
        }
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
        return [
            hex.slice(0, 8),
            hex.slice(8, 12),
            hex.slice(12, 16),
            hex.slice(16, 20),
            hex.slice(20)
        ].join('-');
    }

    function getDeviceId() {
        try {
            let id = String(localStorage.getItem(DEVICE_ID_KEY) || '').trim();
            if (!id) {
                id = generateUuidV4();
                localStorage.setItem(DEVICE_ID_KEY, id);
            }
            return id;
        } catch (e) {
            if (!window.__kaiDeviceIdFallback) {
                window.__kaiDeviceIdFallback = generateUuidV4();
            }
            return String(window.__kaiDeviceIdFallback);
        }
    }

    function safeParseJson(text) {
        if (!text) return {};
        const normalized = String(text).replace(/^\uFEFF/, '').trim();
        return normalized ? JSON.parse(normalized) : {};
    }

    async function fetchFormJson(url, params, options) {
        const opts = options || {};
        if (params instanceof URLSearchParams && !params.has('device_id')) {
            const deviceId = getDeviceId();
            if (deviceId) {
                params.set('device_id', deviceId);
            }
        }
        const timeoutMs = Number(opts.timeoutMs || DEFAULT_TIMEOUT_MS);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
        let response;

        try {
            response = await fetch(url, {
                method: opts.method || 'POST',
                headers: Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded' }, opts.headers || {}),
                body: params instanceof URLSearchParams ? params.toString() : params,
                credentials: 'same-origin',
                cache: 'no-store',
                signal: controller.signal
            });
        } finally {
            clearTimeout(timeoutId);
        }

        const raw = await response.text();
        let data;
        try {
            data = safeParseJson(raw);
        } catch (err) {
            console.error('[KaiAuthForms] Invalid JSON response', { url, status: response.status, raw });
            throw err;
        }

        return { response, data, raw };
    }

    function warmFingerprint() {
        if (fingerprintWarmupPromise) return fingerprintWarmupPromise;
        if (!window.KaiFingerprint || typeof window.KaiFingerprint.collect !== 'function') {
            fingerprintWarmupPromise = Promise.resolve(null);
            return fingerprintWarmupPromise;
        }
        fingerprintWarmupPromise = window.KaiFingerprint.collect().catch(() => null);
        return fingerprintWarmupPromise;
    }

    async function collectFingerprintData() {
        let fpHash = '';
        let fpComponents = '';
        const deviceId = getDeviceId();
        try {
            const fp = await warmFingerprint();
            if (!fp && window.KaiFingerprint && typeof window.KaiFingerprint.collect === 'function') {
                const liveFp = await window.KaiFingerprint.collect();
                fingerprintWarmupPromise = Promise.resolve(liveFp);
                fpHash = liveFp.hash || '';
                fpComponents = JSON.stringify(liveFp.components || {});
                return { fpHash, fpComponents, deviceId };
            }
            if (!fp) return { fpHash, fpComponents, deviceId };
            fpHash = fp.hash || '';
            fpComponents = JSON.stringify(fp.components || {});
        } catch (e) { }
        return { fpHash, fpComponents, deviceId };
    }

    document.addEventListener('DOMContentLoaded', function () {
        warmFingerprint();
    });

    // Auth pages disable the heavy interactive bundle, so password toggle handlers
    // must live here instead of assets/js/script.js.
    document.addEventListener('click', function (event) {
        const toggle = event.target && event.target.closest
            ? event.target.closest('.toggle-password, .toggle-password-confirm')
            : null;
        if (!toggle) return;

        const isConfirm = toggle.classList.contains('toggle-password-confirm');
        let input = null;
        const wrap = toggle.closest('.form-wrap');
        if (wrap) {
            input = wrap.querySelector(isConfirm ? '.pass-confirm' : '.pass-input');
        }
        if (!input) {
            input = document.querySelector(isConfirm ? '.pass-confirm' : '.pass-input');
        }
        if (!input) return;

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';

        // Support both icon sets in case some pages still use feather classes.
        toggle.classList.toggle('fa-eye', !showing);
        toggle.classList.toggle('fa-eye-slash', showing);
        toggle.classList.toggle('feather-eye', !showing);
        toggle.classList.toggle('feather-eye-off', showing);
    });

    window.KaiAuthForms = {
        DEFAULT_TIMEOUT_MS,
        getTurnstileToken,
        resetTurnstile,
        safeParseJson,
        getDeviceId,
        fetchFormJson,
        warmFingerprint,
        collectFingerprintData
    };
})(window);

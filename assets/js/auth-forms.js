(function (window) {
    'use strict';

    if (window.KaiAuthForms) {
        return;
    }

    const DEFAULT_TIMEOUT_MS = 15000;
    const PUBLIC_IP_TIMEOUT_MS = 1800;
    const PUBLIC_IP_CACHE_KEY = 'ks_public_ip_cache';
    let fingerprintWarmupPromise = null;
    let publicIpPromise = null;

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

    function safeParseJson(text) {
        if (!text) return {};
        const normalized = String(text).replace(/^\uFEFF/, '').trim();
        return normalized ? JSON.parse(normalized) : {};
    }

    async function fetchFormJson(url, params, options) {
        const opts = options || {};
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
            const preview = String(raw || '').replace(/\s+/g, ' ').trim().slice(0, 240);
            console.error('[KaiAuthForms] Invalid JSON response', {
                url,
                status: response.status,
                contentType: response.headers.get('content-type') || '',
                preview
            });
            const wrapped = new Error('Invalid JSON response from server');
            wrapped.cause = err;
            wrapped.response = response;
            wrapped.raw = raw;
            throw wrapped;
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

    function readCachedPublicIp() {
        try {
            const raw = localStorage.getItem(PUBLIC_IP_CACHE_KEY);
            if (!raw) return '';
            const parsed = JSON.parse(raw);
            if (!parsed || parsed.expiry <= Date.now() || typeof parsed.ip !== 'string') {
                localStorage.removeItem(PUBLIC_IP_CACHE_KEY);
                return '';
            }
            return parsed.ip.trim();
        } catch (e) {
            return '';
        }
    }

    function cachePublicIp(ip) {
        try {
            localStorage.setItem(PUBLIC_IP_CACHE_KEY, JSON.stringify({
                ip,
                expiry: Date.now() + (30 * 60 * 1000)
            }));
        } catch (e) {
            // non-blocking
        }
    }

    async function fetchPublicIp() {
        const cached = readCachedPublicIp();
        if (cached) return cached;
        if (publicIpPromise) return publicIpPromise;

        publicIpPromise = (async function () {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), PUBLIC_IP_TIMEOUT_MS);

            try {
                const response = await fetch('https://api.ipify.org?format=json', {
                    method: 'GET',
                    cache: 'no-store',
                    signal: controller.signal
                });
                if (!response.ok) return '';

                const data = await response.json();
                const ip = typeof data.ip === 'string' ? data.ip.trim() : '';
                if (!ip) return '';

                cachePublicIp(ip);
                return ip;
            } catch (e) {
                return '';
            } finally {
                clearTimeout(timeoutId);
                publicIpPromise = null;
            }
        })();

        return publicIpPromise;
    }

    async function collectFingerprintData() {
        let fpHash = '';
        let fpComponents = '';
        try {
            const publicIp = await fetchPublicIp();
            const fp = await warmFingerprint();
            if (!fp && window.KaiFingerprint && typeof window.KaiFingerprint.collect === 'function') {
                const liveFp = await window.KaiFingerprint.collect();
                fingerprintWarmupPromise = Promise.resolve(liveFp);
                fpHash = liveFp.hash || '';
                const components = Object.assign({}, liveFp.components || {});
                if (publicIp) {
                    components.network = Object.assign({}, components.network || {}, {
                        publicIp,
                        source: 'ipify'
                    });
                }
                fpComponents = JSON.stringify(components);
                return { fpHash, fpComponents };
            }
            if (!fp) {
                if (publicIp) {
                    fpComponents = JSON.stringify({
                        network: {
                            publicIp,
                            source: 'ipify'
                        }
                    });
                }
                return { fpHash, fpComponents };
            }
            fpHash = fp.hash || '';
            const components = Object.assign({}, fp.components || {});
            if (publicIp) {
                components.network = Object.assign({}, components.network || {}, {
                    publicIp,
                    source: 'ipify'
                });
            }
            fpComponents = JSON.stringify(components);
        } catch (e) { }
        return { fpHash, fpComponents };
    }

    document.addEventListener('DOMContentLoaded', function () {
        warmFingerprint();

        // Password visibility toggle
        document.querySelectorAll('.toggle-password, .toggle-password-confirm').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('feather-eye');
                this.classList.toggle('feather-eye-off');

                const group = this.closest('.pass-group') || this.parentElement.parentElement;
                if (group) {
                    const input = group.querySelector('.pass-input, .pass-confirm');
                    if (input) {
                        input.type = input.type === 'password' ? 'text' : 'password';
                    }
                }
            });
        });
    });

    window.KaiAuthForms = {
        DEFAULT_TIMEOUT_MS,
        getTurnstileToken,
        resetTurnstile,
        safeParseJson,
        fetchFormJson,
        warmFingerprint,
        collectFingerprintData,
        fetchPublicIp
    };
})(window);

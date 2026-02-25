/**
 * KaiShop Fingerprint Collector v1.0
 * Production-grade multi-layer browser fingerprinting
 * 
 * Layers collected:
 *   L3 — Canvas, WebGL, Audio, Fonts, Screen, Timezone, Language, Hardware
 *   L4 — GPU, CPU cores, RAM, Touch
 *   L5 — Storage availability
 * 
 * Usage:
 *   const fp = await KaiFingerprint.collect();
 *   // fp.hash  → SHA-256 hex string (64 chars)
 *   // fp.components → JSON object with all signals
 */
const KaiFingerprint = (() => {
    'use strict';

    // ─── Canvas Fingerprint ───────────────────────────────
    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 128;
            const ctx = canvas.getContext('2d');
            if (!ctx) return 'no-canvas';

            // Text with custom styling
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(60, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.font = '14px Arial';
            ctx.fillText('KaiShop FP 🛒', 2, 15);
            ctx.fillStyle = 'rgba(102,204,0,0.7)';
            ctx.font = '18px Georgia';
            ctx.fillText('KaiShop FP 🛒', 4, 45);

            // Geometric shapes
            ctx.globalCompositeOperation = 'multiply';
            ctx.fillStyle = 'rgb(255,0,255)';
            ctx.beginPath();
            ctx.arc(50, 80, 30, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            ctx.fillStyle = 'rgb(0,255,255)';
            ctx.beginPath();
            ctx.arc(80, 80, 30, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();

            return canvas.toDataURL();
        } catch (e) {
            return 'canvas-error';
        }
    }

    // ─── WebGL Fingerprint ────────────────────────────────
    function getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return { renderer: 'no-webgl', vendor: 'no-webgl' };

            const dbgInfo = gl.getExtension('WEBGL_debug_renderer_info');
            return {
                renderer: dbgInfo ? gl.getParameter(dbgInfo.UNMASKED_RENDERER_WEBGL) : 'unknown',
                vendor: dbgInfo ? gl.getParameter(dbgInfo.UNMASKED_VENDOR_WEBGL) : 'unknown',
                version: gl.getParameter(gl.VERSION),
                shadingVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                maxTextureSize: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                maxViewportDims: gl.getParameter(gl.MAX_VIEWPORT_DIMS)?.toString() || ''
            };
        } catch (e) {
            return { renderer: 'webgl-error', vendor: 'webgl-error' };
        }
    }

    // ─── Audio Fingerprint ────────────────────────────────
    function getAudioFingerprint() {
        return new Promise((resolve) => {
            try {
                const AudioCtx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
                if (!AudioCtx) { resolve('no-audio-ctx'); return; }

                const ctx = new AudioCtx(1, 44100, 44100);
                const oscillator = ctx.createOscillator();
                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(10000, ctx.currentTime);

                const compressor = ctx.createDynamicsCompressor();
                compressor.threshold.setValueAtTime(-50, ctx.currentTime);
                compressor.knee.setValueAtTime(40, ctx.currentTime);
                compressor.ratio.setValueAtTime(12, ctx.currentTime);
                compressor.attack.setValueAtTime(0, ctx.currentTime);
                compressor.release.setValueAtTime(0.25, ctx.currentTime);

                oscillator.connect(compressor);
                compressor.connect(ctx.destination);
                oscillator.start(0);

                ctx.startRendering().then((buffer) => {
                    const data = buffer.getChannelData(0);
                    let sum = 0;
                    for (let i = 4500; i < 5000; i++) {
                        sum += Math.abs(data[i]);
                    }
                    resolve(sum.toString());
                }).catch(() => resolve('audio-render-fail'));

                setTimeout(() => resolve('audio-timeout'), 1000);
            } catch (e) {
                resolve('audio-error');
            }
        });
    }

    // ─── Font Detection ───────────────────────────────────
    function getInstalledFonts() {
        const baseFonts = ['monospace', 'sans-serif', 'serif'];
        const testFonts = [
            'Arial', 'Arial Black', 'Calibri', 'Cambria', 'Comic Sans MS',
            'Consolas', 'Courier New', 'Georgia', 'Helvetica', 'Impact',
            'Lucida Console', 'Lucida Sans Unicode', 'Microsoft Sans Serif',
            'Palatino Linotype', 'Segoe UI', 'Tahoma', 'Times New Roman',
            'Trebuchet MS', 'Verdana', 'Roboto', 'Open Sans', 'Noto Sans'
        ];

        const testStr = 'mmmmmmmmmmlli';
        const testSize = '72px';
        const detected = [];

        try {
            const span = document.createElement('span');
            span.style.position = 'absolute';
            span.style.left = '-9999px';
            span.style.fontSize = testSize;
            span.style.lineHeight = 'normal';
            span.innerHTML = testStr;
            document.body.appendChild(span);

            const baseWidths = {};
            for (const base of baseFonts) {
                span.style.fontFamily = base;
                baseWidths[base] = span.offsetWidth;
            }

            for (const font of testFonts) {
                let isDetected = false;
                for (const base of baseFonts) {
                    span.style.fontFamily = `'${font}', ${base}`;
                    if (span.offsetWidth !== baseWidths[base]) {
                        isDetected = true;
                        break;
                    }
                }
                if (isDetected) detected.push(font);
            }

            document.body.removeChild(span);
        } catch (e) {
            // Silent fail
        }

        return detected;
    }

    // ─── Screen Info ──────────────────────────────────────
    function getScreenInfo() {
        return {
            width: screen.width,
            height: screen.height,
            availWidth: screen.availWidth,
            availHeight: screen.availHeight,
            colorDepth: screen.colorDepth,
            pixelDepth: screen.pixelDepth,
            devicePixelRatio: window.devicePixelRatio || 1
        };
    }

    // ─── Hardware Info ─────────────────────────────────────
    function getHardwareInfo() {
        return {
            cpuCores: navigator.hardwareConcurrency || 'unknown',
            deviceMemory: navigator.deviceMemory || 'unknown',
            maxTouchPoints: navigator.maxTouchPoints || 0,
            platform: navigator.platform || 'unknown'
        };
    }

    // ─── Environment Info ─────────────────────────────────
    function getEnvironmentInfo() {
        // Collect modern Client Hints if available (Chromium feature)
        let clientHints = null;
        if (navigator.userAgentData) {
            clientHints = {
                brands: navigator.userAgentData.brands?.map(b => b.brand).join(','),
                mobile: navigator.userAgentData.mobile,
                platform: navigator.userAgentData.platform
            };
        }

        return {
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezoneOffset: new Date().getTimezoneOffset(),
            language: navigator.language,
            languages: (navigator.languages || []).join(','),
            cookieEnabled: navigator.cookieEnabled,
            doNotTrack: navigator.doNotTrack || 'unknown',
            online: navigator.onLine,
            clientHints: clientHints
        };
    }

    // ─── Storage Availability ─────────────────────────────
    function getStorageInfo() {
        let localStorage = false;
        let sessionStorage = false;
        let indexedDB = false;

        try { localStorage = !!window.localStorage; } catch (e) { }
        try { sessionStorage = !!window.sessionStorage; } catch (e) { }
        try { indexedDB = !!window.indexedDB; } catch (e) { }

        return { localStorage, sessionStorage, indexedDB };
    }

    // ─── SHA-256 Hash ─────────────────────────────────────
    async function sha256(message) {
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    // ─── Main Collector ───────────────────────────────────
    async function collect() {
        const canvas = getCanvasFingerprint();
        const webgl = getWebGLFingerprint();
        const audio = await getAudioFingerprint();
        const fonts = getInstalledFonts();
        const screenInfo = getScreenInfo();
        const hardware = getHardwareInfo();
        const environment = getEnvironmentInfo();
        const storage = getStorageInfo();

        const components = {
            canvas: canvas.substring(0, 100), // truncate for storage
            webgl,
            audio,
            fonts,
            screen: screenInfo,
            hardware,
            environment,
            storage
        };

        // Build stable string for hashing (exclude volatile data)
        const stableSignals = [
            canvas,
            JSON.stringify(webgl),
            audio,
            fonts.join(','),
            screenInfo.width + 'x' + screenInfo.height,
            screenInfo.colorDepth,
            screenInfo.devicePixelRatio,
            hardware.cpuCores,
            hardware.deviceMemory,
            hardware.maxTouchPoints,
            hardware.platform,
            environment.timezone,
            environment.timezoneOffset,
            environment.language
        ].join('|||');

        const hash = await sha256(stableSignals);

        return { hash, components };
    }

    return { collect };
})();

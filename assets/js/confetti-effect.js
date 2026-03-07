(function () {
    var loader = null;

    function ensureReady() {
        if (typeof confetti === 'function') return Promise.resolve();
        if (loader) return loader;

        loader = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
            script.async = true;
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error('confetti_load_failed')); };
            document.head.appendChild(script);
        }).catch(function () {
            loader = null;
        });

        return loader || Promise.resolve();
    }

    function fire(options) {
        if (typeof confetti !== 'function') return;

        const count = 200;
        const isMobile = window.innerWidth <= 768;
        const originY = isMobile ? 0.85 : 0.75;

        const defaults = {
            origin: { y: originY },
            spread: 70,
            ticks: 200,
            gravity: 1.2,
            decay: 0.94,
            startVelocity: 45,
            zIndex: 100000
        };

        confetti({
            ...defaults,
            particleCount: count,
            angle: 60,
            origin: { x: 0, y: originY }
        });

        confetti({
            ...defaults,
            particleCount: count,
            angle: 120,
            origin: { x: 1, y: originY }
        });
    }

    window.KaiConfetti = {
        ensureReady: ensureReady,
        fire: fire
    };
})();

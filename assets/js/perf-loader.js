(function () {
    if (window.__KAI_PERF_LOADER__) return;
    window.__KAI_PERF_LOADER__ = true;

    var prefetched = new Set();

    function isSlowConnection() {
        var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!c) return false;
        if (c.saveData) return true;
        var type = String(c.effectiveType || '').toLowerCase();
        return type === 'slow-2g' || type === '2g';
    }

    function normalizeUrl(href) {
        try {
            return new URL(href, window.location.href);
        } catch (e) {
            return null;
        }
    }

    function shouldPrefetchLink(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.dataset && anchor.dataset.noPrefetch === '1') return false;
        if (anchor.target && anchor.target !== '' && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if ((anchor.getAttribute('rel') || '').includes('nofollow')) return false;

        var url = normalizeUrl(anchor.href);
        if (!url) return false;

        if (url.origin !== window.location.origin) return false;
        if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
        if (url.hash && (url.pathname + url.search) === (window.location.pathname + window.location.search)) return false;

        var href = url.href.toLowerCase();
        if (href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return false;
        if (href.indexOf('/logout') !== -1) return false;
        if (url.searchParams.has('delete')) return false;

        return true;
    }

    function prefetchHref(href) {
        if (!href || prefetched.has(href)) return;
        prefetched.add(href);

        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.as = 'document';
        link.href = href;
        link.crossOrigin = 'anonymous';
        link.onerror = function () {
            prefetched.delete(href);
        };
        document.head.appendChild(link);
    }

    function setupLinkPrefetch() {
        if (isSlowConnection()) return;

        var prefetchOnIntent = function (event) {
            var el = event.target;
            if (!el) return;
            var anchor = el.closest ? el.closest('a[href]') : null;
            if (!shouldPrefetchLink(anchor)) return;
            prefetchHref(anchor.href);
        };

        document.addEventListener('mouseover', prefetchOnIntent, { passive: true });
        document.addEventListener('focusin', prefetchOnIntent, { passive: true });
        document.addEventListener('touchstart', prefetchOnIntent, { passive: true });

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var anchor = entry.target;
                    if (shouldPrefetchLink(anchor)) {
                        prefetchHref(anchor.href);
                    }
                    io.unobserve(anchor);
                });
            }, { rootMargin: '200px' });

            var anchors = document.querySelectorAll('a[href]');
            anchors.forEach(function (a) { io.observe(a); });
        }
    }

    function setupImageHints() {
        var images = document.querySelectorAll('img');
        if (!images.length) return;

        var eagerBudget = 2;
        images.forEach(function (img) {
            if (!img.getAttribute('decoding')) {
                img.setAttribute('decoding', 'async');
            }
            if (img.hasAttribute('loading')) return;

            var rect;
            try {
                rect = img.getBoundingClientRect();
            } catch (e) {
                rect = null;
            }
            var nearViewport = !!rect && rect.top < (window.innerHeight * 1.2);
            if (nearViewport && eagerBudget > 0) {
                img.setAttribute('loading', 'eager');
                eagerBudget--;
            } else {
                img.setAttribute('loading', 'lazy');
            }
        });
    }

    function preconnectOrigins() {
        if (isSlowConnection()) return;
        var origins = [
            'https://cdn.jsdelivr.net',
            'https://cdn.datatables.net',
            'https://cdn.jsdelivr.net',
            'https://cdn.gtranslate.net',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com'
        ];
        var seen = new Set();
        origins.forEach(function (origin) {
            if (!origin || seen.has(origin)) return;
            seen.add(origin);
            if (document.head.querySelector('link[rel="preconnect"][href="' + origin + '"]')) return;
            var link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = origin;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    }

    function run() {
        setupImageHints();
        setupLinkPrefetch();
        preconnectOrigins();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }
})();

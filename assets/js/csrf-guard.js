(function () {
    if (window.__ksCsrfGuardInitialized) {
        return;
    }
    window.__ksCsrfGuardInitialized = true;

    var token = '';
    try {
        token = (window.KS_CSRF_TOKEN || '').toString();
    } catch (e) { token = ''; }

    if (!token) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        token = meta ? (meta.getAttribute('content') || '') : '';
    }

    if (!token) {
        return;
    }

    function ensureFormToken(form) {
        if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') return;

        var action = form.getAttribute('action') || '';
        if (action && /\/api\/sepay\/webhook(?:$|[?#])/i.test(action)) return;

        var input = form.querySelector('input[name="csrf_token"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            form.appendChild(input);
        }
        input.value = token;
    }

    function patchAllForms() {
        document.querySelectorAll('form').forEach(ensureFormToken);
    }

    function shouldSkipCsrf(url) {
        try {
            var u = new URL(url, window.location.origin);
            return /\/api\/sepay\/webhook$/i.test(u.pathname);
        } catch (e) {
            return false;
        }
    }

    function injectIntoFormData(formData) {
        if (!(formData instanceof FormData)) return;
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', token);
        }
    }

    function injectIntoBody(init) {
        if (!init) return init;
        var body = init.body;
        if (!body) return init;

        if (body instanceof FormData) {
            injectIntoFormData(body);
            return init;
        }

        if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
            if (!body.has('csrf_token')) body.append('csrf_token', token);
            return init;
        }

        if (typeof body === 'string') {
            var contentType = '';
            if (init.headers) {
                if (typeof Headers !== 'undefined' && init.headers instanceof Headers) {
                    contentType = init.headers.get('Content-Type') || '';
                } else if (Array.isArray(init.headers)) {
                    init.headers.forEach(function (h) {
                        if (h && String(h[0]).toLowerCase() === 'content-type') contentType = h[1] || '';
                    });
                } else if (typeof init.headers === 'object') {
                    contentType = init.headers['Content-Type'] || init.headers['content-type'] || '';
                }
            }
            if (/application\/x-www-form-urlencoded/i.test(contentType) && body.indexOf('csrf_token=') === -1) {
                init.body = body + (body ? '&' : '') + 'csrf_token=' + encodeURIComponent(token);
            }
        }
        return init;
    }

    // Patch fetch globally
    if (typeof window.fetch === 'function') {
        var _fetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            var url = (typeof input === 'string') ? input : ((input && input.url) || '');
            var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();

            if (method !== 'GET' && method !== 'HEAD' && !shouldSkipCsrf(url)) {
                init = init || {};
                init.headers = init.headers || {};

                if (typeof Headers !== 'undefined' && init.headers instanceof Headers) {
                    if (!init.headers.has('X-CSRF-Token')) init.headers.set('X-CSRF-Token', token);
                } else if (Array.isArray(init.headers)) {
                    var has = init.headers.some(function (h) { return h && String(h[0]).toLowerCase() === 'x-csrf-token'; });
                    if (!has) init.headers.push(['X-CSRF-Token', token]);
                } else {
                    if (!init.headers['X-CSRF-Token'] && !init.headers['x-csrf-token']) {
                        init.headers['X-CSRF-Token'] = token;
                    }
                }

                injectIntoBody(init);
            }

            return _fetch(input, init);
        };
    }

    // jQuery AJAX auto header/data
    function patchJquery() {
        if (!window.jQuery || !window.jQuery.ajaxPrefilter || window.jQuery.__ksCsrfPatched) return;
        window.jQuery.__ksCsrfPatched = true;

        window.jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
            var method = String(options.type || options.method || 'GET').toUpperCase();
            var url = String(options.url || '');
            if (method === 'GET' || method === 'HEAD' || shouldSkipCsrf(url)) return;

            jqXHR.setRequestHeader('X-CSRF-Token', token);

            if (originalOptions && originalOptions.data instanceof FormData) {
                if (!originalOptions.data.has('csrf_token')) {
                    originalOptions.data.append('csrf_token', token);
                }
                return;
            }

            if (typeof originalOptions.data === 'string') {
                if (originalOptions.data.indexOf('csrf_token=') === -1) {
                    options.data = originalOptions.data + (originalOptions.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(token);
                }
                return;
            }

            if (originalOptions.data && typeof originalOptions.data === 'object' && !Array.isArray(originalOptions.data)) {
                if (typeof originalOptions.data.csrf_token === 'undefined') {
                    originalOptions.data.csrf_token = token;
                    options.data = originalOptions.data;
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', patchAllForms);
    document.addEventListener('submit', function (e) { ensureFormToken(e.target); }, true);

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (!m.addedNodes) return;
                m.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) return;
                    if (node.tagName && node.tagName.toLowerCase() === 'form') ensureFormToken(node);
                    if (node.querySelectorAll) node.querySelectorAll('form').forEach(ensureFormToken);
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.documentElement || document.body, { childList: true, subtree: true });
        });
    }

    patchJquery();
    document.addEventListener('DOMContentLoaded', patchJquery);
    window.setTimeout(patchJquery, 500);
})();

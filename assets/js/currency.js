/**
 * currency.js - Global VND <> USD display toggle
 * Looks for elements with [data-price-vnd] and toggles display currency.
 * Exchange rate is set in window.KAI_EXCHANGE_RATE (from admin setting binance_rate_vnd).
 */
(function () {
    var RATE = Number(window.KAI_EXCHANGE_RATE || 25000);
    // Initialize state from localStorage
    var isUsd = localStorage.getItem('kai_currency_is_usd') === 'true';

    function formatVnd(n) {
        var num = Math.round(Number(n || 0));
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ';
    }

    function formatUsd(n) {
        var usd = Number(n || 0) / RATE;
        if (usd < 0.01 && usd > 0) usd = 0.01;
        return '$' + usd.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateToggleUI() {
        var label = document.getElementById('currency-toggle-label');
        if (label) {
            label.textContent = isUsd ? 'USD' : 'VND';
        }
        var icon = document.getElementById('currency-toggle-icon');
        if (icon) {
            if (isUsd) {
                icon.innerHTML = '🌍';
            } else {
                var baseUrl = window.KAI_ASSET_URL || '';
                if (baseUrl && !baseUrl.endsWith('/')) baseUrl += '/';
                icon.innerHTML = '<img src="' + baseUrl + 'assets/images/vn.png" alt="VND">';
            }
        }
        var toggle = document.getElementById('btn-toggle-currency');
        if (toggle) {
            toggle.classList.toggle('is-usd', isUsd);
            toggle.setAttribute('aria-pressed', isUsd ? 'true' : 'false');
        }
    }

    function refreshPrices() {
        document.querySelectorAll('[data-price-vnd]').forEach(function (el) {
            var vnd = Number(el.getAttribute('data-price-vnd') || 0);
            el.textContent = isUsd ? formatUsd(vnd) : formatVnd(vnd);
        });

        // Also update the product detail JS price calculations if on detail page
        if (typeof window.KAI_CURRENCY_CHANGED === 'function') {
            window.KAI_CURRENCY_CHANGED(isUsd, RATE);
        }
    }

    function toggleCurrency() {
        isUsd = !isUsd;
        localStorage.setItem('kai_currency_is_usd', isUsd);
        updateToggleUI();
        refreshPrices();
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateToggleUI();
        refreshPrices();

        // Global delegate for the toggle button (it might be in nav or profile)
        document.addEventListener('click', function (e) {
            if (e.target.closest('#btn-toggle-currency')) {
                toggleCurrency();
            }
        });

        document.addEventListener('keydown', function (e) {
            var toggle = e.target.closest('#btn-toggle-currency');
            if (!toggle) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleCurrency();
            }
        });
    });

    // Expose global API for other scripts
    window.KAI_CURRENCY = {
        getRate: function () { return RATE; },
        isUsd: function () { return isUsd; },
        formatVnd: formatVnd,
        formatUsd: formatUsd,
        refresh: refreshPrices,
        toggle: toggleCurrency
    };
})();

(function () {
    var __ksBalanceConfettiLoader = null;

    function formatVnd(n) {
        var num = Number(n || 0);
        if (!Number.isFinite(num)) num = 0;
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDateTimeText(value) {
        var raw = String(value || '').trim();
        return raw || '--';
    }

    function formatDurationVi(totalSeconds) {
        if (window.KaiTime && typeof window.KaiTime.formatDurationVi === 'function') {
            return window.KaiTime.formatDurationVi(totalSeconds);
        }
        var s = Math.max(0, Math.floor(Number(totalSeconds || 0)));
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        var parts = [];
        if (h > 0) parts.push(h + ' giờ');
        if (m > 0) parts.push(m + ' phút');
        if (sec > 0 || parts.length === 0) parts.push(sec + ' giây');
        return parts.join(' ');
    }

    function ensureConfettiReady() {
        if (typeof confetti === 'function') return Promise.resolve();
        if (__ksBalanceConfettiLoader) return __ksBalanceConfettiLoader;

        __ksBalanceConfettiLoader = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
            script.async = true;
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error('confetti_load_failed')); };
            document.head.appendChild(script);
        }).catch(function () {
            __ksBalanceConfettiLoader = null;
        });

        return __ksBalanceConfettiLoader || Promise.resolve();
    }

    function fireDoubleSideConfetti() {
        if (typeof confetti !== 'function') return;

        var count = 250;
        var defaults = {
            origin: { y: 0.7 },
            spread: 90,
            ticks: 300,
            gravity: 1.2,
            decay: 0.94,
            startVelocity: 45,
            zIndex: 100000
        };

        confetti(Object.assign({}, defaults, {
            particleCount: count,
            angle: 60,
            origin: { x: 0, y: 0.7 }
        }));

        confetti(Object.assign({}, defaults, {
            particleCount: count,
            angle: 120,
            origin: { x: 1, y: 0.7 }
        }));
    }

    function DepositSuccessPresenter(options) {
        options = options || {};
        this.endpoints = options.endpoints || {};
        this.methodRoutes = options.methodRoutes || {};
        this.activeMethod = String(options.activeMethod || 'bank_sepay');
    }

    DepositSuccessPresenter.prototype.ensureStyles = function () {
        var styleId = 'deposit-success-modal-style';
        if (document.getElementById(styleId)) return;

        var style = document.createElement('style');
        style.id = styleId;
        style.innerHTML = [
            '.kai-swal-success-popup{width:680px !important;max-width:92vw !important;border-radius:16px !important;padding:22px 22px 20px !important;}',
            '.kai-swal-success-title{font-size:26px !important;font-weight:800 !important;color:#333 !important;line-height:1.2 !important;margin-top:4px !important;}',
            '.kai-swal-success-html{margin:0 !important;padding:0 !important;}',
            '.kai-swal-success-actions{margin-top:14px !important;}',
            '.kai-swal-success-confirm{background:#ff6900 !important;color:#fff !important;border-radius:10px !important;padding:10px 22px !important;font-weight:700 !important;box-shadow:none !important;}',
            '.kai-balance-success-wrap{text-align:left;}',
            '.kai-balance-success-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;margin-bottom:10px;}',
            '.kai-balance-success-row{display:flex;justify-content:space-between;gap:10px;padding:4px 0;font-size:14px;}',
            '.kai-balance-success-row span{color:#475569;}',
            '.kai-balance-success-row strong{color:#111827;text-align:right;word-break:break-word;}',
            '.kai-balance-success-row.is-green, .kai-balance-success-row.is-green span, .kai-balance-success-row.is-green strong{color:#16a34a;}',
            '.kai-balance-success-balance{margin-top:10px;padding:10px 12px;border:1px solid #e2e8f0;background:#ffffff;border-radius:12px;text-align:center;}',
            '.kai-balance-success-balance__label{font-size:13px;color:#64748b;margin-bottom:4px;}',
            '.kai-balance-success-balance__value{font-size:28px;font-weight:800;color:#00ad5c;line-height:1.15;}',
            '@media (max-width:575.98px){.kai-swal-success-title{font-size:22px !important;}.kai-balance-success-row{font-size:13px;}.kai-balance-success-row strong{max-width:58%;}}'
        ].join('');
        document.head.appendChild(style);
    };

    DepositSuccessPresenter.prototype.fireDoubleSideConfetti = function () {
        fireDoubleSideConfetti();
    };

    DepositSuccessPresenter.prototype.resolveRedirectUrl = function (res) {
        var info = (res && res.deposit_info) ? res.deposit_info : {};
        var methodCode = String((info && info.method) || this.activeMethod || '');
        return this.methodRoutes[methodCode] || this.endpoints.profile || (window.location.origin + '/profile');
    };

    DepositSuccessPresenter.prototype.buildHtml = function (res) {
        var info = (res && res.deposit_info) ? res.deposit_info : {};
        var code = String(info.content || info.deposit_code || '');
        var amount = Number(info.amount || 0);
        var bonusPercent = Number(info.bonus_percent || 0);
        var createdAt = formatDateTimeText(info.created_at_display || info.created_at);
        var completedAt = formatDateTimeText(info.completed_at_display || info.completed_at);
        var processingText = formatDurationVi(info.processing_seconds || 0);
        var rows = '';

        rows += '<div class="kai-balance-success-row is-green"><span>Số tiền nạp</span><strong>' + escapeHtml(formatVnd(amount) + 'đ') + '</strong></div>';
        rows += '<div class="kai-balance-success-row"><span>Nội dung chuyển khoản</span><strong>' + escapeHtml(code || '--') + '</strong></div>';
        rows += '<div class="kai-balance-success-row"><span>Thời gian tạo</span><strong>' + escapeHtml(createdAt) + '</strong></div>';
        rows += '<div class="kai-balance-success-row"><span>Hoàn tất giao dịch</span><strong>' + escapeHtml(completedAt) + '</strong></div>';
        rows += '<div class="kai-balance-success-row"><span>Thời gian xử lý</span><strong>' + escapeHtml(processingText) + '</strong></div>';

        return ''
            + '<div class="kai-balance-success-wrap">'
            + '<div class="kai-balance-success-card">'
            + rows
            + '</div>'
            + '<div class="kai-balance-success-balance">'
            + '<div class="kai-balance-success-balance__label">Số dư hiện tại</div>'
            + '<div class="kai-balance-success-balance__value">' + escapeHtml(formatVnd(Number((res && res.new_balance) || 0)) + 'đ') + '</div>'
            + '</div>'
            + '</div>';
    };

    DepositSuccessPresenter.prototype.show = function (res) {
        var redirectUrl = this.resolveRedirectUrl(res);

        if (!window.Swal) {
            window.alert('Nạp tiền thành công. Số dư: ' + formatVnd(Number((res && res.new_balance) || 0)) + 'đ');
            window.location.href = redirectUrl;
            return;
        }

        this.ensureStyles();
        var self = this;
        window.Swal.fire({
            icon: 'success',
            title: 'Nạp tiền thành công',
            html: self.buildHtml(res),
            width: 680,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff6900',
            allowOutsideClick: false,
            customClass: {
                popup: 'kai-swal-success-popup',
                title: 'kai-swal-success-title',
                htmlContainer: 'kai-swal-success-html',
                actions: 'kai-swal-success-actions',
                confirmButton: 'kai-swal-success-confirm'
            },
            didOpen: function () {
                ensureConfettiReady().then(function () {
                    self.fireDoubleSideConfetti();
                }).catch(function () { });
            }
        }).then(function () {
            window.location.href = redirectUrl;
        });
    };

    window.KaiBalanceSuccess = window.KaiBalanceSuccess || {};
    window.KaiBalanceSuccess.DepositSuccessPresenter = DepositSuccessPresenter;
    window.KaiBalanceSuccess.ensureConfettiReady = ensureConfettiReady;
    window.KaiBalanceSuccess.fireDoubleSideConfetti = fireDoubleSideConfetti;
    window.KaiBalanceSuccess.createPresenter = function (options) {
        return new DepositSuccessPresenter(options || {});
    };
    window.fireDoubleSideConfetti = fireDoubleSideConfetti;
})();

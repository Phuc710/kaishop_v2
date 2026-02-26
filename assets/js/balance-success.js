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
            zIndex: 1000
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
            '.deposit-success-checkmark{width:84px;height:84px;border-radius:50%;display:block;stroke-width:2;stroke:#00ad5c;stroke-miterlimit:10;margin:10px auto 16px;box-shadow:inset 0 0 0 #00ad5c;animation:depositSuccessFill .4s ease-in-out .4s forwards,depositSuccessScale .3s ease-in-out .9s both;}',
            '@keyframes depositSuccessFill{100%{box-shadow:inset 0 0 0 42px #00ad5c;}}',
            '@keyframes depositSuccessScale{0%,100%{transform:none;}50%{transform:scale3d(1.06,1.06,1);}}',
            '.deposit-success-card{margin-top:12px;text-align:left;padding:14px;background:linear-gradient(180deg,#f8fafc 0%,#ffffff 100%);border:1px solid #e2e8f0;border-radius:12px;}',
            '.deposit-success-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px dashed #e2e8f0;font-size:14px;}',
            '.deposit-success-row:last-child{border-bottom:none;}',
            '.deposit-success-row span{color:#475569;}',
            '.deposit-success-row strong{color:#0f172a;text-align:right;word-break:break-word;}',
            '.deposit-success-row strong.amount{color:#00ad5c;}',
            '.deposit-success-balance{margin-top:12px;padding-top:12px;border-top:1px dashed #cbd5e1;text-align:center;}',
            '.deposit-success-balance__label{font-size:13px;color:#64748b;}',
            '.deposit-success-balance__value{font-size:26px;font-weight:800;color:#00ad5c;line-height:1.2;}'
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
        var createdAt = formatDateTimeText(info.created_at);
        var completedAt = formatDateTimeText(info.completed_at);
        var processingText = formatDurationVi(info.processing_seconds || 0);
        var rows = '';

        rows += '<div class="deposit-success-row"><span>Số tiền nạp</span><strong class="amount">' + escapeHtml(formatVnd(amount) + 'đ') + '</strong></div>';
        rows += '<div class="deposit-success-row"><span>Nội dung chuyển khoản</span><strong>' + escapeHtml(code || '--') + '</strong></div>';
        if (bonusPercent > 0) {
            rows += '<div class="deposit-success-row"><span>Khuyến mãi</span><strong>+' + escapeHtml(String(bonusPercent)) + '%</strong></div>';
        }
        rows += '<div class="deposit-success-row"><span>Thời gian tạo</span><strong>' + escapeHtml(createdAt) + '</strong></div>';
        rows += '<div class="deposit-success-row"><span>Hoàn tất giao dịch</span><strong>' + escapeHtml(completedAt) + '</strong></div>';
        rows += '<div class="deposit-success-row"><span>Thời gian xử lý</span><strong>' + escapeHtml(processingText) + '</strong></div>';

        var checkmarkSvg = ''
            + '<svg class="deposit-success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">'
            + '<circle cx="26" cy="26" r="25" fill="none"></circle>'
            + '<path fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" stroke="#fff" stroke-width="4"></path>'
            + '</svg>';

        return ''
            + checkmarkSvg
            + '<div class="deposit-success-card">'
            + rows
            + '<div class="deposit-success-balance">'
            + '<div class="deposit-success-balance__label">Số dư hiện tại</div>'
            + '<div class="deposit-success-balance__value">' + escapeHtml(formatVnd(Number((res && res.new_balance) || 0)) + 'đ') + '</div>'
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
            title: 'Nạp tiền thành công',
            html: self.buildHtml(res),
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff6900',
            allowOutsideClick: false,
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

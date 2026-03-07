(function () {
    function formatVnd(n) {
        var num = Number(n || 0);
        if (!Number.isFinite(num)) num = 0;
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDateTimeText(input) {
        if (!input) return '--';
        return String(input);
    }

    function formatDurationText(seconds) {
        var s = Math.max(0, Number(seconds || 0));
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = Math.floor(s % 60);
        var parts = [];
        if (h > 0) parts.push(h + 'h');
        if (m > 0) parts.push(m + 'm');
        if (sec > 0 || parts.length === 0) parts.push(sec + 's');
        return parts.join(' ');
    }

    function ensureStyles() {
        var styleId = 'deposit-success-modal-style';
        if (document.getElementById(styleId)) return;
        var style = document.createElement('style');
        style.id = styleId;
        style.textContent = ''
            + '.kai-balance-success-wrap { padding: 10px 5px; max-width: 100%; margin: 0 auto; text-align: center; }'
            + '.kai-balance-success-card { background: #f9fafb; border-radius: 12px; padding: 12px 16px; margin-bottom: 15px; border: 1px solid #f3f4f6; }'
            + '.kai-balance-success-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; gap: 10px; }'
            + '.kai-balance-success-label { color: #6b7280; font-size: 14px; text-align: left; min-width: 80px; flex-shrink: 0; }'
            + '.kai-balance-success-value { color: #111827; font-weight: 600; font-size: 14px; text-align: right; word-break: break-all; }'
            + '.kai-balance-success-balance__value { font-size: 18px; line-height: 1.4; color: #111827; font-weight: 600; }'
            + '@media (max-width: 576px) {'
            + '  .kai-balance-success-wrap { padding: 5px 0; }'
            + '  .kai-balance-success-card { padding: 10px 12px; margin-bottom: 12px; }'
            + '  .kai-balance-success-label, .kai-balance-success-value { font-size: 13.5px; }'
            + '  .kai-balance-success-row { margin-bottom: 6px; }'
            + '  .text-success { font-size: 28px !important; margin-bottom: 5px !important; }'
            + '  .kai-balance-success-balance__value { font-size: 16px; margin-top: 10px; }'
            + '  .swal2-title { font-size: 1.25rem !important; }'
            + '  .swal2-popup { padding: 1rem 0.5rem !important; }'
            + '}';
        document.head.appendChild(style);
    }

    function DepositSuccessPresenter(options) {
        this.endpoints = options && options.endpoints ? options.endpoints : {};
        this.methodRoutes = options && options.methodRoutes ? options.methodRoutes : {};
        this.activeMethod = options && options.activeMethod ? options.activeMethod : 'bank_sepay';
        this.binanceRateVnd = Number(options && options.binanceRateVnd ? options.binanceRateVnd : 25000) || 25000;
    }

    DepositSuccessPresenter.prototype.resolveRedirectUrl = function (res) {
        var info = (res && res.deposit_info) ? res.deposit_info : {};
        var methodCode = String((info && info.method) || this.activeMethod || '');
        return this.methodRoutes[methodCode] || this.endpoints.profile || (window.location.origin + '/profile');
    };

    DepositSuccessPresenter.prototype.buildHtml = function (res) {
        var info = (res && res.deposit_info) ? res.deposit_info : {};
        var method = String(info.method || this.activeMethod || 'bank_sepay');
        var code = String(info.content || info.deposit_code || '');
        var amount = Number(info.amount || 0);
        var usdAmount = Number(info.usd_amount || info.usdt_amount || 0);
        var usdtAmount = Number(info.usdt_amount || 0);
        var payerUid = String(info.payer_uid || '');
        var binanceUid = String(info.binance_uid || '');
        var transferNote = String(info.transfer_note || code || '');
        var createdAt = formatDateTimeText(info.created_at_display || info.created_at);
        var completedAt = formatDateTimeText(info.completed_at_display || info.completed_at);
        var processingText = formatDurationText(info.processing_seconds || 0);
        var newBalanceVnd = Number((res && res.new_balance) || 0);
        var balanceLabel = (method === 'binance' ? 'Current Balance' : 'Số dư hiện tại');
        var balanceValue = formatVnd(newBalanceVnd);

        var rows = '';
        var displayAmount = formatVnd(amount);
        var displayBalance = formatVnd(newBalanceVnd);
        var methodLabel = (method === 'binance' ? 'Binance Pay' : 'Chuyển khoản ngân hàng');

        if (method === 'binance') {
            var rate = Math.max(1, Number(this.binanceRateVnd || 25000));
            displayAmount = '+$' + usdAmount.toFixed(2);
            displayBalance = '$' + (newBalanceVnd / rate).toFixed(2);
        } else {
            displayAmount = '+' + formatVnd(amount);
        }

        // Essential Info Rows
        var labelMethod = (method === 'binance' ? 'Method' : 'Phương thức');
        var labelRef = (method === 'binance' ? 'Reference' : 'Mã giao dịch');
        var labelTime = (method === 'binance' ? 'Time' : 'Thời gian');

        rows += '<div class="kai-balance-success-row"><span>' + labelMethod + (method === 'binance' ? '' : ':') + '</span><strong>' + escapeHtml(methodLabel) + '</strong></div>';
        if (info.transaction_id) {
            var labelTxId = (method === 'binance' ? 'Transaction ID' : 'Mã giao dịch');
            rows += '<div class="kai-balance-success-row"><span>' + labelTxId + (method === 'binance' ? '' : ':') + '</span><strong>' + escapeHtml(info.transaction_id) + '</strong></div>';
        } else if (code) {
            rows += '<div class="kai-balance-success-row"><span>' + labelRef + (method === 'binance' ? '' : ':') + '</span><strong>' + escapeHtml(code) + '</strong></div>';
        }

        if (method === 'binance') {
            rows += '<div class="kai-balance-success-row"><span>USDT Paid</span><strong>' + escapeHtml(usdtAmount.toFixed(2) + ' USDT') + '</strong></div>';
        }

        rows += '<div class="kai-balance-success-row"><span>' + labelTime + (method === 'binance' ? '' : ':') + '</span><strong>' + escapeHtml(completedAt) + '</strong></div>';

        return ''
            + '<div class="kai-balance-success-wrap text-center">'
            + '  <div class="mb-3">'
            + '    <div class="text-success" style="font-size: 35px; font-weight: 850; line-height: 1; letter-spacing: -1px;">' + escapeHtml(displayAmount) + '</div>'
            + '  </div>'
            + '  <div class="kai-balance-success-card">' + rows + '</div>'
            + '  <div class="kai-balance-success-balance" style="border:none; background:transparent; padding:0; margin-top:15px;">'
            + '    <div class="kai-balance-success-balance__value" style="color: #111827; font-size: 18px; font-weight: 600;">'
            + '      ' + balanceLabel + ': <span style="color: #16a34a;">' + escapeHtml(displayBalance) + '</span>'
            + '    </div>'
            + '  </div>'
            + '</div>';
    };

    DepositSuccessPresenter.prototype.show = function (res) {
        var redirectUrl = this.resolveRedirectUrl(res);

        if (!window.Swal) {
            window.alert('Deposit success. Balance: ' + formatVnd(Number((res && res.new_balance) || 0)));
            window.location.href = redirectUrl;
            return;
        }

        ensureStyles();
        var self = this;
        var info = (res && res.deposit_info) ? res.deposit_info : {};
        var method = String(info.method || this.activeMethod || 'bank_sepay');

        window.Swal.fire({
            icon: 'success',
            title: (method === 'binance' ? 'Deposit Successful' : 'Thanh toán thành công'),
            html: self.buildHtml(res),
            width: 680,
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#2563eb',
            showCloseButton: true,
            allowOutsideClick: false,
            didOpen: function () {
                if (window.KaiConfetti && typeof window.KaiConfetti.ensureReady === 'function') {
                    window.KaiConfetti.ensureReady().then(function () {
                        if (typeof window.KaiConfetti.fire === 'function') {
                            window.KaiConfetti.fire();
                        }
                    });
                }
            },
            willClose: function () {
                window.location.href = redirectUrl;
            }
        });
    };

    window.KaiBalanceSuccess = {
        createPresenter: function (options) {
            return new DepositSuccessPresenter(options || {});
        }
    };
})();

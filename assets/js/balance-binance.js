(function () {
    function parseJsonConfig(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    function formatUsd(value) {
        var num = Number(value || 0);
        if (!Number.isFinite(num)) num = 0;
        return '$' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function resolveStatusText(status) {
        var normalized = String(status || '').toLowerCase();
        if (normalized === 'completed') return 'Completed';
        if (normalized === 'expired') return 'Expired';
        if (normalized === 'cancelled') return 'Cancelled';
        if (normalized === 'pending') return 'Pending';
        return 'Processing';
    }

    function toastCopySuccess(text) {
        if (!text) return;
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.toast === 'function') {
            SwalHelper.toast('Copied: ' + text, 'success');
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Copied: ' + text,
                showConfirmButton: false,
                timer: 1500
            });
        }
    }

    function alertError(text) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.error === 'function') {
            SwalHelper.error(text);
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: text });
        } else {
            alert(text);
        }
    }

    function toastError(text) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.toast === 'function') {
            SwalHelper.toast(text, 'error');
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: text,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(text);
        }
    }

    function alertInfo(text, title) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.info === 'function') {
            SwalHelper.info(text);
            return Promise.resolve();
        }
        if (typeof Swal !== 'undefined') {
            return Swal.fire({ icon: 'info', title: title || 'Notice', text: text });
        }
        alert(text);
        return Promise.resolve();
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function confirmAction(options) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.confirm === 'function') {
            return SwalHelper.confirm(options.title || 'Confirm', options.text || 'Are you sure?');
        }
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'warning',
                title: options.title || 'Confirm',
                text: options.text || 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Confirm',
                cancelButtonText: options.cancelText || 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then(function (result) {
                return !!result.isConfirmed;
            });
        }
        return Promise.resolve(window.confirm(options.text || 'Are you sure?'));
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
        } catch (e) {
        }
        document.body.removeChild(ta);
        toastCopySuccess(text);
    }

    function copyText(text) {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                toastCopySuccess(text);
            }).catch(function () {
                fallbackCopy(text);
            });
            return;
        }
        fallbackCopy(text);
    }

    function BinanceDepositPage(root, config) {
        this.root = root;
        this.config = config || {};
        this.activeMethod = String(this.config.activeMethod || '');
        if (this.activeMethod !== 'binance') {
            return;
        }

        this.endpoints = this.config.endpoints || {};
        this.createByMethod = (this.endpoints && this.endpoints.createByMethod) ? this.endpoints.createByMethod : {};
        this.methodRoutes = this.config.methodRoutes || {};
        this.bonusTiers = Array.isArray(this.config.bonusTiers) ? this.config.bonusTiers.slice() : [];
        this.ttlSeconds = Number(this.config.ttlSeconds || 300);
        this.csrfToken = String(this.config.csrfToken || '');
        this.storageKey = String(this.config.storageKey || 'ks_balance_binance');
        this.exchangeRateVnd = Number((this.config.binance && this.config.binance.rateVnd) || 25000);

        var activeDeposit = this.config.activeDeposit || null;
        var initialServerNowTs = Number(this.config.serverNowTs || (activeDeposit && activeDeposit.server_now_ts) || 0);

        this.successPresenter = (window.KaiBalanceSuccess && typeof window.KaiBalanceSuccess.createPresenter === 'function')
            ? window.KaiBalanceSuccess.createPresenter({
                endpoints: this.endpoints,
                methodRoutes: this.methodRoutes,
                activeMethod: this.activeMethod,
                binanceRateVnd: this.exchangeRateVnd
            })
            : {
                show: function () {
                    window.location.reload();
                }
            };

        this.elements = {
            stepAmount: root.querySelector('[data-deposit-step="amount"]'),
            stepTransfer: root.querySelector('[data-deposit-step="transfer"]'),
            amountInput: root.querySelector('[data-deposit-input-amount]'),
            payerUidInput: root.querySelector('[data-deposit-input-payer-uid]'),
            quickButtons: Array.prototype.slice.call(root.querySelectorAll('[data-deposit-quick]')),
            preview: root.querySelector('[data-deposit-preview]'),
            previewAmount: root.querySelector('[data-preview-amount]'),
            previewBonusRow: root.querySelector('[data-preview-bonus-row]'),
            previewBonus: root.querySelector('[data-preview-bonus]'),
            previewUsdt: root.querySelector('[data-preview-usdt]'),
            previewTotal: root.querySelector('[data-preview-total]'),
            createButton: root.querySelector('[data-deposit-action="create"]'),
            checkButton: root.querySelector('[data-deposit-action="check"]'),
            cancelButton: root.querySelector('[data-deposit-action="cancel"]'),
            tfBinanceUid: root.querySelector('[data-tf-binance-uid]'),
            tfPayerUid: root.querySelector('[data-tf-payer-uid]'),
            tfUsdt: root.querySelector('[data-tf-usdt]'),
            tfUsdtNote: root.querySelector('[data-tf-usdt-note]'),
            tfCode: root.querySelector('[data-tf-code]'),
            tfExpires: root.querySelector('[data-tf-expires]'),
            tfStatus: root.querySelector('[data-tf-status]'),
            countdown: root.querySelector('[data-deposit-countdown]'),
            countdownFill: root.querySelector('[data-deposit-countdown-fill]'),
            methodButtons: Array.prototype.slice.call(root.querySelectorAll('[data-method-code]'))
        };

        this.state = {
            usdAmount: Number(this.elements.amountInput ? this.elements.amountInput.value : 10) || 10,
            payerUid: String((activeDeposit && activeDeposit.payer_uid) || (this.elements.payerUidInput && this.elements.payerUidInput.value) || '').trim(),
            bonusPercent: 0,
            activeCode: activeDeposit && activeDeposit.deposit_code ? String(activeDeposit.deposit_code) : '',
            activeUsdtAmount: activeDeposit && activeDeposit.usdt_amount ? Number(activeDeposit.usdt_amount) : 0,
            activeCreatedAtTs: activeDeposit && activeDeposit.created_at_ts ? Number(activeDeposit.created_at_ts) : 0,
            activeExpiresAtTs: activeDeposit && activeDeposit.expires_at_ts ? Number(activeDeposit.expires_at_ts) : 0,
            currentCountdownTotal: Math.max(1, Number((activeDeposit && activeDeposit.ttl_seconds) || this.ttlSeconds || 300)),
            countdownTimer: null,
            pollTimer: null,
            pollToken: 0,
            lastKnownStatus: activeDeposit && activeDeposit.deposit_code ? 'pending' : '',
            pollBackoffMs: 0,
            serverOffsetMs: (Number.isFinite(initialServerNowTs) && initialServerNowTs > 0) ? ((initialServerNowTs * 1000) - Date.now()) : 0
        };

        if (this.elements.payerUidInput && this.state.payerUid !== '') {
            this.elements.payerUidInput.value = this.state.payerUid;
        }

        this.bindEvents();
        this.updatePreview();

        if (activeDeposit && activeDeposit.deposit_code) {
            this.resumeActiveDeposit(activeDeposit);
        } else {
            this.clearActiveDepositSnapshot();
        }
    }

    BinanceDepositPage.prototype.storageAvailable = function () {
        try {
            return !!window.localStorage;
        } catch (e) {
            return false;
        }
    };

    BinanceDepositPage.prototype.syncServerOffset = function (serverNowTs) {
        var ts = Number(serverNowTs || 0);
        if (!Number.isFinite(ts) || ts <= 0) return;
        this.state.serverOffsetMs = (ts * 1000) - Date.now();
    };

    BinanceDepositPage.prototype.getServerNowTs = function () {
        return Math.floor((Date.now() + Number(this.state.serverOffsetMs || 0)) / 1000);
    };

    BinanceDepositPage.prototype.persistActiveDepositSnapshot = function (extra) {
        if (!this.storageKey || !this.storageAvailable()) return;
        var payload = Object.assign({
            method: this.activeMethod,
            deposit_code: this.state.activeCode || '',
            payer_uid: this.state.payerUid || '',
            created_at_ts: Number(this.state.activeCreatedAtTs || 0),
            expires_at_ts: Number(this.state.activeExpiresAtTs || 0),
            ttl_seconds: Number(this.state.currentCountdownTotal || this.ttlSeconds || 300),
            server_now_ts: this.getServerNowTs()
        }, extra || {});
        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(payload));
        } catch (e) {
        }
    };

    BinanceDepositPage.prototype.clearActiveDepositSnapshot = function () {
        if (!this.storageKey || !this.storageAvailable()) return;
        try {
            window.localStorage.removeItem(this.storageKey);
        } catch (e) {
        }
    };

    BinanceDepositPage.prototype.resolveBonusPercent = function (usdAmount) {
        var compareAmountVnd = Number(usdAmount || 0) * Math.max(1, Number(this.exchangeRateVnd || 25000));
        var sorted = this.bonusTiers.slice().sort(function (a, b) {
            return Number(b.amount || 0) - Number(a.amount || 0);
        });
        for (var i = 0; i < sorted.length; i++) {
            var tierAmount = Number(sorted[i].amount || 0);
            var tierPercent = Number(sorted[i].percent || 0);
            if (tierAmount > 0 && compareAmountVnd >= tierAmount) {
                return tierPercent;
            }
        }
        return 0;
    };

    BinanceDepositPage.prototype.selectQuickButtonByAmount = function (amount) {
        this.elements.quickButtons.forEach(function (btn) {
            var btnAmount = Number(btn.getAttribute('data-amount') || 0);
            btn.classList.toggle('active', Math.abs(btnAmount - amount) < 0.00001);
        });
    };

    BinanceDepositPage.prototype.updatePreview = function () {
        this.state.bonusPercent = this.resolveBonusPercent(this.state.usdAmount);
        this.selectQuickButtonByAmount(this.state.usdAmount);

        if (!this.elements.preview || !this.elements.previewAmount || !this.elements.previewTotal) return;
        if (this.state.usdAmount <= 0) {
            this.elements.preview.hidden = true;
            return;
        }

        this.elements.preview.hidden = false;

        var bonusUsd = this.state.usdAmount * this.state.bonusPercent / 100;
        var totalUsd = this.state.usdAmount + bonusUsd;

        this.elements.previewAmount.textContent = formatUsd(this.state.usdAmount);

        if (this.elements.previewBonusRow && this.elements.previewBonus) {
            if (this.state.bonusPercent > 0) {
                this.elements.previewBonusRow.hidden = false;
                this.elements.previewBonus.textContent = '+' + formatUsd(bonusUsd) + ' (' + this.state.bonusPercent + '%)';
            } else {
                this.elements.previewBonusRow.hidden = true;
            }
        }

        if (this.elements.previewUsdt) {
            this.elements.previewUsdt.textContent = Number(this.state.usdAmount || 0).toFixed(2) + ' USDT';
        }

        this.elements.previewTotal.textContent = formatUsd(totalUsd);
    };

    BinanceDepositPage.prototype.setTransferStepData = function (data) {
        if (!data) return;

        if (this.elements.tfBinanceUid) this.elements.tfBinanceUid.textContent = String(data.binance_uid || '');
        if (this.elements.tfPayerUid) this.elements.tfPayerUid.textContent = String(data.payer_uid || this.state.payerUid || '');
        if (this.elements.tfCode) this.elements.tfCode.textContent = String(data.deposit_code || this.state.activeCode || '');

        if (this.elements.tfUsdt) {
            var usdt = Number(data.usdt_amount || 0);
            this.state.activeUsdtAmount = usdt;
            var usdtText = usdt.toFixed(2) + ' USDT';
            this.elements.tfUsdt.textContent = usdt > 0 ? ('$' + usdtText) : '';
            if (this.elements.tfUsdtNote) {
                this.elements.tfUsdtNote.textContent = usdt > 0 ? usdtText : '';
            }
        }

        if (this.elements.tfStatus) {
            this.elements.tfStatus.textContent = resolveStatusText(data.status);
        }

        if (this.elements.tfExpires) {
            this.elements.tfExpires.textContent = String(data.expires_at || '');
        }
    };

    BinanceDepositPage.prototype.showExpiredDepositMessage = function () {
        var depositCode = String(this.state.activeCode || '').trim();
        var usdtAmount = Number(this.state.activeUsdtAmount || 0);
        var html = ''
            + '<div style="text-align:left;line-height:1.7">'
            + '<div><strong>Binance payment request expired</strong></div>'
            + '<br>'
            + '<div>Deposit code: <code>' + escapeHtml(depositCode) + '</code></div>'
            + '<br>'
            + '<div>Required amount: <strong>' + escapeHtml(usdtAmount.toFixed(2)) + ' USDT</strong></div>'
            + '<br>'
            + '<div>This request expired after 5 minutes and was cancelled automatically.</div>'
            + '<div>If you already sent the payment, contact support and include the TXID.</div>'
            + '</div>';

        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.warning === 'function') {
            return new Promise(function (resolve) {
                SwalHelper.warning(html, resolve);
            });
        }

        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'warning',
                html: html,
                confirmButtonText: 'Understood'
            });
        }

        alert(
            'Binance payment request expired\n\n'
            + 'Deposit code: ' + depositCode + '\n\n'
            + 'Required amount: ' + usdtAmount.toFixed(2) + ' USDT\n\n'
            + 'This request expired after 5 minutes and was cancelled automatically.\n'
            + 'If you already sent the payment, contact support and include the TXID.'
        );
        return Promise.resolve();
    };

    BinanceDepositPage.prototype.resolveCreateEndpoint = function () {
        var byMethod = this.createByMethod && this.createByMethod[this.activeMethod];
        if (byMethod) {
            return String(byMethod);
        }
        return String(this.endpoints.create || '');
    };

    BinanceDepositPage.prototype.switchToTransferStep = function () {
        if (this.elements.stepAmount) this.elements.stepAmount.hidden = true;
        if (this.elements.stepTransfer) this.elements.stepTransfer.hidden = false;
    };

    BinanceDepositPage.prototype.stopCountdown = function () {
        if (this.state.countdownTimer) {
            clearInterval(this.state.countdownTimer);
            this.state.countdownTimer = null;
        }
    };

    BinanceDepositPage.prototype.stopPolling = function () {
        this.state.pollToken += 1;
        this.state.lastKnownStatus = '';
        this.state.pollBackoffMs = 0;
        if (this.state.pollTimer) {
            clearTimeout(this.state.pollTimer);
            this.state.pollTimer = null;
        }
    };

    BinanceDepositPage.prototype.stopAll = function () {
        this.stopCountdown();
        this.stopPolling();
    };

    BinanceDepositPage.prototype.renderCountdown = function (remainingSeconds) {
        if (!this.elements.countdown || !this.elements.countdownFill) return;

        var remainingExact = Math.max(0, Number(remainingSeconds || 0));
        var remaining = Math.max(0, Math.ceil(remainingExact));
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;
        this.elements.countdown.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

        var total = Math.max(1, this.state.currentCountdownTotal || this.ttlSeconds);
        var pct = Math.max(0, Math.min(100, (remainingExact / total) * 100));
        this.elements.countdownFill.style.width = pct + '%';
    };

    BinanceDepositPage.prototype.startCountdownByExpiry = function (expiresAtTs, totalSeconds, createdAtTs) {
        var self = this;
        this.stopCountdown();

        this.state.activeExpiresAtTs = Math.max(0, Number(expiresAtTs || 0));
        this.state.currentCountdownTotal = Math.max(1, Number(totalSeconds || this.ttlSeconds || 300));
        this.state.activeCreatedAtTs = Math.max(0, Number(createdAtTs || (this.state.activeExpiresAtTs ? (this.state.activeExpiresAtTs - this.state.currentCountdownTotal) : 0)));

        function tick() {
            var remaining = self.state.activeExpiresAtTs > 0
                ? (self.state.activeExpiresAtTs - self.getServerNowTs())
                : 0;
            self.renderCountdown(remaining);
            if (remaining <= 0) {
                self.stopCountdown();
                self.stopPolling();
                self.clearActiveDepositSnapshot();
                self.showExpiredDepositMessage().then(function () {
                    window.location.reload();
                });
            }
        }

        tick();
        this.state.countdownTimer = setInterval(tick, 250);
    };

    BinanceDepositPage.prototype.queueNextPoll = function (fn, delayMs) {
        if (this.state.pollTimer) {
            clearTimeout(this.state.pollTimer);
            this.state.pollTimer = null;
        }
        this.state.pollTimer = setTimeout(fn, Math.max(0, Number(delayMs || 0)));
    };

    BinanceDepositPage.prototype.handleDepositStatusResponse = function (res) {
        if (!res || !res.success) return false;

        this.syncServerOffset(res.server_now_ts);
        if (res.expires_at_ts) this.state.activeExpiresAtTs = Number(res.expires_at_ts) || this.state.activeExpiresAtTs;
        if (res.created_at_ts) this.state.activeCreatedAtTs = Number(res.created_at_ts) || this.state.activeCreatedAtTs;
        if (res.ttl_seconds) this.state.currentCountdownTotal = Math.max(1, Number(res.ttl_seconds));
        if (res.status) {
            this.state.lastKnownStatus = String(res.status);
            if (this.elements.tfStatus) {
                this.elements.tfStatus.textContent = resolveStatusText(res.status);
            }
        }

        this.persistActiveDepositSnapshot();

        if (res.status === 'completed') {
            this.stopAll();
            this.clearActiveDepositSnapshot();
            this.successPresenter.show(res);
            return true;
        }

        if (res.status === 'expired') {
            this.stopAll();
            this.clearActiveDepositSnapshot();
            this.showExpiredDepositMessage().then(function () {
                window.location.reload();
            });
            return true;
        }

        if (res.status === 'cancelled') {
            this.stopAll();
            this.clearActiveDepositSnapshot();
            window.location.reload();
            return true;
        }

        return true;
    };

    BinanceDepositPage.prototype.fetchStatusOnce = function (depositCode) {
        if (!depositCode || !this.endpoints.statusBase) {
            return Promise.resolve(null);
        }
        return fetch(String(this.endpoints.statusBase).replace(/\/$/, '') + '/' + encodeURIComponent(depositCode), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            return response.json();
        });
    };

    BinanceDepositPage.prototype.checkPaymentNow = function () {
        var self = this;
        if (!this.state.activeCode) {
            alertInfo('No active Binance session found.');
            return;
        }

        this.fetchStatusOnce(this.state.activeCode).then(function (res) {
            if (!res || !res.success) {
                alertInfo((res && res.message) ? res.message : 'Payment not found.');
                return;
            }

            var handled = self.handleDepositStatusResponse(res);
            if (handled && String(res.status || '') === 'pending') {
                alertInfo('Payment not found. Please try again in 10-15 seconds.');
            }
        }).catch(function () {
            alertError('Could not check payment at this time.');
        });
    };

    BinanceDepositPage.prototype.startPolling = function (depositCode) {
        var self = this;
        this.stopPolling();
        if (!depositCode || !this.endpoints.statusBase) return;

        this.state.lastKnownStatus = 'pending';
        var pollToken = this.state.pollToken;

        function runLongPollCycle() {
            if (pollToken !== self.state.pollToken) return;
            if (!self.state.activeCode || self.state.activeCode !== depositCode) return;

            var canLongPoll = !!self.endpoints.statusWaitBase;
            var requestPromise;

            if (canLongPoll) {
                var waitUrl = String(self.endpoints.statusWaitBase).replace(/\/$/, '') + '/' + encodeURIComponent(depositCode)
                    + '?since=' + encodeURIComponent(String(self.state.lastKnownStatus || ''))
                    + '&timeout=25';
                requestPromise = fetch(waitUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                }).then(function (response) {
                    return response.json();
                });
            } else {
                requestPromise = self.fetchStatusOnce(depositCode);
            }

            requestPromise.then(function (res) {
                if (pollToken !== self.state.pollToken) return;
                self.state.pollBackoffMs = 0;
                self.handleDepositStatusResponse(res);
                if (pollToken !== self.state.pollToken) return;
                self.queueNextPoll(runLongPollCycle, 100);
            }).catch(function () {
                if (pollToken !== self.state.pollToken) return;
                self.state.pollBackoffMs = self.state.pollBackoffMs > 0 ? Math.min(5000, self.state.pollBackoffMs * 2) : 1500;
                self.queueNextPoll(function () {
                    if (pollToken !== self.state.pollToken) return;
                    self.fetchStatusOnce(depositCode).then(function (res) {
                        if (pollToken !== self.state.pollToken) return;
                        self.handleDepositStatusResponse(res);
                        if (pollToken !== self.state.pollToken) return;
                        self.queueNextPoll(runLongPollCycle, 300);
                    }).catch(function () {
                        if (pollToken !== self.state.pollToken) return;
                        self.queueNextPoll(runLongPollCycle, self.state.pollBackoffMs || 2000);
                    });
                }, self.state.pollBackoffMs || 1500);
            });
        }

        runLongPollCycle();
    };

    BinanceDepositPage.prototype.createDeposit = function () {
        var self = this;
        var amount = Number(this.elements.amountInput ? this.elements.amountInput.value : 0) || 0;
        if (amount < 1) {
            alertError('Minimum deposit is $1.00');
            return;
        }

        var payerUid = String(this.elements.payerUidInput ? this.elements.payerUidInput.value : '').trim();
        if (!/^\d{4,20}$/.test(payerUid)) {
            toastError('Please enter a valid Binance UID (4-20 digits).');
            return;
        }
        this.state.payerUid = payerUid;

        var createEndpoint = this.resolveCreateEndpoint();
        if (!createEndpoint) {
            alertError('Create endpoint is missing.');
            return;
        }

        if (!this.elements.createButton) return;

        var button = this.elements.createButton;
        var originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Initializing...';

        fetch(createEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.csrfToken
            },
            credentials: 'same-origin',
            body: 'amount=' + encodeURIComponent(String(amount))
                + '&payer_uid=' + encodeURIComponent(payerUid)
                + '&csrf_token=' + encodeURIComponent(this.csrfToken)
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload = null;
                try {
                    payload = JSON.parse(text || '{}');
                } catch (e) {
                    payload = null;
                }

                if (!response.ok) {
                    var error = new Error((payload && payload.message) ? payload.message : ('Request failed with status ' + response.status + '.'));
                    error.payload = payload;
                    throw error;
                }

                return payload || {};
            });
        }).then(function (res) {
            button.disabled = false;
            button.innerHTML = originalHtml;

            if (!res || !res.success) {
                alertError((res && res.message) ? res.message : 'Could not initialize Binance session.');
                return;
            }

            var data = res.data || {};
            self.state.activeCode = String(data.deposit_code || '');
            self.state.payerUid = String(data.payer_uid || payerUid);
            self.setTransferStepData(data);
            self.switchToTransferStep();
            self.syncServerOffset(data.server_now_ts);

            var expiresAtTs = Number(data.expires_at_ts || 0);
            if (!expiresAtTs && data.expires_at) {
                var exp = new Date(String(data.expires_at).replace(' ', 'T'));
                expiresAtTs = Math.floor(exp.getTime() / 1000);
            }

            var totalTtl = Math.max(1, Number(data.ttl_seconds || self.ttlSeconds || 300));
            if (!expiresAtTs) {
                expiresAtTs = self.getServerNowTs() + totalTtl;
            }

            self.state.activeCreatedAtTs = expiresAtTs - totalTtl;
            self.state.activeExpiresAtTs = expiresAtTs;
            self.state.currentCountdownTotal = totalTtl;

            self.persistActiveDepositSnapshot({
                usd_amount: Number(data.usd_amount || data.usdt_amount || 0),
                payer_uid: self.state.payerUid,
                deposit_code: self.state.activeCode
            });

            self.startCountdownByExpiry(expiresAtTs, totalTtl, self.state.activeCreatedAtTs);
            self.startPolling(self.state.activeCode);
        }).catch(function (error) {
            button.disabled = false;
            button.innerHTML = originalHtml;
            alertError((error && error.message) ? error.message : 'Could not connect to server.');
        });
    };

    BinanceDepositPage.prototype.cancelDeposit = function () {
        var self = this;
        if (!this.state.activeCode) return;

        confirmAction({
            title: 'Cancel Transaction?',
            text: 'Are you sure you want to cancel this deposit?',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then(function (confirmed) {
            if (!confirmed) return;

            fetch(self.endpoints.cancel, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': self.csrfToken
                },
                credentials: 'same-origin',
                body: 'deposit_code=' + encodeURIComponent(self.state.activeCode)
                    + '&csrf_token=' + encodeURIComponent(self.csrfToken)
            }).then(function (response) {
                return response.json();
            }).then(function (res) {
                if (res && res.success) {
                    self.stopAll();
                    self.clearActiveDepositSnapshot();
                    window.location.reload();
                    return;
                }
                alertError((res && res.message) ? res.message : 'Could not cancel session.');
            }).catch(function () {
                alertError('Could not connect to server.');
            });
        });
    };

    BinanceDepositPage.prototype.bindEvents = function () {
        var self = this;

        if (this.elements.amountInput) {
            this.elements.amountInput.addEventListener('input', function () {
                self.state.usdAmount = Number(this.value || 0) || 0;
                self.updatePreview();
            });
        }

        if (this.elements.payerUidInput) {
            this.elements.payerUidInput.addEventListener('input', function () {
                self.state.payerUid = String(this.value || '').trim();
            });
        }

        this.elements.quickButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var amount = Number(button.getAttribute('data-amount') || 0);
                self.state.usdAmount = amount;
                if (self.elements.amountInput) {
                    self.elements.amountInput.value = String(amount);
                }
                self.updatePreview();
            });
        });

        if (this.elements.createButton) {
            this.elements.createButton.addEventListener('click', function () {
                self.createDeposit();
            });
        }

        if (this.elements.checkButton) {
            this.elements.checkButton.addEventListener('click', function () {
                self.checkPaymentNow();
            });
        }

        if (this.elements.cancelButton) {
            this.elements.cancelButton.addEventListener('click', function () {
                self.cancelDeposit();
            });
        }

        this.root.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-copy-target');
                var text = '';
                if (target === 'uid' && self.elements.tfBinanceUid) text = self.elements.tfBinanceUid.textContent.trim();
                if (target === 'usdt' && self.elements.tfUsdt) text = self.elements.tfUsdt.textContent.replace(/^\$/, '').replace(/\s*USDT$/i, '').trim();
                if (target === 'code' && self.elements.tfCode) text = self.elements.tfCode.textContent.trim();
                copyText(text);
            });
        });

        this.elements.methodButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var methodCode = String(button.getAttribute('data-method-code') || '');
                var methodEnabled = String(button.getAttribute('data-method-enabled') || '0') === '1';
                if (!methodEnabled) {
                    alertInfo('This payment method is not enabled in admin settings.');
                    return;
                }
                var targetUrl = self.methodRoutes[methodCode];
                if (!targetUrl || window.location.href === targetUrl) {
                    return;
                }
                window.location.href = targetUrl;
            });
        });
    };

    BinanceDepositPage.prototype.resumeActiveDeposit = function (activeDeposit) {
        this.syncServerOffset(activeDeposit.server_now_ts || this.config.serverNowTs);
        this.state.activeCode = String(activeDeposit.deposit_code || '');
        this.state.payerUid = String(activeDeposit.payer_uid || this.state.payerUid || '');
        this.state.activeCreatedAtTs = Number(activeDeposit.created_at_ts || 0);
        this.state.currentCountdownTotal = Math.max(1, Number(activeDeposit.ttl_seconds || this.ttlSeconds || 300));
        this.state.activeExpiresAtTs = Number(activeDeposit.expires_at_ts || 0);

        this.setTransferStepData(activeDeposit);
        this.switchToTransferStep();
        this.persistActiveDepositSnapshot();

        this.startCountdownByExpiry(
            this.state.activeExpiresAtTs || (this.getServerNowTs() + this.state.currentCountdownTotal),
            this.state.currentCountdownTotal,
            this.state.activeCreatedAtTs || (this.state.activeExpiresAtTs ? (this.state.activeExpiresAtTs - this.state.currentCountdownTotal) : 0)
        );
        this.startPolling(this.state.activeCode);
    };

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('[data-balance-bank-root]');
        if (!root) return;
        var config = parseJsonConfig('balance-bank-config');
        if (!config || String(config.activeMethod || '') !== 'binance') return;
        new BinanceDepositPage(root, config);
    });
})();

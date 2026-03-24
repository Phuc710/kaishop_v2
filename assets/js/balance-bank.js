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

    function readCsrfToken(fallback) {
        var token = '';
        try {
            token = String(window.KS_CSRF_TOKEN || '').trim();
        } catch (e) {
            token = '';
        }

        if (!token) {
            var meta = document.querySelector('meta[name="csrf-token"]');
            token = meta ? String(meta.getAttribute('content') || '').trim() : '';
        }

        return token || String(fallback || '');
    }

    function formatVnd(value) {
        var num = Number(value || 0);
        if (!Number.isFinite(num)) num = 0;
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + 'đ';
    }

    function resolveStatusText(status) {
        var normalized = String(status || '').toLowerCase();
        if (normalized === 'completed') return 'Đã hoàn tất';
        if (normalized === 'expired') return 'Đã hết hạn';
        if (normalized === 'cancelled') return 'Đã hủy';
        return 'Đang chờ xử lý';
    }

    function toastCopySuccess(text) {
        if (!text) return;
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.toast === 'function') {
            SwalHelper.toast('Đã sao chép: ' + text, 'success');
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Đã sao chép: ' + text,
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
            Swal.fire({ icon: 'error', title: 'Lỗi', text: text });
        } else {
            alert(text);
        }
    }

    function alertInfo(text) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.info === 'function') {
            SwalHelper.info(text);
            return Promise.resolve();
        }
        if (typeof Swal !== 'undefined') {
            return Swal.fire({ icon: 'info', title: 'Thông báo', text: text });
        }
        alert(text);
        return Promise.resolve();
    }

    function confirmAction(options) {
        if (typeof SwalHelper !== 'undefined' && typeof SwalHelper.confirm === 'function') {
            return SwalHelper.confirm(options.title || 'Xác nhận', options.text || 'Bạn có chắc chắn?');
        }
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'warning',
                title: options.title || 'Xác nhận',
                text: options.text || 'Bạn có chắc chắn?',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Đồng ý',
                cancelButtonText: options.cancelText || 'Hủy',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then(function (result) {
                return !!result.isConfirmed;
            });
        }
        return Promise.resolve(window.confirm(options.text || 'Xác nhận?'));
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

    function buildQrUrl(bankShortName, bankAccount, amount, content, owner) {
        if (!bankAccount) return '';
        return 'https://img.vietqr.io/image/' + encodeURIComponent(bankShortName || '') + '-' + encodeURIComponent(bankAccount)
            + '-qr_only.png?amount=' + encodeURIComponent(String(amount || 0))
            + '&addInfo=' + encodeURIComponent(content || '')
            + '&accountName=' + encodeURIComponent(owner || '');
    }

    function parseApiResponse(response) {
        return response.text().then(function (text) {
            var payload = null;
            try {
                payload = JSON.parse(text || '{}');
            } catch (e) {
                payload = null;
            }

            if (!response.ok) {
                var message = (payload && payload.message)
                    ? payload.message
                    : ((response.status === 403)
                        ? 'Phiên làm việc hết hạn, vui lòng tải lại trang.'
                        : ('Yêu cầu thất bại với mã ' + response.status + '.'));
                var error = new Error(message);
                error.payload = payload;
                error.status = response.status;
                throw error;
            }

            return payload || {};
        });
    }

    function BankDepositPage(root, config) {
        this.root = root;
        this.config = config || {};
        this.activeMethod = String(this.config.activeMethod || '');
        if (this.activeMethod !== 'bank_sepay') {
            return;
        }

        this.endpoints = this.config.endpoints || {};
        this.createByMethod = (this.endpoints && this.endpoints.createByMethod) ? this.endpoints.createByMethod : {};
        this.methodRoutes = this.config.methodRoutes || {};
        this.bonusTiers = Array.isArray(this.config.bonusTiers) ? this.config.bonusTiers.slice() : [];
        this.ttlSeconds = Number(this.config.ttlSeconds || 300);
        this.bankConfig = this.config.bank || {};
        this.csrfToken = String(this.config.csrfToken || '');
        this.storageKey = String(this.config.storageKey || 'ks_balance_bank');

        var activeDeposit = this.config.activeDeposit || null;
        var initialServerNowTs = Number(this.config.serverNowTs || (activeDeposit && activeDeposit.server_now_ts) || 0);

        this.successPresenter = (window.KaiBalanceSuccess && typeof window.KaiBalanceSuccess.createPresenter === 'function')
            ? window.KaiBalanceSuccess.createPresenter({
                endpoints: this.endpoints,
                methodRoutes: this.methodRoutes,
                activeMethod: this.activeMethod,
                binanceRateVnd: Number((this.config.binance && this.config.binance.rateVnd) || 25000)
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
            quickButtons: Array.prototype.slice.call(root.querySelectorAll('[data-deposit-quick]')),
            preview: root.querySelector('[data-deposit-preview]'),
            previewAmount: root.querySelector('[data-preview-amount]'),
            previewBonusRow: root.querySelector('[data-preview-bonus-row]'),
            previewBonus: root.querySelector('[data-preview-bonus]'),
            previewTotal: root.querySelector('[data-preview-total]'),
            createButton: root.querySelector('[data-deposit-action="create"]'),
            cancelButton: root.querySelector('[data-deposit-action="cancel"]'),
            downloadQrButton: root.querySelector('[data-deposit-action="download-qr"]'),
            qr: root.querySelector('[data-deposit-qr]'),
            tfBank: root.querySelector('[data-tf-bank]'),
            tfOwner: root.querySelector('[data-tf-owner]'),
            tfAccount: root.querySelector('[data-tf-account]'),
            tfContent: root.querySelector('[data-tf-content]'),
            tfStatus: root.querySelector('[data-tf-status]'),
            tfAmount: root.querySelector('[data-tf-amount]'),
            countdown: root.querySelector('[data-deposit-countdown]'),
            countdownFill: root.querySelector('[data-deposit-countdown-fill]'),
            methodButtons: Array.prototype.slice.call(root.querySelectorAll('[data-method-code]'))
        };

        this.state = {
            amount: Number(this.elements.amountInput ? this.elements.amountInput.value : 10000) || 10000,
            bonusPercent: 0,
            activeCode: activeDeposit && activeDeposit.deposit_code ? String(activeDeposit.deposit_code) : '',
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

        this.bindEvents();
        this.updatePreview();

        if (activeDeposit && activeDeposit.deposit_code) {
            this.resumeActiveDeposit(activeDeposit);
        } else {
            this.clearActiveDepositSnapshot();
        }
    }

    BankDepositPage.prototype.storageAvailable = function () {
        try {
            return !!window.localStorage;
        } catch (e) {
            return false;
        }
    };

    BankDepositPage.prototype.syncServerOffset = function (serverNowTs) {
        var ts = Number(serverNowTs || 0);
        if (!Number.isFinite(ts) || ts <= 0) return;
        this.state.serverOffsetMs = (ts * 1000) - Date.now();
    };

    BankDepositPage.prototype.getServerNowTs = function () {
        return Math.floor((Date.now() + Number(this.state.serverOffsetMs || 0)) / 1000);
    };

    BankDepositPage.prototype.persistActiveDepositSnapshot = function (extra) {
        if (!this.storageKey || !this.storageAvailable()) return;
        var payload = Object.assign({
            method: this.activeMethod,
            deposit_code: this.state.activeCode || '',
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

    BankDepositPage.prototype.clearActiveDepositSnapshot = function () {
        if (!this.storageKey || !this.storageAvailable()) return;
        try {
            window.localStorage.removeItem(this.storageKey);
        } catch (e) {
        }
    };

    BankDepositPage.prototype.resolveBonusPercent = function (amount) {
        var sorted = this.bonusTiers.slice().sort(function (a, b) {
            return Number(b.amount || 0) - Number(a.amount || 0);
        });
        for (var i = 0; i < sorted.length; i++) {
            var tierAmount = Number(sorted[i].amount || 0);
            var tierPercent = Number(sorted[i].percent || 0);
            if (tierAmount > 0 && amount >= tierAmount) {
                return tierPercent;
            }
        }
        return 0;
    };

    BankDepositPage.prototype.selectQuickButtonByAmount = function (amount) {
        this.elements.quickButtons.forEach(function (btn) {
            var btnAmount = Number(btn.getAttribute('data-amount') || 0);
            btn.classList.toggle('active', Math.abs(btnAmount - amount) < 0.00001);
        });
    };

    BankDepositPage.prototype.updatePreview = function () {
        this.state.bonusPercent = this.resolveBonusPercent(this.state.amount);
        this.selectQuickButtonByAmount(this.state.amount);

        if (!this.elements.preview || !this.elements.previewAmount || !this.elements.previewTotal) return;
        if (this.state.amount <= 0) {
            this.elements.preview.hidden = true;
            return;
        }

        this.elements.preview.hidden = false;
        this.elements.previewAmount.textContent = formatVnd(this.state.amount);

        var bonusAmount = Math.floor(this.state.amount * this.state.bonusPercent / 100);
        if (this.elements.previewBonusRow && this.elements.previewBonus) {
            if (this.state.bonusPercent > 0) {
                this.elements.previewBonusRow.hidden = false;
                this.elements.previewBonus.textContent = '+' + formatVnd(bonusAmount) + ' (' + this.state.bonusPercent + '%)';
            } else {
                this.elements.previewBonusRow.hidden = true;
            }
        }

        this.elements.previewTotal.textContent = formatVnd(this.state.amount + bonusAmount);
    };

    BankDepositPage.prototype.setTransferStepData = function (data) {
        if (!data) return;
        if (this.elements.tfBank) this.elements.tfBank.textContent = data.bank_name || this.bankConfig.name || '';
        if (this.elements.tfOwner) this.elements.tfOwner.textContent = data.bank_owner || this.bankConfig.owner || '';
        if (this.elements.tfAccount) this.elements.tfAccount.textContent = data.bank_account || this.bankConfig.account || '';
        if (this.elements.tfContent) this.elements.tfContent.textContent = String(data.deposit_code || '');
        if (this.elements.tfStatus) this.elements.tfStatus.textContent = data.status_text || resolveStatusText(data.status);
        if (this.elements.tfAmount) this.elements.tfAmount.textContent = formatVnd(Number(data.amount || 0));

        if (this.elements.qr) {
            var qrUrl = data.qr_url || buildQrUrl(
                data.bank_short_name || this.bankConfig.shortName,
                data.bank_account || this.bankConfig.account,
                Number(data.amount || 0),
                data.deposit_code || '',
                data.bank_owner || this.bankConfig.owner
            );
            if (qrUrl) this.elements.qr.src = qrUrl;
        }
    };

    BankDepositPage.prototype.resolveCreateEndpoint = function () {
        var byMethod = this.createByMethod && this.createByMethod[this.activeMethod];
        if (byMethod) {
            return String(byMethod);
        }
        return String(this.endpoints.create || '');
    };

    BankDepositPage.prototype.switchToTransferStep = function () {
        if (this.elements.stepAmount) this.elements.stepAmount.hidden = true;
        if (this.elements.stepTransfer) this.elements.stepTransfer.hidden = false;
    };

    BankDepositPage.prototype.stopCountdown = function () {
        if (this.state.countdownTimer) {
            clearInterval(this.state.countdownTimer);
            this.state.countdownTimer = null;
        }
    };

    BankDepositPage.prototype.stopPolling = function () {
        this.state.pollToken += 1;
        this.state.lastKnownStatus = '';
        this.state.pollBackoffMs = 0;
        if (this.state.pollTimer) {
            clearTimeout(this.state.pollTimer);
            this.state.pollTimer = null;
        }
    };

    BankDepositPage.prototype.stopAll = function () {
        this.stopCountdown();
        this.stopPolling();
    };

    BankDepositPage.prototype.renderCountdown = function (remainingSeconds) {
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

    BankDepositPage.prototype.startCountdownByExpiry = function (expiresAtTs, totalSeconds, createdAtTs) {
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
                alertInfo('Giao dịch đã hết thời gian. Vui lòng tạo giao dịch mới.').then(function () {
                    window.location.href = window.location.pathname;
                });
            }
        }

        tick();
        this.state.countdownTimer = setInterval(tick, 250);
    };

    BankDepositPage.prototype.queueNextPoll = function (fn, delayMs) {
        if (this.state.pollTimer) {
            clearTimeout(this.state.pollTimer);
            this.state.pollTimer = null;
        }
        this.state.pollTimer = setTimeout(fn, Math.max(0, Number(delayMs || 0)));
    };

    BankDepositPage.prototype.handleDepositStatusResponse = function (res) {
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
            alertInfo('Giao dịch đã hết thời gian.').then(function () {
                window.location.href = window.location.pathname;
            });
            return true;
        }

        if (res.status === 'cancelled') {
            this.stopAll();
            this.clearActiveDepositSnapshot();
            window.location.href = window.location.pathname;
            return true;
        }

        return true;
    };

    BankDepositPage.prototype.fetchStatusOnce = function (depositCode) {
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

    BankDepositPage.prototype.startPolling = function (depositCode) {
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

    BankDepositPage.prototype.createDeposit = function () {
        var self = this;
        var amount = Number(this.elements.amountInput ? this.elements.amountInput.value : 0) || 0;
        var csrfToken = readCsrfToken(this.csrfToken);
        this.csrfToken = csrfToken;
        if (amount < 10000) {
            alertError('Số tiền nạp tối thiểu là ' + formatVnd(10000));
            return;
        }

        var createEndpoint = this.resolveCreateEndpoint();
        if (!createEndpoint) {
            alertError('Không tìm thấy endpoint tạo giao dịch');
            return;
        }

        if (!this.elements.createButton) return;

        var button = this.elements.createButton;
        var originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo...';

        fetch(createEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin',
            body: 'amount=' + encodeURIComponent(String(amount))
                + '&csrf_token=' + encodeURIComponent(csrfToken)
        }).then(function (response) {
            return parseApiResponse(response);
        }).then(function (res) {
            button.disabled = false;
            button.innerHTML = originalHtml;

            if (!res || !res.success) {
                alertError((res && res.message) ? res.message : 'Không thể tạo giao dịch');
                return;
            }

            var data = res.data || {};
            self.state.activeCode = String(data.deposit_code || '');
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
                amount: Number(data.amount || 0),
                bank_name: data.bank_name || '',
                bank_owner: data.bank_owner || '',
                bank_account: data.bank_account || '',
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

    BankDepositPage.prototype.cancelDeposit = function () {
        var self = this;
        if (!this.state.activeCode) return;

        confirmAction({
            title: 'Hủy giao dịch?',
            text: 'Bạn có chắc chắn muốn hủy giao dịch nạp tiền này không?',
            confirmText: 'Đồng ý',
            cancelText: 'Không'
        }).then(function (confirmed) {
            if (!confirmed) return;

            var csrfToken = readCsrfToken(self.csrfToken);
            self.csrfToken = csrfToken;

            fetch(self.endpoints.cancel, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: 'deposit_code=' + encodeURIComponent(self.state.activeCode)
                    + '&csrf_token=' + encodeURIComponent(csrfToken)
            }).then(function (response) {
                return parseApiResponse(response);
            }).then(function (res) {
                if (res && res.success) {
                    self.stopAll();
                    self.clearActiveDepositSnapshot();
                    window.location.href = window.location.pathname;
                    return;
                }
                alertError((res && res.message) ? res.message : 'Không thể hủy giao dịch');
            }).catch(function (error) {
                alertError((error && error.message) ? error.message : 'Could not connect to server.');
            });
        });
    };

    BankDepositPage.prototype.downloadQr = function () {
        if (!this.elements.qr || !this.elements.qr.src) {
            alertError('Chưa có mã QR để tải');
            return;
        }

        var self = this;
        fetch(this.elements.qr.src).then(function (response) {
            return response.blob();
        }).then(function (blob) {
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'QR_ThanhToan_' + (self.state.activeCode || 'deposit') + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }).catch(function () {
            alertError('Không thể tải QR');
        });
    };

    BankDepositPage.prototype.bindEvents = function () {
        var self = this;

        if (this.elements.amountInput) {
            this.elements.amountInput.addEventListener('input', function () {
                self.state.amount = Number(this.value || 0) || 0;
                self.updatePreview();
            });
        }

        this.elements.quickButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var amount = Number(button.getAttribute('data-amount') || 0);
                self.state.amount = amount;
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

        if (this.elements.cancelButton) {
            this.elements.cancelButton.addEventListener('click', function () {
                self.cancelDeposit();
            });
        }

        if (this.elements.downloadQrButton) {
            this.elements.downloadQrButton.addEventListener('click', function () {
                self.downloadQr();
            });
        }

        this.root.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-copy-target');
                var text = '';
                if (target === 'account' && self.elements.tfAccount) text = self.elements.tfAccount.textContent.trim();
                if (target === 'content' && self.elements.tfContent) text = self.elements.tfContent.textContent.trim();
                if (target === 'amount' && self.elements.tfAmount) text = self.elements.tfAmount.textContent.replace(/[^\d]/g, '').trim();
                copyText(text);
            });
        });

        this.elements.methodButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var methodCode = String(button.getAttribute('data-method-code') || '');
                var methodEnabled = String(button.getAttribute('data-method-enabled') || '0') === '1';
                if (!methodEnabled) {
                    alertInfo('Phương thức này chưa được bật trong cài đặt quản trị.');
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

    BankDepositPage.prototype.resumeActiveDeposit = function (activeDeposit) {
        this.syncServerOffset(activeDeposit.server_now_ts || this.config.serverNowTs);
        this.state.activeCode = String(activeDeposit.deposit_code || '');
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
        if (!config || String(config.activeMethod || '') !== 'bank_sepay') return;
        new BankDepositPage(root, config);
    });
})();

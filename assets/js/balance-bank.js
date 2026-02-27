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

    function formatVnd(n) {
        var num = Number(n || 0);
        if (!Number.isFinite(num)) num = 0;
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function resolveDepositStatusText(status) {
        var normalized = String(status || '').toLowerCase();
        if (normalized === 'completed') return 'Đã hoàn tất';
        if (normalized === 'expired') return 'Đã hết hạn';
        if (normalized === 'cancelled') return 'Đã hủy';
        return 'Đang chờ xử lý';
    }

    function toastCopySuccess(text) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã sao chép: ' + text, showConfirmButton: false, timer: 1500 });
        }
    }

    function alertError(text) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: text });
        } else {
            alert(text);
        }
    }

    function alertSuccess(text) {
        if (window.Swal) {
            Swal.fire({ icon: 'success', title: 'Thành công', text: text });
        } else {
            alert(text);
        }
    }

    function alertInfo(text) {
        if (window.Swal) {
            Swal.fire({ icon: 'info', title: 'Thông báo', text: text });
        } else {
            alert(text);
        }
    }

    function confirmAction(options) {
        if (!window.Swal) {
            return Promise.resolve(window.confirm(options.text || 'Xác nhận?'));
        }
        return Swal.fire({
            icon: 'warning',
            title: options.title || 'Xác nhận',
            text: options.text || 'Bạn có chắc chắn?',
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Đồng ý',
            cancelButtonText: options.cancelText || 'Huỷ',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then(function (result) { return !!result.isConfirmed; });
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

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) { }
        document.body.removeChild(ta);
        toastCopySuccess(text);
    }

    function buildQrUrl(bankShortName, bankAccount, amount, content, owner) {
        if (!bankAccount) return '';
        return 'https://img.vietqr.io/image/' + encodeURIComponent(bankShortName || '') + '-' + encodeURIComponent(bankAccount) +
            '-qr_only.png?amount=' + encodeURIComponent(String(amount || 0)) +
            '&addInfo=' + encodeURIComponent(content || '') +
            '&accountName=' + encodeURIComponent(owner || '');
    }

    function initBankDeposit(root, config) {
        if (!root || !config) return;

        var endpoints = config.endpoints || {};
        var bonusTiers = Array.isArray(config.bonusTiers) ? config.bonusTiers.slice() : [];
        var ttlSeconds = Number(config.ttlSeconds || 300);
        var bankConfig = config.bank || {};
        var activeDeposit = config.activeDeposit || null;
        var methodRoutes = config.methodRoutes || {};
        var csrfToken = String(config.csrfToken || '');
        var storageKey = String(config.storageKey || 'ks_balance_bank');
        var initialServerNowTs = Number(config.serverNowTs || (activeDeposit && activeDeposit.server_now_ts) || 0);
        var successPresenter = (window.KaiBalanceSuccess && typeof window.KaiBalanceSuccess.createPresenter === 'function')
            ? window.KaiBalanceSuccess.createPresenter({
                endpoints: endpoints,
                methodRoutes: methodRoutes,
                activeMethod: config.activeMethod
            })
            : {
                show: function (res) {
                    var redirectUrl = endpoints.profile || (window.location.origin + '/profile');
                    alertSuccess('Nạp tiền thành công. Số dư: ' + formatVnd(Number(res && res.new_balance || 0)) + 'đ');
                    window.location.href = redirectUrl;
                }
            };

        var elements = {
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
            countdownWrap: root.querySelector('[data-deposit-countdown-wrap]'),
            countdown: root.querySelector('[data-deposit-countdown]'),
            countdownFill: root.querySelector('[data-deposit-countdown-fill]'),
            methodButtons: Array.prototype.slice.call(root.querySelectorAll('[data-method-code]'))
        };

        var state = {
            amount: Number(elements.amountInput ? elements.amountInput.value : 10000) || 10000,
            bonusPercent: 0,
            activeCode: activeDeposit && activeDeposit.deposit_code ? String(activeDeposit.deposit_code) : '',
            activeCreatedAtTs: activeDeposit && activeDeposit.created_at_ts ? Number(activeDeposit.created_at_ts) : 0,
            activeExpiresAtTs: activeDeposit && activeDeposit.expires_at_ts ? Number(activeDeposit.expires_at_ts) : 0,
            countdownTimer: null,
            pollTimer: null,
            pollToken: 0,
            lastKnownStatus: activeDeposit && activeDeposit.deposit_code ? 'pending' : '',
            pollBackoffMs: 0,
            currentCountdownTotal: Math.max(1, Number((activeDeposit && activeDeposit.ttl_seconds) || ttlSeconds || 300)),
            serverOffsetMs: (Number.isFinite(initialServerNowTs) && initialServerNowTs > 0) ? ((initialServerNowTs * 1000) - Date.now()) : 0
        };

        function storageAvailable() {
            try {
                return !!window.localStorage;
            } catch (e) {
                return false;
            }
        }

        function syncServerOffset(serverNowTs) {
            var ts = Number(serverNowTs || 0);
            if (!Number.isFinite(ts) || ts <= 0) return;
            state.serverOffsetMs = (ts * 1000) - Date.now();
        }

        function getServerNowTs() {
            return Math.floor((Date.now() + Number(state.serverOffsetMs || 0)) / 1000);
        }

        function persistActiveDepositSnapshot(extra) {
            if (!storageKey || !storageAvailable()) return;
            var payload = Object.assign({
                deposit_code: state.activeCode || '',
                created_at_ts: Number(state.activeCreatedAtTs || 0),
                expires_at_ts: Number(state.activeExpiresAtTs || 0),
                ttl_seconds: Number(state.currentCountdownTotal || ttlSeconds || 300),
                server_now_ts: getServerNowTs()
            }, extra || {});
            try {
                window.localStorage.setItem(storageKey, JSON.stringify(payload));
            } catch (e) { }
        }

        function clearActiveDepositSnapshot() {
            if (!storageKey || !storageAvailable()) return;
            try {
                window.localStorage.removeItem(storageKey);
            } catch (e) { }
        }

        function resolveBonusPercent(amount) {
            var sorted = bonusTiers.slice().sort(function (a, b) { return Number(b.amount || 0) - Number(a.amount || 0); });
            var pct = 0;
            for (var i = 0; i < sorted.length; i++) {
                var tierAmount = Number(sorted[i].amount || 0);
                var tierPercent = Number(sorted[i].percent || 0);
                if (tierAmount > 0 && amount >= tierAmount) {
                    pct = tierPercent;
                    break;
                }
            }
            return pct;
        }

        function selectQuickButtonByAmount(amount) {
            elements.quickButtons.forEach(function (btn) {
                var btnAmount = Number(btn.getAttribute('data-amount') || 0);
                btn.classList.toggle('active', btnAmount === amount);
            });
        }

        function updatePreview() {
            state.bonusPercent = resolveBonusPercent(state.amount);
            selectQuickButtonByAmount(state.amount);

            if (!elements.preview || !elements.previewAmount || !elements.previewTotal) return;
            if (state.amount < 10000) {
                elements.preview.hidden = true;
                return;
            }

            var bonusAmount = Math.floor(state.amount * state.bonusPercent / 100);
            elements.preview.hidden = false;
            elements.previewAmount.textContent = formatVnd(state.amount) + 'đ';
            if (elements.previewBonusRow && elements.previewBonus) {
                if (state.bonusPercent > 0) {
                    elements.previewBonusRow.hidden = false;
                    elements.previewBonus.textContent = '+' + formatVnd(bonusAmount) + 'đ (' + state.bonusPercent + '%)';
                } else {
                    elements.previewBonusRow.hidden = true;
                }
            }
            elements.previewTotal.textContent = formatVnd(state.amount + bonusAmount) + 'đ';
        }

        function setTransferStepData(data) {
            if (elements.tfBank) elements.tfBank.textContent = data.bank_name || bankConfig.name || '';
            if (elements.tfOwner) elements.tfOwner.textContent = data.bank_owner || bankConfig.owner || '';
            if (elements.tfAccount) elements.tfAccount.textContent = data.bank_account || bankConfig.account || '';
            if (elements.tfContent) elements.tfContent.textContent = data.deposit_code || '';
            if (elements.tfStatus) elements.tfStatus.textContent = data.status_text || resolveDepositStatusText(data.status);
            if (elements.tfAmount) elements.tfAmount.textContent = formatVnd(Number(data.amount || 0)) + 'đ';
            if (elements.qr) {
                var qrUrl = data.qr_url || buildQrUrl(data.bank_short_name || bankConfig.shortName, data.bank_account || bankConfig.account, Number(data.amount || 0), data.deposit_code || '', data.bank_owner || bankConfig.owner);
                if (qrUrl) elements.qr.src = qrUrl;
            }
        }

        function switchToTransferStep() {
            if (elements.stepAmount) elements.stepAmount.hidden = true;
            if (elements.stepTransfer) elements.stepTransfer.hidden = false;
        }

        function switchToAmountStep() {
            if (elements.stepTransfer) elements.stepTransfer.hidden = true;
            if (elements.stepAmount) elements.stepAmount.hidden = false;
        }

        function stopCountdown() {
            if (state.countdownTimer) {
                clearInterval(state.countdownTimer);
                state.countdownTimer = null;
            }
        }

        function stopPolling() {
            state.pollToken += 1;
            state.lastKnownStatus = '';
            state.pollBackoffMs = 0;
            if (state.pollTimer) {
                clearTimeout(state.pollTimer);
                state.pollTimer = null;
            }
        }

        function stopAll() {
            stopCountdown();
            stopPolling();
        }

        function renderCountdown(remainingSeconds) {
            if (!elements.countdown || !elements.countdownFill) return;
            var remainingExact = Math.max(0, Number(remainingSeconds || 0));
            var remaining = Math.max(0, Math.ceil(remainingExact));
            var mins = Math.floor(remaining / 60);
            var secs = remaining % 60;
            elements.countdown.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            var total = Math.max(1, state.currentCountdownTotal || ttlSeconds);
            var pct = Math.max(0, Math.min(100, (remainingExact / total) * 100));
            elements.countdownFill.style.width = pct + '%';
        }

        function startCountdownByExpiry(expiresAtTs, totalSeconds, createdAtTs) {
            stopCountdown();
            state.activeExpiresAtTs = Math.max(0, Number(expiresAtTs || 0));
            state.currentCountdownTotal = Math.max(1, Number(totalSeconds || ttlSeconds || 300));
            state.activeCreatedAtTs = Math.max(0, Number(createdAtTs || (state.activeExpiresAtTs ? (state.activeExpiresAtTs - state.currentCountdownTotal) : 0)));

            function tick() {
                var remaining = state.activeExpiresAtTs > 0
                    ? (state.activeExpiresAtTs - getServerNowTs())
                    : 0;
                renderCountdown(remaining);
                if (remaining <= 0) {
                    stopCountdown();
                    stopPolling();
                    clearActiveDepositSnapshot();
                    alertInfo('Giao dịch đã hết thời gian. Vui lòng tạo giao dịch mới.');
                    window.location.reload();
                }
            }

            tick();
            state.countdownTimer = setInterval(function () {
                tick();
            }, 250);
        }

        function queueNextPoll(fn, delayMs) {
            if (state.pollTimer) {
                clearTimeout(state.pollTimer);
                state.pollTimer = null;
            }
            state.pollTimer = setTimeout(fn, Math.max(0, Number(delayMs || 0)));
        }

        function handleDepositStatusResponse(res) {
            if (!res || !res.success) return false;

            syncServerOffset(res.server_now_ts);
            if (res.expires_at_ts) {
                state.activeExpiresAtTs = Number(res.expires_at_ts) || state.activeExpiresAtTs;
            }
            if (res.created_at_ts) {
                state.activeCreatedAtTs = Number(res.created_at_ts) || state.activeCreatedAtTs;
            }
            if (res.ttl_seconds) {
                state.currentCountdownTotal = Math.max(1, Number(res.ttl_seconds));
            }
            if (res.status) {
                state.lastKnownStatus = String(res.status);
                if (elements.tfStatus) {
                    elements.tfStatus.textContent = resolveDepositStatusText(res.status);
                }
            }
            persistActiveDepositSnapshot();

            if (res.status === 'completed') {
                stopAll();
                clearActiveDepositSnapshot();
                successPresenter.show(res);
                return true;
            }

            if (res.status === 'expired' || res.status === 'cancelled') {
                stopAll();
                clearActiveDepositSnapshot();
                window.location.reload();
                return true;
            }

            return true;
        }

        function fetchStatusOnce(depositCode) {
            if (!depositCode || !endpoints.statusBase) {
                return Promise.resolve(null);
            }
            return fetch(String(endpoints.statusBase).replace(/\/$/, '') + '/' + encodeURIComponent(depositCode), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (r) { return r.json(); });
        }

        function startPolling(depositCode) {
            stopPolling();
            if (!depositCode || !endpoints.statusBase) return;
            state.lastKnownStatus = 'pending';
            var pollToken = state.pollToken;

            function runLongPollCycle() {
                if (pollToken !== state.pollToken) return;
                if (!state.activeCode || state.activeCode !== depositCode) return;

                var canLongPoll = !!endpoints.statusWaitBase;
                var requestPromise;

                if (canLongPoll) {
                    var waitUrl = String(endpoints.statusWaitBase).replace(/\/$/, '') + '/' + encodeURIComponent(depositCode)
                        + '?since=' + encodeURIComponent(String(state.lastKnownStatus || ''))
                        + '&timeout=25';
                    requestPromise = fetch(waitUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    }).then(function (r) { return r.json(); });
                } else {
                    requestPromise = fetchStatusOnce(depositCode);
                }

                requestPromise
                    .then(function (res) {
                        if (pollToken !== state.pollToken) return;
                        state.pollBackoffMs = 0;
                        handleDepositStatusResponse(res);
                        if (pollToken !== state.pollToken) return;
                        queueNextPoll(runLongPollCycle, 100);
                    })
                    .catch(function () {
                        if (pollToken !== state.pollToken) return;
                        // Fallback to normal polling cadence on transient errors
                        state.pollBackoffMs = state.pollBackoffMs > 0 ? Math.min(5000, state.pollBackoffMs * 2) : 1500;
                        queueNextPoll(function () {
                            if (pollToken !== state.pollToken) return;
                            fetchStatusOnce(depositCode)
                                .then(function (res) {
                                    if (pollToken !== state.pollToken) return;
                                    handleDepositStatusResponse(res);
                                    if (pollToken !== state.pollToken) return;
                                    queueNextPoll(runLongPollCycle, 300);
                                })
                                .catch(function () {
                                    if (pollToken !== state.pollToken) return;
                                    queueNextPoll(runLongPollCycle, state.pollBackoffMs || 2000);
                                });
                        }, state.pollBackoffMs || 1500);
                    });
            }

            runLongPollCycle();
        }

        function createDeposit() {
            var amount = Number(elements.amountInput ? elements.amountInput.value : 0) || 0;
            if (amount < 10000) {
                alertError('Số tiền nạp tối thiểu 10.000đ');
                return;
            }

            if (!elements.createButton) return;
            var btn = elements.createButton;
            var originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo...';

            fetch(endpoints.create, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: 'amount=' + encodeURIComponent(String(amount)) + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (!res || !res.success) {
                        alertError((res && res.message) ? res.message : 'Không thể tạo giao dịch');
                        return;
                    }
                    var data = res.data || {};
                    state.activeCode = String(data.deposit_code || '');
                    setTransferStepData(data);
                    switchToTransferStep();
                    syncServerOffset(data.server_now_ts);

                    var expiresAtTs = Number(data.expires_at_ts || 0);
                    if (!expiresAtTs && data.expires_at) {
                        var exp = new Date(String(data.expires_at).replace(' ', 'T'));
                        expiresAtTs = Math.floor(exp.getTime() / 1000);
                    }
                    var totalTtl = Math.max(1, Number(data.ttl_seconds || ttlSeconds || 300));
                    if (!expiresAtTs) {
                        expiresAtTs = getServerNowTs() + totalTtl;
                    }
                    state.activeCreatedAtTs = expiresAtTs - totalTtl;
                    state.activeExpiresAtTs = expiresAtTs;
                    state.currentCountdownTotal = totalTtl;
                    persistActiveDepositSnapshot({
                        amount: Number(data.amount || 0),
                        bank_name: data.bank_name || '',
                        bank_owner: data.bank_owner || '',
                        bank_account: data.bank_account || '',
                        deposit_code: state.activeCode
                    });
                    startCountdownByExpiry(expiresAtTs, totalTtl, state.activeCreatedAtTs);
                    startPolling(state.activeCode);
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alertError('Không thể kết nối máy chủ');
                });
        }

        function cancelDeposit() {
            if (!state.activeCode) {
                return;
            }
            confirmAction({
                title: 'Huỷ giao dịch?',
                text: 'Bạn có chắc muốn huỷ giao dịch nạp tiền này?',
                confirmText: 'Huỷ ngay',
                cancelText: 'Không'
            }).then(function (confirmed) {
                if (!confirmed) return;
                fetch(endpoints.cancel, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    credentials: 'same-origin',
                    body: 'deposit_code=' + encodeURIComponent(state.activeCode) + '&csrf_token=' + encodeURIComponent(csrfToken)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.success) {
                            stopAll();
                            clearActiveDepositSnapshot();
                            alertInfo('Đã huỷ giao dịch nạp tiền.');
                            window.location.reload();
                            return;
                        }
                        alertError((res && res.message) ? res.message : 'Không thể huỷ giao dịch');
                    })
                    .catch(function () {
                        alertError('Không thể kết nối máy chủ');
                    });
            });
        }

        function downloadQr() {
            if (!elements.qr || !elements.qr.src) {
                alertError('Chưa có mã QR để tải');
                return;
            }
            fetch(elements.qr.src)
                .then(function (response) { return response.blob(); })
                .then(function (blob) {
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'QR_ThanhToan_' + (state.activeCode || 'deposit') + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                })
                .catch(function () {
                    alertError('Không thể tải QR');
                });
        }

        function bindEvents() {
            if (elements.amountInput) {
                elements.amountInput.addEventListener('input', function () {
                    state.amount = Number(this.value || 0) || 0;
                    updatePreview();
                });
            }

            elements.quickButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var amount = Number(btn.getAttribute('data-amount') || 0);
                    state.amount = amount;
                    if (elements.amountInput) {
                        elements.amountInput.value = String(amount);
                    }
                    updatePreview();
                });
            });

            if (elements.createButton) {
                elements.createButton.addEventListener('click', createDeposit);
            }
            if (elements.cancelButton) {
                elements.cancelButton.addEventListener('click', cancelDeposit);
            }
            if (elements.downloadQrButton) {
                elements.downloadQrButton.addEventListener('click', downloadQr);
            }

            root.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-copy-target');
                    var text = '';
                    if (target === 'account' && elements.tfAccount) text = elements.tfAccount.textContent.trim();
                    if (target === 'content' && elements.tfContent) text = elements.tfContent.textContent.trim();
                    copyText(text);
                });
            });

            elements.methodButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var methodCode = String(btn.getAttribute('data-method-code') || '');
                    var targetUrl = methodRoutes[methodCode];
                    if (!targetUrl) return;
                    if (window.location.href === targetUrl) return;
                    window.location.href = targetUrl;
                });
            });
        }

        bindEvents();
        updatePreview();

        if (activeDeposit && activeDeposit.deposit_code) {
            syncServerOffset(activeDeposit.server_now_ts || config.serverNowTs);
            state.activeCode = String(activeDeposit.deposit_code);
            state.activeCreatedAtTs = Number(activeDeposit.created_at_ts || 0);
            setTransferStepData(activeDeposit);
            switchToTransferStep();
            state.currentCountdownTotal = Math.max(1, Number(activeDeposit.ttl_seconds || ttlSeconds || 300));
            state.activeExpiresAtTs = Number(activeDeposit.expires_at_ts || 0);
            persistActiveDepositSnapshot();
            startCountdownByExpiry(
                state.activeExpiresAtTs || (getServerNowTs() + state.currentCountdownTotal),
                state.currentCountdownTotal,
                state.activeCreatedAtTs || (state.activeExpiresAtTs ? (state.activeExpiresAtTs - state.currentCountdownTotal) : 0)
            );
            startPolling(state.activeCode);
        } else {
            clearActiveDepositSnapshot();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('[data-balance-bank-root]');
        if (!root) return;
        var config = parseJsonConfig('balance-bank-config');
        initBankDeposit(root, config);
    });
})();

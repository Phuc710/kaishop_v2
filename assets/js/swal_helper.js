/**
 * SwalHelper - Centralized SweetAlert2 Wrapper
 * Dung chung cho toan bo project
 */
const SwalHelper = {
    // ============ GLOBAL TOAST INSTANCE ============
    _getToast() {
        return Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
    },

    // ============ TOAST (generic top-right notification) ============
    toast(message, icon = 'success') {
        this._getToast().fire({
            icon: icon,
            title: message
        });
    },

    // ============ SUCCESS (top-right toast, auto-close 3s, no button) ============
    success(message, callback = null) {
        this._getToast().fire({
            icon: 'success',
            title: message
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ ERROR (has OK button) ============
    error(message, callback = null) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message || 'Có lỗi xảy ra.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0981ce'
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ WARNING ============
    warning(message, callback = null) {
        Swal.fire({
            icon: 'warning',
            title: 'Cảnh Báo',
            text: message,
            confirmButtonColor: '#0981ce'
        }).then(() => {
            if (callback) callback();
        });
    },

    // ============ INFO ============
    info(message) {
        Swal.fire({
            icon: 'info',
            title: 'Thông Báo',
            text: message,
            confirmButtonColor: '#0981ce'
        });
    },

    // ============ CONFIRM ============
    confirm(title, text, onConfirm, onCancel = null) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            } else if (onCancel) {
                onCancel();
            }
        });
    },

    // ============ CONFIRM DELETE ============
    confirmDelete(onConfirm) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Hành động này không thể hoàn tác!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    },

    // ============ CONFIRM LOGOUT ============
    confirmLogout(logoutUrl) {
        Swal.fire({
            title: 'Đăng xuất?',
            text: 'Bạn có chắc muốn đăng xuất?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = logoutUrl;
            }
        });
    },

    // ============ LOADING ============
    loading(message = 'Đang xử lý...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { Swal.showLoading(); }
        });
    },

    // ============ CLOSE LOADING ============
    closeLoading() {
        Swal.close();
    },

    // ============ SUCCESS + REDIRECT (auto-close 1.5s) ============
    successRedirect(message, url) {
        Swal.fire({
            icon: 'success',
            title: 'Thành Công',
            text: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = url;
        });
    },

    // ============ SUCCESS + OK + REDIRECT (default 1.2s) ============
    successOkRedirect(message, url, delay = 1200) {
        Swal.fire({
            title: 'Thành Công',
            text: message || 'Thao tác thành công.',
            icon: 'success',
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
            timer: delay
        }).then(() => {
            window.location.href = url;
        });
    },

    successToastRedirect(message, url) {
        Swal.fire({
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = url;
        });
    },

    successReload(message) {
        Swal.fire({
            icon: 'success',
            title: 'Thành Công',
            text: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.reload();
        });
    },

    successToastReload(message) {
        this._getToast().fire({
            icon: 'success',
            title: message
        }).then(() => {
            window.location.reload();
        });
    },

    // ============ ERROR + REDIRECT ============
    errorRedirect(message, url) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message,
            confirmButtonColor: '#0981ce'
        }).then(() => {
            window.location.href = url;
        });
    },

    // ============ ERROR + RELOAD ============
    errorReload(message) {
        Swal.fire({
            icon: 'error',
            title: 'Thất Bại',
            text: message,
            confirmButtonColor: '#0981ce'
        }).then(() => {
            window.location.reload();
        });
    },

    _purchaseStyleInjected: false,

    _escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    _formatMoneyVnd(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    },

    _copyText(text) {
        const content = String(text || '');
        if (!content) return Promise.resolve(false);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(content).then(() => true).catch(() => false);
        }

        try {
            const textarea = document.createElement('textarea');
            textarea.value = content;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(textarea);
            return Promise.resolve(!!ok);
        } catch (e) {
            return Promise.resolve(false);
        }
    },

    _injectPurchaseModalStyle() {
        if (this._purchaseStyleInjected) return;
        if (document.getElementById('ks-order-result-style')) {
            this._purchaseStyleInjected = true;
            return;
        }

        const style = document.createElement('style');
        style.id = 'ks-order-result-style';
        style.textContent = [
            'div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-confirm):hover{background-color:#076fa5 !important;}',
            '.ks-order-modal-popup{max-width:680px !important;border-radius:24px !important;padding:28px 24px 24px !important;}',
            '.ks-order-modal{padding:0;}',
            '.ks-order-modal__head{display:flex;flex-direction:column;align-items:center;gap:6px;margin-bottom:18px;}',
            '.ks-order-modal__title{margin:0;font-size:26px;line-height:1.2;font-weight:900;color:#0f172a;letter-spacing:-0.5px;}',
            '.ks-order-modal__code-wrap{display:flex;flex-direction:column;align-items:center;gap:4px;}',
            '.ks-order-modal__code{display:inline-flex;align-items:center;gap:8px;padding:7px 18px;border-radius:999px;font-weight:800;font-size:14px;letter-spacing:0.5px;}',
            '.ks-order-modal__code.success{border:1px solid #86efac;background:#f0fdf4;color:#065f46;}',
            '.ks-order-modal__code.pending{border:1px solid #fcd34d;background:#fffbeb;color:#92400e;}',
            '.ks-order-modal__time{font-size:13px;color:#94a3b8;font-weight:600;}',
            '.ks-order-modal__status-wrap{margin-top:4px;}',
            '.ks-order-modal__status-pill{display:inline-flex;align-items:center;justify-content:center;padding:5px 14px;border-radius:99px;font-size:13px;font-weight:800;line-height:1;box-shadow:0 1px 2px rgba(0,0,0,0.05);}',
            '.ks-order-modal__status-pill.pending{background:#facc15;color:#713f12;}',
            '.ks-order-modal__status-pill.success{background:#10b981;color:#fff;}',
            '.ks-order-modal__content-wrap{text-align:left;margin-top:12px;}',
            '.ks-order-modal__content-label{font-weight:800;margin-bottom:12px;color:#0f172a;font-size:15px;display:flex;align-items:center;gap:8px;}',
            '.ks-order-modal__content{width:100%;height:140px;margin:0;padding:16px;border-radius:16px;border:1.5px solid #e2e8f0;background:#fff;color:#0f172a;font-size:14px;line-height:1.7;resize:none;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Courier New",monospace;box-shadow:0 2px 4px rgba(0,0,0,0.02);outline:none;transition:border-color .2s ease;}',
            '.ks-order-modal__content:focus{border-color:#3b82f6;}',
            '.ks-order-modal__pending-note{margin:14px 0 0;padding:12px 18px;border-radius:14px;border:1px solid #fcd34d;background:#fffbeb;color:#92400e;font-size:14px;font-weight:600;line-height:1.6;box-shadow:0 1px 2px rgba(0,0,0,0.05);}',
            '.ks-order-modal__actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:24px;}',
            '.ks-order-modal__btn{height:48px;border:0;border-radius:14px;font-size:15px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all .2s ease;box-shadow:0 2px 4px rgba(0,0,0,0.05);}',
            '.ks-order-modal__btn:hover{filter:brightness(.95);transform:translateY(-2px);box-shadow:0 4px 6px rgba(0,0,0,0.1);}',
            '.ks-order-modal__btn.copy{background:#2563eb;color:#fff;}',
            '.ks-order-modal__btn.detail{background:#0891b2;color:#fff;}',
            '.ks-order-modal__btn.more{background:#ffffff;color:#0f172a;border:1.5px solid #e2e8f0;box-shadow:none;}',
            '.ks-order-modal-close{color:#94a3b8 !important;font-size:32px !important;padding:12px !important;right:8px !important;top:8px !important;transition:color .2s ease;}',
            '.ks-order-modal-close:hover{color:#475569 !important;}',
            '@media (max-width:767.98px){.ks-order-modal-popup{padding:20px 16px !important;}.ks-order-modal__actions{grid-template-columns:1fr; gap:10px;}.ks-order-modal__title{font-size:22px;}.ks-order-modal__content{font-size:13.5px;max-height:160px;}.ks-order-modal__btn{height:46px;font-size:14px;}.ks-order-modal__meta{margin:16px 0; padding:16px;}}'
        ].join('');
        document.head.appendChild(style);
        this._purchaseStyleInjected = true;
    },

    /**
     * Hiển thị modal kết quả mua hàng (dùng chung cho cả completed và pending).
     * - completed: hiện nội dung tài khoản đã giao + nút Sao chép
     * - pending (requires_info): ẩn nội dung tài khoản, hiện note chờ xử lý
     */
    purchaseResult(payload = {}, options = {}) {
        const data = payload || {};
        const order = data.order || {};
        const status = String(order.status || '').toLowerCase();
        const isPending = !!data.pending || status === 'pending';

        const baseUrl = (typeof BASE_URL === 'string' && BASE_URL) ? BASE_URL : '';
        const historyUrl = String(options.historyUrl || (baseUrl + '/history-orders'));
        const detailUrl = String(options.detailUrl || historyUrl);

        const orderCode = this._escapeHtml(String(order.order_code_short || order.order_code || '-').toUpperCase());
        const orderTime = this._escapeHtml(order.created_at || new Date().toLocaleString());
        const productName = this._escapeHtml(order.product_name || 'Sản phẩm');
        const qty = Math.max(1, Number(order.quantity || 1));
        const total = this._formatMoneyVnd(order.price || 0);
        const contentRaw = String(
            isPending ? '' : (order.content || order.delivery_content || '')
        ).trim();
        const contentSafe = this._escapeHtml(contentRaw);
        const hasContent = !isPending && contentRaw !== '';

        this._injectPurchaseModalStyle();

        const titleText = isPending ? 'Đặt hàng thành công!' : 'Thanh toán thành công!';
        const swalIcon = isPending ? 'warning' : 'success';
        const codeClass = isPending ? 'pending' : 'success';
        const statusText = isPending ? 'Yêu cầu mới' : 'Hoàn tất';

        let contentSection = '';
        if (hasContent) {
            contentSection = ''
                + '<div class="ks-order-modal__content-wrap">'
                + '<div class="ks-order-modal__content-label"><i class="fas fa-boxes text-primary"></i> Nội dung đã giao:</div>'
                + '<textarea class="ks-order-modal__content" readonly>' + contentSafe + '</textarea>'
                + '</div>';
        }

        const pendingNote = isPending
            ? '<div class="ks-order-modal__pending-note"><i class="fas fa-clock" style="margin-right:6px;"></i>Đơn đang chờ Admin xử lý. Nội dung sẽ được giao trong mục <b>Lịch sử đơn hàng</b>.</div>'
            : '';

        const copyBtn = hasContent
            ? '<button type="button" class="ks-order-modal__btn copy js-order-modal-copy"><i class="far fa-copy"></i> Sao chép</button>'
            : '';

        const html = ''
            + '<div class="ks-order-modal">'
            + '<div class="ks-order-modal__head">'
            + '<h2 class="ks-order-modal__title">' + titleText + '</h2>'
            + '<div class="ks-order-modal__code-wrap">'
            + '<div class="ks-order-modal__code ' + codeClass + '">Đơn hàng #' + orderCode + '</div>'
            + '<div class="ks-order-modal__time">' + orderTime + '</div>'
            + '<div class="ks-order-modal__status-wrap">'
            + '<span class="ks-order-modal__status-pill ' + codeClass + '">' + this._escapeHtml(statusText) + '</span>'
            + '</div>'
            + '</div>'
            + '</div>'
            + contentSection
            + pendingNote
            + '<div class="ks-order-modal__actions">'
            + copyBtn
            + '<button type="button" class="ks-order-modal__btn detail js-order-modal-detail"><i class="far fa-file-alt"></i> Xem chi tiết đơn hàng</button>'
            + '<button type="button" class="ks-order-modal__btn more js-order-modal-buy-more"><i class="fas fa-cart-plus"></i> Mua thêm</button>'
            + '</div>'
            + '</div>';

        if (!window.Swal || !Swal.fire) {
            alert(isPending ? 'Đặt hàng thành công. Đơn đang chờ xử lý.' : 'Thanh toán thành công.');
            return Promise.resolve();
        }

        const helper = this;
        return Swal.fire({
            icon: swalIcon,
            html: html,
            width: Number(options.width || 680),
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'ks-order-modal-popup',
                closeButton: 'ks-order-modal-close'
            },
            didOpen: function (popupEl) {
                const popup = popupEl || document;

                const copyBtnEl = popup.querySelector('.js-order-modal-copy');
                if (copyBtnEl && hasContent) {
                    copyBtnEl.addEventListener('click', function () {
                        helper._copyText(contentRaw).then(function (ok) {
                            helper.toast(ok ? 'Đã sao chép nội dung' : 'Không thể sao chép', ok ? 'success' : 'error');
                        });
                    });
                }

                const detailBtn = popup.querySelector('.js-order-modal-detail');
                if (detailBtn) {
                    detailBtn.addEventListener('click', function () {
                        window.location.href = detailUrl;
                    });
                }

                const buyMoreBtn = popup.querySelector('.js-order-modal-buy-more');
                if (buyMoreBtn) {
                    buyMoreBtn.addEventListener('click', function () {
                        if (typeof options.onBuyMore === 'function') {
                            options.onBuyMore({ payload: data, order: order, isPending: isPending });
                        }
                        Swal.close();
                    });
                }

                if (typeof options.onOpen === 'function') {
                    options.onOpen({ payload: data, order: order, isPending: isPending, popup: popup });
                }

                if (!isPending && typeof options.onCompletedOpen === 'function') {
                    options.onCompletedOpen({ payload: data, order: order, popup: popup });
                }

                if (window.KaiConfetti) {
                    KaiConfetti.ensureReady().then(function () {
                        KaiConfetti.fire();
                    });
                }
            }
        });
    },
};

if (typeof window !== 'undefined') {
    window.SwalHelper = SwalHelper;
}

// ============ BACKWARD COMPATIBLE ============
function showMessage(message, type) {
    SwalHelper.toast(message, type === 'success' ? 'success' : 'error');
}

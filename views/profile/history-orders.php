<?php
/**
 * Lịch sử đơn hàng — History Orders Page
 * Layout: shared profile shell (header.php + footer.php)
 * CSS: user-pages.css (dùng chung toàn bộ trang user)
 */
$userPageTitle = 'Lịch sử đơn hàng';
$userPageAssetFlags = [
    'datatables' => true,
    'flatpickr' => true,
    'interactive_bundle' => false,
];
$activePage = 'order-history';
require __DIR__ . '/layout/header.php';
?>

<style>
    /* Premium Table Styles */
    /* Standardized User Table Styles */
    #order-history-page .user-history-table {
        width: 100% !important;
        min-width: 1000px;
        border-collapse: collapse;
        /* Match balance history */
    }

    #order-history-page .user-history-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0 !important;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 11px;
        padding: 12px 15px !important;
    }

    #order-history-page .user-history-table tbody tr {
        transition: background 0.2s;
    }

    #order-history-page .user-history-table tbody tr:hover td {
        /* No hover effect on table cells as requested */
    }

    #order-history-page .user-history-table td {
        padding: 12px 15px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        vertical-align: middle;
        background: #fff;
    }

    .order-cell-product {
        width: 20%;
        min-width: 180px;
    }

    .order-product-name {
        font-size: 14px;
        margin-bottom: 4px;
        color: #1e293b;
    }

    /* Content Column */
    .order-cell-content {
        max-width: 350px;
    }

    .content-textarea-wrap {
        position: relative;
        width: 100%;
        min-width: 350px;
    }

    .content-textarea {
        width: 100%;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 14.5px;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 30px 8px 10px;
        resize: vertical;
        min-height: 60px;
        line-height: 1.5;
    }

    /* Custom Scrollbar for Textarea */
    .content-textarea::-webkit-scrollbar {
        width: 6px;
    }

    .content-textarea::-webkit-scrollbar-track {
        background: transparent;
    }

    .content-textarea::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .content-textarea::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .content-textarea:focus {
        outline: none;
        border-color: #cbd5e1;
    }

    .btn-copy-absolute {
        position: absolute;
        top: 6px;
        right: 14px;
        padding: 4px 6px;
        font-size: 11px;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        color: #64748b;
        transition: all 0.2s;
        cursor: pointer;
        z-index: 5;
    }

    .btn-copy-absolute:hover {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }

    /* Status Tags (Consistent with App Design) */
    .user-order-status {
        font-size: 13px !important;
        font-weight: 700;
        display: inline-block;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
    }

    .user-order-status.is-completed {
        color: #16a34a !important;
    }

    .user-order-status.is-pending,
    .user-order-status.is-processing {
        color: #b45309 !important;
    }

    .user-order-status.is-cancelled {
        color: #b91c1c !important;
    }

    /* Action Buttons */
    .order-action-btn {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
        background: #fff;
        cursor: pointer;
        color: #64748b;
        margin: 0 2px;
    }

    .order-action-btn:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }

    /* Refactored Premium Modal Styles */
    .premium-order-modal .swal2-popup {
        padding: 0 !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        box-shadow: 0 18px 50px rgba(0, 0, 0, .18) !important;
        width: 100% !important;
        max-width: 460px !important;
        background: #fff !important;
    }

    @media (max-width: 767.98px) {
        .premium-order-modal .swal2-popup {
            width: 95vw !important;
            max-width: 95vw !important;
            margin: 0 auto;
        }

        .user-order-detail__body {
            padding: 15px !important;
        }

        .user-order-detail__header {
            padding: 12px 15px !important;
            font-size: 16px !important;
        }
    }

    .premium-order-modal .swal2-title {
        display: none !important;
    }

    .premium-order-modal .swal2-html-container {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .user-order-detail {
        width: 100%;
        text-align: left;
        border-radius: 14px;
        background: #fff;
    }

    .user-order-detail__header {
        background: #1494a9;
        color: #fff;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 18px;
        font-weight: 700;
    }

    .user-order-detail__close {
        background: none;
        border: none;
        color: #fff;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        transition: opacity 0.2s;
    }

    .user-order-detail__close:hover {
        opacity: 0.8;
    }

    .user-order-detail__body {
        padding: 20px;
    }

    .user-order-detail__section {
        background: #f8fbfc;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 16px;
    }

    .user-order-detail__row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px dashed #e5e7eb;
    }

    .user-order-detail__row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .user-order-detail__label {
        color: #6b7280;
        font-size: 14px;
    }

    .user-order-detail__value {
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        text-align: right;
    }

    .user-order-detail__status-pill {
        color: #16a34a;
        background: #eafaf0;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
    }

    .user-order-detail__status-pill.is-pending {
        color: #d97706;
        background: #fffbeb;
    }

    .user-order-detail__status-pill.is-cancelled {
        color: #dc2626;
        background: #fef2f2;
    }

    .user-order-detail__block-label {
        margin: 0 0 10px;
        font-size: 15px;
        color: #111827;
        font-weight: 700;
    }

    .user-order-detail__content-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #cbd5e1;
        background: #f9fcfd;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .user-order-detail__textarea {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        word-break: break-all;
        flex: 1;
        background: transparent;
        border: none;
        padding: 0;
        margin: 0;
        resize: none;
        font-family: inherit;
        overflow: hidden;
        min-height: 20px;
        line-height: 1.5;
    }

    .user-order-detail__textarea:focus {
        outline: none;
    }

    .btn-copy-pill {
        background: #1494a9;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
        transition: opacity 0.2s;
    }

    .btn-copy-pill:hover {
        opacity: .92;
    }
</style>

<div class="profile-card" id="order-history-page">

    <!-- ── Card Header ──────────────────────────────── -->
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">LỊCH SỬ ĐƠN HÀNG</h5>
        </div>
        <a href="<?= url('') ?>" class="btn btn-edit-profile">
            <i class="fas fa-shopping-bag me-1"></i> Mua Hàng
        </a>
    </div>

    <!-- ── Card Body ────────────────────────────────── -->
    <div class="profile-card-body p-4">

        <!-- Bộ lọc -->
        <div class="user-history-filters mb-4">
            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-6 mb-2">
                    <div class="input-group user-filter-input">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="filter-keyword" class="form-control border-start-0 ps-0"
                            placeholder="Tìm mã đơn hàng hoặc tên sản phẩm...">
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="input-group user-filter-input">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="far fa-calendar-alt"></i>
                        </span>
                        <input type="text" id="filter-date" class="form-control border-start-0 ps-0 bg-white"
                            placeholder="Từ ngày - Đến ngày" readonly>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button id="btn-clear" class="btn btn-clear-custom w-100 py-2" title="Xóa bộ lọc">
                        <i class="fas fa-trash me-1"></i> Xóa lọc
                    </button>
                </div>
            </div>

            <!-- Toolbar: Show / Sort -->
            <div class="user-history-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="user-toolbar-label">Show :</span>
                    <select id="f-length"
                        class="form-select form-select-sm shadow-none user-toolbar-select user-toolbar-select--narrow">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="d-flex align-items-center">
                    <span class="user-toolbar-label">Lọc theo ngày:</span>
                    <select id="f-sort" class="form-select form-select-sm shadow-none user-toolbar-select">
                        <option value="all">Tất cả</option>
                        <option value="today">Hôm nay</option>
                        <option value="7">7 ngày</option>
                        <option value="30">30 ngày</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table: scroll ngang khi cần, không bao giờ rộng hơn container -->
        <div class="table-responsive user-history-table-wrap">
            <table id="order-history-table" class="table table-hover align-middle mb-0 user-history-table">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">SẢN PHẨM</th>
                        <th class="text-center">SL</th>
                        <th class="text-start">NỘI DUNG</th>
                        <th class="text-center">THANH TOÁN</th>
                        <th class="text-center">THỜI GIAN</th>
                        <th class="text-center">THAO TÁC</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div><!-- /.profile-card-body -->
</div><!-- /.profile-card -->

<script>
    /**
     * OrderHistoryManager — OOP class quản lý trang lịch sử đơn hàng.
     * Dùng DataTables server-side, flatpickr date range, SwalHelper.
     */
    (function () {
        'use strict';

        const jquerySrc = '<?= asset('assets/js/jquery.js') ?>';
        const datatablesSrc = '<?= asset('assets/js/datatables.js') ?>';
        const flatpickrSrc = '<?= asset('assets/js/flatpickr.js') ?>';
        const scriptPromises = {};
        let booted = false;

        function loadScriptOnce(src) {
            if (!src) {
                return Promise.reject(new Error('Missing script source'));
            }
            if (scriptPromises[src]) {
                return scriptPromises[src];
            }

            scriptPromises[src] = new Promise(function (resolve, reject) {
                const existing = document.querySelector('script[src="' + src + '"]');
                if (existing && existing.dataset.loaded === 'true') {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = src;
                script.async = false;
                script.onload = function () {
                    script.dataset.loaded = 'true';
                    resolve();
                };
                script.onerror = function () {
                    reject(new Error('Failed to load script: ' + src));
                };
                document.head.appendChild(script);
            });

            return scriptPromises[src];
        }

        async function ensureDependencies() {
            if (!window.jQuery) {
                await loadScriptOnce(jquerySrc);
            }
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable)) {
                await loadScriptOnce(datatablesSrc);
            }
            if (typeof window.flatpickr === 'undefined') {
                await loadScriptOnce(flatpickrSrc);
            }
        }

        async function boot() {
            if (booted) {
                return;
            }
            booted = true;

            try {
                await ensureDependencies();
                initOrderHistoryPage();
            } catch (error) {
                console.error('Order history boot failed:', error);
            }
        }

        // ── Helpers ────────────────────────────────────────────────────────

        function escapeHtml(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function nl2brSafe(text) {
            return escapeHtml(text).replace(/\r?\n/g, '<br>');
        }

        function fmtMoney(v) {
            return new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + 'đ';
        }

        function debounce(fn, wait) {
            let timer = null;
            return function () {
                const ctx = this;
                const args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () { fn.apply(ctx, args); }, wait || 250);
            };
        }

        async function copyToClipboard(text) {
            const value = String(text || '');
            if (!value) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                try { await navigator.clipboard.writeText(value); return; } catch (e) { /* fallback */ }
            }
            const ta = document.createElement('textarea');
            ta.value = value; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); ta.remove();
        }

        async function fetchJson(url, options) {
            const response = await fetch(url, Object.assign({ credentials: 'same-origin', cache: 'no-store' }, options || {}));
            const raw = await response.text();
            let data = {};
            try { data = JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}'); } catch (e) { data = {}; }
            return data || {};
        }

        // ── OrderHistoryManager Class ──────────────────────────────────────

        class OrderHistoryManager {

            constructor(config) {
                this.baseUrl = config.baseUrl;
                this.csrfToken = config.csrfToken || '';
                this.table = null;
                this.datePicker = { clear: function () { $('#filter-date').val(''); } };
            }

            /** Khởi động toàn bộ */
            init() {
                this._initDataTable();
                this._initDatePicker();
                this._bindEvents();
            }

            /** DataTable server-side */
            _initDataTable() {
                const self = this;
                this.table = $('#order-history-table').DataTable({
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: this.baseUrl + '/api/history-orders',
                        type: 'POST',
                        data: function (d) {
                            d.keyword = $('#filter-keyword').val();
                            d.time_range = $('#filter-date').val();
                            d.sort_date = $('#f-sort').val();
                            d.csrf_token = self.csrfToken;
                        }
                    },
                    columns: [
                        {
                            data: 'product_name',
                            className: 'order-cell-product text-start',
                            render: function (data, type, row) {
                                let html = '<div class="order-product-name fw-bold">' + escapeHtml(data || '') + '</div>';
                                html += '<div>' + self._renderStatusBadge(row.status_label || row.status || '', row.status) + '</div>';
                                return html;
                            }
                        },
                        {
                            data: 'quantity',
                            className: 'text-center fw-bold order-cell-qty',
                            render: function (data) {
                                return '<span class="text-muted">x' + data + '</span>';
                            }
                        },
                        {
                            data: 'delivery_content',
                            className: 'order-cell-content',
                            render: function (data, type, row) {
                                const pendingBadge = '<span class="text-muted small"><i class="fas fa-box-open me-1"></i><em>Chờ bàn giao...</em></span>';
                                if (!data) return pendingBadge;
                                const clean = data.trim();
                                if (!clean) return pendingBadge;

                                return `
                                    <div class="content-textarea-wrap">
                                        <textarea class="content-textarea" readonly rows="2">${escapeHtml(clean)}</textarea>
                                        <button type="button" class="btn-copy-absolute js-copy-now" data-text="${escapeHtml(clean)}" title="Copy nhanh">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        },
                        {
                            data: 'payment',
                            className: 'text-center text-nowrap order-cell-payment',
                            render: function (data) {
                                return '<span class="fw-bold" style="color:#059669;">' + fmtMoney(data) + '</span>';
                            }
                        },
                        {
                            data: null,
                            className: 'text-center text-nowrap order-cell-time',
                            render: function (data, type, row) {
                                return self._renderTimeCell(row);
                            }
                        },
                        {
                            data: null,
                            className: 'text-center order-cell-actions',
                            render: function (data, type, row) {
                                return self._renderActions(row);
                            }
                        }
                    ],
                    order: [],
                    ordering: false,
                    pageLength: 10,
                    dom: '<"user-history-table-wrap" t><"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3"<"text-muted small"i><"d-flex align-items-center gap-3"p>>',
                    language: {
                        info: 'Hiển thị _START_ - _END_ trong tổng số _TOTAL_ đơn hàng',
                        infoEmpty: 'Chưa có đơn hàng nào',
                        emptyTable: 'Không tìm thấy đơn hàng nào',
                        paginate: { next: '&rsaquo;', previous: '&lsaquo;' }
                    }
                });
            }

            /** Flatpickr date range */
            _initDatePicker() {
                if (!window.flatpickr) return;
                const self = this;
                const fpLocale = (flatpickr.l10ns && (flatpickr.l10ns.vn || flatpickr.l10ns.VN))
                    ? (flatpickr.l10ns.vn || flatpickr.l10ns.VN)
                    : undefined;

                this.datePicker = flatpickr('#filter-date', {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    locale: fpLocale,
                    onChange: function (selectedDates) {
                        if (selectedDates.length === 2 || selectedDates.length === 0) {
                            self.table.draw();
                        }
                    }
                });
            }

            /** Bind all filter / action events */
            _bindEvents() {
                const self = this;
                const debouncedDraw = debounce(function () { self.table.draw(); }, 280);

                $('#f-length').on('change', function () {
                    self.table.page.len(parseInt($(this).val(), 10)).draw();
                });

                $('#f-sort').on('change', function () { self.table.draw(); });

                $('#filter-keyword').on('input', debouncedDraw).on('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); self.table.draw(); }
                });

                $('#btn-clear').on('click', function () {
                    $('#filter-keyword').val('');
                    $('#f-sort').val('all');
                    self.datePicker.clear();
                    self.table.draw();
                });

                // Row action buttons (delegated)
                $('#order-history-table').on('click', '.js-view-order', function () {
                    self.viewDetail($(this).data('id'));
                });

                $('#order-history-table').on('click', '.js-delete-order', function () {
                    self.deleteOrder($(this).data('id'));
                });

                $('#order-history-table').on('click', '.js-copy-now', function (e) {
                    e.stopPropagation();
                    const txt = $(this).data('text');
                    copyToClipboard(txt).then(() => {
                        const $icon = $(this).find('i');
                        $icon.removeClass('far fa-copy').addClass('fas fa-check');
                        setTimeout(() => {
                            $icon.removeClass('fas fa-check').addClass('far fa-copy');
                        }, 1000);
                        SwalHelper.toast('Đã copy nội dung!', 'success');
                    });
                });

                // Modal copy button (delegated)
                $(document).on('click', '.js-modal-copy-text', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetId = $(this).data('target');
                    const $el = $('#' + targetId);
                    const txt = $el.is('textarea, input') ? $el.val() : $el.text();

                    const $btn = $(this);
                    const originalHtml = $btn.html();

                    copyToClipboard(txt).then(() => {
                        $btn.html('<i class="fa-solid fa-check"></i>').prop('disabled', true);
                        setTimeout(() => {
                            $btn.html(originalHtml).prop('disabled', false);
                        }, 1500);
                        SwalHelper.toast('Đã copy nội dung!', 'success');
                    });
                });
            }

            // ── Render Helpers ───────────────────────────────────────────────

            _renderTimeCell(row) {
                if (window.KaiTime && typeof window.KaiTime.renderUserTimeCell === 'function') {
                    return window.KaiTime.renderUserTimeCell({
                        timeTs: row.time_ts,
                        rawValue: row.time_raw || row.time_display,
                        fallbackText: row.time_display,
                        timeAgo: row.time_ago,
                        className: 'user-time-plain'
                    });
                }
                return escapeHtml(row.time_display || row.time || '--');
            }

            _getStatusClass(status) {
                const s = String(status || '').trim().toLowerCase();
                if (s === 'completed') return 'is-completed';
                if (s === 'cancelled' || s === 'canceled' || s === 'failed') return 'is-cancelled';
                // Everything else (pending, processing, etc.) is orange
                return 'is-pending';
            }

            _renderStatusBadge(label, status) {
                const s = String(status || '').trim().toLowerCase();
                let displayLabel = label || status || '--';

                if (s === 'completed') {
                    displayLabel = 'Hoàn tất';
                } else if (s === 'cancelled' || s === 'canceled' || s === 'failed') {
                    displayLabel = 'Đã hủy';
                } else {
                    displayLabel = 'Đang xử lý';
                }

                return '<span class="user-order-status ' + this._getStatusClass(status) + '">'
                    + escapeHtml(displayLabel)
                    + '</span>';
            }

            _renderActions(row) {
                const id = Number(row.id || 0);
                const downloadUrl = this.baseUrl + '/history-orders/download/' + encodeURIComponent(id);
                return ''
                    + '<button type="button" class="order-action-btn js-view-order" data-id="' + id + '" title="Xem chi tiết" style="color:#007bff;background:#ebf5ff;border-color:#cce5ff;">'
                    + '<i class="fas fa-eye"></i>'
                    + '</button>'
                    + '<a class="order-action-btn text-success mx-1" href="' + downloadUrl + '" title="Tải nội dung" style="background:#ecfdf5;border-color:#a7f3d0;">'
                    + '<i class="fas fa-download"></i>'
                    + '</a>'
                    + '<button type="button" class="order-action-btn js-delete-order text-danger" data-id="' + id + '" title="Ẩn lịch sử" style="background:#fef2f2;border-color:#fecaca;">'
                    + '<i class="fas fa-trash"></i>'
                    + '</button>';
            }

            // ── API Actions ──────────────────────────────────────────────────

            async viewDetail(orderId) {
                const id = Number(orderId || 0);
                if (!id) return;

                try {
                    SwalHelper.loading('Đang tải chi tiết đơn hàng...');
                    const data = await fetchJson(this.baseUrl + '/api/history-orders/detail/' + encodeURIComponent(id), { method: 'GET' });
                    SwalHelper.closeLoading();

                    if (!data.success || !data.order) {
                        SwalHelper.error(data.message || 'Không thể tải chi tiết đơn hàng.');
                        return;
                    }

                    const o = data.order;
                    Swal.fire({
                        html: this._buildDetailHtml(o),
                        showConfirmButton: false,
                        width: '460px',
                        focusConfirm: false,
                        padding: '0',
                        customClass: {
                            container: 'premium-order-modal-container',
                            popup: 'premium-order-modal'
                        },
                        showCloseButton: false,
                        backdrop: `rgba(0,0,0,0.55)`,
                        allowOutsideClick: true,  // Allow closing on backdrop click
                        allowEscapeKey: true      // Still allow ESC to close for UX
                    });

                } catch (err) {
                    console.error(err);
                    SwalHelper.closeLoading();
                    SwalHelper.error('Không thể tải chi tiết đơn hàng.');
                }
            }

            async deleteOrder(orderId) {
                const id = Number(orderId || 0);
                const self = this;
                if (!id) return;

                SwalHelper.confirmDelete(async function () {
                    try {
                        const fd = new FormData();
                        fd.append('order_id', String(id));
                        if (self.csrfToken) fd.append('csrf_token', self.csrfToken);

                        const data = await fetchJson(self.baseUrl + '/api/history-orders/delete', {
                            method: 'POST',
                            body: fd
                        });

                        if (data && data.success) {
                            SwalHelper.toast(data.message || 'Đã ẩn lịch sử đơn hàng', 'success');
                            self.table.ajax.reload(null, false);
                            return;
                        }
                        SwalHelper.error((data && data.message) || 'Không thể ẩn lịch sử đơn hàng.');
                    } catch (err) {
                        SwalHelper.error('Không thể kết nối đến máy chủ!');
                    }
                });
            }

            _buildDetailHtml(o) {
                const self = this;
                const status = String(o.status || '').toLowerCase();
                const isPending = (status === 'pending' || status === 'processing');
                const isCompleted = (status === 'completed');

                // Determine if this is a "requested" type order
                const isRequestedOrder = (o.customer_input && o.customer_input.trim() !== '' && o.customer_input.trim().toLowerCase() !== 'không có');

                let html = `
                    <div class="user-order-detail">
                        <div class="user-order-detail__header">
                            <span>Chi tiết đơn hàng</span>
                            <button type="button" class="user-order-detail__close" onclick="Swal.close()">×</button>
                        </div>
                        <div class="user-order-detail__body">
                            <div class="user-order-detail__section">
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Mã đơn hàng</span>     
                                    <span class="user-order-detail__value">#${escapeHtml(o.order_code || '')}</span>
                                </div>
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Sản phẩm</span>
                                    <span class="user-order-detail__value">${escapeHtml(o.product_name || '')}</span>
                                </div>
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Trạng thái</span>
                                    <span class="user-order-detail__status-pill ${this._getStatusClass(o.status)}">
                                        ${escapeHtml(o.status_label || (isCompleted ? "Hoàn tất" : (isPending ? "Đang xử lý" : (o.status || "Đã hủy"))))}
                                    </span>
                                </div>
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Số lượng</span>
                                    <span class="user-order-detail__value">x${o.quantity || 1}</span>
                                </div>
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Tổng thanh toán</span>
                                    <span class="user-order-detail__value">${fmtMoney(o.price || 0)}</span>
                                </div>
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Thời gian đặt</span>
                                    <span class="user-order-detail__value">${o.created_at_display || o.created_at || ''}</span>
                                </div>
                                ${(isRequestedOrder && !isPending && o.fulfilled_at_display) ? `
                                <div class="user-order-detail__row">
                                    <span class="user-order-detail__label">Thời gian giao</span>
                                    <span class="user-order-detail__value">${o.fulfilled_at_display}</span>
                                </div>
                                ` : ''}
                            </div>

                            ${isRequestedOrder ? `
                            <div class="user-order-detail__section">
                                <h3 class="user-order-detail__block-label">Thông tin yêu cầu của bạn</h3>
                                <div class="user-order-detail__content-box">
                                    <textarea class="user-order-detail__textarea" readonly id="modal-req-text" rows="1">${escapeHtml(o.customer_input)}</textarea>
                                    <button type="button" class="btn-copy-pill js-modal-copy-text" data-target="modal-req-text"><i class="fa-solid fa-copy"></i></button>
                                </div>
                            </div>
                            ` : ''}

                            <div class="user-order-detail__section">
                                <h3 class="user-order-detail__block-label">Nội dung sản phẩm / bàn giao</h3>
                                <div class="user-order-detail__content-box">
                                    <textarea class="user-order-detail__textarea" readonly id="modal-del-text" rows="1"
                                        style="${isPending ? 'font-style:italic;color:#94a3b8;' : ''}">${isPending ? 'Đang xử lý...' : (o.delivery_content || 'Chưa có nội dung')}</textarea>
                                    ${!isPending && o.delivery_content ? `
                                        <button type="button" class="btn-copy-pill js-modal-copy-text" data-target="modal-del-text"><i class="fa-solid fa-copy"></i></button>
                                    ` : ''}
                                </div>
                            </div>
                            
                            ${o.cancel_reason ? `
                            <div class="user-order-detail__section" style="border-color:#fecaca;background:#fff5f5;">
                                <h3 class="user-order-detail__block-label" style="color:#b91c1c;">Lý do hủy / Phản hồi</h3>
                                <p style="margin:0;font-size:14px;color:#b91c1c;">${escapeHtml(o.cancel_reason)}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                return html;
            }
        }

        // ── Boot ───────────────────────────────────────────────────────────
        function initOrderHistoryPage() {
            const manager = new OrderHistoryManager({
                baseUrl: (typeof BASE_URL !== 'undefined' ? BASE_URL : ''),
                csrfToken: (typeof window.KS_CSRF_TOKEN !== 'undefined' ? window.KS_CSRF_TOKEN : '')
            });
            manager.init();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot, { once: true });
        } else {
            boot();
        }

    })();
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>

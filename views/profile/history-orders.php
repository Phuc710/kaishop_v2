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
                        <th class="py-3 text-nowrap">SẢN PHẨM</th>
                        <th class="py-3 text-nowrap text-center">TÌNH TRẠNG</th>
                        <th class="py-3 text-nowrap text-center">SL</th>
                        <th class="py-3 text-nowrap text-center">THANH TOÁN</th>
                        <th class="py-3 text-nowrap text-center">THỜI GIAN</th>
                        <th class="py-3 text-nowrap text-center">THAO TÁC</th>
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
                        // Cột: Sản phẩm — wrap text tự nhiên, không bao giờ rộng hơn container
                        {
                            data: 'product_name',
                            className: 'order-cell-product',
                            render: function (data, type, row) {
                                const code = row.order_code_short || row.order_code || '';
                                return '<div class="fw-semibold text-dark">' + escapeHtml(data || '') + '</div>'
                                    + (code ? '<div class="text-muted small mt-1"><i class="fas fa-hashtag me-1"></i>' + escapeHtml(code) + '</div>' : '');
                            }
                        },
                        // Cột: Tình trạng
                        {
                            data: null,
                            className: 'text-center order-cell-status',
                            render: function (data, type, row) {
                                return self._renderStatusBadge(row.status_label || row.status || '', row.status);
                            }
                        },
                        // Cột: Số lượng
                        {
                            data: 'quantity',
                            className: 'text-center text-nowrap order-cell-qty'
                        },
                        // Cột: Thanh toán
                        {
                            data: 'payment',
                            className: 'text-center text-nowrap order-cell-payment',
                            render: function (data) {
                                return '<span class="fw-bold" style="color:#00ad5c;">' + fmtMoney(data) + '</span>';
                            }
                        },
                        // Cột: Thời gian
                        {
                            data: null,
                            className: 'text-center text-nowrap order-cell-time',
                            render: function (data, type, row) {
                                return self._renderTimeCell(row);
                            }
                        },
                        // Cột: Thao tác
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
                    dom: 't<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3"<"text-muted small"i><"d-flex align-items-center gap-3"p>>',
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
                if (s === 'pending') return 'is-pending';
                if (s === 'processing') return 'is-processing';
                if (s === 'cancelled' || s === 'canceled' || s === 'failed') return 'is-cancelled';
                return 'is-default';
            }

            _renderStatusBadge(label, status) {
                return '<span class="user-order-status ' + this._getStatusClass(status) + '">'
                    + escapeHtml(String(label || status || '--'))
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
                    const html = this._buildDetailHtml(o);

                    Swal.fire({
                        title: 'Chi tiết đơn hàng',
                        html: html,
                        width: 760,
                        confirmButtonText: 'Đóng'
                    });

                } catch (err) {
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
                const rows = [
                    ['Sản phẩm', escapeHtml(o.product_name || '')],
                    ['Mã đơn hàng', escapeHtml(o.order_code_short || o.order_code || '')],
                    ['Số lượng', escapeHtml(o.quantity || 0)],
                    ['Trạng thái', this._renderStatusBadge(o.status_label || o.status || '', o.status)],
                    ['Ngày đặt', escapeHtml(o.created_at_display || o.created_at || '')],
                    ['Thanh toán', '<span class="text-success fw-bold">' + fmtMoney(o.price || 0) + '</span>'],
                ];
                if (o.fulfilled_at_display) {
                    rows.push(['Ngày giao', escapeHtml(o.fulfilled_at_display)]);
                }

                let html = '<div class="user-order-detail"><div class="user-order-detail__grid">';
                rows.forEach(function (pair) {
                    html += '<div>'
                        + '<div class="user-order-detail__label">' + pair[0] + '</div>'
                        + '<div class="user-order-detail__value">' + pair[1] + '</div>'
                        + '</div>';
                });
                html += '</div>';

                if (o.customer_input && o.customer_input.trim() !== '' && o.customer_input.trim().toLowerCase() !== 'không có') {
                    html += '<div class="user-order-detail__block">'
                        + '<div class="user-order-detail__label">Thông tin bạn gửi</div>'
                        + '<div class="user-order-detail__textarea">' + nl2brSafe(o.customer_input) + '</div>'
                        + '</div>';
                }

                html += '<div class="user-order-detail__block">'
                    + '<div class="user-order-detail__label">Nội dung bàn giao</div>'
                    + '<div class="user-order-detail__textarea">' + nl2brSafe(o.delivery_content || 'Chưa có nội dung bàn giao') + '</div>'
                    + '</div>';

                if (o.cancel_reason) {
                    html += '<div class="user-order-detail__block">'
                        + '<div class="user-order-detail__label">Lý do hủy / phản hồi</div>'
                        + '<div class="user-order-detail__textarea">' + nl2brSafe(o.cancel_reason) + '</div>'
                        + '</div>';
                }

                html += '</div>';
                return html;
            }
        }

        // ── Boot ───────────────────────────────────────────────────────────
        $(document).ready(function () {
            const manager = new OrderHistoryManager({
                baseUrl: (typeof BASE_URL !== 'undefined' ? BASE_URL : ''),
                csrfToken: (typeof window.KS_CSRF_TOKEN !== 'undefined' ? window.KS_CSRF_TOKEN : '')
            });
            manager.init();
        });

    })();
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
<?php
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
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">LỊCH SỬ ĐƠN HÀNG</h5>
        </div>
    </div>

    <div class="profile-card-body p-4">
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
                    <button id="btn-clear" class="btn w-100 py-2" title="Xóa bộ lọc">
                        <i class="fas fa-trash me-1"></i> Xóa lọc
                    </button>
                </div>
            </div>

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
                    <span class="user-toolbar-label">Sort by date:</span>
                    <select id="f-sort" class="form-select form-select-sm shadow-none user-toolbar-select">
                        <option value="all">Tất cả</option>
                        <option value="today">Hôm nay</option>
                        <option value="7">7 ngày</option>
                        <option value="30">30 ngày</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive user-history-table-wrap">
            <table id="order-history-table" class="table table-hover align-middle w-100 mb-0 user-history-table">
                <thead class="table-light">
                    <tr>

                        <th class="py-3 text-nowrap text-center">SẢN PHẨM</th>
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
    </div>
</div>

<style>
    #order-history-page .profile-card-body,
    #order-history-page .profile-card-body *:not(i) {
        font-family: 'Signika', sans-serif !important;
        font-size: 13px !important;
    }

    #order-history-page .dataTables_wrapper .dataTables_info,
    #order-history-page .dataTables_wrapper .paginate_button,
    #order-history-page .user-toolbar-label,
    #order-history-page .user-toolbar-select,
    #order-history-page .form-control,
    #order-history-page .btn {
        font-family: 'Signika', sans-serif !important;
        font-size: 13px !important;
    }

    #order-history-page #order-history-table .user-order-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 3px 12px;
        border-radius: 99px;
        border: 1px solid #dbe3ee;
        background: #f8fafc;
        color: #334155;
        font-size: 11px !important;
        font-weight: 700;
        line-height: 1.2;
    }

    #order-history-page #order-history-table .user-order-status.is-pending,
    #order-history-page #order-history-table .small {
        border-color: #facc15;
        background: #fffbeb;
        color: #b45309 !important;
    }

    #order-history-page #order-history-table .user-order-status.is-completed,
    #order-history-page #order-history-table .small[style*="#00ad5c"],
    #order-history-page #order-history-table .small[style*="#00AD5C"] {
        border-color: #86efac;
        background: #f0fdf4;
        color: #16a34a !important;
    }

    #order-history-page #order-history-table .small {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 3px 12px;
        border-radius: 99px;
        border-width: 1px;
        border-style: solid;
        font-size: 11px !important;
        font-weight: 700;
        line-height: 1.2;
    }
</style>

<script>
    $(document).ready(function () {
        function escapeHtml(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderSharedTimeCell(row) {
            if (window.KaiTime && typeof window.KaiTime.renderUserTimeCell === 'function') {
                return window.KaiTime.renderUserTimeCell({
                    timeTs: row.time_ts,
                    rawValue: row.time_raw || row.time_display,
                    fallbackText: row.time_display,
                    timeAgo: row.time_ago,
                    className: 'user-time-plain'
                });
            }
            return String(row.time_display || '--');
        }

        function debounce(fn, wait) {
            let t = null;
            return function () {
                const ctx = this;
                const args = arguments;
                clearTimeout(t);
                t = setTimeout(function () { fn.apply(ctx, args); }, wait || 250);
            };
        }

        let datePicker = { clear: function () { $('#filter-date').val(''); } };

        const table = $('#order-history-table').DataTable({
            serverSide: true,
            ajax: {
                url: BASE_URL + '/api/history-orders',
                type: 'POST',
                data: function (d) {
                    d.keyword = $('#filter-keyword').val();
                    d.time_range = $('#filter-date').val();
                    d.sort_date = $('#f-sort').val();
                    d.csrf_token = (window.KS_CSRF_TOKEN || '');
                }
            },
            columns: [

                {
                    data: 'product_name',
                    render: function (data) {
                        return '<div class="fw-semibold">' + escapeHtml(data || '') + '</div>';
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function (row) {
                        return renderOrderStatusBadge(row.status_label || row.status || '', row.status);
                    }
                },
                { data: 'quantity', className: 'text-center' },
                {
                    data: 'payment',
                    className: 'text-center',
                    render: function (data) {
                        return '<span class="fw-bold" style="color: #00ad5c;">' + fmtMoney(data) + '</span>';
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function (row) {
                        return renderSharedTimeCell(row);
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function (row) {
                        const id = Number(row.id || 0);
                        const downloadUrl = BASE_URL + '/history-orders/download/' + encodeURIComponent(id);
                        return ''
                            + '<button type="button" class="order-action-btn js-view-order" data-id="' + id + '" title="Xem chi tiết" style="color: #007bff; background: #ebf5ff; border-color: #cce5ff;"><i class="fas fa-eye"></i></button>'
                            + '<a class="order-action-btn text-success mx-1" href="' + downloadUrl + '" title="Tải nội dung" style="background: #ecfdf5; border-color: #a7f3d0;"><i class="fas fa-download"></i></a>'
                            + '<button type="button" class="order-action-btn js-delete-order text-danger" data-id="' + id + '" title="Ẩn lịch sử" style="background: #fef2f2; border-color: #fecaca;"><i class="fas fa-trash"></i></button>';
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
                emptyTable: 'Không có dữ liệu đơn hàng',
                paginate: { next: '&rsaquo;', previous: '&lsaquo;' }
            }
        });

        if (window.flatpickr) {
            const fpLocale = (flatpickr.l10ns && (flatpickr.l10ns.vn || flatpickr.l10ns.VN))
                ? (flatpickr.l10ns.vn || flatpickr.l10ns.VN)
                : undefined;
            datePicker = flatpickr('#filter-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                locale: fpLocale,
                onChange: function (selectedDates) {
                    if (selectedDates.length === 2 || selectedDates.length === 0) {
                        table.draw();
                    }
                }
            });
        }

        $('#f-length').on('change', function () {
            table.page.len($(this).val()).draw();
        });
        const debouncedSearchDraw = debounce(function () { table.draw(); }, 280);

        $('#f-sort').on('change', function () { table.draw(); });
        $('#filter-keyword').on('input', debouncedSearchDraw);
        $('#filter-keyword').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                table.draw();
            }
        });
        $('#btn-clear').on('click', function () {
            $('#filter-keyword').val('');
            $('#f-sort').val('all');
            datePicker.clear();
            table.draw();
        });



        $('#order-history-table').on('click', '.js-view-order', function () {
            viewOrderDetail($(this).data('id'));
        });

        $('#order-history-table').on('click', '.js-delete-order', function () {
            deleteOrderHistory($(this).data('id'), table);
        });
    });

    function fmtMoney(v) {
        return new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + 'đ';
    }

    function getOrderStatusClass(status) {
        const normalized = String(status || '').trim().toLowerCase();
        if (normalized === 'completed') return 'is-completed';
        if (normalized === 'pending') return 'is-pending';
        if (normalized === 'processing') return 'is-processing';
        if (normalized === 'cancelled' || normalized === 'canceled' || normalized === 'failed') return 'is-cancelled';
        return 'is-default';
    }

    function renderOrderStatusBadge(label, status) {
        const text = String(label || status || '--');
        return '<span class="user-order-status ' + getOrderStatusClass(status) + '">' + escapeHtml(text) + '</span>';
    }

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

    async function copyToClipboard(text) {
        const value = String(text || '');
        if (!value) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                await navigator.clipboard.writeText(value);
                return;
            } catch (e) {
                // Fallback below
            }
        }

        const ta = document.createElement('textarea');
        ta.value = value;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            cache: 'no-store'
        }, options || {}));
        const raw = await response.text();
        let data = {};
        try {
            data = JSON.parse(String(raw || '').replace(/^\uFEFF/, '').trim() || '{}');
        } catch (e) {
            data = {};
        }
        if (!response.ok && (!data || typeof data !== 'object')) {
            throw new Error('Request failed');
        }
        return data || {};
    }

    async function viewOrderDetail(orderId) {
        const id = Number(orderId || 0);
        if (!id) return;

        try {
            SwalHelper.loading('Đang tải chi tiết đơn hàng...');
            const data = await fetchJson(BASE_URL + '/api/history-orders/detail/' + encodeURIComponent(id), { method: 'GET' });
            SwalHelper.closeLoading();

            if (!data.success || !data.order) {
                SwalHelper.error(data.message || 'Không thể tải chi tiết đơn hàng.');
                return;
            }

            const order = data.order;
            const detailHtml = ''
                + '<div class="user-order-detail">'
                + '<div class="user-order-detail__grid">'
                + '<div><div class="user-order-detail__label">Sản phẩm</div><div class="user-order-detail__value">' + escapeHtml(order.product_name || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Số lượng</div><div class="user-order-detail__value">' + escapeHtml(order.quantity || 0) + '</div></div>'
                + '<div><div class="user-order-detail__label">Mã đơn hàng</div><div class="user-order-detail__value">' + escapeHtml(order.order_code_short || order.order_code || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Trạng thái</div><div class="user-order-detail__value">' + renderOrderStatusBadge(order.status_label || order.status || '', order.status) + '</div></div>'
                + '<div><div class="user-order-detail__label">Thời gian</div><div class="user-order-detail__value">' + escapeHtml(order.created_at_display || order.created_at || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Thanh toán</div><div class="user-order-detail__value text-success fw-bold">' + fmtMoney(order.price || 0) + '</div></div>'
                + '</div>'
                + (order.customer_input && order.customer_input.trim() !== '' && order.customer_input.trim().toLowerCase() !== 'không có' ?
                    '<div class="user-order-detail__block"><div class="user-order-detail__label">Thông tin bạn gửi</div><div class="user-order-detail__textarea">' + nl2brSafe(order.customer_input) + '</div></div>'
                    : '')
                + '<div class="user-order-detail__block"><div class="user-order-detail__label">Nội dung bàn giao</div><div class="user-order-detail__textarea">' + nl2brSafe(order.delivery_content || 'Chưa có nội dung bàn giao') + '</div></div>'
                + (order.cancel_reason ? '<div class="user-order-detail__block"><div class="user-order-detail__label">Lý do hủy / phản hồi</div><div class="user-order-detail__textarea">' + nl2brSafe(order.cancel_reason) + '</div></div>' : '')
                + '</div>';

            Swal.fire({
                title: 'Chi tiết đơn hàng',
                html: detailHtml,
                width: 760,
                confirmButtonText: 'Đóng'
            });
        } catch (error) {
            SwalHelper.closeLoading();
            SwalHelper.error('Không thể tải chi tiết đơn hàng.');
        }
    }

    function deleteOrderHistory(orderId, table) {
        const id = Number(orderId || 0);
        if (!id) return;

        SwalHelper.confirmDelete(async function () {
            try {
                const formData = new FormData();
                formData.append('order_id', String(id));
                if (window.KS_CSRF_TOKEN) {
                    formData.append('csrf_token', window.KS_CSRF_TOKEN);
                }

                const data = await fetchJson(BASE_URL + '/api/history-orders/delete', {
                    method: 'POST',
                    body: formData
                });

                if (data && data.success) {
                    SwalHelper.toast(data.message || 'Đã xóa lịch sử đơn hàng', 'success');
                    if (table && table.ajax) {
                        table.ajax.reload(null, false);
                    }
                    return;
                }

                SwalHelper.error((data && data.message) || 'Không thể xóa lịch sử đơn hàng.');
            } catch (error) {
                SwalHelper.error('Không thể kết nối đến máy chủ!');
            }
        });
    }
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
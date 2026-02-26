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

<div class="profile-card">
    <div class="profile-card-header profile-card-header--with-actions">
        <div>
            <h5 class="text-dark mb-1">Lịch sử đơn hàng</h5>
            <div class="user-card-subtitle">Tra cứu đơn đã mua, xem chi tiết và tải nội dung bàn giao.</div>
        </div>
        <a href="<?= url('history-balance') ?>" class="btn btn-edit-profile">
            <i class="fas fa-wallet me-1"></i> Biến động số dư
        </a>
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
                    <button id="btn-clear" class="btn btn-outline-danger w-100 py-2" title="Xóa bộ lọc">
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

        <div class="table-responsive">
            <table id="order-history-table" class="table table-hover align-middle w-100 mb-0 user-history-table">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 text-nowrap text-center">MÃ ĐƠN HÀNG</th>
                        <th class="py-3 text-nowrap text-center">SẢN PHẨM</th>
                        <th class="py-3 text-nowrap text-center">SỐ LƯỢNG</th>
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

<script>
    $(document).ready(function () {
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
                    data: null,
                    className: 'text-center',
                    render: function (row) {
                        const code = escapeHtml(row.order_code_short || row.order_code || '');
                        return '<button type="button" class="order-code-copy js-copy-order" data-copy="' + code + '">' + code + '</button>';
                    }
                },
                {
                    data: 'product_name',
                    render: function (data, type, row) {
                        return '<div class="fw-semibold">' + escapeHtml(data || '') + '</div>'
                            + '<div class="small text-muted">' + escapeHtml(row.status_label || '') + '</div>';
                    }
                },
                { data: 'quantity', className: 'text-center fw-semibold' },
                {
                    data: 'payment',
                    className: 'text-center',
                    render: function (data) {
                        return '<span>' + fmtMoney(data) + '</span>';
                    }
                },
                { data: 'time_display', className: 'text-center small' },
                {
                    data: null,
                    className: 'text-center',
                    render: function (row) {
                        const id = Number(row.id || 0);
                        const downloadUrl = BASE_URL + '/history-orders/download/' + encodeURIComponent(id);
                        return ''
                            + '<button type="button" class="order-action-btn js-view-order" data-id="' + id + '" title="Xem chi tiết"><i class="fas fa-eye"></i></button>'
                            + '<a class="order-action-btn" href="' + downloadUrl + '" title="Tải nội dung"><i class="fas fa-download"></i></a>'
                            + '<button type="button" class="order-action-btn js-delete-order" data-id="' + id + '" title="Ẩn lịch sử"><i class="fas fa-trash"></i></button>';
                    }
                }
            ],
            order: [],
            ordering: false,
            pageLength: 10,
            dom: 't<"d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3"<"text-muted small"i><"d-flex align-items-center gap-3"p>>',
            language: {
                info: "Hiển thị _START_ - _END_ trong tổng số _TOTAL_ đơn hàng",
                infoEmpty: "Chưa có đơn hàng nào",
                emptyTable: "Không có dữ liệu đơn hàng",
                paginate: { next: "›", previous: "‹" }
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
        $('#f-sort').on('change', function () { table.draw(); });
        $('#filter-keyword').on('input', function () { table.draw(); });
        $('#btn-clear').on('click', function () {
            $('#filter-keyword').val('');
            $('#f-sort').val('all');
            datePicker.clear();
            table.draw();
        });

        $('#order-history-table').on('click', '.js-copy-order', async function () {
            const code = $(this).data('copy');
            try {
                await copyToClipboard(code);
                SwalHelper.toast('Đã sao chép mã đơn hàng', 'success');
            } catch (e) {
                SwalHelper.error('Không thể sao chép mã đơn hàng.');
            }
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
                + '<div><div class="user-order-detail__label">Mã đơn hàng</div><div class="user-order-detail__value">' + escapeHtml(order.order_code_short || order.order_code || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Trạng thái</div><div class="user-order-detail__value">' + escapeHtml(order.status || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Sản phẩm</div><div class="user-order-detail__value">' + escapeHtml(order.product_name || '') + '</div></div>'
                + '<div><div class="user-order-detail__label">Số lượng</div><div class="user-order-detail__value">' + escapeHtml(order.quantity || 0) + '</div></div>'
                + '<div><div class="user-order-detail__label">Thanh toán</div><div class="user-order-detail__value">' + fmtMoney(order.price || 0) + '</div></div>'
                + '<div><div class="user-order-detail__label">Thời gian</div><div class="user-order-detail__value">' + escapeHtml(order.created_at || '') + '</div></div>'
                + '</div>'
                + '<div class="user-order-detail__block"><div class="user-order-detail__label">Thông tin bạn gửi</div><div class="user-order-detail__textarea">' + nl2brSafe(order.customer_input || 'Không có') + '</div></div>'
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

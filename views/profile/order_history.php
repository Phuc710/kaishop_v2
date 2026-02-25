<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'datatables' => true,
        'flatpickr' => true,
        'interactive_bundle' => false,
    ]);
    require __DIR__ . '/../../hethong/head2.php';
    ?>
    <title>Lịch sử đơn hàng | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop'), ENT_QUOTES, 'UTF-8') ?>
    </title>
    <meta name="robots" content="noindex, nofollow">
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5" style="padding-top: 80px !important;">
            <div class="container user-page-container">
                <div class="row">
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = 'order-history';
                        require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <div class="col-lg-9 col-md-8">
                        <div class="profile-card">
                            <div class="profile-card-header profile-card-header--with-actions">
                                <div>
                                    <h1 class="h5 text-dark mb-1">Lịch sử đơn hàng</h1>
                                </div>
                            </div>

                            <div class="profile-card-body p-4">
                                <div class="mb-4">
                                    <div class="row g-2 mb-3 align-items-center">
                                        <div class="col-md-6 mb-2">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="fas fa-search"></i></span>
                                                <input type="text" id="filter-keyword"
                                                    class="form-control border-start-0 ps-0"
                                                    placeholder="Tìm mã đơn hàng hoặc tên sản phẩm...">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                        class="far fa-calendar-alt"></i></span>
                                                <input type="text" id="filter-date"
                                                    class="form-control border-start-0 ps-0 bg-white"
                                                    placeholder="Từ ngày - Đến ngày" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button id="btn-clear" class="btn btn-outline-danger w-100 shadow-sm py-2"
                                                title="Xóa bộ lọc">
                                                <i class="fas fa-trash me-1"></i> Xóa lọc
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center">
                                            <span class="text-secondary fw-bold small me-2 text-uppercase">Show :</span>
                                            <select id="f-length" class="form-select form-select-sm shadow-none"
                                                style="width: 70px;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <span class="text-secondary fw-bold small me-2 text-uppercase">Sort by
                                                date:</span>
                                            <select id="f-sort" class="form-select form-select-sm shadow-none"
                                                style="width: auto;">
                                                <option value="all">Tất cả</option>
                                                <option value="today">Hôm nay</option>
                                                <option value="7">7 ngày</option>
                                                <option value="30">30 ngày</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="order-history-table" class="table table-hover align-middle w-100 mb-0">
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
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <style>
        .order-code-copy {
            border: 1px solid #dbe3ee;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            cursor: pointer;
        }

        .order-code-copy:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #3730a3;
        }

        .order-action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
            margin: 0 2px;
            text-decoration: none;
            cursor: pointer;
        }

        .order-action-btn:hover {
            text-decoration: none;
            background: #f8fafc;
        }

        .order-action-btn.view {
            color: #0284c7;
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .order-action-btn.download {
            color: #0f766e;
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .order-action-btn.delete {
            color: #dc2626;
            border-color: #fecaca;
            background: #fef2f2;
        }
    </style>

    <script>
        $(document).ready(function () {
            let datePicker = { clear: function () { $('#filter-date').val(''); } };

            const table = $('#order-history-table').DataTable({
                serverSide: true,
                processing: false,
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
                        className: 'text-center text-nowrap',
                        width: '16%',
                        render: function (row) {
                            const shortCode = escapeHtml(row.order_code_short || row.order_code || '');
                            return '<button type="button" class="order-code-copy js-copy-order" ' +
                                'data-copy="' + shortCode + '" title="Sao chép mã đơn">' + shortCode + '</button>';
                        }
                    },
                    {
                        data: 'product_name',
                        className: 'text-start',
                        width: '28%',
                        render: function (data, type, row) {
                            const statusClass = row.status === 'completed'
                                ? 'success'
                                : (row.status === 'pending' ? 'warning' : (row.status === 'cancelled' ? 'danger' : 'secondary'));
                            return '<div class="fw-semibold text-dark">' + escapeHtml(data || '') + '</div>' +
                                '<div class="small text-' + statusClass + '">' + escapeHtml(row.status_label || '') + '</div>';
                        }
                    },
                    {
                        data: 'quantity',
                        className: 'text-center text-nowrap fw-semibold',
                        width: '10%'
                    },
                    {
                        data: 'payment',
                        className: 'text-center text-nowrap fw-semibold',
                        width: '14%',
                        render: function (data) {
                            return '<span style="color:#111827;">' + fmtMoney(data) + '</span>';
                        }
                    },
                    {
                        data: 'time_display',
                        className: 'text-center text-nowrap small',
                        width: '18%',
                        render: function (data) {
                            return '<span style="color:#111827;">' + escapeHtml(data || '') + '</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center text-nowrap',
                        width: '14%',
                        render: function (row) {
                            const id = Number(row.id || 0);
                            const downloadUrl = BASE_URL + '/history-orders/download/' + encodeURIComponent(id);
                            return ''
                                + '<button type="button" class="order-action-btn view js-view-order" data-id="' + id + '" title="Xem chi tiết"><i class="fas fa-eye"></i></button>'
                                + '<a class="order-action-btn download" href="' + downloadUrl + '" title="Tải về"><i class="fas fa-download"></i></a>'
                                + '<button type="button" class="order-action-btn delete js-delete-order" data-id="' + id + '" title="Xóa khỏi lịch sử"><i class="fas fa-trash"></i></button>';
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
                    zeroRecords: "Không tìm thấy đơn hàng phù hợp",
                    paginate: {
                        first: "Đầu",
                        last: "Cuối",
                        next: "â€º",
                        previous: "â€¹"
                    }
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

            $('#f-sort').on('change', function () {
                table.draw();
            });

            let searchTimer = null;
            $('#filter-keyword').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    table.draw();
                }, 250);
            });

            $('#btn-clear').on('click', function () {
                $('#filter-keyword').val('');
                $('#f-sort').val('all');
                datePicker.clear();
                table.draw();
            });

            $('#order-history-table').on('click', '.js-copy-order', async function () {
                const code = String($(this).data('copy') || '');
                if (!code) return;
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(code);
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = code;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        ta.remove();
                    }
                    toastMsg('Đã sao chép mã đơn hàng', 'success');
                } catch (e) {
                    toastMsg('Không thể sao chép mã đơn hàng', 'error');
                }
            });

            $('#order-history-table').on('click', '.js-view-order', function () {
                const id = Number($(this).data('id') || 0);
                if (!id) return;
                viewOrderDetail(id);
            });

            $('#order-history-table').on('click', '.js-delete-order', function () {
                const id = Number($(this).data('id') || 0);
                if (!id) return;
                deleteOrderHistory(id, table);
            });
        });

        function fmtMoney(value) {
            return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function toastMsg(msg, type) {
            if (window.SwalHelper && SwalHelper.toast) {
                SwalHelper.toast(msg, type || 'info');
                return;
            }
            if (window.Swal && Swal.fire) {
                Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: type || 'info', title: msg });
                return;
            }
            alert(msg);
        }

        function viewOrderDetail(id) {
            $.get(BASE_URL + '/api/history-orders/detail/' + encodeURIComponent(id), function (res) {
                if (!res || !res.success) {
                    toastMsg((res && res.message) || 'Không thể tải chi tiết đơn hàng.', 'error');
                    return;
                }

                const o = res.order || {};
                const delivery = String(o.delivery_content || '');
                const customerInput = String(o.customer_input || '');
                const cancelReason = String(o.cancel_reason || '');

                let html = '';
                html += '<div style="text-align:left">';
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;">';
                html += '<div><b>Mã đơn:</b> #' + escapeHtml(o.order_code_short || o.order_code || '') + '</div>';
                html += '<div><b>Trạng thái:</b> ' + escapeHtml(o.status || '') + '</div>';
                html += '<div><b>Sản phẩm:</b> ' + escapeHtml(o.product_name || '') + '</div>';
                html += '<div><b>Số lượng:</b> ' + escapeHtml(o.quantity || 1) + '</div>';
                html += '<div><b>Thanh toán:</b> ' + fmtMoney(o.price || 0) + '</div>';
                html += '<div style="grid-column:1 / -1;"><b>Thời gian:</b> ' + escapeHtml(o.created_at || '') + '</div>';
                html += '</div>';

                if (customerInput) {
                    html += '<div class="mt-3"><b>Thông tin bạn đã nhập</b></div>';
                    html += '<textarea readonly style="width:100%;min-height:100px;border:1px solid #ddd;border-radius:10px;padding:10px;">' + escapeHtml(customerInput) + '</textarea>';
                }
                if (delivery) {
                    html += '<div class="mt-3"><b>Nội dung bàn giao</b></div>';
                    html += '<textarea readonly style="width:100%;min-height:150px;border:1px solid #ddd;border-radius:10px;padding:10px;">' + escapeHtml(delivery) + '</textarea>';
                }
                if (cancelReason) {
                    html += '<div class="mt-3"><b>Lý do hủy / phản hồi</b></div>';
                    html += '<textarea readonly style="width:100%;min-height:90px;border:1px solid #ddd;border-radius:10px;padding:10px;">' + escapeHtml(cancelReason) + '</textarea>';
                }
                html += '</div>';

                Swal.fire({
                    title: 'Chi tiết đơn hàng',
                    html: html,
                    width: 900,
                    confirmButtonText: 'Đóng'
                });
            }).fail(function () {
                toastMsg('Lỗi kết nối máy chủ.', 'error');
            });
        }

        function deleteOrderHistory(id, table) {
            Swal.fire({
                title: 'Xóa đơn hàng khỏi lịch sử?',
                text: 'Đơn hàng sẽ bị ẩn khỏi lịch sử của bạn.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#ef4444'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                $.post(BASE_URL + '/api/history-orders/delete', {
                    order_id: id,
                    csrf_token: (window.KS_CSRF_TOKEN || '')
                }, function (res) {
                    if (res && res.success) {
                        toastMsg(res.message || 'Đã xóa đơn hàng', 'success');
                        table.draw(false);
                    } else {
                        toastMsg((res && res.message) || 'Không thể xóa đơn hàng.', 'error');
                    }
                }).fail(function () {
                    toastMsg('Lỗi kết nối máy chủ.', 'error');
                });
            });
        }
    </script>
</body>

</html>


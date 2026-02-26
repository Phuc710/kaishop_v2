<?php
/**
 * View: Nhật ký hệ thống (DataTables - unified, smart AJAX search)
 * Route: GET /admin/logs/activities | /admin/logs/balance-changes
 * Controller: JournalController
 *
 * Follows same DataTable pattern as giftcodes.php - No search button, auto-filter on keyup.
 * Supports ?user= param to auto-filter by username from User Edit page.
 */
$breadcrumbs = [
    ['label' => 'Nhật ký', 'url' => url('admin/logs/activities')],
    ['label' => $pageTitle ?? 'Nhật ký'],
];
$adminNeedsFlatpickr = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

// Read ?user= param (passed from User Edit Page buttons)
$prefilterUser = trim((string) ($_GET['user'] ?? ''));
$isPurchaseJournal = (($basePath ?? '') === 'admin/logs/buying');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">
                    <?= htmlspecialchars($cardTitle ?? 'DANH SÁCH'); ?>
                </h3>
            </div>

            <!-- Filter Bar -->
            <div class="dt-filters">
                <!-- Search Line -->
                <div class="row g-2 mb-3">
                    <div class="col-md-5 mb-2">
                        <input id="f-search" class="form-control form-control-sm" placeholder="Tìm kiếm tất cả..."
                            value="<?= htmlspecialchars($prefilterUser) ?>">
                    </div>

                    <?php if (!empty($showSeverityFilter)): ?>
                        <div class="filter-show ms-3" style="min-width: 150px;">
                            <span class="filter-label">MỨC ĐỘ:</span>
                            <select id="f-severity" class="filter-select flex-grow-1">
                                <option value="all">Tất cả</option>
                                <option value="INFO">INFO</option>
                                <option value="WARNING">WARNING</option>
                                <option value="DANGER">DANGER</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2 mb-2">
                        <input id="f-date" class="form-control form-control-sm" placeholder="Thời gian...">
                    </div>
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                            <i class="fas fa-trash"></i> Xóa lọc
                        </button>
                    </div>
                </div>

                <!-- Dropdown Line -->
                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">SHOW :</span>
                        <select id="f-length" class="filter-select flex-grow-1">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="filter-short justify-content-end">
                        <span class="filter-label">LỌC THEO NGÀY:</span>
                        <select id="f-sort" class="filter-select flex-grow-1">
                            <option value="all">Tất cả</option>
                            <option value="7">7 ngày</option>
                            <option value="15">15 ngày</option>
                            <option value="30">30 ngày</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="<?= htmlspecialchars($tableId ?? 'journalTable') ?>"
                        class="table text-nowrap table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <?php foreach (($columns ?? []) as $column): ?>
                                    <th
                                        class="<?= ($column['align'] ?? '') === 'center' ? 'text-center' : 'text-left'; ?> font-weight-bold align-middle">
                                        <?= htmlspecialchars(mb_strtoupper((string) ($column['label'] ?? ''), 'UTF-8')); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rows)): ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php foreach (($columns ?? []) as $column): ?>
                                            <?php
                                            $key = (string) ($column['key'] ?? '');
                                            $cell = $row[$key] ?? '--';
                                            $alignClass = ($column['align'] ?? '') === 'center' ? 'text-center' : 'text-left';
                                            ?>
                                            <td class="<?= $alignClass; ?> align-middle" <?= ($key === 'severity') ? 'data-severity="' . strip_tags($cell) . '"' : '' ?>><?= (string) $cell; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for JSON Payload -->
    <div class="modal fade" id="payloadModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-bug text-danger mr-2"></i>Chi tiết Payload</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
                    <pre><code id="payloadContent" class="text-dark"></code></pre>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isPurchaseJournal): ?>
        <div class="modal fade" id="purchaseOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-shopping-bag mr-2"></i>Chi tiết đơn hàng</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="purchaseOrderModalLoading" class="text-center py-3 d-none">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...
                        </div>
                        <div id="purchaseOrderModalError" class="alert alert-danger d-none mb-3"></div>
                        <div id="purchaseOrderModalContent" class="d-none">
                            <div id="purchaseOrderMeta" class="mb-3"></div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Thông tin khách đã gửi</label>
                                <textarea id="purchaseOrderCustomerInput" class="form-control" rows="5" readonly></textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Nội dung bàn giao / trả code</label>
                                <textarea id="purchaseOrderDeliveryContent" class="form-control" rows="7"
                                    placeholder="Nhập code/nội dung trả lại cho khách..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-danger" id="btnCancelOrderSubmit">
                            <i class="fas fa-undo mr-1"></i> Hủy đơn + Hoàn tiền
                        </button>
                        <button type="button" class="btn btn-success" id="btnFulfillOrderSubmit">
                            <i class="fas fa-paper-plane mr-1"></i> Giao hàng & Hoàn tất
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    let dt;
    const TABLE_ID = '<?= htmlspecialchars($tableId ?? 'journalTable') ?>';
    // Detect which column index has time data (look for column key = 'time')
    const TIME_COL_INDEX = <?php
    $timeIdx = 0;
    foreach (($columns ?? []) as $i => $col) {
        if (($col['key'] ?? '') === 'time') {
            $timeIdx = $i;
            break;
        }
    }
    echo $timeIdx;
    ?>;

    // Detect which column index has severity data
    const SEVERITY_COL_INDEX = <?php
    $sevIdx = -1;
    foreach (($columns ?? []) as $i => $col) {
        if (($col['key'] ?? '') === 'severity') {
            $sevIdx = $i;
            break;
        }
    }
    echo $sevIdx;
    ?>;
    const IS_PURCHASE_JOURNAL = <?= $isPurchaseJournal ? 'true' : 'false' ?>;
    const PURCHASE_DETAIL_BASE_URL = '<?= url('admin/logs/buying/detail') ?>';
    const PURCHASE_FULFILL_URL = '<?= url('admin/logs/buying/fulfill') ?>';
    const PURCHASE_CANCEL_URL = '<?= url('admin/logs/buying/cancel') ?>';
    const CSRF_TOKEN = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_UNESCAPED_UNICODE) ?>;
    let purchaseModalCurrentOrderId = 0;

    document.addEventListener("DOMContentLoaded", function () {
        let checkExist = setInterval(function () {
            if (window.jQuery && $.fn.DataTable) {
                clearInterval(checkExist);
                initJournalTable();
            }
        }, 100);
    });

    function initJournalTable() {
        dt = $('#' + TABLE_ID).DataTable({
            dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
            responsive: true,
            autoWidth: false,
            order: [[TIME_COL_INDEX, "desc"]],
            pageLength: 20,
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: '<div class="text-center w-100 font-weight-bold py-3">Không tìm thấy dữ liệu</div>',
                sInfo: 'Xem _START_ - _END_ / _TOTAL_ mục',
                sInfoEmpty: 'Xem 0-0 / 0 mục',
                sInfoFiltered: '(lọc từ _MAX_)',
                sSearch: 'Tìm nhanh:',
                oPaginate: { sPrevious: '&lsaquo;', sNext: '&rsaquo;' }
            }
        });

        // Pre-filter by ?user= param if present
        var prefilter = '<?= addslashes($prefilterUser) ?>';
        if (prefilter) {
            dt.search(prefilter).draw();
        }

        // Date Picker initialization (Flatpickr)
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#f-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        dt.draw();
                    }
                },
                onReady: function (selectedDates, dateStr, instance) {
                    const clearBtn = document.createElement('div');
                    clearBtn.className = 'flatpickr-clear-btn mt-2 text-center text-danger';
                        clearBtn.innerHTML = '<span style="cursor:pointer;font-weight:bold;">Xóa lựa chọn</span>';
                    clearBtn.onclick = function () {
                        instance.clear();
                        dt.draw();
                    };
                    instance.calendarContainer.appendChild(clearBtn);
                }
            });
        }

        // Smart Search â€” no button, auto-filter on keyup
        $('#f-search').on('input keyup', function () {
            dt.search($(this).val().trim()).draw();
        });

        // Dropdown Page Length
        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

        // Dropdown Severity Filter
        $('#f-severity').change(function () {
            var val = $(this).val();
            if (SEVERITY_COL_INDEX >= 0) {
                if (val === 'all') {
                    dt.column(SEVERITY_COL_INDEX).search('').draw();
                } else {
                    dt.column(SEVERITY_COL_INDEX).search('^' + val + '$', true, false).draw();
                }
            }
        });

        // Sort by date dropdown
        $('#f-sort').change(function () {
            dt.draw();
        });

        // Clear All Filters
        $('#btn-clear').click(function () {
            $('#f-search, #f-date').val('');
            $('#f-length').val('20');
            $('#f-sort').val('all');
            $('#f-severity').val('all');
            dt.search('').columns().search('');
            dt.page.len(20).order([TIME_COL_INDEX, 'desc']).draw();
        });

        // Date Range Custom Filter (7/15/30 days + daterangepicker)
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                if (settings.nTable.id !== TABLE_ID) return true;

                // Sort by dropdown (7, 15, 30 days)
                var sortVal = $('#f-sort').val();
                if (sortVal !== 'all') {
                    var days = parseInt(sortVal);
                    if (!isNaN(days)) {
                        var rowTime = new Date(data[TIME_COL_INDEX]).getTime();
                        var pastTime = new Date().getTime() - (days * 24 * 60 * 60 * 1000);
                        if (rowTime < pastTime) return false;
                    }
                }

                // DateRangePicker / Flatpickr
                var dr = $('#f-date').val();
                if (!dr) return true;

                var separator = dr.includes(' to ') ? ' to ' : ' - ';
                var range = dr.split(separator);
                if (range.length !== 2) return true;

                var min = new Date(range[0] + ' 00:00:00').getTime();
                var max = new Date(range[1] + ' 23:59:59').getTime();
                var timeCol = new Date(data[TIME_COL_INDEX]).getTime();

                if (isNaN(min) || isNaN(max) || isNaN(timeCol)) return true;
                return timeCol >= min && timeCol <= max;
            }
        );

        // Tooltips
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        if (IS_PURCHASE_JOURNAL) {
            initPurchaseOrderActions();
        }
    }

    function initPurchaseOrderActions() {
        $(document).on('click', '.js-order-view, .js-order-fulfill, .js-order-cancel', function () {
            var orderId = Number($(this).data('order-id') || 0);
            var openFulfillMode = $(this).hasClass('js-order-fulfill');
            if (!orderId) return;
            openPurchaseOrderModal(orderId, openFulfillMode);
        });

        $('#btnFulfillOrderSubmit').on('click', function () {
            submitPurchaseFulfill();
        });

        $('#btnCancelOrderSubmit').on('click', function () {
            submitPurchaseCancel();
        });
    }

    function setPurchaseModalLoading(isLoading) {
        $('#purchaseOrderModalLoading').toggleClass('d-none', !isLoading);
        $('#purchaseOrderModalContent').toggleClass('d-none', isLoading);
    }

    function setPurchaseModalError(message) {
        if (message) {
            $('#purchaseOrderModalError').removeClass('d-none').text(message);
        } else {
            $('#purchaseOrderModalError').addClass('d-none').text('');
        }
    }

    function fillPurchaseOrderModal(order, openFulfillMode) {
        order = order || {};
        purchaseModalCurrentOrderId = Number(order.id || 0);

        var metaHtml = ''
            + '<div class=\"row\">'
            + '<div class=\"col-md-6 mb-2\"><b>Mã đơn:</b> <span class=\"badge bg-light text-dark border\">' + escapeHtml(order.order_code_short || order.order_code || '-') + '</span></div>'
            + '<div class=\"col-md-6 mb-2\"><b>Trạng thái:</b> ' + escapeHtml(order.status || '-') + '</div>'
            + '<div class=\"col-md-6 mb-2\"><b>Khách hàng:</b> ' + escapeHtml(order.username || '-') + '</div>'
            + '<div class=\"col-md-6 mb-2\"><b>Số lượng:</b> ' + escapeHtml(order.quantity || 1) + '</div>'
            + '<div class=\"col-md-12 mb-2\"><b>Sản phẩm:</b> ' + escapeHtml(order.product_name || '-') + '</div>'
            + '</div>';
        $('#purchaseOrderMeta').html(metaHtml);
        $('#purchaseOrderCustomerInput').val(order.customer_input || '');
        var userMessage = '';
        if (order.status === 'cancelled' && order.cancel_reason) {
            userMessage = order.cancel_reason;
        } else {
            userMessage = order.delivery_content || '';
        }
        $('#purchaseOrderDeliveryContent').val(userMessage);

        var canFulfill = ['pending', 'processing'].indexOf(String(order.status || '')) !== -1;
        var canCancel = String(order.status || '') === 'pending';
        $('#purchaseOrderDeliveryContent').prop('readonly', !canFulfill);
        $('#btnFulfillOrderSubmit, #btnCancelOrderSubmit').prop('disabled', false);
        $('#btnFulfillOrderSubmit').toggle(canFulfill);
        $('#btnCancelOrderSubmit').toggle(canCancel);
        if (openFulfillMode && canFulfill) {
            setTimeout(function () {
                var input = document.getElementById('purchaseOrderDeliveryContent');
                if (input) input.focus();
            }, 150);
        }

        if (order.cancel_reason && order.status === 'cancelled') {
            setPurchaseModalError('Lý do hủy: ' + String(order.cancel_reason));
        }
    }

    function openPurchaseOrderModal(orderId, openFulfillMode) {
        if (!IS_PURCHASE_JOURNAL) return;
        purchaseModalCurrentOrderId = Number(orderId || 0);
        setPurchaseModalError('');
        setPurchaseModalLoading(true);
        $('#btnFulfillOrderSubmit, #btnCancelOrderSubmit').prop('disabled', true).hide();
        $('#purchaseOrderModal').modal('show');

        fetch(PURCHASE_DETAIL_BASE_URL + '/' + encodeURIComponent(orderId), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(async function (res) {
                var data = {};
                try { data = await res.json(); } catch (e) { }
                if (!res.ok || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Không tải được chi tiết đơn hàng.');
                }
                return data.order || {};
            })
            .then(function (order) {
                setPurchaseModalLoading(false);
                fillPurchaseOrderModal(order, openFulfillMode);
            })
            .catch(function (err) {
                setPurchaseModalLoading(false);
                $('#purchaseOrderModalContent').addClass('d-none');
                setPurchaseModalError((err && err.message) ? err.message : 'Không tải được chi tiết đơn hàng.');
            });
    }

    function submitPurchaseFulfill() {
        if (!IS_PURCHASE_JOURNAL) return;
        var orderId = Number(purchaseModalCurrentOrderId || 0);
        var deliveryContent = String($('#purchaseOrderDeliveryContent').val() || '').trim();
        if (!orderId) return;
        if (!deliveryContent) {
            setPurchaseModalError('Vui lòng nhập nội dung bàn giao.');
            return;
        }

        setPurchaseModalError('');
        $('#btnFulfillOrderSubmit').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Đang xử lý...');

        var formData = new FormData();
        formData.append('order_id', String(orderId));
        formData.append('delivery_content', deliveryContent);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch(PURCHASE_FULFILL_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF_TOKEN },
            body: formData
        })
            .then(async function (res) {
                var data = {};
                try { data = await res.json(); } catch (e) { }
                if (!res.ok || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Không thể giao hàng lúc này.');
                }
                return data;
            })
            .then(function (data) {
                if (window.Swal && Swal.fire) {
                    Swal.fire({ icon: 'success', title: 'Thành công', text: data.message || 'Đã giao hàng.' });
                }
                $('#purchaseOrderModal').modal('hide');
                window.location.reload();
            })
            .catch(function (err) {
                setPurchaseModalError((err && err.message) ? err.message : 'Không thể giao hàng lúc này.');
            })
            .finally(function () {
                $('#btnFulfillOrderSubmit').prop('disabled', false).html('<i class=\"fas fa-paper-plane mr-1\"></i> Giao hàng & Hoàn tất');
            });
    }

    function submitPurchaseCancel() {
        if (!IS_PURCHASE_JOURNAL) return;
        var orderId = Number(purchaseModalCurrentOrderId || 0);
        var cancelReason = String($('#purchaseOrderDeliveryContent').val() || '').trim();
        if (!orderId) return;
        if (!cancelReason) {
            setPurchaseModalError('Vui lòng nhập nội dung hủy/phản hồi cho user.');
            return;
        }

        var proceed = function () {
            setPurchaseModalError('');
            $('#btnCancelOrderSubmit').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Đang hủy...');

            var formData = new FormData();
            formData.append('order_id', String(orderId));
            formData.append('cancel_reason', cancelReason);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch(PURCHASE_CANCEL_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': CSRF_TOKEN },
                body: formData
            })
                .then(async function (res) {
                    var data = {};
                    try { data = await res.json(); } catch (e) { }
                    if (!res.ok || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Không thể hủy đơn lúc này.');
                    }
                    return data;
                })
                .then(function (data) {
                    if (window.Swal && Swal.fire) {
                        Swal.fire({ icon: 'success', title: 'Thành công', text: data.message || 'Đã hủy đơn và hoàn tiền.' });
                    }
                    $('#purchaseOrderModal').modal('hide');
                    window.location.reload();
                })
                .catch(function (err) {
                    setPurchaseModalError((err && err.message) ? err.message : 'Không thể hủy đơn lúc này.');
                })
                .finally(function () {
                    $('#btnCancelOrderSubmit').prop('disabled', false).html('<i class=\"fas fa-undo mr-1\"></i> Hủy đơn + Hoàn tiền');
                });
        };

        if (window.Swal && Swal.fire) {
            Swal.fire({
                title: 'Hủy đơn pending và hoàn tiền?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hủy đơn',
                cancelButtonText: 'Đóng'
            }).then(function (result) {
                if (result.isConfirmed) {
                    proceed();
                }
            });
            return;
        }

        if (window.confirm('Hủy đơn pending và hoàn tiền cho user?')) {
            proceed();
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showPayloadModal(payloadData) {
        try {
            let parsed = typeof payloadData === 'string' ? JSON.parse(payloadData) : payloadData;
            document.getElementById('payloadContent').textContent = JSON.stringify(parsed, null, 4);
            $('#payloadModal').modal('show');
        } catch (e) {
            document.getElementById('payloadContent').textContent = payloadData;
            $('#payloadModal').modal('show');
        }
    }
</script>


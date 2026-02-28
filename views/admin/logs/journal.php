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
    ['label' => 'Nhật ký', 'url' => url('admin/logs/buying')],
    ['label' => $pageTitle ?? 'Nhật ký'],
];
$adminNeedsFlatpickr = true;
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

// Read ?search= or ?user= param (passed from User Edit Page buttons)
$prefilterUser = trim((string) ($_GET['search'] ?? $_GET['user'] ?? ''));
$isPurchaseJournal = (($basePath ?? '') === 'admin/logs/buying');
$prefilterOrderStatus = trim((string) ($_GET['order_status'] ?? 'all'));
if (!in_array($prefilterOrderStatus, ['all', 'pending', 'processing', 'completed', 'cancelled'], true)) {
    $prefilterOrderStatus = 'all';
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    .badge-soft-success {
        background-color: #ecfdf5 !important;
        color: #059669 !important;
        border: 1px solid #10b981 !important;
    }

    .badge-soft-danger {
        background-color: #fef2f2 !important;
        color: #dc2626 !important;
        border: 1px solid #f87171 !important;
    }

    .badge-soft-warning {
        background-color: #fffbeb !important;
        color: #d97706 !important;
        border: 1px solid #fbbf24 !important;
    }

    .badge-pill {
        border-radius: 50rem !important;
        padding: 0.35em 0.8em !important;
        font-weight: 600 !important;
    }
</style>

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
                    <?php if ($isPurchaseJournal): ?>
                        <div class="col-md-2 mb-2">
                            <select id="f-order-status" class="form-control form-control-sm">
                                <option value="all" <?= $prefilterOrderStatus === 'all' ? 'selected' : '' ?>>Tất cả trạng thái
                                </option>
                                <option value="pending" <?= $prefilterOrderStatus === 'pending' ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="processing" <?= $prefilterOrderStatus === 'processing' ? 'selected' : '' ?>>Đang
                                    xử lý</option>
                                <option value="completed" <?= $prefilterOrderStatus === 'completed' ? 'selected' : '' ?>>Hoàn
                                    tất</option>
                                <option value="cancelled" <?= $prefilterOrderStatus === 'cancelled' ? 'selected' : '' ?>>Đã hủy
                                </option>
                            </select>
                        </div>
                    <?php endif; ?>

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
                        class="table table-hover table-bordered w-100">
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

                            <!-- Stock Hint -->
                            <div id="purchaseOrderStockHint" class="alert alert-info py-2 mb-3 d-none">
                                <i class="fas fa-key mr-1"></i><b>Nội dung kho đã gán sẵn:</b>
                                <div id="purchaseOrderStockContent" class="mt-1"
                                    style="font-family:monospace;white-space:pre-wrap;font-size:12px;"></div>
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
    const TABLE_ID = '<?= htmlspecialchars($tableId ?? "journalTable") ?>';
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
    const STATUS_COL_INDEX = <?php
    $statusIdx = -1;
    foreach (($columns ?? []) as $i => $col) {
        if (($col['key'] ?? '') === 'status') {
            $statusIdx = $i;
            break;
        }
    }
    echo $statusIdx;
    ?>;
    const IS_PURCHASE_JOURNAL = <?= $isPurchaseJournal ? 'true' : 'false' ?>;
    const PURCHASE_DETAIL_BASE_URL = '<?= url("admin/logs/buying/detail") ?>';
    const PURCHASE_FULFILL_URL = '<?= url("admin/logs/buying/fulfill") ?>';
    const PURCHASE_CANCEL_URL = '<?= url("admin/logs/buying/cancel") ?>';
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

        var prefilter = '<?= addslashes($prefilterUser) ?>';
        if (prefilter) {
            dt.search(prefilter).draw();
        }
        if (IS_PURCHASE_JOURNAL && STATUS_COL_INDEX >= 0) {
            applyPurchaseStatusFilter();
        }

        if (typeof flatpickr !== 'undefined') {
            flatpickr('#f-date', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        dt.draw();
                    }
                }
            });
        }

        $('#f-search').on('input keyup', function () {
            dt.search($(this).val().trim()).draw();
        });

        $('#f-length').change(function () {
            dt.page.len($(this).val()).draw();
        });

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

        $('#f-order-status').change(function () {
            applyPurchaseStatusFilter();
        });

        $('#f-sort').change(function () {
            dt.draw();
        });

        $('#btn-clear').click(function () {
            $('#f-search, #f-date').val('');
            $('#f-length').val('20');
            $('#f-sort').val('all');
            $('#f-order-status').val('all');
            dt.search('').columns().search('').page.len(20).draw();
        });

        if (IS_PURCHASE_JOURNAL) {
            initPurchaseOrderActions();
        }
    }

    function applyPurchaseStatusFilter() {
        if (!IS_PURCHASE_JOURNAL || STATUS_COL_INDEX < 0) return;
        var val = String($('#f-order-status').val() || 'all');
        if (val === 'all') {
            dt.column(STATUS_COL_INDEX).search('').draw();
        } else {
            dt.column(STATUS_COL_INDEX).search(val).draw();
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
            + '<div class="row">'
            + '<div class="col-md-6 mb-2"><b>Mã đơn:</b> <span class="badge bg-light text-dark border">' + escapeHtml(order.order_code_short || order.order_code || '-') + '</span></div>'
            + '<div class="col-md-6 mb-2"><b>Trạng thái:</b> ' + escapeHtml(order.status || '-') + '</div>'
            + '<div class="col-md-6 mb-2"><b>Khách hàng:</b> ' + escapeHtml(order.username || '-') + '</div>'
            + '<div class="col-md-6 mb-2"><b>Số lượng:</b> ' + escapeHtml(order.quantity || 1) + '</div>'
            + '<div class="col-md-12 mb-2"><b>Sản phẩm:</b> ' + escapeHtml(order.product_name || '-') + '</div>'
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

        var hasStock = !!order.delivery_content && order.status === 'pending';
        $('#purchaseOrderStockHint').toggleClass('d-none', !hasStock);
        if (hasStock) {
            $('#purchaseOrderStockContent').text(order.delivery_content);
        }

        var canFulfill = ['pending', 'processing'].indexOf(String(order.status || '')) !== -1;
        var canCancel = String(order.status || '') === 'pending';
        $('#purchaseOrderDeliveryContent').prop('readonly', !canFulfill);
        $('#btnFulfillOrderSubmit').toggle(canFulfill);
        $('#btnCancelOrderSubmit').toggle(canCancel);

        if (openFulfillMode && canFulfill) {
            setTimeout(function () {
                $('#purchaseOrderDeliveryContent').focus();
            }, 150);
        }
    }

    function openPurchaseOrderModal(orderId, openFulfillMode) {
        if (!IS_PURCHASE_JOURNAL) return;
        setPurchaseModalError('');
        setPurchaseModalLoading(true);
        $('#purchaseOrderModal').modal('show');

        fetch(PURCHASE_DETAIL_BASE_URL + '/' + encodeURIComponent(orderId))
            .then(res => res.json())
            .then(data => {
                setPurchaseModalLoading(false);
                if (data.success) {
                    fillPurchaseOrderModal(data.order, openFulfillMode);
                } else {
                    setPurchaseModalError(data.message);
                }
            })
            .catch(err => {
                setPurchaseModalLoading(false);
                setPurchaseModalError('Lỗi kết nối server.');
            });
    }

    function submitPurchaseFulfill() {
        var orderId = purchaseModalCurrentOrderId;
        var content = $('#purchaseOrderDeliveryContent').val().trim();
        if (!content) return setPurchaseModalError('Nhập nội dung!');

        var formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('delivery_content', content);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch(PURCHASE_FULFILL_URL, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $('#purchaseOrderModal').modal('hide');
                    location.reload();
                } else {
                    setPurchaseModalError(data.message);
                }
            });
    }

    function submitPurchaseCancel() {
        var orderId = purchaseModalCurrentOrderId;
        var reason = $('#purchaseOrderDeliveryContent').val().trim();
        if (!reason) return setPurchaseModalError('Nhập lý do!');

        var formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('cancel_reason', reason);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch(PURCHASE_CANCEL_URL, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $('#purchaseOrderModal').modal('hide');
                    location.reload();
                } else {
                    setPurchaseModalError(data.message);
                }
            });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showPayloadModal(payloadData) {
        try {
            var parsed = typeof payloadData === 'string' ? JSON.parse(payloadData) : payloadData;
            document.getElementById('payloadContent').textContent = JSON.stringify(parsed, null, 4);
            $('#payloadModal').modal('show');
        } catch (e) {
            document.getElementById('payloadContent').textContent = payloadData;
            $('#payloadModal').modal('show');
        }
    }
</script>
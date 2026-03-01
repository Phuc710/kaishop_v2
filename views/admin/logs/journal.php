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

    .purchase-order-modal .modal-dialog {
        max-width: 980px;
    }

    .purchase-order-modal .modal-content {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
    }

    .purchase-order-modal .modal-header {
        border-bottom: 0;
        padding: 14px 20px;
    }

    .purchase-order-modal .modal-body {
        padding: 20px;
        background: #f8fafc;
    }

    .purchase-order-shell {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .purchase-order-time {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
        max-width: 100%;
        border: 1px solid #dbe2ea;
        background: #ffffff;
        border-radius: 10px;
        padding: 8px 12px;
        font-weight: 600;
    }

    .purchase-order-time-label {
        color: #1f2937;
    }

    .purchase-order-time-value {
        color: #0f172a;
        font-weight: 500;
    }

    .purchase-order-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .purchase-order-meta-item {
        background: #ffffff;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 10px 12px;
        min-height: 76px;
    }

    .purchase-order-meta-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        font-weight: 700;
    }

    .purchase-order-meta-value {
        color: #1e293b;
        font-weight: 600;
        word-break: break-word;
    }

    .purchase-order-card {
        background: #ffffff;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 12px;
        height: 100%;
    }

    .purchase-order-card-title {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 8px;
        font-weight: 700;
    }

    .purchase-order-card-content {
        color: #0f172a;
        white-space: pre-wrap;
        word-break: break-word;
        min-height: 66px;
    }

    .purchase-order-product-row {
        background: #ffffff;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 12px;
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) repeat(2, minmax(0, 1fr));
        gap: 10px;
        align-items: center;
    }

    .purchase-order-product-cell {
        min-width: 0;
        word-break: break-word;
    }

    .purchase-order-product-cell b {
        color: #0f172a;
    }

    .purchase-order-product-info {
        color: #1e293b;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .purchase-order-modal .modal-footer {
        border-top: 0;
        padding: 14px 20px 18px;
        background: #ffffff;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    @media (max-width: 991.98px) {
        .purchase-order-meta-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .purchase-order-product-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .purchase-order-modal .modal-body {
            padding: 14px;
        }

        .purchase-order-meta-grid {
            grid-template-columns: 1fr;
        }

        .purchase-order-modal .modal-footer {
            padding: 12px 14px 14px;
        }
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
        <div class="modal fade purchase-order-modal" id="purchaseOrderModal" tabindex="-1" role="dialog"
            aria-hidden="true">
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
                        <div id="purchaseOrderModalContent" class="purchase-order-shell d-none">
                            <div class="purchase-order-time">
                                <span class="purchase-order-time-label">Đặt:</span>
                                <span id="purchaseOrderCreatedAt" class="purchase-order-time-value">-</span>
                            </div>

                            <div class="purchase-order-meta-grid">
                                <div class="purchase-order-meta-item">
                                    <div class="purchase-order-meta-label">Mã đơn</div>
                                    <div class="purchase-order-meta-value">
                                        <span id="purchaseOrderCodeBadge" class="badge bg-light text-dark border"
                                            style="font-family:monospace;">-</span>
                                    </div>
                                </div>
                                <div class="purchase-order-meta-item">
                                    <div class="purchase-order-meta-label">Người mua</div>
                                    <div id="purchaseOrderBuyer" class="purchase-order-meta-value">-</div>
                                </div>
                                <div class="purchase-order-meta-item">
                                    <div class="purchase-order-meta-label">Trạng thái</div>
                                    <div id="purchaseOrderStatus" class="purchase-order-meta-value">-</div>
                                </div>
                                <div class="purchase-order-meta-item">
                                    <div class="purchase-order-meta-label">Số lượng</div>
                                    <div id="purchaseOrderQuantity" class="purchase-order-meta-value">1</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 mb-2" id="purchaseOrderRequestCol">
                                    <div class="purchase-order-card">
                                        <div class="purchase-order-card-title" id="purchaseOrderRequestTitle">Thông tin yêu
                                            cầu</div>
                                        <div id="purchaseOrderRequest" class="purchase-order-card-content">-</div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-2" id="purchaseOrderDeliveryCol">
                                    <div class="purchase-order-card">
                                        <div class="purchase-order-card-title" id="purchaseOrderDeliveryTitle">Nội dung giao
                                            hàng</div>
                                        <div id="purchaseOrderDelivery" class="purchase-order-card-content">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="purchase-order-product-row">
                                <div class="purchase-order-product-cell">
                                    <b>Sản phẩm:</b> <span id="purchaseOrderProductName">-</span>
                                </div>
                                <div class="purchase-order-product-cell">
                                    <b>Giá mua:</b> <span id="purchaseOrderPrice">0đ</span>
                                </div>
                                <div class="purchase-order-product-cell">
                                    <b id="purchaseOrderExtraLabel">Loại:</b> <span id="purchaseOrderExtraValue">-</span>
                                </div>
                            </div>

                            <div class="purchase-order-card" id="purchaseOrderModeInfoCard">
                                <div class="purchase-order-card-title" id="purchaseOrderModeInfoTitle">Thông tin theo loại
                                    sản phẩm</div>
                                <div id="purchaseOrderProductInfo" class="purchase-order-product-info">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-dismiss="modal">Đóng</button>
                        <a href="#" target="_blank" id="purchaseOrderOpenStockBtn" class="btn btn-primary disabled"
                            aria-disabled="true">
                            <i class="fas fa-warehouse mr-1"></i> Xem chi tiết trong kho
                        </a>
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
        $(document).on('click', '.js-order-view', function () {
            var orderId = Number($(this).data('order-id') || 0);
            if (!orderId) return;
            openPurchaseOrderModal(orderId);
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

    function fillPurchaseOrderModal(order) {
        order = order || {};

        var orderCode = String(order.order_code_short || order.order_code || '-');
        var customerInput = String(order.customer_input || '').trim();
        var deliveryContent = String(order.delivery_content || '').trim();
        if (String(order.status || '') === 'cancelled' && String(order.cancel_reason || '').trim() !== '') {
            deliveryContent = String(order.cancel_reason || '').trim();
        }

        $('#purchaseOrderCreatedAt').text(String(order.created_at_display || order.created_at || '-'));
        $('#purchaseOrderCodeBadge').text(orderCode);
        $('#purchaseOrderBuyer').text(String(order.username || '-'));
        $('#purchaseOrderRequest').html(nl2brEscape(customerInput !== '' ? customerInput : 'Không có'));
        $('#purchaseOrderDelivery').html(nl2brEscape(deliveryContent !== '' ? deliveryContent : 'Chưa có'));
        $('#purchaseOrderStatus').html(getOrderStatusBadgeHtml(String(order.status || '')));
        $('#purchaseOrderProductName').text(String(order.product_name || '-'));
        $('#purchaseOrderQuantity').text(String(order.quantity || 1));
        $('#purchaseOrderPrice').text(formatVnd(order.price || 0));
        applyPurchaseModeUi(order, customerInput, deliveryContent);
        setPurchaseOrderStockLink(order.stock_url || '');
    }

    function applyPurchaseModeUi(order, customerInput, deliveryContent) {
        var product = order.product || {};
        var mode = resolvePurchaseDeliveryMode(product);
        var sourceLink = String(product.source_link || '').trim();
        var productInstructions = String(product.info_instructions || '').trim();

        var ui = {
            requestTitle: 'Thông tin yêu cầu',
            requestText: customerInput !== '' ? customerInput : 'Không có',
            showRequest: customerInput !== '',
            deliveryTitle: 'Nội dung giao hàng',
            deliveryText: deliveryContent !== '' ? deliveryContent : 'Chưa có',
            showDelivery: true,
            extraLabel: 'Loại',
            extraValue: String(product.delivery_label || '-') || '-',
            modeInfoTitle: 'Thông tin theo loại sản phẩm',
            modeInfoHtml: '',
        };

        if (mode === 'manual_info') {
            ui.requestTitle = 'Yêu cầu từ khách';
            ui.requestText = customerInput !== '' ? customerInput : 'Khách chưa gửi yêu cầu.';
            ui.showRequest = true;
            ui.deliveryTitle = 'Phản hồi / giao hàng';
            ui.deliveryText = deliveryContent !== '' ? deliveryContent : 'Chưa phản hồi.';
            ui.extraValue = 'Yêu cầu thông tin';
            ui.modeInfoTitle = 'Thông tin sản phẩm yêu cầu';

            var manualLines = [];
            manualLines.push('<div><b>Loại bàn giao:</b> Yêu cầu thông tin</div>');
            if (productInstructions !== '') {
                manualLines.push('<div class="mt-1"><b>Mẫu yêu cầu:</b> ' + nl2brEscape(productInstructions) + '</div>');
            }
            ui.modeInfoHtml = manualLines.join('');
        } else if (mode === 'source_link') {
            ui.requestTitle = 'Thông tin bổ sung';
            ui.requestText = customerInput !== '' ? customerInput : 'Không có thông tin bổ sung.';
            ui.showRequest = customerInput !== '';
            ui.deliveryTitle = 'Source';
            ui.deliveryText = deliveryContent !== '' ? deliveryContent : (sourceLink !== '' ? sourceLink : 'Chưa có source.');
            ui.extraValue = 'Source';
            ui.modeInfoTitle = 'Thông tin source';

            var sourceLines = [];
            sourceLines.push('<div><b>Loại bàn giao:</b> Source</div>');
            if (sourceLink !== '') {
                sourceLines.push('<div class="mt-1"><b>Source:</b> <code class="text-dark">' + escapeHtml(sourceLink) + '</code></div>');
            }
            ui.modeInfoHtml = sourceLines.join('');
        } else {
            ui.requestTitle = 'Thông tin mua hàng';
            ui.requestText = customerInput !== '' ? customerInput : 'Không có thông tin yêu cầu.';
            ui.showRequest = customerInput !== '';
            ui.deliveryTitle = 'Tài khoản';
            ui.deliveryText = deliveryContent !== '' ? deliveryContent : 'Chưa bàn giao tài khoản.';
            ui.extraValue = 'Tài khoản';
            ui.modeInfoTitle = 'Thông tin tài khoản';

            var accountLines = [];
            accountLines.push('<div><b>Loại bàn giao:</b> Tài khoản</div>');
            if (deliveryContent !== '') {
                accountLines.push('<div class="mt-1"><b>Tài khoản đã giao:</b> Có dữ liệu bàn giao.</div>');
            }
            ui.modeInfoHtml = accountLines.join('');
        }

        $('#purchaseOrderRequestTitle').text(ui.requestTitle);
        $('#purchaseOrderRequest').html(nl2brEscape(ui.requestText));
        $('#purchaseOrderDeliveryTitle').text(ui.deliveryTitle);
        $('#purchaseOrderDelivery').html(nl2brEscape(ui.deliveryText));
        $('#purchaseOrderExtraLabel').text(ui.extraLabel + ':');
        $('#purchaseOrderExtraValue').text(ui.extraValue);
        $('#purchaseOrderModeInfoTitle').text(ui.modeInfoTitle);
        $('#purchaseOrderProductInfo').html(ui.modeInfoHtml !== '' ? ui.modeInfoHtml : '<span class="text-muted">Không có thêm thông tin.</span>');
        $('#purchaseOrderModeInfoCard').toggleClass('d-none', ui.modeInfoHtml === '');

        applyPurchaseContentColumns(ui.showRequest, ui.showDelivery);
    }

    function resolvePurchaseDeliveryMode(product) {
        var p = product || {};
        var mode = String(p.delivery_mode || '').trim();
        if (mode !== '') return mode;

        var type = String(p.product_type || '').trim();
        if (type === 'link') return 'source_link';
        if (Number(p.requires_info || 0) === 1) return 'manual_info';
        return 'account_stock';
    }

    function applyPurchaseContentColumns(showRequest, showDelivery) {
        var requestCol = $('#purchaseOrderRequestCol');
        var deliveryCol = $('#purchaseOrderDeliveryCol');

        requestCol.toggleClass('d-none', !showRequest);
        deliveryCol.toggleClass('d-none', !showDelivery);

        requestCol.removeClass('col-12').addClass('col-lg-6');
        deliveryCol.removeClass('col-12').addClass('col-lg-6');

        if (showRequest && !showDelivery) {
            requestCol.removeClass('col-lg-6').addClass('col-12');
        }
        if (!showRequest && showDelivery) {
            deliveryCol.removeClass('col-lg-6').addClass('col-12');
        }
    }

    function setPurchaseOrderStockLink(stockUrl) {
        var btn = $('#purchaseOrderOpenStockBtn');
        var href = String(stockUrl || '').trim();
        if (href === '') {
            btn.addClass('disabled').attr('aria-disabled', 'true').attr('href', '#');
            return;
        }
        btn.removeClass('disabled').removeAttr('aria-disabled').attr('href', href);
    }

    function getOrderStatusBadgeHtml(status) {
        var st = String(status || '').toLowerCase();
        if (st === 'completed') return '<span class="badge badge-pill badge-soft-success">Hoàn tất</span>';
        if (st === 'cancelled') return '<span class="badge badge-pill badge-soft-danger">Đã hủy</span>';
        if (st === 'pending') return '<span class="badge badge-pill badge-soft-warning">Pending</span>';
        if (st === 'processing') return '<span class="badge badge-pill badge-soft-warning">Đang xử lý</span>';
        return '<span class="badge bg-secondary">' + escapeHtml(status || '-') + '</span>';
    }

    function formatVnd(amount) {
        var num = Number(amount || 0);
        if (Number.isNaN(num)) num = 0;
        return num.toLocaleString('vi-VN') + 'đ';
    }

    function nl2brEscape(text) {
        return escapeHtml(String(text || '')).replace(/\r?\n/g, '<br>');
    }

    function openPurchaseOrderModal(orderId) {
        if (!IS_PURCHASE_JOURNAL) return;
        setPurchaseModalError('');
        setPurchaseModalLoading(true);
        $('#purchaseOrderModal').modal('show');

        fetch(PURCHASE_DETAIL_BASE_URL + '/' + encodeURIComponent(orderId))
            .then(res => res.json())
            .then(data => {
                setPurchaseModalLoading(false);
                if (data.success) {
                    fillPurchaseOrderModal(data.order);
                } else {
                    setPurchaseModalError(data.message);
                }
            })
            .catch(err => {
                setPurchaseModalLoading(false);
                setPurchaseModalError('Lỗi kết nối server.');
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

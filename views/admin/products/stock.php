<?php
/**
 * View: Quản lý kho sản phẩm (tài khoản)
 * Route: GET /admin/products/stock/{id}
 * Controller: AdminProductController@stock
 */
$pageTitle = 'Kho – ' . htmlspecialchars($product['name']);
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => htmlspecialchars($product['name']), 'url' => url('admin/products/edit/' . $product['id'])],
    ['label' => 'Kho'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$timeService = class_exists('TimeService') ? TimeService::instance() : null;

?>
<style>
    .content-wrapper {
        margin-right: 0 !important;
    }

    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .custom-card {
        border-left: 0;
        border-right: 0;
        border-radius: 0;
        border-top: 1px solid var(--card-border);
    }
</style>

<section class="content pb-4 mt-1">
    <div class="container-fluid px-0">

        <!-- STATS -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-info elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span
                            class="info-box-text font-weight-bold text-uppercase"><?= !empty($isManualQueue) ? 'TỔNG ĐƠN' : 'TỔNG KHO' ?></span>
                        <span class="info-box-number h4 mb-0" id="stat-total"><?= $stats['total'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-success elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span
                            class="info-box-text font-weight-bold text-uppercase"><?= !empty($isManualQueue) ? 'CHỜ XỬ LÝ' : 'CÒN LẠI' ?></span>
                        <span class="info-box-number h4 mb-0" id="stat-available"><?= $stats['available'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
                    <span class="info-box-icon bg-danger elevation-1" style="border-radius: 8px;"><i
                            class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span
                            class="info-box-text font-weight-bold text-uppercase"><?= !empty($isManualQueue) ? 'ĐÃ XỬ LÝ' : 'ĐÃ BÁN' ?></span>
                        <span class="info-box-number h4 mb-0" id="stat-sold"><?= $stats['sold'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">DANH SÁCH TRONG KHO</h3>
            </div>

            <!-- BỘ LỌC -->
            <div class="dt-filters">
                <div class="row g-2 justify-content-start align-items-center mb-3">
                    <!-- Search Input -->
                    <div class="col-md-2 mb-2">
                        <input id="searchTerm" class="form-control form-control-sm"
                            placeholder="<?= !empty($isManualQueue) ? 'Tìm kiếm tất cả...' : 'Tìm kiếm...' ?>"
                            value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2 mb-2">
                        <select id="filterStatus" class="form-control form-control-sm">
                            <option value="">-- Trạng thái --</option>
                            <?php if (!empty($isManualQueue)): ?>
                                <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Đang chờ
                                    xử lý</option>
                                <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>Hoàn
                                    tất giao hàng</option>
                                <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy
                                    đơn</option>
                            <?php else: ?>
                                <option value="available" <?= ($statusFilter ?? '') === 'available' ? 'selected' : '' ?>>Còn
                                    trong kho</option>
                                <option value="sold" <?= ($statusFilter ?? '') === 'sold' ? 'selected' : '' ?>>Đã bán thành
                                    công</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <div class="col-md-2 mb-2 text-center">
                        <button type="button" id="btnClearFilters" class="btn btn-danger btn-sm shadow-sm w-100"
                            title="Xóa toàn bộ lọc">
                            Xóa Lọc
                        </button>
                    </div>

                    <?php if (empty($isManualQueue)): ?>
                        <div class="col-md-2 mb-2">
                            <button type="button" id="btnClean" class="btn btn-danger btn-sm shadow-sm w-100"
                                title="Xóa toàn bộ hàng chưa bán">
                                <i class="fas fa-eraser mr-1"></i> Dọn kho
                            </button>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="button" class="btn btn-primary btn-sm shadow-sm w-100 font-weight-bold"
                                onclick="$('#importModal').modal('show')">
                                <i class="fas fa-plus mr-1"></i> Thêm sản phẩm
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="top-filter mb-2">
                    <div class="filter-show">
                        <span class="filter-label">Hiển thị :</span>
                        <select id="filterLimit" class="filter-select flex-grow-1">
                            <option value="10" <?= ($limit ?? 20) == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ($limit ?? 20) == 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ($limit ?? 20) == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= ($limit ?? 20) == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>

                    <div class="filter-short justify-content-end">
                        <span class="filter-label">Lọc theo ngày:</span>
                        <select id="filterPredefinedDate" class="filter-select flex-grow-1">
                            <option value="all" <?= ($dateFilter ?? '') === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="7" <?= ($dateFilter ?? '') === '7' ? 'selected' : '' ?>>7 ngày qua</option>
                            <option value="15" <?= ($dateFilter ?? '') === '15' ? 'selected' : '' ?>>15 ngày qua</option>
                            <option value="30" <?= ($dateFilter ?? '') === '30' ? 'selected' : '' ?>>30 ngày qua</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="stockTable" class="table table-hover table-bordered admin-table w-100">
                        <thead>
                            <tr>
                                <?php if (!empty($isSourceHistory)): ?>
                                    <th class="text-center font-weight-bold align-middle" style="width:100px">MÃ ĐƠN</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:150px">NGÀY MUA</th>
                                    <th class="text-center font-weight-bold align-middle">NGƯỜI MUA</th>
                                    <th class="text-center font-weight-bold align-middle">LINK</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:140px">THAO TÁC</th>
                                <?php elseif (!empty($isManualQueue)): ?>
                                    <th class="text-center font-weight-bold align-middle" style="width:100px">MÃ ĐƠN</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:150px">NGƯỜI MUA</th>
                                    <th class="text-center font-weight-bold align-middle">THÔNG TIN YÊU CẦU</th>
                                    <th class="text-center font-weight-bold align-middle">NỘI DUNG GIAO HÀNG</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:120px">TRẠNG THÁI
                                    </th>
                                    <th class="text-center font-weight-bold align-middle" style="width:140px">THAO TÁC</th>
                                <?php else: ?>
                                    <th class="text-center font-weight-bold align-middle" style="width:100px">MÃ ĐƠN</th>
                                    <th class="text-center font-weight-bold align-middle">NỘI DUNG KHO</th>
                                    <th class="text-center font-weight-bold align-middle">NGƯỜI MUA</th>
                                    <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                    <th class="text-center font-weight-bold align-middle">NGÀY NHẬP</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:140px">THAO TÁC</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="stockBody" style="opacity: 1;">
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="<?= !empty($isSourceHistory) ? '5' : '6' ?>"
                                        class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                        <?= !empty($isManualQueue) ? 'Không có đơn hàng nào cần giải' : 'Kho hiện đang trống' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php include __DIR__ . '/stock/' . basename($partialView) . '.php'; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FULFILL MODAL -->
<div class="modal fade" id="fulfillModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header bg-success text-white"
                style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-paper-plane mr-2"></i>GIAO ĐƠN HÀNG: <span
                        id="fulfillCode"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="fulfillForm">
                    <input type="hidden" name="order_id" id="fulfillOrderId">

                    <div class="info-callout bg-light p-3 rounded mb-4 border-left"
                        style="border-left-width: 4px !important; border-left-color: #28a745 !important;">
                        <div class="small font-weight-bold text-uppercase text-muted mb-1">Thông tin khách yêu cầu:
                        </div>
                        <div id="fulfillCustomerInput" class="text-dark font-weight-bold"
                            style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fas fa-reply mr-1"></i> Nội dung bàn
                            giao:</label>
                        <textarea name="delivery_content" id="deliveryContent" class="form-control" rows="6"
                            placeholder="Nhập nội dung/tài khoản/thông tin để gửi cho khách..." required></textarea>
                        <small class="form-text text-muted italic">Nội dung này sẽ hiển thị trong lịch sử mua hàng của
                            khách.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light px-4 py-3"
                style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Đóng</button>
                <button type="button" id="btnConfirmFulfill" class="btn btn-success px-5 font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> XÁC NHẬN GIAO ĐƠN
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CANCEL MODAL -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header bg-danger text-white"
                style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-times-circle mr-2"></i>HỦY ĐƠN HÀNG: <span
                        id="cancelCode"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="cancelForm">
                    <input type="hidden" id="cancelOrderId">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fas fa-comment-dots mr-1"></i> Lý do hủy
                            (gửi khách):</label>
                        <textarea id="cancelReason" class="form-control" rows="4"
                            placeholder="VD: Sản phẩm hiện đang bảo trì, vui lòng quay lại sau..." required></textarea>
                        <small class="form-text text-danger italic"><i class="fas fa-info-circle mr-1"></i> Đơn hàng này
                            sẽ được HOÀN TIỀN tự động cho người mua.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light px-4 py-3"
                style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Đóng</button>
                <button type="button" id="btnConfirmCancel" class="btn btn-danger px-5 font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> XÁC NHẬN HỦY & HOÀN TIỀN
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-upload mr-2"></i> NHẬP HÀNG MỚI</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-4">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <label class="font-weight-bold text-dark mb-0">Danh sach noi dung kho</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#stockFile').click()">
                            <i class="fas fa-file-import mr-1"></i>Chọn từ file (.txt)
                        </button>
                        <input type="file" id="stockFile" style="display:none;" accept=".txt"
                            onchange="handleStockFile(this)">
                    </div>
                    <textarea id="importContent" class="form-control border-primary" rows="12"
                        placeholder="Noi dung giao 1&#10;Noi dung giao 2&#10;Noi dung giao 3"
                        style="font-family: 'Courier New', Courier, monospace; font-size: 14px; border-width: 2px;"></textarea>
                </div>

                <div id="importResult" class="mb-4" style="display:none;"></div>

                <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Hủy bỏ</button>
                    <button id="btnImport" class="btn btn-primary btn-lg px-5 font-weight-bold shadow">
                        <i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT STOCK -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog border-0" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning py-2">
                <h5 class="modal-title font-weight-bold">SỬA THÔNG TIN</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editId">
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-dark mb-2">Nội dung</label>
                    <textarea id="editContent" class="form-control" rows="3" placeholder="user:pass..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-dismiss="modal">Hủy</button>
                <button type="button" id="btnSaveEdit" class="btn btn-warning font-weight-bold px-5 shadow-sm">LƯU CẬP
                    NHẬT</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    $(function () {
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
        const importUrl = '<?= url("admin/products/stock/" . $product['id'] . "/import") ?>';
        const deleteUrl = '<?= url("admin/products/stock/delete") ?>';
        const updateUrl = '<?= url("admin/products/stock/update") ?>';
        const cleanUrl = '<?= url("admin/products/stock/" . $product['id'] . "/clean") ?>';
        const baseUrl = '<?= url("admin/products/stock/" . $product['id']) ?>';

        window.handleStockFile = function (input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#importContent').val(e.target.result);
                Toast.fire({ icon: 'success', title: 'Đã tải nội dung từ file' });
            };
            reader.readAsText(file);
            input.value = '';
        };

        // Copy content
        $(document).on('click', '.copy-content-btn', function () {
            const content = $(this).data('content');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(content).then(() => {
                    Toast.fire({ icon: 'success', title: 'Đã copy nội dung' });
                });
            } else {
                const temp = $("<input>");
                $("body").append(temp);
                temp.val(content).select();
                document.execCommand("copy");
                temp.remove();
                Toast.fire({ icon: 'success', title: 'Đã copy nội dung' });
            }
        });

        // Import
        $('#btnImport').on('click', function () {
            const content = $('#importContent').val().trim();
            if (!content) { Toast.fire({ icon: 'warning', title: 'Vui lòng nhập danh sách!' }); return; }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...');

            $.post(importUrl, { content: content }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO');
                if (res.success) {
                    $('#importContent').val('');
                    $('#importResult').html(`<div class="alert alert-success border-left">✅ <b>Thành công!</b> Đã nhập <b>${res.added}</b> item mới. Bỏ qua <b>${res.skipped}</b> dòng trùng.</div>`).show();
                    Toast.fire({ icon: 'success', title: 'Đã nhập thành công ' + res.added + ' items' });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                }
            }, 'json').fail(() => {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-2"></i> BẮT ĐẦU NHẬP KHO');
                Toast.fire({ icon: 'error', title: 'Lỗi server!' });
            });
        });

        // Delete
        $(document).on('click', '.delete-stock-btn', function () {
            const id = $(this).data('id');
            Swal.fire({ title: 'Xác nhận xóa?', text: 'Mục này sẽ bị xóa vĩnh viễn khỏi kho!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Hủy', confirmButtonText: 'Xác nhận xóa' })
                .then(r => {
                    if (!r.isConfirmed) return;
                    $.post(deleteUrl, { id: id }, function (res) {
                        if (res.success) {
                            $('#stock-row-' + id).fadeOut(300, function () { $(this).remove(); });
                            Toast.fire({ icon: 'success', title: 'Đã xóa' });
                        } else {
                            Toast.fire({ icon: 'error', title: res.message });
                        }
                    }, 'json');
                });
        });

        // Edit Modal
        $(document).on('click', '.edit-stock-btn', function () {
            $('#editId').val($(this).data('id'));
            $('#editContent').val($(this).data('content'));
            $('#editStockModal').modal('show');
        });

        // Save Edit
        $('#btnSaveEdit').on('click', function () {
            const id = $('#editId').val();
            const content = $('#editContent').val().trim();
            if (!content) {
                Toast.fire({ icon: 'warning', title: 'Nội dung không được để trống' });
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).text('Đang lưu...');
            $.post(updateUrl, { id: id, content: content }, function (res) {
                btn.prop('disabled', false).text('LƯU CẬP NHẬT');
                if (res.success) {
                    $('#editStockModal').modal('hide');
                    $(`#stock-row-${id} code`).text(content);
                    $(`#stock-row-${id} .copy-content-btn`).data('content', content);
                    $(`.edit-stock-btn[data-id="${id}"]`).data('content', content);
                    Toast.fire({ icon: 'success', title: 'Đã cập nhật' });
                } else {
                    Toast.fire({ icon: 'error', title: res.message });
                }
            }, 'json').fail(function (xhr) {
                btn.prop('disabled', false).text('LÆ¯U Cáº¬P NHáº¬T');

                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Lỗi cập nhật nội dung kho';

                Toast.fire({ icon: 'error', title: message });
            });
        });

        // Debounce search
        let searchTimer;

        function smartSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const status = $('#filterStatus').val();
                const search = $('#searchTerm').val().trim();
                const limit = $('#filterLimit').val();
                const dateFilter = $('#filterPredefinedDate').val();

                let startDate = '';
                let endDate = '';


                $('#stockBody').css('opacity', '0.5');

                $.get(window.location.href, {
                    status_filter: status,
                    search: search,
                    limit: limit,
                    date_filter: dateFilter,
                    start_date: startDate,
                    end_date: endDate
                }, function (res) {
                    $('#stockBody').css('opacity', '1');
                    if (res.success) {
                        renderStockTable(res.items, res.isManualQueue);
                        renderStats(res.stats, res.isManualQueue);
                    }
                }, 'json');
            }, 300);
        }

        // Listeners for new filters
        $('#filterStatus, #filterLimit, #filterPredefinedDate').on('change', smartSearch);
        $('#searchTerm').on('input', smartSearch);

        $('#btnClearFilters').on('click', function () {
            $('#searchTerm').val('');
            $('#filterStatus').val('');
            $('#filterLimit').val('20');
            $('#filterPredefinedDate').val('all');
            if (typeof datePicker !== 'undefined' && datePicker) datePicker.clear();
            smartSearch();
        });

        function renderStockTable(items, isManualQueue) {
            let html = '';
            if (!items || items.length === 0) {
                const emptyMsg = isManualQueue ? 'Không có đơn hàng nào' : 'Kho hiện đang trống';
                const colCount = isManualQueue ? 7 : 5;
                html = `<tr><td colspan="${colCount}" class="text-center text-muted py-4"><i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>${emptyMsg}</td></tr>`;
            } else {
                items.forEach(item => {
                    if (isManualQueue) {
                        const statusBadge = item.status === 'pending'
                            ? '<span class="badge badge-warning">Pending</span>'
                            : (item.status === 'completed' ? '<span class="badge badge-success">Xong</span>' : '<span class="badge badge-danger">Hủy</span>');

                        const deliveryDiv = item.status === 'completed'
                            ? `<div class="mt-1 small"><b class="text-success">Giao:</b> <span class="text-muted">${escapeHtml(item.stock_content_plain || '—')}</span></div>`
                            : '';

                        let actionBtns = '';
                        if (item.status === 'pending') {
                            actionBtns = `<div class="btn-group">
                                <button class="btn btn-success btn-sm btn-fulfill" data-id="${item.id}" data-code="${escapeHtml(item.order_code_short || item.order_code)}" data-input="${escapeHtml(item.customer_input || '')}" title="Giao đơn (Gửi thông tin)"><i class="fas fa-paper-plane mr-1"></i> Giao</button>
                                <button class="btn btn-danger btn-sm btn-cancel-order" data-id="${item.id}" data-code="${escapeHtml(item.order_code_short || item.order_code)}" title="Hủy đơn + Hoàn tiền">Hủy</button>
                               </div>`;
                        } else if (item.status === 'completed') {
                            actionBtns = `<button class="btn btn-info btn-sm btn-fulfill" data-id="${item.id}" data-code="${escapeHtml(item.order_code_short || item.order_code)}" data-input="${escapeHtml(item.customer_input || '')}" data-content="${escapeHtml(item.stock_content_plain || '')}" title="Sửa nội dung (Bảo hành)"><i class="fas fa-edit mr-1"></i> Sửa</button>`;
                        } else {
                            actionBtns = '<button class="btn btn-light btn-sm text-muted" disabled><i class="fas fa-check"></i></button>';
                        }

                        html += `<tr>
                            <td class="text-center align-middle"><span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">${escapeHtml(item.order_code_short || item.order_code)}</span></td>
                            <td class="text-center align-middle"><a href="<?= url('admin/users/edit/') ?>${item.username}" class="font-weight-bold text-primary">${escapeHtml(item.username)}</a></td>
                            <td class="align-middle"><div class="small"><b class="text-dark">Yêu cầu:</b> <span class="text-muted">${escapeHtml(item.customer_input || 'N/A')}</span></div>${deliveryDiv}</td>
                            <td class="text-center align-middle">${statusBadge}</td>
                            <td class="text-center align-middle">
                                <span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="${item.created_at_ago || ''}">${item.created_at_display || item.created_at || '—'}</span>
                            </td>
                            <td class="text-center align-middle">
                                ${item.status === 'completed' && item.fulfilled_at
                                ? `<span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="${item.fulfilled_at_ago || ''}">${item.fulfilled_at_display || item.fulfilled_at}</span>`
                                : '<span class="text-muted small">—</span>'}
                            </td>
                            <td class="text-center align-middle">${actionBtns}</td>
                        </tr>`;
                    } else {
                        const statusBadge = item.status === 'available'
                            ? '<span class="badge badge-success px-2 py-1">CÒN HÀNG</span>'
                            : '<span class="badge badge-secondary px-2 py-1">ĐÃ BÁN</span>';

                        const buyerLink = item.buyer_username
                            ? `<a href="<?= url('admin/users/edit/') ?>${item.buyer_username}" class="d-inline-flex align-items-center text-primary font-weight-bold">${escapeHtml(item.buyer_username)}</a>`
                            : '<span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa bán</span>';

                        const soldTime = (item.status === 'sold' && item.sold_at)
                            ? `<div class="mt-1 small"><span class="text-danger font-weight-bold"><i class="far fa-clock mr-1"></i>Bán lúc:</span> <span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="${item.sold_at_ago || ''}">${item.sold_at_display || item.sold_at}</span></div>`
                            : '';

                        const deleteBtn = item.status === 'available'
                            ? `<button class="btn btn-danger btn-sm ml-1 delete-stock-btn" data-id="${item.id}" title="Xóa"><i class="fas fa-trash"></i></button>`
                            : '';

                        html += `<tr id="stock-row-${item.id}" class="${item.status === 'sold' ? 'table-light' : ''}">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <code class="p-1 px-2 border rounded bg-white text-dark mr-2" style="font-size: 14px;">${escapeHtml(item.content)}</code>
                                    <button class="btn btn-xs btn-outline-info copy-content-btn" data-content="${escapeHtml(item.content)}" title="Copy"><i class="far fa-copy"></i></button>
                                </div>
                                ${soldTime}
                            </td>
                            <td class="text-center align-middle">${buyerLink}</td>
                            <td class="text-center align-middle">${statusBadge}</td>
                            <td class="text-center align-middle">
                                <span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="${item.created_at_ago || ''}">${item.created_at_display || item.created_at || '—'}</span>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group">
                                    <button class="btn btn-search-dt btn-sm edit-stock-btn" data-id="${item.id}" data-content="${escapeHtml(item.content)}" title="${item.status === 'available' ? 'Sửa' : 'Sửa bảo hành'}"><i class="fas fa-edit"></i></button>
                                    ${deleteBtn}
                                </div>
                            </td>
                        </tr>`;
                    }
                });
            }
            $('#stockBody').html(html);
            $('[data-toggle="tooltip"]').tooltip();
        }

        const actionUrl = '<?= url('admin/products/stock/action/' . $product['id']) ?>';

        function performStockAction(action, id, data = {}, callback = null) {
            const postData = { action: action, id: id, ...data };
            $.post(actionUrl, postData, function (res) {
                if (res.success) {
                    if (callback) callback(res);
                    else {
                        Toast.fire({ icon: 'success', title: res.message || 'Thành công' });
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                }
            }, 'json').fail(function (xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối máy chủ';
                Swal.fire('Lỗi', msg, 'error');
            });
        }

        // DELETE STOCK
        $(document).on('click', '.delete-stock-btn', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Dữ liệu này sẽ bị xóa khỏi kho!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Đồng ý xóa'
            }).then((result) => {
                if (result.isConfirmed) {
                    performStockAction('delete', id);
                }
            });
        });

        // EDIT STOCK (Open Modal)
        $(document).on('click', '.edit-stock-btn', function () {
            const id = $(this).data('id');
            const content = $(this).data('content');
            $('#editId').val(id);
            $('#editContent').val(content);
            $('#editStockModal').modal('show');
        });

        // SAVE EDIT
        $('#btnSaveEdit').click(function () {
            const id = $('#editId').val();
            const content = $('#editContent').val().trim();
            if (!content) return Swal.fire('Lỗi', 'Nội dung không được để trống', 'error');

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            performStockAction('update', id, { content: content }, () => {
                $('#editStockModal').modal('hide');
                Toast.fire({ icon: 'success', title: 'Đã cập nhật' });
                setTimeout(() => location.reload(), 800);
            });
        });

        // OPEN FULFILL MODAL
        $(document).on('click', '.btn-fulfill', function () {
            const id = $(this).data('id');
            const code = $(this).data('code');
            const input = $(this).data('input');
            const content = $(this).data('content') || '';

            $('#fulfillOrderId').val(id);
            $('#fulfillCode').text(code);
            $('#fulfillCustomerInput').text(input || 'Không có thông tin yêu cầu.');
            $('#deliveryContent').val(content);

            const isEdit = content !== '';
            $('#fulfillModal .modal-header').toggleClass('bg-success', !isEdit).toggleClass('bg-info', isEdit);
            $('#fulfillModal .modal-title').html(isEdit
                ? `<i class="fas fa-edit mr-2"></i>SỬA NỘI DUNG (BẢO HÀNH): ${code}`
                : `<i class="fas fa-paper-plane mr-2"></i>GIAO ĐƠN HÀNG: ${code}`
            );
            $('#btnConfirmFulfill').toggleClass('btn-success', !isEdit).toggleClass('btn-info', isEdit)
                .html(isEdit ? '<i class="fas fa-check-circle mr-1"></i> CẬP NHẬT BẢO HÀNH' : '<i class="fas fa-check-circle mr-1"></i> XÁC NHẬN GIAO ĐƠN');

            $('#fulfillModal').modal('show');
        });

        // CONFIRM FULFILL
        $('#btnConfirmFulfill').click(function () {
            const content = $('#deliveryContent').val().trim();
            if (!content) return Swal.fire('Lỗi', 'Vui lòng nhập nội dung bàn giao!', 'error');

            const id = $('#fulfillOrderId').val();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');

            performStockAction('fulfill', id, { content: content }, () => {
                $('#fulfillModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã xử lý đơn hàng!', timer: 1500 });
                setTimeout(() => location.reload(), 1500);
            });
        });

        // OPEN CANCEL MODAL
        $(document).on('click', '.btn-cancel-order', function () {
            const id = $(this).data('id');
            const code = $(this).data('code');
            $('#cancelOrderId').val(id);
            $('#cancelCode').text(code);
            $('#cancelReason').val('');
            $('#cancelModal').modal('show');
        });

        // CONFIRM CANCEL
        $('#btnConfirmCancel').click(function () {
            const reason = $('#cancelReason').val().trim();
            if (!reason) return Swal.fire('Lỗi', 'Vui lòng nhập lý do hủy đơn!', 'error');

            const id = $('#cancelOrderId').val();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');

            performStockAction('cancel', id, { reason: reason }, () => {
                $('#cancelModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Đã hủy đơn', text: 'Đã hủy và hoàn tiền!', timer: 1500 });
                setTimeout(() => location.reload(), 1500);
            });
        });

        function renderStats(stats, isManualQueue) {
            $('#stat-total').text(stats.total);
            $('#stat-available').text(stats.available);
            $('#stat-sold').text(stats.sold);

            // Dynamically update labels if needed (optional since PHP already handles first load)
            $('.info-box-text').eq(0).text(isManualQueue ? 'TỔNG ĐƠN' : 'TỔNG KHO');
            $('.info-box-text').eq(1).text(isManualQueue ? 'CHỜ XỬ LÝ' : 'CÒN LẠI');
            $('.info-box-text').eq(2).text(isManualQueue ? 'ĐÃ XỬ LÝ' : 'ĐÃ BÁN');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Apply filters (AJAX)
        $('#filterStatus').on('change', smartSearch);
        $('#searchTerm').on('input', smartSearch);

        // Clean
        $('#btnClean').on('click', function () {
            Swal.fire({
                title: 'Dọn sạch kho?',
                text: 'Toàn bộ mục CHƯA BÁN sẽ bị xóa vĩnh viễn!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Đồng ý, Xóa hết',
                cancelButtonText: 'Hủy'
            }).then(r => {
                if (!r.isConfirmed) return;
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>...');

                performStockAction('clean', <?= (int) $product['id'] ?>, {}, () => {
                    Toast.fire({ icon: 'success', title: 'Đã dọn sạch kho' });
                    setTimeout(() => location.reload(), 1200);
                });
            });
        });

        // Init tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
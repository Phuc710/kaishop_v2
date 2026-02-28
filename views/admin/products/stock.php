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

<section class="content pb-4 mt-1">
    <div class="container-fluid">

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

            <!-- Filter Bar -->
            <div class="dt-filters">
                <div class="row g-2 mb-3">
                    <div class="col-md-<?= !empty($isManualQueue) ? '9' : '5' ?> mb-2">
                        <input id="searchTerm" class="form-control form-control-sm"
                            placeholder="<?= !empty($isManualQueue) ? 'Tìm mã đơn, tên khách, nội dung...' : 'Tim noi dung trong kho...' ?>"
                            value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-2 text-center">
                        <select id="filterStatus" class="form-control form-control-sm">
                            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>-- TRẠNG THÁI --</option>
                            <?php if (!empty($isManualQueue)): ?>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>CHỜ XỬ LÝ</option>
                                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>HOÀN TẤT
                                </option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>ĐÃ HỦY
                                </option>
                            <?php else: ?>
                                <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>CÒN HÀNG
                                </option>
                                <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>ĐÃ BÁN</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if (empty($isManualQueue)): ?>
                        <div class="col-md-2 mb-2 text-center">
                            <button type="button" id="btnClean" class="btn btn-danger btn-sm shadow-sm w-100"
                                title="Xóa toàn bộ hàng chưa bán">
                                <i class="fas fa-eraser"></i> Dọn kho
                            </button>
                        </div>
                        <div class="col-md-2 mb-2 text-center">
                            <button type="button" class="btn btn-primary btn-sm shadow-sm w-100 font-weight-bold"
                                onclick="$('#importModal').modal('show')">
                                <i class="fas fa-plus-circle mr-1"></i> Thêm vào kho
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="stockTable" class="table table-hover table-bordered w-100">
                        <thead>
                            <tr>
                                <?php if (!empty($isManualQueue)): ?>
                                    <th class="text-center font-weight-bold align-middle" style="width:100px">MÃ ĐƠN</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:150px">NGƯỜI MUA</th>
                                    <th class="text-center font-weight-bold align-middle">YÊU CẦU / NỘI DUNG</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:120px">TRẠNG THÁI
                                    </th>
                                    <th class="text-center font-weight-bold align-middle" style="width:150px">THỜI GIAN</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:120px">THAO TÁC</th>
                                <?php else: ?>
                                    <th class="text-center font-weight-bold align-middle">NOI DUNG KHO</th>
                                    <th class="text-center font-weight-bold align-middle">NGƯỜI MUA</th>
                                    <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                    <th class="text-center font-weight-bold align-middle">NGÀY NHẬP</th>
                                    <th class="text-center font-weight-bold align-middle" style="width:120px">THAO TÁC</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="<?= !empty($isManualQueue) ? '6' : '5' ?>"
                                        class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                        <?= !empty($isManualQueue) ? 'Không có đơn hàng nào cần giải' : 'Kho hiện đang trống' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <?php if (!empty($isManualQueue)): ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-light text-dark border font-weight-bold"
                                                    style="font-family:monospace;">
                                                    <?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <a href="<?= url('admin/users/edit/' . $item['username']) ?>"
                                                    class="font-weight-bold text-primary">
                                                    <?= htmlspecialchars($item['username']) ?>
                                                </a>
                                            </td>
                                            <td class="align-middle">
                                                <div class="small">
                                                    <b class="text-dark">Yêu cầu:</b>
                                                    <span
                                                        class="text-muted"><?= htmlspecialchars($item['customer_input'] ?: 'N/A') ?></span>
                                                </div>
                                                <?php if ($item['status'] === 'completed'): ?>
                                                    <div class="mt-1 small">
                                                        <b class="text-success">Giao:</b>
                                                        <span
                                                            class="text-muted"><?= htmlspecialchars($item['stock_content_plain'] ?: '—') ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php
                                                $st = $item['status'];
                                                if ($st === 'pending')
                                                    echo '<span class="badge badge-warning">Pending</span>';
                                                elseif ($st === 'completed')
                                                    echo '<span class="badge badge-success">Xong</span>';
                                                else
                                                    echo '<span class="badge badge-danger">Hủy</span>';
                                                ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?= FormatHelper::eventTime($item['created_at_display'] ?? $item['created_at'], $item['created_at']) ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if ($item['status'] === 'pending'): ?>
                                                    <button class="btn btn-success btn-sm btn-fulfill" data-id="<?= $item['id'] ?>"
                                                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                                                        data-input="<?= htmlspecialchars($item['customer_input'] ?: '') ?>"
                                                        title="Giải đơn (Gửi thông tin)">
                                                        <i class="fas fa-paper-plane mr-1"></i> Giải
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-light btn-sm text-muted" disabled>
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr id="stock-row-<?= $item['id'] ?>"
                                            class="<?= $item['status'] === 'sold' ? 'table-light' : '' ?>">
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <code class="p-1 px-2 border rounded bg-white text-dark mr-2"
                                                        style="font-size: 14px;"><?= htmlspecialchars($item['content']) ?></code>
                                                    <button class="btn btn-xs btn-outline-info copy-content-btn"
                                                        data-content="<?= htmlspecialchars($item['content']) ?>" title="Copy">
                                                        <i class="far fa-copy"></i>
                                                    </button>
                                                </div>
                                                <?php if ($item['status'] === 'sold' && $item['sold_at']): ?>
                                                    <div class="mt-1 small">
                                                        <span class="text-danger font-weight-bold"><i class="far fa-clock mr-1"></i>Bán
                                                            lúc:</span>
                                                        <span
                                                            class="text-muted"><?= $timeService ? htmlspecialchars($timeService->formatDisplay($item['sold_at'], 'd/m/Y H:i:s')) : htmlspecialchars(date('d/m/Y H:i:s', strtotime($item['sold_at']))) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if (!empty($item['buyer_username'])): ?>
                                                    <a href="<?= url('admin/users/edit/' . $item['buyer_username']) ?>"
                                                        class="d-inline-flex align-items-center text-primary font-weight-bold">
                                                        <?= htmlspecialchars($item['buyer_username']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa bán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if ($item['status'] === 'available'): ?>
                                                    <span class="badge badge-success px-2 py-1">CÒN HÀNG</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary px-2 py-1">ĐÃ BÁN</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?= FormatHelper::eventTime($item['created_at_display'] ?? ($item['created_at'] ?? ''), $item['created_at'] ?? '') ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="btn-group">
                                                    <button class="btn btn-search-dt btn-sm edit-stock-btn"
                                                        data-id="<?= $item['id'] ?>"
                                                        data-content="<?= htmlspecialchars($item['content']) ?>"
                                                        title="<?= $item['status'] === 'available' ? 'Sửa nội dung' : 'Sửa nội dung (Bảo hành)' ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($item['status'] === 'available'): ?>
                                                        <button class="btn btn-danger btn-sm ml-1 delete-stock-btn"
                                                            data-id="<?= $item['id'] ?>" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
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
                <div class="modal-header bg-success text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-paper-plane mr-2"></i>GIẢI ĐƠN HÀNG: <span id="fulfillCode"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="fulfillForm">
                        <input type="hidden" name="order_id" id="fulfillOrderId">
                        
                        <div class="info-callout bg-light p-3 rounded mb-4 border-left" style="border-left-width: 4px !important; border-left-color: #28a745 !important;">
                            <div class="small font-weight-bold text-uppercase text-muted mb-1">Thông tin khách yêu cầu:</div>
                            <div id="fulfillCustomerInput" class="text-dark font-weight-bold" style="white-space: pre-wrap;"></div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark"><i class="fas fa-reply mr-1"></i> Nội dung bàn giao:</label>
                            <textarea name="delivery_content" id="deliveryContent" class="form-control" rows="6" 
                                placeholder="Nhập nội dung/tài khoản/thông tin để gửi cho khách..." required></textarea>
                            <small class="form-text text-muted italic">Nội dung này sẽ hiển thị trong lịch sử mua hàng của khách.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light px-4 py-3" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Đóng</button>
                    <button type="button" id="btnConfirmFulfill" class="btn btn-success px-5 font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> XÁC NHẬN GIẢI ĐƠN
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
                <h5 class="modal-title font-weight-bold">SỬA NOI DUNG KHO</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editId">
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-dark mb-2">Nội dung (1 dòng duy nhất)</label>
                    <textarea id="editContent" class="form-control" rows="3" placeholder="user:pass..."></textarea>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                        Chỉ chỉnh sửa khi cần thiết (Ví dụ: khách báo sai mật khẩu, bảo hành).
                    </small>
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

                $('#stockBody').css('opacity', '0.5');

                $.get(window.location.href, {
                    status_filter: status,
                    search: search
                }, function (res) {
                    $('#stockBody').css('opacity', '1');
                    if (res.success) {
                        renderStockTable(res.items, res.isManualQueue);
                        renderStats(res.stats, res.isManualQueue);
                    }
                }, 'json');
            }, 300);
        }

        function renderStockTable(items, isManualQueue) {
            let html = '';
            if (!items || items.length === 0) {
                const emptyMsg = isManualQueue ? 'Không có đơn hàng nào' : 'Kho hiện đang trống';
                html = `<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>${emptyMsg}</td></tr>`;
            } else {
                items.forEach(item => {
                    if (isManualQueue) {
                        let statusBadge = '';
                        if (item.status === 'pending') statusBadge = '<span class="badge badge-warning">Pending</span>';
                        else if (item.status === 'completed') statusBadge = '<span class="badge badge-success">Xong</span>';
                        else statusBadge = '<span class="badge badge-danger">Hủy</span>';

                        let deliveryHtml = '';
                        if (item.status === 'completed') {
                            deliveryHtml = `<div class="mt-1 small"><b class="text-success">Giao:</b> <span class="text-muted">${escapeHtml(item.stock_content_plain || '—')}</span></div>`;
                        }

                        let actionHtml = '';
                        if (item.status === 'pending') {
                            actionHtml = `<button class="btn btn-success btn-sm btn-fulfill" 
                                                data-id="${item.id}" 
                                                data-code="${escapeHtml(item.order_code_short || item.order_code)}"
                                                data-input="${escapeHtml(item.customer_input || '')}"
                                                title="Giải đơn (Gửi thông tin)">
                                            <i class="fas fa-paper-plane mr-1"></i> Giải
                                        </button>`;
                        } else {
                            actionHtml = `<button class="btn btn-light btn-sm text-muted" disabled><i class="fas fa-check"></i></button>`;
                        }

                        html += `<tr>
                            <td class="text-center align-middle">
                                <span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">${escapeHtml(item.order_code_short || item.order_code)}</span>
                            </td>
                            <td class="text-center align-middle">
                                <a href="<?= url('admin/users/edit/') ?>${item.username}" class="font-weight-bold text-primary">${escapeHtml(item.username)}</a>
                            </td>
                            <td class="align-middle">
                                <div class="small"><b class="text-dark">Yêu cầu:</b> <span class="text-muted">${escapeHtml(item.customer_input || 'N/A')}</span></div>
                                ${deliveryHtml}
                            </td>
                            <td class="text-center align-middle">${statusBadge}</td>
                            <td class="text-center align-middle">${escapeHtml(item.created_at_display || item.created_at)}</td>
                            <td class="text-center align-middle">${actionHtml}</td>
                        </tr>`;
                        return;
                    }

                    const rowClass = item.status === 'sold' ? 'table-light' : '';
                    const statusBadge = item.status === 'available'
                        ? '<span class="badge badge-success px-2 py-1">CÒN HÀNG</span>'
                        : '<span class="badge badge-secondary px-2 py-1">ĐÃ BÁN</span>';

                    let buyerHtml = '<span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa bán</span>';
                    if (item.buyer_username) {
                        buyerHtml = `<a href="<?= url('admin/users/edit/') ?>${item.buyer_username}" class="d-inline-flex align-items-center text-primary font-weight-bold">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-2" style="width:24px; height:24px; font-size:10px;"><i class="fas fa-user"></i></div>
                                        ${escapeHtml(item.buyer_username)}
                                    </a>`;
                    }

                    let soldAtHtml = '';
                    if (item.status === 'sold' && item.sold_at) {
                        const soldDisplay = item.sold_at_display || item.sold_at;
                        soldAtHtml = `<div class="mt-1 small"><span class="text-danger font-weight-bold"><i class="far fa-clock mr-1"></i>Bán lúc:</span> <span class="text-muted">${escapeHtml(soldDisplay)}</span></div>`;
                    }

                    const editTitle = item.status === 'available' ? 'Sửa nội dung' : 'Sửa nội dung (Bảo hành)';
                    const deleteBtn = item.status === 'available'
                        ? `<button class="btn btn-danger btn-sm ml-1 delete-stock-btn" data-id="${item.id}" title="Xóa"><i class="fas fa-trash"></i></button>`
                        : '';

                    // Time display
                    const timeDisplay = item.created_at_display || item.created_at || '—';
                    const timeAgo = item.created_at_ago || '';
                    const timeHtml = timeAgo
                        ? `<span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="${escapeHtml(timeAgo)}">${escapeHtml(timeDisplay)}</span>`
                        : `<span class="text-muted small">${escapeHtml(timeDisplay)}</span>`;

                    html += `<tr id="stock-row-${item.id}" class="${rowClass}">
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <code class="p-1 px-2 border rounded bg-white text-dark mr-2" style="font-size: 14px;">${escapeHtml(item.content)}</code>
                                <button class="btn btn-xs btn-outline-info copy-content-btn" data-content="${escapeHtml(item.content)}" title="Copy"><i class="far fa-copy"></i></button>
                            </div>
                            ${soldAtHtml}
                        </td>
                        <td class="text-center align-middle">${buyerHtml}</td>
                        <td class="text-center align-middle">${statusBadge}</td>
                        <td class="text-center align-middle">${timeHtml}</td>
                        <td class="text-center align-middle">
                            <div class="btn-group">
                                <button class="btn btn-search-dt btn-sm edit-stock-btn"
                                    data-id="${item.id}" data-content="${escapeHtml(item.content)}" title="${editTitle}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${deleteBtn}
                            </div>
                        </td>
                    </tr>`;
                });
            }
            $('#stockBody').html(html);
            $('[data-toggle="tooltip"]').tooltip();
        }

        // OPEN FULFILL MODAL
        $(document).on('click', '.btn-fulfill', function () {
            const id = $(this).data('id');
            const code = $(this).data('code');
            const input = $(this).data('input');

            $('#fulfillOrderId').val(id);
            $('#fulfillCode').text(code);
            $('#fulfillCustomerInput').text(input || 'Không có thông tin yêu cầu.');
            $('#deliveryContent').val('');
            $('#fulfillModal').modal('show');
        });

        // CONFIRM FULFILL
        $('#btnConfirmFulfill').click(function () {
            const content = $('#deliveryContent').val().trim();
            if (!content) {
                Swal.fire('Lỗi', 'Vui lòng nhập nội dung bàn giao!', 'error');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');

            $.post('<?= url('admin/logs/buying/fulfill') ?>', {
                order_id: $('#fulfillOrderId').val(),
                delivery_content: content
            }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> XÁC NHẬN GIẢI ĐƠN');
                if (res.success) {
                    $('#fulfillModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: 'Đã giải đơn hàng thành công!',
                        timer: 1500
                    });
                    loadStockData(); // Refresh table
                } else {
                    Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                }
            }, 'json');
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
                $.post(cleanUrl, {}, function (res) {
                    if (res.success) {
                        Toast.fire({ icon: 'success', title: res.message });
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-eraser"></i> Dọn kho');
                        Toast.fire({ icon: 'error', title: res.message });
                    }
                }, 'json');
            });
        });

        // Init tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
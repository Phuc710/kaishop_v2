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
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">

        <!-- STATS -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold">Tổng kho</span>
                        <span class="info-box-number h4 mb-0" id="stat-total"><?= $stats['total'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box border-success border-left">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold">Còn lại</span>
                        <span class="info-box-number h4 mb-0" id="stat-available"><?= $stats['available'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase font-weight-bold">Đã bán</span>
                        <span class="info-box-number h4 mb-0" id="stat-sold"><?= $stats['sold'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- IMPORT PANEL -->
            <div class="col-md-4">
                <div class="card custom-card">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h4 class="card-title font-weight-bold mb-0"><i class="fas fa-upload mr-1"></i> NHẬP HÀNG MỚI
                        </h4>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline-info"
                                onclick="$('#addSingleModal').modal('show')">
                                <i class="fas fa-plus mr-1"></i>Thêm 1
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                onclick="$('#stockFile').click()">
                                <i class="fas fa-file-import mr-1"></i>Chọn file .txt
                            </button>
                        </div>
                        <input type="file" id="stockFile" style="display:none;" accept=".txt"
                            onchange="handleStockFile(this)">
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Danh sách tài khoản</label>
                            <textarea id="importContent" class="form-control" rows="15"
                                style="font-family:Consolas,monospace;font-size:13px; background: #fdfdfd;"
                                placeholder="user1:pass1&#10;user2:pass2&#10;..."></textarea>
                            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Mỗi dòng là 1 tài
                                khoản.</small>
                        </div>
                        <button id="btnImport" class="btn btn-primary btn-block btn-lg font-weight-bold">
                            <i class="fas fa-plus-circle mr-1"></i>NHẬP KHO NGAY
                        </button>
                        <div id="importResult" class="mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>

            <!-- STOCK LIST -->
            <div class="col-md-8">
                <div class="card custom-card">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="card-title font-weight-bold mb-0"><i class="fas fa-list mr-1"></i> DANH SÁCH TRONG
                            KHO</h4>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" id="searchTerm" class="form-control" placeholder="Tìm nội dung..."
                                    value="<?= htmlspecialchars($search ?? '') ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="btnSearch">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <select id="filterStatus" class="form-control form-control-sm" style="width:140px;">
                                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                                <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Còn lại
                                    (Sẵn sàng)</option>
                                <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Đã bán (Sold)
                                </option>
                            </select>
                            <button id="btnClean" class="btn btn-sm btn-outline-danger"
                                title="Xóa toàn bộ hàng chưa bán">
                                <i class="fas fa-eraser mr-1"></i>Dọn kho
                            </button>
                            <a href="<?= url('admin/products/edit/' . $product['id']) ?>"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit mr-1"></i>Quay lại Sửa SP
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:650px; overflow-y:auto;">
                            <table class="table table-hover mb-0" id="stockTable">
                                <thead style="background: #f4f6f9; position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="width:60px;">ID</th>
                                        <th>NỘI DUNG TÀI KHOẢN</th>
                                        <th style="width:110px;">TRẠNG THÁI</th>
                                        <th style="width:110px;">NGÀY NHẬP</th>
                                        <th style="width:110px;">HÀNH ĐỘNG</th>
                                    </tr>
                                </thead>
                                <tbody id="stockBody">
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">Kho hiện đang trống</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr id="stock-row-<?= $item['id'] ?>">
                                                <td class="text-muted small align-middle">#<?= $item['id'] ?></td>
                                                <td class="align-middle">
                                                    <?php if ($item['status'] === 'sold'): ?>
                                                        <div class="text-muted italic"
                                                            style="font-size:13px; text-decoration: line-through;">•••••••••••• (Đã
                                                            bán)</div>
                                                        <?php if ($item['sold_at']): ?>
                                                            <small class="text-danger d-block">Bán lúc:
                                                                <?= date('d/m/Y H:i', strtotime($item['sold_at'])) ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <code style="font-size:14px; color: #d63384;" class="account-content"
                                                            data-id="<?= $item['id'] ?>"><?= htmlspecialchars($item['content']) ?></code>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <?php if ($item['status'] === 'available'): ?>
                                                        <span class="badge badge-success px-2 py-1">CÒN HÀNG</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary px-2 py-1">ĐÃ BÁN</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted align-middle">
                                                    <?= $item['created_at'] ? date('d/m H:i', strtotime($item['created_at'])) : '—' ?>
                                                </td>
                                                <td class="align-middle">
                                                    <?php if ($item['status'] === 'available'): ?>
                                                        <button class="btn btn-xs btn-warning edit-stock-btn mr-1"
                                                            data-id="<?= $item['id'] ?>"
                                                            data-content="<?= htmlspecialchars($item['content']) ?>" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-xs btn-danger delete-stock-btn"
                                                            data-id="<?= $item['id'] ?>" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-muted small py-2 bg-light">
                        Tổng cộng: <?= count($items) ?> tài khoản
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL ADD SINGLE STOCK -->
<div class="modal fade" id="addSingleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">THÊM 1 TÀI KHOẢN MỚI</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Nội dung tài khoản</label>
                    <input type="text" id="singleContent" class="form-control" placeholder="Ví dụ: user|pass|extra...">
                    <small class="text-muted">Nhập nội dung 1 dòng duy nhất.</small>
                </div>
            </div>
            <div class="modal-footer pb-3 border-0">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Hủy</button>
                <button type="button" id="btnSaveSingle" class="btn btn-info font-weight-bold px-4">THÊM NGAY</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT STOCK -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0">
            <div class="modal-header bg-warning">
                <h5 class="modal-title font-weight-bold">SỬA NỘI DUNG TÀI KHOẢN</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Nội dung (1 dòng)</label>
                    <input type="text" id="editContent" class="form-control" placeholder="user:pass...">
                </div>
            </div>
            <div class="modal-footer pb-3 border-0">
                <button type="button" class="btn btn-light border" data-dismiss="modal">Hủy</button>
                <button type="button" id="btnSaveEdit" class="btn btn-warning font-weight-bold px-4">LƯU THAY
                    ĐỔI</button>
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

        // Import
        $('#btnImport').on('click', function () {
            const content = $('#importContent').val().trim();
            if (!content) { Toast.fire({ icon: 'warning', title: 'Vui lòng nhập danh sách!' }); return; }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang xử lý...');

            $.post(importUrl, { content: content }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i>NHẬP KHO NGAY');
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    $('#importContent').val('');
                    $('#importResult').html(`<div class="alert alert-success border-left">✅ Đã nhập <b>${res.added}</b> item mới. Bỏ qua <b>${res.skipped}</b> dòng trùng.</div>`).show();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                }
            }, 'json').fail(() => {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i>NHẬP KHO NGAY');
                Toast.fire({ icon: 'error', title: 'Lỗi server!' });
            });
        });

        // Delete
        $(document).on('click', '.delete-stock-btn', function () {
            const id = $(this).data('id');
            Swal.fire({ title: 'Xóa tài khoản này?', text: 'Hành động này không thể hoàn tác.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Hủy', confirmButtonText: 'Xác nhận xóa' })
                .then(r => {
                    if (!r.isConfirmed) return;
                    $.post(deleteUrl, { id: id }, function (res) {
                        if (res.success) {
                            $('#stock-row-' + id).fadeOut(200, function () { $(this).remove(); });
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
            if (!content) return;

            $('#btnSaveEdit').prop('disabled', true).text('Đang lưu...');
            $.post(updateUrl, { id: id, content: content }, function (res) {
                $('#btnSaveEdit').prop('disabled', false).text('LƯU THAY ĐỔI');
                if (res.success) {
                    $('#editStockModal').modal('hide');
                    $(`#stock-row-${id} code`).text(content);
                    $(`.edit-stock-btn[data-id="${id}"]`).data('content', content);
                    Toast.fire({ icon: 'success', title: 'Đã cập nhật' });
                } else {
                    Toast.fire({ icon: 'error', title: res.message });
                }
            }, 'json');
        });

        // Filter
        $('#filterStatus').on('change', applyFilters);

        // Search
        $('#btnSearch').on('click', applyFilters);
        $('#searchTerm').on('keypress', function (e) { if (e.which == 13) applyFilters(); });

        function applyFilters() {
            const status = $('#filterStatus').val();
            const search = $('#searchTerm').val().trim();
            let url = baseUrl + '?';
            if (status) url += 'status_filter=' + status + '&';
            if (search) url += 'search=' + encodeURIComponent(search);
            window.location.href = url;
        }

        // Clean
        $('#btnClean').on('click', function () {
            Swal.fire({
                title: 'Dọn sạch kho?',
                text: 'Toàn bộ nội dung CHƯA BÁN sẽ bị xóa vĩnh viễn!',
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
                        btn.prop('disabled', false).html('<i class="fas fa-eraser mr-1"></i>Dọn kho');
                        Toast.fire({ icon: 'error', title: res.message });
                    }
                }, 'json');
            });
        });

        // Add Single
        $('#btnSaveSingle').on('click', function () {
            const content = $('#singleContent').val().trim();
            if (!content) return;

            const btn = $(this);
            btn.prop('disabled', true).text('Đang thêm...');
            $.post(importUrl, { content: content }, function (res) {
                btn.prop('disabled', false).text('THÊM NGAY');
                if (res.success) {
                    Toast.fire({ icon: 'success', title: 'Đã thêm thành công' });
                    $('#addSingleModal').modal('hide');
                    $('#singleContent').val('');
                    setTimeout(() => location.reload(), 800);
                } else {
                    Toast.fire({ icon: 'error', title: res.message });
                }
            }, 'json');
        });
    });
</script>
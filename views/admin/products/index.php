<?php
/**
 * View: Danh sách sản phẩm
 * Route: GET /admin/products
 * Controller: AdminProductController@index
 */
$pageTitle = 'Danh sách sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm'],
    ['label' => 'Danh sách'],
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
                    <span class="info-box-icon bg-info"><i class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">TỔNG SẢN PHẨM</span>
                        <span class="info-box-number"><?= (int) ($stats['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">ĐANG HIỂN THỊ (ON)</span>
                        <span class="info-box-number"><?= (int) ($stats['active'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-eye-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">ĐANG ẨN (OFF)</span>
                        <span class="info-box-number"><?= (int) ($stats['inactive'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE CARD -->
        <div class="card custom-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title font-weight-bold mb-0">
                    <span class="badge-left-accent"></span> QUẢN LÝ SẢN PHẨM
                </h3>
                <a href="<?= url('admin/products/add') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </a>
            </div>

            <!-- FILTER BAR -->
            <div class="card-body border-bottom py-2">
                <form method="GET" action="<?= url('admin/products') ?>"
                    class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" style="max-width:200px;"
                        placeholder="Tìm tên hoặc slug..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">

                    <select name="category_id" class="form-control form-control-sm" style="max-width:160px;">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($filters['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="form-control form-control-sm" style="max-width:140px;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="ON" <?= ($filters['status'] ?? '') === 'ON' ? 'selected' : '' ?>>ON</option>
                        <option value="OFF" <?= ($filters['status'] ?? '') === 'OFF' ? 'selected' : '' ?>>OFF</option>
                    </select>

                    <select name="type" class="form-control form-control-sm" style="max-width:140px;">
                        <option value="">Tất cả loại</option>
                        <option value="account" <?= ($filters['type'] ?? '') === 'account' ? 'selected' : '' ?>>Tài khoản
                        </option>
                        <option value="link" <?= ($filters['type'] ?? '') === 'link' ? 'selected' : '' ?>>Source Link
                        </option>
                    </select>

                    <button type="submit" class="btn btn-search-dt btn-sm"><i
                            class="fas fa-search mr-1"></i>Tìm</button>
                    <a href="<?= url('admin/products') ?>" class="btn btn-light border btn-sm">Xóa lọc</a>
                </form>
            </div>

            <!-- TABLE -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:60px;">ẢNH</th>
                                <th>TÊN SẢN PHẨM</th>
                                <th>LOẠI</th>
                                <th>DANH MỤC</th>
                                <th>GIÁ BÁN</th>
                                <th>KHO / ĐÃ BÁN</th>
                                <th>TRẠNG THÁI</th>
                                <th>NGÀY TẠO</th>
                                <th style="width:120px;">HÀNH ĐỘNG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Không tìm thấy dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p):
                                    $isAccount = ($p['product_type'] ?? 'account') === 'account';
                                    $pid = (int) $p['id'];
                                    $st = $stockStats[$pid] ?? ['available' => 0, 'sold' => 0];
                                    ?>
                                    <tr id="row-<?= $pid ?>">
                                        <td>
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?= htmlspecialchars($p['image']) ?>"
                                                    style="width:46px;height:46px;object-fit:cover;border-radius:6px;" alt="">
                                            <?php else: ?>
                                                <div
                                                    style="width:46px;height:46px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold"><?= htmlspecialchars($p['name']) ?></div>
                                            <?php if (!empty($p['badge_text'])): ?>
                                                <span class="badge badge-warning"><?= htmlspecialchars($p['badge_text']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isAccount): ?>
                                                <span class="badge badge-info"><i class="fas fa-user mr-1"></i>Tài khoản</span>
                                            <?php else: ?>
                                                <span class="badge badge-purple" style="background:#6f42c1;color:#fff;"><i
                                                        class="fas fa-link mr-1"></i>Source Link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                                        <td class="font-weight-bold text-success"><?= number_format((int) $p['price_vnd']) ?>đ
                                        </td>
                                        <td>
                                            <?php if ($isAccount): ?>
                                                <span class="text-success font-weight-bold"><?= $st['available'] ?></span>
                                                <span class="text-muted"> / đã bán </span>
                                                <span class="text-danger font-weight-bold"><?= $st['sold'] ?></span>
                                                <a href="<?= url('admin/products/stock/' . $pid) ?>"
                                                    class="btn btn-xs btn-outline-secondary ml-1" title="Quản lý kho">
                                                    <i class="fas fa-warehouse"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge badge-light text-muted"><i class="fas fa-infinity mr-1"></i>Vô
                                                    hạn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-xs toggle-status-btn <?= $p['status'] === 'ON' ? 'btn-success' : 'btn-secondary' ?>"
                                                data-id="<?= $pid ?>"
                                                title="<?= $p['status'] === 'ON' ? 'Đang hiển thị – click để ẩn' : 'Đang ẩn – click để hiển thị' ?>">
                                                <?= $p['status'] === 'ON' ? 'ON' : 'OFF' ?>
                                            </button>
                                        </td>
                                        <td class="small text-muted">
                                            <?= $p['created_at'] ? date('d/m/Y', strtotime($p['created_at'])) : '—' ?></td>
                                        <td>
                                            <a href="<?= url('admin/products/edit/' . $pid) ?>"
                                                class="btn btn-xs btn-warning mr-1" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-xs btn-danger delete-btn" data-id="<?= $pid ?>"
                                                data-name="<?= htmlspecialchars($p['name']) ?>" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted small">
                Hiển thị <?= count($products) ?> sản phẩm
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<script>
    $(function () {
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });

        // Toggle status
        $(document).on('click', '.toggle-status-btn', function () {
            const btn = $(this);
            const id = btn.data('id');
            $.post('<?= url("admin/products/toggle-status") ?>', { id: id }, function (res) {
                if (res.success) {
                    const isOn = res.new_status === 'ON';
                    btn.text(isOn ? 'ON' : 'OFF')
                        .removeClass('btn-success btn-secondary')
                        .addClass(isOn ? 'btn-success' : 'btn-secondary');
                    Toast.fire({ icon: 'success', title: isOn ? 'Đã bật hiển thị' : 'Đã ẩn sản phẩm' });
                } else {
                    Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                }
            }, 'json').fail(() => Toast.fire({ icon: 'error', title: 'Lỗi máy chủ!' }));
        });

        // Delete
        $(document).on('click', '.delete-btn', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            Swal.fire({
                title: 'Xóa sản phẩm?', text: name, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonText: 'Hủy', confirmButtonText: 'Xóa'
            }).then(r => {
                if (!r.isConfirmed) return;
                $.post('<?= url("admin/products/delete") ?>', { id: id }, function (res) {
                    if (res.success) {
                        $('#row-' + id).fadeOut(300, function () { $(this).remove(); });
                        Toast.fire({ icon: 'success', title: 'Đã xóa sản phẩm' });
                    } else {
                        Toast.fire({ icon: 'error', title: res.message || 'Lỗi!' });
                    }
                }, 'json');
            });
        });
    });
</script>
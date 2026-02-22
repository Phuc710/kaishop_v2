<?php
/**
 * View: Thêm Mã giảm giá
 * Route: GET/POST /admin/finance/giftcodes/add
 */
$pageTitle = 'Tạo mã giảm giá mới';
$breadcrumbs = [
    ['label' => 'Mã giảm giá', 'url' => url('admin/finance/giftcodes')],
    ['label' => 'Thêm mới'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold text-uppercase mb-0">
                            TẠO MÃ GIẢM GIÁ MỚI
                        </h3>
                    </div>

                    <form action="<?= url('admin/finance/giftcodes/add') ?>" method="POST">
                        <div class="card-body pt-3">

                            <!-- Thông tin mã -->
                            <div class="form-section">
                                <div class="form-section-title">Thông tin mã giảm giá</div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Mã giảm giá</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="giftcode" id="giftcode"
                                                    placeholder="Nhập mã..." required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-search-dt"
                                                        onclick="generateCode()">
                                                        <i class="fas fa-random mr-1"></i>Ngẫu nhiên
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Phần trăm giảm (%)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="giamgia"
                                                    placeholder="VD: 10" min="1" max="100" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <small class="text-muted">Nhập 10 = giảm 10%</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Số lượt sử dụng</label>
                                            <input type="number" class="form-control" name="soluong" value="1" min="1"
                                                required>
                                            <small class="text-muted">Số user sử dụng</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Điều kiện áp dụng -->
                            <div class="form-section">
                                <div class="form-section-title">Điều kiện áp dụng</div>

                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Sản phẩm áp dụng</label>
                                    <select class="form-control select2" name="product_ids[]" multiple="multiple"
                                        data-placeholder="Để trống = áp dụng toàn bộ sản phẩm">
                                        <?php if (!empty($products)): ?>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="text-muted">Để trống sẽ áp dụng cho toàn bộ sản phẩm</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Giá trị đơn tối thiểu (VND)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="min_order" value="0"
                                                    min="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text text-muted small">đ</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Giá trị đơn tối đa (VND)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="max_order" value="0"
                                                    min="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text text-muted small">đ</span>
                                                </div>
                                            </div>
                                            <small class="text-muted">Để 0 = Không giới hạn tối đa</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer text-right bg-transparent border-top-0 pt-0">
                            <a href="<?= url('admin/finance/giftcodes') ?>" class="btn btn-light border mr-2 px-4">
                                <i class="fas fa-times mr-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save mr-1"></i>Lưu mã giảm giá
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%', allowClear: true });
    });

    function generateCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('giftcode').value = result;
    }
</script>
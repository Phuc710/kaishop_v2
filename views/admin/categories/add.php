<?php
/**
 * View: Thêm danh mục
 * Route: GET /admin/categories/add
 * Controller: CategoryController@add
 */
$pageTitle = 'Thêm danh mục';
$breadcrumbs = [
    ['label' => 'Danh mục', 'url' => url('admin/categories')],
    ['label' => 'Thêm mới'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold text-uppercase mb-0">
                            THÊM DANH MỤC MỚI
                        </h3>
                    </div>

                    <form action="<?= url('admin/categories/add') ?>" method="POST">
                        <div class="card-body pt-3">

                            <!-- Thông tin danh mục -->
                            <div class="form-section">
                                <div class="form-section-title">Thông tin danh mục</div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Tên danh mục</label>
                                            <input type="text" class="form-control" name="name"
                                                placeholder="Nhập tên danh mục..." required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Thứ tự ưu tiên</label>
                                            <input type="number" class="form-control" name="display_order" value="0"
                                                min="0">
                                            <small class="text-muted">Số nhỏ = ưu tiên hiển thị cao hơn</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Icon danh mục</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="image" name="icon"
                                            placeholder="Nhập link ảnh icon hoặc chọn từ thư viện">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-search-dt"
                                                onclick="openImageManager && openImageManager()">
                                                <i class="fas fa-image mr-1"></i>Chọn ảnh
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <img id="iconPreview" src="" alt="Preview"
                                            style="max-height: 80px; display: none; border: 1px solid #ddd; padding: 4px; border-radius: 8px;">
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON">Đang bật (Hiện)</option>
                                        <option value="OFF">Đang tắt (Ẩn)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- SEO -->
                            <div class="form-section">
                                <div class="form-section-title">Mô tả SEO</div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Mô tả</label>
                                    <textarea class="form-control" name="description" rows="3"
                                        placeholder="Nhập mô tả danh mục cho SEO..."></textarea>
                                    <small class="text-muted">Mô tả sẽ hiển thị trên trang chủ và kết quả tìm
                                        kiếm</small>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer text-right bg-transparent border-top-0 pt-0">
                            <a href="<?= url('admin/categories') ?>" class="btn btn-light border mr-2 px-4">
                                <i class="fas fa-times mr-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save mr-1"></i>Lưu danh mục
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../layout/foot.php';
require_once __DIR__ . '/../../image-manager-modal.php';
?>

<script>
    $(document).ready(function () {
        $('#image').on('change keyup paste', function () {
            var url = $(this).val();
            if (url) {
                $('#iconPreview').attr('src', url).show();
            } else {
                $('#iconPreview').hide();
            }
        });
    });
</script>
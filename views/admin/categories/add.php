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

                                <div class="row align-items-start">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Tên danh mục</label>
                                            <input type="text" class="form-control" name="name"
                                                placeholder="Nhập tên..." required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Slug</label>
                                            <input type="text" class="form-control" name="slug"
                                                placeholder="Để trống tự tạo...">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold" style="white-space: nowrap;">Ưu tiên</label>
                                            <input type="number" class="form-control" name="display_order" value="0"
                                                min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold text-center d-block">Trạng thái</label>
                                            <select class="form-control font-weight-bold" name="status">
                                                <option class="text-success" value="ON">Hiện (ON)</option>
                                                <option class="text-danger" value="OFF">Ẩn (OFF)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-start">
                                    <div class="col-md-10">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Icon / Hình ảnh</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="image" name="icon"
                                                    placeholder="Link ảnh">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-search-dt"
                                                        onclick="openImageManager && openImageManager()">
                                                        <i class="fas fa-image mr-1"></i>Chọn
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold d-block text-center">Preview</label>
                                            <div class="text-center bg-light border rounded d-flex align-items-center justify-content-center" style="height: 38px;">
                                                <img id="iconPreview" src="" alt="Preview"
                                                    style="max-height: 34px; max-width: 100%; display: none; object-fit: contain;">
                                            </div>
                                        </div>
                                    </div>
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
require_once ROOT_PATH . '/admin/image-manager-modal.php';
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
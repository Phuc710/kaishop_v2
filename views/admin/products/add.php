<?php
/**
 * View: Thêm sản phẩm
 * Route: GET /admin/products/add
 * Controller: AdminProductController@add
 */
$pageTitle = 'Thêm sản phẩm';
$breadcrumbs = [
    ['label' => 'Sản phẩm', 'url' => url('admin/products')],
    ['label' => 'Thêm sản phẩm'],
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
                            THÊM SẢN PHẨM MỚI
                        </h3>
                    </div>

                    <form action="<?= url('admin/products/add') ?>" method="POST" id="productForm">
                        <div class="card-body pt-3">

                            <!-- Section 1: Thông tin sản phẩm -->
                            <div class="form-section">
                                <div class="form-section-title">Thông tin sản phẩm</div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Tên sản phẩm</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Nhập tên sản phẩm..." required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold form-label-req">Giá bán (VNĐ)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="price_vnd"
                                                    placeholder="0" min="0" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">đ</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Thứ tự ưu tiên</label>
                                            <input type="number" class="form-control" name="display_order" value="0"
                                                min="0">
                                            <small class="text-muted">Số nhỏ = hiển thị trước</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Danh mục</label>
                                            <select class="form-control" name="category_id" id="category_id">
                                                <option value="0">— Chọn danh mục —</option>
                                                <?php if (!empty($categories)): ?>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold">Trạng thái</label>
                                            <select class="form-control" name="is_active">
                                                <option value="1">Hoạt động</option>
                                                <option value="0">Tắt</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Hình ảnh -->
                            <div class="form-section">
                                <div class="form-section-title">Hình ảnh</div>

                                <!-- Ảnh chính -->
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold form-label-req">Ảnh chính (Thumbnail)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="image" name="image"
                                            placeholder="Nhập link ảnh hoặc chọn từ thư viện">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-search-dt"
                                                onclick="openImageManager && openImageManager()">
                                                <i class="fas fa-image mr-1"></i>Chọn ảnh
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="" alt="Preview"
                                            style="max-height: 150px; display: none; border: 1px solid #ddd; padding: 4px; border-radius: 8px;">
                                    </div>
                                </div>

                                <!-- Gallery -->
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Gallery (Nhiều ảnh)</label>
                                    <div id="gallery-container"></div>
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                        onclick="addGalleryItem()">
                                        <i class="fas fa-plus mr-1"></i>Thêm ảnh gallery
                                    </button>
                                    <small class="text-muted d-block mt-1">Thêm nhiều ảnh để hiển thị carousel trên
                                        trang sản phẩm</small>
                                </div>
                            </div>

                            <!-- Section 3: Mô tả -->
                            <div class="form-section">
                                <div class="form-section-title">Mô tả sản phẩm</div>
                                <div class="form-group mb-3">
                                    <textarea class="form-control" id="description" name="description"
                                        rows="5"></textarea>
                                </div>
                            </div>

                            <!-- Section 4: SEO & Cấu hình -->
                            <div class="form-section">
                                <div class="form-section-title">SEO & Cấu hình</div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">Slug (URL)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text text-muted">/product/</span>
                                        </div>
                                        <input type="text" class="form-control" name="slug" id="slug"
                                            placeholder="Tự động tạo từ tên sản phẩm">
                                    </div>
                                    <small class="text-muted">Để trống để tự động tạo slug từ tên sản phẩm</small>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer text-right bg-transparent border-top-0 pt-0">
                            <a href="<?= url('admin/products') ?>" class="btn btn-light border mr-2 px-4">
                                <i class="fas fa-times mr-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save mr-1"></i>Thêm sản phẩm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>

<script>
    $(document).ready(function () {
        // Summernote
        if ($.fn.summernote) {
            $('#description').summernote({
                height: 250,
                codemirror: { theme: 'monokai' }
            });
        }

        // Main image preview
        $('#image').on('change keyup paste', function () {
            var url = $(this).val();
            if (url) {
                $('#imagePreview').attr('src', url).show();
            } else {
                $('#imagePreview').hide();
            }
        });

        // Auto slug from name
        $('#name').on('keyup change', function () {
            if (!$('#slug').data('manual')) {
                $('#slug').val(toSlug($(this).val()));
            }
        });
        $('#slug').on('input', function () {
            $(this).data('manual', true);
        });
    });

    /* ── Gallery ── */
    let galleryIndex = 0;

    function addGalleryItem(url) {
        url = url || '';
        const html = `
            <div class="input-group mb-2 gallery-item" id="gallery-${galleryIndex}">
                <input type="text" class="form-control gallery-input" name="gallery[]"
                    value="${escHtml(url)}" placeholder="Link ảnh gallery">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger" onclick="removeGalleryItem(${galleryIndex})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        $('#gallery-container').append(html);
        galleryIndex++;
    }

    function removeGalleryItem(idx) {
        $('#gallery-' + idx).fadeOut(200, function () { $(this).remove(); });
    }

    function escHtml(s) {
        return $('<div/>').text(s).html();
    }

    /* ── Slug Helper ── */
    function toSlug(str) {
        const map = { 'à': 'a', 'á': 'a', 'ạ': 'a', 'ả': 'a', 'ã': 'a', 'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ậ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ặ': 'a', 'ẳ': 'a', 'ẵ': 'a', 'è': 'e', 'é': 'e', 'ẹ': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ê': 'e', 'ề': 'e', 'ế': 'e', 'ệ': 'e', 'ể': 'e', 'ễ': 'e', 'ì': 'i', 'í': 'i', 'ị': 'i', 'ỉ': 'i', 'ĩ': 'i', 'ò': 'o', 'ó': 'o', 'ọ': 'o', 'ỏ': 'o', 'õ': 'o', 'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ộ': 'o', 'ổ': 'o', 'ỗ': 'o', 'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ợ': 'o', 'ở': 'o', 'ỡ': 'o', 'ù': 'u', 'ú': 'u', 'ụ': 'u', 'ủ': 'u', 'ũ': 'u', 'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ự': 'u', 'ử': 'u', 'ữ': 'u', 'ỳ': 'y', 'ý': 'y', 'ỵ': 'y', 'ỷ': 'y', 'ỹ': 'y', 'đ': 'd' };
        for (const [k, v] of Object.entries(map)) {
            str = str.split(k).join(v);
        }
        return str.toUpperCase().replace(/[^A-Z0-9\s_]/g, '').replace(/[\s_]+/g, '_').replace(/^_|_$/g, '');
    }
</script>
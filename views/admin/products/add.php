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
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card custom-card">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-uppercase mb-0">THÊM SẢN PHẨM MỚI</h3>
                </div>

                <form action="<?= url('admin/products/add') ?>" method="POST" id="productForm">
                    <div class="card-body pt-3">

                        <!-- ===== THÔNG TIN CƠ BẢN ===== -->
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
                                            <input type="number" class="form-control text-success font-weight-bold"
                                                name="price_vnd" placeholder="0" min="0" required>
                                            <div class="input-group-append"><span class="input-group-text">đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Loại sản phẩm</label>
                                        <select class="form-control font-weight-bold btn-outline-primary"
                                            name="product_type" id="productType">
                                            <option value="account">🔑 Tài khoản (Bán từ kho)</option>
                                            <option value="link">🔗 Source Link (Link download)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Danh mục</label>
                                        <select class="form-control" name="category_id">
                                            <option value="0">— Chọn danh mục —</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Badge (Nhãn)</label>
                                        <input type="text" class="form-control" name="badge_text"
                                            placeholder="NEW / HOT / -50%">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Thứ tự</label>
                                        <input type="number" class="form-control" name="display_order" value="0"
                                            min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Trạng thái hiển thị</label>
                                        <select class="form-control font-weight-bold" name="status">
                                            <option value="ON" class="text-success">HIỂN THỊ (ON)</option>
                                            <option value="OFF" class="text-danger">ẨN (OFF)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== SOURCE LINK (chỉ khi loại = link) ===== -->
                        <div class="form-section" id="section-link"
                            style="display:none; background: #f8f9fa; border: 1px dashed #dee2e6;">
                            <div class="form-section-title">🔗 Cấu hình Link Download</div>
                            <div class="form-group mb-0 p-2">
                                <label class="font-weight-bold text-primary">Source Link (Mega / GDrive / ...)</label>
                                <input type="text" class="form-control form-control-lg" name="source_link"
                                    id="source_link" placeholder="https://mega.nz/file/...">
                                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Link này sẽ được gửi
                                    tự động khi khách mua thành công. Không giới hạn số lượng bán.</small>
                            </div>
                        </div>

                        <!-- ===== NHẬP KHO NGAY (chỉ khi loại = account) ===== -->
                        <div class="form-section" id="section-stock"
                            style="background: #f0f7ff; border: 1px dashed #cfe2ff;">
                            <div class="form-section-title">📦 Nhập kho tài khoản</div>
                            <div class="form-group mb-0 p-2">
                                <label class="font-weight-bold text-info">Danh sách tài khoản (Mỗi dòng 1 acc)</label>
                                <textarea class="form-control" name="initial_stock" rows="6"
                                    style="font-family:Consolas,monospace;font-size:13px;"
                                    placeholder="user1:pass1&#10;user2:pass2&#10;..."></textarea>
                                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Sau khi tạo, bạn có
                                    thể quản lý kho chi tiết hơn tại trang "Kho".</small>
                            </div>
                        </div>

                        <!-- ===== SEO & URL ===== -->
                        <div class="form-section">
                            <div class="form-section-title">SEO & Hình ảnh</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Đường dẫn (Slug)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">/p/</span>
                                            </div>
                                            <input type="text" class="form-control" name="slug" id="slug"
                                                placeholder="Tự động theo tên">
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Mô tả SEO</label>
                                        <textarea class="form-control" name="seo_description" rows="3"
                                            placeholder="Mô tả ngắn hiện trên Google/Facebook..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold">Ảnh sản phẩm (Thumbnail)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="image" name="image"
                                                placeholder="Link ảnh hoặc chọn từ máy">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary"
                                                    onclick="openImageManager && openImageManager()">
                                                    Chọn ảnh
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-center border rounded p-1 bg-light"
                                            style="height: 100px; display: flex; align-items: center; justify-content: center;">
                                            <img id="imagePreview" src="" alt=""
                                                style="max-height: 90px; max-width: 100%; display: none;">
                                            <span id="noImage" class="text-muted small">Chưa có ảnh</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Ảnh Gallery (nhiều ảnh)</label>
                                <div id="gallery-container" class="row no-gutters"></div>
                                <button type="button" class="btn btn-outline-info btn-sm mt-2"
                                    onclick="addGalleryItem()">
                                    <i class="fas fa-plus mr-1"></i>Thêm ảnh phụ
                                </button>
                            </div>
                        </div>

                        <!-- ===== MÔ TẢ ===== -->
                        <div class="form-section">
                            <div class="form-section-title">Mô tả sản phẩm</div>
                            <div class="form-group mb-0">
                                <textarea class="form-control" id="description" name="description" rows="8"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer text-right border-top-0 pt-0 pb-4">
                        <hr>
                        <a href="<?= url('admin/products') ?>" class="btn btn-light border mr-2 px-4">Quay lại</a>
                        <button type="submit" class="btn btn-primary btn-lg px-5 font-weight-bold">THÊM SẢN
                            PHẨM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>

<script>
    $(function () {
        if ($.fn.summernote) $('#description').summernote({ height: 300 });

        $('#image').on('change keyup paste', function () {
            var url = $(this).val();
            if (url) {
                $('#imagePreview').attr('src', url).show();
                $('#noImage').hide();
            } else {
                $('#imagePreview').hide();
                $('#noImage').show();
            }
        });

        $('#name').on('keyup change', function () {
            if (!$('#slug').data('manual')) $('#slug').val(toSlug($(this).val()));
        });
        $('#slug').on('input', function () { $(this).data('manual', true); });

        $('#productType').on('change', function () {
            var type = $(this).val();
            if (type === 'link') {
                $('#section-link').slideDown();
                $('#section-stock').slideUp();
                $('#source_link').prop('required', true);
            } else {
                $('#section-link').slideUp();
                $('#section-stock').slideDown();
                $('#source_link').prop('required', false);
            }
        }).trigger('change');
    });

    let galleryIndex = 0;
    function addGalleryItem(url) {
        url = url || '';
        const html = `<div class="col-md-4 p-1 gallery-item" id="gallery-${galleryIndex}">
        <div class="input-group input-group-sm">
            <input type="text" class="form-control" name="gallery[]" value="${escHtml(url)}" placeholder="Link ảnh...">
            <div class="input-group-append"><button type="button" class="btn btn-danger" onclick="removeGalleryItem(${galleryIndex})">×</button></div>
        </div>
    </div>`;
        $('#gallery-container').append(html);
        galleryIndex++;
    }
    function removeGalleryItem(idx) { $('#gallery-' + idx).remove(); }
    function escHtml(s) { return $('<div/>').text(s).html(); }
    function toSlug(str) {
        const map = { 'à': 'a', 'á': 'a', 'ạ': 'a', 'ả': 'a', 'ã': 'a', 'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ậ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ặ': 'a', 'ẳ': 'a', 'ẵ': 'a', 'è': 'e', 'é': 'e', 'ẹ': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ê': 'e', 'ề': 'e', 'ế': 'e', 'ệ': 'e', 'ể': 'e', 'ễ': 'e', 'ì': 'i', 'í': 'i', 'ị': 'i', 'ỉ': 'i', 'ĩ': 'i', 'ò': 'o', 'ó': 'o', 'ọ': 'o', 'ỏ': 'o', 'õ': 'o', 'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ộ': 'o', 'ổ': 'o', 'ỗ': 'o', 'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ợ': 'o', 'ở': 'o', 'ỡ': 'o', 'ù': 'u', 'ú': 'u', 'ụ': 'u', 'ủ': 'u', 'ũ': 'u', 'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ự': 'u', 'ử': 'u', 'ữ': 'u', 'ỳ': 'y', 'ý': 'y', 'ỵ': 'y', 'ỷ': 'y', 'ỹ': 'y', 'đ': 'd' };
        for (const [k, v] of Object.entries(map)) str = str.split(k).join(v);
        return str.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/[\s]+/g, '-');
    }
</script>
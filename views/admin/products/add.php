<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm sản phẩm</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Thêm sản phẩm</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Thêm sản phẩm mới</h3>
                        </div>
                        <form action="<?= url('admin/products/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên sản phẩm</label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Nhập tên sản phẩm" required>
                                </div>
                                <div class="form-group">
                                    <label for="price">Giá</label>
                                    <input type="number" class="form-control" id="price" name="price" placeholder="Nhập giá" required>
                                </div>
                                <div class="form-group">
                                    <label for="category">Loại (Category)</label>
                                    <select class="form-control" name="category">
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['name'] ?>"><?= $cat['name'] ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="image">Link Ảnh</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="image" name="image" placeholder="Nhập link ảnh hoặc chọn từ thư viện">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" onclick="openImageManager()">Chọn ảnh</button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="" alt="Preview" style="max-height: 200px; display: none; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Nhập mô tả"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="status">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON">ON</option>
                                        <option value="OFF">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/products') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
<?php // Image Manager Modal - Needs to be adjusted later ?>
<?php include ROOT_PATH . '/admin/image-manager-modal.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize Summernote
        $('#description').summernote({
            height: 250,
            codemirror: { theme: 'monokai' }
        });

        // Image Preview Listener
        $('#image').on('change keyup paste', function() {
            var url = $(this).val();
            if (url) {
                $('#imagePreview').attr('src', url).show();
            } else {
                $('#imagePreview').hide();
            }
        });
    });
</script>

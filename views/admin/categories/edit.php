<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sửa danh mục</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/categories') ?>">Danh mục</a></li>
                        <li class="breadcrumb-item active">Sửa danh mục</li>
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
                            <h3 class="card-title">Cập nhật danh mục</h3>
                        </div>
                        <form action="<?= url('admin/categories/edit/' . $category['id']) ?>" method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên danh mục</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($category['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="status">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON" <?= $category['status'] == 'ON' ? 'selected' : ''; ?>>ON</option>
                                        <option value="OFF" <?= $category['status'] == 'OFF' ? 'selected' : ''; ?>>OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <a href="<?= url('admin/categories') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

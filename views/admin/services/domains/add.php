<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm đuôi miền</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/domains') ?>">Domains</a></li>
                        <li class="breadcrumb-item active">Thêm mới</li>
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
                            <h3 class="card-title">Cấu hình đuôi miền mới</h3>
                        </div>
                        <form action="<?= url('admin/services/domains/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Đuôi miền (Không bao gồm dấu chấm)</label>
                                    <input type="text" class="form-control" name="duoi_mien" placeholder="Ví dụ: com, net, vn" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Giá đăng ký (VNĐ)</label>
                                            <input type="number" class="form-control" name="gia" placeholder="300000" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Giá gia hạn (VNĐ)</label>
                                            <input type="number" class="form-control" name="giahan" placeholder="350000" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON">Hiển thị (ON)</option>
                                        <option value="OFF">Ẩn (OFF)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/services/domains') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../layout/foot.php'; ?>

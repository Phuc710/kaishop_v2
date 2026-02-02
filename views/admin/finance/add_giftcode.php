<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm Giftcode</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/finance/giftcodes') ?>">Giftcode</a></li>
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
                            <h3 class="card-title">Tạo Giftcode mới</h3>
                        </div>
                        <form action="<?= url('admin/finance/giftcodes/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Mã Giftcode</label>
                                    <input type="text" class="form-control" name="giftcode" placeholder="Nhập mã code" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phần trăm giảm (%)</label>
                                            <input type="number" class="form-control" name="giamgia" placeholder="Ví dụ: 10" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Số lượng</label>
                                            <input type="number" class="form-control" name="soluong" value="100" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Dịch vụ áp dụng</label>
                                    <select class="form-control" name="type">
                                        <option value="code">Mã nguồn</option>
                                        <option value="logo">Tạo logo</option>
                                        <option value="domain">Tên miền</option>
                                        <option value="host">Hosting</option>
                                        <option value="all">Tất cả</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Số lượng đã dùng</label>
                                    <input type="number" class="form-control" name="dadung" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON">Hoạt động</option>
                                        <option value="OFF">Tạm tắt</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/finance/giftcodes') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

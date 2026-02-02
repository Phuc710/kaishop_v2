<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm ngân hàng</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/finance/banks') ?>">Banks</a></li>
                        <li class="breadcrumb-item active">Thêm bank</li>
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
                            <h3 class="card-title">Cấu hình ngân hàng mới</h3>
                        </div>
                        <form action="<?= url('admin/finance/banks/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Chủ tài khoản</label>
                                            <input type="text" class="form-control" name="ctk" placeholder="Nhập tên chủ TK" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Số tài khoản</label>
                                            <input type="text" class="form-control" name="stk" placeholder="Nhập số tài khoản" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loại (Bank Type)</label>
                                            <select class="form-control" name="type">
                                                <option value="MOMO">MOMO</option>
                                                <option value="MBBANK">MBBANK</option>
                                                <option value="VIETCOMBANK">VIETCOMBANK</option>
                                                <option value="THESIEURE">THESIEURE</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>STK ID (Nếu có)</label>
                                            <input type="text" class="form-control" name="stk_id" placeholder="ID của bank">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>User ID / Login ID</label>
                                            <input type="text" class="form-control" name="user_id" placeholder="Tên đăng nhập app bank">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="password" placeholder="Mật khẩu app bank">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Token / API Key</label>
                                            <input type="text" class="form-control" name="token" placeholder="Token từ site auto bank">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="ON">Bật auto</option>
                                                <option value="OFF">Tắt auto</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/finance/banks') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

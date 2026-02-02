<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sửa ngân hàng</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/finance/banks') ?>">Banks</a></li>
                        <li class="breadcrumb-item active">Sửa bank</li>
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
                            <h3 class="card-title">Cập nhật cấu hình: <?= htmlspecialchars($bank['ctk']); ?></h3>
                        </div>
                        <form action="<?= url('admin/finance/banks/edit/' . $bank['id']) ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Chủ tài khoản</label>
                                            <input type="text" class="form-control" name="ctk" value="<?= htmlspecialchars($bank['ctk']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Số tài khoản</label>
                                            <input type="text" class="form-control" name="stk" value="<?= htmlspecialchars($bank['stk']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loại (Bank Type)</label>
                                            <select class="form-control" name="type">
                                                <option value="MOMO" <?= $bank['type'] == 'MOMO' ? 'selected' : ''; ?>>MOMO</option>
                                                <option value="MBBANK" <?= $bank['type'] == 'MBBANK' ? 'selected' : ''; ?>>MBBANK</option>
                                                <option value="VIETCOMBANK" <?= $bank['type'] == 'VIETCOMBANK' ? 'selected' : ''; ?>>VIETCOMBANK</option>
                                                <option value="THESIEURE" <?= $bank['type'] == 'THESIEURE' ? 'selected' : ''; ?>>THESIEURE</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>STK ID (Nếu có)</label>
                                            <input type="text" class="form-control" name="stk_id" value="<?= htmlspecialchars($bank['stk_id'] ?? ''); ?>" placeholder="ID của bank">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>User ID / Login ID</label>
                                            <input type="text" class="form-control" name="user_id" value="<?= htmlspecialchars($bank['user_id'] ?? ''); ?>" placeholder="Tên đăng nhập app bank">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="password" value="<?= htmlspecialchars($bank['password'] ?? ''); ?>" placeholder="Mật khẩu app bank">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Token / API Key</label>
                                            <input type="text" class="form-control" name="token" value="<?= htmlspecialchars($bank['token'] ?? ''); ?>" placeholder="Token từ site auto bank">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="ON" <?= $bank['status'] == 'ON' ? 'selected' : ''; ?>>Bật auto</option>
                                                <option value="OFF" <?= $bank['status'] == 'OFF' ? 'selected' : ''; ?>>Tắt auto</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
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

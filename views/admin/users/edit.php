<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Chỉnh sửa thành viên</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/users') ?>">Thành viên</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin thành viên: <?= htmlspecialchars($toz_user['username']); ?></h3>
                        </div>
                        <div class="card-body">
                            <form action="<?= url('admin/users/edit/' . $toz_user['username']) ?>" method="post">
                                <div class="form-group">
                                    <label>USERNAME</label>
                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($toz_user['username']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>EMAIL</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($toz_user['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>SỐ DƯ HIỆN TẠI</label>
                                    <input type="text" class="form-control" readonly value="<?= number_format($toz_user['money']); ?>đ">
                                </div>
                                <div class="form-group">
                                    <label>LEVEL</label>
                                    <select class="form-control" name="level">
                                        <option value="0" <?= $toz_user['level'] == 0 ? 'selected' : ''; ?>>Thành Viên</option>
                                        <option value="9" <?= $toz_user['level'] == 9 ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>TRẠNG THÁI (BAND)</label>
                                    <select class="form-control" name="bannd">
                                        <option value="0" <?= $toz_user['bannd'] == 0 ? 'selected' : ''; ?>>Hoạt động (Un-band)</option>
                                        <option value="1" <?= $toz_user['bannd'] == 1 ? 'selected' : ''; ?>>Bị khóa (Band)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Lưu thông tin</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Cộng/Trừ Tiền -->
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> CỘNG TIỀN</h3>
                        </div>
                        <div class="card-body">
                            <form action="<?= url('admin/users/add-money/' . $toz_user['username']) ?>" method="POST">
                                <div class="form-group">
                                    <label>Số tiền cần cộng (*)</label>
                                    <input type="number" class="form-control" name="tien_cong" placeholder="Nhập số tiền" required>
                                </div>
                                <div class="form-group">
                                    <label>Lý do cộng (*)</label>
                                    <textarea class="form-control" name="rs_cong" placeholder="Nhập nội dung" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">Xác nhận cộng tiền</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-minus-circle mr-1"></i> TRỪ TIỀN</h3>
                        </div>
                        <div class="card-body">
                            <form action="<?= url('admin/users/sub-money/' . $toz_user['username']) ?>" method="POST">
                                <div class="form-group">
                                    <label>Số tiền cần trừ (*)</label>
                                    <input type="number" class="form-control" name="tien_tru" placeholder="Nhập số tiền" required>
                                </div>
                                <div class="form-group">
                                    <label>Lý do trừ (*)</label>
                                    <textarea class="form-control" name="rs_tru" placeholder="Nhập nội dung" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger">Xác nhận trừ tiền</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-4 text-center">
                    <a href="<?= url('admin/users') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

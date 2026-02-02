<?php require_once __DIR__ . '/../../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sửa gói hosting</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/hosting/packs') ?>">Gói hosting</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
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
                            <h3 class="card-title">Cập nhật gói: <?= htmlspecialchars($pack['name_host']); ?></h3>
                        </div>
                        <form action="<?= url('admin/services/hosting/packs/edit/' . $pack['id']) ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tên hiển thị (NAME HOST)</label>
                                            <input type="text" class="form-control" name="name_host" value="<?= htmlspecialchars($pack['name_host']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Mã gói (WHM Package Name)</label>
                                            <input type="text" class="form-control" name="code" value="<?= htmlspecialchars($pack['code']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Loại gói (TITLE HOST)</label>
                                            <select class="form-control" name="title_host">
                                                <option value="Start Up" <?= $pack['title_host'] == 'Start Up' ? 'selected' : ''; ?>>Start Up</option>
                                                <option value="Advanced" <?= $pack['title_host'] == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="Enterprise" <?= $pack['title_host'] == 'Enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Server quản lý</label>
                                            <select class="form-control" name="server_host" required>
                                                <?php foreach ($servers as $sv): ?>
                                                <option value="<?= $sv['id'] ?>" <?= $pack['server_host'] == $sv['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($sv['name_server']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Giá bán (VNĐ)</label>
                                            <input type="number" class="form-control" name="gia_host" value="<?= $pack['gia_host']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Dung lượng</label>
                                            <input type="text" class="form-control" name="dung_luong" value="<?= htmlspecialchars($pack['dung_luong']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Số tên miền khác</label>
                                            <input type="text" class="form-control" name="mien_khac" value="<?= htmlspecialchars($pack['mien_khac']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bí danh (Aliases/Parked Domains)</label>
                                            <input type="text" class="form-control" name="bi_danh" value="<?= htmlspecialchars($pack['bi_danh']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bảo mật (FireWall)</label>
                                            <input type="text" class="form-control" name="firewall" value="<?= htmlspecialchars($pack['firewall']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <a href="<?= url('admin/services/hosting/packs') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../../layout/foot.php'; ?>

<?php require_once __DIR__ . '/../../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm server hosting</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/hosting/packs') ?>">Hosting</a></li>
                        <li class="breadcrumb-item active">Thêm server</li>
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
                            <h3 class="card-title">Cấu hình kết nối WHM API</h3>
                        </div>
                        <form action="<?= url('admin/services/hosting/servers/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tên server</label>
                                            <input type="text" class="form-control" name="name_server" placeholder="Vd: Server Mỹ" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Link login (cPanel/DirectAdmin)</label>
                                            <input type="text" class="form-control" name="link_login" placeholder="https://sv1.example.com:2083" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tài khoản WHM (hoặc Token)</label>
                                            <input type="text" class="form-control" name="tk_whm" placeholder="root / reseller_user" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mật khẩu WHM (hoặc API Key)</label>
                                            <input type="text" class="form-control" name="mk_whm" placeholder="******" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>IP / Hostname Server</label>
                                            <input type="text" class="form-control" name="ip_whm" placeholder="1.2.3.4" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>NameServer 1 (NS1)</label>
                                            <input type="text" class="form-control" name="ns1" placeholder="ns1.example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>NameServer 2 (NS2)</label>
                                            <input type="text" class="form-control" name="ns2" placeholder="ns2.example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="ON">Hoạt động (ON)</option>
                                                <option value="OFF">Tạm ngưng (OFF)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/services/hosting/servers') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../../layout/foot.php'; ?>

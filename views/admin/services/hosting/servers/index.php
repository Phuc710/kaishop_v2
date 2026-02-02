<?php require_once __DIR__ . '/../../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Quản lý server hosting</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/hosting/packs') ?>">Hosting</a></li>
                        <li class="breadcrumb-item active">Servers</li>
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
                            <h3 class="card-title">Danh sách server WHM</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/services/hosting/servers/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Server
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="serverTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>TÊN SERVER</th>
                                            <th>IP / HOSTNAME</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>NGÀY THÊM</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($servers)): ?>
                                            <?php foreach ($servers as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><b><?= htmlspecialchars($row['name_server']); ?></b></td>
                                                <td><?= htmlspecialchars($row['ip_whm']); ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">HOẠT ĐỘNG</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">TẠM NGƯNG</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['time'])); ?></td>
                                                <td>
                                                    <a href="<?= url('admin/services/hosting/servers/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="<?= url('admin/services/hosting/servers?delete=' . $row['id']) ?>"
                                                        class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa server này?')">
                                                        <i class="fas fa-trash"></i> Xóa
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#serverTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../../../layout/foot.php'; ?>

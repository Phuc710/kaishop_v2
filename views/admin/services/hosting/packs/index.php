<?php require_once __DIR__ . '/../../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách gói hosting</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/hosting/servers') ?>">Hosting</a></li>
                        <li class="breadcrumb-item active">Gói hosting</li>
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
                            <h3 class="card-title">Quản lý gói hosting</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/services/hosting/packs/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Gói
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="packTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>TÊN GÓI</th>
                                            <th>SERVER</th>
                                            <th>DUNG LƯỢNG</th>
                                            <th>GIÁ TIỀN</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($packs)): ?>
                                            <?php foreach ($packs as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td>
                                                    <b><?= htmlspecialchars($row['name_host']); ?></b><br>
                                                    <small class="badge badge-info"><?= htmlspecialchars($row['title_host']); ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    global $connection;
                                                    $sv = $connection->query("SELECT name_server FROM list_server_host WHERE id = '{$row['server_host']}'")->fetch_assoc();
                                                    echo $sv ? htmlspecialchars($sv['name_server']) : 'N/A';
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['dung_luong']); ?></td>
                                                <td><b style="color: red;"><?= number_format($row['gia_host']); ?>đ</b></td>
                                                <td>
                                                    <a href="<?= url('admin/services/hosting/packs/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="<?= url('admin/services/hosting/packs?delete=' . $row['id']) ?>"
                                                        class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa gói này?')">
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
    $('#packTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../../../layout/foot.php'; ?>

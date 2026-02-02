<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách đuôi miền</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Domains</li>
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
                            <h3 class="card-title">Danh sách đuôi miền hỗ trợ</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/services/domains/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Miền
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="domainTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>ĐUÔI MIỀN</th>
                                            <th>GIÁ ĐĂNG KÝ</th>
                                            <th>GIÁ GIA HẠN</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($domains)): ?>
                                            <?php foreach ($domains as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><b style="color: blue;">.<?= htmlspecialchars($row['duoi_mien']); ?></b></td>
                                                <td><?= number_format($row['gia']); ?>đ</td>
                                                <td><?= number_format($row['giahan']); ?>đ</td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">HIỂN THỊ</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">ẨN</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('admin/services/domains/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="<?= url('admin/services/domains?delete=' . $row['id']) ?>"
                                                        class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa đuôi miền này?')">
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
    $('#domainTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../../layout/foot.php'; ?>

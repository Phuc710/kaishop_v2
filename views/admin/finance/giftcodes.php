<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Quản lý Giftcode</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Giftcode</li>
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
                            <h3 class="card-title">Danh sách Giftcode</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/finance/giftcodes/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Giftcode
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="giftTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>GIFTCODE</th>
                                            <th>GIẢM GIÁ</th>
                                            <th>DỊCH VỤ</th>
                                            <th>SỐ LƯỢNG</th>
                                            <th>ĐÃ DÙNG</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($giftcodes)): ?>
                                            <?php foreach ($giftcodes as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><span class="badge badge-info"><?= htmlspecialchars($row['giftcode']); ?></span></td>
                                                <td><?= $row['giamgia']; ?>%</td>
                                                <td><?= htmlspecialchars($row['type']); ?></td>
                                                <td><?= $row['soluong']; ?></td>
                                                <td><?= $row['dadung']; ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">ON</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">OFF</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('admin/finance/giftcodes/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
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
    $('#giftTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách bank auto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Banks</li>
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
                            <h3 class="card-title">Danh sách banks</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/finance/banks/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Bank
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>CHỦ TÀI KHOẢN</th>
                                            <th>SỐ TÀI KHOẢN</th>
                                            <th>LOẠI</th>
                                            <th>STATUS</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($banks)): ?>
                                            <?php foreach ($banks as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><?= htmlspecialchars($row['ctk']); ?></td>
                                                <td><?= htmlspecialchars($row['stk']); ?></td>
                                                <td><?= htmlspecialchars($row['type']); ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">ON</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">OFF</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('admin/finance/banks/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
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
    $('#datatable1').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

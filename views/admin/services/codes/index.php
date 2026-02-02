<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách mã nguồn</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Mã nguồn</li>
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
                            <h3 class="card-title">Danh sách mã nguồn</h3>
                            <div class="card-tools">
                                <a class="btn btn-primary btn-sm" href="<?= url('admin/services/codes/add') ?>">
                                    <i class="fas fa-plus-circle mr-1"></i>Thêm Mã Nguồn
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="codeTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>IMG</th>
                                            <th>TÊN CODE</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>GIÁ TIỀN</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($codes)): ?>
                                            <?php foreach ($codes as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td>
                                                    <img src="<?= htmlspecialchars($row['img']); ?>" alt="<?= htmlspecialchars($row['title']); ?>" style="width: 100px; height: auto; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1);">
                                                </td>
                                                <td><?= htmlspecialchars($row['title']); ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">HIỂN THỊ</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">ẨN</span>
                                                    <?php } ?>
                                                </td>
                                                <td><b style="color: red;"><?= number_format($row['gia']); ?>đ</b></td>
                                                <td>
                                                    <a href="<?= url('admin/services/codes/edit/' . $row['id']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="<?= url('admin/services/codes?delete=' . $row['id']) ?>"
                                                        class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa mã nguồn này?')">
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
    $('#codeTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../../layout/foot.php'; ?>

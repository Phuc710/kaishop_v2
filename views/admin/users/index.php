<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách thành viên</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Thành viên</li>
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
                            <h3 class="card-title">Danh sách thành viên</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>USERNAME</th>
                                            <th>EMAIL</th>
                                            <th>MONEY</th>
                                            <th>BAND</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><?= htmlspecialchars($row['username']); ?></td>
                                                <td><?= htmlspecialchars($row['email']); ?></td>
                                                <td><?= number_format($row['money']); ?>đ</td>
                                                <td><?= function_exists('bannd') ? bannd($row['bannd']) : $row['bannd']; ?></td>
                                                <td>
                                                    <a href="<?= url('admin/users/edit/' . $row['username']) ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(<?= $row['id'] ?>)">
                                                        <i class="fas fa-trash"></i> Xóa
                                                    </button>
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

function deleteUser(id) {
    if (confirm("Bạn có chắc muốn xoá thành viên này?")) {
        // Logic to delete can be AJAX call or form submit
        // For now, let's keep it simple or use the controller method
        $.post("<?= url('admin/users/delete') ?>", { user_id: id }, function(data) {
            if (data.success) {
                swal("Thành Công", data.message, "success");
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                swal("Lỗi", data.message, "error");
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>

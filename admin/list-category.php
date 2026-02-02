<?php require_once('head.php'); ?>
<?php require_once('nav.php'); ?>

<?php
// Delete category logic
if (isset($_GET['delete'])) {
    $delete = $_GET['delete'];
    // Optional: Check if products are using this category before deleting
    $delete_query = $connection->query("DELETE FROM `categories` WHERE `id` = '" . $delete . "' ");
    if ($delete_query) {
        echo '<script type="text/javascript">swal("Thành Công","Xóa danh mục thành công","success");setTimeout(function(){ location.href = "list-category.php" },500);</script>';
    } else {
        echo '<script type="text/javascript">swal("Lỗi","Lỗi máy chủ hoặc danh mục đang được sử dụng","error");setTimeout(function(){ location.href = "list-category.php" },500);</script>';
    }
}
?>

<div class="content-wrapper" style="height: calc(100dvh - 56.8px); overflow-y: auto;">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Quản lý danh mục</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Quản lý danh mục</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách danh mục</h3>
                            <div class="card-tools">
                                <a href="add-category.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Thêm mới
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="categoryTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên danh mục</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $connection->query("SELECT * FROM `categories` ORDER BY id DESC");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                    ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><?= $row['name']; ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">ON</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">OFF</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?= $row['created_at']; ?></td>
                                                <td>
                                                    <a href="edit-category.php?id=<?= $row['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="list-category.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?')">
                                                        <i class="fas fa-trash"></i> Xóa
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once('foot.php'); ?>

<script>
    $(function() {
        $("#categoryTable").DataTable({
            "responsive": true,
            "autoWidth": false,
        });
    });
</script>

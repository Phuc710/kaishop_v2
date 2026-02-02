<?php require_once('head.php'); ?>
<?php require_once('nav.php'); ?>

<?php
if (isset($_GET['delete'])) {
    $delete = $_GET['delete'];
    $create = $connection->query("DELETE FROM `products` WHERE `id` = '" . $delete . "' ");
    if ($create) {
        echo '<script type="text/javascript">swal("Thành Công","Xóa thành công","success");setTimeout(function(){ location.href = "list-product.php" },500);</script>';
    } else {
        echo '<script type="text/javascript">swal("Lỗi","Lỗi máy chủ","error");setTimeout(function(){ location.href = "list-product.php" },500);</script>';
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Danh sách sản phẩm</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Danh sách sản phẩm</li>
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
                            <h3 class="card-title">Danh sách sản phẩm</h3>
                            <div class="card-tools">
                                <a href="add-product.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Thêm mới
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Loại</th>
                                        <th>Giá</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $connection->query("SELECT * FROM `products` ORDER BY id DESC");
                                    if ($result) {
                                        while ($row = $result->fetch_assoc()) {
                                    ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><img src="<?= $row['image']; ?>" width="50px"></td>
                                                <td><?= $row['name']; ?></td>
                                                <td><?= $row['category']; ?></td>
                                                <td><?= number_format($row['price']); ?>đ</td>
                                                <td>
                                                    <?php if ($row['status'] == 'ON') { ?>
                                                        <span class="badge badge-success">ON</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-danger">OFF</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="edit-product.php?id=<?= $row['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                    <a href="list-product.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm">
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

<script>
    $(function() {
        $("#example1").DataTable({
            "responsive": true,
            "autoWidth": false,
        });
    });
</script>

<?php require_once('foot.php'); ?>

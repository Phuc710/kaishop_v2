<?php require_once('head.php'); ?>
<?php require_once('nav.php'); ?>

<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $row = $connection->query("SELECT * FROM `categories` WHERE `id` = '$id'")->fetch_array();
    if (!$row) {
        echo '<script type="text/javascript">swal("Lỗi","Danh mục không tồn tại","error");setTimeout(function(){ location.href = "list-category.php" },1000);</script>';
        die();
    }
} else {
    echo '<script type="text/javascript">swal("Lỗi","Thiếu ID danh mục","error");setTimeout(function(){ location.href = "list-category.php" },1000);</script>';
    die();
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $status = $_POST['status'];

    if (empty($name)) {
        echo '<script type="text/javascript">swal("Lỗi","Vui lòng nhập tên danh mục","error"); </script>';
    } else {
        $update = $connection->query("UPDATE `categories` SET `name` = '$name', `status` = '$status' WHERE `id` = '$id'");
        if ($update) {
            echo '<script type="text/javascript">swal("Thành Công","Cập nhật thành công","success");setTimeout(function(){ location.href = "list-category.php" },1000);</script>';
        } else {
            echo '<script type="text/javascript">swal("Lỗi","Lỗi máy chủ","error"); </script>';
        }
    }
}
?>

<div class="content-wrapper" style="height: calc(100dvh - 56.8px); overflow-y: auto;">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sửa danh mục</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Sửa danh mục</li>
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
                            <h3 class="card-title">Cập nhật danh mục</h3>
                        </div>
                        <form method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên danh mục</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= $row['name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="status">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="ON" <?= $row['status'] == 'ON' ? 'selected' : ''; ?>>ON</option>
                                        <option value="OFF" <?= $row['status'] == 'OFF' ? 'selected' : ''; ?>>OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <a href="list-category.php" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once('foot.php'); ?>

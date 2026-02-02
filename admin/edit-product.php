<?php require_once('head.php'); ?>
<?php require_once('nav.php'); ?>

<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $row = $connection->query("SELECT * FROM `products` WHERE `id` = '$id'")->fetch_array();
    if (!$row) {
        echo '<script type="text/javascript">swal("Lỗi","Sản phẩm không tồn tại","error");setTimeout(function(){ location.href = "list-product.php" },1000);</script>';
        die();
    }
} else {
    echo '<script type="text/javascript">swal("Lỗi","Thiếu ID sản phẩm","error");setTimeout(function(){ location.href = "list-product.php" },1000);</script>';
    die();
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_POST['image'];
    $category = $_POST['category'];
    $status = $_POST['status'];

    if (empty($name) || empty($price)) {
        echo '<script type="text/javascript">swal("Lỗi","Vui lòng nhập đầy đủ thông tin","error"); </script>';
    } else {
        $update = $connection->query("UPDATE `products` SET `name` = '$name', `price` = '$price', `description` = '$description', `image` = '$image', `category` = '$category', `status` = '$status' WHERE `id` = '$id'");
        if ($update) {
            echo '<script type="text/javascript">swal("Thành Công","Cập nhật thành công","success");setTimeout(function(){ location.href = "list-product.php" },1000);</script>';
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
                    <h1>Sửa sản phẩm</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Sửa sản phẩm</li>
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
                            <h3 class="card-title">Cập nhật sản phẩm</h3>
                        </div>
                        <form method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên sản phẩm</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= $row['name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="price">Giá</label>
                                    <input type="number" class="form-control" id="price" name="price" value="<?= $row['price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="category">Loại (Category)</label>
                                    <select class="form-control" name="category">
                                        <option value="gpt" <?= $row['category'] == 'gpt' ? 'selected' : ''; ?>>GPT</option>
                                        <option value="gemini" <?= $row['category'] == 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                                        <option value="netflix" <?= $row['category'] == 'netflix' ? 'selected' : ''; ?>>Netflix</option>
                                        <option value="other" <?= $row['category'] == 'other' ? 'selected' : ''; ?>>Khác</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="image">Link Ảnh</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="image" name="image" value="<?= $row['image']; ?>">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" onclick="openImageManager()">Chọn ảnh</button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="<?= $row['image']; ?>" alt="Preview" style="max-height: 200px; display: <?= empty($row['image']) ? 'none' : 'block'; ?>; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?= $row['description']; ?></textarea>
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
                                <a href="list-product.php" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once('foot.php'); ?>
<?php include 'image-manager-modal.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize Summernote
        $('#description').summernote({
            height: 250,
            codemirror: { theme: 'monokai' }
        });

        // Image Preview Listener
        $('#image').on('change keyup paste', function() {
            var url = $(this).val();
            if (url) {
                $('#imagePreview').attr('src', url).show();
            } else {
                $('#imagePreview').hide();
            }
        });
    });
</script>

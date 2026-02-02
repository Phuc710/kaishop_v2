<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php');?>
    <title>AdminLTE 3 | Dashboard</title>
    <?php require_once('nav.php');?>
</head>
<?php
if(isset($_GET['id'])){
    $id = $_GET['id'];
    $toz_code =  $connection->query("SELECT * FROM `khocode` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($connection,"UPDATE `khocode` SET 
    `title` = '".$_POST['title']."',
    `noidung` = '".$_POST['noidung']."',
    `gia` = '".$_POST['gia']."',
    `link` = '".$_POST['link']."',
    `list_img` = '".$_POST['list_img']."',
    `status` = '".$_POST['status']."',
    `img` = '".$_POST['img']."' WHERE `id` = '".$id."' ");

  if($create)
  {
    echo '<script type="text/javascript">if(!alert("Cập nhật thành công !")){window.history.back().location.reload();}</script>';
  }
  else
  {
    echo '<script type="text/javascript">if(!alert("Có lỗi xảy ra !")){window.history.back().location.reload();}</script>'; 
  }
}

?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Edit Code</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Code</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Small boxes (Stat box) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">EDIT CODE</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">TÊN CODE:</label>
                                            <input type="text" class="form-control" name="title"
                                                value="<?=$toz_code['title'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GIÁ</label>
                                            <input type="text" class="form-control" name="gia"
                                                value="<?=$toz_code['gia'];?>" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">LINK</label>
                                            <input type="text" class="form-control" name="link"
                                                value="<?=$toz_code['link'];?>" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">IMG</label>
                                            <input type="text" class="form-control" name="img"
                                                value="<?=$toz_code['img'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">LIST IMG</label>
                                            <textarea class="form-control" name="list_img"
                                                placeholder="Nhập link ảnh mô tả (mỗi dùng 1 link)"
                                                rows="6"><?=$toz_code['list_img'];?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Status</label>
                                            <select class="form-control" name="status">
                                                <option value="<?=$toz_code['status'];?>"><?=$toz_code['status'];?>
                                                </option>
                                                <option value="ON">Hiển Thị(ON)</option>
                                                <option value="OFF">Ẩn(OFF)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NOTE:</label>
                                            <textarea class="textarea" name="noidung" placeholder="Place some text here"
                                                style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"><?=$toz_code['noidung'];?></textarea>
                                        </div>

                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="listcode.php" class="btn btn-info">Về Danh Sách Code</a>
                                </div>
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                    <!-- /.row -->
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->


        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <?php require_once('foot.php');?>
</body>

</html>
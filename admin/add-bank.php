<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php');?>
    <title>AdminLTE 3 | Dashboard</title>
    <?php require_once('nav.php');?>
</head>
<?php
$now = time();
if (isset($_POST["submit"]))
{
    $create = $ketnoi->query("INSERT INTO `list_bank` SET 
    `ctk` = '".$_POST['ctk']."',
    `stk` = '".$_POST['stk']."',
    `loai` = '".$_POST['loai']."',
    `type` = '".$_POST['type']."',
    `img` = '".$_POST['img']."',
    `time` = '".$now."',
    `status` = '".$_POST['status']."' ");

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
                            <h1 class="m-0">Add Bank</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Add Bank</li>
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
                                    <h3 class="card-title">EDIT BANK</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">CHỦ TÀI KHOẢN</label>
                                            <input type="text" class="form-control" name="ctk">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">SỐ TÀI KHOẢN</label>
                                            <input type="text" class="form-control" name="stk">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Loại</label>
                                            <input type="text" class="form-control" name="loai" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Type</label>
                                            <input type="text" class="form-control" name="type" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Link ảnh QR</label>
                                            <input type="text" class="form-control" name="img" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Status</label>
                                            <select class="form-control" name="status">
                                                <option value="ON">Hiển Thị(ON)</option>
                                                <option value="OFF">Ẩn(OFF)</option>
                                            </select>
                                        </div>

                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="setbank.php" class="btn btn-info">Về Danh Sách Bank</a>
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
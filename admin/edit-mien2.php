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
    $toz_host =  $connection->query("SELECT * FROM `history_domain` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($connection,"UPDATE `history_domain` SET 
    
    
    `status` = '".$_POST['status']."' WHERE `id` = '".$id."' ");

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
                            <h1 class="m-0">Edit Host</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Host</li>
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
                                    <h3 class="card-title">EDIT HOST</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">USERNAME</label>
                                            <input readonly="" type="text" class="form-control" name="username"
                                                value="<?=$toz_host['username'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">DOMAIN</label>
                                            <input readonly="" type="text" class="form-control" name="domain"
                                                value="<?=$toz_host['tenmien'].$toz_host['duoimien'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NAME SERVER</label>
                                            <input readonly="" type="text" class="form-control" name="nameserver"
                                                value="<?=$toz_host['nameserver'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NGÀY MUA</label>
                                            <input readonly="" type="text" class="form-control" name="ngay_mua"
                                                value="<?=ngay($toz_host['ngay_mua']);?>">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NGÀY HẾT HẠN</label>
                                            <input readonly="" type="text" class="form-control" name="ngay_het"
                                                value="<?=ngay($toz_host['ngay_het']);?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">STATUS</label>
                                            <select class="form-control" name="status">
                                                <option value="<?=$toz_host['status'];?>">
                                                    <?=$toz_host['status'];?>
                                                </option>
                                                <option value="huy">Hủy Và Hoàn Tiền</option>
                                                <option value="hoatdong">Hoạt Động</option>
                                                <option value="dangtao">Đang Tạo</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="lsmuamien.php" class="btn btn-info">Về Lịch Sử Miền</a>
                                </div>
                                <!-- /end.card-body -->
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
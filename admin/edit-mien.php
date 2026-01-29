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
    $toz_domain =  $ketnoi->query("SELECT * FROM `khosubdomain` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($ketnoi,"UPDATE `khosubdomain` SET 
    `duoimien` = '".$_POST['duoimien']."',
    `gia` = '".$_POST['gia']."',
    `zone_id` = '".$_POST['zone_id']."',
    `thoihan` = '".$_POST['thoihan']."',
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
                            <h1 class="m-0">Edit domain</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit domain</li>
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
                                    <h3 class="card-title">EDIT DOMAIN</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">ĐUÔI MIỀN</label>
                                            <input type="text" class="form-control" name="duoimien"
                                                value="<?=$toz_domain['duoimien'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GIÁ</label>
                                            <input type="text" class="form-control" name="gia"
                                                value="<?=($toz_domain['gia']);?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">THỜI HẠN</label>
                                            <input type="text" class="form-control" name="thoihan"
                                                value="<?=($toz_domain['thoihan']);?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">ZONE ID</label>
                                            <input type="text" placeholder="Bỏ trống nếu là miền chính"
                                                class="form-control" value="<?=($toz_domain['zone_id']);?>"name="zone_id">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">STATUS</label>
                                            <select class="form-control" name="status">
                                                <option value="<?=$toz_domain['status'];?>">
                                                    <?=$toz_domain['status'];?>
                                                </option>
                                                <option value="ON">ON</option>
                                                <option value="OFF">OFF</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="domain.php" class="btn btn-info">Về Domain</a>
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
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
    $toz_host =  $connection->query("SELECT * FROM `list_server_host` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($connection,"UPDATE `list_server_host` SET 
    `name_server` = '".$_POST['name_server']."',
    `link_login` = '".$_POST['link_login']."',
    `tk_whm` = '".$_POST['tk_whm']."',
    `mk_whm` = '".$_POST['mk_whm']."',
    `ip_whm` = '".$_POST['ip_whm']."',
    `ns1` = '".$_POST['ns1']."',
    `ns2` = '".$_POST['ns2']."',
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
                            <h1 class="m-0">Edit Server Host</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Server Host</li>
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
                                    <h3 class="card-title">EDIT SERVER HOST</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NAME SERVER</label>
                                            <input type="text" class="form-control" name="name_server"
                                                value="<?=$toz_host['name_server'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">LINK LOGIN</label>
                                            <input type="text" class="form-control" name="link_login"
                                                value="<?=$toz_host['link_login'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">TÀI KHOẢN WHM</label>
                                            <input type="text" class="form-control" name="tk_whm"
                                                value="<?=$toz_host['tk_whm'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">MẬT KHẨU WHM</label>
                                            <input type="text" class="form-control" name="mk_whm"
                                                value="<?=$toz_host['mk_whm'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">IP WHM</label>
                                            <input type="text" class="form-control" name="ip_whm"
                                                value="<?=$toz_host['ip_whm'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NS1</label>
                                            <input type="text" class="form-control" name="ns1"
                                                value="<?=$toz_host['ns1'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NS2</label>
                                            <input type="text" class="form-control" name="ns2"
                                                value="<?=$toz_host['ns2'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">STATUS</label>
                                            <select class="form-control" name="status">
                                                <option value="<?=$toz_host['status'];?>">
                                                    <?=$toz_host['status'];?>
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
                                    <a href="listserverhost.php" class="btn btn-info">Về Server Host</a>
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
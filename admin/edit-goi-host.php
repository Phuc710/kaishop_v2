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
    $toz_host =  $connection->query("SELECT * FROM `list_host` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($connection,"UPDATE `list_host` SET 
    `name_host` = '".$_POST['name_host']."',
    `title_host` = '".$_POST['title_host']."',
    `server_host` = '".$_POST['server_host']."',
    `code` = '".$_POST['code']."',
    `gia_host` = '".$_POST['gia_host']."',
    `dung_luong` = '".$_POST['dung_luong']."',
    `mien_khac` = '".$_POST['mien_khac']."',
    `firewall` = '".$_POST['firewall']."',
    `bi_danh` = '".$_POST['bi_danh']."' WHERE `id` = '".$id."' ");

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
                            <h1 class="m-0">Edit Gói Host</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Gói Host</li>
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
                                    <h3 class="card-title">EDIT GÓI HOST</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NAME HOST</label>
                                            <input type="text" class="form-control" name="name_host"
                                                value="<?=$toz_host['name_host'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GÓI HOST</label>
                                            <input type="text" class="form-control" name="code"
                                                value="<?=$toz_host['code'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">TITLE HOST</label>
                                            <select class="form-control" name="title_host">
                                                <option value="<?=$toz_host['title_host'];?>">
                                                    <?=$toz_host['title_host'];?>
                                                    </option>
                                                    <option value="Start Up">Start Up</option>
                                                    <option value="Advanced">Advanced</option>
                                                    <option value="Enterprise">Enterprise</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">SERVER HOST</label>
                                            <select class="form-control" name="server_host">
                                                <?php
                                                $sv_host = $connection->query("SELECT * FROM `list_server_host` WHERE `id` = '".$toz_host['server_host']."' ")->fetch_array();
                                                ?>
                                                <option value="<?=$toz_host['server_host'];?>"><?=$sv_host['name_server'];?></option>
                                                <?php
                                                $result = mysqli_query($connection,"SELECT * FROM `list_server_host` WHERE `id` != '".$toz_host['server_host']."' ");
                                                while($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <option value="<?=$row['id'];?>"><?=$row['name_server'];?></option>
                                                <?php } ?>
                                            </select>


                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GIÁ HOST</label>
                                            <input type="text" class="form-control" name="gia_host"
                                                value="<?=$toz_host['gia_host'];?>" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">DUNG LƯỢNG</label>
                                            <input type="text" class="form-control" name="dung_luong"
                                                value="<?=$toz_host['dung_luong'];?>"
                                                placeholder="Nhập dung lượng gói, ví dụ 100 MB">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">MIỀN KHÁC</label>
                                            <input type="text" class="form-control" name="mien_khac"
                                                value="<?=$toz_host['mien_khac'];?>"
                                                placeholder="Không giới hạn/ 1 tên miền">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">BÍ DANH</label>
                                            <input type="text" class="form-control" name="bi_danh"
                                                value="<?=$toz_host['bi_danh'];?>"
                                                placeholder="Không giới hạn/ 1 tên miền">
                                                </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">FireWall</label>
                                            <input type="text" class="form-control" name="firewall"
                                                value="<?=$toz_host['firewall'];?>" placeholder="">
                                        </div>

                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="listgoihost.php" class="btn btn-info">Về Gói Gói Host</a>
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
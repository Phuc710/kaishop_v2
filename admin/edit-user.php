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
    $toz_user =  $ketnoi->query("SELECT * FROM `users` WHERE `username` = '$id' ")->fetch_array();
}
?>
<?php
$now = time();
if (isset($_POST["submit"]))
{
  $create = mysqli_query($ketnoi,"UPDATE `users` SET 
    `username` = '".$_POST['username']."',
    `email` = '".$_POST['email']."',
    `bannd` = '".$_POST['bannd']."',
    `level` = '".$_POST['level']."'WHERE `id` = '".$id."' ");

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
<?php
$now = time();
if (isset($_POST["cong_tien"]))
{
    $tien_cong = $_POST['tien_cong'];
$create = mysqli_query($ketnoi,"UPDATE `users` SET `money` = `money` + '".$tien_cong."', `tong_nap` = `tong_nap` + '".$tien_cong."' WHERE `username` = '".$id."'");
$reason = $_POST["rs_cong"];
$ketnoi->query("INSERT INTO `history_nap_bank` SET 
                `trans_id` = NULL,
                `username` = '".$id."',
                `type` = 'Hệ thống',
                `ctk` = '$reason',
                `stk` = NULL,
                `thucnhan` = '".$tien_cong."',
                `status` = 'hoantat',
                `time` = '$now'");


  if($create)
  {
    echo '<script type="text/javascript">if(!alert("Cộng tiền thành công!")){window.history.back().location.reload();}</script>';
  }
  else
  {
    echo '<script type="text/javascript">if(!alert("Có lỗi xảy ra !")){window.history.back().location.reload();}</script>'; 
  }
}
?>
<?php
if (isset($_POST["tru_tien"]))
{
    $create = mysqli_query($ketnoi,"UPDATE `users` SET `money` = `money` - '".$_POST['tien_tru']."', `tong_nap` = `tong_nap` - '".$_POST['tien_tru']."' WHERE `username` = '".$id."'");
    $reason = $_POST["rs_tru"];
    $ketnoi->query("INSERT INTO `history_nap_bank` SET 
                    `trans_id` = NULL,
                    `username` = '".$id."',
                    `type` = 'Hệ thống',
                    `ctk` = '$reason',
                    `stk` = NULL,
                    `thucnhan` = '-".$_POST['tien_tru']."',
                    `status` = 'hoantat',
                    `time` = '$now'");
    
  if($create)
  {
    echo '<script type="text/javascript">if(!alert("Trừ tiền thành công!")){window.history.back().location.reload();}</script>';
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
                            <h1 class="m-0">Edit User</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit User</li>
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
                                    <h3 class="card-title">EDIT USER</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">USERNAME</label>
                                            <input type="text" class="form-control" name="username"
                                                value="<?=$toz_user['username'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">EMAIL</label>
                                            <input type="text" class="form-control" name="email"
                                                value="<?=$toz_user['email'];?>" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">SỐ DƯ</label>
                                            <input type="text" class="form-control" readonly="" name="money"
                                                value="<?=$toz_user['money'];?>" placeholder="">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">LEVEL</label>
                                            <select class="form-control" name="level">
                                                <option value="<?=$toz_user['level'];?>">
                                                    <?php if($toz_user['level']==0){echo 'Thành Viên';}else{echo 'Admin'; };?>
                                                </option>
                                                <option value="0">Thành Viên</option>
                                                <option value="9">Admin</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">BAND</label>
                                            <select class="form-control" name="bannd">
                                                <option value="<?=$toz_user['bannd'];?>">
                                                    <?php if($toz_user['bannd']==0){echo 'Un Band';}else{echo 'Band'; };?>
                                                </option>
                                                <option value="0">Un Band</option>
                                                <option value="1">Band</option>
                                            </select>
                                        </div>

                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <div class="row">
                                    <section class="col-lg-6 connectedSortable">
                                        <div class="card card-success card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-money-bill-alt mr-1"></i>
                                                    CỘNG TIỀN
                                                </h3>
                                                <div class="card-tools">
                                                    <button type="button" class="btn bg-success btn-sm"
                                                        data-card-widget="collapse">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <button type="button" class="btn bg-warning btn-sm"
                                                        data-card-widget="maximize"><i class="fas fa-expand"></i>
                                                    </button>
                                                    <button type="button" class="btn bg-danger btn-sm"
                                                        data-card-widget="remove">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <form class="" action="" method="POST" role="form">
                                                    <div class="form-group">
                                                        <label>Amount (*)</label>
                                                        <input type="number" class="form-control" name="tien_cong"
                                                            placeholder="Nhập số tiền cần cộng" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Reason (*)</label>
                                                        <textarea class="form-control" name="rs_cong"
                                                            placeholder="Nhập nội dung nếu có"></textarea>
                                                    </div>
                                                    <br>
                                                    <button aria-label="" name="cong_tien"
                                                        class="btn btn-info btn-icon-left m-b-10" type="submit"><i
                                                            class="fas fa-paper-plane mr-1"></i>Submit</button>
                                                </form>
                                            </div>
                                        </div>
                                    </section>
                                    <section class="col-lg-6 connectedSortable">
                                        <div class="card card-danger card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-money-bill-alt mr-1"></i>
                                                    TRỪ TIỀN
                                                </h3>
                                                <div class="card-tools">
                                                    <button type="button" class="btn bg-success btn-sm"
                                                        data-card-widget="collapse">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <button type="button" class="btn bg-warning btn-sm"
                                                        data-card-widget="maximize"><i class="fas fa-expand"></i>
                                                    </button>
                                                    <button type="button" class="btn bg-danger btn-sm"
                                                        data-card-widget="remove">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <form class="" action="" method="POST" role="form">
                                                    <div class="form-group">
                                                        <label>Amount (*)</label>
                                                        <input type="number" class="form-control" name="tien_tru"
                                                            placeholder="Nhập số tiền cần trừ" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Reason (*)</label>
                                                        <textarea class="form-control" name="rs_tru"
                                                            placeholder="Nhập nội dung nếu có"></textarea>
                                                    </div>
                                                    <br>
                                                    <button aria-label="" name="tru_tien"
                                                        class="btn btn-info btn-icon-left m-b-10" type="submit"><i
                                                            class="fas fa-paper-plane mr-1"></i>Submit</button>
                                                </form>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="users.php" class="btn btn-info">Về Danh Sách User</a>
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
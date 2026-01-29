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
    $toz_web =  $ketnoi->query("SELECT * FROM `list_mau_web` WHERE `id` = '$id' ")->fetch_array();
}
?>
<?php
if (isset($_POST["submit"]))
{
  $create = mysqli_query($ketnoi,"UPDATE `list_mau_web` SET 
    `title` = '".$_POST['title']."',
    `mo_ta` = '".$_POST['mo_ta']."',
    `img` = '".$_POST['img']."',
    `list_img` = '".$_POST['list_img']."',
    `gia` = '".$_POST['gia']."',
    `gia_han` = '".$_POST['gia_han']."',
    `ns1` = '".$_POST['ns1']."',
    `ns2` = '".$_POST['ns2']."',
    `ip` = '".$_POST['ip']."',
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
                            <h1 class="m-0">Edit Mẫu Web</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Edit Mẫu Web</li>
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
                                    <h3 class="card-title">EDIT Mẫu Web</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form role="form" action="" method="post">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">TITLE</label>
                                            <input type="text" class="form-control" name="title"
                                                value="<?=$toz_web['title'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">IMG</label>
                                            <input type="text" class="form-control" name="img"
                                                value="<?=$toz_web['img'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">LIST IMG</label>
                                            <textarea class="form-control" name="list_img"
                                                placeholder="Nhập link ảnh mô tả (mỗi dùng 1 link)"
                                                rows="6"><?=$toz_web['list_img'];?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GIÁ</label>
                                            <input type="text" class="form-control" name="gia"
                                                value="<?=$toz_web['gia'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NS1</label>
                                            <input type="text" class="form-control" name="ns1"
                                                value="<?=$toz_web['ns1'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">NS2</label>
                                            <input type="text" class="form-control" name="ns2"
                                                value="<?=$toz_web['ns2'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">IP</label>
                                            <input type="text" class="form-control" name="ip"
                                                value="<?=$toz_web['ip'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">GIA HẠN</label>
                                            <input type="text" class="form-control" name="gia_han"
                                                value="<?=$toz_web['gia_han'];?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">STATUS</label>
                                            <select class="form-control" name="status">
                                                <option value="<?=$toz_web['status'];?>">
                                                    <?=$toz_web['status'];?>
                                                </option>
                                                <option value="ON">ON</option>
                                                <option value="OFF">OFF</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Mô tả</label>
                                            <textarea class="form-control" name="mo_ta"
                                                rows="6"><?=$toz_web['mo_ta'];?></textarea>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Lưu</button>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer clearfix">
                                    <a href="dsmauweb.php" class="btn btn-info">Về DS Mẫu Web</a>
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
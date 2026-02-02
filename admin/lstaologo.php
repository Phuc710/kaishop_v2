<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php');?>
    <title>AdminLTE 3 | Dashboard</title>
    <?php require_once('nav.php');?>

</head>
<?php
if (isset($_GET['del'])) {
    $del = $_GET['del'];
    echo '<script> if (confirm("Bạn có chắc muốn xoá đơn này")) {
        window.location="?delete='.$del.'";
    } else {
        alert("Đã huỷ");
        window.location="?ok";
    }
    </script>';
}
?>

<?php
if (isset($_GET['delete'])) 
{
    $delete = $_GET['delete'];

    $create = mysqli_query($connection,"DELETE FROM `khologo` WHERE `id` = '".$delete."' ");

    if ($create)
    {
      echo '<script type="text/javascript">swal("Thành Công","Xóa thành công","success");setTimeout(function(){ location.href = "chuyen-muc.php" },500);</script>'; 
    }
    else
    {
      echo '<script type="text/javascript">swal("Lỗi","Lỗi máy chủ","error");setTimeout(function(){ location.href = "chuyen-muc.php" },1000);</script>'; 
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
                            <h1 class="m-0">Đơn hàng logo</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Logo</li>
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
                                    <h3 class="card-title">Lịch sử tạo logo</h3>
                                </div>
                                <table id="datatable1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>IMG</th>
                                            <th>USERNAME</th>
                                            <th>YÊU CẦU</th>
                                            <th>NGÀY MUA</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i=1;
                                            $result = mysqli_query($connection,"SELECT * FROM `lich_su_tao_logo`ORDER BY `id` DESC ");
                                            while($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td>
                                                <?=$i++;?>
                                            </td>
                                            <?php 
                                            $id_code = $row['loaicode'];
                                            $code = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$id_code' ")->fetch_array();
                                            ?>
                                            <td>
                                                <img class="card-img-top" style="width: 250px;" src="<?=$code['img'];?>"
                                                    alt="<?=$code['img'];?>">
                                            </td>

                                            <td>
                                                <a><?=$row['username'];?></a>
                                            </td>

                                            <td>
                                                <a><?=$row['yeucau'];?></a>
                                            </td>

                                            <td>
                                                <a><?=ngay($row['time']);?></a>
                                            </td>

                                            <td>
                                                <a><?=host($row['status']);?></a>
                                            </td>
                                            <td>
                                                <a href="edit-don-logo.php?id=<?=$row['id'];?>" class="btn btn-default">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?del=<?=$row['id'];?>" class="btn btn-default">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.row -->
                        </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
    </div>
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
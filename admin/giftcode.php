<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php');?>
    <title>AdminLTE 3 | Dashboard</title>
    <?php require_once('nav.php');?>
    <?php 
$total_money = mysqli_fetch_assoc($connection->query("SELECT SUM(`money`) FROM `users` WHERE `money` >= 0 AND `level`!=1")) ['SUM(`money`)']; 

$total_thanhvien = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) FROM `users` ")) ['COUNT(*)']; 

$total_code = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) FROM `lich_su_mua_code` ")) ['COUNT(*)']; 

$total_thanhvienbanned = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) FROM `users` WHERE `bannd` = '1' ")) ['COUNT(*)']; 
?>
</head>
<?php
if (isset($_GET['del'])) {
    $del = $_GET['del'];
    echo '<script> if (confirm("Bạn có chắc muốn xoá code này")) {
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

    $create = mysqli_query($connection,"DELETE FROM `gift_code` WHERE `id` = '".$delete."' ");

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
                            <h1 class="m-0">Danh sách gift code</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Gift code</li>
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
                                    <h3 class="card-title">Danh sách gift code</h3>
                                    <div class="text-right">
                                        <a class="btn btn-primary btn-icon-left m-b-10" href="add-gift-code.php"
                                            type="button"><i class="fas fa-plus-circle mr-1"></i>Thêm gift code</a>
                                    </div>
                                </div>
                                <table id="datatable1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>GIFTCODE</th>
                                            <th>GIẢM GIÁ</th>
                                            <th>DỊCH VỤ</th>
                                            <th>SỐ LƯỢNG</th>
                                            <th>ĐÃ DÙNG</th>
                                            <th>THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                $result = mysqli_query($connection,"SELECT * FROM `gift_code` ORDER BY id desc ");
                                while($row = mysqli_fetch_assoc($result))
                                {
                                ?>
                                        <tr>
                                            <td><?=$row['id'];?></td>
                                            <td><?=$row['giftcode'];?></td>
                                            <td><?=($row['giamgia']);?>%</td>
                                            <td><?=$row['type'];?></td>
                                            <td><?=($row['soluong']);?></td>
                                            <td><?=($row['dadung']);?></td>
                                            </td>
                                            <td>
                                                <a href="edit-gift.php?id=<?=$row['id'];?>" class="btn btn-default">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?del=<?=$row['id'];?>" class="btn btn-default">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php }?>
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
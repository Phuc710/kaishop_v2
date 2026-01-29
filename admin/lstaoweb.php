<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once('head.php');?>
    <title>AdminLTE 3 | Dashboard</title>
    <?php require_once('nav.php');?>
</head>


<?php
if (isset($_GET['cho'])) {
  $id = $_GET['cho'];
  mysqli_query($ketnoi, "UPDATE `lich_su_tao_web` SET `status` = 'cho' WHERE `id` = '$id' ");
  echo '<meta http-equiv="refresh" content="0;url=/admin/lstaoweb.php">';
}
if (isset($_GET['xoa'])) {
  $id = $_GET['xoa'];
  mysqli_query($ketnoi, "UPDATE `lich_su_tao_web` SET `status` = 'xoa' WHERE `id` = '$id' ");
  echo '<meta http-equiv="refresh" content="0;url=/admin/lstaoweb.php">';
}

if (isset($_GET['duyet'])) {
  $id = $_GET['duyet'];
  mysqli_query($ketnoi, "UPDATE `lich_su_tao_web` SET `status` = 'hoatdong' WHERE `id` = '$id' ");
  echo '<meta http-equiv="refresh" content="0;url=/admin/lstaoweb.php">';
}
if (isset($_GET['tamkhoa'])) {
  $id = $_GET['tamkhoa'];
  mysqli_query($ketnoi, "UPDATE `lich_su_tao_web` SET `status` = 'tamkhoa' WHERE `id` = '$id' ");
  echo '<meta http-equiv="refresh" content="0;url=/admin/lstaoweb.php">';
}
if (isset($_GET['huy'])) {
  $id = $_GET['huy'];
  mysqli_query($ketnoi, "UPDATE `lich_su_tao_web` SET `status` = 'thatbai' WHERE `id` = '$id' ");
  echo '<meta http-equiv="refresh" content="0;url=/admin/lstaoweb.php">';
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
                            <h1 class="m-0">Danh sách đơn tạo web</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Danh sách đơn tạo web</li>
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
                                    <h3 class="card-title">Danh sách đơn tạo web</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <style>
                                        .pagination-container {
                                            text-align: right;
                                            margin-top: 10px;
                                        }

                                        #datatable1 {
                                            margin-bottom: 20px;
                                        }
                                        </style>

                                        <table id="datatable1" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th><b style="color: black">#</b></th>
                                                    <th><b style="color: black">Username</b></th>
                                                    <th><b style="color: black">Tên Miền</b></th>
                                                    <th><b style="color: black">Loại Web</b></th>
                                                    <th><b style="color: black">User Admin</b></th>
                                                    <th><b style="color: black">Pass Admin</b></th>
                                                    <th><b style="color: black">Trạng Thái</b></th>
                                                    <th><b style="color: black">Bắt Đầu</b></th>
                                                    <th><b style="color: black">Hết Hạn</b></th>
                                                    <th><b style="color: black">Thao Tác</b></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $result = mysqli_query($ketnoi, "SELECT * FROM `lich_su_tao_web` ORDER BY id desc ");
                                                while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td class="text-center"><?= $i++; ?></td>
                                                    <td class="text-center">
                                                        <?= $row['username']; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?= $row['domain']; ?>
                                                    </td>
                                                    <?php
                                                    $site = $ketnoi->query("SELECT * FROM `list_mau_web` WHERE `id` = '".$row['loaiweb']."' ")->fetch_array();
                                                    ?>
                                                    <td class="text-center">
                                                        <?=$site['title'];?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?=$row['user_admin'];?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?=$row['pass_admin'];?>
                                                    </td>
                                                    <td class="text-center"><?= status($row['status']); ?></td>
                                                    <td class="text-center"><?=ngay($row['ngay_mua']); ?></td>
                                                    <td class="text-center"><?=ngay($row['ngay_het']); ?></td>
                                                    <td><a href="?duyet=<?= $row['id']; ?>"
                                                            class="btn btn-success btn-sm">Duyệt</a><a
                                                            href="?huy=<?= $row['id']; ?>"
                                                            class="btn btn-info btn-sm">Hủy</a><a
                                                            href="?xoa=<?= $row['id']; ?>"
                                                            class="btn btn-danger btn-sm">Xóa</a><a
                                                            href="?tamkhoa=<?= $row['id']; ?>"
                                                            class="btn btn-warning btn-sm">Khoá</a></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>

                                        <div id="pagination-container" class="pagination-container"></div>

                                        <script>
                                        $(document).ready(function() {
                                            var $table = $('#datatable1');
                                            var $rows = $table.find('tbody tr');
                                            var $paginationContainer = $('#pagination-container');

                                            var limitPerPage = 10;
                                            var totalPages = Math.ceil($rows.length / limitPerPage);

                                            // Tạo chuyển trang
                                            if (totalPages > 1) {
                                                var pagination = '<ul class="pagination">';

                                                for (var i = 1; i <= totalPages; i++) {
                                                    pagination +=
                                                        '<li class="page-item"><a class="page-link" href="javascript:void(0);">' +
                                                        i + '</a></li>';
                                                }

                                                pagination += '</ul>';

                                                $paginationContainer.html(pagination).show();

                                                // Ẩn các bảng không ở trang hiện tại
                                                $rows.hide().slice(0, limitPerPage).show();

                                                // Sự kiện chuyển trang
                                                $paginationContainer.on('click', '.page-link', function() {
                                                    var currentPage = $(this).text();
                                                    var start = (currentPage - 1) * limitPerPage;
                                                    var end = start + limitPerPage;

                                                    $rows.hide().slice(start, end).show();

                                                    $paginationContainer.find('.page-link').removeClass(
                                                        'active');
                                                    $(this).addClass('active');
                                                });
                                            }
                                        });
                                        </script>
                                    </div>
                                    <!-- /.row -->
                                </div><!-- /.container-fluid -->
                            </div><!-- /.container-fluid -->
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
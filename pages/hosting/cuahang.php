<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__.'/../../hethong/head2.php';?>
    <?php
    $id_server = antixss($_GET['server']);
    $server = $ketnoi->query("SELECT * FROM `list_server_host` WHERE `id` = '$id_server' AND `status` = 'ON' ")->fetch_array();
    if(empty($server)){
        echo '<script type="text/javascript">setTimeout(function(){ location.href = "/" }, 0);</script>';
    }
    ?>
    <title>Danh Sách Các Loại Hosting | <?=$chungapi['ten_web'];?></title>
    <?php require __DIR__.'/../../hethong/nav.php';?>
</head>

    <!-- Secondary Nav End -->
    <main>
        <div class="w-breadcrumb-area">
            <div class="breadcrumb-img">
                <div class="breadcrumb-left">
                    <img src="<?=asset('assets/images/banner-bg-03.png')?>" alt="img">
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-12">
                        <nav aria-label="breadcrumb" class="page-breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="/">Trang chủ</a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">Hosting</li>
                            </ol>
                        </nav>
                        <h2 class="breadcrumb-title">
                            Các gói dịch vụ
                        </h2>
                    </div>
                </div>
            </div>
        </div>
 
        <section class="py-110">
            <div class="container">
                <div class="row justify-content-center">
                 <?php
                                    $result = mysqli_query($ketnoi,"SELECT * FROM `list_host` WHERE `server_host`='$id_server' ");
                                    while($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="price-card aos aos-init aos-animate">
                            <div class="price-title">
                                <div class="plan-type">
                                    <h3><?= $row['name_host'] ?> </h3>
                                </div>
                                <div class="amt-item">
                                    <h2><?= tien($row['gia_host']); ?>đ</h2>
                                    <p>Tháng</p>
                                </div>
                            </div>
                            <div class="price-features">
                                <h6>Includes</h6>
                                <ul>
                                    <li><span><i class="bx bx-check-double"></i></span>Dung lượng: <?= $row['dung_luong']; ?></li>
                                    <li><span><i class="bx bx-check-double"></i>Miền Khác : Không giới hạn</li>
                                    <li><span><i class="bx bx-check-double"></i>Miền Con : Không giới hạn</li>
                                    <li><span><i class="bx bx-check-double"></i>Băng Thông : Không giới hạn</li>
                                    <li><span><i class="bx bx-check-double"></i>Các thông số khác : Không giới hạn</li>
                                    <li><span><i class="bx bx-check-double"></i>Miễn phí chứng chỉ SSL</li>
                                    <li><span><i class="bx bx-check-double"></i>Vị trí máy chủ: Việt Nam</li>
                                </ul>
                            </div>
                            <div class="price-btn">
                                <a href="/thanh-toan-host/<?= $row['id']; ?>" class="btn-primary">Chọn gói<i class="feather-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                  <?php }?>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__.'/../../hethong/foot.php';?>
</html>
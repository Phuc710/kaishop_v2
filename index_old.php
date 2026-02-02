<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/hethong/head2.php'; ?>
    <title> Trang Chủ | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/hethong/nav.php'; ?>
</head>

<body>
    <main>
        <div class="breadcrumb-bar breadcrumb-bar-info">
            <div class="breadcrumb-img">
                <div class="breadcrumb-left">
                    <img src="<?= asset('assets/images/banner-bg-03.png') ?>" alt="img">
                </div>
            </div>
            <div class="container">
                <div class="row mt-3">
                    <div class="col-md-12 col-12">

                        <div class="slide-title-wrap">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="slider-title">
                                        <h2>Các danh mục dịch vụ của chúng tôi</h2>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="owl-nav service-nav nav-control nav-top"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div id="loading-indicator" class="loading-indicator">
                                <div class="spinner"></div>
                            </div>


                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('ma-nguon')?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/gfTDT4y.png" class="lazyLoad w-100"
                                                    height="180" alt="Code miễn phí và có phí"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('ma-nguon')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('ma-nguon')?>" class="truncate-2-lines">
                                                    Code miễn phí và có phí </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>

                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('tao-web')?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/lthtBve.png" class="lazyLoad w-100"
                                                    height="180" alt="Tạo Web giá rẻ"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('tao-web')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('tao-web')?>" class="truncate-2-lines">
                                                    Tạo Web giá rẻ </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>
                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('server-hosting')?>"><img
                                                    src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/BNrb3PJ.png" class="lazyLoad w-100"
                                                    height="180" alt="Hosting việt nam"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('server-hosting')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('server-hosting')?>" class="truncate-2-lines">
                                                    Hosting việt nam </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>
                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('tao-logo')?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/k1ueHlU.png" class="lazyLoad w-100"
                                                    height="180" alt="Thiết kế logo"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('tao-logo')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('tao-logo')?>" class="truncate-2-lines">
                                                    Thiết kế logo </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>
                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('mua-mien')?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/h1Q09vT.png" class="lazyLoad w-100"
                                                    height="180" alt="Mua tên miền giá rẻ"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('mua-mien')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('mua-mien')?>" class="truncate-2-lines">
                                                    Mua tên miền giá rẻ </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>

                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?=url('subdomain')?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/n3d3gxr.png" class="lazyLoad w-100"
                                                    height="180" alt="Subdomain giá rẻ"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="<?=url('subdomain')?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?=url('subdomain')?>" class="truncate-2-lines">
                                                    Subdomain giá rẻ </a>
                                            </h3>
                                        </div>
                                        </a>
                                    </div>
                            </article>



                        </div>
                    </div>
                    </section>
    </main>

    <?php
    if (isset($_SESSION['session'])) {
        ?>
        <!-- Main End -->
        <div class="modal new-modal fade" id="modal_notification" data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Thông báo</h5>
                        <button type="button" class="close-btn" data-bs-dismiss="modal"><span>×</span></button>
                    </div>
                    <div class="modal-body service-modal">
                        <?= $chungapi['thongbao']; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="dontShowAgainBtn">Không hiển thị lại trong 2
                            giờ</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>

        <script>
            function chuadn() {
                showMessage("Vui Lòng Đăng Nhập Để Xài Dịch Vụ!", "error");
            }
            chuadn();
        </script>
    <?php } ?>

    <?php require __DIR__ . '/hethong/foot.php'; ?>
</body>

</html>
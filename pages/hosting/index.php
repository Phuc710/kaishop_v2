<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Danh mục hosting | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    <style>
        .slide-title-wrap {
            margin-top: 70px;
        }
    </style>
</head>
<main>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="slide-title-wrap">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="slider-title">
                                <h2>Các server hosting của chúng tôi</h2>
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

                    <?php
                    $result = mysqli_query($connection, "SELECT * FROM `list_server_host` WHERE `status` = 'ON'");
                    while ($row = mysqli_fetch_assoc($result)) { ?>
                        <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category5">
                            <div class="gigs-grid">
                                <div class="gigs-img">
                                    <div>
                                        <a href="/server-hosting/<?= $row['id']; ?>">
                                            <img src="<?=asset('assets/images/lazyload.gif')?>"
                                                data-src="https://inet.vn/public/img/service/cloudserver_bg_illus.webp"
                                                class="lazyLoad w-100" height="180" alt="<?= $row['name_server']; ?>">
                                        </a>
                                    </div>
                                    <div class="fav-selection"></div>
                                    <div class="user-thumb">
                                        <a href="/server-hosting/<?= $row['id']; ?>"></a>
                                    </div>
                                </div>
                                <div class="gigs-content">
                                    <div class="gigs-info">
                                        <div class="star-rate"></div>
                                    </div>
                                    <div class="gigs-title">
                                        <h3>
                                            <a href="/server-hosting/<?= $row['id']; ?>" class="truncate-2-lines">
                                                <?= $row['name_server']; ?> </a>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>
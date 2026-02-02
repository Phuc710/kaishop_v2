<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Danh Mục Web | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-12">
                    <div class="slide-title-wrap">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="slider-title">
                                    <h2>Danh mục web của chúng tôi</h2>
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
                        $result = mysqli_query($connection, "SELECT * FROM `list_mau_web` WHERE `status` = 'ON'");
                        while ($row = mysqli_fetch_assoc($result)) { ?>

                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item category1 category3">

                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="/view-web/<?= $row['id']; ?>"><img src="<?=asset('assets/images/lazyload.gif')?>"
                                                    data-src="<?= $row['img']; ?>" class="lazyLoad w-100" height="180"
                                                    alt="<?= $row['title']; ?>"></a>
                                        </div>
                                        <div class="fav-selection">
                                            </a>
                                        </div>
                                        <div class="user-thumb">
                                            <a href="/view-web/<?= $row['id']; ?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-info">
                                            <a href="#" class="badge bg-primary-light">Php Script</a>
                                            <div class="star-rate">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="/view-web/<?= $row['id']; ?>" class="truncate-2-lines">
                                                    <?= $row['title']; ?> </a>
                                            </h3>
                                        </div>

                                        <div class="gigs-card-footer">
                                            <div class="gigs-share">
                                                <a href="#">
                                                    <i class="fa fa-share-alt"></i>
                                                </a>
                                            </div>
                                            <h5><?= tien($row['gia']); ?>đ</h5>
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
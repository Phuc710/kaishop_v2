<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title> Trang Chủ | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
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
                                <div class="col-md-12">
                                    <div class="slider-title">
                                        <h2>Các sản phẩm & dịch vụ</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Dynamic Products -->
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item">
                                        <div class="gigs-grid">
                                            <div class="gigs-img">
                                                <div class="">
                                                    <a href="<?= url('product/' . $product['id']) ?>">
                                                        <img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                            data-src="<?= $product['image'] ?>" class="lazyLoad w-100"
                                                            height="180" alt="<?= $product['name'] ?>">
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="gigs-content">
                                                <div class="gigs-title">
                                                    <h3>
                                                        <a href="<?= url('product/' . $product['id']) ?>" class="truncate-2-lines">
                                                            <?= $product['name'] ?>
                                                        </a>
                                                    </h3>
                                                </div>
                                                <div class="gigs-info">
                                                    <div class="star-rate">
                                                        <span>Price: <?= number_format($product['price']) ?>đ</span>
                                                    </div>
                                                </div>
                                                <div class="gigs-card-footer">
                                                    <a href="<?= url('product/' . $product['id']) ?>" class="btn btn-primary btn-block">Xem chi tiết</a>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <p class="text-center">Chưa có sản phẩm nào.</p>
                                </div>
                            <?php endif; ?>

                            <!-- Static Services (Legacy) -->
                            <article class="col-xl-3 col-lg-4 col-md-6 mb-4 grid-item">
                                <div class="gigs-grid">
                                    <div class="gigs-img">
                                        <div class="">
                                            <a href="<?= url('ma-nguon') ?>"><img src="<?= asset('assets/images/lazyload.gif') ?>"
                                                    data-src="https://i.imgur.com/gfTDT4y.png" class="lazyLoad w-100"
                                                    height="180" alt="Code miễn phí và có phí"></a>
                                        </div>
                                    </div>
                                    <div class="gigs-content">
                                        <div class="gigs-title">
                                            <h3>
                                                <a href="<?= url('ma-nguon') ?>" class="truncate-2-lines">Code miễn phí và có phí</a>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </article>
                            <!-- ... (Other static services can be added here if needed) ... -->

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>

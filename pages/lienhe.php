<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../hethong/head2.php'; ?>
    <title>Trang Liên Hệ | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../hethong/nav.php'; ?>
</head>

<body>
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
                                <li class="breadcrumb-item" aria-current="/lien-he">Liên hệ</li>
                            </ol>
                        </nav>
                        <h2 class="breadcrumb-title">
                            Liên hệ
                        </h2>
                    </div>
                </div>
            </div>
        </div>


        <section class="contact-section">

            <div class="contact-bottom bg-white">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex">
                            <div class="contact-grid w-100">
                                <div class="contact-content">
                                    <div class="contact-icon">
                                        <span>
                                            <img src="<?=asset('assets/images/contact-mail.svg')?>" alt="Icon">
                                        </span>
                                    </div>
                                    <div class="contact-details">
                                        <h6>Email Address</h6>
                                        <p> <?= $chungapi['email_cf']; ?> </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 d-flex">
                            <div class="contact-grid w-100">
                                <div class="contact-content">
                                    <div class="contact-icon">
                                        <span>
                                            <img src="<?=asset('assets/images/contact-phone.svg')?>" alt="Icon">
                                        </span>
                                    </div>
                                    <div class="contact-details">
                                        <h6>Phone Number</h6>
                                        <p> <?= $chungapi['sdt_admin']; ?> </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../hethong/foot.php'; ?>
</body>

</html>
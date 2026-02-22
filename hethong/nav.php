<link href="https://fonts.googleapis.com/css2?family=Signika:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    * {
        font-family: 'Signika', sans-serif;
        letter-spacing: 0.5px;
    }

    .pagination {
        display: inline-block;
    }

    .pagination a {
        color: black;
        float: left;
        padding: 8px 16px;
        text-decoration: none;
        transition: background-color 0.3s;
        border-radius: 50%;
        border: 1px solid #B4B4B4;
        margin: 0 4px;
        font-size: 18px;
    }

    .pagination a:hover {
        background-color: #ddd;
    }

    .pagination a.active {
        background-color: #ff6900;
        color: white;
        border: 1px solid #ff6900;
    }

    .pagination a:first-child,
    .pagination a:last-child {
        border-radius: 50%;
    }

    .shop-widget-btn {
        width: 100%;
        font-size: 15px;
        padding: 10px 20px;
        border-radius: 8px;
        color: #39404a;
        background: #e8e8e8;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all linear .3s;
    }

    .shop-widget-btn:hover {
        color: #fff;
        background: #ff6900
    }

    .shop-widget-btn i {
        margin-right: 8px;
        margin-top: -1px
    }
</style>


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/glightbox.css') ?>" />
<link rel="stylesheet" href="<?= asset('assets/css/aos.css') ?>" />
<link rel="stylesheet" href="<?= asset('assets/css/nice_select.css') ?>" />
<link href="<?= asset('assets/css/quill_core.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/quill_snow.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/bootstrap.css') ?>" rel="stylesheet" />

<link href="<?= asset('assets/css/swiper.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/job_post.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/responsive.css') ?>" rel="stylesheet" />
<link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/notify.css') ?>" />
<script src="<?= asset('assets/js/notify.js') ?>"></script>
<script src="<?= asset('assets/js/jquery.js') ?>"></script>
<script src="<?= asset('assets/js/lazyload.js') ?>"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css">
<script src="<?= asset('assets/js/sweetalert.js') ?>"></script>
<script src="<?= asset('assets/js/swal_helper.js') ?>"></script>
<link rel="stylesheet" href="<?= asset('assets/css/flatpickr.css') ?>">
<script src="<?= asset('assets/js/flatpickr.js') ?>"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<link
    href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/slick.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/slick_theme.css') ?>">
<link rel="stylesheet" type="text/css" href="<?= asset('assets/css/datatables.css') ?>">
<script type="text/javascript" charset="utf8" src="<?= asset('assets/js/datatables.js') ?>"></script>

<style>
    .slider-card {
        margin-top: 20px;
    }

    .slider .service-img-wrap img {
        width: 100%;
        height: auto;
        border-radius: 8px;
    }

    .slider-nav-thumbnails img {
        width: 80px;
        height: auto;
        margin: 5px;
        border: 2px solid #ddd;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }

    .slider-nav-thumbnails img:hover,
    .slider-nav-thumbnails .slick-current img {
        border-color: #007bff;
    }
</style>

<style>
    body {
        font-family: 'Roboto', sans-serif !important;
    }

    .gigs-img {
        position: relative;
        overflow: hidden;
    }

    .gigs-img img {
        transition: transform 0.3s ease;
    }

    .gigs-img:hover img {
        transform: scale(1.1);
    }

    .gigs-img .user-thumb img {
        transition: none;
        transform: none;
    }

    .gigs-img:hover {
        background-image: url('https://i.imgur.com/7FUvCBL.png');
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
    }

    .gigs-img::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(1);
        width: 70px;
        height: 70px;
        background-image: url('https://i.imgur.com/7FUvCBL.png');
        background-size: cover;
        background-position: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .gigs-img:hover::after {
        opacity: 1;
    }
</style>

<!-- showMessage() đã được define trong swal_helper.js -->

<link rel="stylesheet" type="text/css" href="<?= asset('assets/css/datatables.css') ?>">
<script type="text/javascript" charset="utf8" src="<?= asset('assets/js/datatables.js') ?>"></script>
</head>

<body>
    <div class="loader-wrapper">
        <span class="site-loader"> </span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var loadingOverlay = document.querySelector('.loader-wrapper');
            loadingOverlay.style.display = 'none';
        });
    </script>
    <!-- Menu Start -->
    <header class="header-primary">
        <div class="container">
            <nav class="navbar navbar-expand-xl justify-content-between">
                <a href="<?= url('') ?>">
                    <?php global $chungapi; ?>
                    <img src="<?= $chungapi['logo']; ?>" width="150" alt="dailycode.vn" />
                </a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="d-block d-xl-none">
                            <div class="logo">
                                <a href="<?= url('') ?>"><img src="<?= $chungapi['logo']; ?>" width="150"
                                        alt="dailycode.vn" /></a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('') ?>" role="button" aria-expanded="false">Trang chủ</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" aria-expanded="false">Nạp tiền</a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="<?= url('nap-bank') ?>" class="dropdown-item"><span>Ngân hàng tự
                                            động</span></a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" aria-expanded="false">Lịch sử</a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="<?= url('history-code') ?>" class="dropdown-item"><span>Lịch sử mua mã
                                            nguồn</span></a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('lien-he') ?>">Liên hệ</a>
                        </li>
                    </ul>
                </div>
                <?php
                global $chungapi, $username, $user;
                if (isset($_SESSION['session'])) {
                    ?>
                    <div class="navbar-right d-flex align-items-center gap-2">
                        <div class="gtranslate_wrapper"></div>
                        <div class="align-items-center">
                            <div class="dropdown">
                                <button type="button" class="d-flex header-widget" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <img src="<?= asset('assets/images/avt.png') ?>" class="rounded-circle w-40 me-1"
                                        alt="">
                                    <span>
                                        <p class="text-uppercase"><?= $username; ?></p>
                                        <p style="color:red;"><?= tien($user['money']); ?>đ</p>
                                    </span>
                                </button>
                                <ul class="dashboard-profile dropdown-menu"
                                    style="position: absolute;inset: 0px 0px auto auto;margin: 0px;transform: translate3d(0px, 58.4px, 0px);">
                                    <li>
                                        <?php if ($user['level'] == '9') { ?>
                                            <a class="dashboard-profile-item dropdown-item" href="<?= url('admin') ?>"><i
                                                    class="fa-solid fa-gear"></i> Admin</a>
                                        </li>
                                        <li>
                                        <?php } ?>
                                        <a class="dashboard-profile-item dropdown-item" href="<?= url('profile') ?>"><i
                                                class="fa fa-user me-1 fs-10"></i>Tài khoản</a>
                                    </li>
                                    <li>
                                        <a class="dashboard-profile-item dropdown-item" href="javascript:void(0)"
                                            onclick="SwalHelper.confirmLogout('<?= url('logout') ?>')">
                                            <i class="fa-solid fa-right-from-bracket me-1 fs-10"></i>Đăng xuất
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php
                } else {
                    echo '
                <div class="navbar-right d-flex align-items-center gap-2">
                    <div class="gtranslate_wrapper"></div>
                    <div class="align-items-center">
                        <a href="' . url('login') . '" class="btn-primary me-1">
                                Đăng nhập
                            </a>
                    </div>';
                }
                ?>
                    <button class="navbar-toggler d-block d-xl-none" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span></span>
                    </button>
                </div>
            </nav>
        </div>
    </header>
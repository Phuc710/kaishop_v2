<!DOCTYPE html>
<!--begin::Head-->

<head>
    <?php require __DIR__ . '/hethong/config.php'; ?>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>404 Không tìm thấy</title>
</head>
<!--end::Head-->

<!--begin::Body-->

<body id="kt_body" class="auth-bg">

    <!--Begin::Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5FS8GGP" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!--End::Google Tag Manager (noscript) -->

    <!--begin::Main-->
    <div class="d-flex flex-column flex-root">
        <!--begin::Authentication - 404 Page-->
        <div class="d-flex flex-column flex-center flex-column-fluid p-10">
            <!--begin::Illustration-->
            <img src="<?= asset('assets/media/illustrations/404-hd.png') ?>" alt="" class="mw-100 mb-10 h-lg-450px" />
            <!--end::Illustration-->

            <!--begin::Message-->
            <h1 class="fw-bold mb-10" style="color: #A3A3C7">Không tìm thấy</h1>
            <!--end::Message-->

            <!--begin::Link-->
            <a href="/" class="btn btn-primary">Về trang chủ</a>
            <!--end::Link-->
        </div>
        <!--end::Authentication - 404 Page-->

    </div>
    <!--end::Main-->




    <!--end::Javascript-->
</body>
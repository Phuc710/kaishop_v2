<?php require_once('config.php'); ?>
<!-- Required meta tags -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="<?= $chungapi['mo_ta']; ?>" />
<meta name="keywords" content="<?= $chungapi['key_words']; ?>" />
<meta property="og:locale" content="vi_VN" />
<meta property="og:type" content="article" />
<meta property="og:img" content="<?= $chungapi['banner']; ?>" />
<meta property="og:title" content="<?= $chungapi['mo_ta']; ?>" />
<meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST']; ?>" />
<meta property="og:site_name" content="<?= $chungapi['ten_web']; ?>" />
<link rel="shortcut icon" href="<?= $chungapi['favicon']; ?>" />

<!-- External CSS/JS Libraries -->
<link href="https://fonts.googleapis.com/css2?family=Signika:wght@600;700;800&display=swap" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/glightbox.min.css') ?>" />
<link rel="stylesheet" href="<?= asset('assets/css/aos.css') ?>" />
<link rel="stylesheet" href="<?= asset('assets/css/nice-select.css') ?>" />
<link href="<?= asset('assets/css/quill.core.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/quill.snow.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/bootstrap.min.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/font-awesome-all.min.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/fontawesome.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/swiper-bundle.min.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/job_post.css') ?>" rel="stylesheet" />
<link href="<?= asset('assets/css/resposive.css') ?>" rel="stylesheet" />
<link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/divineshop.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.css" />
<script src="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.min.js"></script>
<script src="<?= asset('assets/js/jquery.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/lazyload@2.0.0-rc.2/lazyload.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<link
    href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>

<!-- Global JS Variables -->
<script>
    const BASE_URL = '<?= url('') ?>';
    const ASSET_URL = '<?= asset('') ?>';
    const AJAX_URL = '<?= ajax_url('') ?>';
</script>
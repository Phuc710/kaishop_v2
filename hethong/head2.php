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
<link rel="stylesheet" href="<?= asset('assets/css/divineshop.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/user-pages.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/notify.css') ?>" />
<script src="<?= asset('assets/js/notify.js') ?>"></script>
<script src="<?= asset('assets/js/jquery.js') ?>"></script>
<script src="<?= asset('assets/js/lazyload.js') ?>"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css">
<script src="<?= asset('assets/js/sweetalert.js') ?>"></script>
<link rel="stylesheet" href="<?= asset('assets/css/flatpickr.css') ?>">
<script src="<?= asset('assets/js/flatpickr.js') ?>"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<link
    href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Tilt+Neon&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/slick.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/slick_theme.css') ?>">
<link rel="stylesheet" type="text/css" href="<?= asset('assets/css/datatables.css') ?>">
<script type="text/javascript" charset="utf8" src="<?= asset('assets/js/datatables.js') ?>"></script>

<!-- Global JS Variables -->
<script>
    const BASE_URL = '<?= url('') ?>';
    const ASSET_URL = '<?= asset('') ?>';
    const AJAX_URL = '<?= ajax_url('') ?>';
</script>

<?php if (isset($user['id'])): ?>
    <script src="<?= asset('assets/js/fingerprint.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                // Auto-update fingerprint on access
                const fp = await KaiFingerprint.collect();
                let fd = new FormData();
                fd.append('fingerprint', fp.hash);
                fd.append('fp_components', JSON.stringify(fp.components));
                fetch(BASE_URL + '/api/update-fingerprint', {
                    method: 'POST',
                    body: fd
                }).catch(() => { });
            } catch (e) { }
        });
    </script>
<?php endif; ?>
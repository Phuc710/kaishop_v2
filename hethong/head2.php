<?php
require_once('config.php');

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocalHost = $host === 'localhost'
    || strpos($host, 'localhost:') === 0
    || $host === '127.0.0.1'
    || strpos($host, '127.0.0.1:') === 0
    || $host === '[::1]'
    || strpos($host, '[::1]:') === 0;

$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$appDir = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
if ($appDir !== '' && strpos($requestPath, $appDir) === 0) {
    $requestPath = substr($requestPath, strlen($appDir));
}
if ($requestPath === '') {
    $requestPath = '/';
}

$siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
$defaultTitle = $siteName;
$defaultDescription = (string) ($chungapi['mo_ta'] ?? 'Dịch vụ số chất lượng cao.');
$defaultKeywords = (string) ($chungapi['key_words'] ?? '');
$defaultImage = (string) ($chungapi['banner'] ?? ($chungapi['logo'] ?? ''));
$defaultCanonical = url(ltrim($requestPath, '/'));

$seoTitleValue = isset($seoTitle) && trim((string) $seoTitle) !== '' ? trim((string) $seoTitle) : $defaultTitle;
$seoDescriptionValue = isset($seoDescription) && trim((string) $seoDescription) !== '' ? trim((string) $seoDescription) : $defaultDescription;
$seoKeywordsValue = isset($seoKeywords) && trim((string) $seoKeywords) !== '' ? trim((string) $seoKeywords) : $defaultKeywords;
$seoCanonicalValue = isset($seoCanonical) && trim((string) $seoCanonical) !== '' ? trim((string) $seoCanonical) : $defaultCanonical;
$seoImageValue = isset($seoImage) && trim((string) $seoImage) !== '' ? trim((string) $seoImage) : $defaultImage;

$privatePaths = [
    '/login',
    '/register',
    '/password-reset',
    '/profile',
    '/password',
    '/history-balance',
    '/history-code',
    '/history-orders',
    '/deposit',
    '/bao-tri',
];
$isPrivatePage = false;
foreach ($privatePaths as $p) {
    if ($requestPath === $p || (substr($p, -1) !== '/' && strpos($requestPath, $p . '/') === 0)) {
        $isPrivatePage = true;
        break;
    }
}

$seoRobotsValue = isset($seoRobots) && trim((string) $seoRobots) !== ''
    ? trim((string) $seoRobots)
    : ($isPrivatePage ? 'noindex, nofollow' : 'index, follow');

$pageAssets = isset($GLOBALS['pageAssets']) && is_array($GLOBALS['pageAssets']) ? $GLOBALS['pageAssets'] : [];

$lightweightPaths = [
    '/login',
    '/register',
    '/password-reset',
    '/profile',
    '/password',
    '/history-balance',
    '/history-code',
    '/history-orders',
    '/deposit',
    '/chinh-sach',
    '/dieu-khoan',
    '/bao-tri',
];
$isLightweightPath = false;
foreach ($lightweightPaths as $p) {
    if ($requestPath === $p || (substr($p, -1) !== '/' && strpos($requestPath, $p . '/') === 0)) {
        $isLightweightPath = true;
        break;
    }
}
$defaultInteractiveBundle = !$isLightweightPath;

$resolvedAssetFlags = [
    'interactive_bundle' => array_key_exists('interactive_bundle', $pageAssets) ? (bool) $pageAssets['interactive_bundle'] : $defaultInteractiveBundle,
    'datatables' => array_key_exists('datatables', $pageAssets) ? (bool) $pageAssets['datatables'] : ($requestPath === '/history-code'),
    'flatpickr' => array_key_exists('flatpickr', $pageAssets) ? (bool) $pageAssets['flatpickr'] : ($requestPath === '/history-code'),
    'turnstile' => array_key_exists('turnstile', $pageAssets) ? (bool) $pageAssets['turnstile'] : false,
];

if ($isLocalHost) {
    $resolvedAssetFlags['turnstile'] = false;
}

$GLOBALS['pageAssetFlagsResolved'] = $resolvedAssetFlags;

$ogType = isset($seoOgType) && trim((string) $seoOgType) !== '' ? trim((string) $seoOgType) : 'website';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoKeywordsValue !== ''): ?>
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywordsValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<meta name="robots" content="<?= htmlspecialchars($seoRobotsValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="theme-color" content="#ff6900">
<link rel="canonical" href="<?= htmlspecialchars($seoCanonicalValue, ENT_QUOTES, 'UTF-8') ?>">

<meta property="og:locale" content="vi_VN">
<meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($seoCanonicalValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoImageValue !== ''): ?>
    <meta property="og:image" content="<?= htmlspecialchars($seoImageValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoImageValue !== ''): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImageValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<link rel="shortcut icon" href="<?= htmlspecialchars((string) ($chungapi['favicon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Signika:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.0/css/boxicons.min.css">

<link rel="stylesheet" href="<?= asset('assets/css/nice_select.css') ?>">
<link href="<?= asset('assets/css/bootstrap.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/job_post.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/responsive.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/divineshop.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/user-pages.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/notify.css') ?>">

<?php if (!empty($resolvedAssetFlags['interactive_bundle'])): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/glightbox.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/aos.css') ?>">
    <link href="<?= asset('assets/css/quill_core.css') ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/quill_snow.css') ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/swiper.css') ?>" rel="stylesheet">
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['flatpickr'])): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/flatpickr.css') ?>">
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['datatables'])): ?>
    <link rel="stylesheet" type="text/css" href="<?= asset('assets/css/datatables.css') ?>">
<?php endif; ?>

<script src="<?= asset('assets/js/jquery.js') ?>"></script>
<script src="<?= asset('assets/js/notify.js') ?>"></script>
<script src="<?= asset('assets/js/sweetalert.js') ?>"></script>
<script src="<?= asset('assets/js/swal_helper.js') ?>"></script>
<script src="<?= asset('assets/js/lazyload.js') ?>"></script>

<?php if (!empty($resolvedAssetFlags['flatpickr'])): ?>
    <script src="<?= asset('assets/js/flatpickr.js') ?>"></script>
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['datatables'])): ?>
    <script type="text/javascript" charset="utf8" src="<?= asset('assets/js/datatables.js') ?>"></script>
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['turnstile'])): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

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
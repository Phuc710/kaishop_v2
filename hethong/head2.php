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
    // Split heavy interactive assets so pages can opt out safely without disabling all scripts.
    'vendor_quill' => array_key_exists('vendor_quill', $pageAssets) ? (bool) $pageAssets['vendor_quill'] : $defaultInteractiveBundle,
    'vendor_glightbox' => array_key_exists('vendor_glightbox', $pageAssets) ? (bool) $pageAssets['vendor_glightbox'] : $defaultInteractiveBundle,
    'vendor_swiper' => array_key_exists('vendor_swiper', $pageAssets) ? (bool) $pageAssets['vendor_swiper'] : $defaultInteractiveBundle,
    'vendor_aos' => array_key_exists('vendor_aos', $pageAssets) ? (bool) $pageAssets['vendor_aos'] : $defaultInteractiveBundle,
    'vendor_isotope' => array_key_exists('vendor_isotope', $pageAssets) ? (bool) $pageAssets['vendor_isotope'] : $defaultInteractiveBundle,
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

<?php
$resolveSiteIconUrl = static function ($path): string {
    $cleanPath = trim(preg_replace('/\s+/', '', (string) $path));
    if ($cleanPath === '') {
        return '';
    }

    if (preg_match('~^(?:https?:)?//|^(?:data|blob):~i', $cleanPath)) {
        return $cleanPath;
    }

    return asset(ltrim($cleanPath, '/'));
};

$appendIconVersion = static function (string $href, string $version): string {
    if ($href === '' || $version === '') {
        return $href;
    }

    return $href . (str_contains($href, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
};

$faviconHref = '';
$faviconVersion = trim((string) ($chungapi['updated_at'] ?? time()));
foreach (
    [
        (string) ($chungapi['favicon'] ?? ''),
        (string) ($chungapi['logo'] ?? ''),
        'assets/images/header_logo.gif',
    ] as $iconCandidate
) {
    if (trim($iconCandidate) === '')
        continue;
    $resolvedIcon = $resolveSiteIconUrl($iconCandidate);
    if ($resolvedIcon !== '') {
        $faviconHref = $resolvedIcon;
        break;
    }
}
$faviconHref = $appendIconVersion($faviconHref, (string) $faviconVersion);
?>
<link rel="icon" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">
<link rel="shortcut icon" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
<link href="https://fonts.googleapis.com/css2?family=Signika:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" media="print"
    onload="this.media='all'">
<noscript>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</noscript>

<link rel="stylesheet" href="<?= asset('assets/css/nice_select.css') ?>" media="print" onload="this.media='all'">
<link href="<?= asset('assets/css/bootstrap.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/job_post.css') ?>" rel="stylesheet">
<link href="<?= asset('assets/css/responsive.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/home.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/user-pages.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/notify.css') ?>" media="print" onload="this.media='all'">

<?php if (!empty($resolvedAssetFlags['interactive_bundle']) && !empty($resolvedAssetFlags['vendor_glightbox'])): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/glightbox.css') ?>">
<?php endif; ?>
<?php if (!empty($resolvedAssetFlags['interactive_bundle']) && !empty($resolvedAssetFlags['vendor_aos'])): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/aos.css') ?>">
<?php endif; ?>
<?php if (!empty($resolvedAssetFlags['interactive_bundle']) && !empty($resolvedAssetFlags['vendor_quill'])): ?>
    <link href="<?= asset('assets/css/quill_core.css') ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/quill_snow.css') ?>" rel="stylesheet">
<?php endif; ?>
<?php if (!empty($resolvedAssetFlags['interactive_bundle']) && !empty($resolvedAssetFlags['vendor_swiper'])): ?>
    <link href="<?= asset('assets/css/swiper.css') ?>" rel="stylesheet">
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['flatpickr'])): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/flatpickr.css') ?>">
<?php endif; ?>

<?php if (!empty($resolvedAssetFlags['datatables'])): ?>
    <link rel="stylesheet" type="text/css" href="<?= asset('assets/css/datatables.css') ?>">
<?php endif; ?>

<script src="<?= asset('assets/js/jquery.js') ?>"></script>
<script src="<?= asset('assets/js/notify.js') ?>" defer></script>
<script src="<?= asset('assets/js/sweetalert.js') ?>" defer></script>
<script src="<?= asset('assets/js/swal_helper.js') ?>" defer></script>
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
    window.KS_TIME_CONFIG = Object.assign({}, window.KS_TIME_CONFIG || {}, {
        appTimezone: '<?= htmlspecialchars(function_exists('app_timezone') ? app_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        displayTimezone: '<?= htmlspecialchars(function_exists('app_display_timezone') ? app_display_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        dbTimezone: '<?= htmlspecialchars(function_exists('app_db_timezone') ? app_db_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        locale: 'vi-VN'
    });
</script>
<script src="<?= asset('assets/js/time-utils.js') ?>" defer></script>
<script src="<?= asset('assets/js/maintenance-runtime.js') ?>" defer></script>
<script src="<?= asset('assets/js/confetti-effect.js') ?>" defer></script>

<?php if (isset($user['id'])): ?>
    <script src="<?= asset('assets/js/fingerprint.js') ?>"></script>
    <script>
        window.addEventListener('load', () => {
            setTimeout(async () => {
                try {
                    if (typeof KaiFingerprint === 'undefined') return;
                    const fp = await KaiFingerprint.collect();
                    if (!fp || !fp.hash) return;
                    const syncKey = 'ks_fp_sync_meta_v1';
                    const now = Date.now();
                    let lastSync = null;
                    try {
                        lastSync = JSON.parse(localStorage.getItem(syncKey) || 'null');
                    } catch (e) { }
                    if (lastSync && lastSync.hash === fp.hash && Number(lastSync.expiry || 0) > now) {
                        return;
                    }

                    const fd = new FormData();
                    fd.append('fingerprint', fp.hash);
                    fetch(BASE_URL + '/api/update-fingerprint', {
                        method: 'POST',
                        body: fd
                    }).then(() => {
                        try {
                            localStorage.setItem(syncKey, JSON.stringify({
                                hash: fp.hash,
                                expiry: now + (24 * 60 * 60 * 1000)
                            }));
                        } catch (e) { }
                    }).catch(() => { });
                } catch (e) { }
            }, 1500);
        });
    </script>
<?php endif; ?>
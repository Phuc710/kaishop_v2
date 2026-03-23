<?php
require_once __DIR__ . '/config.php';
global $chungapi;

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocalHost = $host === 'localhost'
    || strpos($host, 'localhost:') === 0
    || $host === '127.0.0.1'
    || strpos($host, '127.0.0.1:') === 0
    || $host === '[::1]'
    || strpos($host, '[::1]:') === 0;

$requestPath = function_exists('app_request_path') ? app_request_path(false) : ((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
$requestPathNoLocale = function_exists('app_request_path') ? app_request_path(true) : $requestPath;

$siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
$siteBaseUrl = rtrim((string) BASE_URL, '/');
$isEnglishPage = function_exists('app_is_english') && app_is_english();
$defaultTitle = $siteName;
$defaultDescription = (string) ($chungapi['mo_ta'] ?? 'Dịch vụ số chất lượng cao.');
$defaultKeywords = (string) ($chungapi['key_words'] ?? '');
$defaultImage = (string) ($chungapi['banner'] ?? ($chungapi['logo'] ?? ''));
$viCanonical = rtrim((string) BASE_URL, '/') . ($requestPathNoLocale === '/' ? '/' : $requestPathNoLocale);
$enCanonical = rtrim((string) BASE_URL, '/') . '/en' . ($requestPathNoLocale === '/' ? '' : $requestPathNoLocale);
$defaultCanonical = $isEnglishPage ? $enCanonical : $viCanonical;

$seoTitleValue = isset($seoTitle) && trim((string) $seoTitle) !== '' ? trim((string) $seoTitle) : $defaultTitle;
$seoDescriptionValue = isset($seoDescription) && trim((string) $seoDescription) !== '' ? trim((string) $seoDescription) : $defaultDescription;
$seoKeywordsValue = isset($seoKeywords) && trim((string) $seoKeywords) !== '' ? trim((string) $seoKeywords) : $defaultKeywords;
$seoCanonicalValue = isset($seoCanonical) && trim((string) $seoCanonical) !== '' ? trim((string) $seoCanonical) : $defaultCanonical;
$seoImageValue = isset($seoImage) && trim((string) $seoImage) !== '' ? trim((string) $seoImage) : $defaultImage;
$seoSchemaTypeValue = isset($seoSchemaType) && trim((string) $seoSchemaType) !== '' ? trim((string) $seoSchemaType) : 'WebPage';

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
    '/deposit-bank',
    '/balance',
    '/bao-tri',
];
$isPrivatePage = false;
foreach ($privatePaths as $p) {
    if ($requestPathNoLocale === $p || (substr($p, -1) !== '/' && strpos($requestPathNoLocale, $p . '/') === 0)) {
        $isPrivatePage = true;
        break;
    }
}

$seoRobotsValue = isset($seoRobots) && trim((string) $seoRobots) !== ''
    ? trim((string) $seoRobots)
    : ($isPrivatePage ? 'noindex, nofollow' : 'index, follow');

if ($isLocalHost) {
    $seoRobotsValue = 'noindex, nofollow, noarchive';
}

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
    '/deposit-bank',
    '/balance',
    '/chinh-sach',
    '/dieu-khoan',
    '/bao-tri',
];
$isLightweightPath = false;
foreach ($lightweightPaths as $p) {
    if ($requestPathNoLocale === $p || (substr($p, -1) !== '/' && strpos($requestPathNoLocale, $p . '/') === 0)) {
        $isLightweightPath = true;
        break;
    }
}
$defaultInteractiveBundle = !$isLightweightPath;

$resolvedAssetFlags = [
    'interactive_bundle' => array_key_exists('interactive_bundle', $pageAssets) ? (bool) $pageAssets['interactive_bundle'] : $defaultInteractiveBundle,
    'datatables' => array_key_exists('datatables', $pageAssets) ? (bool) $pageAssets['datatables'] : ($requestPathNoLocale === '/history-code'),
    'flatpickr' => array_key_exists('flatpickr', $pageAssets) ? (bool) $pageAssets['flatpickr'] : ($requestPathNoLocale === '/history-code'),
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
$geoRegion = $isEnglishPage ? 'GLOBAL' : 'VN';
$geoPlacename = $isEnglishPage ? 'International' : 'Việt Nam';
$languageName = $isEnglishPage ? 'English' : 'Vietnamese';
$ogLocale = $isEnglishPage ? 'en_US' : 'vi_VN';
$resolveAbsoluteUrl = static function (string $path) use ($siteBaseUrl): string {
    $cleanPath = trim($path);
    if ($cleanPath === '') {
        return $siteBaseUrl . '/';
    }

    if (preg_match('~^(?:https?:)?//|^(?:data|blob):~i', $cleanPath)) {
        if (strpos($cleanPath, '//') === 0) {
            $scheme = parse_url($siteBaseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $cleanPath;
        }

        return $cleanPath;
    }

    if ($cleanPath[0] !== '/') {
        $cleanPath = '/' . ltrim($cleanPath, '/');
    }

    return $siteBaseUrl . $cleanPath;
};
$schemaFilter = static function ($value): bool {
    if (is_array($value)) {
        return $value !== [];
    }

    return $value !== null && $value !== '';
};
$seoCanonicalValue = $resolveAbsoluteUrl($seoCanonicalValue);
$viCanonical = $resolveAbsoluteUrl($viCanonical);
$enCanonical = $resolveAbsoluteUrl($enCanonical);
$seoImageValue = $seoImageValue !== '' ? $resolveAbsoluteUrl($seoImageValue) : '';
$siteHomeUrl = $siteBaseUrl . '/';
$sitemapUrl = $resolveAbsoluteUrl(url('sitemap.xml'));
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoKeywordsValue !== ''): ?>
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywordsValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<meta name="robots" content="<?= htmlspecialchars($seoRobotsValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="googlebot" content="<?= htmlspecialchars($seoRobotsValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="theme-color" content="#e65100">
<meta name="author" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
<meta name="csrf-token"
    content="<?= htmlspecialchars(function_exists('csrf_token') ? csrf_token() : '', ENT_QUOTES, 'UTF-8') ?>">
<meta name="revisit-after" content="3 days">
<meta name="geo.region" content="<?= htmlspecialchars($geoRegion, ENT_QUOTES, 'UTF-8') ?>">
<meta name="geo.placename" content="Việt Nam">
<meta name="language" content="<?= htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars($seoCanonicalValue, ENT_QUOTES, 'UTF-8') ?>">
<link rel="alternate" hreflang="vi" href="<?= htmlspecialchars($viCanonical, ENT_QUOTES, 'UTF-8') ?>">
<link rel="alternate" hreflang="en" href="<?= htmlspecialchars($enCanonical, ENT_QUOTES, 'UTF-8') ?>">
<link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($viCanonical, ENT_QUOTES, 'UTF-8') ?>">
<link rel="sitemap" type="application/xml" title="Sitemap"
    href="<?= htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8') ?>">

<meta property="og:locale" content="<?= htmlspecialchars($ogLocale, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($seoCanonicalValue, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoImageValue !== ''): ?>
    <meta property="og:image" content="<?= htmlspecialchars($seoImageValue, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:url" content="<?= htmlspecialchars($seoImageValue, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($seoDescriptionValue, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:url" content="<?= htmlspecialchars($seoCanonicalValue, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($seoImageValue !== ''): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImageValue, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($seoTitleValue, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<?php

$appendIconVersion = static function (string $href, string $version): string {
    if ($href === '' || $version === '') {
        return $href;
    }

    $parsedHref = parse_url($href);
    if (is_array($parsedHref) && !empty($parsedHref['query'])) {
        parse_str((string) $parsedHref['query'], $queryParams);
        if (array_key_exists('v', $queryParams)) {
            return $href;
        }
    }

    return $href . (str_contains($href, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
};

$faviconHref = UrlHelper::resolveFavicon($chungapi['favicon'] ?? '', $chungapi['logo'] ?? '');
$faviconVersion = trim((string) ($chungapi['updated_at'] ?? TimeService::instance()->nowTs()));
$faviconHref = $appendIconVersion($faviconHref, (string) $faviconVersion);
$fallbackFaviconHref = asset('assets/images/kaishop_favicon.png');
$siteLogoUrl = $resolveAbsoluteUrl(UrlHelper::resolveIcon($chungapi['logo'] ?? '', 'assets/images/header_logo.gif'));
$organizationSameAs = array_values(array_unique(array_filter([
    trim((string) ($chungapi['fb_admin'] ?? '')),
    trim((string) ($chungapi['tele_admin'] ?? '')),
    trim((string) ($chungapi['support_tele'] ?? '')),
    trim((string) ($chungapi['tiktok_admin'] ?? '')),
    trim((string) ($chungapi['youtube_admin'] ?? '')),
], static fn($value) => $value !== '')));
$organizationId = $siteHomeUrl . '#organization';
$websiteId = $siteHomeUrl . '#website';
$webPageId = $seoCanonicalValue . '#webpage';
$contactPoint = array_filter([
    '@type' => 'ContactPoint',
    'contactType' => 'customer support',
    'url' => trim((string) ($chungapi['support_tele'] ?? ($chungapi['tele_admin'] ?? ''))),
    'email' => trim((string) ($chungapi['email_cf'] ?? '')),
    'telephone' => trim((string) ($chungapi['sdt_admin'] ?? '')),
    'availableLanguage' => ['Vietnamese', 'English'],
], $schemaFilter);
$organizationSchema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => $organizationId,
    'name' => $siteName,
    'url' => $siteHomeUrl,
    'logo' => $siteLogoUrl,
    'image' => $seoImageValue !== '' ? $seoImageValue : $siteLogoUrl,
    'description' => $seoDescriptionValue,
    'email' => trim((string) ($chungapi['email_cf'] ?? '')),
    'telephone' => trim((string) ($chungapi['sdt_admin'] ?? '')),
    'sameAs' => $organizationSameAs,
    'contactPoint' => $contactPoint !== [] ? [$contactPoint] : [],
    'hasMap' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d19186.820390896668!2d106.64489592286598!3d10.833848329042643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317529005ce32e29%3A0x92701134ab02bfba!2sTan%20Son%20Nhat%20Driving%20Range!5e0!3m2!1svi!2s!4v1774275991944!5m2!1svi!2s',
], $schemaFilter);
$websiteSchema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    '@id' => $websiteId,
    'url' => $siteHomeUrl,
    'name' => $siteName,
    'description' => $defaultDescription,
    'inLanguage' => $isEnglishPage ? 'en' : 'vi',
    'publisher' => ['@id' => $organizationId],
], $schemaFilter);
$webPageSchema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => $seoSchemaTypeValue,
    '@id' => $webPageId,
    'url' => $seoCanonicalValue,
    'name' => $seoTitleValue,
    'description' => $seoDescriptionValue,
    'inLanguage' => $isEnglishPage ? 'en' : 'vi',
    'isPartOf' => ['@id' => $websiteId],
    'about' => ['@id' => $organizationId],
    'publisher' => ['@id' => $organizationId],
    'primaryImageOfPage' => $seoImageValue !== '' ? $seoImageValue : null,
], $schemaFilter);
$emitIdentitySchema = $requestPathNoLocale === '/';
?>
<link rel="icon" href="<?= asset('assets/favicon/favicon.ico') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= asset('assets/favicon/apple-touch-icon.png') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/favicon/favicon-32x32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/favicon/favicon-16x16.png') ?>">
<link rel="shortcut icon" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($emitIdentitySchema): ?>
    <script type="application/ld+json">
                <?= json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
                </script>
    <script type="application/ld+json">
                <?= json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
                </script>
<?php endif; ?>
<script type="application/ld+json">
<?= json_encode($webPageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<script>
    (function () {
        const primaryHref = '<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>';
        const fallbackHref = '<?= htmlspecialchars($fallbackFaviconHref, ENT_QUOTES, 'UTF-8') ?>';
        if (!primaryHref || !fallbackHref || primaryHref === fallbackHref) {
            return;
        }

        const iconLoader = new Image();
        iconLoader.onerror = function () {
            document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]').forEach(function (el) {
                el.setAttribute('href', fallbackHref);
            });
        };
        iconLoader.src = primaryHref;
    })();
</script>

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
<link rel="stylesheet" href="<?= asset('assets/css/cursor.css') ?>">

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
    const BASE_URL = '<?= rtrim(url(''), '/') ?>';
    const ASSET_URL = '<?= rtrim(asset(''), '/') ?>';
    const AJAX_URL = '<?= rtrim(ajax_url(''), '/') ?>';
    window.KS_CSRF_TOKEN = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '', JSON_UNESCAPED_UNICODE) ?>;
    window.KAI_ASSET_URL = ASSET_URL;
    window.KAI_EXCHANGE_RATE = <?= (int) max(1, (int) get_setting('binance_rate_vnd', 25000)) ?>;
    window.KS_TIME_CONFIG = Object.assign({}, window.KS_TIME_CONFIG || {}, {
        appTimezone: '<?= htmlspecialchars(function_exists('app_timezone') ? app_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        displayTimezone: '<?= htmlspecialchars(function_exists('app_display_timezone') ? app_display_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        dbTimezone: '<?= htmlspecialchars(function_exists('app_db_timezone') ? app_db_timezone() : date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?>',
        locale: '<?= $isEnglishPage ? 'en-US' : 'vi-VN' ?>'
    });
</script>
<script src="<?= asset('assets/js/time-utils.js') ?>" defer></script>
<script src="<?= asset('assets/js/currency.js') ?>" defer></script>
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
                    if (window.KS_CSRF_TOKEN) {
                        fd.append('csrf_token', window.KS_CSRF_TOKEN);
                    }
                    fetch(BASE_URL + '/api/update-fingerprint', {
                        method: 'POST',
                        body: fd,
                        headers: window.KS_CSRF_TOKEN ? { 'X-CSRF-Token': window.KS_CSRF_TOKEN } : {}
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
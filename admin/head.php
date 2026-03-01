<?php require_once dirname(__DIR__) . '/hethong/config.php'; ?>
<?php
if (empty($_SESSION['admin'])) {
    $alertIp = $GLOBALS['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if ($alertIp != '1.1.1.1' && $alertIp != '::1') {
        $service = telegram_service();
        if ($service) {
            $service->send($alertIp . ' Truy cập trái phép vào Admin');
        }
    }

    header('Location: ' . url('/'));
    exit();
}
?>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$resolveAdminIconUrl = static function ($path): string {
    $cleanPath = trim(preg_replace('/\s+/', '', (string) $path));
    if ($cleanPath === '') {
        return '';
    }

    if (preg_match('~^(?:https?:)?//|^(?:data|blob):~i', $cleanPath)) {
        return $cleanPath;
    }

    return asset(ltrim($cleanPath, '/'));
};

$appendAdminIconVersion = static function (string $href, string $version): string {
    if ($href === '' || $version === '') {
        return $href;
    }

    return $href . (str_contains($href, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
};

$adminFaviconHref = '';
$adminFaviconVersion = trim((string) ($chungapi['updated_at'] ?? ''));
foreach (
    [
        (string) ($chungapi['favicon'] ?? ''),
        (string) ($chungapi['logo'] ?? ''),
        'assets/images/header_logo.gif',
    ] as $iconCandidate
) {
    $resolvedIcon = $resolveAdminIconUrl($iconCandidate);
    if ($resolvedIcon !== '') {
        $adminFaviconHref = $resolvedIcon;
        break;
    }
}
$adminFaviconHref = $appendAdminIconVersion($adminFaviconHref, $adminFaviconVersion);
?>
<link rel="icon" href="<?= htmlspecialchars($adminFaviconHref, ENT_QUOTES, 'UTF-8') ?>" />
<link rel="shortcut icon" href="<?= htmlspecialchars($adminFaviconHref, ENT_QUOTES, 'UTF-8') ?>" />
<?php
$adminNeedsFlatpickr = !empty($adminNeedsFlatpickr);
$adminNeedsSummernote = !empty($adminNeedsSummernote);
$adminCssVersion = (string) @filemtime(dirname(__DIR__) . '/assets/css/admin.css');
$adminPagesCssVersion = (string) @filemtime(dirname(__DIR__) . '/assets/css/admin-pages.css');
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/fontawesome-free/css/all.min.css"
    crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/icheck-bootstrap/icheck-bootstrap.min.css"
    crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/dist/css/adminlte.min.css"
    crossorigin="anonymous">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/overlayScrollbars/css/OverlayScrollbars.min.css"
    crossorigin="anonymous">
<?php if ($adminNeedsFlatpickr): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/flatpickr.css') ?>">
<?php endif; ?>
<?php if ($adminNeedsSummernote): ?>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/quangtuu2006/admin_lite@main/plugins/summernote/summernote-bs4.min.css"
        crossorigin="anonymous">
<?php endif; ?>

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet"
    crossorigin="anonymous">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>?v=<?= urlencode($adminCssVersion) ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin-pages.css') ?>?v=<?= urlencode($adminPagesCssVersion) ?>">

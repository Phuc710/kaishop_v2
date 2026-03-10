<?php
/**
 * Shared profile page shell header.
 * Variables:
 * - $userPageTitle (string)
 * - $activePage (string)
 * - $userPageAssetFlags (array)
 */
if (isset($userPageAssetFlags) && is_array($userPageAssetFlags)) {
    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], $userPageAssetFlags);
}

$userPageTitle = (string) ($userPageTitle ?? 'Thông tin tài khoản');
$activePage = (string) ($activePage ?? 'profile');

// Handle Favicon Synchronicity
if (!isset($userPageFaviconOverride) || trim((string) $userPageFaviconOverride) === '') {
    $userPageFaviconOverride = asset('assets/images/kaishop_favicon.png');
}
$userPageFaviconOverride = trim((string) $userPageFaviconOverride);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../../hethong/head2.php'; ?>
    <?php if ($userPageFaviconOverride !== ''): ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars($userPageFaviconOverride, ENT_QUOTES, 'UTF-8'); ?>">
        <link rel="shortcut icon" type="image/png"
            href="<?= htmlspecialchars($userPageFaviconOverride, ENT_QUOTES, 'UTF-8'); ?>">
        <link rel="apple-touch-icon" href="<?= htmlspecialchars($userPageFaviconOverride, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <title>
        <?= htmlspecialchars($userPageTitle, ENT_QUOTES, 'UTF-8'); ?> |
        <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop'), ENT_QUOTES, 'UTF-8'); ?>
    </title>
    <meta name="robots" content="noindex, nofollow">
</head>

<body>
    <?php require __DIR__ . '/../../../hethong/nav.php'; ?>

    <main class="user-page-main">
        <section class="user-page-section">
            <div class="container user-page-container">
                <div class="row gx-4 user-page-grid">
                    <div class="col-lg-3 col-md-4 user-page-sidebar-col d-none d-md-block">
                        <?php require __DIR__ . '/../../../hethong/user_sidebar.php'; ?>
                    </div>
                    <div class="col-lg-9 col-md-8 user-page-content-col">
<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
if (!class_exists('NavConfig')) {
    require_once dirname(__DIR__) . '/app/Helpers/NavConfig.php';
}


$requestPathNav = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$appDirNav = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
if ($appDirNav !== '' && strpos($requestPathNav, $appDirNav) === 0) {
    $requestPathNav = substr($requestPathNav, strlen($appDirNav));
}
if ($requestPathNav === '') {
    $requestPathNav = '/';
}
$authNavPaths = NavConfig::authNavPaths();
$isAuthNavPage = false;
foreach ($authNavPaths as $p) {
    if ($requestPathNav === $p || strpos($requestPathNav, $p . '/') === 0) {
        $isAuthNavPage = true;
        break;
    }
}
?>
<style>
    * {
        font-family: 'Roboto', sans-serif;
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

    .header-primary {
        /* box-shadow: rgba(0, 0, 0, 0.15) 0px 5px 15px 0px; */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 2000;
        background: #fff;
        border-bottom: 1px solid #f1f1f1;
    }

    main {
        padding-top: 80px;
    }

    .nav-divider {
        height: 1px;
        margin: 15px 25px;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.01), rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.01));
        list-style: none;
    }

    /* Core Navigation Classes */
    .ks-navbar {
        position: relative;
        min-height: 80px;
        padding: 0;
    }

    .ks-logo-container {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1050;
        padding: 10px 0;
    }

    .ks-logo-img {
        position: absolute;
        height: 110px;
        width: auto;
        min-width: 100px;
        object-fit: contain;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.05));
        top: 50%;
        transform: translateY(-50%);
    }

    @media (max-width: 1199.98px) {
        .ks-logo-container {
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            max-width: 100px;
        }

        .ks-logo-img {
            top: auto;
            transform: none;
            height: 100px;
            min-width: 50px;
            width: auto;
            position: static;
            margin: 0;
            max-height: none;
        }

        .mobile-sidebar-header {
            padding: 12px 16px 8px;
        }

        .mobile-sidebar-brand {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ks-mobile-sidebar-logo {
            display: block;
            width: 100px;
            height: auto;
            margin: 0;
        }
    }

    /* Active State Highlights */
    .navbar-nav .nav-link.active {
        color: #ff6900 !important;
        font-weight: 700;
    }

    .dropdown-item.active {
        background-color: rgba(255, 105, 0, 0.08);
        color: #ff6900 !important;
        font-weight: 600;
    }

    .mobile-nav-list .nav-link.active-item {
        background-color: rgba(255, 105, 0, 0.05);
        border-left: 4px solid #ff6900;
        color: #ff6900 !important;
    }

    .mobile-nav-list .nav-link.active-item .nav-text,
    .mobile-nav-list .nav-link.active-item .nav-icon {
        color: #ff6900 !important;
    }

    .navbar .navbar-nav .nav-link {
        display: flex !important;
        align-items: center !important;
        justify-content: center;
        gap: 8px;
        margin: 0 12px !important;
        white-space: nowrap;
        position: relative;
    }

    .navbar .navbar-nav .nav-link img {
        width: 20px;
        height: 20px;
        object-fit: contain;
        margin: 0 !important;
        order: 2;
        /* Put image after label */
    }

    /* Fixed the overlapping <i> icon (caret) */
    .navbar .dropdown-toggle::after {
        position: static !important;
        margin-left: 6px !important;
        vertical-align: middle !important;
        display: inline-block !important;
        line-height: inherit !important;
        top: auto !important;
        order: 3;
        /* Put caret after image */
    }
</style>
<div class="loader-wrapper" id="global-loader">
    <span class="site-loader"> </span>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var loadingOverlay = document.getElementById('global-loader');
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    });
</script>

<!-- Menu Start -->
<header class="header-primary<?= $isAuthNavPage ? ' auth-shell-header' : '' ?>">
    <div class="container">
        <nav class="navbar navbar-expand-xl justify-content-center ks-navbar">
            <a href="<?= url('') ?>" class="ks-logo-container"
                aria-label="Trang chủ <?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop', ENT_QUOTES, 'UTF-8') ?>">
                <?php global $chungapi; ?>
                <img src="<?= asset($chungapi['logo']); ?>" width="180" height="110"
                    alt="<?= htmlspecialchars($chungapi['ten_web'] ?? 'KaiShop', ENT_QUOTES, 'UTF-8') ?>"
                    draggable="false" class="ks-img-guard ks-logo-img" decoding="async" fetchpriority="high"
                    loading="eager" />
            </a>

            <!-- [PC NAVIGATION] - Horizontal Menu Center -->
            <div class="collapse navbar-collapse d-none d-xl-flex justify-content-center" id="navbarNavPC">
                <ul class="navbar-nav">
                    <?php
                    $publicHeaderItems = NavConfig::publicHeaderMenu();
                    foreach ($publicHeaderItems as $navItem):
                        $navType = (string) ($navItem['type'] ?? 'link');
                        $navLabel = htmlspecialchars((string) ($navItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $embedImg = (string) ($navItem['embed_img'] ?? '');
                        ?>
                        <?php
                        $isActive = NavConfig::isPublicLinkActive($navItem, $requestPathNav);
                        if ($navType === 'dropdown'):
                            ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle<?= $isActive ? ' active' : '' ?>" href="#" role="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <?= $navLabel ?>
                                    <?php if ($embedImg !== ''): ?>
                                        <img src="<?= htmlspecialchars($embedImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon">
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php foreach ((array) ($navItem['children'] ?? []) as $child): ?>
                                        <?php
                                        $childLabel = htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        $childIcon = (string) ($child['icon'] ?? '');
                                        $childImg = (string) ($child['embed_img'] ?? '');
                                        $childHref = (string) ($child['href'] ?? '#');
                                        $isChildActive = NavConfig::isPublicLinkActive($child, $requestPathNav);
                                        $isBinance = strpos(strtolower($childLabel), 'binance') !== false;
                                        ?>
                                        <li>
                                            <a href="<?= htmlspecialchars($childHref, ENT_QUOTES, 'UTF-8') ?>"
                                                class="dropdown-item d-flex align-items-center<?= $isChildActive ? ' active' : '' ?>">

                                                <?php if ($childImg !== '' || $childIcon !== ''): ?>
                                                    <span class="me-2 d-flex align-items-center justify-content-center"
                                                        style="width: 24px;">
                                                        <?php if ($childImg !== ''): ?>
                                                            <img src="<?= htmlspecialchars($childImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon"
                                                                style="height: <?= $isBinance ? '20px' : '16px' ?>;">
                                                        <?php elseif ($childIcon !== ''): ?>
                                                            <i class="<?= htmlspecialchars($childIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span><?= $childLabel ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $isActive ? ' active' : '' ?>"
                                    href="<?= htmlspecialchars((string) ($navItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (!empty($navItem['icon'])): ?>
                                        <i class="<?= htmlspecialchars((string) $navItem['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= $navLabel ?>
                                    <?php if ($embedImg !== ''): ?>
                                        <img src="<?= htmlspecialchars($embedImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon"
                                            draggable="false" class="ks-img-guard" decoding="async" loading="eager"
                                            fetchpriority="high">
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- [MOBILE NAVIGATION] - Sidebar Drawer -->
            <div class="mobile-sidebar-nav d-xl-none" id="navbarNav">
                <div class="mobile-sidebar-header justify-content-center">
                    <div class="mobile-sidebar-brand">
                        <img src="<?= asset($chungapi['logo']); ?>" width="100" height="100"
                            alt="<?= htmlspecialchars($chungapi['ten_web'] ?? 'logo', ENT_QUOTES, 'UTF-8') ?>"
                            draggable="false" class="ks-img-guard ks-mobile-sidebar-logo" decoding="async"
                            loading="eager" fetchpriority="high">
                    </div>
                </div>

                <ul class="navbar-nav mobile-nav-list">
                    <?php
                    foreach ($publicHeaderItems as $index => $navItem):
                        $navType = (string) ($navItem['type'] ?? 'link');
                        $navLabel = htmlspecialchars((string) ($navItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $navMobileIcon = htmlspecialchars((string) ($navItem['mobile_icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $embedImg = (string) ($navItem['embed_img'] ?? '');
                        $collapseId = "navCollapse_" . $index;
                        ?>
                        <?php
                        $isActive = NavConfig::isPublicLinkActive($navItem, $requestPathNav);
                        if ($navType === 'dropdown'):
                            ?>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle-custom<?= $isActive ? ' active-item' : '' ?>"
                                    data-bs-toggle="collapse" href="#<?= $collapseId ?>" role="button"
                                    aria-expanded="<?= $isActive ? 'true' : 'false' ?>">
                                    <div class="nav-link-content">
                                        <span class="nav-icon">
                                            <?php if ($navMobileIcon !== ''): ?>
                                                <i class="<?= $navMobileIcon ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="nav-text"><?= $navLabel ?></span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down arrow-icon"></i>
                                </a>
                                <div class="collapse sub-menu-collapse<?= $isActive ? ' show' : '' ?>" id="<?= $collapseId ?>">
                                    <ul class="sub-nav-list">
                                        <?php foreach ((array) ($navItem['children'] ?? []) as $child): ?>
                                            <?php
                                            $childLabel = htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                            $childIcon = (string) ($child['icon'] ?? '');
                                            $childImg = (string) ($child['embed_img'] ?? '');
                                            $childHref = (string) ($child['href'] ?? '#');
                                            $isChildActive = NavConfig::isPublicLinkActive($child, $requestPathNav);
                                            $isBinance = strpos(strtolower($childLabel), 'binance') !== false;
                                            ?>
                                            <li class="sub-nav-item">
                                                <a href="<?= htmlspecialchars($childHref, ENT_QUOTES, 'UTF-8') ?>"
                                                    class="sub-nav-link<?= $isChildActive ? ' active' : '' ?>">
                                                    <?php if ($childImg !== '' || $childIcon !== ''): ?>
                                                        <span class="child-icon">
                                                            <?php if ($childImg !== ''): ?>
                                                                <img src="<?= htmlspecialchars($childImg, ENT_QUOTES, 'UTF-8') ?>"
                                                                    alt="icon" style="height: 18px;">
                                                            <?php elseif ($childIcon !== ''): ?>
                                                                <i class="<?= htmlspecialchars($childIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="child-text"><?= $childLabel ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $isActive ? ' active-item' : '' ?>"
                                    href="<?= htmlspecialchars((string) ($navItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="nav-link-content">
                                        <span class="nav-icon">
                                            <?php if ($navMobileIcon !== ''): ?>
                                                <i class="<?= $navMobileIcon ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="nav-text"><?= $navLabel ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!isset($_SESSION['session'])): ?>
                        <li class="nav-divider"></li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('login') ?>">
                                <div class="nav-link-content">
                                    <span class="nav-icon">
                                        <i class="fa-solid fa-right-to-bracket"></i>
                                    </span>
                                    <span class="nav-text" style="font-weight: 600;">Đăng nhập</span>
                                </div>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="mobile-nav-backdrop d-xl-none" data-bs-toggle="collapse" data-bs-target="#navbarNav"></div>

            <?php
            global $chungapi, $username, $user;
            if (isset($_SESSION['session'])) {
                ?>
                <div class="navbar-right d-flex align-items-center gap-2">
                    <div class="gtranslate_wrapper"></div>

                    <div class="align-items-center">
                        <div class="dropdown">
                            <button type="button" class="d-flex align-items-center header-widget" data-bs-toggle="dropdown"
                                aria-expanded="false" style="padding: 4px 8px; justify-content: center;">
                                <?php
                                $userAvatar = trim((string) ($user['avatar_url'] ?? ''));
                                if ($userAvatar === '') {
                                    $userAvatar = asset('assets/images/avt.png');
                                }
                                $userDisplayName = trim((string) ($user['full_name'] ?? ''));
                                if ($userDisplayName === '') {
                                    $userDisplayName = $username;
                                } else {
                                    // Use last name for short display
                                    $userDisplayName = FormatHelper::getLastName($userDisplayName);
                                }
                                ?>
                                <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" decoding="async"
                                    class="rounded-circle w-40" style="margin-right: 8px;" alt="User Avatar" width="40"
                                    height="40">
                                <span style="display:flex; flex-direction:column; align-items:flex-start; line-height:1;">
                                    <span class="text-uppercase"
                                        style="font-weight:bold; color:#333; font-size:13px; line-height:1;"><?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span
                                        style="color:#ff6900; font-weight:800; font-size:13px; line-height:1; margin-top:2px;">
                                        <strong data-header-balance
                                            data-price-vnd="<?= (int) $user['money']; ?>"><?= tien($user['money']); ?></strong>
                                    </span>
                                </span>
                            </button>


                            <?php
                            $publicUserDropdownItems = NavConfig::publicUserDropdownItems(((int) ($user['level'] ?? 0)) === 9);
                            $userSidebarItems = NavConfig::userSidebarItems();
                            $mobileDropdownItems = [];
                            if (((int) ($user['level'] ?? 0)) === 9) {
                                $mobileDropdownItems[] = [
                                    'type' => 'link',
                                    'href' => url('admin'),
                                    'icon' => 'fa-solid fa-gear',
                                    'color' => '#ef4444',
                                    'label' => 'Admin',
                                ];
                            }
                            $mobileDropdownItems = array_merge($mobileDropdownItems, $userSidebarItems);
                            ?>
                            <ul class="dashboard-profile dropdown-menu"
                                style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(0px, 58.4px, 0px);">
                                <!-- Desktop Items -->
                                <?php foreach ($publicUserDropdownItems as $dropdownItem): ?>
                                    <?php
                                    $dropdownType = (string) ($dropdownItem['type'] ?? 'link');
                                    $dropdownIcon = htmlspecialchars((string) ($dropdownItem['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownLabel = htmlspecialchars((string) ($dropdownItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownHref = htmlspecialchars((string) ($dropdownItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <li class="d-none d-md-block">
                                        <?php if ($dropdownType === 'logout'): ?>
                                            <a class="dashboard-profile-item dropdown-item logout-item" href="<?= $dropdownHref ?>"
                                                onclick="event.preventDefault(); SwalHelper.confirmLogout('<?= $dropdownHref ?>')"
                                                aria-label="<?= $dropdownLabel ?>">
                                                <i class="<?= $dropdownIcon ?>"></i><?= $dropdownLabel ?>
                                            </a>
                                        <?php else: ?>
                                            <a class="dashboard-profile-item dropdown-item" href="<?= $dropdownHref ?>">
                                                <i class="<?= $dropdownIcon ?>"></i><?= $dropdownLabel ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>

                                <!-- Mobile Items (replaces sidebar) -->
                                <?php foreach ($mobileDropdownItems as $dropdownItem): ?>
                                    <?php
                                    $dropdownType = (string) ($dropdownItem['type'] ?? 'link');
                                    $dropdownIcon = htmlspecialchars((string) ($dropdownItem['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownLabel = htmlspecialchars((string) ($dropdownItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownHref = htmlspecialchars((string) ($dropdownItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
                                    $dropdownColor = (string) ($dropdownItem['color'] ?? '');
                                    $iconStyle = $dropdownColor !== '' ? ' style="color: ' . $dropdownColor . ' !important;"' : '';
                                    ?>
                                    <li class="d-md-none">
                                        <?php if ($dropdownType === 'logout'): ?>
                                            <a class="dashboard-profile-item dropdown-item logout-item" href="<?= $dropdownHref ?>"
                                                onclick="event.preventDefault(); SwalHelper.confirmLogout('<?= $dropdownHref ?>')"
                                                aria-label="<?= $dropdownLabel ?>">
                                                <i class="<?= $dropdownIcon ?>" <?= $iconStyle ?>></i><?= $dropdownLabel ?>
                                            </a>
                                        <?php else: ?>
                                            <a class="dashboard-profile-item dropdown-item" href="<?= $dropdownHref ?>">
                                                <i class="<?= $dropdownIcon ?>" <?= $iconStyle ?>></i><?= $dropdownLabel ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        </div>
                    </div>
                </div>
                <?php
            } else {
                $guestHeaderActions = NavConfig::publicGuestActions();
                ?>
                <div class="navbar-right d-flex align-items-center gap-2">
                    <div class="gtranslate_wrapper"></div>
                    <div class="align-items-center">
                        <?php foreach ($guestHeaderActions as $guestAction): ?>
                            <a href="<?= htmlspecialchars((string) ($guestAction['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                                class="<?= htmlspecialchars((string) ($guestAction['class'] ?? 'btn btn-primary me-1'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($guestAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
            ?>

            <button class="navbar-toggler d-block d-xl-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Nhấn để mở menu điều hướng">
                <span></span>
            </button>
        </nav>
    </div>
</header>
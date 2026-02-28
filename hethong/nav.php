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
</style>

<div class="loader-wrapper">
    <span class="site-loader"> </span>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var loadingOverlay = document.querySelector('.loader-wrapper');
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    });
</script>

<!-- Menu Start -->
<header class="header-primary<?= $isAuthNavPage ? ' auth-shell-header' : '' ?>">
    <div class="container">
        <nav class="navbar navbar-expand-xl justify-content-between">
            <a href="<?= url('') ?>">
                <?php global $chungapi; ?>
                <img src="<?= $chungapi['logo']; ?>" width="150" alt="dailycode.vn" decoding="async" fetchpriority="high" />
            </a>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="d-block d-xl-none">
                        <div class="logo">
                            <a href="<?= url('') ?>">
                                <img src="<?= $chungapi['logo']; ?>" width="150" alt="dailycode.vn" decoding="async" fetchpriority="high" />
                            </a>
                        </div>
                    </li>

                    <?php
                    $publicHeaderItems = NavConfig::publicHeaderMenu();
                    foreach ($publicHeaderItems as $navItem):
                        $navType = (string) ($navItem['type'] ?? 'link');
                        $navLabel = htmlspecialchars((string) ($navItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $navMobileIcon = htmlspecialchars((string) ($navItem['mobile_icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $embedImg = (string) ($navItem['embed_img'] ?? '');
                        ?>
                        <?php if ($navType === 'dropdown'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" aria-expanded="false">
                                    <?php if ($navMobileIcon !== ''): ?><i
                                            class="<?= $navMobileIcon ?> me-2 d-xl-none"></i><?php endif; ?><?= $navLabel ?>
                                    <?php if ($embedImg !== ''): ?>
                                        <img src="<?= htmlspecialchars($embedImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon" loading="lazy" decoding="async" fetchpriority="low"
                                            style="height: 22px; margin-left: 4px; vertical-align: middle; margin-top: -2px;">
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php foreach ((array) ($navItem['children'] ?? []) as $child): ?>
                                        <?php
                                        $childLabel = htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        $childIcon = (string) ($child['icon'] ?? '');
                                        $childImg = (string) ($child['embed_img'] ?? '');
                                        $isBinance = strpos(strtolower($childLabel), 'binance') !== false;
                                        ?>
                                        <li>
                                            <a href="<?= htmlspecialchars((string) ($child['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                                                class="dropdown-item d-flex align-items-center">
                                                <?php if ($childImg !== '' || $childIcon !== ''): ?>
                                                    <span class="d-flex align-items-center justify-content-center me-2"
                                                        style="width: 30px;">
                                                        <?php if ($childImg !== ''): ?>
                                                            <img src="<?= htmlspecialchars($childImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon" loading="lazy" decoding="async" fetchpriority="low"
                                                                style="height: <?= $isBinance ? '25px' : '18px' ?>; width: auto; max-width: 28px; vertical-align: middle;">
                                                        <?php elseif ($childIcon !== ''): ?>
                                                            <i class="<?= htmlspecialchars($childIcon, ENT_QUOTES, 'UTF-8') ?> <?= $isBinance ? 'is-binance' : '' ?>"
                                                                style="font-size: <?= $isBinance ? '18px' : '14px' ?>;"></i>
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
                                <a class="nav-link"
                                    href="<?= htmlspecialchars((string) ($navItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                                    role="button" aria-expanded="false">
                                    <?php if ($navMobileIcon !== ''): ?><i
                                            class="<?= $navMobileIcon ?> me-2 d-xl-none"></i><?php endif; ?><?= $navLabel ?>
                                    <?php if ($embedImg !== ''): ?>
                                        <img src="<?= htmlspecialchars($embedImg, ENT_QUOTES, 'UTF-8') ?>" alt="icon" loading="lazy" decoding="async" fetchpriority="low"
                                            style="height: 22px; margin-left: 4px; vertical-align: middle; margin-top: -4px;">
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                            <button type="button" class="d-flex align-items-center header-widget" data-bs-toggle="dropdown"
                                aria-expanded="false" style="padding: 4px 8px; justify-content: center;">
                                <?php
                                $userAvatar = trim((string) (($user['avatar_url'] ?? '') ?: ($user['avatar'] ?? '')));
                                if ($userAvatar === '') {
                                    $userAvatar = asset('assets/images/avt.png');
                                }
                                ?>
                                <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>"
                                    decoding="async"
                                    class="rounded-circle w-40" style="margin-right: 8px;" alt="">
                                <span class="text-center">
                                    <p class="text-uppercase"
                                        style="font-weight: bold; color: #333; line-height: 1; border-radius: 6px; display: inline-block; font-size: 13px;">
                                        <?= $username; ?>
                                    </p>
                                    <p style=" color: #ff6900; font-weight: 800; font-size: 13px; line-height: 1;
                                    margin-top: 2px;"><strong><?= tien($user['money']); ?>đ</strong></p>
                                </span>
                            </button>

                            <?php $publicUserDropdownItems = NavConfig::publicUserDropdownItems(((int) ($user['level'] ?? 0)) === 9); ?>
                            <ul class="dashboard-profile dropdown-menu"
                                style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(0px, 58.4px, 0px);">
                                <?php foreach ($publicUserDropdownItems as $dropdownItem): ?>
                                    <?php
                                    $dropdownType = (string) ($dropdownItem['type'] ?? 'link');
                                    $dropdownIcon = htmlspecialchars((string) ($dropdownItem['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownLabel = htmlspecialchars((string) ($dropdownItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $dropdownHref = htmlspecialchars((string) ($dropdownItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <li>
                                        <?php if ($dropdownType === 'logout'): ?>
                                            <a class="dashboard-profile-item dropdown-item logout-item" href="javascript:void(0)"
                                                onclick="SwalHelper.confirmLogout('<?= $dropdownHref ?>')">
                                                <i class="<?= $dropdownIcon ?>"></i><?= $dropdownLabel ?>
                                            </a>
                                        <?php else: ?>
                                            <a class="dashboard-profile-item dropdown-item" href="<?= $dropdownHref ?>">
                                                <i class="<?= $dropdownIcon ?>"></i><?= $dropdownLabel ?>
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
                if (!$isAuthNavPage) {
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
            }
            ?>

            <button class="navbar-toggler d-block d-xl-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span></span>
            </button>
        </nav>
    </div>
</header>

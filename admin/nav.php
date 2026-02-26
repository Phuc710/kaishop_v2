<?php
/**
 * KAISHOP Admin Navigation - Single Source UI
 * Menu items are now loaded from NavConfig (APP_DIR-safe).
 */
require_once dirname(__DIR__) . '/hethong/AdminMenuRenderer.php';
if (!class_exists('NavConfig')) {
    require_once dirname(__DIR__) . '/app/Helpers/NavConfig.php';
}

$_adminUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_adminUri = rtrim((string) $_adminUri, '/') ?: '/';
$isAdminSettings = (bool) preg_match('#/admin/setting$#', $_adminUri);
$sidebarLogoUrl = trim((string) ($chungapi['logo'] ?? ''));
$sidebarLogoAlt = trim((string) ($chungapi['ten_web'] ?? 'Logo'));
$adminSidebarItems = NavConfig::adminSidebarItems();
$adminQuickActions = NavConfig::adminHeaderQuickActions();
?>

<nav class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <?php if ($sidebarLogoUrl !== ''): ?>
            <img src="<?= htmlspecialchars($sidebarLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($sidebarLogoAlt, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <span><?= htmlspecialchars($sidebarLogoAlt, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <ul>
        <?php foreach ($adminSidebarItems as $navItem): ?>
            <?php
            $navType = (string) ($navItem['type'] ?? 'link');

            if ($navType === 'header') {
                echo '<li class="sidebar-header">' . htmlspecialchars((string) ($navItem['label'] ?? ''), ENT_QUOTES, 'UTF-8') . '</li>';
                continue;
            }

            if ($navType === 'tree') {
                AdminMenuRenderer::renderTreeGroup($navItem);
                continue;
            }

            if ($navType === 'logout') {
                $logoutHref = htmlspecialchars((string) ($navItem['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $logoutIcon = htmlspecialchars((string) ($navItem['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                $logoutLabel = htmlspecialchars((string) ($navItem['label'] ?? ''), ENT_QUOTES, 'UTF-8');

                echo '<li>';
                echo '<a href="javascript:void(0)" onclick="SwalHelper.confirmLogout(\'' . $logoutHref . '\')">';
                if ($logoutIcon !== '') {
                    echo '<i class="' . $logoutIcon . '"></i> ';
                }
                echo $logoutLabel;
                echo '</a>';
                echo '</li>';
                continue;
            }

            AdminMenuRenderer::renderFlatItems([$navItem]);
            ?>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- TOP HEADER -->
<header class="app-header">
    <div class="main-header-container">
        <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle"
            data-bs-toggle="sidebar" href="javascript:void(0);" id="sidebarToggle">
            <span></span>
        </a>
        <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
            <?php foreach ($adminQuickActions as $quickAction): ?>
                <?php
                $quickKey = (string) ($quickAction['key'] ?? '');
                $quickHref = htmlspecialchars((string) ($quickAction['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $quickTitle = htmlspecialchars((string) ($quickAction['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $quickAria = htmlspecialchars((string) ($quickAction['aria'] ?? $quickTitle), ENT_QUOTES, 'UTF-8');
                $quickColor = htmlspecialchars((string) ($quickAction['color'] ?? '#111827'), ENT_QUOTES, 'UTF-8');
                $quickIcon = (string) ($quickAction['icon'] ?? '');
                if ($quickKey === 'settings' && $isAdminSettings) {
                    $quickIcon .= ' bx-spin';
                }
                $quickIcon = htmlspecialchars(trim($quickIcon), ENT_QUOTES, 'UTF-8');
                ?>
                <a href="<?= $quickHref ?>"<?= $quickKey === 'home' ? ' target="_blank"' : '' ?> title="<?= $quickTitle ?>" aria-label="<?= $quickAria ?>"
                    style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; border:1px solid #e7e8f0; background:#fff; color:<?= $quickColor ?>; text-decoration:none; box-shadow:0 2px 8px rgba(17,28,67,.06);">
                    <i class="<?= $quickIcon ?>"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Smooth Dropdown toggle
        document.querySelectorAll('.admin-sidebar > ul > li > button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var li = this.parentElement;
                var sub = this.nextElementSibling;
                if (!sub) return;

                var isExpanding = !li.classList.contains('active-group');

                if (isExpanding) {
                    li.classList.add('active-group');
                    sub.style.display = 'block';
                    var height = sub.scrollHeight + 'px';
                    sub.style.maxHeight = '0px';

                    // Force layout reflow
                    sub.offsetHeight;

                    sub.style.maxHeight = height;
                } else {
                    li.classList.remove('active-group');
                    var height = sub.scrollHeight + 'px';
                    sub.style.maxHeight = height; // Set explicitly before collapse

                    // Force layout reflow
                    sub.offsetHeight;

                    sub.style.maxHeight = '0px';

                    // Wait for transition to finish before hiding
                    setTimeout(() => {
                        if (!li.classList.contains('active-group')) {
                            sub.style.display = 'none';
                        }
                    }, 300); // matches CSS transition duration
                }
            });
        });

        // Sidebar toggle (desktop & mobile)
        var toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                document.body.classList.toggle('sidebar-collapsed');
                toggleBtn.classList.toggle('is-active');
            });
        }
    });
</script>

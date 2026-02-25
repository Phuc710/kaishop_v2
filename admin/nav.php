<?php
/**
 * KAISHOP Admin Navigation — Single Source
 * All sidebar code lives here. Other files just include this one.
 * Styling lives in assets/css/admin.css
 */
require_once dirname(__DIR__) . '/hethong/AdminMenuRenderer.php';

$_adminUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_adminUri = rtrim((string) $_adminUri, '/') ?: '/';
$isAdminDashboard = (bool) preg_match('#/admin/?$|/admin/index\.php$#', $_adminUri);
$isAdminSettings = (bool) preg_match('#/admin/setting$#', $_adminUri);
$sidebarLogoUrl = trim((string) ($chungapi['logo'] ?? ''));
$sidebarLogoAlt = trim((string) ($chungapi['ten_web'] ?? 'Logo'));
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
        <li class="sidebar-header">MAIN</li>
        <li>
            <a href="<?= url('admin') ?>" <?php
              if ($isAdminDashboard)
                  echo ' class="active"';
              ?>>
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <?php
        AdminMenuRenderer::renderTreeGroup(
            AdminMenuRenderer::journalGroup(url('admin/logs/buying'), url('admin/logs/balance-changes'), url('admin/logs/deposits'))
        );
        ?>

        <li class="sidebar-header">DỊCH VỤ</li>

        <?php
        AdminMenuRenderer::renderTreeGroup(
            AdminMenuRenderer::productGroup(url('admin/categories'), url('admin/products'))
        );
        ?>

        <li class="sidebar-header">QUẢN LÝ</li>

        <li>
            <a href="<?= url('admin/users') ?>" <?php
              if (strpos($_adminUri, 'user') !== false)
                  echo ' class="active"';
              ?>>
                <i class="fas fa-users"></i> Thành viên
            </a>
        </li>

        <?php
        AdminMenuRenderer::renderFlatItems([
            ['href' => url('admin/finance/giftcodes'), 'icon' => 'fas fa-tags', 'label' => 'Mã giảm giá'],
        ]);
        ?>

        <li class="sidebar-header">HỆ THỐNG</li>
        <li>
            <a href="<?= url('admin/setting') ?>" <?= $isAdminSettings ? ' class="active"' : '' ?>>
                <i class="bx bx-cog setting-spin"></i> Cài đặt
            </a>
        </li>
        <li>
            <a href="javascript:void(0)" onclick="SwalHelper.confirmLogout('<?= url('logout') ?>')">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </li>
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
            <a href="<?= url('/') ?>" target="_blank" title="Xem trang chủ" aria-label="Xem trang chủ"
                style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; border:1px solid #e7e8f0; background:#fff; color:#059669; text-decoration:none; box-shadow:0 2px 8px rgba(17,28,67,.06);">
                <i class="bx bx-home"></i>
            </a>
            <a href="<?= url('admin/setting') ?>" title="Cài đặt" aria-label="Cài đặt"
                style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; border:1px solid #e7e8f0; background:#fff; color:#6d28d9; text-decoration:none; box-shadow:0 2px 8px rgba(17,28,67,.06);">
                <i class="bx bx-cog<?= $isAdminSettings ? ' bx-spin' : '' ?>"></i>
            </a>
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
<?php
/**
 * User Sidebar Component (Shared)
 * Dùng chung cho toàn bộ trang user.
 * Required variable: $activePage
 */
if (!class_exists('NavConfig')) {
    require_once dirname(__DIR__) . '/app/Helpers/NavConfig.php';
}
$activePage = $activePage ?? '';
$userSidebarItems = NavConfig::userSidebarItems();
?>
<div class="user-sidebar">
    <?php foreach ($userSidebarItems as $item): ?>
        <?php
        $type = (string) ($item['type'] ?? 'link');
        $icon = htmlspecialchars((string) ($item['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $isActive = ($type === 'link') && ($activePage === (string) ($item['active_key'] ?? ''));
        $activeClass = $isActive ? ' active' : '';
        ?>
        <?php if ($type === 'logout'): ?>
            <a href="javascript:void(0)" onclick="SwalHelper.confirmLogout('<?= htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>')"
                class="sidebar-item" style="cursor:pointer;">
                <i class="<?= $icon ?>"></i> <?= $label ?>
            </a>
        <?php else: ?>
            <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-item<?= $activeClass ?>">
                <i class="<?= $icon ?>"></i> <?= $label ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

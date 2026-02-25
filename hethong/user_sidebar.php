<?php
/**
 * User Sidebar Component (Shared)
 * DÃ¹ng chung cho toÃ n bá»™ trang user.
 * Required variable: $activePage
 */
$activePage = $activePage ?? '';
?>
<div class="user-sidebar">
    <a href="<?= url('profile') ?>" class="sidebar-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-user"></i> ThÃ´ng tin cÃ¡ nhÃ¢n
    </a>
    <a href="<?= url('history-code') ?>" class="sidebar-item <?= $activePage === 'history' ? 'active' : '' ?>">
        <i class="fas fa-wallet"></i> Biến động số dư
    </a>
    <a href="<?= url('deposit-bank') ?>" class="sidebar-item <?= $activePage === 'deposit' ? 'active' : '' ?>">
        <i class="fas fa-university"></i> Náº¡p tiá»n
    </a>
    <a href="<?= url('password') ?>" class="sidebar-item <?= $activePage === 'password' ? 'active' : '' ?>">
        <i class="fas fa-key"></i> Thay Ä‘á»•i máº­t kháº©u
    </a>
    <a href="javascript:void(0)" onclick="SwalHelper.confirmLogout('<?= url('logout') ?>')" class="sidebar-item" style="cursor:pointer;">
        <i class="fas fa-sign-out-alt"></i> ÄÄƒng xuáº¥t
    </a>
</div>

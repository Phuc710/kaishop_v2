<?php
/**
 * User Sidebar Component (Shared)
 * Dùng chung cho toàn bộ trang user.
 * Required variable: $activePage
 */
$activePage = $activePage ?? '';
?>
<div class="user-sidebar">
    <a href="<?= url('profile') ?>" class="sidebar-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-user"></i> Thông tin cá nhân
    </a>
    <a href="<?= url('history-balance') ?>" class="sidebar-item <?= $activePage === 'history' ? 'active' : '' ?>">
        <i class="fas fa-wallet"></i> Biến động số dư
    </a>
    <a href="<?= url('history-orders') ?>" class="sidebar-item <?= $activePage === 'order-history' ? 'active' : '' ?>">
        <i class="fas fa-receipt"></i> Lịch sử đơn hàng
    </a>
    
    <a href="<?= url('deposit-bank') ?>" class="sidebar-item <?= $activePage === 'deposit' ? 'active' : '' ?>">
        <i class="fas fa-university"></i> Nạp tiền
    </a>
    <a href="<?= url('password') ?>" class="sidebar-item <?= $activePage === 'password' ? 'active' : '' ?>">
        <i class="fas fa-key"></i> Thay đổi mật khẩu
    </a>
    <a href="javascript:void(0)" onclick="SwalHelper.confirmLogout('<?= url('logout') ?>')" class="sidebar-item"
        style="cursor:pointer;">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
    </a>
</div>

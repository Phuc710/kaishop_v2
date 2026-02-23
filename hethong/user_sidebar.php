<?php
/**
 * User Sidebar Component (Shared)
 * Include file này vào TẤT CẢ trang user: profile, password, history...
 * Thêm menu 1 chỗ → có tất cả.
 *
 * Required variable: $activePage (string) — tên page đang active
 * Ví dụ: $activePage = 'profile'; hoặc $activePage = 'password';
 */
$activePage = $activePage ?? '';
?>
<div class="user-sidebar">
    <a href="<?= url('profile') ?>" class="sidebar-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="fas fa-user"></i> Thông tin cá nhân
    </a>
    <a href="<?= url('history-code') ?>" class="sidebar-item <?= $activePage === 'history' ? 'active' : '' ?>">
        <i class="fas fa-wallet"></i> Biến động số dư
    </a>
    <a href="<?= url('password') ?>" class="sidebar-item <?= $activePage === 'password' ? 'active' : '' ?>">
        <i class="fas fa-key"></i> Thay đổi mật khẩu
    </a>
    <a href="javascript:void(0)" onclick="SwalHelper.confirmLogout('<?= url('logout') ?>')" class="sidebar-item"
        style="cursor:pointer;">
        <i class="fas fa-sign-out-alt"></i> Đăng Xuất
    </a>
</div>
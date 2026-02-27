<?php

/**
 * Centralized navigation configuration (admin + public + user sidebar).
 * Uses url() when available and falls back to APP_DIR-safe path building.
 */
class NavConfig
{
    private static function buildUrl(string $path = ''): string
    {
        if (function_exists('url')) {
            return (string) url($path);
        }

        $base = defined('APP_DIR') ? rtrim((string) APP_DIR, '/') : '';
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return $base !== '' ? $base : '/';
        }

        return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    }

    public static function authNavPaths(): array
    {
        return ['/login', '/login-otp', '/register', '/password-reset'];
    }

    public static function adminSidebarItems(): array
    {
        return [
            ['type' => 'header', 'label' => 'MAIN'],
            [
                'type' => 'link',
                'href' => self::buildUrl('admin'),
                'icon' => 'fas fa-tachometer-alt',
                'label' => 'Dashboard',
                'active_patterns' => ['#^/admin(?:/index\.php)?/?$#'],
            ],
            [
                'type' => 'tree',
                'icon' => 'bx bx-history side-menu__icon',
                'label' => 'Nhật ký',
                'children' => [
                    [
                        'href' => self::buildUrl('admin/logs/buying'),
                        'label' => 'Lịch sử mua hàng',
                        'active_prefixes' => ['admin/logs/buying', 'admin/logs/buying/detail'],
                    ],
                    [
                        'href' => self::buildUrl('admin/logs/deposits'),
                        'label' => 'Lịch sử nạp tiền',
                        'active_prefixes' => ['admin/logs/deposits'],
                    ],
                    [
                        'href' => self::buildUrl('admin/logs/balance-changes'),
                        'label' => 'Biến động số dư',
                        'active_prefixes' => ['admin/logs/balance-changes'],
                    ],
                    [
                        'href' => self::buildUrl('admin/logs/system'),
                        'label' => 'Nhật ký hệ thống',
                        'active_prefixes' => ['admin/logs/system'],
                    ],
                ],
            ],
            ['type' => 'header', 'label' => 'DỊCH VỤ'],
            [
                'type' => 'tree',
                'icon' => 'bx bx-cart side-menu__icon',
                'label' => 'Sản phẩm',
                'children' => [
                    [
                        'href' => self::buildUrl('admin/categories'),
                        'label' => 'Chuyên mục',
                        'active_prefixes' => ['admin/categories'],
                    ],
                    [
                        'href' => self::buildUrl('admin/products'),
                        'label' => 'Tất cả sản phẩm',
                        'active_prefixes' => ['admin/products'],
                    ],
                ],
            ],
            ['type' => 'header', 'label' => 'QUẢN LÝ'],
            [
                'type' => 'link',
                'href' => self::buildUrl('admin/users'),
                'icon' => 'fas fa-users',
                'label' => 'Thành viên',
                'active_prefixes' => ['admin/users'],
            ],
            [
                'type' => 'link',
                'href' => self::buildUrl('admin/finance/giftcodes'),
                'icon' => 'fas fa-tags',
                'label' => 'Mã giảm giá',
                'active_prefixes' => ['admin/finance/giftcodes'],
            ],
            ['type' => 'header', 'label' => 'HỆ THỐNG'],
            [
                'type' => 'link',
                'href' => self::buildUrl('admin/setting'),
                'icon' => 'bx bx-cog setting-spin',
                'label' => 'Cài đặt',
                'active_prefixes' => ['admin/setting'],
            ],
            [
                'type' => 'logout',
                'href' => self::buildUrl('logout'),
                'icon' => 'fas fa-sign-out-alt',
                'label' => 'Đăng xuất',
            ],
        ];
    }

    public static function adminHeaderQuickActions(): array
    {
        return [
            [
                'key' => 'home',
                'href' => self::buildUrl('/'),
                'title' => 'Xem trang chủ',
                'aria' => 'Xem trang chủ',
                'icon' => 'bx bx-home',
                'color' => '#059669',
            ],
            [
                'key' => 'settings',
                'href' => self::buildUrl('admin/setting'),
                'title' => 'Cài đặt',
                'aria' => 'Cài đặt',
                'icon' => 'bx bx-cog',
                'color' => '#6d28d9',
            ],
        ];
    }

    public static function publicHeaderMenu(): array
    {
        return [
            [
                'type' => 'link',
                'href' => self::buildUrl(''),
                'label' => 'Trang chủ',
                'mobile_icon' => 'fa-solid fa-house-chimney',
                'embed_img' => 'https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif',
            ],
            [
                'type' => 'dropdown',
                'label' => 'Nạp tiền',
                'mobile_icon' => 'fa-solid fa-wallet',
                'children' => [
                    [
                        'href' => self::buildUrl('balance/bank'),
                        'label' => 'Bank',
                        'embed_img' => self::buildUrl('assets/images/bank.png'),
                    ],
                    [
                        'href' => self::buildUrl('balance/binance'),
                        'label' => 'Binance',
                        'embed_img' => self::buildUrl('assets/images/Binance_icon.svg'),
                    ],
                    [
                        'href' => self::buildUrl('balance/momo'),
                        'label' => 'Momo',
                        'embed_img' => self::buildUrl('assets/images/momo.webp'),
                    ],
                ],
            ],
            [
                'type' => 'dropdown',
                'label' => 'Lịch sử',
                'mobile_icon' => 'fa-solid fa-clock-rotate-left',
                'children' => [
                    [
                        'href' => self::buildUrl('history-balance'),
                        'label' => 'Lịch sử nạp tiền',
                    ],
                    [
                        'href' => self::buildUrl('history-orders'),
                        'label' => 'Lịch sử mua hàng',
                    ],
                ],
            ],
            [
                'type' => 'link',
                'href' => self::buildUrl('lien-he'),
                'label' => 'Liên hệ',
                'mobile_icon' => 'fa-solid fa-headset',
            ],
        ];
    }

    public static function publicUserDropdownItems(bool $isAdmin): array
    {
        $items = [];

        if ($isAdmin) {
            $items[] = [
                'type' => 'link',
                'href' => self::buildUrl('admin'),
                'icon' => 'fa-solid fa-gear',
                'label' => 'Admin',
            ];
        }

        $items[] = [
            'type' => 'link',
            'href' => self::buildUrl('profile'),
            'icon' => 'fa fa-user me-1 fs-10',
            'label' => 'Tài khoản',
        ];

        $items[] = [
            'type' => 'logout',
            'href' => self::buildUrl('logout'),
            'icon' => 'fa-solid fa-right-from-bracket me-1 fs-10',
            'label' => 'Đăng xuất',
        ];

        return $items;
    }

    public static function publicGuestActions(): array
    {
        return [
            [
                'type' => 'link',
                'href' => self::buildUrl('login'),
                'label' => 'Đăng nhập',
                'class' => 'btn btn-primary me-1',
            ],
        ];
    }

    public static function userSidebarItems(): array
    {
        return [
            [
                'type' => 'link',
                'active_key' => 'profile',
                'href' => self::buildUrl('profile'),
                'icon' => 'fas fa-user',
                'label' => 'Thông tin cá nhân',
            ],
            [
                'type' => 'link',
                'active_key' => 'history',
                'href' => self::buildUrl('history-balance'),
                'icon' => 'fas fa-wallet',
                'label' => 'Lịch sử nạp tiền',
            ],
            [
                'type' => 'link',
                'active_key' => 'order-history',
                'href' => self::buildUrl('history-orders'),
                'icon' => 'fas fa-receipt',
                'label' => 'Lịch sử đơn hàng',
            ],
            [
                'type' => 'link',
                'active_key' => 'balance',
                'href' => self::buildUrl('balance/bank'),
                'icon' => 'fas fa-university',
                'label' => 'Nạp tiền',
            ],
            [
                'type' => 'link',
                'active_key' => 'password',
                'href' => self::buildUrl('password'),
                'icon' => 'fas fa-key',
                'label' => 'Thay đổi mật khẩu',
            ],
            [
                'type' => 'logout',
                'href' => self::buildUrl('logout'),
                'icon' => 'fas fa-sign-out-alt',
                'label' => 'Đăng xuất',
            ],
        ];
    }
}

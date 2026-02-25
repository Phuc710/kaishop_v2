<?php
/**
 * Renderer menu admin gọn, tái sử dụng cho nhiều layout.
 */
class AdminMenuRenderer
{
    /**
     * Normalize path for active menu checks.
     */
    private static function normalizePath(string $path): string
    {
        $parsed = parse_url($path, PHP_URL_PATH);
        $clean = $parsed === null ? $path : $parsed;
        $clean = '/' . ltrim((string) $clean, '/');

        if (defined('APP_DIR') && APP_DIR !== '' && strpos($clean, APP_DIR) === 0) {
            $clean = substr($clean, strlen(APP_DIR));
            $clean = '/' . ltrim($clean, '/');
        }

        return rtrim($clean, '/') ?: '/';
    }

    /**
     * Check current request path with link path.
     */
    private static function isCurrent(string $href): bool
    {
        $current = self::normalizePath($_SERVER['REQUEST_URI'] ?? '/');
        $target = self::normalizePath($href);
        return $current === $target;
    }

    /**
     * Cau hinh menu San pham dung chung.
     *
     * @param string $categoryHref Link trang chuyen muc
     * @param string $productHref Link trang tat ca san pham
     * @return array{
     *   icon:string,
     *   label:string,
     *   children:array<int, array{href:string, label:string}>
     * }
     */
    public static function productGroup(string $categoryHref, string $productHref): array
    {
        return [
            'icon' => 'bx bx-cart side-menu__icon',
            'label' => 'Sản phẩm',
            'children' => [
                [
                    'href' => $categoryHref,
                    'label' => 'Chuyên mục',
                ],
                [
                    'href' => $productHref,
                    'label' => 'Tất cả sản phẩm',
                ],
            ],
        ];
    }



    /**
     * Cau hinh menu Nhat ky dung chung.
     */
    public static function journalGroup(string $activityHref, string $balanceHref, string $depositHref): array
    {
        return [
            'icon' => 'bx bx-history side-menu__icon',
            'label' => 'Nhật ký',
            'children' => [
                [
                    'href' => $activityHref,
                    'label' => 'Lịch sử mua hàng',
                ],
                [
                    'href' => $depositHref,
                    'label' => 'Lịch sử nạp tiền',
                ],
                [
                    'href' => $balanceHref,
                    'label' => 'Biến động số dư',
                ],
                [
                    'href' => url('admin/logs/system'),
                    'label' => 'Nhật ký hệ thống',
                ],
            ],
        ];
    }

    /**
     * Render danh sach item menu dang link don.
     *
     * @param array<int, array{href:string, icon:string, label:string}> $items
     */
    public static function renderFlatItems(array $items): void
    {
        foreach ($items as $item) {
            $href = htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $icon = htmlspecialchars((string) ($item['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');

            $activeClass = self::isCurrent($href) ? ' class="active"' : '';

            echo '<li>';
            echo '<a href="' . $href . '"' . $activeClass . '>';
            if ($icon) {
                echo '<i class="' . $icon . '"></i> ';
            }
            echo $label;
            echo '</a>';
            echo '</li>';
        }
    }

    /**
     * Render menu cha (treeview) va cac menu con.
     *
     * @param array{
     *   icon:string,
     *   label:string,
     *   children:array<int, array{href:string, label:string}>
     * } $group
     */
    public static function renderTreeGroup(array $group): void
    {
        $icon = htmlspecialchars((string) ($group['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) ($group['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $children = $group['children'] ?? [];
        $isGroupActive = false;

        foreach ($children as $child) {
            if (self::isCurrent((string) ($child['href'] ?? '#'))) {
                $isGroupActive = true;
                break;
            }
        }

        $activeAttr = $isGroupActive ? ' class="active-group"' : '';

        echo '<li' . $activeAttr . '>';
        echo '<button type="button">';
        if ($icon) {
            echo '<i class="' . $icon . '"></i> ';
        }
        echo $label . '</button>';
        echo '<ul>';

        foreach ($children as $child) {
            $href = htmlspecialchars((string) ($child['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $childLabel = htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $childClass = self::isCurrent((string) ($child['href'] ?? '#')) ? ' class="active"' : '';

            echo '<li>';
            echo '<a href="' . $href . '"' . $childClass . '>' . $childLabel . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</li>';
    }

    /**
     * Render block He thong dung chung.
     *
     * @param string $settingHref Link cai dat
     * @param string $logoutHref Link dang xuat
     */
    public static function renderSystemGroup(string $settingHref, string $logoutHref): void
    {
        $safeSettingHref = htmlspecialchars($settingHref, ENT_QUOTES, 'UTF-8');
        $safeLogoutHref = htmlspecialchars($logoutHref, ENT_QUOTES, 'UTF-8');

        echo '<li class="sidebar-header">HỆ THỐNG</li>';
        echo '<li>';
        echo '<a href="' . $safeSettingHref . '">';
        echo '<i class="bx bx-cog setting-spin"></i> Cài đặt';
        echo '</a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="javascript:void(0)" onclick="SwalHelper.confirmLogout(\'' . $safeLogoutHref . '\')">';
        echo '<i class="fas fa-sign-out-alt"></i> Đăng xuất';
        echo '</a>';
        echo '</li>';
    }
}

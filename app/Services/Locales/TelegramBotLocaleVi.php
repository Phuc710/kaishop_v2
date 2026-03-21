<?php

class TelegramBotLocaleVi implements TelegramBotLocaleInterface
{
    private array $map = [
        'invalid_command' => '❌ Lệnh không hợp lệ. Gửi /start để mở bot.',
        'back_home' => '◀️ Quay lại',
        'menu_shop' => '🛍️ Cửa hàng',
        'menu_orders' => '📦 Đơn hàng',
        'menu_help' => '❓ Trợ giúp',
        'menu_language' => '🌐 Ngôn ngữ',
        'menu_admin' => '📊 Thống kê Admin',
        'main_prompt' => '⚡ <b>Vui lòng chọn chức năng bên dưới:</b>',
        'main_prompt_short' => '⚡ Chọn chức năng bên dưới để bắt đầu',
        'greeting' => "👋 Xin chào <b>{name}</b>!\nChào mừng bạn đến với <b>{site}</b> 🌟\n\n🔗 Website: <a href=\"https://{domain}\"><b>{domain}</b></a>\n\n━━━━━━━━━━━━━━\n⚡ <b>Chọn chức năng bên dưới để bắt đầu:</b>",
        'language_picker' => "🌟 <b>Xin chào {name}</b>\n\nChào mừng bạn đến với <b>{site}</b> 🌟\n🌐 Vui lòng chọn ngôn ngữ để bắt đầu trải nghiệm:\n\n🔗 Official: <b>kaishop.id.vn</b>",
        'language_updated_vi' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
        'language_updated_en' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
        'help_title' => '✨ <b>TRỢ GIÚP — DANH SÁCH LỆNH</b>',
        'help_shop' => '🛍️ /shop    — Cửa hàng',
        'help_orders' => '📦 /orders  — Lịch sử đơn hàng',
        'help_menu' => '📋 /menu    — Mở menu nhanh',
        'help_help' => '❓ /help    — Trợ giúp',
        'help_admin' => "\n🔐 <b>LỆNH ADMIN:</b>\n📊 /stats                   — Thống kê\n📢 /broadcast &lt;nội_dung&gt; — Gửi thông báo\n🚧 /maintenance on|off       — Bảo trì\n🏦 /setbank &lt;bank|stk|chủ&gt; — Đổi ngân hàng",

    ];

    public function getLocaleCode(): string
    {
        return 'vi';
    }

    public function getMessage(string $key, array $vars = []): string
    {
        $text = $this->map[$key] ?? $key;
        if (empty($vars)) {
            return $text;
        }

        $replace = [];
        foreach ($vars as $name => $value) {
            $replace['{' . $name . '}'] = (string) $value;
        }

        return strtr($text, $replace);
    }
}

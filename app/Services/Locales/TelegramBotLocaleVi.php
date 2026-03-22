<?php

class TelegramBotLocaleVi implements TelegramBotLocaleInterface
{
    private array $map = [
        'invalid_command' => '❌ Lệnh không hợp lệ. Gửi /start để mở bot.',
        'back_home' => '⬅️ Quay lại',
        'menu_shop' => '🛍️ Cửa hàng',
        'menu_orders' => '📦 Đơn hàng',
        'menu_help' => '❓ Trợ giúp',
        'menu_language' => '🌐 Ngôn ngữ',
        'menu_admin' => '📊 Thống kê Admin',
        'main_prompt' => '👇 <b>Vui lòng chọn chức năng bên dưới:</b>',
        'main_prompt_short' => '👇 Chọn chức năng bên dưới để bắt đầu',
        'button_refresh' => '🔄 Cập nhật',
        'greeting' => "👋 Chào mừng <b>{name}</b> đến với <b>KaiBot</b>! 🤖\n\n━━━━━━━━━━━━━━━━\n✅ Mua hàng tự động 24/7\n⚠️ Lưu ý: Sau khi mua hàng nhớ backup!\n\n👇 Chọn chức năng bên dưới để bắt đầu:",
        'language_picker' => "🌐 Official Website: {domain}\n🔐 OTP Service: {otp_service}\n📢 Channel: {channel}\n👤 Admin: {admin}\n\n──────────────────────\n👇 Vui lòng chọn ngôn ngữ bên dưới để bắt đầu:",
        'language_updated_vi' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
        'language_updated_en' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
        'help_title' => '✨ <b>DANH SÁCH LỆNH</b>',
        'help_shop' => '🛍️ /shop    — Cửa hàng',
        'help_orders' => '📦 /orders  — Lịch sử đơn hàng',
        'help_menu' => '📋 /menu    — Mở menu nhanh',
        'help_help' => '❓ /help    — Trợ giúp',
        'help_admin' => "\n🔐 LỆNH ADMIN:\n📊 /stats                   — Thống kê\n📢 /broadcast &lt;nội_dung&gt; — Gửi thông báo\n🚧 /maintenance on|off       — Bảo trì\n🏦 /setbank &lt;bank|stk|chủ&gt; — Đổi ngân hàng",
        'buy_now' => '🛒 Mua ngay',
        'gift_input_title' => '🎟️ <b>NHẬP GIFTCODE</b>',
        'gift_input_prompt' => '👇 Vui lòng nhập Giftcode:',
        'gift_invalid_title' => '❌ <b>GIFTCODE KHÔNG HỢP LỆ</b>',
        'gift_invalid_msg' => 'Vui lòng kiểm tra lại mã hoặc bấm Quay lại.',
        'gift_button' => '🎟️ Nhập Giftcode',
        'confirm_order_title' => '🧾 <b>Xác nhận đơn hàng</b>',
        'confirm_unit_price' => '💵 Đơn giá',
        'confirm_total' => '💎 Tổng thanh toán',
        'confirm_discount' => '🏷️ Giftcode',
        'confirm_info' => '📝 Thông tin',
        'confirm_button' => '✅ Xác nhận',
        'confirm_free' => '🎁 Nhận miễn phí',
        'success_title' => '🎉 THANH TOÁN THÀNH CÔNG 🎉',
        'product_sent_caption' => '🎁 Sản phẩm được gửi ở bên dưới.',
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

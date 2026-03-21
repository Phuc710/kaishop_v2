<?php

class TelegramBotLocaleEn implements TelegramBotLocaleInterface
{
    private array $map = [
        'invalid_command' => '❌ Invalid command. Send /start to open the bot.',
        'back_home' => '⬅️ Back',
        'menu_shop' => '🛍️ Shop',
        'menu_orders' => '📦 Orders',
        'menu_help' => '❓ Help',
        'menu_language' => '🌐 Language',
        'menu_admin' => '📊 Admin Stats',
        'main_prompt' => '👇 <b>Please choose an option below to continue:</b>',
        'main_prompt_short' => '👇 Choose an option below to continue',
        'button_refresh' => '🔄 Refresh',
        'greeting' => "🌐 Official Website: <b>kaishop.id.vn</b>\n🔐 OTP Service: <b>tmail.kaishop.id.vn</b>\n📢 Channel: <b>{channel}</b>\n👤 Admin: <b>{admin}</b>\n\n──────────────────────\n👇 Choose an option below to start:",
        'language_picker' => "🌐 Official Website: <b>kaishop.id.vn</b>\n🔐 OTP Service: <b>tmail.kaishop.id.vn</b>\n📢 Channel: <b>{channel}</b>\n👤 Admin: <b>{admin}</b>\n\n──────────────────────\n👇 Choose your language to start:",
        'language_updated_vi' => '🇺🇸 Switched to English.',
        'language_updated_en' => '🇺🇸 Switched to English.',
        'help_title' => '✨ <b>COMMAND LIST</b>',
        'help_shop' => '🛍️ /shop    — Open shop',
        'help_orders' => '📦 /orders  — Recent orders',
        'help_menu' => '📋 /menu    — Open quick menu',
        'help_help' => '❓ /help    — Help',
        'help_admin' => "\n🔐 ADMIN COMMANDS:\n📊 /stats                   — Stats\n📢 /broadcast &lt;content&gt;   — Broadcast\n🚧 /maintenance on|off       — Maintenance\n🏦 /setbank &lt;bank|acc|name&gt; — Update bank info",
        'buy_now' => '🛒 Buy now',
        'gift_input_title' => '🎟️ <b>ENTER DISCOUNT CODE</b>',
        'gift_input_prompt' => '👇 Please enter your discount code:',
        'gift_invalid_title' => '❌ <b>INVALID DISCOUNT CODE</b>',
        'gift_invalid_msg' => 'Please enter another code or click Back.',
        'gift_button' => '🎟 Enter discount code',
        'confirm_order_title' => '🧾 <b>Confirm order</b>',
        'confirm_unit_price' => '💵 Unit price',
        'confirm_total' => '💎 Total payment',
        'confirm_discount' => '🏷️ Discount',
        'confirm_info' => '📝 Information',
        'confirm_button' => '✅ Confirm Order',
    ];

    public function getLocaleCode(): string
    {
        return 'en';
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

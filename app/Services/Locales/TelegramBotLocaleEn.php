<?php

class TelegramBotLocaleEn implements TelegramBotLocaleInterface
{
    private array $map = [
        'invalid_command' => '❌ Invalid command. Send /start to open the bot.',
        'back_home' => '◀️ Back',
        'menu_shop' => '🛍️ Shop',
        'menu_orders' => '📦 Orders',
        'menu_help' => '❓ Help',
        'menu_language' => '🌐 Language',
        'menu_admin' => '📊 Admin Stats',
        'main_prompt' => '⚡ <b>Please choose an option below to continue:</b>',
        'main_prompt_short' => '⚡ Choose an option below to continue',
        'greeting' => "👋 Hello <b>{name}</b>!\nWelcome to <b>{site}</b> 🌟\n\n🔗 Website: <a href=\"https://{domain}\"><b>{domain}</b></a>\n\n━━━━━━━━━━━━━━\n⚡ <b>Please choose an option below to continue:</b>",
        'language_picker' => "🌟 <b>Hello {name}</b>\n\nWelcome to <b>{site}</b> 🌟\n🌐 Please choose your language to continue:\n\n🔗 Official: <b>kaishop.id.vn</b>",
        'language_updated_vi' => '🇺🇸 Switched to English.',
        'language_updated_en' => '🇺🇸 Switched to English.',
        'help_title' => '✨ <b>HELP — COMMAND LIST</b>',
        'help_shop' => '🛍️ /shop    — Open shop',
        'help_orders' => '📦 /orders  — Recent orders',
        'help_menu' => '📋 /menu    — Open quick menu',
        'help_help' => '❓ /help    — Help',
        'help_admin' => "\n🔐 <b>ADMIN COMMANDS:</b>\n📊 /stats                   — Stats\n📢 /broadcast &lt;content&gt;   — Broadcast\n🚧 /maintenance on|off       — Maintenance\n🏦 /setbank &lt;bank|acc|name&gt; — Update bank info",

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

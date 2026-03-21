<?php

/**
 * TelegramBotService — KaiShop Bot Core Logic
 *
 * Architecture:
 *  - Standalone Flow: Shadow Account tự động cho user mới
 *  - Role-Based Menus: /start và /menu hiển thị khác nhau cho User/Admin
 *  - Shared Backend: 100% dùng chung Model/Service với Web
 *  - File-based Rate Limit: tồn tại giữa các webhook request độc lập
 *  - Purchase Cooldown: chặn double-click mua hàng
 *  - Deposit TTL 5 phút: đồng bộ với Web, SePay webhook tự từ chối nếu quá hạn
 *
 * @see TelegramConfig   — tất cả constants và getters tập trung
 * @see TelegramService  — API wrapper cấp thấp (sendTo, buildInlineKeyboard)
 *
 * Traits (mỗi file ~600–900 dòng):
 *  - TelegramBotServiceDepositTrait  — Nạp tiền ngân hàng + Binance Pay
 *  - TelegramBotServiceShopTrait     — Shop, mua hàng, danh mục, sản phẩm
 *  - TelegramBotServiceAdminTrait    — Lệnh admin: stats, broadcast, setbank
 */
class TelegramBotService
{
    use TelegramBotServiceDepositTrait;
    use TelegramBotServiceShopTrait;
    use TelegramBotServiceAdminTrait;

    // =========================================================
    //  Dependencies
    // =========================================================

    private TelegramService $telegram;
    private UserTelegramLink $linkModel;
    private TelegramLinkCode $otpModel;
    private Product $productModel;
    private Category $categoryModel;
    private PurchaseService $purchaseService;
    private DepositService $depositService;
    private User $userModel;
    private Order $orderModel;
    private AccountEcosystemService $accountEcosystemService;
    private ?TimeService $timeService = null;
    private TelegramAccountService $accService;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
        $this->linkModel = new UserTelegramLink();
        $this->otpModel = new TelegramLinkCode();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->purchaseService = new PurchaseService();
        $this->depositService = new DepositService();
        $this->userModel = new User();
        $this->orderModel = new Order();
        $this->accountEcosystemService = new AccountEcosystemService($this->userModel->getConnection());
        $this->accService = new TelegramAccountService();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
    }

    // =========================================================
    //  Telegram Locale
    // =========================================================

    private function telegramLocaleDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'locale';
    }

    private function telegramLocaleFile(int $telegramId): string
    {
        return $this->telegramLocaleDir() . DIRECTORY_SEPARATOR . $telegramId . '.txt';
    }

    private function hasTelegramLocale(int $telegramId): bool
    {
        return in_array($this->getTelegramLocale($telegramId), ['vi', 'en'], true);
    }

    private function getTelegramLocale(int $telegramId): string
    {
        $file = $this->telegramLocaleFile($telegramId);
        if (!is_file($file)) {
            return '';
        }

        $locale = strtolower(trim((string) @file_get_contents($file)));
        return in_array($locale, ['vi', 'en'], true) ? $locale : '';
    }

    private function setTelegramLocale(int $telegramId, string $locale): void
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, ['vi', 'en'], true)) {
            return;
        }

        $dir = $this->telegramLocaleDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        @file_put_contents($this->telegramLocaleFile($telegramId), $locale, LOCK_EX);
    }

    private function isTelegramEnglish(int $telegramId): bool
    {
        return $this->getTelegramLocale($telegramId) === 'en';
    }

    private function tgText(int $telegramId, string $key, array $vars = []): string
    {
        $locale = $this->isTelegramEnglish($telegramId) ? 'en' : 'vi';
        $map = [
            'vi' => [
                'invalid_command' => '❌ Lệnh không hợp lệ. Gửi /start để mở bot.',
                'back_home' => '◀️ Quay lại',
                'menu_shop' => '🛍️ Cửa hàng',
                'menu_wallet' => '💰 Ví của tôi',
                'menu_deposit' => '💳 Nạp tiền',
                'menu_orders' => '📦 Đơn hàng',
                'menu_link' => '🔗 Liên kết Web',
                'menu_unlink' => '🔓 Hủy liên kết',
                'menu_help' => '❓ Trợ giúp',
                'menu_language' => '🌐 Ngôn ngữ',
                'menu_admin' => '📊 Thống kê Admin',
                'main_prompt' => '👇 Chọn chức năng bên dưới để bắt đầu:',
                'main_prompt_short' => '👇 Chọn chức năng bên dưới để bắt đầu',
                'greeting' => "👋 Xin chào <b>{name}</b>!\nChào mừng bạn đến với <b>{site}</b>.\nOfficial Website: 🔗 <a href=\"https://{domain}\">Here</a>\n\n👤 Tài khoản: <b>{username}</b>\n💵 Số dư: <b>{money}</b>\n\n━━━━━━━━━━━━━━\n👇 Chọn chức năng bên dưới để bắt đầu",
                'language_picker' => "✨ <b>Xin chào {name}</b>\n\nChào mừng bạn đến với <b>{site}</b>.\n🌐 Vui lòng chọn ngôn ngữ để tiếp tục:",
                'language_updated_vi' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
                'language_updated_en' => '🇺🇸 Switched to English.',
                'help_title' => '✨ <b>TRỢ GIÚP — DANH SÁCH LỆNH</b>',
                'help_shop' => '🛍️ /shop    — Cửa hàng',
                'help_wallet' => '💰 /balance — Ví của tôi',
                'help_bank' => '🏦 /bank    — Nạp tiền ngân hàng (VND)',
                'help_binance' => '🟡 /binance — Nạp tiền Binance Pay (USD)',
                'help_orders' => '📦 /orders  — Lịch sử đơn hàng',
                'help_menu' => '📋 /menu    — Mở menu nhanh',
                'help_link' => '🔗 /link    — Liên kết Web',
                'help_help' => '❓ /help    — Trợ giúp',
                'help_admin' => "\n🔐 <b>LỆNH ADMIN:</b>\n📊 /stats                   — Thống kê\n📢 /broadcast &lt;nội_dung&gt; — Gửi thông báo\n🚧 /maintenance on|off       — Bảo trì\n🏦 /setbank &lt;bank|stk|chủ&gt; — Đổi ngân hàng",
                'deposit_menu_title' => '💳 <b>CHỌN PHƯƠNG THỨC NẠP TIỀN</b>',
                'deposit_menu_desc' => "1️⃣ <b>Ngân hàng (VND)</b> — Chuyển khoản nội địa qua SePay.\n2️⃣ <b>Binance Pay (USDT)</b> — Nạp qua tài khoản Binance Funding.\n\n👇 Vui lòng chọn phương thức phù hợp:",
                'deposit_menu_bank' => '🏦 Ngân hàng',
                'deposit_menu_bank_off' => '🏦 Ngân hàng (Bảo trì)',
                'deposit_menu_binance' => '🟡 Binance Pay',
                'deposit_menu_binance_off' => '🟡 Binance (Bảo trì)',
                'deposit_input_title' => "💳 <b>NẠP TIỀN</b>\n\n📌 Nạp tối thiểu: <b>{min}</b>\n👇 Chọn nhanh hoặc nhập số tiền bạn muốn nạp:",
                'binance_input_title' => "💳 <b>BINANCE PAY (USDT)</b>\n\n📌 Nạp tối thiểu: <b>${min}</b>\n👇 Chọn nhanh hoặc nhập số USDT bạn muốn nạp:",
            ],
            'en' => [
                'invalid_command' => '❌ Invalid command. Send /start to open the bot.',
                'back_home' => '◀️ Back',
                'menu_shop' => '🛍️ Shop',
                'menu_wallet' => '💰 Wallet',
                'menu_deposit' => '💳 Top Up',
                'menu_orders' => '📦 Orders',
                'menu_link' => '🔗 Link Web',
                'menu_unlink' => '🔓 Unlink Web',
                'menu_help' => '❓ Help',
                'menu_language' => '🌐 Language',
                'menu_admin' => '📊 Admin Stats',
                'main_prompt' => '👇 Choose an option below to continue:',
                'main_prompt_short' => '👇 Choose an option below to continue',
                'greeting' => "👋 Hello <b>{name}</b>!\nWelcome to <b>{site}</b>.\nOfficial Website: 🔗 <a href=\"https://{domain}\">Here</a>\n\n👤 Account: <b>{username}</b>\n💵 Balance: <b>{money}</b>\n\n━━━━━━━━━━━━━━\n👇 Choose an option below to continue",
                'language_picker' => "✨ <b>Hello {name}</b>\n\nWelcome to <b>{site}</b>.\n🌐 Please choose your language to continue:",
                'language_updated_vi' => '🇻🇳 Đã chuyển sang Tiếng Việt.',
                'language_updated_en' => '🇺🇸 Switched to English.',
                'help_title' => '✨ <b>HELP — COMMAND LIST</b>',
                'help_shop' => '🛍️ /shop    — Open shop',
                'help_wallet' => '💰 /balance — View wallet',
                'help_bank' => '🏦 /bank    — Bank top-up (VND)',
                'help_binance' => '🟡 /binance — Binance Pay top-up (USD)',
                'help_orders' => '📦 /orders  — Recent orders',
                'help_menu' => '📋 /menu    — Open quick menu',
                'help_link' => '🔗 /link    — Link web account',
                'help_help' => '❓ /help    — Help',
                'help_admin' => "\n🔐 <b>ADMIN COMMANDS:</b>\n📊 /stats                   — Stats\n📢 /broadcast &lt;content&gt;   — Broadcast\n🚧 /maintenance on|off       — Maintenance\n🏦 /setbank &lt;bank|acc|name&gt; — Update bank info",
                'deposit_menu_title' => '💳 <b>CHOOSE A TOP-UP METHOD</b>',
                'deposit_menu_desc' => "1️⃣ <b>Bank (VND)</b> — Domestic transfer via SePay.\n2️⃣ <b>Binance Pay (USDT)</b> — Funding wallet transfer.\n\n👇 Please choose a payment method:",
                'deposit_menu_bank' => '🏦 Bank',
                'deposit_menu_bank_off' => '🏦 Bank (Offline)',
                'deposit_menu_binance' => '🟡 Binance Pay',
                'deposit_menu_binance_off' => '🟡 Binance (Offline)',
                'deposit_input_title' => "💳 <b>TOP UP</b>\n\n📌 Minimum deposit: <b>{min}</b>\n👇 Choose a quick amount or enter your deposit value:",
                'binance_input_title' => "💳 <b>BINANCE PAY (USDT)</b>\n\n📌 Minimum deposit: <b>${min}</b>\n👇 Choose a quick amount or enter your USDT amount:",
            ],
        ];

        $text = $map[$locale][$key] ?? ($map['vi'][$key] ?? $key);
        if ($vars === []) {
            return $text;
        }

        $replace = [];
        foreach ($vars as $name => $value) {
            $replace['{' . $name . '}'] = (string) $value;
        }

        return strtr($text, $replace);
    }

    // =========================================================
    //  Bot Initialization
    // =========================================================

    /**
     * Set up bot commands and menu button.
     * Called from Admin Panel to sync with Telegram.
     */
    public function initializeBot(): array
    {
        $commands = [
            ['command' => 'start', 'description' => 'Mở bot'],
            ['command' => 'menu', 'description' => 'Mở menu nhanh'],
            ['command' => 'shop', 'description' => 'Duyệt danh mục sản phẩm'],
            ['command' => 'wallet', 'description' => 'Xem số dư ví'],
            ['command' => 'balance', 'description' => 'Xem số dư ví'],
            ['command' => 'bank', 'description' => 'Nạp tiền qua ngân hàng (VND)'],
            ['command' => 'deposit', 'description' => 'Nạp tiền qua ngân hàng'],
            ['command' => 'binance', 'description' => 'Nạp tiền qua Binance Pay (USD)'],
            ['command' => 'orders', 'description' => 'Lịch sử 5 đơn hàng gần nhất'],
            ['command' => 'help', 'description' => 'Danh sách lệnh trợ giúp'],
        ];

        $englishCommands = [
            ['command' => 'start', 'description' => 'Open bot'],
            ['command' => 'menu', 'description' => 'Open quick menu'],
            ['command' => 'shop', 'description' => 'Browse products'],
            ['command' => 'wallet', 'description' => 'View wallet'],
            ['command' => 'balance', 'description' => 'View wallet'],
            ['command' => 'bank', 'description' => 'Bank deposit (VND)'],
            ['command' => 'deposit', 'description' => 'Bank deposit'],
            ['command' => 'binance', 'description' => 'Binance Pay deposit (USD)'],
            ['command' => 'orders', 'description' => 'Recent orders'],
            ['command' => 'help', 'description' => 'Help command list'],
        ];

        $res = $this->telegram->setMyCommands($commands);
        if (empty($res['ok'])) {
            return $res;
        }

        $this->telegram->setMyCommands($englishCommands, null, 'en');

        $adminCommands = array_merge($commands, [
            ['command' => 'stats', 'description' => 'Thống kê hệ thống (Admin)'],
            ['command' => 'broadcast', 'description' => 'Gửi thông báo hàng loạt (Admin)'],
            ['command' => 'maintenance', 'description' => 'Bật/tắt bảo trì (Admin)'],
            ['command' => 'setbank', 'description' => 'Cập nhật thông tin ngân hàng (Admin)'],
        ]);

        $englishAdminCommands = array_merge($englishCommands, [
            ['command' => 'stats', 'description' => 'System stats (Admin)'],
            ['command' => 'broadcast', 'description' => 'Broadcast message (Admin)'],
            ['command' => 'maintenance', 'description' => 'Toggle maintenance (Admin)'],
            ['command' => 'setbank', 'description' => 'Update bank info (Admin)'],
        ]);

        foreach (TelegramConfig::adminIds() as $adminId) {
            $this->telegram->apiCall('setMyCommands', [
                'commands' => json_encode($adminCommands, JSON_UNESCAPED_UNICODE),
                'scope' => json_encode(['type' => 'chat', 'chat_id' => (int) $adminId], JSON_UNESCAPED_UNICODE),
            ]);
            $this->telegram->apiCall('setMyCommands', [
                'commands' => json_encode($englishAdminCommands, JSON_UNESCAPED_UNICODE),
                'scope' => json_encode(['type' => 'chat', 'chat_id' => (int) $adminId], JSON_UNESCAPED_UNICODE),
                'language_code' => 'en',
            ]);
        }

        $this->telegram->apiCall('setChatMenuButton', [
            'menu_button' => json_encode(['type' => 'commands'])
        ]);

        return $res;
    }

    // =========================================================
    //  Entry Point
    // =========================================================

    public function processUpdate(array $update): void
    {
        if ($this->isDuplicateOrOldUpdate($update)) {
            return;
        }

        // Track User
        $tid = (int) ($update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? 0);
        if ($tid > 0) {
            $from = $update['message']['from'] ?? $update['callback_query']['from'] ?? [];
            (new TelegramUser())->upsert(
                $tid,
                $from['username'] ?? null,
                $from['first_name'] ?? null
            );
        }

        $telegramId = $tid;
        $chatId = (string) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? '');

        if ($telegramId > 0) {
            $rateState = $this->checkUserRateLimit($telegramId, $update);
            if (empty($rateState['allowed'])) {
                if ($chatId !== '' && $this->checkAndSetCooldown("rl_warn_{$telegramId}", 5)) {
                    $retryAfter = max(1, (int) ($rateState['retry_after'] ?? 1));
                    $actionName = (string) ($rateState['action'] ?? 'request');
                    $message = "⚠️ <b>Bạn đang thao tác quá nhanh!</b>\n";
                    $message .= "Hành động: <b>{$actionName}</b>\n";
                    $message .= "Vui lòng chờ <b>{$retryAfter} giây</b> rồi thử lại.";
                    $this->telegram->sendTo($chatId, $message);
                }
                return;
            }
        }

        if ($telegramId > 0 && $chatId === '') {
            return;
        }

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    // =========================================================
    //  Message Router
    // =========================================================

    private function handleMessage(array $message): void
    {
        $chatId = (string) $message['chat']['id'];
        $telegramId = (int) $message['from']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $fromName = trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));

        $this->upsertTelegramUser($message['from']);
        // Only log MESSAGE if it is a command or part of an active flow
        $isCommand = str_starts_with($text, '/');
        $inFlow = $this->isPurchaseInputMode($telegramId) || $this->isDepositInputMode($telegramId) || $this->getBinanceSession($telegramId);
        if ($isCommand || $inFlow) {
            $this->writeLog("[MSG] {$fromName} ({$telegramId}): {$text}", 'INFO', 'INCOMING', 'MESSAGE');
        }

        if (TelegramConfig::isMaintenanceEnabled() && !TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, TelegramConfig::maintenanceMessage());
            return;
        }

        if (!str_starts_with($text, '/')) {
            if ($this->handleBinanceInput($chatId, $telegramId, $text))
                return;
            if ($this->handleDepositAmountInput($chatId, $telegramId, $text))
                return;
            if ($this->handlePurchaseInput($chatId, $telegramId, $text))
                return;
            $this->handleMenuText($chatId, $telegramId, $text);
            return;
        }

        $parts = explode(' ', $text);
        $command = strtolower(explode('@', $parts[0])[0]);
        $args = array_slice($parts, 1);

        match ($command) {
            '/start' => $this->cmdStart($chatId, $telegramId, $fromName),
            '/menu' => $this->cmdMenu($chatId, $telegramId),
            '/shop' => $this->cmdShop($chatId),
            '/wallet',
            '/balance' => $this->cmdWallet($chatId, $telegramId),
            '/deposit',
            '/bank' => $this->cmdDeposit($chatId, $telegramId, $args),
            '/binance' => $this->cmdBinance($chatId, $telegramId, $args),
            '/orders' => $this->cmdOrders($chatId, $telegramId),
            '/link' => $this->cmdLink($chatId, $telegramId, $args, $message['from']),
            '/unlink' => $this->cmdUnlink($chatId, $telegramId),
            '/help' => $this->cmdHelp($chatId, $telegramId),
            // Admin
            '/stats' => $this->cmdStats($chatId, $telegramId),
            '/broadcast' => $this->cmdBroadcast($chatId, $telegramId, $args),
            '/maintenance' => $this->cmdMaintenance($chatId, $telegramId, $args),
            '/setbank' => $this->cmdSetBank($chatId, $telegramId, $args),
            default => $this->telegram->sendTo($chatId, "❌ Lệnh không hợp lệ. Gửi /start để mở menu."),
        };
    }

    private function handleMenuText(string $chatId, int $telegramId, string $rawText): void
    {
        $text = function_exists('mb_strtolower')
            ? mb_strtolower(trim($rawText), 'UTF-8')
            : strtolower(trim($rawText));

        if ($text === '' || $text === 'menu' || $text === 'mở menu' || $text === 'mo menu') {
            $this->cmdMenu($chatId, $telegramId);
            return;
        }
        if ($text === 'shop' || $text === 'cửa hàng' || $text === 'cua hang') {
            $this->cmdShop($chatId);
            return;
        }
        if ($text === 'ví' || $text === 'vi' || $text === 'số dư' || $text === 'so du') {
            $this->cmdWallet($chatId, $telegramId);
            return;
        }
        if ($text === 'nạp tiền' || $text === 'nap tien') {
            $this->startDepositInputMode($chatId, $telegramId);
            return;
        }
        if ($text === 'đơn hàng' || $text === 'don hang') {
            $this->cmdOrders($chatId, $telegramId);
            return;
        }
        if ($text === 'liên kết web' || $text === 'lien ket web') {
            $this->cmdLink($chatId, $telegramId, [], []);
            return;
        }
        if ($text === 'trợ giúp' || $text === 'tro giup' || $text === 'help') {
            $this->cmdHelp($chatId, $telegramId);
            return;
        }
        if ($text === 'back' || $text === 'quay lại' || $text === 'quay lai' || $text === 'trở lại' || $text === 'tro lai') {
            $this->showMainMenu($chatId, $telegramId, '', true);
            return;
        }

        if ($text === 'language' || $text === 'lang' || $text === 'ngon ngu') {
            $this->showLanguageSelection($chatId, $telegramId);
            return;
        }
        if ($text === 'ngôn ngữ') {
            $this->showLanguageSelection($chatId, $telegramId);
            return;
        }

        $this->cmdMenu($chatId, $telegramId);
    }

    // =========================================================
    //  Callback Query Router
    // =========================================================

    private function handleCallbackQuery(array $query): void
    {
        $callbackId = (string) $query['id'];
        $chatId = (string) $query['message']['chat']['id'];
        $telegramId = (int) $query['from']['id'];
        $data = (string) ($query['data'] ?? '');
        $messageId = (int) ($query['message']['message_id'] ?? 0);

        $this->upsertTelegramUser($query['from']);

        if (TelegramConfig::isMaintenanceEnabled() && !TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->answerCallbackQuery($callbackId, "Hệ thống đang bảo trì, vui lòng thử lại sau.", true);
            return;
        }

        if ($data === 'change_lang') {
            $this->showLanguageSelection($chatId, $telegramId, $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if ($data === 'lang_vi' || $data === 'lang_en') {
            $locale = $data === 'lang_en' ? 'en' : 'vi';
            $name = trim((string) (($query['from']['first_name'] ?? '') . ' ' . ($query['from']['last_name'] ?? '')));
            $this->setTelegramLocale($telegramId, $locale);
            $this->showMainMenu($chatId, $telegramId, $name, true, $messageId);
            $this->telegram->answerCallbackQuery($callbackId, $this->tgText($telegramId, $locale === 'en' ? 'language_updated_en' : 'language_updated_vi'));
            return;
        }

        // Composite callbacks must be parsed before generic split('_')
        if (preg_match('/^buy_gift_(\d+)_(\d+)$/', $data, $m)) {
            $this->startGiftCodeInputMode($chatId, $telegramId, (int) $m[1], (int) $m[2], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^do_buy_(\d+)_(\d+)$/', $data, $m)) {
            $this->cbDoBuy($chatId, $telegramId, (int) $m[1], (int) $m[2], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^order_pay_bank_(\d+)$/', $data, $m)) {
            $this->cbOrderPayBank($chatId, $telegramId, (int) $m[1], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^order_pay_binance_(\d+)$/', $data, $m)) {
            $this->cbOrderPayBinance($chatId, $telegramId, (int) $m[1], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^order_check_(\d+)$/', $data, $m)) {
            $this->cbOrderCheck($chatId, $telegramId, (int) $m[1], $messageId, $callbackId);
            return;
        }
        if (preg_match('/^order_cancel_(\d+)$/', $data, $m)) {
            $this->cbOrderCancel($chatId, $telegramId, (int) $m[1], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^do_unlink_(bot|web)$/', $data, $m)) {
            $this->cbDoUnlink($chatId, $telegramId, (string) $m[1], $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if ($data === 'deposit_menu') {
            $this->showDepositMethodMenu($chatId, $telegramId, $messageId);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if ($data === 'deposit_bank') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->startDepositInputMode($chatId, $telegramId, $messageId);
            return;
        }
        if ($data === 'binance_start') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->startBinanceInputMode($chatId, $telegramId, $messageId);
            return;
        }
        if (preg_match('/^bin_amount_([0-9]+(?:\.[0-9]{1,2})?)$/', $data, $m)) {
            $amount = (float) $m[1];
            // Sau khi chọn số tiền, hỏi UID
            $this->setBinanceSession($telegramId, [
                'step' => 'await_uid',
                'amount' => $amount,
                'message_id' => $messageId
            ], 300);

            $msg = "💵 Số tiền: <b>$" . number_format($amount, 2) . " USDT</b>\n\n" .
                "👉 Bây giờ hãy nhập <b>Binance UID</b> của bạn để hệ thống tự động cộng tiền.";

            $this->telegram->editOrSend($chatId, $messageId, $msg);
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }
        if (preg_match('/^bin_check_([A-Za-z0-9_-]{3,64})$/', $data, $m)) {
            $this->cbBinanceCheck($chatId, $telegramId, (string) $m[1], $messageId, $callbackId);
            return;
        }
        if (preg_match('/^deposit_(\d+)$/', $data, $m)) {
            $amount = (int) $m[1];
            if ($amount >= DepositService::MIN_AMOUNT) {
                $this->clearDepositInputMode($telegramId);
                $this->cmdDeposit($chatId, $telegramId, [(string) $amount], $messageId);
                $this->telegram->answerCallbackQuery($callbackId);
            } else {
                $this->telegram->answerCallbackQuery($callbackId, "Số tiền tối thiểu là " . number_format(DepositService::MIN_AMOUNT) . "đ", true);
            }
            return;
        }

        if (preg_match('/^cancel_dep_([A-Za-z0-9_-]{3,64})$/', $data, $m)) {
            $this->cancelDepositFromCallback($chatId, $telegramId, (string) $m[1], $messageId, $callbackId);
            return;
        }

        $parts = explode('_', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'shop':
                $this->cmdShop($chatId, $messageId);
                break;
            case 'cat':
                $this->cbCategory($chatId, (int) ($parts[1] ?? 0), $messageId);
                break;
            case 'prod':
                $this->cbProduct($chatId, (int) ($parts[1] ?? 0), $messageId);
                break;
            case 'buy':
                $this->cbBuyConfirm($chatId, $telegramId, (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 1), null, $messageId);
                break;
            case 'wallet':
                $this->cmdWallet($chatId, $telegramId, $messageId);
                break;
            case 'deposit':
                $this->showDepositMethodMenu($chatId, $telegramId, $messageId);
                break;
            case 'orders':
                $this->cmdOrders($chatId, $telegramId, $messageId);
                break;
            case 'unlink':
                $this->cmdUnlink($chatId, $telegramId);
                break;
            case 'menu':
            case 'back':
                $this->clearDepositInputMode($telegramId);
                $this->clearBinanceSession($telegramId);
                $fromName = trim(($query['from']['first_name'] ?? '') . ' ' . ($query['from']['last_name'] ?? ''));
                $this->showMainMenu($chatId, $telegramId, $fromName, true, $messageId);
                break;
            case 'help':
                $this->cmdHelp($chatId, $telegramId, $messageId);
                break;
            case 'link':
                $this->cmdLink($chatId, $telegramId, [], $query['from'] ?? []);
                break;
            case 'stats':
                $this->cmdStats($chatId, $telegramId);
                break;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    private function cancelDepositFromCallback(string $chatId, int $telegramId, string $depositCode, int $messageId, string $callbackId): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            $this->telegram->answerCallbackQuery($callbackId, 'Không tìm thấy tài khoản liên kết.', true);
            return;
        }

        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findByCode($depositCode);
        if (
            !$deposit
            || (int) ($deposit['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)
        ) {
            $this->telegram->answerCallbackQuery($callbackId, 'Không tìm thấy giao dịch nạp tiền.', true);
            return;
        }

        $method = $this->normalizeDepositMethod((string) ($deposit['method'] ?? DepositService::METHOD_BANK_SEPAY));
        $methodLabel = $method === DepositService::METHOD_BINANCE ? 'Binance' : 'Bank';
        $status = strtolower(trim((string) ($deposit['status'] ?? '')));

        if ($status === 'pending') {
            $depositModel->cancelByUser((int) $deposit['id'], (int) ($user['id'] ?? 0));
            $this->clearDepositInputMode($telegramId);
            if ($method === DepositService::METHOD_BINANCE) {
                $this->clearBinanceSession($telegramId);
            }
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            $this->showDepositMethodMenu($chatId, $telegramId);
            $this->telegram->answerCallbackQuery($callbackId, 'Đã hủy giao dịch ' . $methodLabel . '.');
            return;
        }

        $statusText = match ($status) {
            'completed' => 'đã hoàn tất',
            'cancelled' => 'đã bị hủy',
            'expired' => 'đã hết hạn',
            default => 'không còn hoạt động',
        };

        $this->telegram->answerCallbackQuery($callbackId, 'Giao dịch ' . $methodLabel . ' ' . $statusText . '.', true);
    }

    private function normalizeDepositMethod(string $method): string
    {
        $method = strtolower(trim($method));
        return $method === DepositService::METHOD_BINANCE
            ? DepositService::METHOD_BINANCE
            : DepositService::METHOD_BANK_SEPAY;
    }

    // =========================================================
    //  User Commands (Core)
    // =========================================================

    /** /start — Chào mừng + mở menu */
    private function cmdStart(string $chatId, int $telegramId, string $name): void
    {
        $this->showLanguageSelection($chatId, $telegramId, 0, $name);
    }

    /** /menu — Menu bàn phím + inline theo vai trò */
    private function cmdMenu(string $chatId, int $telegramId): void
    {
        if (!$this->hasTelegramLocale($telegramId)) {
            $this->showLanguageSelection($chatId, $telegramId);
            return;
        }

        $this->showMainMenu($chatId, $telegramId, '', false);
    }

    private function showLanguageSelection(string $chatId, int $telegramId, int $messageId = 0, string $name = ''): void
    {
        $siteName = get_setting('ten_web', 'KaiShop');
        $displayName = trim($name) !== '' ? trim($name) : ($this->isTelegramEnglish($telegramId) ? 'friend' : 'bạn');

        $message = $this->tgText($telegramId, 'language_picker', [
            'name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
            'site' => htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8'),
        ]);

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🇻🇳 Việt Nam', 'callback_data' => 'lang_vi'],
                ['text' => '🇺🇸 English', 'callback_data' => 'lang_en'],
            ],
        ]);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $message, $markup);
            return;
        }

        $this->telegram->sendTo($chatId, $message, ['reply_markup' => $markup]);
    }

    /**
     * Main menu renderer.
     * When $messageId > 0, edits existing message in-place (no chat spam).
     */
    private function showMainMenu(string $chatId, int $telegramId, string $name = '', bool $withGreeting = false, int $messageId = 0): void
    {
        if (!$this->hasTelegramLocale($telegramId)) {
            $this->showLanguageSelection($chatId, $telegramId, $messageId, $name);
            return;
        }

        $siteName = get_setting('ten_web', 'KaiShop');
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $inlineRows = $this->buildMainInlineRows($telegramId, (string) ($user['username'] ?? ''));
        $markup = TelegramService::buildInlineKeyboard($inlineRows);

        $domain = defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_HOST) : ($_SERVER['HTTP_HOST'] ?? 'kaishop.id.vn');
        if (empty($domain))
            $domain = 'kaishop.id.vn';

        if ($withGreeting) {
            $username = trim((string) ($user['username'] ?? ''));
            $displayName = trim($name) !== '' ? trim($name) : $username;
            if ($displayName === '') {
                $displayName = $this->isTelegramEnglish($telegramId) ? 'friend' : 'bạn';
            }
            if ($displayName === '')
                $displayName = 'bạn';

            $money = number_format((int) ($user['money'] ?? 0)) . "đ";

            $msg = "👋 Xin chào <b>" . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . "</b>!\n";
            $msg .= "Chào mừng bạn đến với <b>" . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . "</b> 🤖.\n";
            $msg .= "Official Website: 🔗 <a href=\"https://{$domain}\">Here</a>\n\n";
            $msg .= "👤 Tài khoản: <b>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</b>\n";
            $msg .= "💵 Số dư: <b>{$money}</b>\n\n";
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "👇 Chọn chức năng bên dưới để bắt đầu\n\n";
        } else {
            $msg = "👇 <b>Chọn chức năng bên dưới để bắt đầu:</b>\n\n";
        }

        if ($withGreeting) {
            $username = trim((string) ($user['username'] ?? ''));
            $displayName = trim($name) !== '' ? trim($name) : $username;
            if ($displayName === '') {
                $displayName = $this->isTelegramEnglish($telegramId) ? 'friend' : 'bạn';
            }

            $money = number_format((int) ($user['money'] ?? 0)) . 'đ';
            $msg = $this->tgText($telegramId, 'greeting', [
                'name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
                'site' => htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8'),
                'domain' => htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8'),
                'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
                'money' => htmlspecialchars($money, ENT_QUOTES, 'UTF-8'),
            ]);
        } else {
            $msg = '<b>' . $this->tgText($telegramId, 'main_prompt') . '</b>';
        }

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /** @return array<int,array<int,array<string,string>>> */
    private function buildMainInlineRows(int $telegramId, string $username = ''): array
    {
        $isLinked = $username !== '' && !str_starts_with($username, 'tg_');
        $linkText = $isLinked ? '🔗 Hủy liên kết' : '🔗 Liên kết Web';
        $linkText = $isLinked
            ? $this->tgText($telegramId, 'menu_unlink')
            : $this->tgText($telegramId, 'menu_link');
        $linkCallback = $isLinked ? 'unlink' : 'link_help';

        $inlineRows = [
            [
                ['text' => '🛍️ Cửa hàng', 'callback_data' => 'shop'],
                ['text' => '💰 Ví của tôi', 'callback_data' => 'wallet'],
            ],
            [
                ['text' => '💳 Nạp tiền', 'callback_data' => 'deposit_menu'],
                ['text' => '📦 Đơn hàng', 'callback_data' => 'orders'],
            ],
            [
                ['text' => $linkText, 'callback_data' => $linkCallback],
                ['text' => '❓ Trợ giúp', 'callback_data' => 'help'],
            ],
        ];

        $inlineRows = [
            [
                ['text' => $this->tgText($telegramId, 'menu_shop'), 'callback_data' => 'shop'],
                ['text' => $this->tgText($telegramId, 'menu_wallet'), 'callback_data' => 'wallet'],
            ],
            [
                ['text' => $this->tgText($telegramId, 'menu_deposit'), 'callback_data' => 'deposit_menu'],
                ['text' => $this->tgText($telegramId, 'menu_orders'), 'callback_data' => 'orders'],
            ],
            [
                ['text' => $linkText, 'callback_data' => $linkCallback],
                ['text' => $this->tgText($telegramId, 'menu_help'), 'callback_data' => 'help'],
            ],
            [
                ['text' => $this->tgText($telegramId, 'menu_language'), 'callback_data' => 'change_lang'],
            ],
        ];

        if (TelegramConfig::isAdmin($telegramId)) {
            $inlineRows[] = [
                ['text' => '📊 Thống kê Admin', 'callback_data' => 'stats_admin'],
            ];
        }

        if (TelegramConfig::isAdmin($telegramId)) {
            $lastIndex = count($inlineRows) - 1;
            if ($lastIndex >= 0) {
                $inlineRows[$lastIndex][0]['text'] = $this->tgText($telegramId, 'menu_admin');
            }
        }

        return $inlineRows;
    }

    /** @return array<string,string> */
    private function backHomeButton(int $telegramId = 0): array
    {
        if ($telegramId > 0) {
            return ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'back_home'];
        }

        return ['text' => '◀️ Quay lại', 'callback_data' => 'back_home'];
    }

    /** @return array<string,mixed> */
    private function buildPayNowBackKeyboard(string $payUrl = '', string $depositCode = ''): array
    {
        $rows = [];
        $url = trim($payUrl);
        if ($url !== '' && str_starts_with($url, 'http')) {
            $rows[] = [['text' => '💳 Thanh toán ngay', 'url' => $url]];
        } else {
            $rows[] = [['text' => '💳 Thanh toán ngay', 'callback_data' => 'deposit_menu']];
        }

        if ($depositCode !== '') {
            $rows[] = [['text' => '❌ Hủy giao dịch', 'callback_data' => 'cancel_dep_' . $depositCode]];
        } else {
            $rows[] = [['text' => '❌ Hủy giao dịch', 'callback_data' => 'deposit_menu']];
        }

        return TelegramService::buildInlineKeyboard($rows);
    }

    /** /shop — Danh mục sản phẩm */
    private function cmdShop(string $chatId, int $messageId = 0): void
    {
        $categories = $this->categoryModel->getActive();
        if (empty($categories)) {
            $emptyMsg = "🛍️ Hiện hệ thống chưa có danh mục sản phẩm nào.";
            $emptyMarkup = TelegramService::buildInlineKeyboard([
                [['text' => '⬅️ Quay lại menu chính', 'callback_data' => 'back_home']],
            ]);
            if ($messageId > 0) {
                $this->telegram->editOrSend($chatId, $messageId, $emptyMsg, $emptyMarkup);
            } else {
                $this->telegram->sendTo($chatId, $emptyMsg, ['reply_markup' => $emptyMarkup]);
            }
            return;
        }

        $rows = [];
        foreach ($categories as $cat) {
            $rows[] = [['text' => '📦 ' . $cat['name'], 'callback_data' => 'cat_' . $cat['id']]];
        }
        $rows[] = [['text' => '⬅️ Quay lại menu chính', 'callback_data' => 'back_home']];

        $msg = "🛍️ <b>TẤT CẢ DANH MỤC</b>\n\n👇 Vui lòng chọn danh mục:";
        $markup = TelegramService::buildInlineKeyboard($rows);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /** /wallet — Xem thông tin ví */
    private function cmdWallet(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $msg = "💰 <b>THÔNG TIN VÍ CỦA BẠN</b>\n\n";
        $msg .= "👤 Tài khoản: <b>" . htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') . "</b>\n";
        $msg .= "💵 Số dư: <b>" . number_format((int) ($user['money'] ?? 0)) . "đ</b>\n\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "👇 Cần nạp thêm? Chọn Nạp tiền ngay!";

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '💳 Nạp tiền ngay', 'callback_data' => 'deposit_menu'],
                $this->backHomeButton(),
            ]
        ]);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /** /orders — 5 đơn hàng gần nhất */
    private function cmdOrders(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $orders = $this->orderModel->getUserVisibleOrders((int) $user['id'], [], 0, 5);

        if (empty($orders)) {
            $this->telegram->sendTo($chatId, "📦 Bạn chưa có đơn hàng nào.\n\n👉 Chọn cửa hàng để mua sản phẩm", [
                'reply_markup' => TelegramService::buildInlineKeyboard([[$this->backHomeButton()]]),
            ]);
            return;
        }

        $msg = "📦 <b>LỊCH SỬ ĐƠN HÀNG (5 gần nhất)</b>\n\n";
        foreach ($orders as $o) {
            $statusIcon = ((string) ($o['status'] ?? '') === 'completed') ? '✅' : '⏳';
            $orderCode = htmlspecialchars((string) ($o['order_code_short'] ?? $o['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
            $productName = htmlspecialchars((string) ($o['product_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $price = number_format((int) ($o['price'] ?? 0)) . "đ";
            $quantity = max(1, (int) ($o['quantity'] ?? 1));

            $rawContent = trim((string) ($o['stock_content_plain'] ?? ''));
            if ($rawContent === '') {
                $rawContent = ((string) ($o['status'] ?? '') === 'completed')
                    ? 'Đơn hoàn tất nhưng chưa có nội dung bàn giao.'
                    : 'Đơn đang xử lý, nội dung sẽ được cập nhật sau.';
            }
            $content = $this->formatOrderContentForTelegram($rawContent);

            $msg .= "{$statusIcon} Mã đơn: <code>{$orderCode}</code>\n";
            $msg .= "📦 Tên SP: <b>{$productName}</b>\n";
            $msg .= "💰 Giá: <b>{$price}</b>\n";
            $msg .= "🔢 SL: <b>{$quantity}</b>\n";
            $msg .= "🔑 Nội dung:\n<code>{$content}</code>\n";
            $msg .= "━━━━━━━━━━━━━━\n";
        }

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🔄 Cập nhật', 'callback_data' => 'orders'],
                $this->backHomeButton(),
            ]
        ]);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    private function formatOrderContentForTelegram(string $content, int $limit = 500): string
    {
        $clean = trim($content);
        if ($clean === '')
            return 'Chưa có nội dung.';

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($clean, 'UTF-8') > $limit) {
                $clean = mb_substr($clean, 0, $limit, 'UTF-8') . "\n... (đã rút gọn)";
            }
        } elseif (strlen($clean) > $limit) {
            $clean = substr($clean, 0, $limit) . "\n... (đã rút gọn)";
        }

        return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    }

    // =========================================================
    //  Link / Unlink Commands
    // =========================================================

    /** /link <otp> — Liên kết tài khoản Web bằng mã OTP */
    private function cmdLink(string $chatId, int $telegramId, array $args, array $from): void
    {
        $code = trim($args[0] ?? '');
        if ($code === '') {
            $domain = defined('BASE_URL') ? parse_url(BASE_URL, PHP_URL_HOST) : ($_SERVER['HTTP_HOST'] ?? 'kaishop.id.vn');
            if (empty($domain))
                $domain = 'kaishop.id.vn';

            $this->telegram->sendTo(
                $chatId,
                "🔗 <b>LIÊN KẾT TÀI KHOẢN WEB</b>\n\n"
                . "1️⃣ Đăng nhập 🔗 <a href=\"https://{$domain}\">TRUY CẬP WEBSITE</a> › Hồ sơ › Liên kết Telegram.\n"
                . "2️⃣ Lấy mã OTP và gửi lệnh: <code>/link 123456</code>.\n\n"
                . "Sau khi liên kết, tài khoản Web và Telegram sẽ đồng bộ ví!",
                ['reply_markup' => TelegramService::buildInlineKeyboard([[$this->backHomeButton()]])]
            );
            return;
        }

        $targetUserId = $this->otpModel->verifyCode($code);
        if (!$targetUserId) {
            $this->telegram->sendTo($chatId, "❌ Mã OTP không đúng hoặc đã hết hạn. Vui lòng thử mã mới trên web.");
            return;
        }

        $targetUser = $this->userModel->findById($targetUserId);
        if (!$targetUser) {
            $this->telegram->sendTo($chatId, "❌ Không tìm thấy tài khoản web để liên kết.");
            return;
        }

        $currentLink = $this->linkModel->findByTelegramId($telegramId);
        $currentUserId = (int) ($currentLink['user_id'] ?? 0);

        if ($currentUserId > 0 && $currentUserId !== $targetUserId) {
            $merge = $this->accountEcosystemService->mergeIntoPrimary($currentUserId, $targetUserId);
            if (empty($merge['success'])) {
                $this->telegram->sendTo(
                    $chatId,
                    "⚠️ Lỗi hợp nhất dữ liệu: " . htmlspecialchars((string) ($merge['message'] ?? 'Lỗi không xác định.'))
                );
                return;
            }
        }

        $firstName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        $linked = $this->linkModel->linkUser($targetUserId, $telegramId, $from['username'] ?? null, $firstName);

        if (!$linked) {
            $this->writeLog("❌ Liên kết thất bại cho User #{$targetUserId}", 'WARN', 'INCOMING', 'AUTH');
            $this->telegram->sendTo($chatId, "❌ Liên kết thất bại. Vui lòng thử lại sau.");
            return;
        }

        $freshUser = $this->userModel->findById($targetUserId) ?: $targetUser;
        $this->writeLog("🔗 " . ($freshUser['username'] ?? 'User') . " đã liên kết tài khoản Web thành công", 'INFO', 'INCOMING', 'AUTH');
        $now = TimeService::instance()->formatDisplay('now', 'H:i:s d/m/Y');
        $this->telegram->sendTo(
            $chatId,
            "✅ <b>LIÊN KẾT THÀNH CÔNG</b>\n\n"
            . "👤 Tài khoản: <b>" . htmlspecialchars((string) ($freshUser['username'] ?? '')) . "</b>\n"
            . "💰 Số dư ví: <b>" . number_format((int) ($freshUser['money'] ?? 0)) . "đ</b>\n"
            . "📅 Thời gian: <b>{$now}</b>"
        );

        $this->cmdMenu($chatId, $telegramId);
    }

    /** /unlink — Hủy liên kết Telegram (Step 1: Chọn nơi giữ tiền) */
    private function cmdUnlink(string $chatId, int $telegramId): void
    {
        $link = $this->linkModel->findByTelegramId($telegramId);
        if (!$link) {
            $this->telegram->sendTo($chatId, "⚠️ Bạn chưa liên kết tài khoản nào.");
            return;
        }

        $firstName = htmlspecialchars($link['first_name'] ?? $link['telegram_username'] ?? 'Người dùng');
        $msg = "❓ <b>HỦY LIÊN KẾT TÀI KHOẢN</b>\n\n"
            . "Sau khi hủy liên kết, bạn muốn <b>số dư ví hiện tại</b> sẽ nằm ở đâu?\n\n"
            . "1. <b>🤖 {$firstName}</b>: Tiền sẽ được chuyển sang tài khoản Bot hiện tại.\n"
            . "2. <b>🌐 Tài khoản WEB</b>: Tiền sẽ ở lại tài khoản Web hiện tại.\n\n"
            . "⚠️ <i>Lưu ý: Hành động này không thể hoàn tác.</i>";

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🤖 ' . $firstName, 'callback_data' => 'do_unlink_bot'],
                ['text' => '🌐 Tài khoản WEB', 'callback_data' => 'do_unlink_web'],
            ],
            [['text' => '❌ Hủy bỏ', 'callback_data' => 'menu']]
        ]);

        $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
    }

    /** Step 2: Thực hiện hủy liên kết */
    private function cbDoUnlink(string $chatId, int $telegramId, string $dest, int $messageId): void
    {
        $res = $this->accService->unlinkWithChoice($telegramId, $dest);

        if ($res['success']) {
            $this->writeLog("🔓 User {$telegramId} đã hủy liên kết tài khoản", 'INFO', 'INCOMING', 'AUTH');
            $msg = "✅ <b>ĐÃ HỦY LIÊN KẾT</b>\n\n"
                . ($dest === 'bot' ? "Tiền đã được chuyển vào tài khoản ví Bot của bạn." : "Tiền vẫn nằm tại tài khoản Web của bạn.")
                . "\n\nBạn có thể liên kết lại bất cứ lúc nào.";
            $this->telegram->editMessage($chatId, $messageId, $msg, TelegramService::buildInlineKeyboard([
                [$this->backHomeButton()]
            ]));
        } else {
            $this->telegram->sendTo($chatId, "❌ " . $res['message']);
        }
    }

    /** /help — Danh sách lệnh */
    private function cmdHelp(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $isAdmin = TelegramConfig::isAdmin($telegramId);

        $msg = "✨ <b>TRỢ GIÚP — DANH SÁCH LỆNH</b>\n\n";
        $msg .= "🛍️ /shop    — Cửa hàng\n";
        $msg .= "💰 /balance — Ví của tôi\n";
        $msg .= "💳 /bank    — Nạp tiền ngân hàng (VND)\n";
        $msg .= "🟡 /binance — Nạp tiền Binance Pay (USD)\n";
        $msg .= "📦 /orders  — Lịch sử đơn hàng\n";
        $msg .= "📋 /menu    — Mở menu nhanh\n";
        $msg .= "🔗 /link    — Liên kết Web\n";
        $msg .= "❓ /help    — Trợ giúp\n";

        if ($isAdmin) {
            $msg .= "\n👑 <b>LỆNH ADMIN:</b>\n";
            $msg .= "📊 /stats                   — Thống kê\n";
            $msg .= "📢 /broadcast &lt;nội_dung&gt; — Gửi thông báo\n";
            $msg .= "🚧 /maintenance on|off       — Bảo trì\n";
            $msg .= "🏦 /setbank &lt;bank|stk|chủ&gt; — Đổi ngân hàng\n";
        }

        $msg = $this->tgText($telegramId, 'help_title') . "\n\n";
        $msg .= $this->tgText($telegramId, 'help_shop') . "\n";
        $msg .= $this->tgText($telegramId, 'help_wallet') . "\n";
        $msg .= $this->tgText($telegramId, 'help_bank') . "\n";
        $msg .= $this->tgText($telegramId, 'help_binance') . "\n";
        $msg .= $this->tgText($telegramId, 'help_orders') . "\n";
        $msg .= $this->tgText($telegramId, 'help_menu') . "\n";
        $msg .= $this->tgText($telegramId, 'help_link') . "\n";
        $msg .= $this->tgText($telegramId, 'help_help');
        if ($isAdmin) {
            $msg .= $this->tgText($telegramId, 'help_admin');
        }

        $markup = TelegramService::buildInlineKeyboard([[$this->backHomeButton($telegramId)]]);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    // =========================================================
    //  Shadow Account Management
    // =========================================================

    /**
     * Resolve linked user — tự động tạo Shadow Account nếu chưa có.
     * Trả về null nếu tài khoản bị ban hoặc không thể khởi tạo.
     */
    private function resolveLinkedUser(string $chatId, int $telegramId): ?array
    {
        $link = $this->linkModel->findByTelegramId($telegramId);
        if (!$link) {
            $this->ensureShadowAccount($telegramId);
            $link = $this->linkModel->findByTelegramId($telegramId);
        }

        if (!$link) {
            $this->telegram->sendTo($chatId, "❌ Không thể khởi tạo tài khoản. Vui lòng thử lại.");
            return null;
        }

        $user = $this->userModel->findById($link['user_id']);
        if (!$user) {
            $this->telegram->sendTo($chatId, "⚠️ Không tìm thấy tài khoản. Thử /link để liên kết lại.");
            return null;
        }

        if ((int) ($user['bannd'] ?? 0) === 1) {
            $this->telegram->sendTo($chatId, "🚫 Tài khoản của bạn đã bị khóa. Liên hệ hỗ trợ nếu có nhầm lẫn.");
            return null;
        }

        return $user;
    }

    /**
     * Đảm bảo user Telegram có Web User record (Shadow Account).
     * Username format: tg_{telegramId}
     */
    private function ensureShadowAccount(int $telegramId, ?string $username = null, ?string $firstName = null): void
    {
        if ($this->linkModel->findByTelegramId($telegramId))
            return;

        $shadowUsername = 'tg_' . $telegramId;
        $db = $this->userModel->getConnection();

        $stmt = $db->prepare("SELECT `id` FROM `users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$shadowUsername]);
        $uid = $stmt->fetchColumn();

        if (!$uid) {
            $uid = $this->userModel->create([
                'username' => $shadowUsername,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'email' => "{$shadowUsername}@telegram.bot",
                'money' => 0,
                'level' => 0,
                'bannd' => 0,
            ]);
        }

        if ($uid) {
            $this->linkModel->linkUser((int) $uid, $telegramId, $username, $firstName);
        }
    }

    /**
     * Cập nhật last_active + tạo Shadow Account nếu chưa có.
     */
    private function upsertTelegramUser(array $from): void
    {
        $telegramId = (int) $from['id'];
        $username = $from['username'] ?? null;
        $firstName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));

        if (!$this->linkModel->updateLastActive($telegramId, $username, $firstName)) {
            $this->ensureShadowAccount($telegramId, $username, $firstName);
        }
    }

    // =========================================================
    //  Rate Limiting — File-based (persistent across requests)
    // =========================================================

    private function checkUserRateLimit(int $telegramId, array $update): array
    {
        $windowSec = TelegramConfig::RATE_LIMIT_WINDOW;
        $maxPoints = max(10, TelegramConfig::rateLimit() * 2);
        $actionInfo = $this->resolveRateAction($update);
        $weight = max(1, (int) ($actionInfo['weight'] ?? 1));
        $actionName = (string) ($actionInfo['name'] ?? 'request');

        $dir = TelegramConfig::rateDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $file = $dir . '/' . md5("user_weighted_{$telegramId}") . '.json';
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $windowStart = $now - $windowSec;

        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return ['allowed' => true, 'retry_after' => 0, 'action' => $actionName];
        }

        $allowed = true;
        $retryAfter = 0;

        try {
            if (!@flock($fp, LOCK_EX)) {
                return ['allowed' => true, 'retry_after' => 0, 'action' => $actionName];
            }

            rewind($fp);
            $raw = stream_get_contents($fp);
            $entries = [];
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        $ts = (int) ($entry['ts'] ?? 0);
                        $w = max(1, (int) ($entry['w'] ?? 1));
                        if ($ts > $windowStart) {
                            $entries[] = ['ts' => $ts, 'w' => $w];
                        }
                    }
                }
            }

            $currentPoints = 0;
            foreach ($entries as $entry) {
                $currentPoints += (int) $entry['w'];
            }

            if (($currentPoints + $weight) > $maxPoints) {
                $allowed = false;
                $needToFree = ($currentPoints + $weight) - $maxPoints;
                usort($entries, static fn(array $a, array $b): int => ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0));

                $freed = 0;
                foreach ($entries as $entry) {
                    $freed += (int) ($entry['w'] ?? 1);
                    $entryTs = (int) ($entry['ts'] ?? $now);
                    $candidate = ($entryTs + $windowSec) - $now;
                    if ($freed >= $needToFree) {
                        $retryAfter = max(1, $candidate);
                        break;
                    }
                }
                if ($retryAfter <= 0) {
                    $retryAfter = max(1, $windowSec);
                }
            } else {
                $entries[] = ['ts' => $now, 'w' => $weight];
            }

            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($entries));
            fflush($fp);
            @flock($fp, LOCK_UN);
        } finally {
            @fclose($fp);
        }

        return ['allowed' => $allowed, 'retry_after' => $retryAfter, 'action' => $actionName];
    }

    /** Cooldown check — Trả về true nếu ngoài cooldown (được phép) */
    private function checkAndSetCooldown(string $key, int $seconds): bool
    {
        return $this->getCooldownRemaining($key, $seconds) === 0;
    }

    private function getCooldownRemaining(string $key, int $seconds): int
    {
        $dir = TelegramConfig::cooldownDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . '/' . md5($key) . '.ts';
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $seconds = max(1, (int) $seconds);

        if (file_exists($file)) {
            $last = (int) @file_get_contents($file);
            $elapsed = $now - $last;
            if ($elapsed < $seconds) {
                return $seconds - $elapsed;
            }
        }

        @file_put_contents($file, (string) $now, LOCK_EX);
        return 0;
    }

    private function isDuplicateOrOldUpdate(array $update): bool
    {
        $updateId = (int) ($update['update_id'] ?? 0);
        if ($updateId <= 0)
            return false;

        $dir = TelegramConfig::rateDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . DIRECTORY_SEPARATOR . 'tg_last_update_id.txt';
        $fp = @fopen($file, 'c+');
        if (!$fp)
            return false;

        try {
            if (!@flock($fp, LOCK_EX))
                return false;

            rewind($fp);
            $raw = trim((string) stream_get_contents($fp));
            $last = ($raw !== '' && is_numeric($raw)) ? (int) $raw : 0;

            if ($updateId <= $last) {
                @flock($fp, LOCK_UN);
                return true;
            }

            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, (string) $updateId);
            fflush($fp);
            @flock($fp, LOCK_UN);
            return false;
        } finally {
            @fclose($fp);
        }
    }

    private function resolveRateAction(array $update): array
    {
        if (isset($update['callback_query'])) {
            $data = (string) ($update['callback_query']['data'] ?? '');
            return $this->resolveCallbackRateAction($data);
        }

        if (isset($update['message'])) {
            $message = (array) $update['message'];
            $text = trim((string) ($message['text'] ?? ''));
            $telegramId = (int) ($message['from']['id'] ?? 0);

            if ($text !== '' && str_starts_with($text, '/')) {
                $cmd = strtolower(explode('@', explode(' ', $text)[0])[0]);
                return $this->resolveCommandRateAction($cmd);
            }

            if ($telegramId > 0) {
                if ($this->isPurchaseInputMode($telegramId) || $this->isDepositInputMode($telegramId) || $this->getBinanceSession($telegramId)) {
                    return ['name' => 'input_flow', 'weight' => 2];
                }
            }

            return ['name' => 'message_text', 'weight' => 1];
        }

        return ['name' => 'unknown', 'weight' => 1];
    }

    private function resolveCallbackRateAction(string $data): array
    {
        if (preg_match('/^do_buy_\d+_\d+$/', $data))
            return ['name' => 'do_buy', 'weight' => 4];
        if (preg_match('/^order_(pay_bank|pay_binance|check|cancel)_\d+$/', $data))
            return ['name' => 'order_payment', 'weight' => 3];
        if (preg_match('/^buy_gift_\d+_\d+$/', $data))
            return ['name' => 'buy_gift', 'weight' => 3];
        if (preg_match('/^buy_\d+_\d+$/', $data))
            return ['name' => 'buy', 'weight' => 3];
        if (str_starts_with($data, 'prod_') || str_starts_with($data, 'cat_') || $data === 'shop')
            return ['name' => 'browse_shop', 'weight' => 1];
        if ($data === 'binance_start' || str_starts_with($data, 'bin_amount_') || str_starts_with($data, 'bin_check_'))
            return ['name' => 'binance_action', 'weight' => 2];
        if (in_array($data, ['wallet', 'orders', 'deposit', 'deposit_menu'], true))
            return ['name' => 'account_action', 'weight' => 2];
        if (in_array($data, ['menu', 'back', 'help', 'link', 'unlink'], true) || str_starts_with($data, 'do_unlink_'))
            return ['name' => 'menu_action', 'weight' => 1];
        return ['name' => 'callback', 'weight' => 1];
    }

    private function resolveCommandRateAction(string $command): array
    {
        if (in_array($command, ['/broadcast', '/maintenance', '/setbank'], true))
            return ['name' => ltrim($command, '/'), 'weight' => 5];
        if (in_array($command, ['/deposit', '/bank', '/binance', '/orders', '/wallet'], true))
            return ['name' => ltrim($command, '/'), 'weight' => 3];
        if (in_array($command, ['/shop', '/stats'], true))
            return ['name' => ltrim($command, '/'), 'weight' => 2];
        return ['name' => ltrim($command, '/'), 'weight' => 1];
    }

    /** Sliding window rate limiter (file-based) */
    private function fileRateCheck(string $key, int $max, int $windowSec): bool
    {
        $dir = TelegramConfig::rateDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . '/' . md5($key) . '.json';
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $windowStart = $now - $windowSec;

        $timestamps = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        $timestamps = array_values(array_filter($timestamps, static fn(int $ts) => $ts > $windowStart));

        if (count($timestamps) >= $max)
            return false;

        $timestamps[] = $now;
        @file_put_contents($file, json_encode($timestamps), LOCK_EX);
        return true;
    }

    // =========================================================
    //  Logging
    // =========================================================

    /**
     * Ghi log hoạt động Bot vào bảng telegram_logs (Terminal view).
     * Silent fail — không bao giờ ném exception.
     */
    private function writeLog(
        string $message,
        string $level = 'INFO',
        string $type = 'INCOMING',
        string $category = 'GENERAL',
        $data = null
    ): void {
        try {
            // 1. Log to internal terminal (telegram_logs)
            $logModel = new TelegramLog();
            $logModel->log($message, $level, $type, $category, $data);

            // 2. Log to system journal (system_logs) for admin visibility
            if (class_exists('Logger')) {
                $payload = is_array($data) ? $data : ['raw_data' => $data];
                $payload['bot_category'] = $category;
                $payload['bot_type'] = $type;

                // Sync severity levels
                switch (strtoupper($level)) {
                    case 'WARN':
                    case 'WARNING':
                        Logger::warning('TelegramBot', $category, $message, $payload);
                        break;
                    case 'ERROR':
                    case 'DANGER':
                    case 'CRITICAL':
                        Logger::danger('TelegramBot', $category, $message, $payload);
                        break;
                    default:
                        Logger::info('TelegramBot', $category, $message, $payload);
                        break;
                }
            }
        } catch (Throwable $e) {
            error_log('[TelegramBotService] writeLog failed: ' . $e->getMessage());
        }
    }
}

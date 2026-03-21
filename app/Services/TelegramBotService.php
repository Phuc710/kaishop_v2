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
        require_once __DIR__ . '/Locales/TelegramBotLocaleInterface.php';
        require_once __DIR__ . '/Locales/TelegramBotLocaleVi.php';
        require_once __DIR__ . '/Locales/TelegramBotLocaleEn.php';

        $localeClass = $this->isTelegramEnglish($telegramId) ? new TelegramBotLocaleEn() : new TelegramBotLocaleVi();
        return $localeClass->getMessage($key, $vars);
    }

    private function tgChoice(int $telegramId, string $vi, string $en): string
    {
        return $this->isTelegramEnglish($telegramId) ? $en : $vi;
    }

    private function tgRuntimeMessage(int $telegramId, string $message): string
    {
        $message = trim($message);
        if ($message === '' || !$this->isTelegramEnglish($telegramId)) {
            return $message;
        }

        static $runtimeMap = [
        'Bạn chưa đăng nhập. /start and try again.' => 'Could not identify your Telegram account. Please send /start and try again.',
        'Tài khoản đang bị khóa.' => 'Your account is locked.',
        'Sản phẩm không khả dụng.' => 'This product is not available.',
        'Giá sản phẩm không hợp lệ.' => 'Invalid product price.',
        'Số lượng mua nhỏ hơn mức tối thiểu.' => 'Quantity is below the minimum allowed.',
        'Vui lòng nhập thông tin yêu cầu trước khi mua.' => 'Please enter the required information before purchasing.',
        'Vui lòng nhập thông tin yêu cầu trước khi tiếp tục.' => 'Please enter the required information before continuing.',
        'Sản phẩm tạm hết hàng.' => 'This product is temporarily out of stock.',
        'Số lượng mua vượt quá tồn kho hoặc giới hạn tối đa.' => 'Quantity exceeds stock or the maximum allowed limit.',
        'Số lượng mua vượt quá giới hạn tối đa.' => 'Quantity exceeds the maximum allowed limit.',
        'Số dư không đủ để thanh toán.' => 'Your balance is insufficient for payment.',
        'Loại sản phẩm này chỉ được mua tối đa 1 cái mỗi đơn hàng.' => 'This product type can only be purchased once per order.',
        'Loại sản phẩm này chỉ hỗ trợ mua tối đa 1 sản phẩm mỗi đơn.' => 'This product type supports only one item per order.',
        'Sản phẩm Source Link chưa được cấu hình link giao.' => 'The Source Link product has not been configured with a delivery link.',
        'Sản phẩm Source Link chưa được cấu hình nội dung giao.' => 'The Source Link product has not been configured with delivery content.',
        'Không thể xử lý đơn hàng lúc này. Vui lòng thử lại sau.' => 'Could not process the order right now. Please try again later.',
        'Không thể tạo đơn hàng lúc này.' => 'Could not create the order right now.',
        'Phương thức thanh toán không hợp lệ.' => 'Invalid payment method.',
        'Không tìm thấy đơn hàng.' => 'Order not found.',
        'Đơn hàng này không còn chờ thanh toán.' => 'This order is no longer pending payment.',
        'Đơn hàng đã hết hạn thanh toán.' => 'This order has expired.',
        'Không tìm thấy tài khoản người dùng.' => 'User account not found.',
        'Giá trị đơn hàng không hợp lệ.' => 'Invalid order amount.',
        'Deposit không gắn với đơn hàng nào.' => 'This deposit is not linked to any order.',
        'Đơn hàng không tồn tại.' => 'Order does not exist.',
        'Đơn hàng đã được thanh toán trước đó.' => 'This order has already been paid.',
        'Đơn hàng không còn ở trạng thái chờ thanh toán.' => 'This order is no longer in pending-payment status.',
        'Sản phẩm không còn tồn tại.' => 'The product no longer exists.',
        'Đã xác nhận thanh toán đơn hàng.' => 'Order payment confirmed.',
        'Không thể xác nhận thanh toán lúc này.' => 'Could not confirm payment right now.',
        'Đơn hàng không hợp lệ.' => 'Invalid order.',
        'Bạn không có quyền thao tác đơn này.' => 'You do not have permission to modify this order.',
        'Đơn hàng này không còn chờ thanh toán.' => 'This order is no longer pending payment.',
        'Đơn hàng đã hết hạn.' => 'The order has expired.',
        'Đã hủy đơn hàng.' => 'Order cancelled.',
        'Không thể hủy đơn lúc này.' => 'Could not cancel the order right now.',
        'UID Binance không hợp lệ.' => 'Invalid Binance UID.',
        'Binance Pay chưa sẵn sàng.' => 'Binance Pay is not ready yet.',
        'Binance Pay chưa được cấu hình.' => 'Binance Pay is not configured.',
        'Không thể tạo phiên thanh toán Binance.' => 'Could not create a Binance payment session.',
        'Hệ thống mã giảm giá chưa sẵn sàng.' => 'The discount-code system is not ready yet.',
        'Mã giảm giá không tồn tại.' => 'Discount code does not exist.',
        'Mã giảm giá đã bị tắt.' => 'Discount code has been disabled.',
        'Mã giảm giá đã hết lượt sử dụng.' => 'Discount code has no remaining uses.',
        'Mã giảm giá đã hết hạn.' => 'Discount code has expired.',
        'Mã giảm giá không áp dụng cho sản phẩm này.' => 'This discount code does not apply to this product.',
        'Đơn hàng chưa đạt mức tối thiểu để dùng mã giảm giá.' => 'The order does not meet the minimum amount for this discount code.',
        'Đơn hàng vượt quá giá trị áp dụng của mã giảm giá.' => 'The order exceeds the applicable amount for this discount code.',
            'Mã giảm giá vừa hết lượt. Vui lòng thử lại.' => 'The discount code has just run out. Please try again.',
            'User not found' => 'Could not identify your Telegram account. Please send /start and try again.',
        ];

        if (isset($runtimeMap[$message])) {
            return $runtimeMap[$message];
        }

        if (preg_match('/^Bạn còn thiếu (.+)$/u', $message, $m)) {
            return 'You still need ' . $m[1] . '.';
        }

        return $message;
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
            ['command' => 'orders', 'description' => 'Lịch sử 5 đơn hàng gần nhất'],
            ['command' => 'help', 'description' => 'Danh sách lệnh trợ giúp'],
        ];

        $englishCommands = [
            ['command' => 'start', 'description' => 'Open bot'],
            ['command' => 'menu', 'description' => 'Open quick menu'],
            ['command' => 'shop', 'description' => 'Browse products'],
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
                    $message = $this->tgChoice(
                        $telegramId,
                        "⚠️ <b>Bạn đang thao tác quá nhanh!</b>\nVui lòng đợi <b>{$retryAfter} giây</b> trước khi thực hiện hành động tiếp theo.",
                        "⚠️ <b>You're acting too quickly.</b>\nPlease wait <b>{$retryAfter} seconds</b> before your next action."
                    );
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
        $inFlow = $this->isPurchaseInputMode($telegramId);
        if ($isCommand || $inFlow) {
            $this->writeLog("[MSG] {$fromName} ({$telegramId}): {$text}", 'INFO', 'INCOMING', 'MESSAGE');
        }

        if (TelegramConfig::isMaintenanceEnabled() && !TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo(
                $chatId,
                $this->tgChoice(
                    $telegramId,
                    TelegramConfig::maintenanceMessage(),
                    '🛠 The system is under maintenance. Please try again later.'
                )
            );
            return;
        }

        if (!str_starts_with($text, '/')) {

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
            '/shop' => $this->cmdShop($chatId, $telegramId),
            '/orders' => $this->cmdOrders($chatId, $telegramId),
            '/help' => $this->cmdHelp($chatId, $telegramId),
            // Admin
            '/stats' => $this->cmdStats($chatId, $telegramId),
            '/broadcast' => $this->cmdBroadcast($chatId, $telegramId, $args),
            '/maintenance' => $this->cmdMaintenance($chatId, $telegramId, $args),
            '/setbank' => $this->cmdSetBank($chatId, $telegramId, $args),
            default => $this->telegram->sendTo($chatId, $this->tgText($telegramId, 'invalid_command')),
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
            $this->cmdShop($chatId, $telegramId);
            return;
        }
        if ($text === 'orders' || $text === 'đơn hàng' || $text === 'don hang') {
            $this->cmdOrders($chatId, $telegramId);
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
            $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'Hệ thống đang bảo trì, vui lòng thử lại sau.', 'The system is under maintenance. Please try again later.'), true);
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



        $parts = explode('_', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'shop':
                if ($messageId > 0)
                    $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '✅ Đã cập nhật.', '✅ Updated.'), false);
                $this->cmdShop($chatId, $telegramId, $messageId);
                return;
            case 'cat':
                $this->cbCategory($chatId, $telegramId, (int) ($parts[1] ?? 0), $messageId, $callbackId);
                return;
            case 'prod':
                if ($messageId > 0)
                    $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '✅ Đã cập nhật.', '✅ Updated.'), false);
                $this->cbProduct($chatId, $telegramId, (int) ($parts[1] ?? 0), $messageId);
                return;
            case 'buy':
                $this->cbBuyConfirm($chatId, $telegramId, (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 1), null, $messageId);
                break;
            case 'orders':
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '✅ Đã cập nhật.', '✅ Updated.'), false);
                $this->cmdOrders($chatId, $telegramId, $messageId);
                return;
            case 'order':
                if (($parts[1] ?? '') === 'check') {
                    $this->cbOrderCheck($chatId, $telegramId, (int) ($parts[2] ?? 0), $messageId, $callbackId);
                } elseif (($parts[1] ?? '') === 'cancel') {
                    $this->cbOrderCancel($chatId, $telegramId, (int) ($parts[2] ?? 0), $messageId, $callbackId);
                }
                return;
            case 'menu':
            case 'back':

                $fromName = trim(($query['from']['first_name'] ?? '') . ' ' . ($query['from']['last_name'] ?? ''));
                $this->showMainMenu($chatId, $telegramId, $fromName, true, $messageId);
                break;
            case 'help':
                $this->cmdHelp($chatId, $telegramId, $messageId);
                break;
            case 'stats':
                $this->cmdStats($chatId, $telegramId);
                break;
        }

        $this->telegram->answerCallbackQuery($callbackId);
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
                ['text' => '[ 🇻🇳 Tiếng Việt ]', 'callback_data' => 'lang_vi'],
                ['text' => '[ 🇺🇸 English ]', 'callback_data' => 'lang_en'],
            ],
        ]);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $message, $markup, ['disable_web_page_preview' => true]);
            return;
        }

        $this->telegram->sendTo($chatId, $message, ['reply_markup' => $markup, 'disable_web_page_preview' => true]);
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

            $msg = $this->tgText($telegramId, 'greeting', [
                'name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
                'site' => htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8'),
                'domain' => htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8'),
            ]);
        } else {
            $msg = $this->tgText($telegramId, 'main_prompt');
        }

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup, ['disable_web_page_preview' => true]);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup, 'disable_web_page_preview' => true]);
        }
    }

    /** @return array<int,array<int,array<string,string>>> */
    private function buildMainInlineRows(int $telegramId, string $username = ''): array
    {
        $inlineRows = [
            [
                ['text' => $this->tgText($telegramId, 'menu_shop'), 'callback_data' => 'shop'],
                ['text' => $this->tgText($telegramId, 'menu_orders'), 'callback_data' => 'orders'],
            ],
            [
                ['text' => $this->tgText($telegramId, 'menu_help'), 'callback_data' => 'help'],
                ['text' => $this->tgText($telegramId, 'menu_language'), 'callback_data' => 'change_lang'],
            ],
        ];

        if (TelegramConfig::isAdmin($telegramId)) {
            $inlineRows[] = [
                ['text' => $this->tgText($telegramId, 'menu_admin'), 'callback_data' => 'stats_admin'],
            ];
        }



        return $inlineRows;
    }

    /** @return array<string,string> */
    private function backHomeButton(int $telegramId = 0): array
    {
        if ($telegramId > 0) {
            return ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'back_home'];
        }

        return ['text' => '◀️ Back', 'callback_data' => 'back_home'];
    }

    /** @return array<string,mixed> */
    private function buildPayNowBackKeyboard(string $payUrl = '', string $depositCode = '', int $telegramId = 0): array
    {
        $rows = [];
        $url = trim($payUrl);
        if ($url !== '' && str_starts_with($url, 'http')) {
            $rows[] = [['text' => $this->tgChoice($telegramId, '💳 Thanh toán ngay', '💳 Pay Now'), 'url' => $url]];
        } else {
            $rows[] = [['text' => $this->tgChoice($telegramId, '💳 Thanh toán ngay', '💳 Pay Now'), 'callback_data' => 'deposit_menu']];
        }

        if ($depositCode !== '') {
            $rows[] = [['text' => $this->tgChoice($telegramId, '❌ Hủy giao dịch', '❌ Cancel Payment'), 'callback_data' => 'cancel_dep_' . $depositCode]];
        } else {
            $rows[] = [['text' => $this->tgChoice($telegramId, '❌ Hủy giao dịch', '❌ Cancel Payment'), 'callback_data' => 'deposit_menu']];
        }

        return TelegramService::buildInlineKeyboard($rows);
    }

    /** /shop — Danh mục sản phẩm */
    private function cmdShop(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $categories = $this->categoryModel->getActive();
        if (empty($categories)) {
            $emptyMsg = $this->tgChoice($telegramId, '🛍️ Hiện hệ thống chưa có danh mục sản phẩm nào.', '🛍️ There are no product categories yet.');
            $emptyMarkup = TelegramService::buildInlineKeyboard([
                [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'back_home']],
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
        $rows[] = [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'back_home']];

        $msg = $this->tgChoice($telegramId, "🛍️ <b>TẤT CẢ DANH MỤC</b>\n\n👇 Vui lòng chọn danh mục:", "🛍️ <b>ALL CATEGORIES</b>\n\n👇 Please choose a category:");
        $markup = TelegramService::buildInlineKeyboard($rows);

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
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, "📦 Bạn chưa có đơn hàng nào.\n\n👉 Vui lòng ghé thăm cửa hàng để mua sản phẩm!", "📦 You don't have any orders yet.\n\n👉 Please visit the shop to buy a product!"), [
                'reply_markup' => TelegramService::buildInlineKeyboard([[$this->backHomeButton($telegramId)]]),
            ]);
            return;
        }

        $msg = $this->tgChoice($telegramId, "📦 <b>LỊCH SỬ ĐƠN HÀNG</b>\n<i>(Hiển thị 5 đơn hàng gần nhất)</i>\n\n", "📦 <b>ORDER HISTORY</b>\n<i>(Showing latest 5 orders)</i>\n\n");
        foreach ($orders as $o) {
            $statusIcon = ((string) ($o['status'] ?? '') === 'completed') ? '✅' : '⏳';
            $orderCode = htmlspecialchars((string) ($o['order_code_short'] ?? $o['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
            $productName = htmlspecialchars((string) ($o['product_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $price = number_format((int) ($o['price'] ?? 0)) . "đ";
            $quantity = max(1, (int) ($o['quantity'] ?? 1));

            $rawContent = trim((string) ($o['stock_content_plain'] ?? ''));
            if ($rawContent === '') {
                $rawContent = ((string) ($o['status'] ?? '') === 'completed')
                    ? $this->tgChoice($telegramId, 'Đơn hoàn tất nhưng chưa có nội dung bàn giao.', 'The order is completed but no delivery content is available yet.')
                    : $this->tgChoice($telegramId, 'Đơn đang xử lý, nội dung sẽ được cập nhật sau.', 'The order is being processed. Content will be updated later.');
            }
            $content = $this->formatOrderContentForTelegram($rawContent);

            $msg .= $this->tgChoice($telegramId, "{$statusIcon} Mã đơn: <code>{$orderCode}</code>\n", "{$statusIcon} Order ID: <code>{$orderCode}</code>\n");
            $msg .= $this->tgChoice($telegramId, "📦 Tên SP: <b>{$productName}</b>\n", "📦 Product: <b>{$productName}</b>\n");
            $msg .= $this->tgChoice($telegramId, "💰 Giá: <b>{$price}</b>\n", "💰 Price: <b>{$price}</b>\n");
            $msg .= $this->tgChoice($telegramId, "🔢 SL: <b>{$quantity}</b>\n", "🔢 Qty: <b>{$quantity}</b>\n");
            $msg .= $this->tgChoice($telegramId, "🔑 Nội dung:\n<code>{$content}</code>\n", "🔑 Content:\n<code>{$content}</code>\n");
            $msg .= "━━━━━━━━━━━━━━\n";
        }

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => $this->tgChoice($telegramId, '🔄 Cập nhật', '🔄 Refresh'), 'callback_data' => 'orders'],
                $this->backHomeButton($telegramId),
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
            return 'No content yet.';

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($clean, 'UTF-8') > $limit) {
                $clean = mb_substr($clean, 0, $limit, 'UTF-8') . "\n... (truncated)";
            }
        } elseif (strlen($clean) > $limit) {
            $clean = substr($clean, 0, $limit) . "\n... (truncated)";
        }

        return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    }

    // =========================================================
    //  Help Command
    // =========================================================

    /** /help — Danh sách lệnh */
    private function cmdHelp(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $isAdmin = TelegramConfig::isAdmin($telegramId);

        $msg = $this->tgText($telegramId, 'help_title') . "\n\n";
        $msg .= $this->tgText($telegramId, 'help_shop') . "\n";
        $msg .= $this->tgText($telegramId, 'help_orders') . "\n";
        $msg .= $this->tgText($telegramId, 'help_menu') . "\n";
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
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '❌ Không thể khởi tạo tài khoản. Vui lòng thử lại.', '❌ Could not initialize your account. Please try again.'));
            return null;
        }

        $user = $this->userModel->findById($link['user_id']);
        if (!$user) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⚠️ Lỗi: Không tìm thấy tài khoản hệ thống.', '⚠️ Error: System account not found.'));
            return null;
        }

        if ((int) ($user['bannd'] ?? 0) === 1) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '🚫 Tài khoản của bạn đã bị khóa. Liên hệ hỗ trợ nếu có nhầm lẫn.', '🚫 Your account has been locked. Contact support if this is a mistake.'));
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
                if ($this->isPurchaseInputMode($telegramId)) {
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
        if (in_array($data, ['orders'], true))
            return ['name' => 'account_action', 'weight' => 2];
        if (in_array($data, ['menu', 'back', 'help'], true))
            return ['name' => 'menu_action', 'weight' => 1];
        return ['name' => 'callback', 'weight' => 1];
    }

    private function resolveCommandRateAction(string $command): array
    {
        if (in_array($command, ['/broadcast', '/maintenance', '/setbank'], true))
            return ['name' => ltrim($command, '/'), 'weight' => 5];
        if (in_array($command, ['/orders'], true))
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

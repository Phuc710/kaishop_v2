<?php

/**
 * TelegramBotService - KaiShop Bot Core Logic
 *
 * Architecture:
 *  - Standalone Flow: User Telegram mới để tự động tạo Shadow Account để mua hàng ngay
 *  - Role-Based Menus: /start và /menu hiển thị khác nhau cho User/Admin
 *  - Shared Backend: 100% dùng chung Model/Service với Web
 *  - File-based Rate Limit: tồn tại giữa các webhook request độc lập
 *  - Purchase Cooldown: chặn double-click mua hàng
 *  - Deposit TTL 5 phút: đồng bộ với Web, SePay webhook tự từ chối nếu quá hạn
 *
 * @see TelegramConfig - tất cả constants và getters tập trung
 * @see TelegramService - API wrapper cấp thấp (sendTo, buildInlineKeyboard)
 */
class TelegramBotService
{
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
    //  Entry Point
    // =========================================================

    public function processUpdate(array $update): void
    {
        $telegramId = (int) ($update['message']['from']['id']
            ?? $update['callback_query']['from']['id']
            ?? 0);

        if ($telegramId > 0 && !$this->checkUserRateLimit($telegramId)) {
            // Chỉ gửi thông báo nếu chưa gửi trong vòng 5 giây qua
            if ($this->checkAndSetCooldown("rl_warn_{$telegramId}", 5)) {
                $this->telegram->sendTo(
                    (string) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? ''),
                    "⚠️ <b>Vui lòng thao tác chậm lại!</b>\nHệ thống phát hiện tần suất gửi lệnh quá nhanh. Vui lòng đợi vài giây và thử lại."
                );
            }
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

        // Cập nhật last_active + tạo shadow account nếu chưa có
        $this->upsertTelegramUser($message['from']);

        if (!str_starts_with($text, '/')) {
            if ($this->handleDepositAmountInput($chatId, $telegramId, $text)) {
                return;
            }
            if ($this->handlePurchaseInput($chatId, $telegramId, $text)) {
                return;
            }
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
            '/wallet' => $this->cmdWallet($chatId, $telegramId),
            '/deposit' => $this->cmdDeposit($chatId, $telegramId, $args),
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

        $this->upsertTelegramUser($query['from']);

        $parts = explode('_', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'shop':
                $this->cmdShop($chatId);
                break;
            case 'cat':
                $this->cbCategory($chatId, (int) ($parts[1] ?? 0), (int) ($query['message']['message_id'] ?? 0));
                break;
            case 'prod':
                $this->cbProduct($chatId, (int) ($parts[1] ?? 0));
                break;
            case 'buy':
                // buy_{prodId}_{qty}
                $this->cbBuyConfirm($chatId, $telegramId, (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 1));
                break;
            case 'do':
                // do_buy_{prodId}_{qty}
                $this->cbDoBuy($chatId, $telegramId, (int) ($parts[2] ?? 0), (int) ($parts[3] ?? 1));
                break;
            case 'buy_gift':
                // buy_gift_{prodId}_{qty}
                $this->startGiftCodeInputMode($chatId, $telegramId, (int) ($parts[2] ?? 0), (int) ($parts[3] ?? 1));
                break;
            case 'wallet':
                $this->cmdWallet($chatId, $telegramId);
                break;
            case 'deposit':
                $this->startDepositInputMode($chatId, $telegramId);
                break;
            case 'orders':
                $this->cmdOrders($chatId, $telegramId, (int) ($query['message']['message_id'] ?? 0));
                break;
            case 'unlink':
                $this->cmdUnlink($chatId, $telegramId);
                break;
            case 'do_unlink':
                // do_unlink_{bot/web}
                $this->cbDoUnlink($chatId, $telegramId, (string) ($parts[1] ?? 'web'), (int) ($query['message']['message_id'] ?? 0));
                break;
            case 'menu':
                $this->cmdMenu($chatId, $telegramId);
                break;
            case 'back':
                $this->clearDepositInputMode($telegramId);
                $fromName = trim(($query['from']['first_name'] ?? '') . ' ' . ($query['from']['last_name'] ?? ''));
                $this->showMainMenu($chatId, $telegramId, $fromName, true);
                break;
            case 'help':
                $this->cmdHelp($chatId, $telegramId);
                break;
            case 'link':
                $this->cmdLink($chatId, $telegramId, [], $query['from'] ?? []);
                break;
            case 'stats':
                $this->cmdStats($chatId, $telegramId);
                break;
            // Ignore unknown callback
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    // =========================================================
    //  USER Commands
    // =========================================================

    /**
     * /start — Chào mừng + Hướng dẫn sử dụng
     */
    private function cmdStart(string $chatId, int $telegramId, string $name): void
    {
        $this->showMainMenu($chatId, $telegramId, $name, true);
    }

    /**
     * /menu — Menu bàn phím + inline theo vai trò
     */
    private function cmdMenu(string $chatId, int $telegramId): void
    {
        $this->showMainMenu($chatId, $telegramId, '', false);
    }

    /**
     * Main menu renderer (supports greeting mode for /start and Back action).
     */
    private function showMainMenu(string $chatId, int $telegramId, string $name = '', bool $withGreeting = false): void
    {
        $siteName = get_setting('ten_web', 'KaiShop');
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $inlineRows = $this->buildMainInlineRows($telegramId, (string) ($user['username'] ?? ''));

        if ($withGreeting) {
            $username = trim((string) ($user['username'] ?? ''));
            $displayName = trim($name) !== '' ? trim($name) : $username;
            if ($displayName === '') {
                $displayName = 'bạn';
            }

            $money = number_format((int) ($user['money'] ?? 0)) . "đ";

            $msg = "👋 Xin chào <b>" . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . "</b>!\n";
            $msg .= "Chào mừng bạn đến với <b>" . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . "</b> 🤖.\n\n";
            $msg .= "👤 Tài khoản: <b>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</b>\n";
            $msg .= "💵 Số dư: <b>" . $money . "</b>\n\n";
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "👇 Chọn chức năng bên dưới để bắt đầu";

            $this->telegram->sendTo($chatId, $msg, [
                'reply_markup' => TelegramService::buildInlineKeyboard($inlineRows),
            ]);
            return;
        }

        $this->telegram->sendTo($chatId, "👇 <b>Chọn chức năng bên dưới để bắt đầu:</b>", [
            'reply_markup' => TelegramService::buildInlineKeyboard($inlineRows),
        ]);
    }

    /**
     * @return array<int,array<int,array<string,string>>>
     */
    private function buildMainInlineRows(int $telegramId, string $username = ''): array
    {
        $isLinked = $username !== '' && !str_starts_with($username, 'tg_');
        $linkText = $isLinked ? '🔗 Hủy liên kết' : '🔗 Liên kết Web';
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

        if (TelegramConfig::isAdmin($telegramId)) {
            $inlineRows[] = [
                ['text' => '📊 Thống kê Admin', 'callback_data' => 'stats_admin'],
            ];
        }

        return $inlineRows;
    }

    /**
     * @return array<int,array<int,string>>
     */
    private function buildMainReplyRows(): array
    {
        return [
            ['Shop', 'Vi'],
            ['Nap tien', 'Don hang'],
            ['Lien ket Web', 'Tro giup'],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function backHomeButton(): array
    {
        return ['text' => '◀️ Quay lại', 'callback_data' => 'back_home'];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayNowBackKeyboard(string $payUrl = ''): array
    {
        $rows = [];
        $url = trim($payUrl);
        if ($url !== '' && str_starts_with($url, 'http')) {
            $rows[] = [['text' => '💳 Thanh toán ngay', 'url' => $url]];
        } else {
            $rows[] = [['text' => '💳 Thanh toán ngay', 'callback_data' => 'deposit_menu']];
        }
        $rows[] = [$this->backHomeButton()];
        return TelegramService::buildInlineKeyboard($rows);
    }

    /**
     * /shop — Danh mục sản phẩm
     */
    private function cmdShop(string $chatId): void
    {
        $categories = $this->categoryModel->getActive();
        if (empty($categories)) {
            $this->telegram->sendTo($chatId, "🛍️ Hiện hệ thống chưa có danh mục sản phẩm nào.");
            return;
        }

        $rows = [];
        foreach ($categories as $cat) {
            $rows[] = [['text' => '📦 ' . $cat['name'], 'callback_data' => 'cat_' . $cat['id']]];
        }

        $rows[] = [['text' => '⬅️ Quay lại menu chính', 'callback_data' => 'menu']];

        $this->telegram->sendTo($chatId, "🛍️ <b>TẤT CẢ DANH MỤC</b>\n\n👇 Vui lòng chọn danh mục:", [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * /wallet — Xem thông tin ví
     */
    private function cmdWallet(string $chatId, int $telegramId): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $msg = "💰 <b>THÔNG TIN VÍ CỦA BẠN</b>\n\n";
        $msg .= "👤 Tài khoản: <b>" . htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') . "</b>\n";
        $msg .= "💵 Số dư: <b>" . number_format((int) ($user['money'] ?? 0)) . "đ</b>\n\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "👇 Cần nạp thêm? Chọn Nạp tiền ngay!.";

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    ['text' => '💳 Nạp tiền ngay', 'callback_data' => 'deposit_menu'],
                    $this->backHomeButton(),
                ]
            ]),
        ]);
    }

    private function handleDepositAmountInput(string $chatId, int $telegramId, string $text): bool
    {
        if (!$this->isDepositInputMode($telegramId)) {
            return false;
        }

        $text = trim($text);
        if ($text === '') {
            $this->telegram->sendTo($chatId, "⚠️ Vui lòng nhập số tiền cần nạp (Ví dụ: <code>50000</code>).");
            return true;
        }

        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower(trim($text));

        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'cancel', 'quay lại', 'quay lai'], true)) {
            $this->clearDepositInputMode($telegramId);
            $this->telegram->sendTo($chatId, "✅ Đã hủy thao tác nạp tiền.");
            return true;
        }

        $amount = (int) preg_replace('/\D/', '', $text);
        if ($amount <= 0) {
            $this->telegram->sendTo($chatId, "⚠️ Số tiền không hợp lệ. Vui lòng nhập số, ví dụ: <code>50000</code>.");
            return true;
        }

        $this->clearDepositInputMode($telegramId);
        $this->cmdDeposit($chatId, $telegramId, [(string) $amount]);
        return true;
    }

    private function startDepositInputMode(string $chatId, int $telegramId): void
    {
        $this->setDepositInputMode($telegramId);
        $this->telegram->sendTo(
            $chatId,
            "💳 <b>NẠP TIỀN VÀO VÍ</b>\n\n👇 Vui lòng nhập số tiền bạn muốn nạp:\n\nẤn <code>Quay lại</code> để thoát.",
            [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [$this->backHomeButton()],
                ]),
            ]
        );
    }

    private function depositInputDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'deposit_input';
    }

    private function depositInputFile(int $telegramId): string
    {
        return $this->depositInputDir() . DIRECTORY_SEPARATOR . $telegramId . '.json';
    }

    private function setDepositInputMode(int $telegramId, int $ttl = 300): void
    {
        $dir = $this->depositInputDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $payload = [
            'created_at' => $now,
            'expires_at' => $now + max(60, $ttl),
        ];

        @file_put_contents($this->depositInputFile($telegramId), json_encode($payload), LOCK_EX);
    }

    private function clearDepositInputMode(int $telegramId): void
    {
        $file = $this->depositInputFile($telegramId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function isDepositInputMode(int $telegramId): bool
    {
        $file = $this->depositInputFile($telegramId);
        if (!is_file($file)) {
            return false;
        }

        $raw = @file_get_contents($file);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            @unlink($file);
            return false;
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $expiresAt = (int) ($data['expires_at'] ?? 0);
        if ($expiresAt <= 0 || $expiresAt < $now) {
            @unlink($file);
            return false;
        }

        return true;
    }

    // --- PURCHASE INPUT ---

    private function purchaseInputDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'purchase_input';
    }

    private function purchaseInputFile(int $telegramId): string
    {
        return $this->purchaseInputDir() . DIRECTORY_SEPARATOR . $telegramId . '.json';
    }

    private function isPurchaseInputMode(int $telegramId): bool
    {
        return is_file($this->purchaseInputFile($telegramId));
    }

    private function setPurchaseSession(int $telegramId, array $data): void
    {
        $dir = $this->purchaseInputDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $data['created_at'] = $this->timeService ? $this->timeService->nowTs() : time();
        @file_put_contents($this->purchaseInputFile($telegramId), json_encode($data), LOCK_EX);
    }

    private function getPurchaseSession(int $telegramId): ?array
    {
        $file = $this->purchaseInputFile($telegramId);
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if (!$raw)
            return null;
        $data = json_decode($raw, true);
        if (!$data)
            return null;

        // Check timeout (5 minutes = 300 seconds)
        $createdAt = (int) ($data['created_at'] ?? 0);
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        if ($createdAt > 0 && ($now - $createdAt) > 300) {
            $this->clearPurchaseSession($telegramId);
            return null;
        }

        return $data;
    }

    private function clearPurchaseSession(int $telegramId): void
    {
        $file = $this->purchaseInputFile($telegramId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function handlePurchaseInput(string $chatId, int $telegramId, string $text): bool
    {
        $file = $this->purchaseInputFile($telegramId);
        $wasInMode = is_file($file);

        $session = $this->getPurchaseSession($telegramId);
        if (!$session) {
            // Silently clear if existed but expired, and let other handlers process
            if ($wasInMode) {
                $this->clearPurchaseSession($telegramId);
            }
            return false;
        }

        $text = trim($text);
        if ($text === '') {
            return true;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'back', 'quay lại', 'quay lai'], true)) {
            $this->clearPurchaseSession($telegramId);
            $this->telegram->sendTo($chatId, "✅ Đã hủy thao tác mua hàng.");
            $this->cmdShop($chatId);
            return true;
        }

        $step = $session['step'] ?? '';
        $prodId = (int) ($session['prod_id'] ?? 0);
        $p = $this->productModel->find($prodId);
        if (!$p) {
            $this->clearPurchaseSession($telegramId);
            return false;
        }

        if ($step === 'qty') {
            $qty = (int) preg_replace('/\D/', '', $text);
            if ($qty <= 0) {
                $this->telegram->sendTo($chatId, "⚠️ Số lượng không hợp lệ. Vui lòng nhập số.");
                return true;
            }

            $session['qty'] = $qty;
            if ((int) ($p['requires_info'] ?? 0) === 1) {
                $session['step'] = 'info';
                $this->setPurchaseSession($telegramId, $session);
                $instr = trim((string) ($p['info_instructions'] ?? ''));
                $prompt = "📝 <b>NHẬP THÔNG TIN YÊU CẦU</b>\n\n";
                if ($instr !== '') {
                    $prompt .= "<i>" . htmlspecialchars($instr) . "</i>\n\n";
                }
                $prompt .= "👇 Vui lòng nhập nội dung để admin xử lý:";
                $this->telegram->sendTo($chatId, $prompt, [
                    'reply_markup' => TelegramService::buildInlineKeyboard([
                        [['text' => '❌ Hủy bỏ', 'callback_data' => 'shop']],
                    ]),
                ]);
            } else {
                $session['step'] = 'confirm';
                $this->setPurchaseSession($telegramId, $session);
                $this->cbBuyConfirm($chatId, $telegramId, $prodId, $qty);
            }
            return true;
        }

        if ($step === 'info') {
            $session['info'] = $text;
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $session);
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, (int) $session['qty'], $text);
            return true;
        }

        if ($step === 'gift') {
            $session['giftcode'] = strtoupper($text);
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $session);
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, (int) $session['qty'], $session['info'] ?? null);
            return true;
        }

        return false;
    }

    private function startGiftCodeInputMode(string $chatId, int $telegramId, int $prodId, int $qty): void
    {
        $session = $this->getPurchaseSession($telegramId);
        if (!$session) {
            $this->telegram->sendTo($chatId, "⏰ <b>Giao dịch hết hạn!</b>\nPhiên mua hàng của bạn đã quá 5 phút và tự động bị hủy. Vui lòng bắt đầu lại từ Cửa hàng.");
            $this->showMainMenu($chatId, $telegramId);
            return;
        }

        $session['step'] = 'gift';
        $this->setPurchaseSession($telegramId, $session);

        $this->telegram->sendTo($chatId, "🏷️ <b>NHẬP MÃ GIẢM GIÁ</b>\n\n👇 Vui lòng nhập mã giảm giá của bạn:", [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [['text' => '◀️ Quay lại', 'callback_data' => 'buy_' . $prodId . '_' . $qty]],
            ]),
        ]);
    }
    /**
     * /deposit <số_tiền> - Tạo mã chuyển khoản ngân hàng (TTL 5 phút)
     */
    private function cmdDeposit(string $chatId, int $telegramId, array $args): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $amount = (int) preg_replace('/\D/', '', $args[0] ?? '0');
        if ($amount < DepositService::MIN_AMOUNT) {
            $this->telegram->sendTo(
                $chatId,
                "⚠️ Số tiền nạp tối thiểu <b>" . number_format(DepositService::MIN_AMOUNT) . "đ</b>.\n\nVí dụ: <code>/deposit 50000</code>"
            );
            return;
        }

        $siteConfig = Config::getSiteConfig();
        $result = $this->depositService->createBankDeposit($user, $amount, $siteConfig);

        if (!$result['success']) {
            $this->telegram->sendTo($chatId, "❌ " . htmlspecialchars((string) ($result['message'] ?? 'Không bắt đầu được phiên nạp tiền.')));
            return;
        }

        $d = $result['data'];
        $qrUrl = trim((string) ($d['qr_url'] ?? ''));
        $ttlSeconds = (int) ($d['ttl_seconds'] ?? 300);
        if ($ttlSeconds <= 0) {
            $ttlSeconds = 300;
        }

        $message = $this->buildDepositInstructionMessage($d, $ttlSeconds);
        $photoSent = false;
        $depositKeyboard = $this->buildPayNowBackKeyboard($qrUrl);

        if ($qrUrl !== '' && str_starts_with($qrUrl, 'http')) {
            $telegramQrUrl = $this->toTelegramQrUrl($qrUrl);
            $photoSent = $this->telegram->sendPhotoTo($chatId, $telegramQrUrl, $message, [
                'reply_markup' => $depositKeyboard,
            ]);

            if (!$photoSent && $telegramQrUrl !== $qrUrl) {
                $photoSent = $this->telegram->sendPhotoTo($chatId, $qrUrl, $message, [
                    'reply_markup' => $depositKeyboard,
                ]);
            }
        }

        if (!$photoSent) {
            if ($qrUrl !== '' && str_starts_with($qrUrl, 'http')) {
                $message .= "\n\nQR: " . htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8');
            }
            $this->telegram->sendTo($chatId, $message, [
                'reply_markup' => $depositKeyboard,
            ]);
        }
    }

    /**
     * Ưu tiên QR template đầy đủ khi gửi Telegram để người dùng nhìn rõ thông tin.
     */
    private function toTelegramQrUrl(string $qrUrl): string
    {
        // Use a more compact template if it's a VietQR URL
        if (strpos($qrUrl, 'vietqr.net') !== false) {
            return str_replace(['-compact2.png', '-qr_only.png'], '-compact.png', $qrUrl);
        }
        return $qrUrl;
    }

    /**
     * Format nội dung nạp tiền chuẩn icon + text cho Telegram.
     *
     * @param array<string,mixed> $depositData
     */
    private function buildDepositInstructionMessage(array $depositData, int $ttlSeconds): string
    {
        $ttlMinutes = max(1, (int) ceil($ttlSeconds / 60));
        $bankName = htmlspecialchars((string) ($depositData['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankOwner = htmlspecialchars((string) ($depositData['bank_owner'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankAccount = htmlspecialchars((string) ($depositData['bank_account'] ?? ''), ENT_QUOTES, 'UTF-8');
        $depositCode = htmlspecialchars((string) ($depositData['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $amount = number_format((int) ($depositData['amount'] ?? 0)) . "đ";

        $msg = "🏦 <b>THÔNG TIN CHUYỂN KHOẢN</b>\n\n";
        $msg .= "🏛 Ngân hàng: <b>{$bankName}</b>\n";
        $msg .= "👤 Chủ TK: <b>{$bankOwner}</b>\n";
        $msg .= "💳 Số TK: <code>{$bankAccount}</code>\n";
        $msg .= "💰 Số tiền: <b>{$amount}</b>\n";
        $msg .= "📝 Nội dung: <code>{$depositCode}</code>\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "⚠️ <b>QUAN TRỌNG:</b> Mã hết hạn sau <b>{$ttlMinutes} phút</b>!\n";
        $msg .= "🚫 <b>Nội dung chuyển khoản phải chính xác để cộng tiền tự động.</b>";

        return $msg;
    }

    /**
     * /orders - 5 đơn hàng gần nhất
     */
    private function cmdOrders(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $orders = $this->orderModel->getUserVisibleOrders((int) $user['id'], [], 0, 5);

        if (empty($orders)) {
            $this->telegram->sendTo($chatId, "📦 Bạn chưa có đơn hàng nào.\n\n👇 Chọn Cửa hàng để duyệt sản phẩm.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [$this->backHomeButton()]
                ]),
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
            $msg .= "💰 Giá:  <b>{$price}</b>\n";
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
            $this->telegram->editMessage($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    private function formatOrderContentForTelegram(string $content, int $limit = 500): string
    {
        $clean = trim($content);
        if ($clean === '') {
            return 'Chưa có nội dung.';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($clean, 'UTF-8') > $limit) {
                $clean = mb_substr($clean, 0, $limit, 'UTF-8') . "\n... (đã rút gọn)";
            }
        } elseif (strlen($clean) > $limit) {
            $clean = substr($clean, 0, $limit) . "\n... (đã rút gọn)";
        }

        return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    }

    /**
     * /link <otp> - Liên kết tài khoản Web bằng mã OTP
     */
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
                . "1️⃣ Đăng nhập web (<code>{$domain}</code>) > Hồ sơ > Liên kết Telegram.\n"
                . "2️⃣ Lấy mã OTP và gửi lệnh: <code>/link 123456</code>.\n\n"
                . "Sau khi liên kết, tài khoản Web và Telegram sẽ đồng bộ ví!",
                [
                    'reply_markup' => TelegramService::buildInlineKeyboard([
                        [$this->backHomeButton()]
                    ]),
                ]
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
            $this->telegram->sendTo($chatId, "❌ Liên kết thất bại. Vui lòng thử lại sau.");
            return;
        }

        $freshUser = $this->userModel->findById($targetUserId) ?: $targetUser;
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

    /**
     * /unlink - Hủy liên kết Telegram - Web (Step 1: Choice)
     */
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
            . "1. <b>🤖 {$firstName}</b>: Tiền sẽ được chuyển sang tài khoản Bot hiện tại của bạn.\n"
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

    /**
     * Step 2: Action (No confirmation step as per user request)
     */
    private function cbDoUnlink(string $chatId, int $telegramId, string $dest, int $messageId): void
    {
        $res = $this->accService->unlinkWithChoice($telegramId, $dest);

        if ($res['success']) {
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
    /**
     * /help - Danh sách tất cả lệnh (phân theo quyền)
     */
    private function cmdHelp(string $chatId, int $telegramId): void
    {
        $isAdmin = TelegramConfig::isAdmin($telegramId);

        $msg = "🌟 <b>TRỢ GIÚP - DANH SÁCH LỆNH</b>\n\n";
        $msg .= "🛍 /shop - Cửa hàng\n";
        $msg .= "💰 /wallet - Ví của tôi\n";
        $msg .= "💳 /deposit - Nạp tiền\n";
        $msg .= "📦 /orders - Lịch sử giao dịch\n";
        $msg .= "📋 /menu - Mở menu nhanh\n";
        $msg .= "🔗 /link - Liên kết Web\n";
        $msg .= "❓ /help - Trợ giúp\n";

        if ($isAdmin) {
            $msg .= "\n👑 <b>LỆNH ADMIN:</b>\n";
            $msg .= "📊 /stats - Thống kê\n";
            $msg .= "📢 /broadcast &lt;nội_dung&gt; - Gửi thông báo\n";
            $msg .= "🛠 /maintenance on|off - Chế độ bảo trì\n";
            $msg .= "🏦 /setbank &lt;bank|stk|chủ&gt; - Đổi ngân hàng\n";
        }

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    $this->backHomeButton(),
                ]
            ]),
        ]);
    }

    // =========================================================
    //  Inline Callback Handlers
    // =========================================================

    /**
     * cat_{id} - Danh sách sản phẩm theo danh mục
     */
    private function cbCategory(string $chatId, int $catId, int $messageId = 0): void
    {
        $products = $this->productModel->getFiltered(['category_id' => $catId, 'status' => 'ON']);
        if (empty($products)) {
            $this->telegram->sendTo($chatId, "⚠️ Danh mục này hiện chưa có sản phẩm nào.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [
                        ['text' => '⬅️ Quay lại', 'callback_data' => 'shop'],
                    ]
                ]),
            ]);
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());

        $rows = [];
        foreach ($products as $p) {
            $stock = $inventory->getAvailableStock($p);
            $stockText = $stock === null ? 'Vô hạn' : number_format($stock);
            $priceText = number_format((float) $p['price_vnd']) . 'đ';

            // Theo format yêu cầu: Tên | Giá | 📦 SL
            $btnText = "{$p['name']} | {$priceText} | 📦 {$stockText}";

            $rows[] = [
                [
                    'text' => $btnText,
                    'callback_data' => 'prod_' . $p['id'],
                ]
            ];
        }

        // Nút cập nhật và nút quay lại
        $rows[] = [['text' => '🔄 Cập nhật sản phẩm', 'callback_data' => 'cat_' . $catId]];
        $rows[] = [['text' => '⬅️ Thay đổi Danh mục', 'callback_data' => 'shop']];

        $msg = "🛍️ <b>DANH SÁCH SẢN PHẨM</b>\n\n👇 Chọn sản phẩm bên dưới:";
        $markup = TelegramService::buildInlineKeyboard($rows);

        if ($messageId > 0) {
            $this->telegram->editMessage($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /**
     * prod_{id} - Chi tiết sản phẩm
     */
    private function cbProduct(string $chatId, int $prodId): void
    {
        $p = $this->productModel->find($prodId);
        if (!$p || $p['status'] !== 'ON') {
            $this->telegram->sendTo($chatId, "❌ Sản phẩm không tồn tại hoặc đã ngừng bán.");
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());
        $stock = $inventory->getAvailableStock($p);

        $msg = "🛍️ <b>" . htmlspecialchars($p['name']) . "</b>\n\n";
        $msg .= "💎 Giá: <b>" . number_format((float) $p['price_vnd']) . "đ</b>\n";

        $stockText = 'Hết hàng';
        if ($stock === null) {
            $stockText = 'Vô hạn';
        } elseif ($stock > 0) {
            $stockText = number_format($stock) . ' sản phẩm';
        }
        $msg .= "📦 Kho: <b>{$stockText}</b>\n\n";

        $desc = strip_tags((string) ($p['description'] ?? ''));
        if ($desc !== '') {
            $msg .= "📝 <b>Mô tả:</b>\n<i>" . htmlspecialchars(mb_substr($desc, 0, 300)) . (mb_strlen($desc) > 300 ? '...' : '') . "</i>\n";
        }

        $rows = [];
        if ($stock === null || $stock > 0) {
            // Redirect to confirm (or quantity input if needed)
            $rows[] = [['text' => '🛒 MUA NGAY', 'callback_data' => 'buy_' . $p['id'] . '_1']];
        }
        $rows[] = [['text' => '⬅️ Quay lại', 'callback_data' => 'cat_' . ($p['category_id'] ?? 0)]];

        $image = trim((string) ($p['image'] ?? ''));
        if ($image !== '') {
            $photoUrl = str_starts_with($image, 'http') ? $image : (rtrim(BASE_URL, '/') . '/' . ltrim($image, '/'));

            // Telegram cannot fetch from localhost. Skip photo if so to avoid API error.
            if (!str_contains($photoUrl, 'localhost') && !str_contains($photoUrl, '127.0.0.1')) {
                if (
                    $this->telegram->sendPhotoTo($chatId, $photoUrl, $msg, [
                        'reply_markup' => TelegramService::buildInlineKeyboard($rows),
                    ])
                ) {
                    return;
                }
            }
        }

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * buy_{prodId}_{qty} - Màn xác nhận mua hàng
     */
    private function cbBuyConfirm(string $chatId, int $telegramId, int $prodId, int $qty, ?string $customerInfo = null): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $p = $this->productModel->find($prodId);
        if (!$p)
            return;

        $productType = (string) ($p['product_type'] ?? 'account');
        $requiresInfo = (int) ($p['requires_info'] ?? 0) === 1;

        // Nếu mới bắt đầu (qty=1 mặc định từ callback) và là loại cần hỏi thêm
        if ($qty === 1 && $customerInfo === null) {
            // Source Code (link) luôn mặc định SL=1 và không cần hỏi thêm gì
            if ($productType !== 'link' && ($productType === 'account' || $requiresInfo)) {
                $this->setPurchaseSession($telegramId, [
                    'prod_id' => $prodId,
                    'qty' => 1,
                    'step' => 'qty'
                ]);
                $this->telegram->sendTo($chatId, "🔢 <b>NHẬP SỐ LƯỢNG</b>\n\n👇 Vui lòng nhập số lượng bạn muốn mua:", [
                    'reply_markup' => TelegramService::buildInlineKeyboard([
                        [['text' => '❌ Hủy bỏ', 'callback_data' => 'shop']],
                    ]),
                ]);
                return;
            }
        }

        // Lấy session để check giftcode
        $session = $this->getPurchaseSession($telegramId);
        $giftcode = $session['giftcode'] ?? null;

        $price = (float) $p['price_vnd'];
        $subtotal = $price * $qty;
        $discount = 0;
        $total = $subtotal;
        $giftError = null;

        if ($giftcode) {
            try {
                $quote = $this->purchaseService->quoteForDisplay($prodId, [
                    'quantity' => $qty,
                    'giftcode' => $giftcode
                ]);
                if ($quote['success']) {
                    $total = (float) ($quote['pricing']['total_price'] ?? $subtotal);
                    $discount = (float) ($quote['pricing']['discount_amount'] ?? 0);
                } else {
                    $giftError = $quote['message'] ?? 'Mã giảm giá không hợp lệ.';
                    $giftcode = null; // Reset if invalid
                    if (isset($session['giftcode'])) {
                        unset($session['giftcode']);
                        $this->setPurchaseSession($telegramId, $session);
                    }
                }
            } catch (Throwable $e) {
                $giftcode = null;
            }
        }

        $balance = (float) ($user['money'] ?? 0);

        $msg = "🛒 <b>XÁC NHẬN MUA HÀNG</b>\n\n";
        if ($giftError) {
            $msg .= "⚠️ <b>Lỗi mã giảm giá:</b> " . htmlspecialchars($giftError) . "\n\n";
        }
        $msg .= "📦 Sản phẩm: <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= "🔢 Số lượng: <b>{$qty}</b>\n";

        if ($discount > 0) {
            $msg .= "🏷️ Tạm tính: <s>" . number_format($subtotal) . "đ</s>\n";
            $msg .= "🏷️ Giảm giá: -<b>" . number_format($discount) . "đ</b> (<i>{$giftcode}</i>)\n";
        }

        $msg .= "💎 Thành tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "💰 Số dư ví: <b>" . number_format($balance) . "đ</b>\n\n";

        if ($customerInfo !== null && trim($customerInfo) !== '') {
            $msg .= "📝 Thông tin: <code>" . htmlspecialchars($customerInfo) . "</code>\n\n";
        }

        if ($balance < $total) {
            $msg .= "⚠️ Số dư không đủ! Cần nạp thêm: <b>" . number_format($total - $balance) . "đ</b>";
            $this->telegram->sendTo($chatId, $msg, [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [
                        ['text' => '💳 Nạp tiền', 'callback_data' => 'deposit'],
                        ['text' => '❌ Hủy bỏ', 'callback_data' => 'prod_' . $prodId],
                    ]
                ]),
            ]);
            return;
        }

        $msg .= "⚠️ Xác nhận trừ tiền trực tiếp từ ví.";

        $confirmAction = "do_buy_" . $prodId . "_" . $qty;

        // Cập nhật session confirm
        $this->setPurchaseSession($telegramId, [
            'prod_id' => $prodId,
            'qty' => $qty,
            'info' => $customerInfo,
            'giftcode' => $giftcode,
            'step' => 'confirm'
        ]);

        $rows = [];
        if (!$giftcode) {
            $rows[] = [['text' => '🏷️ Nhập mã giảm giá', 'callback_data' => 'buy_gift_' . $prodId . '_' . $qty]];
        }
        $rows[] = [
            ['text' => '❌ HỦY BỎ', 'callback_data' => 'prod_' . $prodId],
            ['text' => '✅ XÁC NHẬN MUA', 'callback_data' => $confirmAction],
        ];

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * do_buy_{prodId}_{qty} - Thực hiện mua hàng
     */
    private function cbDoBuy(string $chatId, int $telegramId, int $prodId, int $qty): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        // Lấy info từ session nếu có
        $session = $this->getPurchaseSession($telegramId);
        if (!$session) {
            $this->telegram->sendTo($chatId, "⏰ <b>Giao dịch hết hạn!</b>\nPhiên mua hàng của bạn đã quá 5 phút và tự động bị hủy. Vui lòng bắt đầu lại từ Cửa hàng.");
            $this->showMainMenu($chatId, $telegramId);
            return;
        }

        $customerInput = ($session && (int) $session['prod_id'] === $prodId) ? ($session['info'] ?? null) : null;
        $giftcode = ($session && (int) $session['prod_id'] === $prodId) ? ($session['giftcode'] ?? null) : null;

        $this->clearPurchaseSession($telegramId);

        // Cooldown chặn double-click
        $cooldownSec = TelegramConfig::buyCooldown();
        if (!$this->checkAndSetCooldown("buy_{$telegramId}", $cooldownSec)) {
            $this->telegram->sendTo($chatId, "⏳ Vui lòng chờ {$cooldownSec} giây giữa 2 lần thao tác.");
            return;
        }

        $result = $this->purchaseService->purchaseWithWallet($prodId, $user, [
            'quantity' => $qty,
            'customer_input' => $customerInput,
            'giftcode' => $giftcode,
            'source' => 'telegram',
            'telegram_id' => $telegramId,
        ]);

        if (!$result['success']) {
            $this->telegram->sendTo($chatId, "❌ <b>LỖI:</b> " . htmlspecialchars($result['message'] ?? 'Giao dịch không thành công.'));
        }
        // Trường hợp success: PurchaseService đã enqueue notification chuẩn vào Outbox, không cần gửi thêm ở đây để tránh trùng lặp.
    }

    // =========================================================
    //  ADMIN Commands
    // =========================================================

    /**
     * /stats - Thống kê toàn hệ thống
     */
    private function cmdStats(string $chatId, int $telegramId): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $conn = $this->userModel->getConnection();
        $today = $this->timeService
            ? $this->timeService->formatDb($this->timeService->nowTs(), 'Y-m-d')
            : date('Y-m-d');

        $userCount = $this->userModel->count();
        $tgCount = (int) $conn->query("SELECT COUNT(*) FROM `user_telegram_links`")->fetchColumn();
        $newTgToday = (int) $conn->query("SELECT COUNT(*) FROM `user_telegram_links` WHERE DATE(`linked_at`)='{$today}'")->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(`price`),0) AS rev FROM `orders` WHERE DATE(`created_at`)=? AND `status`='completed'");
        $stmt->execute([$today]);
        $todayOrders = $stmt->fetch(PDO::FETCH_ASSOC);

        $depositPending = (int) $conn->query("SELECT COUNT(*) FROM `pending_deposits` WHERE `status`='pending'")->fetchColumn();

        $outboxStats = (new TelegramOutbox())->getStats();

        $lastCron = trim((string) get_setting('last_cron_run', ''));
        $workerStatus = $lastCron === '' ? '❌ Chưa chạy' : "✅ {$lastCron}";

        $msg = "📊 <b>THỐNG KÊ HỆ THỐNG</b> ({$today})\n\n";
        $msg .= "👥 Tổng user web: <b>{$userCount}</b>\n";
        $msg .= "🔗 Đã liên kết TG: <b>{$tgCount}</b> <i>(+{$newTgToday} hôm nay)</i>\n\n";
        $msg .= "🛍 <b>Đơn hàng hôm nay:</b>\n";
        $msg .= "   Số đơn: <b>" . $todayOrders['cnt'] . "</b>\n";
        $msg .= "   Doanh thu: <b>" . number_format((float) $todayOrders['rev']) . "đ</b>\n\n";
        $msg .= "💰 Nạp chờ duyệt: <b>{$depositPending}</b>\n\n";
        $msg .= "📤 <b>Outbox:</b>\n";
        $msg .= "   Chờ gửi: <b>{$outboxStats['pending']}</b>\n";
        $msg .= "   Đã gửi:  <b>{$outboxStats['sent']}</b>\n";
        $msg .= "   Lỗi:     <b>{$outboxStats['failed']}</b>\n\n";
        $msg .= "⚙️ Worker: {$workerStatus}";

        $this->telegram->sendTo($chatId, $msg);
    }

    /**
     * /broadcast <nội dung> - Push thông báo tới tất cả user đã link (qua Outbox)
     */
    private function cmdBroadcast(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $content = trim(implode(' ', $args));
        if ($content === '') {
            $this->telegram->sendTo(
                $chatId,
                "📢 <b>BROADCAST</b>\n\nCú pháp:\n<code>/broadcast &lt;nội dung&gt;</code>\n\nVí dụ:\n<code>/broadcast ⚡ Flash sale 50% trong 24h!</code>"
            );
            return;
        }

        $conn = $this->userModel->getConnection();
        $links = $conn->query("SELECT `telegram_id` FROM `user_telegram_links`")->fetchAll(PDO::FETCH_COLUMN);

        if (empty($links)) {
            $this->telegram->sendTo($chatId, "⚠️ Chưa có user nào liên kết Telegram.");
            return;
        }

        $outbox = new TelegramOutbox();
        $msgText = "📢 <b>THÔNG BÁO HỆ THỐNG</b>\n\n" . $content;
        $count = 0;

        foreach ($links as $tid) {
            $outbox->push((int) $tid, $msgText);
            $count++;
        }

        $this->telegram->sendTo(
            $chatId,
            "✅ Đã xếp hàng <b>{$count}</b> tin vào Outbox.\nSẽ gửi trong ít phút tới."
        );
    }

    /**
     * /maintenance on|off - Bật/tắt bảo trì hệ thống
     */
    private function cmdMaintenance(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $action = strtolower(trim($args[0] ?? ''));
        if (!in_array($action, ['on', 'off'], true)) {
            $this->telegram->sendTo(
                $chatId,
                "🛠 <b>BẢO TRÌ HỆ THỐNG</b>\n\n"
                . "<code>/maintenance on</code>  - Bật bảo trì\n"
                . "<code>/maintenance off</code> - Tắt bảo trì"
            );
            return;
        }

        try {
            if (!class_exists('MaintenanceService')) {
                $path = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
                require_once $path . '/app/Services/MaintenanceService.php';
            }
            $svc = new MaintenanceService();
            if ($action === 'on') {
                $svc->saveConfig(['maintenance_enabled' => '1']);
                $this->telegram->sendTo($chatId, "🛠 <b>Đã bật bảo trì!</b>\nWebsite khóa và hiện trang bảo trì.");
            } else {
                $svc->clearNow();
                $this->telegram->sendTo($chatId, "✅ <b>Đã tắt bảo trì!</b>\nWebsite hoạt động bình thường.");
            }
        } catch (Throwable $e) {
            $this->telegram->sendTo($chatId, "❌ Lỗi: " . $e->getMessage());
        }
    }

    /**
     * /setbank <Ngân hàng>|<STK>|<Chủ TK> - Cập nhật thông tin ngân hàng nhanh
     */
    private function cmdSetBank(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $payload = implode(' ', $args);
        $parts = explode('|', $payload);

        if (count($parts) < 3) {
            $this->telegram->sendTo(
                $chatId,
                "🏦 <b>SETBANK</b>\n\nCú pháp:\n<code>/setbank Ngân hàng|Số TK|Chủ TK</code>\n\nVí dụ:\n<code>/setbank MB Bank|0123456789|NGUYEN THANH PHUC</code>"
            );
            return;
        }

        [$bankName, $bankAcc, $bankOwner] = array_map('trim', $parts);

        $conn = $this->userModel->getConnection();
        $stmt = $conn->prepare("UPDATE `setting` SET `bank_name`=?, `bank_account`=?, `bank_owner`=? ORDER BY `id` ASC LIMIT 1");
        $stmt->execute([$bankName, $bankAcc, $bankOwner]);
        Config::clearSiteConfigCache();

        $this->telegram->sendTo(
            $chatId,
            "✅ <b>Đã cập nhật ngân hàng!</b>\n\n"
            . "🏦 " . htmlspecialchars($bankName) . "\n"
            . "💳 " . htmlspecialchars($bankAcc) . "\n"
            . "👤 " . htmlspecialchars($bankOwner)
        );
    }

    // =========================================================
    //  Standalone: Shadow Account Management
    // =========================================================

    /**
     * Resolve linked user - tự động tạo Shadow Account nếu chưa có
     * Trả về null nếu tài khoản bị ban hoặc không thể khởi tạo
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
     * Đảm bảo user Telegram có Web User record (Shadow Account)
     * Username format: tg_{telegramId}
     */
    private function ensureShadowAccount(int $telegramId, ?string $username = null, ?string $firstName = null): void
    {
        // Không tạo lại nếu đã có
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
     * Cập nhật last_active khi user hoạt động
     * Nếu chưa có link thì tạo Shadow Account
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
    //  Rate Limiting ??" File-based (persistent across requests)
    // =========================================================

    /**
     * Per-user rate limit - tồn tại giữa các Webhook request nhờ file
     */
    private function checkUserRateLimit(int $telegramId): bool
    {
        return $this->fileRateCheck(
            "user_{$telegramId}",
            TelegramConfig::rateLimit(),
            TelegramConfig::RATE_LIMIT_WINDOW
        );
    }

    /**
     * Cooldown check - Trả về true nếu ngoài cooldown (được phép)
     */
    private function checkAndSetCooldown(string $key, int $seconds): bool
    {
        $dir = TelegramConfig::cooldownDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . '/' . md5($key) . '.ts';
        $now = $this->timeService ? $this->timeService->nowTs() : time();

        if (file_exists($file)) {
            $last = (int) @file_get_contents($file);
            if ($now - $last < $seconds)
                return false;
        }

        @file_put_contents($file, (string) $now, LOCK_EX);
        return true;
    }

    /**
     * Sliding window rate limiter (file-based)
     */
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
}

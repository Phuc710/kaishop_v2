<?php

/**
 * TelegramBotService — KaiShop Bot Core Logic
 *
 * Architecture:
 *  - Standalone Flow: User Telegram mới → tự động tạo Shadow Account → mua hàng ngay
 *  - Role-Based Menus: /start và /menu hiển thị khác nhau cho User/Admin
 *  - Shared Backend: 100% dùng chung Model/Service với Web
 *  - File-based Rate Limit: tồn tại giữa các webhook request độc lập
 *  - Purchase Cooldown: chặn double-click mua hàng
 *  - Deposit TTL 5 phút: đồng bộ với Web, SePay webhook tự từ chối nếu quá hạn
 *
 * @see TelegramConfig — tất cả constants và getters tập trung
 * @see TelegramService — API wrapper cấp thấp (sendTo, buildInlineKeyboard)
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
            return; // Silent drop — Telegram đã nhận 200 OK
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
            return; // Bỏ qua tin nhắn thường
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
            default => $this->telegram->sendTo($chatId, "❌ Lệnh không hợp lệ. Gửi /help để xem danh sách lệnh.")
        };
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
                $this->cbCategory($chatId, (int) ($parts[1] ?? 0));
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
            case 'wallet':
                $this->cmdWallet($chatId, $telegramId);
                break;
            case 'deposit':
                // deposit_menu — hiện hướng dẫn /deposit
                $this->telegram->sendTo(
                    $chatId,
                    "💳 <b>NẠP TIỀN</b>\n\nGõ lệnh:\n<code>/deposit &lt;số_tiền&gt;</code>\n\nVí dụ:\n<code>/deposit 50000</code>"
                );
                break;
            case 'orders':
                $this->cmdOrders($chatId, $telegramId);
                break;
            case 'menu':
                $this->cmdMenu($chatId, $telegramId);
                break;
            case 'help':
                $this->cmdHelp($chatId, $telegramId);
                break;
            case 'stats':
                // stats_admin — shortcut cho Admin từ menu
                $this->cmdStats($chatId, $telegramId);
                break;
            // Bỏ qua các callback không xác định
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    // =========================================================
    //  USER Commands
    // =========================================================

    /**
     * /start — Chào mừng + Role-based menu
     */
    private function cmdStart(string $chatId, int $telegramId, string $name): void
    {
        $siteName = get_setting('ten_web', 'KaiShop');
        $isAdmin = TelegramConfig::isAdmin($telegramId);

        // Ban check ngay từ đầu
        $link = $this->linkModel->findByTelegramId($telegramId);
        if ($link) {
            $user = $this->userModel->findById($link['user_id']);
            if ($user && (int) ($user['bannd'] ?? 0) === 1) {
                $this->telegram->sendTo($chatId, "🚫 Tài khoản của bạn đã bị khóa. Liên hệ hỗ trợ nếu có nhầm lẫn.");
                return;
            }
        }

        $msg = "👋 Chào mừng <b>{$name}</b> đến với <b>{$siteName} Bot</b>!\n\n";

        if ($isAdmin) {
            $msg .= "👑 <b>QUYỀN QUẢN TRỊ VIÊN</b>\n";
            $msg .= "Hệ thống đã nhận diện bạn là Administrator.\n\n";
            $msg .= "🛠 <b>LỆNH ADMIN:</b>\n";
            $msg .= "📊 /stats — Thống kê hệ thống\n";
            $msg .= "📣 /broadcast — Gửi thông báo toàn bộ\n";
            $msg .= "🔧 /maintenance — Bật/tắt bảo trì\n";
            $msg .= "🏦 /setbank — Cấu hình ngân hàng\n\n";
        } else {
            $msg .= "🛍 <b>CỬA HÀNG TRỰC TUYẾN</b>\n";
            $msg .= "Mua sắm, nạp tiền và xem đơn hàng ngay tại đây.\n\n";

            if ($link && !empty($user['username'])) {
                $msg .= "👤 Đang đăng nhập: <b>" . htmlspecialchars($user['username']) . "</b>\n\n";
            } else {
                $msg .= "💡 <i>Mẹo: Gửi /link &lt;mã OTP&gt; để đồng bộ tài khoản Web.</i>\n\n";
            }
        }

        $msg .= "📚 <b>LỆNH NGƯỜI DÙNG:</b>\n";
        $msg .= "🛒 /shop — Xem sản phẩm\n";
        $msg .= "💰 /wallet — Kiểm tra số dư\n";
        $msg .= "💳 /deposit — Nạp tiền\n";
        $msg .= "📜 /orders — Lịch sử đơn hàng\n";
        $msg .= "⚙️ /menu — Menu phím ảo\n";
        $msg .= "❓ /help — Hướng dẫn";

        $this->telegram->sendTo($chatId, $msg);

        // Hiển thị Menu phím ảo luôn sau lời chào
        $this->cmdMenu($chatId, $telegramId);
    }

    /**
     * /menu — Menu phím ảo, phân quyền Admin/User
     */
    private function cmdMenu(string $chatId, int $telegramId): void
    {
        $siteName = get_setting('ten_web', 'KaiShop');
        $rows = [
            [
                ['text' => '🛒 Cửa hàng', 'callback_data' => 'shop'],
                ['text' => '💰 Ví của tôi', 'callback_data' => 'wallet'],
            ],
            [
                ['text' => '💳 Nạp tiền', 'callback_data' => 'deposit_menu'],
                ['text' => '📜 Đơn hàng', 'callback_data' => 'orders'],
            ],
            [
                ['text' => '❓ Trợ giúp', 'callback_data' => 'help'],
            ],
        ];

        if (TelegramConfig::isAdmin($telegramId)) {
            $rows[] = [
                ['text' => '📊 Thống kê Admin', 'callback_data' => 'stats_admin'],
            ];
        }

        $this->telegram->sendTo($chatId, "⚙️ <b>{$siteName} — MENU</b>\nChọn chức năng:", [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * /shop — Danh mục sản phẩm
     */
    private function cmdShop(string $chatId): void
    {
        $categories = $this->categoryModel->getActive();
        if (empty($categories)) {
            $this->telegram->sendTo($chatId, "🛍 Hiện hệ thống chưa có danh mục sản phẩm nào.");
            return;
        }

        $rows = [];
        foreach ($categories as $cat) {
            $rows[] = [['text' => '📁 ' . $cat['name'], 'callback_data' => 'cat_' . $cat['id']]];
        }

        $this->telegram->sendTo($chatId, "🛍 <b>DANH MỤC SẢN PHẨM</b>\nVui lòng chọn danh mục:", [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * /wallet — Xem số dư ví (yêu cầu Shadow Account)
     */
    private function cmdWallet(string $chatId, int $telegramId): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $msg = "💰 <b>THÔNG TIN VÍ</b>\n\n";
        $msg .= "👤 Tài khoản: <b>" . htmlspecialchars($user['username']) . "</b>\n";
        $msg .= "💵 Số dư: <b>" . number_format((float) ($user['money'] ?? 0)) . "đ</b>\n";
        $msg .= "📈 Tổng nạp: <b>" . number_format((float) ($user['tong_nap'] ?? 0)) . "đ</b>\n\n";
        $msg .= "👉 Nạp thêm: <code>/deposit &lt;số_tiền&gt;</code>";

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    ['text' => '💳 Nạp tiền ngay', 'callback_data' => 'deposit_menu'],
                    ['text' => '🔙 Menu', 'callback_data' => 'menu'],
                ]
            ]),
        ]);
    }

    /**
     * /deposit <số_tiền> — Tạo mã chuyển khoản ngân hàng (TTL 5 phút)
     */
    private function cmdDeposit(string $chatId, int $telegramId, array $args): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $amount = (int) preg_replace('/\D/', '', $args[0] ?? '0');
        if ($amount < DepositService::MIN_AMOUNT) {
            $this->telegram->sendTo(
                $chatId,
                "❌ Số tiền nạp tối thiểu <b>" . number_format(DepositService::MIN_AMOUNT) . "đ</b>.\n\nVí dụ nạp 50k:\n<code>/deposit 50000</code>"
            );
            return;
        }

        $siteConfig = Config::getSiteConfig();
        $result = $this->depositService->createBankDeposit($user, $amount, $siteConfig);

        if (!$result['success']) {
            $this->telegram->sendTo($chatId, "❌ " . ($result['message'] ?? 'Không thể tạo mã nạp tiền. Vui lòng thử lại.'));
            return;
        }

        $d = $result['data'];
        $msg = "💳 <b>THÔNG TIN CHUYỂN KHOẢN</b>\n\n";
        $msg .= "🏦 Ngân hàng: <b>" . htmlspecialchars($d['bank_name']) . "</b>\n";
        $msg .= "👤 Chủ TK: <b>" . htmlspecialchars($d['bank_owner']) . "</b>\n";
        $msg .= "🔢 Số TK: <code>" . htmlspecialchars($d['bank_account']) . "</code>\n";
        $msg .= "💰 Số tiền: <b>" . number_format($d['amount']) . "đ</b>\n";
        $msg .= "📝 Nội dung: <code>" . htmlspecialchars($d['deposit_code']) . "</code>\n\n";
        $msg .= "⏰ <b>QUAN TRỌNG:</b> Mã hết hạn sau <b>5 phút</b>!\n";
        $msg .= "⚠️ Nội dung chuyển khoản phải chính xác để được cộng tiền tự động.";

        $this->telegram->sendTo($chatId, $msg);
    }

    /**
     * /orders — 5 đơn hàng gần nhất
     */
    private function cmdOrders(string $chatId, int $telegramId): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $orders = $this->orderModel->getUserVisibleOrders((int) $user['id'], [], 0, 5);

        if (empty($orders)) {
            $this->telegram->sendTo($chatId, "📜 Bạn chưa có đơn hàng nào.\n\n👉 Gõ /shop để xem sản phẩm.");
            return;
        }

        $msg = "📜 <b>5 ĐƠN HÀNG GẦN NHẤT</b>\n\n";
        foreach ($orders as $o) {
            $statusIcon = $o['status'] === 'completed' ? '✅' : '⏳';
            $msg .= "{$statusIcon} <code>" . htmlspecialchars($o['order_code_short'] ?? $o['order_code']) . "</code>\n";
            $msg .= "   📦 " . htmlspecialchars($o['product_name']) . "\n";
            $msg .= "   💵 " . number_format((float) $o['price']) . "đ\n\n";
        }

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    ['text' => '🔙 Menu', 'callback_data' => 'menu'],
                ]
            ]),
        ]);
    }

    /**
     * /link <otp> — Liên kết tài khoản Web bằng mã OTP
     */
    private function cmdLink(string $chatId, int $telegramId, array $args, array $from): void
    {
        $code = trim($args[0] ?? '');
        if ($code === '') {
            $this->telegram->sendTo(
                $chatId,
                "🔗 <b>LIÊN KẾT TÀI KHOẢN WEB</b>\n\n"
                . "1. Đăng nhập Website → Hồ sơ → Liên kết Telegram\n"
                . "2. Copy mã OTP và gửi:\n\n"
                . "<code>/link &lt;mã_otp&gt;</code>\n\nVí dụ:\n<code>/link 123456</code>"
            );
            return;
        }

        $userId = $this->otpModel->verifyCode($code);
        if (!$userId) {
            $this->telegram->sendTo($chatId, "❌ Mã OTP không chính xác hoặc đã hết hạn (5 phút).\n\nTrở về Website để lấy mã mới.");
            return;
        }

        $firstName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        $linked = $this->linkModel->linkUser($userId, $telegramId, $from['username'] ?? null, $firstName);

        if ($linked) {
            $user = $this->userModel->findById($userId);
            $this->telegram->sendTo(
                $chatId,
                "🎉 <b>LIÊN KẾT THÀNH CÔNG!</b>\n\n"
                . "Tài khoản Web: <b>" . htmlspecialchars($user['username'] ?? '???') . "</b>\n"
                . "Từ nay lịch sử mua hàng và ví sẽ được đồng bộ."
            );
            $this->cmdMenu($chatId, $telegramId);
        } else {
            $this->telegram->sendTo($chatId, "❌ Liên kết thất bại. Vui lòng thử lại.");
        }
    }

    /**
     * /unlink — Hủy liên kết Telegram ↔ Web
     */
    private function cmdUnlink(string $chatId, int $telegramId): void
    {
        $link = $this->linkModel->findByTelegramId($telegramId);
        if (!$link) {
            $this->telegram->sendTo($chatId, "⚠️ Bạn chưa liên kết tài khoản nào.");
            return;
        }

        $user = $this->userModel->findById($link['user_id']);
        $username = htmlspecialchars($user['username'] ?? '???');

        if ($this->linkModel->unlinkByTelegramId($telegramId)) {
            $this->telegram->sendTo(
                $chatId,
                "✅ Đã hủy liên kết với tài khoản <b>{$username}</b>.\n\n"
                . "Liên kết lại bất cứ lúc nào: <code>/link &lt;otp&gt;</code>"
            );
        } else {
            $this->telegram->sendTo($chatId, "❌ Hủy liên kết thất bại. Vui lòng thử lại.");
        }
    }

    /**
     * /help — Danh sách tất cả lệnh (phân theo quyền)
     */
    private function cmdHelp(string $chatId, int $telegramId): void
    {
        $isAdmin = TelegramConfig::isAdmin($telegramId);

        $msg = "🆘 <b>DANH SÁCH LỆNH</b>\n\n";
        $msg .= "🛒 /shop — Xem danh mục sản phẩm\n";
        $msg .= "💰 /wallet — Kiểm tra số dư ví\n";
        $msg .= "💳 /deposit &lt;số_tiền&gt; — Nạp tiền (hết hạn 5 phút)\n";
        $msg .= "📜 /orders — Lịch sử mua hàng\n";
        $msg .= "⚙️ /menu — Mở menu phím ảo\n";
        $msg .= "🔗 /link &lt;otp&gt; — Liên kết tài khoản Web\n";
        $msg .= "🔓 /unlink — Hủy liên kết\n";
        $msg .= "❓ /help — Trợ giúp\n";

        if ($isAdmin) {
            $msg .= "\n🛠 <b>LỆNH ADMIN:</b>\n";
            $msg .= "📊 /stats — Thống kê hệ thống\n";
            $msg .= "📣 /broadcast &lt;nội dung&gt; — Gửi thông báo\n";
            $msg .= "🔧 /maintenance on|off — Bật/tắt bảo trì\n";
            $msg .= "🏦 /setbank &lt;bank|stk|chủ&gt; — Cấu hình ngân hàng\n";
        }

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    ['text' => '🔙 Menu', 'callback_data' => 'menu'],
                ]
            ]),
        ]);
    }

    // =========================================================
    //  Inline Callback Handlers
    // =========================================================

    /**
     * cat_{id} — Danh sách sản phẩm theo danh mục
     */
    private function cbCategory(string $chatId, int $catId): void
    {
        $products = $this->productModel->getFiltered(['category_id' => $catId, 'status' => 'ON']);
        if (empty($products)) {
            $this->telegram->sendTo($chatId, "😿 Danh mục này hiện không có sản phẩm.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [
                        ['text' => '🔙 Quay lại', 'callback_data' => 'shop'],
                    ]
                ]),
            ]);
            return;
        }

        $rows = [];
        foreach ($products as $p) {
            $rows[] = [
                [
                    'text' => '🎁 ' . $p['name'] . ' — ' . number_format((float) $p['price_vnd']) . 'đ',
                    'callback_data' => 'prod_' . $p['id'],
                ]
            ];
        }
        $rows[] = [['text' => '🔙 Danh mục', 'callback_data' => 'shop']];

        $this->telegram->sendTo($chatId, "🎁 <b>DANH SÁCH SẢN PHẨM</b>\nChọn sản phẩm:", [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * prod_{id} — Chi tiết sản phẩm
     */
    private function cbProduct(string $chatId, int $prodId): void
    {
        $p = $this->productModel->find($prodId);
        if (!$p || $p['status'] !== 'ON') {
            $this->telegram->sendTo($chatId, "⚠️ Sản phẩm không tồn tại hoặc đã ngừng bán.");
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());
        $stock = $inventory->getAvailableStock($p);

        $msg = "📦 <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= "💰 Giá: <b>" . number_format((float) $p['price_vnd']) . "đ</b>\n";
        $msg .= "📦 Tồn kho: <b>" . ($stock > 0 ? $stock : 'Hết hàng') . "</b>\n\n";

        $desc = strip_tags((string) ($p['description'] ?? ''));
        if ($desc !== '') {
            $msg .= "<i>" . htmlspecialchars(mb_substr($desc, 0, 300)) . (mb_strlen($desc) > 300 ? '...' : '') . "</i>";
        }

        $rows = [];
        if ($stock > 0) {
            $rows[] = [['text' => '🛒 MUA NGAY', 'callback_data' => 'buy_' . $p['id'] . '_1']];
        }
        $rows[] = [['text' => '🔙 Quay lại', 'callback_data' => 'cat_' . ($p['category_id'] ?? 0)]];

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard($rows),
        ]);
    }

    /**
     * buy_{prodId}_{qty} — Màn xác nhận mua hàng
     */
    private function cbBuyConfirm(string $chatId, int $telegramId, int $prodId, int $qty): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $p = $this->productModel->find($prodId);
        if (!$p)
            return;

        $total = (float) $p['price_vnd'] * $qty;
        $balance = (float) ($user['money'] ?? 0);

        $msg = "🛒 <b>XÁC NHẬN MUA HÀNG</b>\n\n";
        $msg .= "📦 Sản phẩm: <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= "🔢 Số lượng: <b>{$qty}</b>\n";
        $msg .= "💵 Thành tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "💰 Số dư ví: <b>" . number_format($balance) . "đ</b>\n\n";

        if ($balance < $total) {
            $msg .= "⚠️ Số dư không đủ! Cần nạp thêm: <b>" . number_format($total - $balance) . "đ</b>";
            $this->telegram->sendTo($chatId, $msg, [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [
                        ['text' => '💳 Nạp tiền', 'callback_data' => 'deposit_menu'],
                        ['text' => '❌ Hủy', 'callback_data' => 'prod_' . $prodId],
                    ]
                ]),
            ]);
            return;
        }

        $msg .= "⚠️ Xác nhận sẽ trừ tiền ngay từ ví của bạn.";

        $this->telegram->sendTo($chatId, $msg, [
            'reply_markup' => TelegramService::buildInlineKeyboard([
                [
                    ['text' => '❌ HỦY', 'callback_data' => 'prod_' . $prodId],
                    ['text' => '✅ XÁC NHẬN MUA', 'callback_data' => 'do_buy_' . $prodId . '_' . $qty],
                ]
            ]),
        ]);
    }

    /**
     * do_buy_{prodId}_{qty} — Thực hiện mua hàng
     */
    private function cbDoBuy(string $chatId, int $telegramId, int $prodId, int $qty): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        // Cooldown chặn double-click
        $cooldownSec = TelegramConfig::buyCooldown();
        if (!$this->checkAndSetCooldown("buy_{$telegramId}", $cooldownSec)) {
            $this->telegram->sendTo($chatId, "⏳ Vui lòng chờ {$cooldownSec} giây giữa 2 lần mua.");
            return;
        }

        $result = $this->purchaseService->purchaseWithWallet($prodId, $user, [
            'quantity' => $qty,
            'source' => 'telegram',
            'telegram_id' => $telegramId,
        ]);

        if ($result['success']) {
            $msg = "🎉 <b>THANH TOÁN THÀNH CÔNG!</b>\n\n";
            $msg .= "🧾 Đơn hàng: <code>" . htmlspecialchars($result['order']['order_code'] ?? '???') . "</code>\n";
            $msg .= "📦 Sản phẩm: <b>" . htmlspecialchars($result['order']['product_name'] ?? '') . "</b>\n";

            if (!empty($result['order']['content'])) {
                $msg .= "\n🔑 <b>Nội dung:</b>\n<code>" . htmlspecialchars($result['order']['content']) . "</code>";
            } elseif (!empty($result['pending'])) {
                $msg .= "\n⏳ Đang chờ xử lý. Admin sẽ giao hàng sớm.";
            }

            $this->telegram->sendTo($chatId, $msg);
        } else {
            $this->telegram->sendTo($chatId, "❌ <b>LỖI:</b> " . htmlspecialchars($result['message'] ?? 'Giao dịch không thành công.'));
        }
    }

    // =========================================================
    //  ADMIN Commands
    // =========================================================

    /**
     * /stats — Thống kê toàn hệ thống
     */
    private function cmdStats(string $chatId, int $telegramId): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $conn = $this->userModel->getConnection();
        $today = date('Y-m-d', TimeService::instance()->nowTs());

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
        $msg .= "👤 Tổng user web: <b>{$userCount}</b>\n";
        $msg .= "🔗 Đã liên kết TG: <b>{$tgCount}</b> <i>(+{$newTgToday} hôm nay)</i>\n\n";
        $msg .= "📦 <b>Đơn hàng hôm nay:</b>\n";
        $msg .= "   Số đơn: <b>" . $todayOrders['cnt'] . "</b>\n";
        $msg .= "   Doanh thu: <b>" . number_format((float) $todayOrders['rev']) . "đ</b>\n\n";
        $msg .= "💳 Nạp chờ duyệt: <b>{$depositPending}</b>\n\n";
        $msg .= "✉️ <b>Outbox:</b>\n";
        $msg .= "   Chờ gửi: <b>{$outboxStats['pending']}</b>\n";
        $msg .= "   Đã gửi:  <b>{$outboxStats['sent']}</b>\n";
        $msg .= "   Lỗi:     <b>{$outboxStats['failed']}</b>\n\n";
        $msg .= "⚙️ Worker: {$workerStatus}";

        $this->telegram->sendTo($chatId, $msg);
    }

    /**
     * /broadcast <nội dung> — Push thông báo tới tất cả user đã link (qua Outbox)
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
                "📣 <b>BROADCAST</b>\n\nCú pháp:\n<code>/broadcast &lt;nội dung&gt;</code>\n\nVí dụ:\n<code>/broadcast 🔥 Flash sale 50% trong 24h!</code>"
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
            "✅ Đã xếp hàng <b>{$count}</b> tin nhắn vào Outbox.\nWorker cron sẽ gửi trong vài phút tới."
        );
    }

    /**
     * /maintenance on|off — Bật/tắt bảo trì hệ thống
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
                "🔧 <b>BẢO TRÌ HỆ THỐNG</b>\n\n"
                . "<code>/maintenance on</code>  — Bật bảo trì\n"
                . "<code>/maintenance off</code> — Tắt bảo trì"
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
                $this->telegram->sendTo($chatId, "🔒 <b>Đã bật bảo trì hệ thống.</b>\nWebsite hiển thị trang bảo trì cho người dùng.");
            } else {
                $svc->clearNow();
                $this->telegram->sendTo($chatId, "✅ <b>Đã tắt bảo trì hệ thống.</b>\nWebsite hoạt động bình thường trở lại.");
            }
        } catch (Throwable $e) {
            $this->telegram->sendTo($chatId, "❌ Lỗi: " . $e->getMessage());
        }
    }

    /**
     * /setbank <Ngân hàng>|<STK>|<Chủ TK> — Cập nhật thông tin ngân hàng nhanh
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
            . "🔢 " . htmlspecialchars($bankAcc) . "\n"
            . "👤 " . htmlspecialchars($bankOwner)
        );
    }

    // =========================================================
    //  Standalone: Shadow Account Management
    // =========================================================

    /**
     * Resolve linked user — tự động tạo Shadow Account nếu chưa có
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
            $this->telegram->sendTo($chatId, "❌ Không thể khởi tạo tài khoản. Vui lòng thử lại hoặc liên hệ hỗ trợ.");
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
     * Nếu chưa có link → tạo Shadow Account
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

    /**
     * Per-user rate limit — tồn tại giữa các Webhook request nhờ file
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
     * Cooldown check — Trả về true nếu ngoài cooldown (được phép)
     */
    private function checkAndSetCooldown(string $key, int $seconds): bool
    {
        $dir = TelegramConfig::cooldownDir();
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);

        $file = $dir . '/' . md5($key) . '.ts';
        $now = time();

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
        $now = time();
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

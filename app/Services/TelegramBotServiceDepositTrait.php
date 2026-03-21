<?php

/**
 * TelegramBotServiceDepositTrait
 *
 * Xử lý toàn bộ luồng nạp tiền:
 *  - Menu chọn phương thức nạp (Bank / Binance)
 *  - Nạp ngân hàng qua SePay (/deposit)
 *  - Nạp Binance Pay (/binance, cbBinanceCheck)
 *  - Quản lý session file-based cho Bank và Binance
 */
trait TelegramBotServiceDepositTrait
{
    // =========================================================
    //  Menu chọn phương thức nạp tiền
    // =========================================================

    private function showDepositMethodMenu(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $siteConfig = Config::getSiteConfig();
        $methods = $this->depositService->getAvailableMethods($siteConfig);

        $bankEnabled = false;
        $binanceEnabled = false;
        foreach ($methods as $method) {
            $code = strtolower(trim((string) ($method['code'] ?? '')));
            $enabled = !empty($method['enabled']);
            if ($code === DepositService::METHOD_BANK_SEPAY) {
                $bankEnabled = $enabled;
            }
            if ($code === DepositService::METHOD_BINANCE) {
                $binanceEnabled = $enabled;
            }
        }

        $bankLabel = $bankEnabled ? '🏦 Ngân hàng (VND)' : '🏦 Ngân hàng (Bảo trì)';
        $binanceLabel = $binanceEnabled ? '💳 Binance Pay (USD)' : '💳 Binance Pay (Bảo trì)';

        $msg = "💳 <b>CHỌN PHƯƠNG THỨC NẠP TIỀN</b>\n\n";
        $msg .= "1️⃣ <b>Ngân hàng (VND)</b> — Chuyển khoản nội địa qua SePay.\n";
        $msg .= "2️⃣ <b>Binance Pay (USDT)</b> — Nạp qua tài khoản Binance Funding.\n\n";

        if (!$bankEnabled && !$binanceEnabled) {
            $msg .= "🔴 Tất cả kênh nạp tiền đang bảo trì.\n";
        } elseif (!$binanceEnabled) {
            $msg .= "🔴 Binance Pay hiện đang bảo trì, tạm thời chưa nhận nạp.\n";
        } elseif (!$bankEnabled) {
            $msg .= "🔴 Ngân hàng hiện đang bảo trì, tạm thời chưa nhận nạp.\n";
        }

        $msg .= "\n👇 Vui lòng chọn phương thức phù hợp:";

        $rows = [
            [
                ['text' => $bankLabel, 'callback_data' => 'deposit_bank'],
                ['text' => $binanceLabel, 'callback_data' => 'binance_start'],
            ],
            [['text' => '⬅️ Quay lại', 'callback_data' => 'back_home']],
        ];

        $markup = TelegramService::buildInlineKeyboard($rows);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
            return;
        }
        $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
    }

    // =========================================================
    //  Nạp tiền ngân hàng — /deposit
    // =========================================================

    /**
     * /deposit <số_tiền> — Tạo mã chuyển khoản ngân hàng (TTL 5 phút)
     */
    private function cmdDeposit(string $chatId, int $telegramId, array $args, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        // Kiểm tra trạng thái kênh Bank
        $siteConfig = Config::getSiteConfig();
        if ((int) ($siteConfig['bank_pay_enabled'] ?? 1) !== 1) {
            $this->telegram->sendTo($chatId, "🔴 <b>Kênh nạp ngân hàng đang bảo trì.</b>\nVui lòng quay lại sau hoặc sử dụng Binance Pay.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [['text' => '💳 Thử Binance Pay', 'callback_data' => 'binance_start']],
                    [$this->backHomeButton()],
                ]),
            ]);
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

        if ($amount > DepositService::MAX_AMOUNT) {
            $this->telegram->sendTo(
                $chatId,
                "⚠️ Số tiền nạp tối đa <b>" . number_format(DepositService::MAX_AMOUNT) . "đ</b>."
            );
            return;
        }

        $result = $this->depositService->createBankDeposit($user, $amount, $siteConfig, SourceChannelHelper::BOTTELE);

        if (!$result['success']) {
            $this->writeLog("❌ Nạp tiền thất bại: " . ($result['message'] ?? 'Lỗi không xác định'), 'WARN', 'OUTGOING', 'DEPOSIT', ['user_id' => $user['id'], 'amount' => $amount]);
            $msg = "❌ " . htmlspecialchars((string) ($result['message'] ?? 'Không bắt đầu được phiên nạp tiền.'));
            $markup = TelegramService::buildInlineKeyboard([
                [['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu']],
                [$this->backHomeButton()],
            ]);
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
            return;
        }

        $d = $result['data'];
        $this->writeLog("💳 " . ($user['username'] ?? 'User') . " bắt đầu nạp " . number_format($amount) . "đ (Mã: " . ($d['deposit_code'] ?? '???') . ")", 'INFO', 'INCOMING', 'DEPOSIT');
        $qrUrl = trim((string) ($d['qr_url'] ?? ''));
        $ttlSeconds = max(60, (int) ($d['ttl_seconds'] ?? 300));

        $message = $this->buildDepositInstructionMessage($d, $ttlSeconds);
        $depositCode = (string) ($d['deposit_code'] ?? '');
        $depositKeyboard = $this->buildPayNowBackKeyboard($qrUrl, $depositCode);
        $photoSent = false;

        // Nếu nạp từ callback (menu), xóa menu cũ cho sạch chat
        if ($messageId > 0) {
            $this->telegram->deleteMessage($chatId, $messageId);
        }

        if ($qrUrl !== '' && str_starts_with($qrUrl, 'http')) {
            $telegramQrUrl = $this->toTelegramQrUrl($qrUrl);
            $photoSent = $this->telegram->sendPhotoTo($chatId, $telegramQrUrl, $message, ['reply_markup' => $depositKeyboard]);

            if (!$photoSent && $telegramQrUrl !== $qrUrl) {
                $photoSent = $this->telegram->sendPhotoTo($chatId, $qrUrl, $message, ['reply_markup' => $depositKeyboard]);
            }
        }

        if (!$photoSent) {
            if ($qrUrl !== '' && str_starts_with($qrUrl, 'http')) {
                $message .= "\n\nQR: " . htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8');
            }
            $this->telegram->sendTo($chatId, $message, ['reply_markup' => $depositKeyboard]);
        }
    }

    /**
     * Ưu tiên QR template đầy đủ khi gửi Telegram để người dùng nhìn rõ thông tin.
     */
    private function toTelegramQrUrl(string $qrUrl): string
    {
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

    // =========================================================
    //  Deposit Input Mode — nhập số tiền qua chat
    // =========================================================

    private function handleDepositAmountInput(string $chatId, int $telegramId, string $text): bool
    {
        if (!$this->isDepositInputMode($telegramId)) {
            return false;
        }

        $text = trim($text);
        if ($text === '') {
            $this->telegram->sendTo($chatId, "⚠️ Vui lòng nhập số tiền cần nạp (tối thiểu <b>" . number_format(DepositService::MIN_AMOUNT) . "đ</b>).\n\nVí dụ: <code>50000</code>", [
                'reply_markup' => $this->buildDepositQuickMarkup()
            ]);
            return true;
        }

        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower(trim($text));

        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'cancel', 'quay lại', 'quay lai'], true)) {
            $session = $this->getDepositInputSession($telegramId);
            $messageId = (int) ($session['message_id'] ?? 0);
            $this->clearDepositInputMode($telegramId);
            $this->showDepositMethodMenu($chatId, $telegramId, $messageId);
            return true;
        }

        $amount = (int) preg_replace('/\D/', '', $text);
        if ($amount <= 0) {
            $this->telegram->sendTo($chatId, "⚠️ Số tiền không hợp lệ. Vui lòng nhập số, ví dụ: <code>50000</code>.", [
                'reply_markup' => $this->buildDepositQuickMarkup()
            ]);
            return true;
        }

        $minAmount = DepositService::MIN_AMOUNT;
        if ($amount < $minAmount) {
            $this->telegram->sendTo($chatId, "⚠️ Số tiền nạp tối thiểu là <b>" . number_format($minAmount) . "đ</b>.\n\nVui lòng nhập lại số tiền ≥ " . number_format($minAmount) . "đ.", [
                'reply_markup' => $this->buildDepositQuickMarkup()
            ]);
            return true;
        }

        $this->clearDepositInputMode($telegramId);
        $this->cmdDeposit($chatId, $telegramId, [(string) $amount]);
        return true;
    }

    /**
     * Hiển thị menu nạp tiền với nút nhanh và nhãn số tiền tối thiểu.
     */
    private function startDepositInputMode(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $this->setDepositInputMode($telegramId, 300, $messageId);

        $minFormatted = number_format(DepositService::MIN_AMOUNT);

        $msg = "💳 <b>NẠP TIỀN</b>\n\n";
        $msg .= "📌 Nạp tối thiểu: <b>{$minFormatted}đ</b>\n";
        $msg .= "👇 Chọn nhanh hoặc nhập số tiền bạn muốn nạp:";

        $markup = $this->buildDepositQuickMarkup();

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /**
     * Build quick-deposit keyboard — đồng bộ amounts với cấu hình DB.
     *
     * @return array<string,mixed>
     */
    private function buildDepositQuickMarkup(): array
    {
        // Lấy bonus tiers từ DB để đồng bộ các nút nhanh
        $siteConfig = Config::getSiteConfig();
        $bonusTiers = $this->depositService->getBonusTiers($siteConfig);

        // Lọc lấy 4 mức tiêu biểu (giữ giá trị dưới 1 triệu, lấy tối đa 4 cái)
        $amounts = [];
        foreach ($bonusTiers as $tier) {
            $a = (int) ($tier['amount'] ?? 0);
            if ($a >= DepositService::MIN_AMOUNT && $a <= 1000000) {
                $amounts[] = $a;
            }
        }
        // Fallback nếu DB không có tiers
        if (empty($amounts)) {
            $amounts = [20000, 50000, 100000, 500000];
        }
        // Đảm bảo đủ 4 nút, bổ sung mặc định nếu thiếu
        $defaults = [20000, 50000, 100000, 500000];
        $amounts = array_unique(array_merge($amounts, $defaults));
        sort($amounts);
        $amounts = array_slice($amounts, 0, 4);

        $quickButtons = [];
        foreach ($amounts as $a) {
            if ($a >= 1000000) {
                $label = number_format($a / 1000000, 0) . 'M';
            } elseif ($a >= 1000) {
                $label = number_format($a / 1000, 0) . 'K';
            } else {
                $label = (string) $a;
            }
            $quickButtons[] = ['text' => $label, 'callback_data' => 'deposit_' . $a];
        }

        return TelegramService::buildInlineKeyboard([
            $quickButtons,
            [['text' => '⬅️ Quay lại', 'callback_data' => 'deposit_menu']],
        ]);
    }

    private function depositInputDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'deposit_input';
    }

    private function depositInputFile(int $telegramId): string
    {
        return $this->depositInputDir() . DIRECTORY_SEPARATOR . $telegramId . '.json';
    }

    private function setDepositInputMode(int $telegramId, int $ttl = 300, int $messageId = 0): void
    {
        $dir = $this->depositInputDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $payload = [
            'created_at' => $now,
            'expires_at' => $now + max(60, $ttl),
            'message_id' => $messageId,
        ];

        @file_put_contents($this->depositInputFile($telegramId), json_encode($payload), LOCK_EX);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getDepositInputSession(int $telegramId): ?array
    {
        $file = $this->depositInputFile($telegramId);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            @unlink($file);
            return null;
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $expiresAt = (int) ($data['expires_at'] ?? 0);
        if ($expiresAt <= 0 || $expiresAt < $now) {
            @unlink($file);
            return null;
        }

        return $data;
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

    // =========================================================
    //  Binance Pay — /binance & cbBinanceCheck
    // =========================================================

    private function handleBinanceInput(string $chatId, int $telegramId, string $text): bool
    {
        $session = $this->getBinanceSession($telegramId);
        if (!$session) {
            return false;
        }

        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim($text), 'UTF-8')
            : strtolower(trim($text));

        if (in_array($normalized, ['hủy', 'huy', 'cancel', 'thoát', 'thoat', 'quay lại', 'quay lai'], true)) {
            $messageId = (int) ($session['message_id'] ?? 0);
            $this->clearBinanceSession($telegramId);
            $this->showDepositMethodMenu($chatId, $telegramId, $messageId);
            return true;
        }

        $step = (string) ($session['step'] ?? '');

        // Step 1: Nhập số tiền USDT
        if ($step === 'await_amount') {
            $amountRaw = preg_replace('/[^0-9.]/', '', $text);
            $amount = (float) $amountRaw;
            if ($amount <= 0) {
                $this->telegram->sendTo($chatId, '⚠️ Số tiền không hợp lệ. Vui lòng nhập số USDT cần nạp (ví dụ: <code>10</code>).', [
                    'reply_markup' => $this->buildBinanceAmountKeyboard(),
                ]);
                return true;
            }

            // Chuyển sang bước hỏi UID
            $this->setBinanceSession($telegramId, [
                'step' => 'await_uid',
                'amount' => $amount,
                'message_id' => (int) ($session['message_id'] ?? 0)
            ], 300);

            $this->telegram->sendTo($chatId, '💵 Số tiền: <b>$' . number_format($amount, 2) . ' USDT</b>' . "\n\n" .
                "👉 Bây giờ hãy nhập <b>Binance UID</b> của bạn để hệ thống tự động cộng tiền.");
            return true;
        }

        // Step 2: Nhập UID
        if ($step === 'await_uid') {
            $uid = preg_replace('/\D/', '', $text);
            if (!preg_match('/^\d{4,20}$/', (string) $uid)) {
                $this->telegram->sendTo($chatId, '⚠️ UID Binance không hợp lệ. Vui lòng nhập UID gồm 4–20 chữ số.');
                return true;
            }

            $amount = (float) ($session['amount'] ?? 0);
            $messageId = (int) ($session['message_id'] ?? 0);

            $this->clearBinanceSession($telegramId);
            $this->cmdBinance($chatId, $telegramId, [(string) $amount, (string) $uid], $messageId);
            return true;
        }

        return false;
    }

    /**
     * Bắt đầu luồng nạp Binance: Bước 1 - Hỏi số tiền
     */
    protected function startBinanceInputMode(string $chatId, int $telegramId, int $messageId = 0): void
    {
        $this->setBinanceSession($telegramId, [
            'step' => 'await_amount',
            'message_id' => $messageId
        ], 300);

        $siteConfig = Config::getSiteConfig();
        $rate = max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000));
        $minUsdt = max(1.0, round(DepositService::MIN_AMOUNT / $rate, 0));
        $minUsdtLabel = ((float) floor($minUsdt) === (float) $minUsdt)
            ? number_format($minUsdt, 0)
            : number_format($minUsdt, 2, '.', '');

        $msg = "💳 <b>BINANCE PAY (USDT)</b>\n\n";
        $msg .= "📌 Nạp tối thiểu: <b>$" . $minUsdtLabel . "</b>\n";
        $msg .= "👇 Vui lòng chọn nhanh hoặc nhập số USDT bạn muốn nạp:";

        $markup = $this->buildBinanceAmountKeyboard();
        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
            return;
        }

        $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
    }

    private function cmdBinance(string $chatId, int $telegramId, array $args, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $siteConfig = Config::getSiteConfig();

        // Kiểm tra admin có bật Binance không
        if ((int) ($siteConfig['binance_pay_enabled'] ?? 0) !== 1) {
            $msg = "🔴 <b>Binance Pay đang bảo trì.</b>\nVui lòng quay lại sau hoặc sử dụng Ngân hàng.";
            $markup = TelegramService::buildInlineKeyboard([
                [['text' => '🏦 Thử Ngân hàng', 'callback_data' => 'deposit_bank']],
                [$this->backHomeButton()],
            ]);
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
            return;
        }

        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            $markup = TelegramService::buildInlineKeyboard([
                [['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu']],
                [$this->backHomeButton()],
            ]);
            $this->telegram->editOrSend($chatId, $messageId, '🔴 Binance Pay chưa sẵn sàng. Vui lòng liên hệ admin.', $markup);
            return;
        }

        // $args[0] = số USDT, $args[1] = payer UID từ session (không lưu DB)
        $amountRaw = preg_replace('/[^0-9.]/', '', (string) ($args[0] ?? '0'));
        $usdAmount = (float) $amountRaw;
        $payerUid = trim((string) ($args[1] ?? ''));

        // Bước 1: Hỏi số tiền nếu chưa có
        if ($usdAmount <= 0) {
            $this->startBinanceInputMode($chatId, $telegramId, $messageId);
            return;
        }

        // Bước 2: Hỏi UID nếu chưa có — gửi kèm ảnh QR từ admin setting
        if ($payerUid === '' || !preg_match('/^\d{4,20}$/', $payerUid)) {
            $this->setBinanceSession($telegramId, [
                'step' => 'await_uid',
                'amount' => $usdAmount,
                'message_id' => $messageId
            ], 300);

            $msg = "💵 Số tiền: <b>$" . number_format($usdAmount, 2) . " USDT</b>\n\n" .
                "👉 Vui lòng nhập <b>Binance UID</b> của bạn để tiếp tục.\n" .
                "<i>Scan QR để tìm UID hoặc vào Binance App → Profile → Copy UID</i>";

            $this->sendTelegramMediaOrText(
                $chatId,
                $messageId,
                (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
                $msg
            );
            return;
        }

        // Bước 3: Tạo giao dịch — không lưu UID vào DB, đồng bộ web
        $this->clearBinanceSession($telegramId);
        $result = $this->depositService->createBinanceDeposit(
            $user,
            $usdAmount,
            $payerUid,
            $siteConfig,
            SourceChannelHelper::BOTTELE
        );

        if (empty($result['success'])) {
            $markup = TelegramService::buildInlineKeyboard([
                [['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu']],
                [$this->backHomeButton()],
            ]);
            $this->telegram->editOrSend($chatId, $messageId, '❌ Không thể tạo phiên Binance: ' . htmlspecialchars((string) ($result['message'] ?? 'Lỗi không xác định'), ENT_QUOTES, 'UTF-8'), $markup);
            return;
        }

        $d = (array) ($result['data'] ?? []);
        $depositCode = (string) ($d['deposit_code'] ?? '');
        $usdtAmount = (float) ($d['usdt_amount'] ?? 0);
        $receiverUid = (string) ($d['binance_uid'] ?? '');
        $expiresAt = trim((string) ($d['expires_at'] ?? ''));

        $msg = "🟡 <b>BINANCE PAY — THÔNG TIN THANH TOÁN</b>\n\n";
        $msg .= "📬 Pay to Binance ID: <b>" . htmlspecialchars($receiverUid, ENT_QUOTES, 'UTF-8') . "</b>\n\n";
        $msg .= "📋 Mã giao dịch: <code>" . htmlspecialchars($depositCode, ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "👤 UID: <code>" . htmlspecialchars($payerUid, ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "💵 Send EXACTLY: <b>$" . number_format($usdtAmount, 2, '.', '') . " USDT</b>\n";
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>" . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . "</b>\n";
        }
        $msg .= "━━━━━━━━━━━━━━\n";
        $telegramBinanceWarning = trim((string) ($siteConfig['deposit_warning_binance'] ?? ''));
        if ($telegramBinanceWarning !== '') {
            $telegramBinanceWarning = str_replace(
                ['{amount}', '{uid}'],
                ['<b>' . number_format($usdtAmount, 2, '.', '') . ' USDT</b>', '<b>' . htmlspecialchars($receiverUid, ENT_QUOTES, 'UTF-8') . '</b>'],
                $telegramBinanceWarning
            );
            $msg .= "\n⚠️ " . $telegramBinanceWarning;
        } else {
            $msg .= "\n⚠️ Lưu ý: nhập chính xác <b>UID Binance của bạn</b> và chuyển đúng <b>$" . number_format($usdtAmount, 2, '.', '') . " USDT</b> thì hệ thống mới auto match được.";
        }

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🔍 Kiểm tra thanh toán', 'callback_data' => 'bin_check_' . $depositCode],
                ['text' => '❌ Hủy giao dịch', 'callback_data' => 'cancel_dep_' . $depositCode],
            ],
        ]);

        $this->sendTelegramMediaOrText(
            $chatId,
            0,
            (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
            $msg,
            $markup
        );
    }

    private function cbBinanceCheck(string $chatId, int $telegramId, string $depositCode, int $messageId = 0, string $callbackId = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '🔗 Chưa liên kết tài khoản.', true);
            }
            return;
        }

        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findByCode($depositCode);
        if (!$deposit || (int) ($deposit['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '❌ Không thấy phiên Binance.', true);
            }
            return;
        }

        $method = strtolower(trim((string) ($deposit['method'] ?? '')));
        if ($method !== DepositService::METHOD_BINANCE) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⚠️ Đây không phải lệnh Binance.', true);
            }
            return;
        }

        if ($depositModel->isLogicallyExpired($deposit)) {
            $depositModel->markExpired();
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⌛ Lệnh Binance đã hết hạn.', true);
            }
            return;
        }

        $siteConfig = Config::getSiteConfig();

        if (strtolower((string) ($deposit['status'] ?? '')) === 'completed') {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '✅ Thanh toán đã xác nhận.', true);
            }
            $msgSuccess = $this->buildBinanceSuccessMessage($user, $siteConfig);
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            $this->sendTelegramMediaOrText(
                $chatId,
                0,
                (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
                $msgSuccess
            );
            return;
        }

        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '🚫 Binance tạm dừng, thử lại sau.', true);
            }
            return;
        }

        $tx = $binanceService->findMatchingTransaction($deposit);
        if (!$tx) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⏳ Chưa thấy giao dịch. Thử lại sau 10-15s.', true);
            }
            return;
        }

        $result = $binanceService->processTransaction($tx, $deposit, $user);
        if (!empty($result['success'])) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '🎉 Thanh toán Binance thành công!', true);
            }
            $freshUser = $this->userModel->findById((int) ($user['id'] ?? 0));
            $msg = $this->buildBinanceSuccessMessage($freshUser ?? $user, $siteConfig);
            if ($messageId > 0)
                $this->telegram->deleteMessage($chatId, $messageId);
            $this->sendTelegramMediaOrText(
                $chatId,
                0,
                (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
                $msg
            );
            return;
        }

        if ($callbackId !== '') {
            $this->telegram->answerCallbackQuery(
                $callbackId,
                '⚠️ Chưa xác minh được giao dịch.',
                true
            );
        }
    }

    /**
     * Format thông báo thành công Binance.
     *
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     */
    private function buildBinanceSuccessMessage(array $user, array $siteConfig): string
    {
        $balanceVnd = (int) ($user['money'] ?? 0);
        $rate = max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000));
        $balanceUsd = number_format($balanceVnd / $rate, 2, '.', ',');
        $balanceVndFmt = number_format($balanceVnd, 0, ',', '.');

        return "🟡 <b>BINANCE PAY — THANH TOÁN THÀNH CÔNG</b>\n\n"
            . "✅ Đã nhận tiền vào tài khoản!\n"
            . "💰 Số dư hiện tại: <b>\${$balanceUsd}</b> (~{$balanceVndFmt} VND)";
    }

    /**
     * @param array<string,mixed> $deposit
     */
    private function buildBinanceExpiredMessage(array $deposit): string
    {
        $depositCode = htmlspecialchars((string) ($deposit['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $usdtAmount = number_format((float) ($deposit['usdt_amount'] ?? 0), 2, '.', '');
        $payerUid = htmlspecialchars(trim((string) ($deposit['payer_uid'] ?? '')), ENT_QUOTES, 'UTF-8');
        $vndAmount = number_format((int) ($deposit['amount'] ?? 0), 0, ',', '.');

        $msg = "⌛ <b>BINANCE PAY ĐÃ HẾT HẠN</b>\n\n"
            . "📋 Mã giao dịch: <code>{$depositCode}</code>\n"
            . "💵 Số tiền: <b>$" . $usdtAmount . " USDT</b>\n";

        if ($payerUid !== '') {
            $msg .= "👤 UID Binance: <code>{$payerUid}</code>\n";
        }

        $msg .= "\n⚠️ Lệnh đã quá <b>5 phút</b> nên hệ thống tự hủy.\n";
        $msg .= "Nếu bạn đã chuyển tiền, vui lòng liên hệ hỗ trợ kèm <b>TXID</b>.";

        return $msg;
    }

    private function sendTelegramMediaOrText(string $chatId, int $messageId, string $imagePath, string $message, ?array $markup = null): void
    {
        $sendOptions = $markup ? ['reply_markup' => $markup] : [];
        $imageUrl = $this->resolveTelegramPublicImageUrl($imagePath);
        if ($imageUrl !== '' && $this->telegram->sendPhotoTo($chatId, $imageUrl, $message, $sendOptions)) {
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            return;
        }

        $localImagePath = $this->resolveTelegramLocalImagePath($imagePath);
        if ($localImagePath !== '' && class_exists('CURLFile') && $this->telegram->sendPhotoTo($chatId, new CURLFile($localImagePath), $message, $sendOptions)) {
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, $message, $markup);
    }

    private function resolveTelegramPublicImageUrl(string $imagePath): string
    {
        $resolvedPath = trim($imagePath);
        if ($resolvedPath === '') {
            return '';
        }

        $resolvedUrl = trim((string) asset($resolvedPath));
        if ($resolvedUrl === '') {
            return '';
        }

        if (!preg_match('~^https?://~i', $resolvedUrl)) {
            $baseUrl = rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/');
            if ($baseUrl === '') {
                return '';
            }
            $resolvedUrl = $baseUrl . '/' . ltrim($resolvedUrl, '/');
        }

        $host = strtolower(trim((string) parse_url($resolvedUrl, PHP_URL_HOST)));
        if ($host === '' || !$this->isTelegramReachableHost($host)) {
            return '';
        }

        return $resolvedUrl;
    }

    private function resolveTelegramLocalImagePath(string $imagePath): string
    {
        $resolvedPath = trim($imagePath);
        if ($resolvedPath === '' || preg_match('~^https?://~i', $resolvedPath)) {
            return '';
        }

        $urlPath = (string) (parse_url($resolvedPath, PHP_URL_PATH) ?? $resolvedPath);
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($urlPath, '/\\'));
        $appDir = trim((string) (defined('APP_DIR') ? APP_DIR : ''), '/\\');

        if ($appDir !== '') {
            $appDirPrefix = $appDir . DIRECTORY_SEPARATOR;
            if (stripos($normalizedPath, $appDirPrefix) === 0) {
                $normalizedPath = substr($normalizedPath, strlen($appDirPrefix));
            }
        }

        $projectRoot = dirname(__DIR__, 2);
        $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . $normalizedPath;
        return is_file($absolutePath) ? $absolutePath : '';
    }

    private function isTelegramReachableHost(string $host): bool
    {
        $normalizedHost = trim(strtolower($host), '[]');
        if ($normalizedHost === '' || $normalizedHost === 'localhost' || $normalizedHost === '127.0.0.1' || $normalizedHost === '::1') {
            return false;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($normalizedHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if (!str_contains($normalizedHost, '.')) {
            return false;
        }

        foreach (['.local', '.localhost', '.test', '.invalid'] as $suffix) {
            if (str_ends_with($normalizedHost, $suffix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build Binance amount keyboard — các nút USDT đồng bộ tỷ giá từ siteConfig.
     *
     * @return array<string,mixed>
     */
    private function buildBinanceAmountKeyboard(): array
    {
        $siteConfig = Config::getSiteConfig();
        $rate = max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000));
        $minUsdt = max(1.0, round(DepositService::MIN_AMOUNT / $rate, 0));

        // Quick amounts (USDT) - Sync with web tiers
        $quickAmounts = [1, 4, 8, 20];

        $buttons = [];
        foreach ($quickAmounts as $usdt) {
            $buttons[] = ['text' => '$' . $usdt, 'callback_data' => 'bin_amount_' . $usdt];
        }

        return TelegramService::buildInlineKeyboard([
            $buttons,
            [$this->backHomeButton()],
        ]);
    }

    // =========================================================
    //  Binance Session File-based
    // =========================================================

    private function binanceSessionDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'binance_session';
    }

    private function binanceSessionFile(int $telegramId): string
    {
        return $this->binanceSessionDir() . DIRECTORY_SEPARATOR . $telegramId . '.json';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function setBinanceSession(int $telegramId, array $payload, int $ttl = 300): void
    {
        $dir = $this->binanceSessionDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $payload['created_at'] = $now;
        $payload['expires_at'] = $now + max(60, $ttl);
        @file_put_contents($this->binanceSessionFile($telegramId), json_encode($payload), LOCK_EX);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getBinanceSession(int $telegramId): ?array
    {
        $file = $this->binanceSessionFile($telegramId);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        $payload = $raw ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            @unlink($file);
            return null;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        if ($expiresAt <= 0 || $expiresAt < $now) {
            @unlink($file);
            return null;
        }

        return $payload;
    }

    private function clearBinanceSession(int $telegramId): void
    {
        $file = $this->binanceSessionFile($telegramId);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

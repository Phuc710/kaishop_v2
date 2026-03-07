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

        $bankLabel = $bankEnabled ? '🏦 Ngân hàng (VND)' : '🏦 Ngân hàng 🔴 Bảo trì';
        $binanceLabel = $binanceEnabled ? '🟡 Binance Pay (USD)' : '🟡 Binance Pay (Bảo trì)';

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
    private function cmdDeposit(string $chatId, int $telegramId, array $args): void
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
                    [['text' => '🟡 Thử Binance Pay', 'callback_data' => 'binance_start']],
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
            $this->telegram->sendTo($chatId, "❌ " . htmlspecialchars((string) ($result['message'] ?? 'Không bắt đầu được phiên nạp tiền.')));
            return;
        }

        $d = $result['data'];
        $this->writeLog("💳 " . ($user['username'] ?? 'User') . " bắt đầu nạp " . number_format($amount) . "đ (Mã: " . ($d['deposit_code'] ?? '???') . ")", 'INFO', 'INCOMING', 'DEPOSIT');
        $qrUrl = trim((string) ($d['qr_url'] ?? ''));
        $ttlSeconds = max(60, (int) ($d['ttl_seconds'] ?? 300));

        $message = $this->buildDepositInstructionMessage($d, $ttlSeconds);
        $depositKeyboard = $this->buildPayNowBackKeyboard($qrUrl);
        $photoSent = false;

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
            $this->clearDepositInputMode($telegramId);
            $this->telegram->sendTo($chatId, "✅ Đã hủy thao tác nạp tiền.");
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
        $this->setDepositInputMode($telegramId);

        $minFormatted = number_format(DepositService::MIN_AMOUNT);

        $msg = "💳 <b>NẠP TIỀN VÀO VÍ</b>\n\n";
        $msg .= "📌 Nạp tối thiểu: <b>{$minFormatted}đ</b>\n\n";
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
            [['text' => '❌ Hủy', 'callback_data' => 'back_home']],
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
            $this->clearBinanceSession($telegramId);
            $this->telegram->sendTo($chatId, '✅ Đã hủy phiên nạp Binance.');
            return true;
        }

        $step = (string) ($session['step'] ?? '');

        if ($step === 'await_uid') {
            $uid = preg_replace('/\D/', '', $text);
            if (!preg_match('/^\d{4,20}$/', (string) $uid)) {
                $this->telegram->sendTo($chatId, '⚠️ UID Binance không hợp lệ. Vui lòng nhập UID gồm 4–20 chữ số.');
                return true;
            }

            // Lưu vào session, KHOONG lưu DB — đồng bộ web flow
            $this->setBinanceSession($telegramId, ['step' => 'await_amount', 'uid' => (string) $uid], 300);
            $this->telegram->sendTo($chatId, '✅ UID: <code>' . htmlspecialchars((string) $uid, ENT_QUOTES, 'UTF-8') . "</code>\n\nBây giờ hãy nhập số USDT cần nạp (ví dụ: <code>10</code>).", [
                'reply_markup' => $this->buildBinanceAmountKeyboard(),
            ]);
            return true;
        }

        if ($step === 'await_amount') {
            $amountRaw = preg_replace('/[^0-9.]/', '', $text);
            $amount = (float) $amountRaw;
            if ($amount <= 0) {
                $this->telegram->sendTo($chatId, '⚠️ Số tiền không hợp lệ. Ví dụ: <code>10</code> (USDT).', [
                    'reply_markup' => $this->buildBinanceAmountKeyboard(),
                ]);
                return true;
            }

            // Lấy UID từ session và truyền qua args[1] — không lưu DB
            $uid = (string) ($session['uid'] ?? '');
            $this->clearBinanceSession($telegramId);
            $this->cmdBinance($chatId, $telegramId, [(string) $amount, $uid]);
            return true;
        }

        return false;
    }

    private function cmdBinance(string $chatId, int $telegramId, array $args): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $siteConfig = Config::getSiteConfig();

        // Kiểm tra admin có bật Binance không
        if ((int) ($siteConfig['binance_pay_enabled'] ?? 0) !== 1) {
            $this->telegram->sendTo($chatId, "🔴 <b>Binance Pay đang bảo trì.</b>\nVui lòng quay lại sau hoặc sử dụng Ngân hàng.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [['text' => '🏦 Thử Ngân hàng', 'callback_data' => 'deposit_bank']],
                    [$this->backHomeButton()],
                ]),
            ]);
            return;
        }

        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            $this->telegram->sendTo($chatId, '🔴 Binance Pay chưa sẵn sàng. Vui lòng liên hệ admin.');
            return;
        }

        // $args[0] = số USDT, $args[1] = payer UID từ session (không lưu DB)
        $amountRaw = preg_replace('/[^0-9.]/', '', (string) ($args[0] ?? '0'));
        $usdAmount = (float) $amountRaw;
        $payerUid = trim((string) ($args[1] ?? ''));

        $rate = max(1, (int) ($siteConfig['binance_rate_vnd'] ?? 25000));
        $minUsdt = round(DepositService::MIN_AMOUNT / $rate, 2);

        // Bước 1: Hỏi UID nếu chưa có (mỗi lần giao dịch đều hỏi — đồng bộ web)
        if ($payerUid === '' || !preg_match('/^\d{4,20}$/', $payerUid)) {
            $this->setBinanceSession($telegramId, ['step' => 'await_uid'], 300);
            $this->telegram->sendTo(
                $chatId,
                "🟡 <b>BINANCE PAY</b>\n\n" .
                "Vui lòng nhập <b>Binance UID</b> của bạn.\n" .
                "<i>UID chỉ dùng cho giao dịch này, không bị lưu lại.</i>\n\n" .
                "Ví dụ: <code>12345678</code>"
            );
            return;
        }

        // Bước 2: Hỏi số tiền nếu chưa có
        if ($usdAmount <= 0) {
            $this->setBinanceSession($telegramId, ['step' => 'await_amount', 'uid' => $payerUid], 300);
            $minLabel = number_format($minUsdt, 2) . ' USDT';
            $this->telegram->sendTo($chatId, "💵 Nhập số USDT cần nạp (tối thiểu <b>{$minLabel}</b>).", [
                'reply_markup' => $this->buildBinanceAmountKeyboard(),
            ]);
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
            $this->telegram->sendTo($chatId, '❌ Không thể tạo phiên Binance: ' . htmlspecialchars((string) ($result['message'] ?? 'Lỗi không xác định'), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $d = (array) ($result['data'] ?? []);
        $depositCode = (string) ($d['deposit_code'] ?? '');
        $usdtAmount = (float) ($d['usdt_amount'] ?? 0);
        $receiverUid = (string) ($d['binance_uid'] ?? '');
        $noteText = trim((string) ($d['note_text'] ?? ''));
        $expiresAt = trim((string) ($d['expires_at'] ?? ''));

        $msg = "🟡 <b>BINANCE PAY — THÔNG TIN THANH TOÁN</b>\n\n";
        $msg .= "📋 Mã giao dịch: <code>" . htmlspecialchars($depositCode, ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "👤 UID của bạn: <code>" . htmlspecialchars($payerUid, ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "📬 UID nhận tiền: <code>" . htmlspecialchars($receiverUid, ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "💵 Số cần gửi: <b>" . number_format($usdtAmount, 2, '.', '') . " USDT</b>\n";
        if ($noteText !== '') {
            $msg .= "📝 Ghi chú: <code>" . htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>" . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . "</b>\n";
        }
        $msg .= "\n✅ Sau khi chuyển tiền, nhấn <b>Kiểm tra thanh toán</b> bên dưới.";

        $keyboard = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🔍 Kiểm tra thanh toán', 'callback_data' => 'bin_check_' . $depositCode],
                ['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu'],
            ],
            [$this->backHomeButton()],
        ]);

        $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $keyboard]);
    }

    private function cbBinanceCheck(string $chatId, int $telegramId, string $depositCode, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findByCode($depositCode);
        if (!$deposit || (int) ($deposit['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ Không tìm thấy phiên nạp Binance.');
            return;
        }

        $method = strtolower(trim((string) ($deposit['method'] ?? '')));
        if ($method !== DepositService::METHOD_BINANCE) {
            $this->telegram->editOrSend($chatId, $messageId, '⚠️ Phiên này không phải Binance Pay.');
            return;
        }

        if ($depositModel->isLogicallyExpired($deposit)) {
            $depositModel->markExpired();
            $this->telegram->editOrSend($chatId, $messageId, '⏰ Phiên nạp đã hết hạn. Vui lòng tạo phiên mới bằng lệnh /binance.');
            return;
        }

        $siteConfig = Config::getSiteConfig();

        if (strtolower((string) ($deposit['status'] ?? '')) === 'completed') {
            $this->telegram->editOrSend($chatId, $messageId, $this->buildBinanceSuccessMessage($user, $siteConfig));
            return;
        }

        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            $this->telegram->editOrSend($chatId, $messageId, '🔴 Binance Pay đang tạm dừng. Vui lòng thử lại sau.');
            return;
        }

        $tx = $binanceService->findMatchingTransaction($deposit);
        if (!$tx) {
            $retryKeyboard = TelegramService::buildInlineKeyboard([
                [['text' => '🔄 Kiểm tra lại', 'callback_data' => 'bin_check_' . $depositCode]],
                [['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu']],
            ]);
            $this->telegram->editOrSend($chatId, $messageId, '⏳ Chưa tìm thấy giao dịch hợp lệ. Vui lòng thử lại sau 10–15 giây.', $retryKeyboard);
            return;
        }

        $result = $binanceService->processTransaction($tx, $deposit, $user);
        if (!empty($result['success'])) {
            $freshUser = $this->userModel->findById((int) ($user['id'] ?? 0));
            $this->telegram->editOrSend($chatId, $messageId, $this->buildBinanceSuccessMessage($freshUser ?? $user, $siteConfig));
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, '❌ Chưa xác minh được giao dịch: ' . htmlspecialchars((string) ($result['message'] ?? 'Lỗi không xác định'), ENT_QUOTES, 'UTF-8'));
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

        return "✅ <b>ĐÃ NHẬN THANH TOÁN BINANCE!</b>\n\n"
            . "💰 Số dư hiện tại: <b>\${$balanceUsd}</b> (~{$balanceVndFmt} VND)";
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

        // Quick amounts (USDT), bắt đầu từ min
        $quickAmounts = [5, 10, 20, 50];
        // Loại bỏ mức thấp hơn min
        $quickAmounts = array_values(array_filter($quickAmounts, fn($v) => $v >= $minUsdt));
        if (empty($quickAmounts)) {
            $quickAmounts = [max(1, (int) $minUsdt), 10, 20, 50];
        }

        $buttons = [];
        foreach ($quickAmounts as $usdt) {
            $buttons[] = ['text' => '$' . $usdt, 'callback_data' => 'bin_amount_' . $usdt];
        }

        return TelegramService::buildInlineKeyboard([
            $buttons,
            [
                ['text' => '💳 Menu nạp tiền', 'callback_data' => 'deposit_menu'],
                $this->backHomeButton(),
            ],
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

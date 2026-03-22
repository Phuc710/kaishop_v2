<?php

/**
 * TelegramBotServiceShopTrait
 *
 * Xử lý luồng mua hàng:
 *  - Danh mục sản phẩm (cbCategory)
 *  - Chi tiết sản phẩm (cbProduct)
 *  - Xác nhận mua (cbBuyConfirm)
 *  - Thực hiện mua (cbDoBuy)
 *  - Nhập số lượng, thông tin KH, mã giảm giá (handlePurchaseInput)
 *  - Quản lý session file-based cho purchase
 */
trait TelegramBotServiceShopTrait
{
    // =========================================================
    //  Purchase Input Mode
    // =========================================================

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
        $this->clearBinanceSession($telegramId);

        $dir = $this->purchaseInputDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $ttl = TelegramConfig::purchaseSessionTtl();
        $data['created_at'] = (int) ($data['created_at'] ?? $now);
        $data['updated_at'] = $now;
        $data['expires_at'] = $now + $ttl;
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

        $ttl = TelegramConfig::purchaseSessionTtl();
        $createdAt = (int) ($data['created_at'] ?? 0);
        $expiresAt = (int) ($data['expires_at'] ?? 0);
        $now = $this->timeService ? $this->timeService->nowTs() : time();

        if ($expiresAt <= 0 && $createdAt > 0) {
            $expiresAt = $createdAt + $ttl;
            $data['expires_at'] = $expiresAt;
        }

        if ($expiresAt > 0 && $now >= $expiresAt) {
            $this->clearPurchaseSession($telegramId);
            return null;
        }

        // Sliding expiration
        $data['updated_at'] = $now;
        $data['expires_at'] = $now + $ttl;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data;
    }

    private function purchaseSessionTimeoutText(int $telegramId = 0): string
    {
        $ttl = TelegramConfig::purchaseSessionTtl();
        $minutes = (int) ceil($ttl / 60);
        return $this->tgChoice(
            $telegramId,
            "⏰ <b>Giao dịch hết hạn!</b>\nPhiên mua hàng của bạn đã quá {$minutes} phút và tự động bị hủy.",
            "⏰ <b>Session expired!</b>\nYour purchase session was inactive for more than {$minutes} minutes and was cancelled automatically."
        );
    }

    private function clearPurchaseSession(int $telegramId): void
    {
        $file = $this->purchaseInputFile($telegramId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function resolvePurchaseMessageId(int $messageId = 0, ?array $session = null): int
    {
        if ($messageId > 0) {
            return $messageId;
        }
        return (int) ($session['message_id'] ?? 0);
    }

    private function attachPurchaseMessageId(array $session, int $messageId): array
    {
        if ($messageId > 0) {
            $session['message_id'] = $messageId;
        }
        return $session;
    }

    private function binanceSessionDir(): string
    {
        return TelegramConfig::rateDir() . DIRECTORY_SEPARATOR . 'binance_session';
    }

    private function binanceSessionFile(int $telegramId): string
    {
        return $this->binanceSessionDir() . DIRECTORY_SEPARATOR . $telegramId . '.json';
    }

    private function setBinanceSession(int $telegramId, array $data, int $ttl = 300): void
    {
        // Don't clear purchase session! We need it for UID-before-Order flow.

        $dir = $this->binanceSessionDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $ttl = max(60, $ttl);
        $data['created_at'] = (int) ($data['created_at'] ?? $now);
        $data['updated_at'] = $now;
        $data['ttl_seconds'] = (int) ($data['ttl_seconds'] ?? $ttl);
        $data['expires_at'] = $now + (int) $data['ttl_seconds'];

        @file_put_contents($this->binanceSessionFile($telegramId), json_encode($data), LOCK_EX);
    }

    private function getBinanceSession(int $telegramId): ?array
    {
        $file = $this->binanceSessionFile($telegramId);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if (!$raw) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $now = $this->timeService ? $this->timeService->nowTs() : time();
        $expiresAt = (int) ($data['expires_at'] ?? 0);
        if ($expiresAt > 0 && $now >= $expiresAt) {
            $this->clearBinanceSession($telegramId);
            return null;
        }

        $data['updated_at'] = $now;
        $data['expires_at'] = $now + max(60, (int) ($data['ttl_seconds'] ?? 300));
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return $data;
    }

    private function isBinanceInputMode(int $telegramId): bool
    {
        return is_file($this->binanceSessionFile($telegramId));
    }

    private function clearBinanceSession(int $telegramId): void
    {
        $file = $this->binanceSessionFile($telegramId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function formatCurrency(int $vnd, int $telegramId): string
    {
        if ($this->isTelegramEnglish($telegramId)) {
            $rate = TelegramConfig::binanceRate();
            $usd = $vnd / $rate;
            return '$' . number_format($usd, 2, '.', ',');
        }
        return number_format($vnd, 0, ',', '.') . 'đ';
    }

    private function sendTelegramMediaOrText(string $chatId, int $messageId, string $mediaPath, string $message, ?array $markup = null): void
    {
        $mediaPath = trim($mediaPath);
        $sent = false;

        if ($mediaPath !== '') {
            $photo = $mediaPath;
            if (!str_starts_with($photo, 'http://') && !str_starts_with($photo, 'https://')) {
                $photo = rtrim((string) BASE_URL, '/') . '/' . ltrim($photo, '/');
            }

            if (!str_contains($photo, 'localhost') && !str_contains($photo, '127.0.0.1')) {
                $options = [];
                if ($markup !== null) {
                    $options['reply_markup'] = $markup;
                }
                $sent = $this->telegram->sendPhotoTo($chatId, $photo, $message, $options);
            }
        }

        if ($sent) {
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, $message, $markup);
    }

    private function findLatestPendingTelegramOrderId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $orders = $this->orderModel->getUserVisibleOrders($userId, [], 0, 10);
        foreach ($orders as $order) {
            if ((string) ($order['status'] ?? '') !== 'pending') {
                continue;
            }
            if ((string) ($order['payment_status'] ?? '') !== 'pending') {
                continue;
            }

            return (int) ($order['id'] ?? 0);
        }

        return 0;
    }

    // =========================================================
    //  Xử lý input mua hàng
    // =========================================================

    private function handleBinanceInput(string $chatId, int $telegramId, string $text): bool
    {
        $session = $this->getBinanceSession($telegramId);
        if (!$session) {
            return false;
        }

        $text = trim($text);
        if ($text === '') {
            return true;
        }

        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower($text);

        // Standard commands for canceling or going back
        if (in_array($normalized, ['hủy', 'huy', 'cancel', 'thoát', 'thoat', 'back', 'quay lại', 'quay lai'], true)) {
            $messageId = (int) ($session['message_id'] ?? 0);
            $purpose = (string) ($session['purpose'] ?? '');
            $this->clearBinanceSession($telegramId);

            // For order payment, go back to order details or main menu
            if ($purpose === 'order_payment') {
                $buySession = $this->getPurchaseSession($telegramId);
                if ($buySession && isset($buySession['prod_id'], $buySession['qty'])) {
                    $this->cbBuyConfirm(
                        $chatId,
                        $telegramId,
                        (int) $buySession['prod_id'],
                        (int) $buySession['qty'],
                        $buySession['info'] ?? null,
                        $messageId,
                        (string) ($buySession['binance_uid'] ?? '')
                    );
                } else {
                    $this->showMainMenu($chatId, $telegramId, '', false, 0);
                }
            } else {
                $this->showMainMenu($chatId, $telegramId, '', false, 0);
            }
            return true;
        }

        $step = (string) ($session['step'] ?? '');

        // Step: Await Binance UID
        if ($step === 'await_uid') {
            $uid = preg_replace('/\D/', '', $text);
            if (!preg_match('/^\d{4,20}$/', (string) $uid)) {
                // User typed invalid UID -> send NEW message (No edit per user request)
                $this->telegram->sendTo($chatId, $this->buildBinanceUidPrompt(true), [
                    'reply_markup' => $this->buildBinanceUidMarkup()
                ]);
                return true;
            }

            $messageId = (int) ($session['message_id'] ?? 0);
            $purpose = (string) ($session['purpose'] ?? '');

            // No longer saving UID to DB as requested by user. 
            // We pass it to the next step via temporary variables.

            if ($purpose === 'link_uid_before_buy') {
                $this->clearBinanceSession($telegramId);

                // Retrieve purchase session to know where to redirect back
                $buySession = $this->getPurchaseSession($telegramId);
                if ($buySession && isset($buySession['prod_id'], $buySession['qty'])) {
                    // Pass 0 to force new message after text input
                    $this->cbBuyConfirm($chatId, $telegramId, (int) $buySession['prod_id'], (int) $buySession['qty'], $buySession['info'] ?? null, 0, $uid);
                } else {
                    $this->showMainMenu($chatId, $telegramId, '✅ Linked successfully!', false, 0);
                }
                return true;
            }

            if ($purpose === 'order_payment') {
                $orderId = (int) ($session['order_id'] ?? 0);
                $this->clearBinanceSession($telegramId);

                if ($orderId > 0) {
                    $this->cbOrderPayBinance($chatId, $telegramId, $orderId, 0, $uid);
                } else {
                    // If order doesn't exist yet, proceed to create it
                    $buySession = $this->getPurchaseSession($telegramId);
                    if ($buySession && isset($buySession['prod_id'], $buySession['qty'])) {
                        $this->cbDoBuy($chatId, $telegramId, (int) $buySession['prod_id'], (int) $buySession['qty'], 0, $uid);
                    } else {
                        $this->showMainMenu($chatId, $telegramId, '✅ Linked successfully!', false, 0);
                    }
                }
                return true;
            }

            $this->clearBinanceSession($telegramId);
            return false;
        }

        return false;
    }

    private function handlePurchaseInput(string $chatId, int $telegramId, string $text): bool
    {
        $file = $this->purchaseInputFile($telegramId);
        $wasInMode = is_file($file);

        $session = $this->getPurchaseSession($telegramId);
        if (!$session) {
            if ($wasInMode) {
                $this->clearPurchaseSession($telegramId);
            }
            return false;
        }
        $messageId = 0; // Always send new message for text input responses as requested by user

        $text = trim($text);
        if ($text === '') {
            return true;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'cancel', 'back', 'quay lại', 'quay lai'], true)) {
            $prodId = (int) ($session['prod_id'] ?? 0);
            $this->clearPurchaseSession($telegramId);
            $this->showCategoryListForProduct($chatId, $telegramId, $prodId, $messageId);
            return true;
        }

        $step = $session['step'] ?? '';
        $prodId = (int) ($session['prod_id'] ?? 0);
        $p = $this->productModel->find($prodId);
        if (!$p || !Product::isVisibleOnTelegram($p)) {
            $this->clearPurchaseSession($telegramId);
            return false;
        }

        if ($step === 'qty') {
            try {
                $rules = $this->getQuantityRules($p);
                if (!preg_match('/^[0-9]+$/', $text)) {
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "⚠️ Vui lòng chỉ nhập số (ví dụ: <b>1</b>, <b>2</b>, <b>10</b>).\n\n", "⚠️ Please enter numbers only (for example: <b>1</b>, <b>2</b>, <b>10</b>).\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
                    return true;
                }

                $qty = (int) $text;
                if ($qty <= 0) {
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "⚠️ Số lượng phải lớn hơn 0.\n\n", "⚠️ Quantity must be greater than 0.\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
                    return true;
                }

                $validation = $this->validateRequestedQuantity($p, $qty, $telegramId);
                if (!$validation['ok']) {
                    $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? $this->tgChoice($telegramId, '⚠️ Số lượng không hợp lệ.', '⚠️ Invalid quantity.')));
                    return true;
                }
            } catch (Throwable $e) {
                error_log('Telegram qty validation failed: ' . $e->getMessage());
                $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, '⚠️ Không thể kiểm tra giới hạn mua lúc này. Vui lòng thử lại sau.', '⚠️ Could not validate purchase limits right now. Please try again later.'));
                return true;
            }

            $session['qty'] = $qty;
            if ((int) ($p['requires_info'] ?? 0) === 1) {
                $session['step'] = 'info';
                $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                $instr = trim((string) ($p['info_instructions'] ?? ''));
                $prompt = $this->tgChoice($telegramId, "📝 <b>THÔNG TIN ĐƠN HÀNG</b>\n\n", "📝 <b>ORDER INFORMATION</b>\n\n");
                if ($instr !== '') {
                    $prompt .= "<i>" . htmlspecialchars($instr) . "</i>\n\n";
                }
                $prompt .= $this->tgChoice($telegramId, '👇 Vui lòng cung cấp thông tin theo yêu cầu bên dưới để hoàn tất đơn hàng:', '👇 Please provide the required information below to complete your order:');
                $cancelCallback = ((int) ($p['category_id'] ?? 0) > 0)
                    ? ('cat_' . (int) $p['category_id'])
                    : 'shop';
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => $this->tgChoice($telegramId, '❌ Hủy bỏ', '❌ Cancel'), 'callback_data' => $cancelCallback]],
                ]));
            } else {
                $session['step'] = 'confirm';
                $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                $this->cbBuyConfirm($chatId, $telegramId, $prodId, $qty, null, 0);
            }
            return true;
        }

        if ($step === 'info') {
            $session['info'] = $text;
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, (int) $session['qty'], $text, 0);
            return true;
        }

        if ($step === 'gift') {
            $giftcode = strtoupper(trim($text));
            if ($giftcode === '') {
                $this->telegram->editOrSend($chatId, 0, $this->tgChoice($telegramId, '⚠️ Vui lòng nhập mã giảm giá hợp lệ.', '⚠️ Please enter a valid discount code.'));
                return true;
            }

            $qty = max(1, (int) ($session['qty'] ?? 1));
            try {
                $quote = $this->purchaseService->quoteForDisplay($prodId, [
                    'quantity' => $qty,
                    'giftcode' => $giftcode,
                    'source_channel' => Product::CHANNEL_TELEGRAM,
                ]);

                if (empty($quote['success'])) {
                    $errorMsg = $this->tgRuntimeMessage($telegramId, (string) ($quote['message'] ?? ''));
                    $fullMsg = $this->tgText($telegramId, 'gift_invalid_title') . "\n";
                    if ($errorMsg !== '') {
                        $fullMsg .= htmlspecialchars($errorMsg) . "\n\n";
                    }
                    $fullMsg .= $this->tgText($telegramId, 'gift_invalid_msg');

                    $markup = TelegramService::buildInlineKeyboard([
                        [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => "buy_{$prodId}_{$qty}"]]
                    ]);

                    $this->telegram->editOrSend($chatId, 0, $fullMsg, $markup);
                    return true;
                }
            } catch (Throwable $e) {
                error_log('Telegram giftcode quote failed: ' . $e->getMessage());
                $this->telegram->editOrSend($chatId, 0, $this->tgChoice($telegramId, '⚠️ Không thể kiểm tra mã giảm giá lúc này. Vui lòng thử lại sau.', '⚠️ Could not verify the discount code right now. Please try again later.'));
                return true;
            }

            $session['giftcode'] = $giftcode;
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, $qty, $session['info'] ?? null, 0);
            return true;
        }

        return false;
    }

    private function showCategoryListForProduct(string $chatId, int $telegramId, int $prodId, int $messageId = 0): void
    {
        if ($prodId > 0) {
            $product = $this->productModel->find($prodId);
            $catId = (int) ($product['category_id'] ?? 0);
            if ($catId > 0) {
                $this->cbCategory($chatId, $telegramId, $catId, $messageId);
                return;
            }
        }
        $this->cmdShop($chatId, $telegramId, $messageId);
    }

    private function getQuantityRules(array $product): array
    {
        $product = Product::normalizeRuntimeRow($product);
        $inventory = new ProductInventoryService(new ProductStock());

        $minQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
        $maxQtyConfig = max(0, (int) ($product['max_purchase_qty'] ?? 0));
        $availableStock = $inventory->getAvailableStock($product);
        $effectiveMax = Product::isStockManagedProduct($product)
            ? $inventory->getDynamicMaxQty($product, $maxQtyConfig)
            : $maxQtyConfig;

        $isPurchasable = true;
        if ($availableStock !== null && $availableStock <= 0) {
            $isPurchasable = false;
        }
        if ($effectiveMax <= 0 && $availableStock !== null) {
            $isPurchasable = false;
        }
        if ($effectiveMax > 0 && $minQty > $effectiveMax) {
            $isPurchasable = false;
        }

        return [
            'min_qty' => $minQty,
            'max_qty' => $effectiveMax,
            'available_stock' => $availableStock,
            'max_config' => $maxQtyConfig,
            'is_purchasable' => $isPurchasable,
        ];
    }

    private function formatQuantityRuleHint(array $rules, int $telegramId = 0): string
    {
        $minQty = max(1, (int) ($rules['min_qty'] ?? 1));
        $maxQty = (int) ($rules['max_qty'] ?? 0);
        $stock = $rules['available_stock'] ?? null;

        $maxText = $maxQty > 0 ? number_format($maxQty) : (($stock === null) ? $this->tgChoice($telegramId, 'Không giới hạn', 'Unlimited') : '0');

        return $this->tgChoice($telegramId, "• Tối thiểu: <b>{$minQty}</b> | Tối đa: <b>{$maxText}</b>", "• Min: <b>{$minQty}</b> | Max: <b>{$maxText}</b>");
    }

    private function validateRequestedQuantity(array $product, int $qty, int $telegramId = 0): array
    {
        if (!Product::isVisibleOnTelegram($product)) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, '⚠️ Sản phẩm hiện không bán trên Telegram.', '⚠️ This product is not sold on Telegram right now.'),
            ];
        }

        $rules = $this->getQuantityRules($product);
        $hint = $this->formatQuantityRuleHint($rules, $telegramId);
        $minQty = (int) ($rules['min_qty'] ?? 1);
        $maxQty = (int) ($rules['max_qty'] ?? 0);

        if (!($rules['is_purchasable'] ?? false)) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "⚠️ Sản phẩm hiện không đủ SL tồn kho/giới hạn hiện tại.\n\n{$hint}", "⚠️ This product does not currently meet the stock or purchase-limit requirements.\n\n{$hint}"),
            ];
        }

        if ($qty < $minQty) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "⚠️ Số lượng tối thiểu là <b>{$minQty}</b>.\n\n{$hint}", "⚠️ Minimum quantity is <b>{$minQty}</b>.\n\n{$hint}"),
            ];
        }

        if ($maxQty > 0 && $qty > $maxQty) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "⚠️ Số lượng tối đa là <b>{$maxQty}</b>.\n\n{$hint}", "⚠️ Maximum quantity is <b>{$maxQty}</b>.\n\n{$hint}"),
            ];
        }

        return ['ok' => true, 'message' => ''];
    }

    private function startGiftCodeInputMode(string $chatId, int $telegramId, int $prodId, int $qty, int $messageId = 0): void
    {
        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        if (!$session) {
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText($telegramId) . "\n" . $this->tgChoice($telegramId, 'Vui lòng chọn lại sản phẩm để tiếp tục.', 'Please choose the product again to continue.'));
            $this->showCategoryListForProduct($chatId, $telegramId, $prodId, $messageId);
            return;
        }

        $session['step'] = 'gift';
        $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));

        $this->telegram->editOrSend($chatId, $messageId, $this->tgText($telegramId, 'gift_input_title') . "\n\n" . $this->tgText($telegramId, 'gift_input_prompt'), TelegramService::buildInlineKeyboard([
            [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'buy_' . $prodId . '_' . $qty]],
        ]));
    }

    // =========================================================
    //  Danh mục & Chi tiết sản phẩm
    // =========================================================

    /**
     * cat_{id} — Danh sách sản phẩm theo danh mục
     */
    private function cbCategory(string $chatId, int $telegramId, int $catId, int $messageId = 0, string $callbackId = ''): void
    {
        $this->clearPurchaseSession($telegramId);
        $this->clearBinanceSession($telegramId);

        $products = $this->productModel->getFiltered([
            'category_id' => $catId,
            'channel' => Product::CHANNEL_TELEGRAM,
        ]);
        if (empty($products)) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId);
            }
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⚠️ Danh mục này hiện chưa có sản phẩm nào.', '⚠️ This category does not have any products yet.'), [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'shop']],
                ]),
            ]);
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());

        $rows = [];
        foreach ($products as $p) {
            $stock = $inventory->getAvailableStock($p);
            $isOutOfStock = ($stock !== null && $stock <= 0);
            $stockText = $stock === null ? $this->tgChoice($telegramId, 'Vô hạn', 'Unlimited') : number_format($stock);
            $priceText = $this->formatCurrency((int) $p['price_vnd'], $telegramId);

            if ($isOutOfStock) {
                // Trigger a popup alert instead of navigating (as requested)
                $btnText = "{$p['name']} | {$priceText} | " . $this->tgChoice($telegramId, '❌ Hết hàng', '❌ Out of stock');
                $rows[] = [['text' => $btnText, 'callback_data' => 'oos']];
            } else {
                // Directly link to purchase (no intermediate detail step as requested)
                $btnText = "{$p['name']} | {$priceText} | 📦 {$stockText}";
                $rows[] = [['text' => $btnText, 'callback_data' => 'buy_' . $p['id'] . '_1']];
            }
        }

        $rows[] = [
            ['text' => $this->tgText($telegramId, 'button_refresh'), 'callback_data' => 'cat_refresh_' . $catId],
            ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'shop']
        ];

        $msg = $this->tgChoice($telegramId, "🛍️ <b>DANH SÁCH SẢN PHẨM</b>\n\n👇 Chọn sản phẩm bên dưới:", "🛍️ <b>PRODUCT LIST</b>\n\n👇 Choose a product below:");
        $markup = TelegramService::buildInlineKeyboard($rows);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /**
     * prod_{id} — Chi tiết sản phẩm
     */
    private function cbProduct(string $chatId, int $telegramId, int $prodId, int $messageId = 0): void
    {
        $this->clearPurchaseSession($telegramId);
        $this->clearBinanceSession($telegramId);

        $p = $this->productModel->find($prodId);
        if (!$p || !Product::isVisibleOnTelegram($p)) {
            $errMsg = $this->tgChoice($telegramId, '❌ Sản phẩm không tồn tại hoặc đã ngừng bán.', '❌ Product does not exist or is no longer available.');
            if ($messageId > 0) {
                $this->telegram->editOrSend($chatId, $messageId, $errMsg);
            } else {
                $this->telegram->sendTo($chatId, $errMsg);
            }
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());
        $stock = $inventory->getAvailableStock($p);

        $msg = "🛍️ <b>" . htmlspecialchars($p['name']) . "</b>\n\n";
        $msg .= $this->tgChoice($telegramId, '💎 Giá', '💎 Price') . ": <b>" . $this->formatCurrency((int) ($p['price_vnd'] ?? 0), $telegramId) . "</b>\n";

        $stockText = $this->tgChoice($telegramId, 'Hết hàng', 'Out of stock');
        if ($stock === null) {
            $stockText = $this->tgChoice($telegramId, 'Vô hạn', 'Unlimited');
        } elseif ($stock > 0) {
            $stockText = number_format($stock) . ' ' . $this->tgChoice($telegramId, 'sản phẩm', 'items');
        }
        $msg .= $this->tgChoice($telegramId, '📦 Kho', '📦 Stock') . ": <b>{$stockText}</b>\n\n";

        $descRaw = (string) ($p['description'] ?? '');
        // Preserve newlines from block elements
        $desc = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $descRaw);
        $desc = str_replace(['&nbsp;', '&amp;', '&quot;', '&apos;', '&lt;', '&gt;'], [' ', '&', '"', "'", '<', '>'], $desc);
        $desc = strip_tags($desc);
        $desc = trim(preg_replace("/\n\s*\n+/", "\n\n", $desc));

        if ($desc !== '') {
            $msg .= '<b>' . $this->tgChoice($telegramId, 'Mô tả', 'Description') . ":</b>\n<i>" . htmlspecialchars(mb_substr($desc, 0, 500)) . (mb_strlen($desc) > 500 ? '...' : '') . "</i>\n";
        }

        $rows = [];
        $rows[] = [
            ['text' => $this->tgText($telegramId, 'buy_now'), 'callback_data' => "buy_{$prodId}_1"],
            ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'cat_' . ($p['category_id'] ?? 0)]
        ];

        $markup = TelegramService::buildInlineKeyboard($rows);

        $image = trim((string) ($p['image'] ?? ''));
        if ($image !== '' && $messageId <= 0) {
            $photoUrl = str_starts_with($image, 'http') ? $image : (rtrim(BASE_URL, '/') . '/' . ltrim($image, '/'));
            if (!str_contains($photoUrl, 'localhost') && !str_contains($photoUrl, '127.0.0.1')) {
                if ($this->telegram->sendPhotoTo($chatId, $photoUrl, $msg, ['reply_markup' => $markup])) {
                    return;
                }
            }
        }

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    // =========================================================
    //  Xác nhận & Thực hiện mua hàng
    // =========================================================

    /**
     * buy_{prodId}_{qty} — Màn xác nhận mua hàng
     */
    private function cbBuyConfirm(string $chatId, int $telegramId, int $prodId, int $qty, ?string $customerInfo = null, int $messageId = 0, string $binanceUid = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $p = $this->productModel->find($prodId);
        if (!$p || !Product::isVisibleOnTelegram($p))
            return;

        $session = $this->getPurchaseSession($telegramId);
        // If messageId is strictly 0, it means we are responding to user text input (like qty or info)
        // Per user request: "cái nào mà user input nhập vào á là No edit message nha hiện ra cái mới"
        if ($messageId <= 0) {
            $messageId = 0;
        } else {
            $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        }
        $hasActiveSessionForProduct = is_array($session) && (int) ($session['prod_id'] ?? 0) === $prodId;
        $binanceUid = trim($binanceUid !== '' ? $binanceUid : (string) ($session['binance_uid'] ?? ''));

        $productType = (string) ($p['product_type'] ?? 'account');
        $requiresInfo = (int) ($p['requires_info'] ?? 0) === 1;

        if ($qty === 1 && $customerInfo === null && !$hasActiveSessionForProduct) {
            if ($productType !== 'link' && ($productType === 'account' || $requiresInfo)) {
                $rules = $this->getQuantityRules($p);
                if (!($rules['is_purchasable'] ?? false)) {
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "⚠️ Sản phẩm hiện không đủ điều kiện mua theo tồn kho/giới hạn hiện tại.\n\n", "⚠️ This product does not currently meet the stock or purchase-limit requirements.\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
                    return;
                }

                $cancelCallback = ((int) ($p['category_id'] ?? 0) > 0)
                    ? ('cat_' . (int) $p['category_id'])
                    : 'shop';
                $this->setPurchaseSession($telegramId, [
                    'prod_id' => $prodId,
                    'qty' => 1,
                    'step' => 'qty',
                    'message_id' => $messageId,
                ]);
                $prompt = $this->tgChoice($telegramId, "🔢 <b>NHẬP SỐ LƯỢNG</b>\n\n", "🔢 <b>ENTER QUANTITY</b>\n\n")
                    . $this->formatQuantityRuleHint($rules, $telegramId)
                    . "\n\n━━━━━━━━━━━━━━\n"
                    . $this->tgChoice($telegramId, '👇 Vui lòng nhập số lượng bạn muốn mua:', '👇 Please enter the quantity you want to buy:');
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => $this->tgChoice($telegramId, '❌ Hủy bỏ', '❌ Cancel'), 'callback_data' => $cancelCallback]],
                ]));
                return;
            }
        }

        $validation = $this->validateRequestedQuantity($p, max(1, $qty), $telegramId);
        if (!$validation['ok']) {
            $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? $this->tgChoice($telegramId, '⚠️ Số lượng không hợp lệ.', '⚠️ Invalid quantity.')));
            return;
        }

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
                    'giftcode' => $giftcode,
                    'source_channel' => Product::CHANNEL_TELEGRAM,
                ]);
                if ($quote['success']) {
                    $total = (float) ($quote['pricing']['total_price'] ?? $subtotal);
                    $discount = (float) ($quote['pricing']['discount_amount'] ?? 0);
                } else {
                    $giftError = $this->tgRuntimeMessage($telegramId, (string) ($quote['message'] ?? ''));
                    if ($giftError === '') {
                        $giftError = $this->tgChoice($telegramId, 'Mã giảm giá không hợp lệ.', 'Invalid discount code.');
                    }
                    $giftcode = null;
                    if (isset($session['giftcode'])) {
                        unset($session['giftcode']);
                        $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                    }
                }
            } catch (Throwable $e) {
                $giftcode = null;
            }
        }



        $unitPrice = $price;
        $confirmAction = "do_buy_" . $prodId . "_" . $qty;

        $this->setPurchaseSession($telegramId, [
            'prod_id' => $prodId,
            'qty' => $qty,
            'info' => $customerInfo,
            'giftcode' => $giftcode,
            'binance_uid' => $binanceUid,
            'step' => 'confirm',
            'message_id' => $messageId,
        ]);

        $msg = $this->tgText($telegramId, 'confirm_order_title') . "\n\n";
        if ($giftError) {
            $msg .= $this->tgChoice($telegramId, '⚠️ Lỗi Giftcode: ', '⚠️ Giftcode error: ') . htmlspecialchars($giftError) . "\n\n";
        }
        $msg .= $this->tgChoice($telegramId, '📦 Sản phẩm', '📦 Product') . ": <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= $this->tgChoice($telegramId, '🔢 Số lượng', '🔢 Quantity') . ": <b>{$qty}</b>\n";
        $msg .= $this->tgText($telegramId, 'confirm_unit_price') . ": <b>" . $this->formatCurrency((int) $unitPrice, $telegramId) . "</b>\n";

        if ($customerInfo !== null && trim($customerInfo) !== '') {
            $msg .= $this->tgText($telegramId, 'confirm_info') . ": <code>" . htmlspecialchars($customerInfo) . "</code>\n";
        }

        if ($discount > 0) {
            $msg .= $this->tgText($telegramId, 'confirm_discount') . ": -<b>" . $this->formatCurrency((int) $discount, $telegramId) . "</b> (<i>{$giftcode}</i>)\n";
        }

        $msg .= "\n────────────\n\n";
        $msg .= $this->tgText($telegramId, 'confirm_total') . ": <b>" . $this->formatCurrency((int) $total, $telegramId) . "</b>";
        if ($binanceUid !== '') {
            $msg .= "\nBinance UID: <code>" . htmlspecialchars($binanceUid, ENT_QUOTES, 'UTF-8') . "</code>";
        }

        $rows = [];
        $row1 = [];
        $row1[] = ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'prod_' . $prodId];

        if ((int) $total === 0) {
            // Free flow - single "Claim" button
            $row1[] = ['text' => $this->tgText($telegramId, 'confirm_free'), 'callback_data' => $confirmAction];
            $rows[] = $row1;
        } else {
            if (!$giftcode) {
                $row1[] = ['text' => $this->tgText($telegramId, 'gift_button'), 'callback_data' => 'buy_gift_' . $prodId . '_' . $qty];
            }
            $rows[] = $row1;

            // NEW: Auto-jump to UID entry for English Binance flow
            $isEnglish = $this->isTelegramEnglish($telegramId);
            if ($isEnglish) {
                if ($binanceUid === '') {
                    $rows[] = [['text' => '🆔 Enter UID', 'callback_data' => 'link_binance_uid_order']];
                } else {
                    $rows[] = [
                        ['text' => $this->tgText($telegramId, 'confirm_button'), 'callback_data' => $confirmAction],
                        ['text' => '🔄 Change UID', 'callback_data' => 'link_binance_uid_order']
                    ];
                }
            } else {
                $rows[] = [
                    ['text' => $this->tgText($telegramId, 'confirm_button'), 'callback_data' => $confirmAction]
                ];
            }
        }

        $this->telegram->editOrSend($chatId, $messageId, $msg, TelegramService::buildInlineKeyboard($rows));
    }

    /**
     * do_buy_{prodId}_{qty} — Thực hiện mua hàng
     */
    private function cbDoBuy(string $chatId, int $telegramId, int $prodId, int $qty, int $messageId = 0, string $forcedBinanceUid = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $cooldownSec = TelegramConfig::buyCooldown();
        $remaining = $this->getCooldownRemaining("buy_{$telegramId}", $cooldownSec);

        $session = $this->getPurchaseSession($telegramId);
        // If messageId is strictly 0, we strictly send a new message (per user request: no edit on input)
        if ($messageId <= 0) {
            $messageId = 0;
        } else {
            $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        }

        if (!$session) {
            if ($remaining > 0) {
                // Ignore double click
                return;
            }
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText($telegramId) . "\n" . $this->tgChoice($telegramId, 'Vui lòng chọn lại sản phẩm để tiếp tục.', 'Please choose the product again to continue.'));
            return;
        }

        if ($remaining > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "⏳ Bạn đang thao tác quá nhanh. Vui lòng chờ <b>{$remaining} giây</b> rồi thử lại.", "⏳ You're acting too quickly. Please wait <b>{$remaining} seconds</b> and try again."));
            return;
        }

        $customerInput = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['info'] ?? null) : null;
        $giftcode = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['giftcode'] ?? null) : null;
        $savedBinanceUid = trim((string) ($session['binance_uid'] ?? ''));
        $effectiveBinanceUid = trim($forcedBinanceUid !== '' ? $forcedBinanceUid : $savedBinanceUid);

        // FREE FLOW BYPASS UID PROMPT
        $isFree = false;
        try {
            $quote = $this->purchaseService->quoteForDisplay($prodId, [
                'quantity' => $qty,
                'giftcode' => $giftcode,
                'source_channel' => Product::CHANNEL_TELEGRAM
            ]);
            if ($quote['success'] && (int) ($quote['pricing']['total_price'] ?? 1) === 0) {
                $isFree = true;
            }
        } catch (Throwable $e) {
            $isFree = false;
        }

        if (!$isFree && $this->isTelegramEnglish($telegramId) && !preg_match('/^\d{4,20}$/', $effectiveBinanceUid)) {
            $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
            $this->cbLinkBinanceUid($chatId, $telegramId, $messageId, 'order_payment', 0);
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, '⏳ Đang xử lý giao dịch và tạo QR Code.', '⏳ Processing your order and generating the QR code.'));

        $result = $this->purchaseService->createTelegramPendingOrder($prodId, $user, [
            'quantity' => $qty,
            'customer_input' => $customerInput,
            'giftcode' => $giftcode,
            'telegram_id' => $telegramId,
        ]);

        $product = $this->productModel->find($prodId);
        $prodName = (string) ($product['name'] ?? $this->tgChoice($telegramId, 'Sản phẩm #' . $prodId, 'Product #' . $prodId));

        if (!$result['success']) {
            $this->writeLog("❌ Mua hàng thất bại: {$prodName} x {$qty} (" . ($result['message'] ?? 'Lỗi') . ")", 'WARN', 'INCOMING', 'PURCHASE');
            $this->telegram->editOrSend($chatId, $messageId, '❌ <b>' . $this->tgChoice($telegramId, 'THÔNG BÁO LỖI', 'ERROR NOTIFICATION') . ':</b> ' . htmlspecialchars($this->tgRuntimeMessage($telegramId, (string) ($result['message'] ?? $this->tgChoice($telegramId, 'Giao dịch không thành công.', 'The transaction was not successful.')))), TelegramService::buildInlineKeyboard([
                [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'prod_' . $prodId]],
            ]));
            return;
        }

        $this->clearPurchaseSession($telegramId);

        $order = (array) ($result['order'] ?? []);

        // Handle Free Flow - Auto Finalize
        if ((int) ($order['total_price'] ?? 0) === 0) {
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, '⏳ Đang xử lý đơn hàng của bạn...', '⏳ Processing your order...'));
            $finalizeResult = $this->purchaseService->finalizeTelegramOrderPayment([
                'order_id' => $order['id'],
                'method' => 'free'
            ]);

            if ($finalizeResult['success']) {
                // Delete the "processing" message so the success document appears cleanly
                if ($messageId > 0) {
                    $this->telegram->deleteMessage($chatId, $messageId);
                }
                $this->cbOrderCheck($chatId, $telegramId, (int) $order['id'], 0);
            } else {
                $this->telegram->editOrSend($chatId, $messageId, '❌ ' . $this->tgChoice($telegramId, 'Lỗi xử lý đơn hàng miễn phí.', 'Error processing free order.'));
            }
            return;
        }

        $this->renderTelegramOrderPaymentMenu($chatId, $telegramId, $order, $messageId, $effectiveBinanceUid);
    }

    /**
     * @param array<string,mixed> $order
     */
    private function renderTelegramOrderPaymentMenu(string $chatId, int $telegramId, array $order, int $messageId = 0, string $forcedBinanceUid = ''): void
    {
        $orderId = (int) ($order['id'] ?? 0);
        if ($orderId <= 0) {
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, '❌ Không thể tạo đơn chờ thanh toán lúc này.', '❌ Could not create a pending payment order right now.'));
            return;
        }

        if ($this->isTelegramEnglish($telegramId)) {
            $this->cbOrderPayBinance($chatId, $telegramId, $orderId, $messageId, $forcedBinanceUid);
        } else {
            $this->cbOrderPayBank($chatId, $telegramId, $orderId, $messageId);
        }
    }

    /**
     * @param array<string,mixed> $siteConfig
     */
    private function isTelegramBinanceAvailable(array $siteConfig): bool
    {
        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        return $binanceService !== null && $binanceService->isEnabled();
    }

    /**
     * @param array<string,mixed> $order
     */
    private function buildTelegramPendingOrderSummary(array $order, int $telegramId = 0): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? $this->tgChoice($telegramId, 'Sản phẩm', 'Product')), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $unitPrice = (int) ($order['unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = (int) floor(((int) ($order['total_price'] ?? $order['price'] ?? 0)) / $quantity);
        }
        $subtotal = (int) ($order['subtotal_price'] ?? ($unitPrice * $quantity));
        $discount = (int) ($order['discount_amount'] ?? 0);
        $total = (int) ($order['total_price'] ?? $order['price'] ?? 0);
        $expiresAt = trim((string) ($order['payment_expires_at'] ?? ''));

        $msg = $this->tgChoice($telegramId, "🧾 Mã đơn: <code>{$orderCode}</code>\n", "🧾 Order ID: <code>{$orderCode}</code>\n");
        $msg .= $this->tgChoice($telegramId, "📦 Sản phẩm: <b>{$productName}</b>\n", "📦 Product: <b>{$productName}</b>\n");
        $msg .= $this->tgChoice($telegramId, "🔢 Số lượng: <b>{$quantity}</b>\n", "🔢 Quantity: <b>{$quantity}</b>\n");
        $msg .= $this->tgChoice($telegramId, '💵 Đơn giá', '💵 Unit Price') . ": <b>" . $this->formatCurrency((int) $unitPrice, $telegramId) . "</b>\n";
        if ($discount > 0) {
            $msg .= $this->tgChoice($telegramId, '🏷️ Giảm giá', '🏷️ Discount') . ": -<b>" . $this->formatCurrency((int) $discount, $telegramId) . "</b>\n";
        }
        $msg .= $this->tgChoice($telegramId, '💎 Tổng thanh toán', '💎 Total Payment') . ": <b>" . $this->formatCurrency((int) $total, $telegramId) . "</b>\n";

        $customerInput = trim((string) ($order['customer_input'] ?? ''));
        if ($customerInput !== '') {
            $msg .= $this->tgChoice($telegramId, '📝 Thông tin', '📝 Information') . ": <code>" . htmlspecialchars($customerInput, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }
        if ($expiresAt !== '') {
            $msg .= $this->tgChoice($telegramId, '⏰ Hết hạn', '⏰ Expires At') . ": <b>" . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . "</b>\n";
        }

        $msg .= "\n" . $this->tgChoice($telegramId, '👇 Chọn phương thức để tiếp tục thanh toán.', '👇 Choose a method to continue with payment.');
        return $msg;
    }

    private function cbOrderPayBank(string $chatId, int $telegramId, int $orderId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $result = $this->purchaseService->activateTelegramOrderPayment($orderId, (int) ($user['id'] ?? 0), DepositService::METHOD_BANK_SEPAY);
        if (empty($result['success'])) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ ' . htmlspecialchars($this->tgRuntimeMessage($telegramId, (string) ($result['message'] ?? $this->tgChoice($telegramId, 'Không thể tạo phiên thanh toán.', 'Could not create a payment session.'))), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBankPaymentMessage($order, $payment, $telegramId);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => $this->tgChoice($telegramId, '🔍 Kiểm tra', '🔍 Check'), 'callback_data' => 'order_check_' . $orderId],
                ['text' => $this->tgChoice($telegramId, '❌ Hủy đơn', '❌ Cancel Order'), 'callback_data' => 'order_cancel_' . $orderId],
            ]
        ]);

        $qrUrl = trim((string) ($payment['qr_url'] ?? ''));
        $sent = false;
        if ($qrUrl !== '' && str_starts_with($qrUrl, 'http')) {
            $sent = $this->telegram->sendPhotoTo($chatId, $qrUrl, $message, ['reply_markup' => $markup]);
        }

        if ($sent) {
            if ($messageId > 0) {
                $this->telegram->deleteMessage($chatId, $messageId);
            }
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, $message, $markup);
    }

    private function cbOrderPayBinance(string $chatId, int $telegramId, int $orderId, int $messageId = 0, string $payerUid = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $linkModel = new UserTelegramLink();
        $payerUid = $payerUid !== '' ? $payerUid : $linkModel->getBinanceUidByUserId((int) ($user['id'] ?? 0));

        if ($payerUid === '' || !preg_match('/^\d{4,20}$/', $payerUid)) {
            $this->cbLinkBinanceUid($chatId, $telegramId, $messageId, 'order_payment', $orderId);
            return;
        }

        $result = $this->purchaseService->activateTelegramOrderPayment(
            $orderId,
            (int) ($user['id'] ?? 0),
            DepositService::METHOD_BINANCE,
            ['payer_uid' => $payerUid]
        );

        if (empty($result['success'])) {
            $err = (string) ($result['message'] ?? $this->tgChoice($telegramId, 'Không thể tạo phiên thanh toán Binance.', 'Could not create a Binance payment session.'));
            $this->telegram->editOrSend($chatId, $messageId, '❌ ' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBinancePaymentMessage($order, $payment, $telegramId);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => $this->tgChoice($telegramId, '🔍 Kiểm tra', '🔍 Check'), 'callback_data' => 'order_check_' . $orderId],
                ['text' => $this->tgChoice($telegramId, '❌ Hủy đơn', '❌ Cancel'), 'callback_data' => 'order_cancel_' . $orderId],
            ]
        ]);

        $this->sendTelegramMediaOrText(
            $chatId,
            $messageId,
            (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
            $message,
            $markup
        );
    }

    private function buildBinanceUidPrompt(bool $includeError = false): string
    {
        $prompt = "<b>BINANCE UID</b>\n\n";
        $prompt .= "Send your Binance UID to continue.";

        if ($includeError) {
            $prompt .= "\n\n⚠️ Invalid Binance UID. Enter 4-20 digits.";
        }

        return $prompt;
    }

    private function buildBinanceUidMarkup(): array
    {
        return TelegramService::buildInlineKeyboard([
            [['text' => 'Cancel', 'callback_data' => 'menu']]
        ]);
    }

    /**
     * Pre-order Binance UID linking flow
     */
    private function cbLinkBinanceUid(
        string $chatId,
        int $telegramId,
        int $messageId = 0,
        string $purpose = 'link_uid_before_buy',
        int $orderId = 0
    ): void {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $this->setBinanceSession($telegramId, [
            'step' => 'await_uid',
            'purpose' => $purpose,
            'order_id' => $orderId,
            'message_id' => $messageId,
        ]);

        $prompt = $this->buildBinanceUidPrompt(false);
        $markup = $this->buildBinanceUidMarkup();

        $this->telegram->editOrSend($chatId, $messageId, $prompt, $markup);
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $payment
     * VI-ONLY: Strictly Vietnamese. This message is only sent to Vietnamese users paying via Bank.
     */
    private function buildTelegramOrderBankPaymentMessage(array $order, array $payment, int $telegramId = 0): string
    {
        $bankName = htmlspecialchars((string) ($payment['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankOwner = htmlspecialchars((string) ($payment['bank_owner'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankAccount = htmlspecialchars((string) ($payment['bank_account'] ?? ''), ENT_QUOTES, 'UTF-8');
        $depositCode = htmlspecialchars((string) ($payment['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $rawAmount = (int) ($payment['amount'] ?? 0);
        $amount = number_format($rawAmount, 0, ',', '.') . 'đ';
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmt = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $msg = "🏦 <b>THANH TOÁN ĐƠN HÀNG</b>\n\n";
        $msg .= "🧾 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "🔢 Số lượng: <b>x{$quantity}</b>\n";
        if ($discountAmt > 0 && $giftcode !== '') {
            $msg .= "🏷️ Giảm giá: -<b>" . number_format($discountAmt, 0, ',', '.') . "đ</b> (<i>{$giftcode}</i>)\n";
        }
        $msg .= "💎 Tổng cần trả: <b>{$amount}</b>\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "🏛️ Ngân hàng: <b>{$bankName}</b>\n";
        $msg .= "👤 Chủ TK: <b>{$bankOwner}</b>\n";
        $msg .= "💳 Số TK: <code>{$bankAccount}</code>\n";
        $msg .= "📝 Nội dung CK: <code>{$depositCode}</code>\n";
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>{$expiresAt}</b>\n";
        }
        $msg .= "\n🚫 <b>QUAN TRỌNG:</b> Nội dung chuyển khoản và số tiền phải chính xác 100%.\n";
        $msg .= "✅ Quét QR hoặc chuyển khoản thủ công. Hệ thống tự động xác nhận.\n";
        return $msg;
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $payment
     * EN-ONLY: Strictly English. This message is only sent to English users paying via Binance.
     */
    private function buildTelegramOrderBinancePaymentMessage(array $order, array $payment, int $telegramId = 0): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $receiverUid = htmlspecialchars((string) ($payment['binance_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $payerUid = htmlspecialchars((string) ($payment['payer_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $usdtText = rtrim(rtrim(number_format((float) ($payment['usdt_amount'] ?? 0), 8, '.', ''), '0'), '.');
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmt = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $rate = (float) TelegramConfig::binanceRate();
        $discountUsdt = ($rate > 0 && $discountAmt > 0)
            ? rtrim(rtrim(number_format($discountAmt / $rate, 8, '.', ''), '0'), '.')
            : '0';

        $msg = "🟡 <b>BINANCE PAY — ORDER PAYMENT</b>\n\n";
        $msg .= "🧾 Order ID: <code>{$orderCode}</code>\n";
        $msg .= "📦 Product: <b>{$productName}</b>\n";
        $msg .= "🔢 Quantity: <b>x{$quantity}</b>\n";
        if ($discountAmt > 0 && $giftcode !== '') {
            $msg .= "🏷️ Discount: -<b>{$discountUsdt} USDT</b> (<i>{$giftcode}</i>)\n";
        }
        $msg .= "💎 Total to pay: <b>{$usdtText} USDT</b>\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "🆔 Receiver UID: <code>{$receiverUid}</code>\n";
        $msg .= "👤 Your (Payer) UID: <code>{$payerUid}</code>\n";
        if ($expiresAt !== '') {
            $msg .= "⏰ Expires at: <b>{$expiresAt}</b>\n";
        }

        // Dynamic warning from settings or default
        $warning = trim((string) get_setting('deposit_warning_binance', ''));
        if ($warning !== '') {
            $warning = str_replace(
                ['{amount}', '{uid}'],
                ["<b>{$usdtText} USDT</b>", "<code>{$receiverUid}</code>"],
                $warning
            );
            $msg .= "\n⚠️ " . $warning;
        } else {
            $msg .= "\n⚠️ <b>IMPORTANT:</b> Send the EXACT amount from the payer UID above. Wrong UID or amount will NOT be matched.";
        }

        return $msg;
    }

    private function cbOrderCheck(string $chatId, int $telegramId, int $orderId, int $messageId = 0, string $callbackId = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '🔗 Chưa liên kết tài khoản.', '🔗 Your account is not linked yet.'), true);
            }
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '❌ Không tìm thấy đơn hàng.', '❌ Order not found.'), true);
            }
            return;
        }

        $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? 'paid')));
        $status = strtolower(trim((string) ($order['status'] ?? '')));
        $method = strtolower(trim((string) ($order['payment_method'] ?? '')));
        $isBinance = (strpos($method, 'binance') !== false);

        if ($paymentStatus === 'paid') {
            if ($status === 'completed') {
                $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));
                if ($deliveryContent !== '') {
                    $orderIdForFile = (int) ($order['id'] ?? 0);
                    $filename = "order_{$orderIdForFile}.txt";

                    $orderCode = $order['order_code_short'] ?? $order['order_code'] ?? "#{$orderIdForFile}";
                    $prodName = (string) ($order['product_name'] ?? 'Sản phẩm');
                    $orderQty = max(1, (int) ($order['quantity'] ?? 1));
                    $totalText = $this->formatCurrency((int) ($order['total_price'] ?? 0), $telegramId);

                    $caption = "{$this->tgText($telegramId, 'success_title')}\n";
                    $caption .= "━━━━━━━━━━━━━━━━━\n\n";
                    $caption .= $this->tgChoice($telegramId, "📦 Mã đơn: <code>{$orderCode}</code>\n", "📦 Order Code: <code>{$orderCode}</code>\n");
                    $caption .= "🛒 {$prodName}\n";
                    $caption .= $this->tgChoice($telegramId, "🔢 Số lượng: <b>x{$orderQty}</b>\n", "🔢 Quantity: <b>x{$orderQty}</b>\n");
                    $caption .= $this->tgChoice($telegramId, "💰 Tổng: <b>{$totalText}</b>\n\n", "💰 Total: <b>{$totalText}</b>\n\n");
                    $caption .= $this->tgText($telegramId, 'product_sent_caption');

                    $this->telegram->sendDocumentFromContent($chatId, $deliveryContent, $filename, $caption);
                    if ($callbackId !== '') {
                        $popup = $isBinance ? '🎉 Product sent!' : '🎉 Sản phẩm đã được gửi!';
                        $this->telegram->answerCallbackQuery($callbackId, $popup, false);
                    }
                    return;
                }
            }

            $message = $status === 'completed'
                ? ($isBinance ? '🎉 Payment successful!' : '🎉 Thanh toán thành công!')
                : ($isBinance ? '✅ Payment received.' : '✅ Đã nhận thanh toán.');
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $message, true);
            }
            return;
        }

        if ($paymentStatus === 'expired' || $paymentStatus === 'cancelled' || $status === 'cancelled') {
            if ($callbackId !== '') {
                $msg = $isBinance ? '⌛ This order is no longer payable.' : '⌛ Đơn này không còn hiệu lực thanh toán.';
                $this->telegram->answerCallbackQuery($callbackId, $msg, true);
            }
            return;
        }

        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findLatestByOrderId($orderId, true);
        if (!$deposit) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '❌ Chưa tìm thấy giao dịch khớp.', '❌ No matching payment yet.'), true);
            }
            return;
        }

        $method = strtolower(trim((string) ($deposit['method'] ?? DepositService::METHOD_BANK_SEPAY)));
        if ($method !== DepositService::METHOD_BINANCE) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '❌ Chưa tìm thấy giao dịch khớp.', '❌ No matching payment yet.'), true);
            }
            return;
        }

        if ($depositModel->isLogicallyExpired($deposit)) {
            $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), $this->tgChoice($telegramId, 'Đơn hàng hết hạn thanh toán.', 'Order payment expired.'), true);
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '⌛ Đơn hàng đã hết hạn thanh toán.', '⌛ Payment expired.'), true);
            }
            return;
        }

        $siteConfig = Config::getSiteConfig();
        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '🚫 Binance tạm dừng, thử lại sau.', '🚫 Binance Pay is unavailable right now.'), true);
            }
            return;
        }

        $tx = $binanceService->findMatchingTransaction($deposit);
        if (!$tx) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '❌ Chưa tìm thấy giao dịch khớp.', '❌ No matching payment yet.'), true);
            }
            return;
        }

        $result = $binanceService->processTransaction($tx, $deposit, $user);
        if ($callbackId !== '') {
            $this->telegram->answerCallbackQuery(
                $callbackId,
                !empty($result['success'])
                ? $this->tgChoice($telegramId, '🎉 Thanh toán thành công! Đơn hàng đang được xử lý.', '🎉 Payment successful. Your order is being processed.')
                : $this->tgChoice($telegramId, '❌ Chưa tìm thấy giao dịch khớp.', '❌ No matching payment yet.'),
                true
            );
        }
    }

    private function cbOrderCancel(string $chatId, int $telegramId, int $orderId, int $messageId = 0, string $callbackId = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, '❌ Không tìm thấy đơn hàng.', '❌ Order not found.'), true);
            }
            return;
        }

        $method = strtolower(trim((string) ($order['payment_method'] ?? '')));
        $isBinance = (strpos($method, 'binance') !== false);
        $result = $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), $isBinance ? 'User cancelled the order.' : $this->tgChoice($telegramId, 'Người dùng hủy đơn.', 'User cancelled the order.'));
        $this->clearBinanceSession($telegramId);

        if ($callbackId !== '') {
            $success = !empty($result['success']);
            $rawMsg = (string) ($result['message'] ?? ($success ? 'Order cancelled.' : 'Could not cancel this order.'));
            $message = trim($this->tgRuntimeMessage($telegramId, $rawMsg));
            $this->telegram->answerCallbackQuery($callbackId, ($success ? '✅ ' : '❌ ') . $message, !$success);
        }

        if (empty($result['success'])) {
            return;
        }

        if ($messageId > 0) {
            $this->telegram->deleteMessage($chatId, $messageId);
        }

        $this->showMainMenu($chatId, $telegramId, '', false, 0);
    }
}

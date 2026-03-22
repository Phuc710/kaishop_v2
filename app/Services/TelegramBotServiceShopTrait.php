<?php

/**
 * TelegramBotServiceShopTrait
 *
 * Xá»­ lÃ½ luá»“ng mua hÃ ng:
 *  - Danh má»¥c sáº£n pháº©m (cbCategory)
 *  - Chi tiáº¿t sáº£n pháº©m (cbProduct)
 *  - XÃ¡c nháº­n mua (cbBuyConfirm)
 *  - Thá»±c hiá»‡n mua (cbDoBuy)
 *  - Nháº­p sá»‘ lÆ°á»£ng, thÃ´ng tin KH, mÃ£ giáº£m giÃ¡ (handlePurchaseInput)
 *  - Quáº£n lÃ½ session file-based cho purchase
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
            "â° <b>Giao dá»‹ch háº¿t háº¡n!</b>\nPhiÃªn mua hÃ ng cá»§a báº¡n Ä‘Ã£ quÃ¡ {$minutes} phÃºt vÃ  tá»± Ä‘á»™ng bá»‹ há»§y.",
            "â° <b>Session expired!</b>\nYour purchase session was inactive for more than {$minutes} minutes and was cancelled automatically."
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
        return number_format($vnd, 0, ',', '.') . 'Ä‘';
    }

    private function sendTelegramMediaOrText(string $chatId, int $messageId, string $mediaPath, string $message, ?array $markup = null): int
    {
        $mediaPath = trim($mediaPath);

        if ($mediaPath !== '') {
            $photo = $mediaPath;
            if (!str_starts_with($photo, 'http://') && !str_starts_with($photo, 'https://')) {
                $photo = rtrim((string) BASE_URL, '/') . '/' . ltrim($photo, '/');
            }

            if (!str_contains($photo, 'localhost') && !str_contains($photo, '127.0.0.1')) {
                if ($messageId > 0) {
                    $edited = $this->telegram->editMessagePhoto($chatId, $messageId, $photo, $message, $markup);
                    if ($edited) {
                        return $messageId;
                    }
                }

                $options = [];
                if ($markup !== null) {
                    $options['reply_markup'] = $markup;
                }
                $sentMessageId = $this->telegram->sendPhotoToWithResult($chatId, $photo, $message, $options);
                if ($sentMessageId > 0) {
                    if ($messageId > 0) {
                        $this->telegram->deleteMessage($chatId, $messageId);
                    }
                    return $sentMessageId;
                }
            }
        }

        return $this->telegram->editOrSendWithResult($chatId, $messageId, $message, $markup);
    }

    private function persistTelegramPaymentMessageId(int $orderId, int $messageId): void
    {
        if ($orderId <= 0 || $messageId <= 0) {
            return;
        }

        try {
            $this->purchaseService->storeTelegramPaymentMessageId($orderId, $messageId);
        } catch (Throwable $e) {
            // non-blocking
        }
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
    //  Xá»­ lÃ½ input mua hÃ ng
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
        if (in_array($normalized, ['há»§y', 'huy', 'cancel', 'thoÃ¡t', 'thoat', 'back', 'quay láº¡i', 'quay lai'], true)) {
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
                    $this->showMainMenu($chatId, $telegramId, 'âœ… Linked successfully!', false, 0);
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
                        $this->showMainMenu($chatId, $telegramId, 'âœ… Linked successfully!', false, 0);
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
        if (in_array($normalized, ['há»§y', 'huy', 'thoÃ¡t', 'thoat', 'cancel', 'back', 'quay láº¡i', 'quay lai'], true)) {
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
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "âš ï¸ Vui lÃ²ng chá»‰ nháº­p sá»‘ (vÃ­ dá»¥: <b>1</b>, <b>2</b>, <b>10</b>).\n\n", "âš ï¸ Please enter numbers only (for example: <b>1</b>, <b>2</b>, <b>10</b>).\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
                    return true;
                }

                $qty = (int) $text;
                if ($qty <= 0) {
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "âš ï¸ Sá»‘ lÆ°á»£ng pháº£i lá»›n hÆ¡n 0.\n\n", "âš ï¸ Quantity must be greater than 0.\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
                    return true;
                }

                $validation = $this->validateRequestedQuantity($p, $qty, $telegramId);
                if (!$validation['ok']) {
                    $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? $this->tgChoice($telegramId, 'âš ï¸ Sá»‘ lÆ°á»£ng khÃ´ng há»£p lá»‡.', 'âš ï¸ Invalid quantity.')));
                    return true;
                }
            } catch (Throwable $e) {
                error_log('Telegram qty validation failed: ' . $e->getMessage());
                $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, 'âš ï¸ KhÃ´ng thá»ƒ kiá»ƒm tra giá»›i háº¡n mua lÃºc nÃ y. Vui lÃ²ng thá»­ láº¡i sau.', 'âš ï¸ Could not validate purchase limits right now. Please try again later.'));
                return true;
            }

            $session['qty'] = $qty;
            if ((int) ($p['requires_info'] ?? 0) === 1) {
                $session['step'] = 'info';
                $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                $instr = trim((string) ($p['info_instructions'] ?? ''));
                $prompt = $this->tgChoice($telegramId, "ðŸ“ <b>THÃ”NG TIN ÄÆ N HÃ€NG</b>\n\n", "ðŸ“ <b>ORDER INFORMATION</b>\n\n");
                if ($instr !== '') {
                    $prompt .= "<i>" . htmlspecialchars($instr) . "</i>\n\n";
                }
                $prompt .= $this->tgChoice($telegramId, 'ðŸ‘‡ Vui lÃ²ng cung cáº¥p thÃ´ng tin theo yÃªu cáº§u bÃªn dÆ°á»›i Ä‘á»ƒ hoÃ n táº¥t Ä‘Æ¡n hÃ ng:', 'ðŸ‘‡ Please provide the required information below to complete your order:');
                $cancelCallback = ((int) ($p['category_id'] ?? 0) > 0)
                    ? ('cat_' . (int) $p['category_id'])
                    : 'shop';
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => $this->tgChoice($telegramId, 'âŒ Há»§y bá»', 'âŒ Cancel'), 'callback_data' => $cancelCallback]],
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
                $this->telegram->editOrSend($chatId, 0, $this->tgChoice($telegramId, 'âš ï¸ Vui lÃ²ng nháº­p mÃ£ giáº£m giÃ¡ há»£p lá»‡.', 'âš ï¸ Please enter a valid discount code.'));
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
                $this->telegram->editOrSend($chatId, 0, $this->tgChoice($telegramId, 'âš ï¸ KhÃ´ng thá»ƒ kiá»ƒm tra mÃ£ giáº£m giÃ¡ lÃºc nÃ y. Vui lÃ²ng thá»­ láº¡i sau.', 'âš ï¸ Could not verify the discount code right now. Please try again later.'));
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

        $maxText = $maxQty > 0 ? number_format($maxQty) : (($stock === null) ? $this->tgChoice($telegramId, 'KhÃ´ng giá»›i háº¡n', 'Unlimited') : '0');

        return $this->tgChoice($telegramId, "â€¢ Tá»‘i thiá»ƒu: <b>{$minQty}</b> | Tá»‘i Ä‘a: <b>{$maxText}</b>", "â€¢ Min: <b>{$minQty}</b> | Max: <b>{$maxText}</b>");
    }

    private function validateRequestedQuantity(array $product, int $qty, int $telegramId = 0): array
    {
        if (!Product::isVisibleOnTelegram($product)) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, 'âš ï¸ Sáº£n pháº©m hiá»‡n khÃ´ng bÃ¡n trÃªn Telegram.', 'âš ï¸ This product is not sold on Telegram right now.'),
            ];
        }

        $rules = $this->getQuantityRules($product);
        $hint = $this->formatQuantityRuleHint($rules, $telegramId);
        $minQty = (int) ($rules['min_qty'] ?? 1);
        $maxQty = (int) ($rules['max_qty'] ?? 0);

        if (!($rules['is_purchasable'] ?? false)) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "âš ï¸ Sáº£n pháº©m hiá»‡n khÃ´ng Ä‘á»§ SL tá»“n kho/giá»›i háº¡n hiá»‡n táº¡i.\n\n{$hint}", "âš ï¸ This product does not currently meet the stock or purchase-limit requirements.\n\n{$hint}"),
            ];
        }

        if ($qty < $minQty) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "âš ï¸ Sá»‘ lÆ°á»£ng tá»‘i thiá»ƒu lÃ  <b>{$minQty}</b>.\n\n{$hint}", "âš ï¸ Minimum quantity is <b>{$minQty}</b>.\n\n{$hint}"),
            ];
        }

        if ($maxQty > 0 && $qty > $maxQty) {
            return [
                'ok' => false,
                'message' => $this->tgChoice($telegramId, "âš ï¸ Sá»‘ lÆ°á»£ng tá»‘i Ä‘a lÃ  <b>{$maxQty}</b>.\n\n{$hint}", "âš ï¸ Maximum quantity is <b>{$maxQty}</b>.\n\n{$hint}"),
            ];
        }

        return ['ok' => true, 'message' => ''];
    }

    private function startGiftCodeInputMode(string $chatId, int $telegramId, int $prodId, int $qty, int $messageId = 0): void
    {
        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        if (!$session) {
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText($telegramId) . "\n" . $this->tgChoice($telegramId, 'Vui lÃ²ng chá»n láº¡i sáº£n pháº©m Ä‘á»ƒ tiáº¿p tá»¥c.', 'Please choose the product again to continue.'));
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
    //  Danh má»¥c & Chi tiáº¿t sáº£n pháº©m
    // =========================================================

    /**
     * cat_{id} â€” Danh sÃ¡ch sáº£n pháº©m theo danh má»¥c
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
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, 'âš ï¸ Danh má»¥c nÃ y hiá»‡n chÆ°a cÃ³ sáº£n pháº©m nÃ o.', 'âš ï¸ This category does not have any products yet.'), [
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
            $stockText = $stock === null ? $this->tgChoice($telegramId, 'VÃ´ háº¡n', 'Unlimited') : number_format($stock);
            $priceText = $this->formatCurrency((int) $p['price_vnd'], $telegramId);

            if ($isOutOfStock) {
                // Trigger a popup alert instead of navigating (as requested)
                $btnText = "{$p['name']} | {$priceText} | " . $this->tgChoice($telegramId, 'âŒ Háº¿t hÃ ng', 'âŒ Out of stock');
                $rows[] = [['text' => $btnText, 'callback_data' => 'oos']];
            } else {
                // Directly link to purchase (no intermediate detail step as requested)
                $btnText = "{$p['name']} | {$priceText} | ðŸ“¦ {$stockText}";
                $rows[] = [['text' => $btnText, 'callback_data' => 'buy_' . $p['id'] . '_1']];
            }
        }

        $rows[] = [
            ['text' => $this->tgText($telegramId, 'button_refresh'), 'callback_data' => 'cat_refresh_' . $catId],
            ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'shop']
        ];

        $msg = $this->tgChoice($telegramId, "ðŸ›ï¸ <b>DANH SÃCH Sáº¢N PHáº¨M</b>\n\nðŸ‘‡ Chá»n sáº£n pháº©m bÃªn dÆ°á»›i:", "ðŸ›ï¸ <b>PRODUCT LIST</b>\n\nðŸ‘‡ Choose a product below:");
        $markup = TelegramService::buildInlineKeyboard($rows);

        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $msg, $markup);
        } else {
            $this->telegram->sendTo($chatId, $msg, ['reply_markup' => $markup]);
        }
    }

    /**
     * prod_{id} â€” Chi tiáº¿t sáº£n pháº©m
     */
    private function cbProduct(string $chatId, int $telegramId, int $prodId, int $messageId = 0): void
    {
        $this->clearPurchaseSession($telegramId);
        $this->clearBinanceSession($telegramId);

        $p = $this->productModel->find($prodId);
        if (!$p || !Product::isVisibleOnTelegram($p)) {
            $errMsg = $this->tgChoice($telegramId, 'âŒ Sáº£n pháº©m khÃ´ng tá»“n táº¡i hoáº·c Ä‘Ã£ ngá»«ng bÃ¡n.', 'âŒ Product does not exist or is no longer available.');
            if ($messageId > 0) {
                $this->telegram->editOrSend($chatId, $messageId, $errMsg);
            } else {
                $this->telegram->sendTo($chatId, $errMsg);
            }
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());
        $stock = $inventory->getAvailableStock($p);

        $msg = "ðŸ›ï¸ <b>" . htmlspecialchars($p['name']) . "</b>\n\n";
        $msg .= $this->tgChoice($telegramId, 'ðŸ’Ž GiÃ¡', 'ðŸ’Ž Price') . ": <b>" . $this->formatCurrency((int) ($p['price_vnd'] ?? 0), $telegramId) . "</b>\n";

        $stockText = $this->tgChoice($telegramId, 'Háº¿t hÃ ng', 'Out of stock');
        if ($stock === null) {
            $stockText = $this->tgChoice($telegramId, 'VÃ´ háº¡n', 'Unlimited');
        } elseif ($stock > 0) {
            $stockText = number_format($stock) . ' ' . $this->tgChoice($telegramId, 'sáº£n pháº©m', 'items');
        }
        $msg .= $this->tgChoice($telegramId, 'ðŸ“¦ Kho', 'ðŸ“¦ Stock') . ": <b>{$stockText}</b>\n\n";

        $descRaw = (string) ($p['description'] ?? '');
        // Preserve newlines from block elements
        $desc = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $descRaw);
        $desc = str_replace(['&nbsp;', '&amp;', '&quot;', '&apos;', '&lt;', '&gt;'], [' ', '&', '"', "'", '<', '>'], $desc);
        $desc = strip_tags($desc);
        $desc = trim(preg_replace("/\n\s*\n+/", "\n\n", $desc));

        if ($desc !== '') {
            $msg .= '<b>' . $this->tgChoice($telegramId, 'MÃ´ táº£', 'Description') . ":</b>\n<i>" . htmlspecialchars(mb_substr($desc, 0, 500)) . (mb_strlen($desc) > 500 ? '...' : '') . "</i>\n";
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
    //  XÃ¡c nháº­n & Thá»±c hiá»‡n mua hÃ ng
    // =========================================================

    /**
     * buy_{prodId}_{qty} â€” MÃ n xÃ¡c nháº­n mua hÃ ng
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
        // Per user request: "cÃ¡i nÃ o mÃ  user input nháº­p vÃ o Ã¡ lÃ  No edit message nha hiá»‡n ra cÃ¡i má»›i"
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
                    $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "âš ï¸ Sáº£n pháº©m hiá»‡n khÃ´ng Ä‘á»§ Ä‘iá»u kiá»‡n mua theo tá»“n kho/giá»›i háº¡n hiá»‡n táº¡i.\n\n", "âš ï¸ This product does not currently meet the stock or purchase-limit requirements.\n\n") . $this->formatQuantityRuleHint($rules, $telegramId));
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
                $prompt = $this->tgChoice($telegramId, "ðŸ”¢ <b>NHáº¬P Sá» LÆ¯á»¢NG</b>\n\n", "ðŸ”¢ <b>ENTER QUANTITY</b>\n\n")
                    . $this->formatQuantityRuleHint($rules, $telegramId)
                    . "\n\nâ”â”â”â”â”â”â”â”â”â”â”â”â”â”\n"
                    . $this->tgChoice($telegramId, 'ðŸ‘‡ Vui lÃ²ng nháº­p sá»‘ lÆ°á»£ng báº¡n muá»‘n mua:', 'ðŸ‘‡ Please enter the quantity you want to buy:');
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => $this->tgChoice($telegramId, 'âŒ Há»§y bá»', 'âŒ Cancel'), 'callback_data' => $cancelCallback]],
                ]));
                return;
            }
        }

        $validation = $this->validateRequestedQuantity($p, max(1, $qty), $telegramId);
        if (!$validation['ok']) {
            $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? $this->tgChoice($telegramId, 'âš ï¸ Sá»‘ lÆ°á»£ng khÃ´ng há»£p lá»‡.', 'âš ï¸ Invalid quantity.')));
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
                        $giftError = $this->tgChoice($telegramId, 'MÃ£ giáº£m giÃ¡ khÃ´ng há»£p lá»‡.', 'Invalid discount code.');
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
            $msg .= $this->tgChoice($telegramId, 'âš ï¸ Lá»—i Giftcode: ', 'âš ï¸ Giftcode error: ') . htmlspecialchars($giftError) . "\n\n";
        }
        $msg .= $this->tgChoice($telegramId, 'ðŸ“¦ Sáº£n pháº©m', 'ðŸ“¦ Product') . ": <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= $this->tgChoice($telegramId, 'ðŸ”¢ Sá»‘ lÆ°á»£ng', 'ðŸ”¢ Quantity') . ": <b>{$qty}</b>\n";
        $msg .= $this->tgText($telegramId, 'confirm_unit_price') . ": <b>" . $this->formatCurrency((int) $unitPrice, $telegramId) . "</b>\n";

        if ($customerInfo !== null && trim($customerInfo) !== '') {
            $msg .= $this->tgText($telegramId, 'confirm_info') . ": <code>" . htmlspecialchars($customerInfo) . "</code>\n";
        }

        if ($discount > 0) {
            $msg .= $this->tgText($telegramId, 'confirm_discount') . ": -<b>" . $this->formatCurrency((int) $discount, $telegramId) . "</b> (<i>{$giftcode}</i>)\n";
        }

        $msg .= "\nâ”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€\n\n";
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
                    $rows[] = [['text' => 'ðŸ†” Enter UID', 'callback_data' => 'link_binance_uid_order']];
                } else {
                    $rows[] = [
                        ['text' => $this->tgText($telegramId, 'confirm_button'), 'callback_data' => $confirmAction],
                        ['text' => 'ðŸ”„ Change UID', 'callback_data' => 'link_binance_uid_order']
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
     * do_buy_{prodId}_{qty} â€” Thá»±c hiá»‡n mua hÃ ng
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
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText($telegramId) . "\n" . $this->tgChoice($telegramId, 'Vui lÃ²ng chá»n láº¡i sáº£n pháº©m Ä‘á»ƒ tiáº¿p tá»¥c.', 'Please choose the product again to continue.'));
            return;
        }

        if ($remaining > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, "â³ Báº¡n Ä‘ang thao tÃ¡c quÃ¡ nhanh. Vui lÃ²ng chá» <b>{$remaining} giÃ¢y</b> rá»“i thá»­ láº¡i.", "â³ You're acting too quickly. Please wait <b>{$remaining} seconds</b> and try again."));
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

        $processingText = $this->tgChoice($telegramId, 'â³ Äang xá»­ lÃ½ giao dá»‹ch vÃ  táº¡o QR Code.', 'â³ Processing your order and generating the QR code.');
        if ($messageId > 0) {
            $this->telegram->editOrSend($chatId, $messageId, $processingText);
        } else {
            $messageId = $this->telegram->sendToWithResult($chatId, $processingText);
            if ($messageId <= 0) {
                $this->telegram->sendTo($chatId, $processingText);
                $messageId = 0;
            }
        }

        $result = $this->purchaseService->createTelegramPendingOrder($prodId, $user, [
            'quantity' => $qty,
            'customer_input' => $customerInput,
            'giftcode' => $giftcode,
            'telegram_id' => $telegramId,
        ]);

        $product = $this->productModel->find($prodId);
        $prodName = (string) ($product['name'] ?? $this->tgChoice($telegramId, 'Sáº£n pháº©m #' . $prodId, 'Product #' . $prodId));

        if (!$result['success']) {
            $this->writeLog("âŒ Mua hÃ ng tháº¥t báº¡i: {$prodName} x {$qty} (" . ($result['message'] ?? 'Lá»—i') . ")", 'WARN', 'INCOMING', 'PURCHASE');
            $this->telegram->editOrSend($chatId, $messageId, 'âŒ <b>' . $this->tgChoice($telegramId, 'THÃ”NG BÃO Lá»–I', 'ERROR NOTIFICATION') . ':</b> ' . htmlspecialchars($this->tgRuntimeMessage($telegramId, (string) ($result['message'] ?? $this->tgChoice($telegramId, 'Giao dá»‹ch khÃ´ng thÃ nh cÃ´ng.', 'The transaction was not successful.')))), TelegramService::buildInlineKeyboard([
                [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'prod_' . $prodId]],
            ]));
            return;
        }

        $this->clearPurchaseSession($telegramId);

        $order = (array) ($result['order'] ?? []);

        // Handle Free Flow - Auto Finalize
        if ((int) ($order['total_price'] ?? 0) === 0) {
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, 'â³ Äang xá»­ lÃ½ Ä‘Æ¡n hÃ ng cá»§a báº¡n...', 'â³ Processing your order...'));
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
                $this->telegram->editOrSend($chatId, $messageId, 'âŒ ' . $this->tgChoice($telegramId, 'Lá»—i xá»­ lÃ½ Ä‘Æ¡n hÃ ng miá»…n phÃ­.', 'Error processing free order.'));
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
            $this->telegram->editOrSend($chatId, $messageId, $this->tgChoice($telegramId, 'âŒ KhÃ´ng thá»ƒ táº¡o Ä‘Æ¡n chá» thanh toÃ¡n lÃºc nÃ y.', 'âŒ Could not create a pending payment order right now.'));
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
        $productName = htmlspecialchars((string) ($order['product_name'] ?? $this->tgChoice($telegramId, 'Sáº£n pháº©m', 'Product')), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $unitPrice = (int) ($order['unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = (int) floor(((int) ($order['total_price'] ?? $order['price'] ?? 0)) / $quantity);
        }
        $subtotal = (int) ($order['subtotal_price'] ?? ($unitPrice * $quantity));
        $discount = (int) ($order['discount_amount'] ?? 0);
        $total = (int) ($order['total_price'] ?? $order['price'] ?? 0);
        $expiresAt = trim((string) ($order['payment_expires_at'] ?? ''));

        $msg = $this->tgChoice($telegramId, "ðŸ§¾ MÃ£ Ä‘Æ¡n: <code>{$orderCode}</code>\n", "ðŸ§¾ Order ID: <code>{$orderCode}</code>\n");
        $msg .= $this->tgChoice($telegramId, "ðŸ“¦ Sáº£n pháº©m: <b>{$productName}</b>\n", "ðŸ“¦ Product: <b>{$productName}</b>\n");
        $msg .= $this->tgChoice($telegramId, "ðŸ”¢ Sá»‘ lÆ°á»£ng: <b>{$quantity}</b>\n", "ðŸ”¢ Quantity: <b>{$quantity}</b>\n");
        $msg .= $this->tgChoice($telegramId, 'ðŸ’µ ÄÆ¡n giÃ¡', 'ðŸ’µ Unit Price') . ": <b>" . $this->formatCurrency((int) $unitPrice, $telegramId) . "</b>\n";
        if ($discount > 0) {
            $msg .= $this->tgChoice($telegramId, 'ðŸ·ï¸ Giáº£m giÃ¡', 'ðŸ·ï¸ Discount') . ": -<b>" . $this->formatCurrency((int) $discount, $telegramId) . "</b>\n";
        }
        $msg .= $this->tgChoice($telegramId, 'ðŸ’Ž Tá»•ng thanh toÃ¡n', 'ðŸ’Ž Total Payment') . ": <b>" . $this->formatCurrency((int) $total, $telegramId) . "</b>\n";

        $customerInput = trim((string) ($order['customer_input'] ?? ''));
        if ($customerInput !== '') {
            $msg .= $this->tgChoice($telegramId, 'ðŸ“ ThÃ´ng tin', 'ðŸ“ Information') . ": <code>" . htmlspecialchars($customerInput, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }
        if ($expiresAt !== '') {
            $msg .= $this->tgChoice($telegramId, 'â° Háº¿t háº¡n', 'â° Expires At') . ": <b>" . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . "</b>\n";
        }

        $msg .= "\n" . $this->tgChoice($telegramId, 'ðŸ‘‡ Chá»n phÆ°Æ¡ng thá»©c Ä‘á»ƒ tiáº¿p tá»¥c thanh toÃ¡n.', 'ðŸ‘‡ Choose a method to continue with payment.');
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
            $this->telegram->editOrSend($chatId, $messageId, 'âŒ ' . htmlspecialchars($this->tgRuntimeMessage($telegramId, (string) ($result['message'] ?? $this->tgChoice($telegramId, 'KhÃ´ng thá»ƒ táº¡o phiÃªn thanh toÃ¡n.', 'Could not create a payment session.'))), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBankPaymentMessage($order, $payment, $telegramId);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => $this->tgChoice($telegramId, 'ðŸ” Kiá»ƒm tra', 'ðŸ” Check'), 'callback_data' => 'order_check_' . $orderId],
                ['text' => $this->tgChoice($telegramId, 'âŒ Há»§y Ä‘Æ¡n', 'âŒ Cancel Order'), 'callback_data' => 'order_cancel_' . $orderId],
            ]
        ]);

        $paymentMessageId = $this->sendTelegramMediaOrText(
            $chatId,
            $messageId,
            trim((string) ($payment['qr_url'] ?? '')),
            $message,
            $markup
        );
        $this->persistTelegramPaymentMessageId($orderId, $paymentMessageId);
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
            $err = (string) ($result['message'] ?? $this->tgChoice($telegramId, 'KhÃ´ng thá»ƒ táº¡o phiÃªn thanh toÃ¡n Binance.', 'Could not create a Binance payment session.'));
            $this->telegram->editOrSend($chatId, $messageId, 'âŒ ' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBinancePaymentMessageClean($order, $payment, $telegramId);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => $this->tgChoice($telegramId, 'ðŸ” Kiá»ƒm tra', 'ðŸ” Check'), 'callback_data' => 'order_check_' . $orderId],
                ['text' => $this->tgChoice($telegramId, 'âŒ Há»§y Ä‘Æ¡n', 'âŒ Cancel'), 'callback_data' => 'order_cancel_' . $orderId],
            ]
        ]);

        $paymentMessageId = $this->sendTelegramMediaOrText(
            $chatId,
            $messageId,
            (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
            $message,
            $markup
        );
        $this->persistTelegramPaymentMessageId($orderId, $paymentMessageId);
    }

    private function buildBinanceUidPrompt(bool $includeError = false): string
    {
        $prompt = "ðŸ†” <b>BINANCE UID</b>\n\n";
        $prompt .= "Send your Binance UID to continue.";

        if ($includeError) {
            $prompt .= "\n\nInvalid Binance UID. Send 4-20 digits only.";
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
        $amount = number_format($rawAmount, 0, ',', '.') . 'Ä‘';
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Sáº£n pháº©m'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmt = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $msg = "ðŸ¦ <b>THANH TOÃN ÄÆ N HÃ€NG</b>\n\n";
        $msg .= "ðŸ§¾ MÃ£ Ä‘Æ¡n: <code>{$orderCode}</code>\n";
        $msg .= "ðŸ“¦ Sáº£n pháº©m: <b>{$productName}</b>\n";
        $msg .= "ðŸ”¢ Sá»‘ lÆ°á»£ng: <b>x{$quantity}</b>\n";
        if ($discountAmt > 0 && $giftcode !== '') {
            $msg .= "ðŸ·ï¸ Giáº£m giÃ¡: -<b>" . number_format($discountAmt, 0, ',', '.') . "Ä‘</b> (<i>{$giftcode}</i>)\n";
        }
        $msg .= "ðŸ’Ž Tá»•ng cáº§n tráº£: <b>{$amount}</b>\n";
        $msg .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
        $msg .= "ðŸ›ï¸ NgÃ¢n hÃ ng: <b>{$bankName}</b>\n";
        $msg .= "ðŸ‘¤ Chá»§ TK: <b>{$bankOwner}</b>\n";
        $msg .= "ðŸ’³ Sá»‘ TK: <code>{$bankAccount}</code>\n";
        $msg .= "ðŸ“ Ná»™i dung CK: <code>{$depositCode}</code>\n";
        if ($expiresAt !== '') {
            $msg .= "â° Háº¿t háº¡n: <b>{$expiresAt}</b>\n";
        }
        $msg .= "\nðŸš« <b>QUAN TRá»ŒNG:</b> Ná»™i dung chuyá»ƒn khoáº£n vÃ  sá»‘ tiá»n pháº£i chÃ­nh xÃ¡c 100%.\n";
        $msg .= "âœ… QuÃ©t QR hoáº·c chuyá»ƒn khoáº£n thá»§ cÃ´ng. Há»‡ thá»‘ng tá»± Ä‘á»™ng xÃ¡c nháº­n.\n";
        return $msg;
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $payment
     * EN-ONLY: Strictly English. This message is only sent to English users paying via Binance.
     */
    private function buildTelegramOrderBinancePaymentMessageClean(array $order, array $payment, int $telegramId = 0): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Product'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $receiverUid = htmlspecialchars((string) ($payment['binance_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ownerName = htmlspecialchars(trim((string) ($payment['binance_owner'] ?? get_setting('binance_owner', get_setting('ten_web', 'KaiShop')))), ENT_QUOTES, 'UTF-8');
        $usdtText = rtrim(rtrim(number_format((float) ($payment['usdt_amount'] ?? 0), 8, '.', ''), '0'), '.');
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmt = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $rate = (float) TelegramConfig::binanceRate();
        $discountUsdt = ($rate > 0 && $discountAmt > 0)
            ? rtrim(rtrim(number_format($discountAmt / $rate, 8, '.', ''), '0'), '.')
            : '0';

        $lines = [
            html_entity_decode('&#128993;', ENT_QUOTES, 'UTF-8') . " <b>BINANCE PAY &#8212; ORDER PAYMENT</b>",
            "",
            html_entity_decode('&#129534;', ENT_QUOTES, 'UTF-8') . " Order ID: <code>{$orderCode}</code>",
            html_entity_decode('&#128230;', ENT_QUOTES, 'UTF-8') . " Product: <b>{$productName}</b>",
            html_entity_decode('&#128290;', ENT_QUOTES, 'UTF-8') . " Quantity: <b>x{$quantity}</b>",
        ];

        if ($discountAmt > 0 && $giftcode !== '') {
            $lines[] = html_entity_decode('&#127991;&#65039;', ENT_QUOTES, 'UTF-8') . " Discount: -<b>{$discountUsdt} USDT</b> (<i>{$giftcode}</i>)";
        }

        $lines[] = html_entity_decode('&#128142;', ENT_QUOTES, 'UTF-8') . " Total to pay: <b>{$usdtText} USDT</b>";
        $lines[] = str_repeat(html_entity_decode('&#9473;', ENT_QUOTES, 'UTF-8'), 14);
        $lines[] = html_entity_decode('&#127380;', ENT_QUOTES, 'UTF-8') . " Receiver UID: <code>{$receiverUid}</code>";

        if ($ownerName !== '') {
            $lines[] = html_entity_decode('&#128100;', ENT_QUOTES, 'UTF-8') . " Nick Name: <b>{$ownerName}</b>";
        }

        if ($expiresAt !== '') {
            $lines[] = html_entity_decode('&#9200;', ENT_QUOTES, 'UTF-8') . " Expires at: <b>{$expiresAt}</b>";
        }

        $lines[] = "";

        $warning = trim((string) get_setting('deposit_warning_binance', ''));
        if ($warning !== '') {
            $warning = str_replace(
                ['{amount}', '{uid}'],
                ["<b>{$usdtText} USDT</b>", "<code>{$receiverUid}</code>"],
                $warning
            );
            $warning = trim((string) preg_replace('/\R+/', "\n", $warning));
            if ($warning !== '') {
                $lines[] = html_entity_decode('&#9888;&#65039;', ENT_QUOTES, 'UTF-8') . " " . $warning;
            }
        } else {
            $lines[] = html_entity_decode('&#9888;&#65039;', ENT_QUOTES, 'UTF-8') . " <b>AUTO MATCHING:</b> Send the EXACT amount only.";
            $lines[] = html_entity_decode('&#10060;', ENT_QUOTES, 'UTF-8') . " Wrong UID or amount will NOT be matched.";
        }

        return implode("\n", $lines);
    }

    private function resolveTelegramOrderTotalText(array $order, int $telegramId = 0, array $deposit = []): string
    {
        $method = strtolower(trim((string) ($order['payment_method'] ?? $deposit['method'] ?? '')));
        $isBinance = strpos($method, 'binance') !== false || $method === DepositService::METHOD_BINANCE;

        if ($isBinance) {
            $usdtAmount = (float) ($deposit['usdt_amount'] ?? 0);
            if ($usdtAmount <= 0) {
                $usdtAmount = (float) ($order['usdt_amount'] ?? 0);
            }
            if ($usdtAmount <= 0) {
                $totalVnd = (int) ($order['total_price'] ?? $order['price'] ?? 0);
                $rate = (float) TelegramConfig::binanceRate();
                if ($totalVnd > 0 && $rate > 0) {
                    $usdtAmount = $totalVnd / $rate;
                }
            }

            return '$' . number_format(max(0, $usdtAmount), 2, '.', ',');
        }

        return $this->formatCurrency((int) ($order['total_price'] ?? 0), $telegramId);
    }

    private function buildTelegramOrderSuccessSummary(array $order, int $telegramId = 0, array $deposit = []): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? $this->tgChoice($telegramId, 'Sáº£n pháº©m', 'Product')), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $status = strtolower(trim((string) ($order['status'] ?? '')));
        $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));
        $totalText = $this->resolveTelegramOrderTotalText($order, $telegramId, $deposit);

        $msg = "{$this->tgText($telegramId, 'success_title')}\n";
        $msg .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n\n";
        $msg .= $this->tgChoice($telegramId, "ðŸ“¦ MÃ£ Ä‘Æ¡n: <code>{$orderCode}</code>\n", "ðŸ“¦ Order Code: <code>{$orderCode}</code>\n");
        $msg .= "ðŸ›’ {$productName}\n";
        $msg .= $this->tgChoice($telegramId, "ðŸ”¢ Sá»‘ lÆ°á»£ng: <b>x{$quantity}</b>\n", "ðŸ”¢ Quantity: <b>x{$quantity}</b>\n");
        $msg .= $this->tgChoice($telegramId, "ðŸ’° Tá»•ng: <b>{$totalText}</b>\n\n", "ðŸ’° Total: <b>{$totalText}</b>\n\n");

        if ($status === 'completed' && $deliveryContent !== '') {
            $msg .= $this->tgText($telegramId, 'product_sent_caption');
        } elseif ($status === 'completed') {
            $msg .= $this->tgChoice($telegramId, 'âœ… ÄÆ¡n hÃ ng Ä‘Ã£ hoÃ n táº¥t.', 'âœ… Your order has been completed.');
        } else {
            $msg .= $this->tgChoice($telegramId, 'âš™ï¸ Thanh toÃ¡n Ä‘Ã£ Ä‘Æ°á»£c xÃ¡c nháº­n tá»± Ä‘á»™ng. ÄÆ¡n hÃ ng Ä‘ang Ä‘Æ°á»£c xá»­ lÃ½.', 'âš™ï¸ Payment was confirmed automatically. Your order is being processed.');
        }

        return $msg;
    }

    private function cbOrderCheck(string $chatId, int $telegramId, int $orderId, int $messageId = 0, string $callbackId = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'ðŸ”— ChÆ°a liÃªn káº¿t tÃ i khoáº£n.', 'ðŸ”— Your account is not linked yet.'), true);
            }
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n hÃ ng.', 'âŒ Order not found.'), true);
            }
            return;
        }

        $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? 'paid')));
        $status = strtolower(trim((string) ($order['status'] ?? '')));
        $method = strtolower(trim((string) ($order['payment_method'] ?? '')));
        $isBinance = (strpos($method, 'binance') !== false);
        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findLatestByOrderId($orderId, true) ?: [];

        if ($paymentStatus === 'paid') {
            if ($messageId > 0) {
                $this->telegram->editOrSend(
                    $chatId,
                    $messageId,
                    $this->buildTelegramOrderSuccessSummary($order, $telegramId, is_array($deposit) ? $deposit : [])
                );
            }

            if ($status === 'completed') {
                $deliveryContent = trim((string) ($order['delivery_content'] ?? $order['stock_content_plain'] ?? ''));
                if ($deliveryContent !== '') {
                    $orderIdForFile = (int) ($order['id'] ?? 0);
                    $filename = "order_{$orderIdForFile}.txt";

                    if (!$isBinance || $messageId <= 0) {
                        $caption = $this->buildTelegramOrderSuccessSummary($order, $telegramId, is_array($deposit) ? $deposit : []);
                        $this->telegram->sendDocumentFromContent($chatId, $deliveryContent, $filename, $caption);
                    }

                    if ($callbackId !== '') {
                        $popup = $isBinance
                            ? 'ðŸŽ‰ Payment confirmed automatically! Product sent.'
                            : 'ðŸŽ‰ Sáº£n pháº©m Ä‘Ã£ Ä‘Æ°á»£c gá»­i!';
                        $this->telegram->answerCallbackQuery($callbackId, $popup, false);
                    }
                    return;
                }
            }

            $message = $status === 'completed'
                ? ($isBinance ? 'ðŸŽ‰ Payment confirmed automatically!' : 'ðŸŽ‰ Thanh toÃ¡n thÃ nh cÃ´ng!')
                : ($isBinance ? 'âœ… Payment received automatically.' : 'âœ… ÄÃ£ nháº­n thanh toÃ¡n.');
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $message, true);
            }
            return;
        }

        if ($paymentStatus === 'expired' || $paymentStatus === 'cancelled' || $status === 'cancelled') {
            if ($callbackId !== '') {
                $msg = $isBinance ? 'âŒ› This order is no longer payable.' : 'âŒ› ÄÆ¡n nÃ y khÃ´ng cÃ²n hiá»‡u lá»±c thanh toÃ¡n.';
                $this->telegram->answerCallbackQuery($callbackId, $msg, true);
            }
            return;
        }

        if (!$deposit) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ ChÆ°a tÃ¬m tháº¥y giao dá»‹ch khá»›p.', 'âŒ No matching payment yet.'), true);
            }
            return;
        }

        $method = strtolower(trim((string) ($deposit['method'] ?? DepositService::METHOD_BANK_SEPAY)));
        if ($method !== DepositService::METHOD_BINANCE) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ ChÆ°a tÃ¬m tháº¥y giao dá»‹ch khá»›p.', 'âŒ No matching payment yet.'), true);
            }
            return;
        }

        if ($depositModel->isLogicallyExpired($deposit)) {
            $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), $this->tgChoice($telegramId, 'ÄÆ¡n hÃ ng háº¿t háº¡n thanh toÃ¡n.', 'Order payment expired.'), true);
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ› ÄÆ¡n hÃ ng Ä‘Ã£ háº¿t háº¡n thanh toÃ¡n.', 'âŒ› Payment expired.'), true);
            }
            return;
        }

        $siteConfig = Config::getSiteConfig();
        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'ðŸš« Binance táº¡m dá»«ng, thá»­ láº¡i sau.', 'ðŸš« Binance Pay is unavailable right now.'), true);
            }
            return;
        }

        $tx = $binanceService->findMatchingTransaction($deposit);
        if (!$tx) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ ChÆ°a tÃ¬m tháº¥y giao dá»‹ch khá»›p.', 'âŒ No matching payment yet.'), true);
            }
            return;
        }

        $result = $binanceService->processTransaction($tx, $deposit, $user);
        if (!empty($result['success'])) {
            $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: $order;
            $deposit = $depositModel->findLatestByOrderId($orderId, true) ?: $deposit;

            if ($messageId > 0) {
                $this->telegram->editOrSend(
                    $chatId,
                    $messageId,
                    $this->buildTelegramOrderSuccessSummary($order, $telegramId, $deposit)
                );
            }
        }

        if ($callbackId !== '') {
            $this->telegram->answerCallbackQuery(
                $callbackId,
                !empty($result['success'])
                ? $this->tgChoice($telegramId, 'ðŸŽ‰ Thanh toÃ¡n tá»± Ä‘á»™ng thÃ nh cÃ´ng!', 'ðŸŽ‰ Payment confirmed automatically!')
                : $this->tgChoice($telegramId, 'âŒ ChÆ°a tÃ¬m tháº¥y giao dá»‹ch khá»›p.', 'âŒ No matching payment yet.'),
                !empty($result['success']) ? false : true
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
                $this->telegram->answerCallbackQuery($callbackId, $this->tgChoice($telegramId, 'âŒ KhÃ´ng tÃ¬m tháº¥y Ä‘Æ¡n hÃ ng.', 'âŒ Order not found.'), true);
            }
            return;
        }

        $method = strtolower(trim((string) ($order['payment_method'] ?? '')));
        $isBinance = (strpos($method, 'binance') !== false);
        $result = $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), $isBinance ? 'User cancelled the order.' : $this->tgChoice($telegramId, 'NgÆ°á»i dÃ¹ng há»§y Ä‘Æ¡n.', 'User cancelled the order.'));
        $this->clearBinanceSession($telegramId);

        if ($callbackId !== '') {
            $success = !empty($result['success']);
            $rawMsg = (string) ($result['message'] ?? ($success ? 'Order cancelled.' : 'Could not cancel this order.'));
            $message = trim($this->tgRuntimeMessage($telegramId, $rawMsg));
            $this->telegram->answerCallbackQuery($callbackId, ($success ? 'âœ… ' : 'âŒ ') . $message, !$success);
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


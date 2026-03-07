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

    private function purchaseSessionTimeoutText(): string
    {
        $ttl = TelegramConfig::purchaseSessionTtl();
        $minutes = (int) ceil($ttl / 60);
        return "⏰ <b>Giao dịch hết hạn!</b>\nPhiên mua hàng của bạn đã quá {$minutes} phút và tự động bị hủy.";
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

    // =========================================================
    //  Xử lý input mua hàng
    // =========================================================

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
        $messageId = $this->resolvePurchaseMessageId(0, $session);

        $text = trim($text);
        if ($text === '') {
            return true;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'back', 'quay lại', 'quay lai'], true)) {
            $prodId = (int) ($session['prod_id'] ?? 0);
            $this->clearPurchaseSession($telegramId);
            $this->showCategoryListForProduct($chatId, $prodId, $messageId);
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
            try {
                $rules = $this->getQuantityRules($p);
                if (!preg_match('/^[0-9]+$/', $text)) {
                    $this->telegram->editOrSend($chatId, $messageId, "⚠️ Vui lòng chỉ nhập số (ví dụ: <b>1</b>, <b>2</b>, <b>10</b>).\n\n" . $this->formatQuantityRuleHint($rules));
                    return true;
                }

                $qty = (int) $text;
                if ($qty <= 0) {
                    $this->telegram->editOrSend($chatId, $messageId, "⚠️ Số lượng phải lớn hơn 0.\n\n" . $this->formatQuantityRuleHint($rules));
                    return true;
                }

                $validation = $this->validateRequestedQuantity($p, $qty);
                if (!$validation['ok']) {
                    $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? '⚠️ Số lượng không hợp lệ.'));
                    return true;
                }
            } catch (Throwable $e) {
                error_log('Telegram qty validation failed: ' . $e->getMessage());
                $this->telegram->editOrSend($chatId, $messageId, "⚠️ Không thể kiểm tra giới hạn mua lúc này. Vui lòng thử lại sau.");
                return true;
            }

            $session['qty'] = $qty;
            if ((int) ($p['requires_info'] ?? 0) === 1) {
                $session['step'] = 'info';
                $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                $instr = trim((string) ($p['info_instructions'] ?? ''));
                $prompt = "📝 <b>NHẬP THÔNG TIN YÊU CẦU</b>\n\n";
                if ($instr !== '') {
                    $prompt .= "<i>" . htmlspecialchars($instr) . "</i>\n\n";
                }
                $prompt .= "👇 Vui lòng nhập nội dung để admin xử lý:";
                $cancelCallback = ((int) ($p['category_id'] ?? 0) > 0)
                    ? ('cat_' . (int) $p['category_id'])
                    : 'shop';
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => '❌ Hủy bỏ', 'callback_data' => $cancelCallback]],
                ]));
            } else {
                $session['step'] = 'confirm';
                $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
                $this->cbBuyConfirm($chatId, $telegramId, $prodId, $qty, null, $messageId);
            }
            return true;
        }

        if ($step === 'info') {
            $session['info'] = $text;
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, (int) $session['qty'], $text, $messageId);
            return true;
        }

        if ($step === 'gift') {
            $giftcode = strtoupper(trim($text));
            if ($giftcode === '') {
                $this->telegram->editOrSend($chatId, $messageId, "⚠️ Vui lòng nhập mã giảm giá hợp lệ.");
                return true;
            }

            $qty = max(1, (int) ($session['qty'] ?? 1));
            try {
                $quote = $this->purchaseService->quoteForDisplay($prodId, [
                    'quantity' => $qty,
                    'giftcode' => $giftcode,
                ]);

                if (empty($quote['success'])) {
                    $message = (string) ($quote['message'] ?? 'Mã giảm giá không hợp lệ.');
                    $this->telegram->editOrSend($chatId, $messageId, "❌ <b>MÃ GIẢM GIÁ KHÔNG HỢP LỆ</b>\n{$message}\n\nVui lòng nhập lại mã khác hoặc bấm Quay lại.");
                    return true;
                }
            } catch (Throwable $e) {
                error_log('Telegram giftcode quote failed: ' . $e->getMessage());
                $this->telegram->editOrSend($chatId, $messageId, "⚠️ Không thể kiểm tra mã giảm giá lúc này. Vui lòng thử lại sau.");
                return true;
            }

            $session['giftcode'] = $giftcode;
            $session['step'] = 'confirm';
            $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));
            $this->cbBuyConfirm($chatId, $telegramId, $prodId, $qty, $session['info'] ?? null, $messageId);
            return true;
        }

        return false;
    }

    private function showCategoryListForProduct(string $chatId, int $prodId, int $messageId = 0): void
    {
        if ($prodId > 0) {
            $product = $this->productModel->find($prodId);
            $catId = (int) ($product['category_id'] ?? 0);
            if ($catId > 0) {
                $this->cbCategory($chatId, $catId, $messageId);
                return;
            }
        }
        $this->cmdShop($chatId, $messageId);
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

    private function formatQuantityRuleHint(array $rules): string
    {
        $minQty = max(1, (int) ($rules['min_qty'] ?? 1));
        $maxQty = (int) ($rules['max_qty'] ?? 0);
        $stock = $rules['available_stock'] ?? null;

        $maxText = $maxQty > 0 ? number_format($maxQty) : (($stock === null) ? 'Không giới hạn' : '0');

        return "• Min: <b>{$minQty}</b> | Max: <b>{$maxText}</b>";
    }

    private function validateRequestedQuantity(array $product, int $qty): array
    {
        $rules = $this->getQuantityRules($product);
        $hint = $this->formatQuantityRuleHint($rules);
        $minQty = (int) ($rules['min_qty'] ?? 1);
        $maxQty = (int) ($rules['max_qty'] ?? 0);

        if (!($rules['is_purchasable'] ?? false)) {
            return [
                'ok' => false,
                'message' => "⚠️ Sản phẩm hiện không đủ SL tồn kho/giới hạn hiện tại.\n\n{$hint}",
            ];
        }

        if ($qty < $minQty) {
            return [
                'ok' => false,
                'message' => "⚠️ Số lượng tối thiểu là <b>{$minQty}</b>.\n\n{$hint}",
            ];
        }

        if ($maxQty > 0 && $qty > $maxQty) {
            return [
                'ok' => false,
                'message' => "⚠️ Số lượng tối đa là <b>{$maxQty}</b>.\n\n{$hint}",
            ];
        }

        return ['ok' => true, 'message' => ''];
    }

    private function startGiftCodeInputMode(string $chatId, int $telegramId, int $prodId, int $qty, int $messageId = 0): void
    {
        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        if (!$session) {
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText() . "\nVui lòng chọn lại sản phẩm để tiếp tục.");
            $this->showCategoryListForProduct($chatId, $prodId, $messageId);
            return;
        }

        $session['step'] = 'gift';
        $this->setPurchaseSession($telegramId, $this->attachPurchaseMessageId($session, $messageId));

        $this->telegram->editOrSend($chatId, $messageId, "🏷️ <b>NHẬP MÃ GIẢM GIÁ</b>\n\n👇 Vui lòng nhập mã giảm giá của bạn:", TelegramService::buildInlineKeyboard([
            [['text' => '◀️ Quay lại', 'callback_data' => 'buy_' . $prodId . '_' . $qty]],
        ]));
    }

    // =========================================================
    //  Danh mục & Chi tiết sản phẩm
    // =========================================================

    /**
     * cat_{id} — Danh sách sản phẩm theo danh mục
     */
    private function cbCategory(string $chatId, int $catId, int $messageId = 0): void
    {
        $products = $this->productModel->getFiltered(['category_id' => $catId, 'status' => 'ON']);
        if (empty($products)) {
            $this->telegram->sendTo($chatId, "⚠️ Danh mục này hiện chưa có sản phẩm nào.", [
                'reply_markup' => TelegramService::buildInlineKeyboard([
                    [['text' => '⬅️ Quay lại', 'callback_data' => 'shop']],
                ]),
            ]);
            return;
        }

        $inventory = new ProductInventoryService(new ProductStock());

        $rows = [];
        foreach ($products as $p) {
            $stock = $inventory->getAvailableStock($p);
            $isOutOfStock = ($stock !== null && $stock <= 0);
            $stockText = $stock === null ? 'Vô hạn' : number_format($stock);
            $priceText = number_format((float) $p['price_vnd']) . 'đ';

            $btnText = "{$p['name']} | {$priceText} | 📦 {$stockText}";
            if ($isOutOfStock) {
                $btnText .= " (❌ Hết Hàng)";
            }

            $rows[] = [['text' => $btnText, 'callback_data' => 'prod_' . $p['id']]];
        }

        $rows[] = [['text' => '🔄 Cập nhật sản phẩm', 'callback_data' => 'cat_' . $catId]];
        $rows[] = [['text' => '⬅️ Thay đổi Danh mục', 'callback_data' => 'shop']];

        $msg = "🛍️ <b>DANH SÁCH SẢN PHẨM</b>\n\n👇 Chọn sản phẩm bên dưới:";
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
    private function cbProduct(string $chatId, int $prodId, int $messageId = 0): void
    {
        $p = $this->productModel->find($prodId);
        if (!$p || $p['status'] !== 'ON') {
            $errMsg = "❌ Sản phẩm không tồn tại hoặc đã ngừng bán.";
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
            $rows[] = [['text' => '🛒 MUA NGAY', 'callback_data' => 'buy_' . $p['id'] . '_1']];
        }
        $rows[] = [['text' => '⬅️ Quay lại', 'callback_data' => 'cat_' . ($p['category_id'] ?? 0)]];

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
    private function cbBuyConfirm(string $chatId, int $telegramId, int $prodId, int $qty, ?string $customerInfo = null, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $p = $this->productModel->find($prodId);
        if (!$p)
            return;

        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        $hasActiveSessionForProduct = is_array($session) && (int) ($session['prod_id'] ?? 0) === $prodId;

        $productType = (string) ($p['product_type'] ?? 'account');
        $requiresInfo = (int) ($p['requires_info'] ?? 0) === 1;

        if ($qty === 1 && $customerInfo === null && !$hasActiveSessionForProduct) {
            if ($productType !== 'link' && ($productType === 'account' || $requiresInfo)) {
                $rules = $this->getQuantityRules($p);
                if (!($rules['is_purchasable'] ?? false)) {
                    $this->telegram->editOrSend($chatId, $messageId, "⚠️ Sản phẩm hiện không đủ điều kiện mua theo tồn kho/giới hạn hiện tại.\n\n" . $this->formatQuantityRuleHint($rules));
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
                $prompt = "🔢 <b>NHẬP SỐ LƯỢNG</b>\n\n"
                    . $this->formatQuantityRuleHint($rules)
                    . "\n\n━━━━━━━━━━━━━━\n👇 Vui lòng nhập số lượng bạn muốn mua:";
                $this->telegram->editOrSend($chatId, $messageId, $prompt, TelegramService::buildInlineKeyboard([
                    [['text' => '❌ Hủy bỏ', 'callback_data' => $cancelCallback]],
                ]));
                return;
            }
        }

        $validation = $this->validateRequestedQuantity($p, max(1, $qty));
        if (!$validation['ok']) {
            $this->telegram->editOrSend($chatId, $messageId, (string) ($validation['message'] ?? '⚠️ Số lượng không hợp lệ.'));
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
                    'giftcode' => $giftcode
                ]);
                if ($quote['success']) {
                    $total = (float) ($quote['pricing']['total_price'] ?? $subtotal);
                    $discount = (float) ($quote['pricing']['discount_amount'] ?? 0);
                } else {
                    $giftError = $quote['message'] ?? 'Mã giảm giá không hợp lệ.';
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

        $msg .= "━━━━━━━━━━━━━━\n\n";
        $msg .= "💎 Thành tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "💰 Số dư ví: <b>" . number_format($balance) . "đ</b>\n";

        if ($customerInfo !== null && trim($customerInfo) !== '') {
            $msg .= "\n📝 Thông tin: <code>" . htmlspecialchars($customerInfo) . "</code>\n";
        }

        if ($balance < $total) {
            $shortfall = (int) ceil($total - $balance);
            $depositAmount = max(DepositService::MIN_AMOUNT, $shortfall);
            $msg .= "\n\n⚠️ Số dư không đủ! Cần nạp thêm: <b>" . number_format($shortfall) . "đ</b>";
            if ($depositAmount > $shortfall) {
                $msg .= "\n(Mức nạp tối thiểu hiện tại: <b>" . number_format(DepositService::MIN_AMOUNT) . "đ</b>)";
            }

            $this->telegram->editOrSend($chatId, $messageId, $msg, TelegramService::buildInlineKeyboard([
                [
                    ['text' => '💳 Nạp thêm ' . number_format($shortfall) . 'đ', 'callback_data' => 'deposit_' . $depositAmount],
                    ['text' => '❌ Hủy bỏ', 'callback_data' => 'prod_' . $prodId],
                ]
            ]));
            return;
        }

        $msg .= "\n\n⚠️ Xác nhận thanh toán trừ tiền ví.";

        $confirmAction = "do_buy_" . $prodId . "_" . $qty;

        $this->setPurchaseSession($telegramId, [
            'prod_id' => $prodId,
            'qty' => $qty,
            'info' => $customerInfo,
            'giftcode' => $giftcode,
            'step' => 'confirm',
            'message_id' => $messageId,
        ]);

        $rows = [];
        if (!$giftcode) {
            $rows[] = [['text' => '🏷️ Nhập mã giảm giá', 'callback_data' => 'buy_gift_' . $prodId . '_' . $qty]];
        }
        $rows[] = [
            ['text' => '❌ HỦY BỎ', 'callback_data' => 'prod_' . $prodId],
            ['text' => '✅ XÁC NHẬN MUA', 'callback_data' => $confirmAction],
        ];

        $this->telegram->editOrSend($chatId, $messageId, $msg, TelegramService::buildInlineKeyboard($rows));
    }

    /**
     * do_buy_{prodId}_{qty} — Thực hiện mua hàng
     */
    private function cbDoBuy(string $chatId, int $telegramId, int $prodId, int $qty, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user)
            return;

        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);
        if (!$session) {
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText() . "\nVui lòng chọn lại sản phẩm để tiếp tục.");
            return;
        }

        $customerInput = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['info'] ?? null) : null;
        $giftcode = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['giftcode'] ?? null) : null;

        $this->clearPurchaseSession($telegramId);

        $cooldownSec = TelegramConfig::buyCooldown();
        $remaining = $this->getCooldownRemaining("buy_{$telegramId}", $cooldownSec);
        if ($remaining > 0) {
            $this->telegram->editOrSend($chatId, $messageId, "⏳ Bạn đang thao tác quá nhanh. Vui lòng chờ <b>{$remaining} giây</b> rồi thử lại.");
            return;
        }

        $result = $this->purchaseService->purchaseWithWallet($prodId, $user, [
            'quantity' => $qty,
            'customer_input' => $customerInput,
            'giftcode' => $giftcode,
            'source' => 'telegram',
            'source_channel' => SourceChannelHelper::BOTTELE,
            'telegram_id' => $telegramId,
        ]);

        $product = $this->productModel->find($prodId);
        $prodName = (string) ($product['name'] ?? ('Sản phẩm #' . $prodId));

        if (!$result['success']) {
            $this->writeLog("❌ Mua hàng thất bại: {$prodName} x {$qty} (" . ($result['message'] ?? 'Lỗi') . ")", 'WARN', 'INCOMING', 'PURCHASE');
            $this->telegram->editOrSend($chatId, $messageId, "❌ <b>LỖI:</b> " . htmlspecialchars((string) ($result['message'] ?? 'Giao dịch không thành công.')), TelegramService::buildInlineKeyboard([
                [['text' => '⬅️ Quay lại sản phẩm', 'callback_data' => 'prod_' . $prodId]],
            ]));
            return;
        }

        $totalPrice = (int) ($result['data']['total_price'] ?? 0);
        $this->writeLog("🛒 SUCCESS: " . ($user['username'] ?? 'User') . " mua {$prodName} x {$qty} (" . number_format($totalPrice) . "đ)", 'INFO', 'INCOMING', 'PURCHASE');

        $successMsg = "✅ <b>ĐÃ XÁC NHẬN GIAO DỊCH</b>\n\n";
        $successMsg .= "📦 Sản phẩm: <b>" . htmlspecialchars($prodName) . "</b>\n";
        $successMsg .= "🔢 Số lượng: <b>{$qty}</b>\n";
        $successMsg .= "💎 Thành tiền: <b>" . number_format($totalPrice) . "đ</b>\n\n";
        $successMsg .= "📩 Thông tin đơn hàng sẽ được gửi ngay bên dưới.";

        $this->telegram->editOrSend($chatId, $messageId, $successMsg, TelegramService::buildInlineKeyboard([
            [['text' => '🛍️ Mua tiếp', 'callback_data' => 'prod_' . $prodId]],
            [$this->backHomeButton()],
        ]));
    }
}

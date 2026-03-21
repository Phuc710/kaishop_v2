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
        $messageId = 0; // Always send new message for text input responses as requested by user

        $text = trim($text);
        if ($text === '') {
            return true;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        if (in_array($normalized, ['hủy', 'huy', 'thoát', 'thoat', 'back', 'quay lại', 'quay lai'], true)) {
            $prodId = (int) ($session['prod_id'] ?? 0);
            $this->clearPurchaseSession($telegramId);
            $this->showCategoryListForProduct($chatId, $telegramId, $prodId, $messageId);
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
                $this->telegram->editOrSend($chatId, 0, "⚠️ Vui lòng nhập mã giảm giá hợp lệ.");
                return true;
            }

            $qty = max(1, (int) ($session['qty'] ?? 1));
            try {
                $quote = $this->purchaseService->quoteForDisplay($prodId, [
                    'quantity' => $qty,
                    'giftcode' => $giftcode,
                ]);

                if (empty($quote['success'])) {
                    $errorMsg = (string) ($quote['message'] ?? '');
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
                $this->telegram->editOrSend($chatId, 0, "⚠️ Không thể kiểm tra mã giảm giá lúc này. Vui lòng thử lại sau.");
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
    private function cbCategory(string $chatId, int $telegramId, int $catId, int $messageId = 0): void
    {
        $products = $this->productModel->getFiltered(['category_id' => $catId, 'status' => 'ON']);
        if (empty($products)) {
            $this->telegram->sendTo($chatId, "⚠️ Danh mục này hiện chưa có sản phẩm nào.", [
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
            $stockText = $stock === null ? 'Vô hạn' : number_format($stock);
            $priceText = number_format((float) $p['price_vnd']) . 'đ';

            $btnText = "{$p['name']} | {$priceText} | 📦 {$stockText}";
            if ($isOutOfStock) {
                $btnText .= " (❌ Hết Hàng)";
            }

            $rows[] = [['text' => $btnText, 'callback_data' => 'prod_' . $p['id']]];
        }

        $rows[] = [['text' => $this->tgText($telegramId, 'button_refresh'), 'callback_data' => 'cat_' . $catId]];
        $rows[] = [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'shop']];

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
    private function cbProduct(string $chatId, int $telegramId, int $prodId, int $messageId = 0): void
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

        $descRaw = (string) ($p['description'] ?? '');
        $desc = strip_tags($descRaw);
        $desc = str_replace(['&nbsp;', '&amp;', '&quot;', '&apos;', '&lt;', '&gt;'], [' ', '&', '"', "'", '<', '>'], $desc);
        $desc = trim($desc);

        if ($desc !== '') {
            $msg .= "<b>Mô tả:</b>\n<i>" . htmlspecialchars(mb_substr($desc, 0, 500)) . (mb_strlen($desc) > 500 ? '...' : '') . "</i>\n";
        }

        $rows = [];
        if ($stock === null || $stock > 0) {
            $rows[] = [['text' => $this->tgText($telegramId, 'buy_now'), 'callback_data' => 'buy_' . $p['id'] . '_1']];
        }
        $rows[] = [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'cat_' . ($p['category_id'] ?? 0)]];

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



        $unitPrice = $price;
        $confirmAction = "do_buy_" . $prodId . "_" . $qty;

        $this->setPurchaseSession($telegramId, [
            'prod_id' => $prodId,
            'qty' => $qty,
            'info' => $customerInfo,
            'giftcode' => $giftcode,
            'step' => 'confirm',
            'message_id' => $messageId,
        ]);

        $msg = $this->tgText($telegramId, 'confirm_order_title') . "\n\n";
        if ($giftError) {
            $msg .= "⚠️ Lỗi mã giảm giá: " . htmlspecialchars($giftError) . "\n\n";
        }
        $msg .= "📦 Sản phẩm: <b>" . htmlspecialchars($p['name']) . "</b>\n";
        $msg .= "🔢 Số lượng: <b>{$qty}</b>\n";
        $msg .= $this->tgText($telegramId, 'confirm_unit_price') . ": <b>" . number_format($unitPrice) . "đ</b>\n";

        if ($customerInfo !== null && trim($customerInfo) !== '') {
            $msg .= $this->tgText($telegramId, 'confirm_info') . ": <code>" . htmlspecialchars($customerInfo) . "</code>\n";
        }

        if ($discount > 0) {
            $msg .= $this->tgText($telegramId, 'confirm_discount') . ": -<b>" . number_format($discount) . "đ</b> (<i>{$giftcode}</i>)\n";
        }

        $msg .= "\n────────────\n\n";
        $msg .= $this->tgText($telegramId, 'confirm_total') . ": <b>" . number_format($total) . "đ</b>";

        $rows = [];
        $row1 = [];
        $row1[] = ['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'prod_' . $prodId];
        if (!$giftcode) {
            $row1[] = ['text' => $this->tgText($telegramId, 'gift_button'), 'callback_data' => 'buy_gift_' . $prodId . '_' . $qty];
        }
        $rows[] = $row1;
        $rows[] = [['text' => $this->tgText($telegramId, 'confirm_button'), 'callback_data' => $confirmAction]];

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

        $cooldownSec = TelegramConfig::buyCooldown();
        $remaining = $this->getCooldownRemaining("buy_{$telegramId}", $cooldownSec);

        $session = $this->getPurchaseSession($telegramId);
        $messageId = $this->resolvePurchaseMessageId($messageId, $session ?? []);

        if (!$session) {
            if ($remaining > 0) {
                // Ignore double click
                return;
            }
            $this->telegram->editOrSend($chatId, $messageId, $this->purchaseSessionTimeoutText() . "\nVui lòng chọn lại sản phẩm để tiếp tục.");
            return;
        }

        if ($remaining > 0) {
            $this->telegram->editOrSend($chatId, $messageId, "⏳ Bạn đang thao tác quá nhanh. Vui lòng chờ <b>{$remaining} giây</b> rồi thử lại.");
            return;
        }

        $this->telegram->editOrSend($chatId, $messageId, "⏳ Đang xử lý giao dịch và tạo QR Code...\nVui lòng chờ trong giây lát.");

        $customerInput = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['info'] ?? null) : null;
        $giftcode = ((int) ($session['prod_id'] ?? 0) === $prodId) ? ($session['giftcode'] ?? null) : null;

        $this->clearPurchaseSession($telegramId);

        $result = $this->purchaseService->createTelegramPendingOrder($prodId, $user, [
            'quantity' => $qty,
            'customer_input' => $customerInput,
            'giftcode' => $giftcode,
            'telegram_id' => $telegramId,
        ]);

        $product = $this->productModel->find($prodId);
        $prodName = (string) ($product['name'] ?? ('Sản phẩm #' . $prodId));

        if (!$result['success']) {
            $this->writeLog("❌ Mua hàng thất bại: {$prodName} x {$qty} (" . ($result['message'] ?? 'Lỗi') . ")", 'WARN', 'INCOMING', 'PURCHASE');
            $this->telegram->editOrSend($chatId, $messageId, "❌ <b>LỖI:</b> " . htmlspecialchars((string) ($result['message'] ?? 'Giao dịch không thành công.')), TelegramService::buildInlineKeyboard([
                [['text' => $this->tgText($telegramId, 'back_home'), 'callback_data' => 'prod_' . $prodId]],
            ]));
            return;
        }

        $order = (array) ($result['order'] ?? []);
        $totalPrice = (int) ($order['total_price'] ?? 0);
        $this->renderTelegramOrderPaymentMenu($chatId, $telegramId, $order, $messageId);
        return;
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

    /**
     * @param array<string,mixed> $order
     */
    private function renderTelegramOrderPaymentMenu(string $chatId, int $telegramId, array $order, int $messageId = 0): void
    {
        $orderId = (int) ($order['id'] ?? 0);
        if ($orderId <= 0) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ Không thể tạo đơn chờ thanh toán lúc này.');
            return;
        }

        if ($this->isTelegramEnglish($telegramId)) {
            $this->cbOrderPayBinance($chatId, $telegramId, $orderId, $messageId);
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
    private function buildTelegramPendingOrderSummary(array $order): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $unitPrice = (int) ($order['unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = (int) floor(((int) ($order['total_price'] ?? $order['price'] ?? 0)) / $quantity);
        }
        $subtotal = (int) ($order['subtotal_price'] ?? ($unitPrice * $quantity));
        $discount = (int) ($order['discount_amount'] ?? 0);
        $total = (int) ($order['total_price'] ?? $order['price'] ?? 0);
        $expiresAt = trim((string) ($order['payment_expires_at'] ?? ''));

        $msg = "🧾 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "🔢 Số lượng: <b>{$quantity}</b>\n";
        $msg .= "💵 Đơn giá: <b>" . number_format($unitPrice, 0, ',', '.') . "đ</b>\n";
        $msg .= "🧮 Tạm tính: <b>" . number_format($subtotal, 0, ',', '.') . "đ</b>\n";
        if ($discount > 0) {
            $msg .= "🏷️ Giảm giá: -<b>" . number_format($discount, 0, ',', '.') . "đ</b>\n";
        }
        $msg .= "💎 Tổng thanh toán: <b>" . number_format($total, 0, ',', '.') . "đ</b>\n";

        $customerInput = trim((string) ($order['customer_input'] ?? ''));
        if ($customerInput !== '') {
            $msg .= "📝 Thông tin: <code>" . htmlspecialchars($customerInput, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>" . htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') . "</b>\n";
        }

        $msg .= "\n👇 Chọn phương thức để tiếp tục thanh toán.";
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
            $this->telegram->editOrSend($chatId, $messageId, '❌ ' . htmlspecialchars((string) ($result['message'] ?? 'Không thể tạo phiên thanh toán.'), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBankPaymentMessage($order, $payment);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '✅ Kiểm tra thanh toán', 'callback_data' => 'order_check_' . $orderId],
                ['text' => '❌ Hủy đơn', 'callback_data' => 'order_cancel_' . $orderId],
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

        $payerUid = trim($payerUid);
        if ($payerUid === '' || !preg_match('/^\d{4,20}$/', $payerUid)) {
            $this->startOrderBinanceUidInputMode($chatId, $telegramId, $orderId, $messageId);
            return;
        }

        $result = $this->purchaseService->activateTelegramOrderPayment(
            $orderId,
            (int) ($user['id'] ?? 0),
            DepositService::METHOD_BINANCE,
            ['payer_uid' => $payerUid]
        );

        if (empty($result['success'])) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ ' . htmlspecialchars((string) ($result['message'] ?? 'Không thể tạo phiên Binance.'), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0)) ?: (array) ($result['order'] ?? []);
        $payment = (array) ($result['payment'] ?? []);
        $message = $this->buildTelegramOrderBinancePaymentMessage($order, $payment);
        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🔍 Kiểm tra thanh toán', 'callback_data' => 'order_check_' . $orderId],
                ['text' => '❌ Hủy đơn', 'callback_data' => 'order_cancel_' . $orderId],
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

    private function startOrderBinanceUidInputMode(string $chatId, int $telegramId, int $orderId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ Không tìm thấy đơn hàng.');
            return;
        }

        $this->setBinanceSession($telegramId, [
            'step' => 'await_uid',
            'purpose' => 'order_payment',
            'order_id' => $orderId,
            'message_id' => $messageId,
        ], 300);

        $total = (int) ($order['price'] ?? 0);
        $msg = "🟡 <b>BINANCE PAY</b>\n\n";
        $msg .= "🧾 Mã đơn: <code>" . htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8') . "</code>\n";
        $msg .= "💎 Cần thanh toán: <b>" . number_format($total, 0, ',', '.') . "đ</b>\n\n";
        $msg .= "👤 Vui lòng nhập <b>UID Binance của bạn</b> để tiếp tục.";

        $this->sendTelegramMediaOrText(
            $chatId,
            $messageId,
            (string) get_setting('binance_qr_image', 'assets/images/qr_binane.jpg'),
            $msg
        );
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $payment
     */
    private function buildTelegramOrderBankPaymentMessage(array $order, array $payment): string
    {
        $bankName = htmlspecialchars((string) ($payment['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankOwner = htmlspecialchars((string) ($payment['bank_owner'] ?? ''), ENT_QUOTES, 'UTF-8');
        $bankAccount = htmlspecialchars((string) ($payment['bank_account'] ?? ''), ENT_QUOTES, 'UTF-8');
        $depositCode = htmlspecialchars((string) ($payment['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $amount = number_format((int) ($payment['amount'] ?? 0), 0, ',', '.') . 'đ';
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmount = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $msg = "🏦 <b>THANH TOÁN ĐƠN HÀNG</b>\n\n";
        $msg .= "🧾 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "🔢 Số lượng: <b>{$quantity}</b>\n";
        if ($discountAmount > 0 && $giftcode !== '') {
            $msg .= "🏷️ Giảm giá: -<b>" . number_format($discountAmount, 0, ',', '.') . "đ</b> (<i>{$giftcode}</i>)\n";
        }
        $msg .= "💎 Tổng cần trả: <b>{$amount}</b>\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "🏛 Ngân hàng: <b>{$bankName}</b>\n";
        $msg .= "👤 Chủ TK: <b>{$bankOwner}</b>\n";
        $msg .= "💳 Số TK: <code>{$bankAccount}</code>\n";
        $msg .= "📝 Nội dung: <code>{$depositCode}</code>\n";
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>{$expiresAt}</b>\n";
        }

        return $msg;
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed> $payment
     */
    private function buildTelegramOrderBinancePaymentMessage(array $order, array $payment): string
    {
        $orderCode = htmlspecialchars((string) ($order['order_code_short'] ?? $order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars((string) ($order['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $depositCode = htmlspecialchars((string) ($payment['deposit_code'] ?? ''), ENT_QUOTES, 'UTF-8');
        $receiverUid = htmlspecialchars((string) ($payment['binance_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $receiverName = htmlspecialchars((string) ($payment['binance_owner'] ?? ''), ENT_QUOTES, 'UTF-8');
        $payerUid = htmlspecialchars((string) ($payment['payer_uid'] ?? ''), ENT_QUOTES, 'UTF-8');
        $usdtText = number_format((float) ($payment['usdt_amount'] ?? 0), 2, '.', '');
        $vndText = number_format((int) ($payment['amount'] ?? 0), 0, ',', '.') . 'đ';
        $expiresAt = htmlspecialchars((string) ($payment['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $discountAmount = (int) ($order['discount_amount'] ?? 0);
        $giftcode = htmlspecialchars(trim((string) ($order['giftcode_code'] ?? '')), ENT_QUOTES, 'UTF-8');

        $msg = "🟡 <b>BINANCE PAY — THANH TOÁN ĐƠN</b>\n\n";
        $msg .= "🧾 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "🔢 Số lượng: <b>{$quantity}</b>\n";
        if ($discountAmount > 0 && $giftcode !== '') {
            $msg .= "🏷️ Giảm giá: -<b>" . number_format($discountAmount, 0, ',', '.') . "đ</b> (<i>{$giftcode}</i>)\n";
        }
        $msg .= "💎 Tổng đơn: <b>{$vndText}</b>\n";
        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "🏷 Người nhận: <b>{$receiverName}</b>\n";
        $msg .= "🆔 UID nhận: <code>{$receiverUid}</code>\n";
        $msg .= "📋 Mã giao dịch: <code>{$depositCode}</code>\n";
        $msg .= "👤 UID của bạn: <code>{$payerUid}</code>\n";
        $msg .= "💵 Send EXACTLY: <b>$" . $usdtText . " USDT</b>\n";
        if ($expiresAt !== '') {
            $msg .= "⏰ Hết hạn: <b>{$expiresAt}</b>\n";
        }
        $msg .= "\n⚠️ Chuyển đúng UID gửi, UID nhận và số USDT để hệ thống auto match đơn hàng.";

        return $msg;
    }

    private function cbOrderCheck(string $chatId, int $telegramId, int $orderId, int $messageId = 0, string $callbackId = ''): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '🔗 Chưa liên kết tài khoản.', true);
            }
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '❌ Không tìm thấy đơn hàng.', true);
            }
            return;
        }

        $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? 'paid')));
        $status = strtolower(trim((string) ($order['status'] ?? '')));

        if ($paymentStatus === 'paid') {
            $message = $status === 'completed'
                ? '✅ Đơn đã thanh toán và giao hàng.'
                : '✅ Đã thanh toán. Đơn đang được xử lý.';
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, $message, true);
            }
            return;
        }

        if ($paymentStatus === 'expired' || $paymentStatus === 'cancelled' || $status === 'cancelled') {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⌛ Đơn này không còn hiệu lực thanh toán.', true);
            }
            return;
        }

        $depositModel = new PendingDeposit();
        $deposit = $depositModel->findLatestByOrderId($orderId, true);
        if (!$deposit) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⏳ Chưa có giao dịch được xác nhận.', true);
            }
            return;
        }

        $method = strtolower(trim((string) ($deposit['method'] ?? DepositService::METHOD_BANK_SEPAY)));
        if ($method !== DepositService::METHOD_BINANCE) {
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⏳ Chưa thấy chuyển khoản phù hợp. Thử lại sau 10-15s.', true);
            }
            return;
        }

        if ($depositModel->isLogicallyExpired($deposit)) {
            $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), 'Đơn hàng hết hạn thanh toán.', true);
            if ($callbackId !== '') {
                $this->telegram->answerCallbackQuery($callbackId, '⌛ Đơn hàng đã hết hạn thanh toán.', true);
            }
            return;
        }

        $siteConfig = Config::getSiteConfig();
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
        if ($callbackId !== '') {
            $this->telegram->answerCallbackQuery(
                $callbackId,
                !empty($result['success']) ? '🎉 Thanh toán thành công!' : '⚠️ Chưa xác minh được giao dịch.',
                true
            );
        }
    }

    private function cbOrderCancel(string $chatId, int $telegramId, int $orderId, int $messageId = 0): void
    {
        $user = $this->resolveLinkedUser($chatId, $telegramId);
        if (!$user) {
            return;
        }

        $order = $this->orderModel->getByIdForUser($orderId, (int) ($user['id'] ?? 0));
        if (!$order) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ Không tìm thấy đơn hàng.');
            return;
        }

        $result = $this->purchaseService->cancelTelegramPendingOrder($orderId, (int) ($user['id'] ?? 0), 'Người dùng hủy đơn.');
        if (empty($result['success'])) {
            $this->telegram->editOrSend($chatId, $messageId, '❌ ' . htmlspecialchars((string) ($result['message'] ?? 'Không thể hủy đơn.'), ENT_QUOTES, 'UTF-8'));
            return;
        }

        $this->clearBinanceSession($telegramId);

        $markup = TelegramService::buildInlineKeyboard([
            [
                ['text' => '🛍️ Mua lại', 'callback_data' => 'prod_' . (int) ($order['product_id'] ?? 0)],
                ['text' => '🏠 Menu', 'callback_data' => 'menu'],
            ]
        ]);

        $this->telegram->editOrSend($chatId, $messageId, '❌ <b>Đã hủy đơn hàng.</b>', $markup);
    }
}

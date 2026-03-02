<?php

/**
 * PurchaseService
 * Transactional wallet purchase flow for stock-based products.
 */
class PurchaseService
{
    private PDO $db;
    private Product $productModel;
    private ProductStock $stockModel;
    private ProductInventoryService $inventoryService;
    private User $userModel;
    private Order $orderModel;
    private $giftCodeModel = null;
    private ?CryptoService $crypto = null;
    private ?TimeService $timeService = null;
    private ?BalanceChangeService $balanceChangeService = null;
    private array $schemaColumnCache = [];

    public function __construct(
        ?Product $productModel = null,
        ?ProductStock $stockModel = null,
        ?User $userModel = null,
        ?Order $orderModel = null
    ) {
        $this->db = Database::getInstance()->getConnection();
        $this->productModel = $productModel ?: new Product();
        $this->stockModel = $stockModel ?: new ProductStock();
        $this->inventoryService = new ProductInventoryService($this->stockModel);
        $this->userModel = $userModel ?: new User();
        $this->orderModel = $orderModel ?: new Order();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
        if (class_exists('BalanceChangeService')) {
            $this->balanceChangeService = new BalanceChangeService($this->db);
        }
        if (class_exists('GiftCode')) {
            $this->giftCodeModel = new GiftCode();
        }
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
        $this->ensureSourceChannelSchema();
    }

    /**
     * @return array{success:bool,message:string,order?:array<string,mixed>}
     */
    public function purchaseWithWallet(int $productId, array $currentUser, array $options = []): array
    {
        $userId = (int) ($currentUser['id'] ?? 0);
        $username = (string) ($currentUser['username'] ?? '');
        $requestedQty = max(1, (int) ($options['quantity'] ?? 1));
        $customerInput = trim((string) ($options['customer_input'] ?? ''));
        $giftcodeInput = strtoupper(trim((string) ($options['giftcode'] ?? '')));
        $sourceChannel = SourceChannelHelper::normalize($options['source_channel'] ?? ($options['source'] ?? SourceChannelHelper::WEB));
        $sourceName = trim((string) ($options['source'] ?? ($sourceChannel === SourceChannelHelper::BOTTELE ? 'telegram' : 'web')));
        if ($sourceName === '') {
            $sourceName = $sourceChannel === SourceChannelHelper::BOTTELE ? 'telegram' : 'web';
        }
        if ($userId <= 0 || $username === '') {
            return ['success' => false, 'message' => 'Bạn chưa đăng nhập.'];
        }

        try {
            $this->db->beginTransaction();

            $userStmt = $this->db->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$user) {
                throw new RuntimeException('User not found');
            }
            if ((int) ($user['bannd'] ?? 0) === 1) {
                throw new RuntimeException('Tài khoản đang bị khóa.');
            }

            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$product || (string) ($product['status'] ?? '') !== 'ON') {
                throw new RuntimeException('Sản phẩm không khả dụng.');
            }
            $product = Product::normalizeRuntimeRow($product);

            $productType = (string) ($product['product_type'] ?? 'account');
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $stockManaged = Product::isStockManagedProduct($product);

            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price <= 0) {
                throw new RuntimeException('Giá sản phẩm không hợp lệ.');
            }

            $minQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
            $maxQtyConfig = max(0, (int) ($product['max_purchase_qty'] ?? 0));
            if ($requestedQty < $minQty) {
                throw new RuntimeException('Số lượng mua nhỏ hơn mức tối thiểu.');
            }

            if ($requiresInfo && $customerInput === '') {
                throw new RuntimeException('Vui lòng nhập thông tin yêu cầu trước khi mua.');
            }

            $availableStock = $this->inventoryService->getAvailableStock($product);
            if ($stockManaged) {
                $dynamicMax = $this->inventoryService->getDynamicMaxQty($product, $maxQtyConfig);
                if ($dynamicMax <= 0) {
                    throw new RuntimeException('Sản phẩm tạm hết hàng.');
                }
                if ($requestedQty > $dynamicMax) {
                    throw new RuntimeException('Số lượng mua vượt quá tồn kho hoặc giới hạn tối đa.');
                }
            } elseif ($maxQtyConfig > 0 && $requestedQty > $maxQtyConfig) {
                throw new RuntimeException('Số lượng mua vượt quá giới hạn tối đa.');
            }

            $subtotalPrice = $price * $requestedQty;
            $discountAmount = 0;
            $giftcodeMeta = null;

            if ($giftcodeInput !== '') {
                $giftcodeMeta = $this->validateGiftCodeForPurchase(
                    $giftcodeInput,
                    $productId,
                    $subtotalPrice
                );
                $discountPercent = max(0, min(100, (int) ($giftcodeMeta['giamgia'] ?? 0)));
                // Chỉ giảm giá trên 1 sản phẩm duy nhất để tránh lạm dụng (User request)
                $discountAmount = (int) floor(($price * $discountPercent) / 100);
                if ($discountAmount > $subtotalPrice) {
                    $discountAmount = $subtotalPrice;
                }
            }

            $totalPrice = max(0, $subtotalPrice - $discountAmount);
            $orderCode = $this->orderModel->generateOrderCode();

            $orderStatus = $requiresInfo ? 'pending' : 'processing';
            $orderColumns = [
                'order_code',
                'user_id',
                'username',
                'product_id',
                'product_name',
                'price',
                'status',
                'payment_method',
                'source',
                'telegram_id',
                'ip_address',
                'user_agent',
                'quantity',
                'customer_input',
                'created_at',
            ];
            $orderValues = [
                $orderCode,
                $userId,
                $username,
                $productId,
                (string) ($product['name'] ?? ('Product #' . $productId)),
                $totalPrice,
                $orderStatus,
                'wallet',
                $sourceName,
                isset($options['telegram_id']) ? (int) $options['telegram_id'] : null,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $requestedQty,
                $customerInput !== '' ? $customerInput : null,
                $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s'),
            ];

            if ($this->hasColumn('orders', 'source_channel')) {
                $orderColumns[] = 'source_channel';
                $orderValues[] = $sourceChannel;
            }

            $insertFields = '`' . implode('`, `', $orderColumns) . '`';
            $insertMarks = implode(', ', array_fill(0, count($orderColumns), '?'));
            $insertOrder = $this->db->prepare("INSERT INTO `orders` ({$insertFields}) VALUES ({$insertMarks})");
            $insertOrder->execute($orderValues);
            $orderId = (int) $this->db->lastInsertId();

            // Decrement manual stock if applicable
            if ($requiresInfo && ($product['delivery_mode'] ?? '') === 'manual_info') {
                $decStmt = $this->db->prepare("UPDATE `products` SET `manual_stock` = GREATEST(0, `manual_stock` - ?) WHERE `id` = ?");
                $decStmt->execute([$requestedQty, $productId]);
            }

            $beforeBalance = (int) ($user['money'] ?? 0);
            $afterBalance = $beforeBalance - $totalPrice;
            if ($totalPrice > 0) {
                $debitStmt = $this->db->prepare("UPDATE `users` SET `money` = `money` - ? WHERE `id` = ? AND `money` >= ?");
                $debitStmt->execute([$totalPrice, $userId, $totalPrice]);
                if ($debitStmt->rowCount() < 1) {
                    throw new RuntimeException('Số dư không đủ để thanh toán.');
                }
            }

            if ($totalPrice > 0 && $this->balanceChangeService) {
                try {
                    $this->balanceChangeService->record(
                        $userId,
                        $username,
                        $beforeBalance,
                        -$totalPrice,
                        $afterBalance,
                        'Thanh toan mua hang: ' . $orderCode,
                        $sourceChannel
                    );
                } catch (Throwable $e) {
                    // Non-blocking if log schema differs.
                }
            }

            if ($giftcodeMeta) {
                $this->markGiftCodeUsedInTransaction((int) $giftcodeMeta['id'], $giftcodeInput, $orderCode, $username);
            }

            $deliveredPlain = '';
            $firstStockId = 0;

            if ($stockManaged) {
                $allocated = $this->inventoryService->allocateInCurrentTransaction($product, $requestedQty, $userId, $orderId);
                if (!$allocated) {
                    throw new RuntimeException('San pham tam het hang.');
                }

                $firstStockId = (int) ($allocated['stock_id'] ?? 0);
                $deliveredPlain = (string) ($allocated['delivery_content'] ?? '');

                if (!$requiresInfo && $deliveredPlain !== '') {
                    $this->completeOrderDelivery($orderId, $firstStockId, $deliveredPlain);
                } elseif ($requiresInfo) {
                    $this->linkStockToPendingOrder($orderId, $firstStockId, $deliveredPlain);
                }
            } elseif (!$requiresInfo && $productType === 'link') {
                if ($requestedQty > 1) {
                    throw new RuntimeException('Loại sản phẩm này chỉ được mua tối đa 1 cái mỗi đơn hàng.');
                }
                $sourceLink = trim((string) ($product['source_link'] ?? ''));
                if ($sourceLink === '') {
                    throw new RuntimeException('Sản phẩm Source Link chưa được cấu hình link giao.');
                }
                $deliveredPlain = $sourceLink;
                $this->completeOrderDelivery($orderId, null, $deliveredPlain);
            }

            $activityStmt = $this->db->prepare("
                INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
                VALUES (?, ?, ?, ?)
            ");
            $activityStmt->execute([
                $username,
                'Mua san pham: ' . (string) ($product['name'] ?? ('#' . $productId))
                . ($giftcodeMeta ? (' | Ma giam gia: ' . $giftcodeInput) : ''),
                -$totalPrice,
                (string) ($this->timeService ? $this->timeService->nowTs() : time()),
            ]);

            $this->db->commit();

            // Enqueue Telegram notification if user is linked
            try {
                if (class_exists('UserTelegramLink') && class_exists('TelegramOutbox')) {
                    $linkModel = new UserTelegramLink();
                    $link = $linkModel->findByUserId($userId);
                    if ($link) {
                        $outbox = new TelegramOutbox();
                        $notifMsg = "🛍 <b>ĐƠN HÀNG THÀNH CÔNG</b>\n\n";
                        $notifMsg .= "Mã đơn: <code>{$orderCode}</code>\n";
                        $notifMsg .= "📦Tên SP: <b>" . ($product['name'] ?? '') . "</b>\n";
                        $notifMsg .= "💰Giá:  <b>" . number_format($totalPrice) . "đ</b>\n";
                        $notifMsg .= "SL: <b>{$requestedQty}</b>\n";

                        if ($requiresInfo) {
                            $notifMsg .= "\n⏳ Đang chờ xử lý. Admin sẽ giao hàng sớm cho bạn.";
                        } else if (!empty($deliveredPlain)) {
                            $notifMsg .= "🔑 Nội dung:\n<code>" . htmlspecialchars($deliveredPlain) . "</code>\n";
                        }
                        $notifMsg .= "━━━━━━━━━━━━━━\n";
                        $notifMsg .= "🙏 Cảm ơn bạn đã mua hàng!";

                        $outbox->enqueue((int) $link['telegram_id'], $notifMsg);
                    }
                }
            } catch (Throwable $teleErr) {
                // Non-blocking
            }

            Logger::info('Billing', 'product_purchase_success', "Mua san pham thanh cong: {$username}", [
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'user_id' => $userId,
                'product_id' => $productId,
                'price' => $price,
                'quantity' => $requestedQty,
                'stock_id' => $firstStockId,
                'requires_info' => $requiresInfo ? 1 : 0,
                'subtotal_price' => $subtotalPrice,
                'discount_amount' => $discountAmount,
                'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                'source_channel' => $sourceChannel,
            ]);

            $orderShortCode = $this->makeShortOrderDisplayCode($orderCode);

            if ($requiresInfo) {
                return [
                    'success' => true,
                    'pending' => true,
                    'message' => 'Đơn hàng đã tạo ở trạng thái chờ. Vui lòng chờ admin xử lý và giao nội dung.',
                    'order' => [
                        'id' => $orderId,
                        'order_code' => $orderCode,
                        'order_code_short' => $orderShortCode,
                        'product_name' => (string) ($product['name'] ?? ''),
                        'price' => $totalPrice,
                        'subtotal_price' => $subtotalPrice,
                        'discount_amount' => $discountAmount,
                        'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                        'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                        'unit_price' => $price,
                        'quantity' => $requestedQty,
                        'status' => 'pending',
                        'customer_input' => $customerInput,
                        'info_instructions' => (string) ($product['info_instructions'] ?? ''),
                        'created_at' => $this->timeService
                            ? $this->timeService->formatDisplay($this->timeService->nowTs(), 'H:i:s d/m/Y')
                            : date('H:i:s d/m/Y'),
                    ],
                ];
            }

            return [
                'success' => true,
                'message' => 'Thanh toán thành công!',
                'order' => [
                    'id' => $orderId,
                    'order_code' => $orderCode,
                    'order_code_short' => $orderShortCode,
                    'product_name' => (string) ($product['name'] ?? ''),
                    'price' => $totalPrice,
                    'subtotal_price' => $subtotalPrice,
                    'discount_amount' => $discountAmount,
                    'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                    'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                    'unit_price' => $price,
                    'quantity' => $requestedQty,
                    'content' => $deliveredPlain,
                    'stock_id' => $firstStockId,
                    'created_at' => $this->timeService
                        ? $this->timeService->formatDisplay($this->timeService->nowTs(), 'H:i:s d/m/Y')
                        : date('H:i:s d/m/Y'),
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            Logger::warning('Billing', 'product_purchase_failed', 'Mua san pham that bai', [
                'user_id' => $userId,
                'username' => $username,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'source_channel' => $sourceChannel,
            ]);

            if ($e instanceof RuntimeException) {
                return ['success' => false, 'message' => $e->getMessage()];
            }

            return ['success' => false, 'message' => 'Không thể xử lý đơn hàng lúc này. Vui lòng thử lại sau.'];
        }
    }

    /**
     * Preview pricing for product detail page (quantity + giftcode)
     * @return array{success:bool,message:string,pricing?:array<string,mixed>}
     */
    public function quoteForDisplay(int $productId, array $options = []): array
    {
        $requestedQty = max(1, (int) ($options['quantity'] ?? 1));
        $giftcodeInput = strtoupper(trim((string) ($options['giftcode'] ?? '')));

        try {
            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1");
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$product || (string) ($product['status'] ?? '') !== 'ON') {
                throw new RuntimeException('Sản phẩm không khả dụng.');
            }
            $product = Product::normalizeRuntimeRow($product);

            $productType = (string) ($product['product_type'] ?? 'account');
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $stockManaged = Product::isStockManagedProduct($product);
            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price <= 0) {
                throw new RuntimeException('Giá sản phẩm không hợp lệ.');
            }

            $minQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
            $maxQtyConfig = max(0, (int) ($product['max_purchase_qty'] ?? 0));

            if ($requestedQty < $minQty) {
                throw new RuntimeException('Số lượng mua nhỏ hơn mức tối thiểu.');
            }

            if ($productType === 'link' && $requestedQty > 1) {
                throw new RuntimeException('Loại sản phẩm này chỉ hỗ trợ mua tối đa 1 sản phẩm mỗi đơn.');
            }

            $availableStock = $this->inventoryService->getAvailableStock($product);
            if ($stockManaged) {
                $dynamicMax = $this->inventoryService->getDynamicMaxQty($product, $maxQtyConfig);
                if ($dynamicMax <= 0) {
                    throw new RuntimeException('Sản phẩm tạm hết hàng.');
                }
                if ($requestedQty > $dynamicMax) {
                    throw new RuntimeException('Số lượng mua vượt quá tồn kho hoặc giới hạn tối đa.');
                }
            } elseif ($maxQtyConfig > 0 && $requestedQty > $maxQtyConfig) {
                throw new RuntimeException('Số lượng mua vượt quá giới hạn tối đa.');
            }

            $subtotalPrice = $price * $requestedQty;
            $discountAmount = 0;
            $giftcodeMeta = null;

            if ($giftcodeInput !== '') {
                $giftcodeMeta = $this->validateGiftCodeGeneric($giftcodeInput, $productId, $subtotalPrice, false);
                $discountPercent = max(0, min(100, (int) ($giftcodeMeta['giamgia'] ?? 0)));
                // Chỉ giảm giá trên 1 sản phẩm duy nhất để tránh lạm dụng (User request)
                $discountAmount = (int) floor(($price * $discountPercent) / 100);
                if ($discountAmount > $subtotalPrice) {
                    $discountAmount = $subtotalPrice;
                }
            }

            $totalPrice = max(0, $subtotalPrice - $discountAmount);

            return [
                'success' => true,
                'message' => $giftcodeMeta ? 'Áp dụng mã giảm giá thành công.' : 'Đã cập nhật thành tiền.',
                'pricing' => [
                    'unit_price' => $price,
                    'quantity' => $requestedQty,
                    'subtotal_price' => $subtotalPrice,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                    'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                    'available_stock' => $availableStock,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Không thể kiểm tra mã giảm giá lúc này.',
            ];
        }
    }

    private function completeOrderDelivery(int $orderId, ?int $stockId, string $deliveryContentPlain): void
    {
        $stored = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
            ? $this->crypto->encryptString($deliveryContentPlain)
            : $deliveryContentPlain;
        $fulfilledAt = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');

        if ($stockId !== null && $stockId > 0) {
            $stmt = $this->db->prepare("
                UPDATE `orders`
                SET `status` = 'completed', `stock_id` = ?, `stock_content` = ?, `fulfilled_at` = ?
                WHERE `id` = ? LIMIT 1
            ");
            $stmt->execute([$stockId, $stored, $fulfilledAt, $orderId]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE `orders`
            SET `status` = 'completed', `stock_content` = ?, `fulfilled_at` = ?
            WHERE `id` = ? LIMIT 1
        ");
        $stmt->execute([$stored, $fulfilledAt, $orderId]);
    }

    private function linkStockToPendingOrder(int $orderId, ?int $stockId, string $deliveryContentPlain): void
    {
        $stored = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
            ? $this->crypto->encryptString($deliveryContentPlain)
            : $deliveryContentPlain;

        $stmt = $this->db->prepare("
            UPDATE `orders`
            SET `stock_id` = ?, `stock_content` = ?
            WHERE `id` = ? LIMIT 1
        ");
        $stmt->execute([$stockId, $stored, $orderId]);
    }

    private function validateGiftCodeForPurchase(string $giftcode, int $productId, int $subtotalPrice): array
    {
        return $this->validateGiftCodeGeneric($giftcode, $productId, $subtotalPrice, true);
    }

    private function validateGiftCodeGeneric(string $giftcode, int $productId, int $subtotalPrice, bool $forUpdate): array
    {
        if (!($this->giftCodeModel instanceof GiftCode)) {
            throw new RuntimeException('Hệ thống mã giảm giá chưa sẵn sàng.');
        }

        $sql = "SELECT * FROM `gift_code` WHERE `giftcode` = ? LIMIT 1" . ($forUpdate ? " FOR UPDATE" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$giftcode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            throw new RuntimeException('Mã giảm giá không tồn tại.');
        }
        if ((string) ($row['status'] ?? '') !== 'ON') {
            throw new RuntimeException('Mã giảm giá đã bị tắt.');
        }

        $soluong = (int) ($row['soluong'] ?? 0);
        $dadung = (int) ($row['dadung'] ?? 0);
        if ($soluong > 0 && $dadung >= $soluong) {
            throw new RuntimeException('Mã giảm giá đã hết lượt sử dụng.');
        }

        $expiredAt = trim((string) ($row['expired_at'] ?? ''));
        $expiredTs = $this->timeService
            ? $this->timeService->toTimestamp($expiredAt, $this->timeService->getDbTimezone())
            : (strtotime($expiredAt) ?: null);
        $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
        if ($expiredAt !== '' && $expiredTs !== null && $expiredTs < $nowTs) {
            throw new RuntimeException('Mã giảm giá đã hết hạn.');
        }

        $type = trim((string) ($row['type'] ?? 'all'));
        if ($type === 'product') {
            $productIds = array_filter(array_map('intval', explode(',', (string) ($row['product_ids'] ?? ''))));
            if (!in_array($productId, $productIds, true)) {
                throw new RuntimeException('Mã giảm giá không áp dụng cho sản phẩm này.');
            }
        }

        $minOrder = max(0, (int) ($row['min_order'] ?? 0));
        $maxOrder = max(0, (int) ($row['max_order'] ?? 0));
        if ($minOrder > 0 && $subtotalPrice < $minOrder) {
            throw new RuntimeException('Đơn hàng chưa đạt mức tối thiểu để dùng mã giảm giá.');
        }
        if ($maxOrder > 0 && $subtotalPrice > $maxOrder) {
            throw new RuntimeException('Đơn hàng vượt quá giá trị áp dụng của mã giảm giá.');
        }

        return $row;
    }

    private function markGiftCodeUsedInTransaction(int $giftCodeId, string $giftcode, string $orderCode, string $username): void
    {
        $upd = $this->db->prepare("
            UPDATE `gift_code`
            SET `dadung` = `dadung` + 1
            WHERE `id` = ?
              AND (`soluong` = 0 OR `dadung` < `soluong`)
            LIMIT 1
        ");
        $upd->execute([$giftCodeId]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Mã giảm giá vừa hết lượt. Vui lòng thử lại.');
        }

        try {
            $logStmt = $this->db->prepare("
                INSERT INTO `lich_su_mua_code` (`trans_id`, `username`, `loaicode`, `status`, `time`)
                VALUES (?, ?, ?, 'thanhcong', ?)
            ");
            $logStmt->execute([
                $orderCode,
                $username,
                $giftCodeId,
                (string) ($this->timeService ? $this->timeService->nowTs() : time()),
            ]);
        } catch (Throwable $e) {
            // Legacy optional table; do not fail purchase if logging schema differs.
        }
    }

    private function makeShortOrderDisplayCode(string $orderCode): string
    {
        return strtoupper(substr(hash('sha256', $orderCode), 0, 8));
    }

    private function ensureSourceChannelSchema(): void
    {
        try {
            if (!$this->hasColumn('orders', 'source_channel')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `source_channel` TINYINT(1) NOT NULL DEFAULT 0 AFTER `source`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            $this->db->exec("ALTER TABLE `orders` ADD KEY `idx_orders_source_created` (`source_channel`, `created_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        unset($this->schemaColumnCache['orders.source_channel']);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->schemaColumnCache)) {
            return $this->schemaColumnCache[$cacheKey];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
              AND column_name = :column_name
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        $exists = (int) $stmt->fetchColumn() > 0;
        $this->schemaColumnCache[$cacheKey] = $exists;
        return $exists;
    }
}


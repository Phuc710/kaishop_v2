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
        if (class_exists('GiftCode')) {
            $this->giftCodeModel = new GiftCode();
        }
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
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
                $discountAmount = (int) floor(($subtotalPrice * $discountPercent) / 100);
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
                (string) ($options['source'] ?? 'web'),
                isset($options['telegram_id']) ? (int) $options['telegram_id'] : null,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $requestedQty,
                $customerInput !== '' ? $customerInput : null,
                TimeService::instance()->nowSql(),
            ];

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

            if ($totalPrice > 0) {
                $debitStmt = $this->db->prepare("UPDATE `users` SET `money` = `money` - ? WHERE `id` = ? AND `money` >= ?");
                $debitStmt->execute([$totalPrice, $userId, $totalPrice]);
                if ($debitStmt->rowCount() < 1) {
                    throw new RuntimeException('Số dư không đủ để thanh toán.');
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
                (string) time(),
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
                        $notifMsg .= "Sản phẩm: <b>" . ($product['name'] ?? '') . "</b>\n";
                        $notifMsg .= "Tổng tiền: <b>" . number_format($totalPrice) . "đ</b>\n";

                        if ($requiresInfo) {
                            $notifMsg .= "\n⏳ Đang chờ xử lý. Admin sẽ giao hàng sớm cho bạn.";
                        } else if (!empty($deliveredPlain)) {
                            $notifMsg .= "\n🔑 Nội dung:\n<code>{$deliveredPlain}</code>";
                        }

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
                        'created_at' => date('H:i:s d/m/Y'),
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
                    'created_at' => date('H:i:s d/m/Y'),
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
                $discountAmount = (int) floor(($subtotalPrice * $discountPercent) / 100);
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
        $stored = $deliveryContentPlain;

        if ($stockId !== null && $stockId > 0) {
            $stmt = $this->db->prepare("
                UPDATE `orders`
                SET `status` = 'completed', `stock_id` = ?, `stock_content` = ?, `fulfilled_at` = ?
                WHERE `id` = ? LIMIT 1
            ");
            $stmt->execute([$stockId, $stored, TimeService::instance()->nowSql(), $orderId]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE `orders`
            SET `status` = 'completed', `stock_content` = ?, `fulfilled_at` = ?
            WHERE `id` = ? LIMIT 1
        ");
        $stmt->execute([$stored, TimeService::instance()->nowSql(), $orderId]);
    }

    private function linkStockToPendingOrder(int $orderId, ?int $stockId, string $deliveryContentPlain): void
    {
        $stored = $deliveryContentPlain;

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
        if ($expiredAt !== '' && strtotime($expiredAt) !== false && strtotime($expiredAt) < time()) {
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
                (string) time(),
            ]);
        } catch (Throwable $e) {
            // Legacy optional table; do not fail purchase if logging schema differs.
        }
    }

    private function makeShortOrderDisplayCode(string $orderCode): string
    {
        return strtoupper(substr(hash('sha256', $orderCode), 0, 8));
    }
}


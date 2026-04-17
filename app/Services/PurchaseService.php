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
    private ?ChatGptGuardService $guardService = null;


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
        if (class_exists('ChatGptGuardService')) {
            $this->guardService = new ChatGptGuardService();
        }

        $this->ensureSourceChannelSchema();
        $this->ensureTelegramPaymentSchema();
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
            $product = $this->assertProductAvailableForChannel(
                $productStmt->fetch(PDO::FETCH_ASSOC) ?: null,
                $this->resolveVisibilityChannel($sourceChannel)
            );

            $productType = (string) ($product['product_type'] ?? 'account');
            $deliveryMode = (string) ($product['delivery_mode'] ?? Product::resolveDeliveryMode($product));
            $isBusinessAuto = $deliveryMode === 'business_invite_auto';
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $stockManaged = Product::isStockManagedProduct($product);

            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price < 0) {
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

            $orderStatus = ($requiresInfo && !$isBusinessAuto) ? 'pending' : 'processing';
            $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
            $orderCreatedAtSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
            $orderColumns = [
                'order_code',
                'user_id',
                'username',
                'product_id',
                'product_name',
                'price',
                'subtotal_price',
                'discount_amount',
                'giftcode_code',
                'giftcode_percent',
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
                $subtotalPrice,
                $discountAmount,
                $giftcodeMeta ? $giftcodeInput : null,
                $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                $orderStatus,
                'wallet',
                $sourceName,
                isset($options['telegram_id']) ? (int) $options['telegram_id'] : null,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $requestedQty,
                $customerInput !== '' ? $customerInput : null,
                $orderCreatedAtSql,
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

            if ($deliveryMode === 'manual_info') {
                $decStmt = $this->db->prepare("UPDATE `products` SET `manual_stock` = GREATEST(0, `manual_stock` - ?) WHERE `id` = ?");
                $decStmt->execute([$requestedQty, $productId]);
            }

            $beforeBalance = (int) ($user['money'] ?? 0);
            $afterBalance = $beforeBalance - $totalPrice;
            if ($totalPrice > 0) {
                if ($totalPrice > $beforeBalance) {
                    $missingAmount = $totalPrice - $beforeBalance;
                    throw new RuntimeException('Bạn còn thiếu ' . number_format($missingAmount, 0, ',', '.') . 'đ');
                }
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
            } elseif ($isBusinessAuto) {
                $inviteResult = $this->processBusinessInviteAuto($orderId, $product, $customerInput, 'web_purchase');
                if (!$inviteResult['success']) {
                    throw new RuntimeException($inviteResult['message']);
                }
                $deliveredPlain = $inviteResult['delivery_content'] ?? '';
            }

            $nowTs = (string) ($this->timeService ? $this->timeService->nowTs() : time());
            $nowSql = $this->timeService
                ? $this->timeService->nowSql($this->timeService->getDbTimezone())
                : date('Y-m-d H:i:s', $nowTs);

            // Write to activity table — created_at is explicit (not relying on MySQL DEFAULT)
            // to ensure timezone consistency with the rest of the system (via TimeService::nowSql).
            $activityStmt = $this->db->prepare("
                INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`, `created_at`)
                VALUES (?, ?, ?, ?, ?)
            ");
            $activityStmt->execute([
                $username,
                'Mua san pham: ' . (string) ($product['name'] ?? ('#' . $productId))
                . ($giftcodeMeta ? (' | Ma giam gia: ' . $giftcodeInput) : ''),
                -$totalPrice,
                $nowTs,
                $nowSql,
            ]);

            $this->db->commit();

            $orderedAtDisplay = $this->timeService
                ? $this->timeService->formatDisplay($orderCreatedAtSql, 'H:i:s d/m/Y')
                : date('H:i:s d/m/Y', strtotime($orderCreatedAtSql) ?: time());

            $orderShortCode = $this->makeShortOrderDisplayCode($orderCode);
            $orderData = [
                'id' => $orderId,
                'order_code' => $orderCode,
                'order_code_short' => $orderShortCode,
                'product_name' => (string) ($product['name'] ?? ''),
                'price' => $price,
                'quantity' => $requestedQty,
                'total_price' => $totalPrice,
                'username' => $username,
                'customer_input' => $customerInput,
                'delivery_content' => $deliveredPlain,
                'source_label' => SourceChannelHelper::label($sourceChannel),
                'ordered_at' => $orderedAtDisplay,
            ];

            // Enqueue Telegram notification
            try {
                if (class_exists('OrderNotificationService')) {
                    $notifService = new OrderNotificationService();
                    if ($requiresInfo && !$isBusinessAuto) {
                        $notifService->notifyAdminPendingOrder($orderData);
                    } else {
                        $notifService->notifyAdminNewOrder($orderData);
                    }
                }
            } catch (Throwable $teleErr) {
                // Non-blocking
            }

            // Send email notification to user
            try {
                if ($sourceChannel === SourceChannelHelper::WEB) {
                    if (!class_exists('MailService')) {
                        require_once __DIR__ . '/MailService.php';
                    }
                    $mailData = array_merge($orderData, [
                        'status' => ($requiresInfo && !$isBusinessAuto) ? 'pending' : 'completed',
                        'product_image' => $product['image'] ?? '',
                        'source_link' => $product['source_link'] ?? '',
                        'info_instructions' => $product['info_instructions'] ?? '',
                        'delivery_mode' => $deliveryMode,
                    ]);
                    (new MailService())->sendOrderSuccess($user, $mailData, $product);
                }
            } catch (Throwable $mailErr) {
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

            if ($requiresInfo && !$isBusinessAuto) {
                return [
                    'success' => true,
                    'pending' => true,
                    'message' => 'Đơn hàng đã tạo ở trạng thái chờ. Vui lòng chờ admin xử lý và giao nội dung.',
                    'order' => array_merge($orderData, [
                        'subtotal_price' => $subtotalPrice,
                        'discount_amount' => $discountAmount,
                        'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                        'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                        'unit_price' => $price,
                        'status' => 'pending',
                        'info_instructions' => (string) ($product['info_instructions'] ?? ''),
                        'created_at' => $this->timeService
                            ? $this->timeService->formatDisplay($this->timeService->nowTs(), 'H:i:s d/m/Y')
                            : date('H:i:s d/m/Y'),
                    ]),
                ];
            }

            return [
                'success' => true,
                'message' => 'Thanh toán thành công!',
                'order' => array_merge($orderData, [
                    'subtotal_price' => $subtotalPrice,
                    'discount_amount' => $discountAmount,
                    'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                    'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                    'unit_price' => $price,
                    'content' => $deliveredPlain,
                    'stock_id' => $firstStockId,
                    'created_at' => $this->timeService
                        ? $this->timeService->formatDisplay($this->timeService->nowTs(), 'H:i:s d/m/Y')
                        : date('H:i:s d/m/Y'),
                ]),
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
     * Create a Telegram order that is waiting for external payment.
     *
     * @return array{success:bool,message:string,order?:array<string,mixed>}
     */
    public function createTelegramPendingOrder(int $productId, array $currentUser, array $options = []): array
    {
        $userId = (int) ($currentUser['id'] ?? 0);
        $username = (string) ($currentUser['username'] ?? '');
        $telegramId = (int) ($options['telegram_id'] ?? 0);
        $requestedQty = max(1, (int) ($options['quantity'] ?? 1));
        $customerInput = trim((string) ($options['customer_input'] ?? ''));
        $giftcodeInput = strtoupper(trim((string) ($options['giftcode'] ?? '')));

        if ($userId <= 0 || $username === '') {
            return ['success' => false, 'message' => 'Bạn chưa đăng nhập.'];
        }

        try {
            $startedTransaction = !$this->db->inTransaction();
            if ($startedTransaction) {
                $this->db->beginTransaction();
            }

            $this->cancelExistingPendingTelegramOrdersInTransaction($userId, 'Tạo đơn mới thay thế');

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
            $product = $this->assertProductAvailableForChannel(
                $productStmt->fetch(PDO::FETCH_ASSOC) ?: null,
                Product::CHANNEL_TELEGRAM
            );

            $productType = (string) ($product['product_type'] ?? 'account');
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $stockManaged = Product::isStockManagedProduct($product);
            $deliveryMode = (string) ($product['delivery_mode'] ?? Product::resolveDeliveryMode($product));

            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price < 0) {
                throw new RuntimeException('Giá sản phẩm không hợp lệ.');
            }

            $minQty = max(1, (int) ($product['min_purchase_qty'] ?? 1));
            $maxQtyConfig = max(0, (int) ($product['max_purchase_qty'] ?? 0));
            if ($requestedQty < $minQty) {
                throw new RuntimeException('Số lượng mua nhỏ hơn mức tối thiểu.');
            }

            if ($requiresInfo && $customerInput === '') {
                throw new RuntimeException('Vui lòng nhập thông tin yêu cầu trước khi tiếp tục.');
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

            if ($productType === 'link' && $requestedQty > 1) {
                throw new RuntimeException('Loại sản phẩm này chỉ được mua tối đa 1 cái mỗi đơn hàng.');
            }

            $subtotalPrice = $price * $requestedQty;
            $discountAmount = 0;
            $giftcodeMeta = null;
            if ($giftcodeInput !== '') {
                $giftcodeMeta = $this->validateGiftCodeForPurchase($giftcodeInput, $productId, $subtotalPrice);
                $discountPercent = max(0, min(100, (int) ($giftcodeMeta['giamgia'] ?? 0)));
                $discountAmount = (int) floor(($price * $discountPercent) / 100);
                if ($discountAmount > $subtotalPrice) {
                    $discountAmount = $subtotalPrice;
                }
            }

            $totalPrice = max(0, $subtotalPrice - $discountAmount);
            $orderCode = $this->orderModel->generateOrderCode();
            $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
            $nowSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
            $ttl = (int) (new PendingDeposit())->getPendingTtlSeconds();
            $paymentExpiresAt = $this->timeService
                ? $this->timeService->formatDb($nowTs + $ttl)
                : date('Y-m-d H:i:s', $nowTs + $ttl);

            $orderColumns = [
                'order_code',
                'user_id',
                'username',
                'product_id',
                'product_name',
                'price',
                'status',
                'payment_method',
                'payment_status',
                'payment_expires_at',
                'ip_address',
                'user_agent',
                'source',
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
                'pending',
                'telegram_payment',
                'pending',
                $paymentExpiresAt,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'telegram',
                $requestedQty,
                $customerInput !== '' ? $customerInput : null,
                $nowSql,
            ];

            if ($this->hasColumn('orders', 'source_channel')) {
                $orderColumns[] = 'source_channel';
                $orderValues[] = SourceChannelHelper::BOTTELE;
            }
            if ($this->hasColumn('orders', 'telegram_id')) {
                $orderColumns[] = 'telegram_id';
                $orderValues[] = $telegramId > 0 ? $telegramId : null;
            }
            if ($this->hasColumn('orders', 'subtotal_price')) {
                $orderColumns[] = 'subtotal_price';
                $orderValues[] = $subtotalPrice;
            }
            if ($this->hasColumn('orders', 'discount_amount')) {
                $orderColumns[] = 'discount_amount';
                $orderValues[] = $discountAmount;
            }
            if ($this->hasColumn('orders', 'giftcode_code')) {
                $orderColumns[] = 'giftcode_code';
                $orderValues[] = $giftcodeMeta ? $giftcodeInput : null;
            }
            if ($this->hasColumn('orders', 'giftcode_percent')) {
                $orderColumns[] = 'giftcode_percent';
                $orderValues[] = $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0;
            }

            $insertFields = '`' . implode('`, `', $orderColumns) . '`';
            $insertMarks = implode(', ', array_fill(0, count($orderColumns), '?'));
            $insertOrder = $this->db->prepare("INSERT INTO `orders` ({$insertFields}) VALUES ({$insertMarks})");
            $insertOrder->execute($orderValues);
            $orderId = (int) $this->db->lastInsertId();

            if ($stockManaged) {
                $allocated = $this->inventoryService->allocateInCurrentTransaction($product, $requestedQty, $userId, $orderId);
                if (!$allocated) {
                    throw new RuntimeException('Sản phẩm tạm hết hàng.');
                }
                $this->linkStockToPendingOrder($orderId, (int) ($allocated['stock_id'] ?? 0), (string) ($allocated['delivery_content'] ?? ''));
            } elseif ($deliveryMode === 'manual_info') {
                $reserveStmt = $this->db->prepare("UPDATE `products` SET `manual_stock` = `manual_stock` - ? WHERE `id` = ? AND `manual_stock` >= ?");
                $reserveStmt->execute([$requestedQty, $productId, $requestedQty]);
                if ($reserveStmt->rowCount() < 1) {
                    throw new RuntimeException('Sản phẩm tạm hết hàng.');
                }
            }

            if ($giftcodeMeta) {
                $this->markGiftCodeUsedInTransaction((int) $giftcodeMeta['id'], $giftcodeInput, $orderCode, $username);
            }

            if ($startedTransaction) {
                $this->db->commit();
            }

            $orderShortCode = $this->makeShortOrderDisplayCode($orderCode);
            return [
                'success' => true,
                'message' => 'Đã tạo đơn chờ thanh toán.',
                'order' => [
                    'id' => $orderId,
                    'order_code' => $orderCode,
                    'order_code_short' => $orderShortCode,
                    'product_id' => $productId,
                    'product_name' => (string) ($product['name'] ?? ''),
                    'quantity' => $requestedQty,
                    'unit_price' => $price,
                    'subtotal_price' => $subtotalPrice,
                    'discount_amount' => $discountAmount,
                    'giftcode' => $giftcodeMeta ? $giftcodeInput : null,
                    'giftcode_percent' => $giftcodeMeta ? (int) ($giftcodeMeta['giamgia'] ?? 0) : 0,
                    'total_price' => $totalPrice,
                    'customer_input' => $customerInput,
                    'payment_status' => 'pending',
                    'payment_expires_at' => $paymentExpiresAt,
                    'requires_info' => $requiresInfo ? 1 : 0,
                    'delivery_mode' => $deliveryMode,
                ],
            ];
        } catch (Throwable $e) {
            if (($startedTransaction ?? false) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Không thể tạo đơn hàng lúc này.'];
        }
    }

    /**
     * @return array{success:bool,message:string,payment?:array<string,mixed>,order?:array<string,mixed>}
     */
    public function activateTelegramOrderPayment(int $orderId, int $userId, string $method, array $paymentContext = []): array
    {
        $method = trim(strtolower($method));
        if (!in_array($method, [DepositService::METHOD_BANK_SEPAY, DepositService::METHOD_BINANCE], true)) {
            return ['success' => false, 'message' => 'Phương thức thanh toán không hợp lệ.'];
        }

        $order = $this->orderModel->getByIdForUser($orderId, $userId);
        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        }

        if ((string) ($order['payment_status'] ?? 'paid') !== 'pending' || (string) ($order['status'] ?? '') !== 'pending') {
            return ['success' => false, 'message' => 'Đơn hàng này không còn chờ thanh toán.'];
        }

        $expiresAt = trim((string) ($order['payment_expires_at'] ?? ''));
        $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
        $expiresTs = 0;
        if ($expiresAt !== '') {
            $assumeTz = $this->timeService ? $this->timeService->getDbTimezone() : 'Asia/Ho_Chi_Minh';
            $expiresTs = $this->timeService ? ($this->timeService->toTimestamp($expiresAt, $assumeTz) ?? 0) : (strtotime($expiresAt) ?: 0);
        }

        if ($expiresTs > 0 && $nowTs >= $expiresTs) {
            $this->cancelTelegramPendingOrder($orderId, $userId, 'Đơn hàng hết hạn thanh toán.', true);
            return ['success' => false, 'message' => 'Đơn hàng đã hết hạn thanh toán.'];
        }

        $pendingDepositModel = new PendingDeposit();
        $pendingDepositModel->cancelPendingByOrderId($orderId);

        $user = $this->userModel->find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Không tìm thấy tài khoản người dùng.'];
        }

        $siteConfig = Config::getSiteConfig();
        $totalPrice = (int) ($order['price'] ?? 0);
        if ($totalPrice < 0) {
            return ['success' => false, 'message' => 'Giá trị đơn hàng không hợp lệ.'];
        }

        if ($method === DepositService::METHOD_BANK_SEPAY) {
            $result = $this->buildOrderBankPayment($user, $totalPrice, $siteConfig, $orderId, $paymentContext);
        } else {
            $payerUid = trim((string) ($paymentContext['payer_uid'] ?? ''));
            $result = $this->buildOrderBinancePayment($user, $totalPrice, $payerUid, $siteConfig, $orderId, $paymentContext);
        }

        if (empty($result['success'])) {
            return $result;
        }

        try {
            $paymentCode = (string) (($result['data']['deposit_code'] ?? ''));
            $updateCols = [];
            $params = [];
            if ($this->hasColumn('orders', 'payment_method')) {
                $updateCols[] = "`payment_method` = ?";
                $params[] = $method;
            }
            if ($this->hasColumn('orders', 'cancel_reason')) {
                $updateCols[] = "`cancel_reason` = NULL";
            }
            if ($this->hasColumn('orders', 'updated_at')) {
                // keep MySQL auto-updated where available
            }
            if ($this->hasColumn('orders', 'customer_input')) {
                // no-op, kept for schema awareness
            }

            if (!empty($updateCols)) {
                $params[] = $orderId;
                $stmt = $this->db->prepare("UPDATE `orders` SET " . implode(', ', $updateCols) . " WHERE `id` = ? LIMIT 1");
                $stmt->execute($params);
            }
        } catch (Throwable $e) {
            // non-blocking
        }

        return [
            'success' => true,
            'message' => 'Đã tạo phiên thanh toán.',
            'order' => $order,
            'payment' => (array) ($result['data'] ?? []),
        ];
    }

    public function storeTelegramPaymentMessageId(int $orderId, int $messageId): void
    {
        if ($orderId <= 0 || $messageId <= 0) {
            return;
        }

        try {
            if ($this->hasColumn('orders', 'telegram_message_id')) {
                $stmt = $this->db->prepare("UPDATE `orders` SET `telegram_message_id` = ? WHERE `id` = ? LIMIT 1");
                $stmt->execute([$messageId, $orderId]);
            }
        } catch (Throwable $e) {
            // non-blocking
        }

        try {
            if (class_exists('PendingDeposit')) {
                (new PendingDeposit())->updateTelegramMessageIdByOrderId($orderId, $messageId);
            }
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    public function resolveTelegramPaymentMessageId(array $order, array $deposit = []): int
    {
        $messageId = (int) ($order['telegram_message_id'] ?? $deposit['telegram_message_id'] ?? 0);
        if ($messageId > 0) {
            return $messageId;
        }

        $orderId = (int) ($order['id'] ?? $deposit['order_id'] ?? 0);
        if ($orderId <= 0 || !class_exists('PendingDeposit')) {
            return 0;
        }

        try {
            return (new PendingDeposit())->findTelegramMessageIdByOrderId($orderId);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * @param array<string,mixed> $deposit
     * @param array<string,mixed> $paymentMeta
     * @return array{success:bool,message:string,order?:array<string,mixed>}
     */
    public function finalizeTelegramOrderPayment(array $deposit, array $paymentMeta = []): array
    {
        $orderId = (int) ($deposit['order_id'] ?? 0);
        if ($orderId <= 0 && (!isset($deposit['method']) || $deposit['method'] !== 'free')) {
            return ['success' => false, 'message' => 'Deposit không gắn với đơn hàng nào.'];
        }

        if (isset($deposit['method']) && $deposit['method'] === 'free') {
            $orderId = (int) ($deposit['order_id'] ?? 0);
        }


        try {
            $startedTransaction = !$this->db->inTransaction();
            if ($startedTransaction) {
                $this->db->beginTransaction();
            }

            $order = $this->orderModel->getByIdForUpdate($orderId);
            if (!$order) {
                throw new RuntimeException('Đơn hàng không tồn tại.');
            }

            $paymentStatus = (string) ($order['payment_status'] ?? 'paid');
            $status = (string) ($order['status'] ?? '');
            if ($paymentStatus === 'paid') {
                if ($startedTransaction) {
                    $this->db->commit();
                }
                return ['success' => true, 'message' => 'Đơn hàng đã được thanh toán trước đó.', 'order' => $order];
            }
            if ($paymentStatus !== 'pending' || $status !== 'pending') {
                throw new RuntimeException('Đơn hàng không còn ở trạng thái chờ thanh toán.');
            }

            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $productStmt->execute([(int) ($order['product_id'] ?? 0)]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$product) {
                throw new RuntimeException('Sản phẩm không còn tồn tại.');
            }
            $product = Product::normalizeRuntimeRow($product);
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $deliveryMode = (string) ($product['delivery_mode'] ?? Product::resolveDeliveryMode($product));
            $stockManaged = Product::isStockManagedProduct($product);

            $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
            $nowSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
            $finalStatus = $requiresInfo ? 'processing' : 'completed';

            $updCols = [
                "`payment_status` = 'paid'",
                "`payment_method` = ?",
            ];
            $updParams = [(string) ($deposit['method'] ?? 'bank_sepay')];
            if ($finalStatus === 'processing') {
                $updCols[] = "`status` = 'processing'";
            }
            $updParams[] = $orderId;
            $stmt = $this->db->prepare("UPDATE `orders` SET " . implode(', ', $updCols) . " WHERE `id` = ? LIMIT 1");
            $stmt->execute($updParams);

            $deliveredPlain = trim((string) ($order['stock_content_plain'] ?? ''));
            if (!$requiresInfo) {
                if ($deliveryMode === 'source_link') {
                    $sourceLink = trim((string) ($product['source_link'] ?? ''));
                    if ($sourceLink === '') {
                        throw new RuntimeException('Sản phẩm Source Link chưa được cấu hình nội dung giao.');
                    }
                    $deliveredPlain = $sourceLink;
                    $this->completeOrderDelivery($orderId, null, $deliveredPlain);
                } elseif ($stockManaged) {
                    $this->completeOrderDelivery($orderId, (int) ($order['stock_id'] ?? 0), $deliveredPlain);
                }
            } elseif ($deliveryMode === 'business_invite_auto') {
                $customerInput = (string) ($order['customer_input'] ?? '');
                $inviteResult = $this->processBusinessInviteAuto($orderId, $product, $customerInput, 'telegram_payment_bot');
                if ($inviteResult['success']) {
                    $deliveredPlain = $inviteResult['delivery_content'] ?? '';
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }

            $freshOrder = $this->orderModel->getById($orderId) ?: $order;
            $freshOrder['delivery_content'] = $deliveredPlain;
            $freshOrder['total_price'] = (int) ($freshOrder['price'] ?? 0);
            $freshOrder['unit_price'] = (int) floor(((int) ($freshOrder['price'] ?? 0)) / max(1, (int) ($freshOrder['quantity'] ?? 1)));
            if ((int) ($freshOrder['telegram_message_id'] ?? 0) <= 0 && (int) ($deposit['telegram_message_id'] ?? 0) > 0) {
                $freshOrder['telegram_message_id'] = (int) $deposit['telegram_message_id'];
            }

            try {
                if (class_exists('OrderNotificationService')) {
                    $notifService = new OrderNotificationService();
                    if ((string) ($freshOrder['status'] ?? '') !== 'completed') {
                        $notifService->notifyAdminPendingOrder($freshOrder);
                    } else {
                        $notifService->notifyAdminNewOrder($freshOrder);
                    }
                }
            } catch (Throwable $e) {
                // non-blocking
            }

            return ['success' => true, 'message' => 'Đã xác nhận thanh toán đơn hàng.', 'order' => $freshOrder];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Không thể xác nhận thanh toán lúc này.'];
        }
    }

    public function cancelTelegramPendingOrder(int $orderId, int $userId = 0, string $reason = 'Người dùng hủy đơn.', bool $markExpired = false): array
    {
        if ($orderId <= 0) {
            return ['success' => false, 'message' => 'Đơn hàng không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();

            $order = $this->orderModel->getByIdForUpdate($orderId);
            if (!$order) {
                throw new RuntimeException('Đơn hàng không tồn tại.');
            }
            if ($userId > 0 && (int) ($order['user_id'] ?? 0) !== $userId) {
                throw new RuntimeException('Bạn không có quyền thao tác đơn này.');
            }

            $paymentStatus = (string) ($order['payment_status'] ?? 'paid');
            $status = (string) ($order['status'] ?? '');
            if ($paymentStatus !== 'pending' || $status !== 'pending') {
                throw new RuntimeException('Đơn hàng này không còn chờ thanh toán.');
            }

            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $productStmt->execute([(int) ($order['product_id'] ?? 0)]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $product = $product ? Product::normalizeRuntimeRow($product) : [];
            $deliveryMode = (string) ($product['delivery_mode'] ?? '');

            $nextPaymentStatus = $markExpired ? 'expired' : 'cancelled';
            $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
            $nowSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
            $stmt = $this->db->prepare("
                UPDATE `orders`
                SET `status` = 'cancelled', `payment_status` = ?, `cancel_reason` = ?, `fulfilled_at` = ?
                WHERE `id` = ? LIMIT 1
            ");
            $stmt->execute([$nextPaymentStatus, $reason, $nowSql, $orderId]);

            $this->stockModel->releaseByOrderId($orderId);
            if ($deliveryMode === 'manual_info' && $product !== []) {
                $restoreQty = max(1, (int) ($order['quantity'] ?? 1));
                $this->db->prepare("UPDATE `products` SET `manual_stock` = `manual_stock` + ? WHERE `id` = ? LIMIT 1")
                    ->execute([$restoreQty, (int) ($product['id'] ?? 0)]);
            }

            $giftcodeCode = trim((string) ($order['giftcode_code'] ?? ''));
            if ($giftcodeCode !== '') {
                $this->releaseGiftCodeUsageInTransaction($giftcodeCode);
            }

            (new PendingDeposit())->cancelPendingByOrderId($orderId);

            $this->db->commit();
            return ['success' => true, 'message' => $markExpired ? 'Đơn hàng đã hết hạn.' : 'Đã hủy đơn hàng.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Không thể hủy đơn lúc này.'];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function expireTelegramPendingOrders(): array
    {
        if (!$this->hasColumn('orders', 'payment_status') || !$this->hasColumn('orders', 'payment_expires_at')) {
            return [];
        }

        $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
        $nowSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
        $stmt = $this->db->prepare("
            SELECT `id`, `user_id`, `telegram_id`, `order_code`, `product_name`, `price`, `quantity`, `payment_method`
            FROM `orders`
            WHERE `status` = 'pending' AND `payment_status` = 'pending' AND `payment_expires_at` IS NOT NULL AND `payment_expires_at` <= ?
            ORDER BY `id` ASC
            LIMIT 50
        ");
        $stmt->execute([$nowSql]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $expired = [];
        foreach ($rows as $row) {
            $res = $this->cancelTelegramPendingOrder((int) ($row['id'] ?? 0), 0, 'Đơn hàng đã hết hạn thanh toán.', true);
            if (!empty($res['success'])) {
                $expired[] = $row;
            }
        }

        return $expired;
    }

    private function ensureTelegramPaymentSchema(): void
    {
        try {
            if (!$this->hasColumn('orders', 'payment_status')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT 'paid' AFTER `payment_method`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'payment_expires_at')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `payment_expires_at` DATETIME NULL AFTER `payment_status`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'telegram_message_id')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `telegram_message_id` BIGINT(20) NULL AFTER `payment_expires_at`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'subtotal_price')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `subtotal_price` BIGINT(20) NOT NULL DEFAULT 0 AFTER `price`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'discount_amount')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `discount_amount` BIGINT(20) NOT NULL DEFAULT 0 AFTER `subtotal_price`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'giftcode_code')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `giftcode_code` VARCHAR(100) NULL AFTER `discount_amount`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            if (!$this->hasColumn('orders', 'giftcode_percent')) {
                $this->db->exec("ALTER TABLE `orders` ADD COLUMN `giftcode_percent` INT(11) NOT NULL DEFAULT 0 AFTER `giftcode_code`");
            }
        } catch (Throwable $e) {
            // ignore if ALTER is restricted
        }

        try {
            $this->db->exec("ALTER TABLE `orders` ADD KEY `idx_orders_payment_status_expiry` (`payment_status`, `payment_expires_at`)");
        } catch (Throwable $e) {
            // ignore if key exists or ALTER is restricted
        }

        unset(
            $this->schemaColumnCache['orders.payment_status'],
            $this->schemaColumnCache['orders.payment_expires_at'],
            $this->schemaColumnCache['orders.telegram_message_id'],
            $this->schemaColumnCache['orders.subtotal_price'],
            $this->schemaColumnCache['orders.discount_amount'],
            $this->schemaColumnCache['orders.giftcode_code'],
            $this->schemaColumnCache['orders.giftcode_percent']
        );
    }

    private function cancelExistingPendingTelegramOrdersInTransaction(int $userId, string $reason): void
    {
        if ($userId <= 0 || !$this->hasColumn('orders', 'payment_status')) {
            return;
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM `orders`
            WHERE `user_id` = ?
              AND `status` = 'pending'
              AND `payment_status` = 'pending'
              AND (`user_deleted_at` IS NULL OR `user_deleted_at` = '')
            ORDER BY `id` ASC
            FOR UPDATE
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $this->cancelTelegramPendingOrderInTransaction($row, $reason, false);
        }
    }

    /**
     * @param array<string,mixed> $order
     */
    private function cancelTelegramPendingOrderInTransaction(array $order, string $reason, bool $markExpired): void
    {
        $orderId = (int) ($order['id'] ?? 0);
        if ($orderId <= 0) {
            return;
        }

        $paymentStatus = (string) ($order['payment_status'] ?? 'paid');
        $status = (string) ($order['status'] ?? '');
        if ($paymentStatus !== 'pending' || $status !== 'pending') {
            return;
        }

        $product = [];
        $productId = (int) ($order['product_id'] ?? 0);
        if ($productId > 0) {
            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $productStmt->execute([$productId]);
            $productRow = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($productRow) {
                $product = Product::normalizeRuntimeRow($productRow);
            }
        }

        $nextPaymentStatus = $markExpired ? 'expired' : 'cancelled';
        $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
        $nowSql = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);
        $this->db->prepare("
            UPDATE `orders`
            SET `status` = 'cancelled', `payment_status` = ?, `cancel_reason` = ?, `fulfilled_at` = ?
            WHERE `id` = ? LIMIT 1
        ")->execute([$nextPaymentStatus, $reason, $nowSql, $orderId]);

        $this->stockModel->releaseByOrderId($orderId);

        if ((string) ($product['delivery_mode'] ?? '') === 'manual_info' && !empty($product)) {
            $restoreQty = max(1, (int) ($order['quantity'] ?? 1));
            $this->db->prepare("UPDATE `products` SET `manual_stock` = `manual_stock` + ? WHERE `id` = ? LIMIT 1")
                ->execute([$restoreQty, (int) ($product['id'] ?? 0)]);
        }

        $giftcodeCode = trim((string) ($order['giftcode_code'] ?? ''));
        if ($giftcodeCode !== '') {
            $this->releaseGiftCodeUsageInTransaction($giftcodeCode);
        }

        (new PendingDeposit())->cancelPendingByOrderId($orderId);
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     * @param array<string,mixed> $paymentContext
     * @return array<string,mixed>
     */
    private function buildOrderBankPayment(array $user, int $amount, array $siteConfig, int $orderId, array $paymentContext = []): array
    {
        $pendingDeposit = new PendingDeposit();
        $depositResult = $pendingDeposit->createDeposit(
            (int) ($user['id'] ?? 0),
            (string) ($user['username'] ?? ''),
            max(0, $amount),
            0,
            SourceChannelHelper::BOTTELE,
            $orderId,
            false
        );

        if (!$depositResult) {
            return ['success' => false, 'message' => 'KhÃ´ng thá»ƒ táº¡o phiÃªn thanh toÃ¡n ngÃ¢n hÃ ng.'];
        }

        $depositService = new DepositService($pendingDeposit);
        $bankName = trim((string) ($siteConfig['bank_name'] ?? 'MB Bank'));
        $bankAccount = trim((string) ($siteConfig['bank_account'] ?? ''));
        $bankOwner = trim((string) ($siteConfig['bank_owner'] ?? ''));
        $expiresAt = (string) ($depositResult['expires_at'] ?? '');

        return [
            'success' => true,
            'data' => [
                'payment_type' => 'order',
                'order_id' => $orderId,
                'method' => DepositService::METHOD_BANK_SEPAY,
                'deposit_code' => (string) ($depositResult['deposit_code'] ?? ''),
                'amount' => $amount,
                'bonus_percent' => 0,
                'bonus_amount' => 0,
                'total_receive' => $amount,
                'status' => 'pending',
                'status_text' => 'Äang chá» xá»­ lÃ½',
                'source_channel' => SourceChannelHelper::BOTTELE,
                'expires_at' => $expiresAt,
                'expires_at_ts' => $this->timeService
                    ? (int) ($this->timeService->toTimestamp($expiresAt, $this->timeService->getDbTimezone()) ?? 0)
                    : (strtotime($expiresAt) ?: 0),
                'ttl_seconds' => $pendingDeposit->getPendingTtlSeconds(),
                'server_now_ts' => $this->timeService ? $this->timeService->nowTs() : time(),
                'bank_name' => $bankName,
                'bank_short_name' => $depositService->resolveQrBankName($bankName),
                'bank_account' => $bankAccount,
                'bank_owner' => $bankOwner,
                'qr_url' => $depositService->buildVietQrUrl(
                    $bankName,
                    $bankAccount,
                    $amount,
                    (string) ($depositResult['deposit_code'] ?? ''),
                    $bankOwner
                ),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $siteConfig
     * @param array<string,mixed> $paymentContext
     * @return array<string,mixed>
     */
    private function buildOrderBinancePayment(array $user, int $amount, string $payerUid, array $siteConfig, int $orderId, array $paymentContext = []): array
    {
        $payerUid = trim($payerUid);
        if (!preg_match('/^\d{4,20}$/', $payerUid)) {
            return ['success' => false, 'message' => 'Invalid Binance UID.'];
        }

        if (!class_exists('BinancePayService')) {
            return ['success' => false, 'message' => 'Binance Pay is not ready.'];
        }

        $binanceService = new BinancePayService($siteConfig, $this->db);
        if (!$binanceService->isEnabled()) {
            return ['success' => false, 'message' => 'Binance Pay is not configured.'];
        }

        $rawUsdt = isset($paymentContext['usdt_amount'])
            ? (float) $paymentContext['usdt_amount']
            : ((float) ceil(($amount / max(1, $binanceService->getExchangeRate())) * 100) / 100);
        $usdtAmount = round(max(BinancePayService::MIN_USDT, min(BinancePayService::MAX_USDT, $rawUsdt)), 8);

        $pendingDeposit = new PendingDeposit();
        $depositResult = $pendingDeposit->createBinanceDeposit(
            (int) ($user['id'] ?? 0),
            (string) ($user['username'] ?? ''),
            max(0, $amount),
            $usdtAmount,
            $payerUid,
            0,
            SourceChannelHelper::BOTTELE,
            $orderId,
            false
        );

        if (!$depositResult) {
            return ['success' => false, 'message' => 'Could not create a Binance payment session.'];
        }

        $expiresAt = (string) ($depositResult['expires_at'] ?? '');
        $transferNote = $binanceService->getTransferNote((string) ($depositResult['deposit_code'] ?? ''));

        return [
            'success' => true,
            'data' => [
                'payment_type' => 'order',
                'order_id' => $orderId,
                'method' => DepositService::METHOD_BINANCE,
                'deposit_code' => (string) ($depositResult['deposit_code'] ?? ''),
                'amount' => $amount,
                'usd_amount' => $usdtAmount,
                'usdt_amount' => $usdtAmount,
                'payer_uid' => $payerUid,
                'exchange_rate' => $binanceService->getExchangeRate(),
                'binance_uid' => $binanceService->getUid(),
                'binance_owner' => trim((string) ($siteConfig['binance_owner'] ?? get_setting('ten_web', 'KaiShop'))),
                'transfer_note' => $transferNote,
                'note_text' => 'Send the exact amount from the correct payer UID for auto matching.',
                'warning_rules' => [
                    'Send USDT only from Binance Funding.',
                    'Payer UID, receiver UID, and amount must match.',
                    'This payment session expires in 5 minutes.',
                ],
                'bonus_percent' => 0,
                'bonus_vnd' => 0,
                'total_receive' => $amount,
                'source_channel' => SourceChannelHelper::BOTTELE,
                'status' => 'pending',
                'status_text' => 'Pending',
                'expires_at' => $expiresAt,
                'expires_at_ts' => $this->timeService
                    ? (int) ($this->timeService->toTimestamp($expiresAt, $this->timeService->getDbTimezone()) ?? 0)
                    : (strtotime($expiresAt) ?: 0),
                'ttl_seconds' => $pendingDeposit->getPendingTtlSeconds(),
                'server_now_ts' => $this->timeService ? $this->timeService->nowTs() : time(),
            ],
        ];
    }

    private function releaseGiftCodeUsageInTransaction(string $giftcode): void
    {
        $giftcode = strtoupper(trim($giftcode));
        if ($giftcode === '' || !($this->giftCodeModel instanceof GiftCode)) {
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE `gift_code`
            SET `dadung` = GREATEST(0, `dadung` - 1)
            WHERE `giftcode` = ?
            LIMIT 1
        ");
        $stmt->execute([$giftcode]);
    }

    /**
     * Preview pricing for product detail page (quantity + giftcode)
     * @return array{success:bool,message:string,pricing?:array<string,mixed>}
     */
    public function quoteForDisplay(int $productId, array $options = []): array
    {
        $requestedQty = max(1, (int) ($options['quantity'] ?? 1));
        $giftcodeInput = strtoupper(trim((string) ($options['giftcode'] ?? '')));
        $visibilityChannel = $this->resolveVisibilityChannel($options['source_channel'] ?? ($options['source'] ?? Product::CHANNEL_WEB));

        try {
            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1");
            $productStmt->execute([$productId]);
            $product = $this->assertProductAvailableForChannel(
                $productStmt->fetch(PDO::FETCH_ASSOC) ?: null,
                $visibilityChannel
            );

            $productType = (string) ($product['product_type'] ?? 'account');
            $requiresInfo = (int) ($product['requires_info'] ?? 0) === 1;
            $stockManaged = Product::isStockManagedProduct($product);
            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price < 0) {
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

    private function assertProductAvailableForChannel(?array $product, string $channel): array
    {
        if (!$product) {
            throw new RuntimeException('Sản phẩm không khả dụng.');
        }

        $product = Product::normalizeRuntimeRow($product);
        if (!Product::isVisibleOnChannel($product, $channel)) {
            throw new RuntimeException('Sản phẩm không khả dụng.');
        }

        return $product;
    }

    private function resolveVisibilityChannel($source): string
    {
        if (is_int($source) || ctype_digit((string) $source)) {
            return ((int) $source) === SourceChannelHelper::BOTTELE
                ? Product::CHANNEL_TELEGRAM
                : Product::CHANNEL_WEB;
        }

        $source = strtolower(trim((string) $source));
        if (in_array($source, ['telegram', 'bottele', Product::CHANNEL_TELEGRAM], true)) {
            return Product::CHANNEL_TELEGRAM;
        }

        return Product::CHANNEL_WEB;
    }

    private function completeOrderDelivery(int $orderId, ?int $stockId, string $deliveryContentPlain): void
    {
        $stored = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
            ? $this->crypto->encryptString($deliveryContentPlain)
            : $deliveryContentPlain;
        $nowTs = $this->timeService ? $this->timeService->nowTs() : time();
        $fulfilledAt = $this->timeService ? $this->timeService->formatDb($nowTs) : date('Y-m-d H:i:s', $nowTs);

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

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $product
     * @param array<string,mixed> $order
     */
    private function sendOrderSuccessMailNonBlocking(array $user, array $product, array $order, int $sourceChannel): void
    {
        if ($sourceChannel !== SourceChannelHelper::WEB) {
            return;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return;
        }

        try {
            if (!class_exists('MailService')) {
                require_once __DIR__ . '/MailService.php';
            }
            (new MailService())->sendOrderSuccess($user, $order, $product);
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * Process business_invite_auto fulfillment
     */
    public function processBusinessInviteAuto(int $orderId, array $product, string $customerEmail, string $actor = 'system'): array
    {
        if (!$this->guardService) {
            return ['success' => false, 'message' => 'ChatGptGuardService not available.'];
        }

        $result = $this->guardService->createAutoInviteForOrder($orderId, $product, $customerEmail, $actor);

        if ($result['success']) {
            $deliveryContent = "✅ Invitation sent to: " . $customerEmail . "\n"
                . "Farm: " . $result['farm_name'] . "\n"
                . "Expires at: " . $result['expires_at'] . "\n"
                . "Instructions: Please check your email inbox (and spam folder) for the invitation link from OpenAI.";

            // Update the main order with the delivery content
            $this->completeOrderDelivery($orderId, null, $deliveryContent);

            return array_merge($result, ['delivery_content' => $deliveryContent]);
        }

        return $result;
    }
}

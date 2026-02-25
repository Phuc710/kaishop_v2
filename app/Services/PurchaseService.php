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
    private User $userModel;
    private Order $orderModel;
    private ?CryptoService $crypto = null;

    public function __construct(
        ?Product $productModel = null,
        ?ProductStock $stockModel = null,
        ?User $userModel = null,
        ?Order $orderModel = null
    ) {
        $this->db = Database::getInstance()->getConnection();
        $this->productModel = $productModel ?: new Product();
        $this->stockModel = $stockModel ?: new ProductStock();
        $this->userModel = $userModel ?: new User();
        $this->orderModel = $orderModel ?: new Order();
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
    }

    /**
     * @return array{success:bool,message:string,order?:array<string,mixed>}
     */
    public function purchaseWithWallet(int $productId, array $currentUser): array
    {
        $userId = (int) ($currentUser['id'] ?? 0);
        $username = (string) ($currentUser['username'] ?? '');
        if ($userId <= 0 || $username === '') {
            return ['success' => false, 'message' => 'Ban chua dang nhap.'];
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
                throw new RuntimeException('Tai khoan dang bi khoa.');
            }

            $productStmt = $this->db->prepare("SELECT * FROM `products` WHERE `id` = ? LIMIT 1 FOR UPDATE");
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$product || (string) ($product['status'] ?? '') !== 'ON') {
                throw new RuntimeException('San pham khong kha dung.');
            }

            $productType = (string) ($product['product_type'] ?? 'account');
            if ($productType !== 'account') {
                throw new RuntimeException('San pham nay chua ho tro mua tu dong.');
            }

            $price = (int) ($product['price_vnd'] ?? 0);
            if ($price <= 0) {
                throw new RuntimeException('Gia san pham khong hop le.');
            }

            if ((int) ($user['money'] ?? 0) < $price) {
                throw new RuntimeException('So du khong du de thanh toan.');
            }

            $orderCode = $this->orderModel->generateOrderCode();

            $insertOrder = $this->db->prepare("
                INSERT INTO `orders`
                (`order_code`, `user_id`, `username`, `product_id`, `product_name`, `price`, `status`, `payment_method`, `ip_address`, `user_agent`)
                VALUES (?, ?, ?, ?, ?, ?, 'processing', 'wallet', ?, ?)
            ");
            $insertOrder->execute([
                $orderCode,
                $userId,
                $username,
                $productId,
                (string) ($product['name'] ?? ('Product #' . $productId)),
                $price,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $stock = $this->stockModel->claimOneInCurrentTransaction($productId, $userId, $orderId);
            if (!$stock) {
                throw new RuntimeException('San pham tam het hang.');
            }

            $debitStmt = $this->db->prepare("UPDATE `users` SET `money` = `money` - ? WHERE `id` = ? AND `money` >= ?");
            $debitStmt->execute([$price, $userId, $price]);
            if ($debitStmt->rowCount() < 1) {
                throw new RuntimeException('So du khong du de thanh toan.');
            }

            $completeOrder = $this->db->prepare("
                UPDATE `orders`
                SET `status` = 'completed', `stock_id` = ?, `stock_content` = ?
                WHERE `id` = ? LIMIT 1
            ");
            $stockContentPlain = (string) ($stock['content'] ?? '');
            $stockContentStored = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
                ? $this->crypto->encryptString($stockContentPlain)
                : $stockContentPlain;
            $completeOrder->execute([
                (int) ($stock['id'] ?? 0),
                $stockContentStored,
                $orderId,
            ]);

            $activityStmt = $this->db->prepare("
                INSERT INTO `lich_su_hoat_dong` (`username`, `hoatdong`, `gia`, `time`)
                VALUES (?, ?, ?, ?)
            ");
            $activityStmt->execute([
                $username,
                'Mua san pham: ' . (string) ($product['name'] ?? ('#' . $productId)),
                -$price,
                (string) time(),
            ]);

            $this->db->commit();

            Logger::info('Billing', 'product_purchase_success', "Mua san pham thanh cong: {$username}", [
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'user_id' => $userId,
                'product_id' => $productId,
                'price' => $price,
                'stock_id' => (int) ($stock['id'] ?? 0),
            ]);

            return [
                'success' => true,
                'message' => 'Thanh toan thanh cong!',
                'order' => [
                    'id' => $orderId,
                    'order_code' => $orderCode,
                    'product_name' => (string) ($product['name'] ?? ''),
                    'price' => $price,
                    'content' => $stockContentPlain,
                    'stock_id' => (int) ($stock['id'] ?? 0),
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

            return ['success' => false, 'message' => 'Khong the xu ly don hang luc nay. Vui long thu lai sau.'];
        }
    }
}

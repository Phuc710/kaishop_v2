<?php

class ProductInventoryService
{
    private PDO $db;
    private ProductStock $stockModel;

    public function __construct(?ProductStock $stockModel = null)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->stockModel = $stockModel ?: new ProductStock();
    }

    public function usesUnlimitedStock(array $product): bool
    {
        return !Product::isStockManagedProduct($product);
    }

    public function usesWarehouseStock(array $product): bool
    {
        return Product::isStockManagedProduct($product);
    }

    public function getAvailableStock(array $product): ?int
    {
        $deliveryMode = Product::resolveDeliveryMode($product);

        if ($deliveryMode === 'source_link') {
            return null; // Unlimited
        }

        if ($deliveryMode === 'manual_info') {
            return (int) ($product['manual_stock'] ?? 0);
        }

        if ($deliveryMode === 'account_stock') {
            return $this->stockModel->countAvailable((int) ($product['id'] ?? 0));
        }

        return 0;

        return 0;
    }

    public function getDynamicMaxQty(array $product, int $configuredMax): int
    {
        $configuredMax = max(0, $configuredMax);
        $available = $this->getAvailableStock($product);

        if ($available === null) {
            return $configuredMax;
        }

        return $configuredMax > 0 ? min($configuredMax, $available) : $available;
    }

    public function getStats(array $product): array
    {
        $stats = $this->getStatsForProducts([$product]);
        return $stats[(int) ($product['id'] ?? 0)] ?? [
            'available' => 0,
            'sold' => 0,
            'unlimited' => $this->usesUnlimitedStock($product),
        ];
    }

    public function getStatsForProducts(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $productsById = [];
        $accountProductIds = [];
        foreach ($products as $product) {
            $productId = (int) ($product['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $productsById[$productId] = $product;
            if ($this->usesWarehouseStock($product)) {
                $accountProductIds[] = $productId;
            }
        }

        if (empty($productsById)) {
            return [];
        }

        $accountStats = empty($accountProductIds)
            ? []
            : $this->stockModel->getStatsForProducts($accountProductIds);
        $soldCounts = $this->getSoldCounts(array_keys($productsById));

        $result = [];
        foreach ($productsById as $productId => $product) {
            if ($this->usesUnlimitedStock($product)) {
                $result[$productId] = [
                    'available' => 0,
                    'sold' => (int) ($soldCounts[$productId] ?? 0),
                    'unlimited' => true,
                ];
                continue;
            }

            $deliveryMode = Product::resolveDeliveryMode($product);

            if ($deliveryMode === 'source_link') {
                $result[$productId] = [
                    'available' => 0,
                    'sold' => $ordersSold[$productId] ?? 0,
                    'unlimited' => true,
                    'is_manual_queue' => false
                ];
                continue;
            }

            if ($deliveryMode === 'manual_info') {
                $pCounts = $this->getPendingAndCompletedCounts($productId);
                $result[$productId] = [
                    'available' => (int) ($product['manual_stock'] ?? 0),
                    'sold' => (int) ($pCounts['completed'] ?? 0),
                    'pending' => (int) ($pCounts['pending'] ?? 0),
                    'unlimited' => false,
                    'is_manual_queue' => true
                ];
                continue;
            }

            if ($deliveryMode === 'account_stock') {
                $stats = $accountStats[$productId] ?? ['available' => 0, 'sold' => 0];
                $result[$productId] = [
                    'available' => (int) ($stats['available'] ?? 0),
                    'sold' => (int) ($stats['sold'] ?? 0),
                    'unlimited' => false,
                    'is_manual_queue' => false
                ];
                continue;
            }
        }

        return $result;
    }

    public function allocateInCurrentTransaction(array $product, int $quantity, int $buyerId, int $orderId): ?array
    {
        $quantity = max(1, $quantity);

        if ($this->usesUnlimitedStock($product)) {
            return [
                'stock_id' => null,
                'delivery_content' => '',
            ];
        }

        if ($this->usesWarehouseStock($product)) {
            $firstStockId = null;
            $items = [];
            for ($i = 0; $i < $quantity; $i++) {
                $stock = $this->stockModel->claimOneInCurrentTransaction((int) ($product['id'] ?? 0), $buyerId, $orderId);
                if (!$stock) {
                    return null;
                }

                if ($firstStockId === null) {
                    $firstStockId = (int) ($stock['id'] ?? 0);
                }
                $items[] = (string) ($stock['content'] ?? '');
            }

            return [
                'stock_id' => $firstStockId,
                'delivery_content' => implode(PHP_EOL, $items),
            ];
        }

        return null;
    }

    private function getSoldCounts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare("
            SELECT `product_id`, COALESCE(SUM(`quantity`), 0) AS sold_qty
            FROM `orders`
            WHERE `product_id` IN ($placeholders) AND `status` = 'completed'
            GROUP BY `product_id`
        ");
        $stmt->execute($productIds);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) ($row['product_id'] ?? 0)] = (int) ($row['sold_qty'] ?? 0);
        }
        return $result;
    }

    private function countPendingOrders(int $productId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `orders` WHERE `product_id` = ? AND `status` = 'pending'");
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn();
    }

    private function getPendingAndCompletedCounts(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed
            FROM `orders` 
            WHERE `product_id` = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['pending' => 0, 'completed' => 0];
    }
}

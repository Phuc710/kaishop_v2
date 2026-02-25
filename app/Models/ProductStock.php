<?php

/**
 * ProductStock Model
 * Manages stock items (accounts) for products of type 'account'
 */
class ProductStock extends Model
{
    protected $table = 'product_stock';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    /**
     * Ensure the product_stock table exists
     */
    private function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `product_stock` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `content` text NOT NULL,
            `status` enum('available','sold') NOT NULL DEFAULT 'available',
            `order_id` int(11) DEFAULT NULL,
            `buyer_id` int(11) DEFAULT NULL,
            `note` varchar(255) DEFAULT NULL,
            `sold_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_stock_product` (`product_id`),
            KEY `idx_stock_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    /**
     * Get all stock items for a product
     */
    public function getByProduct(int $productId, string $statusFilter = '', string $search = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
        $params = [$productId];
        if ($statusFilter !== '') {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }
        if ($search !== '') {
            $sql .= " AND content LIKE ?";
            $params[] = '%' . $search . '%';
        }
        $sql .= " ORDER BY id DESC";
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Import bulk items (one per line)
     * Returns [added, skipped_duplicates]
     */
    public function importBulk(int $productId, string $rawText): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $rawText))));
        $added = 0;
        $skipped = 0;

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->table} (product_id, content) VALUES (?, ?)"
        );

        foreach ($lines as $line) {
            if ($line === '')
                continue;
            $stmt->execute([$productId, $line]);
            if ($stmt->rowCount() > 0) {
                $added++;
            } else {
                $skipped++;
            }
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * Claim one available stock item for purchase (atomic)
     * Returns the stock row or null if out of stock
     */
    public function claimOne(int $productId, int $buyerId, ?int $orderId = null): ?array
    {
        // Atomic: select + update in one step to avoid race condition
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM {$this->table} WHERE product_id = ? AND status = 'available' ORDER BY id ASC LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                return null;
            }

            $now = date('Y-m-d H:i:s');
            $update = $this->db->prepare(
                "UPDATE {$this->table} SET status='sold', buyer_id=?, order_id=?, sold_at=? WHERE id=?"
            );
            $update->execute([$buyerId, $orderId, $now, $row['id']]);

            $this->db->commit();

            // Return full row
            $stmt2 = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
            $stmt2->execute([$row['id']]);
            return $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return null;
        }
    }

    /**
     * Get stats for a product
     */
    public function getStats(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(status='available') as available,
                SUM(status='sold') as sold
             FROM {$this->table} WHERE product_id = ?"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int) ($row['total'] ?? 0),
            'available' => (int) ($row['available'] ?? 0),
            'sold' => (int) ($row['sold'] ?? 0),
        ];
    }

    /**
     * Get stats for multiple products at once (for list view)
     * Returns [product_id => ['available'=>X, 'sold'=>Y]]
     */
    public function getStatsForProducts(array $productIds): array
    {
        if (empty($productIds))
            return [];

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT product_id,
                SUM(status='available') as available,
                SUM(status='sold') as sold
             FROM {$this->table}
             WHERE product_id IN ($placeholders)
             GROUP BY product_id"
        );
        $stmt->execute($productIds);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['product_id']] = [
                'available' => (int) $row['available'],
                'sold' => (int) $row['sold'],
            ];
        }
        return $result;
    }

    /**
     * Delete a stock item (only if available)
     */
    public function deleteAvailable(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id=? AND status='available'"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete ALL available stock for a product
     */
    public function deleteAllAvailable(int $productId): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE product_id=? AND status='available'"
        );
        $stmt->execute([$productId]);
        return $stmt->rowCount();
    }

    /**
     * Update stock item content (only if available)
     */
    public function updateContent(int $id, string $content): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET content=? WHERE id=? AND status='available'"
        );
        $stmt->execute([$content, $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count available for a product
     */
    public function countAvailable(int $productId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE product_id=? AND status='available'"
        );
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn();
    }
}

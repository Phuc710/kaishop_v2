<?php

/**
 * ProductStock Model
 * Manages warehouse stock items for every stock-managed product.
 */
class ProductStock extends Model
{
    protected $table = 'product_stock';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all stock items for a product with filtering
     */
    public function getByProduct(int $productId, array $filters = []): array
    {
        $status = $filters['status_filter'] ?? '';
        $search = $filters['search'] ?? '';
        $dateFilter = $filters['date_filter'] ?? '';
        $limit = (int) ($filters['limit'] ?? 20);

        $sql = "SELECT t.*, u.username as buyer_username 
                FROM {$this->table} t 
                LEFT JOIN users u ON t.buyer_id = u.id 
                WHERE t.product_id = ?";
        $params = [$productId];

        if ($status !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (t.content LIKE ? OR u.username LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($dateFilter !== '' && $dateFilter !== 'all') {
            $days = (int) $dateFilter;
            if ($days > 0) {
                $sql .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $params[] = $days;
            }
        }

        $sql .= " ORDER BY t.id DESC LIMIT " . (int) $limit;

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
     * Update stock item content.
     * Allow both available and sold items because admins may need to adjust
     * delivered credentials for warranty/support cases.
     */
    public function updateContent(int $id, string $content): bool
    {
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }

        if ((string) ($existing['content'] ?? '') === $content) {
            return true;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET content=? WHERE id=?"
        );
        $stmt->execute([$content, $id]);
        return $stmt->rowCount() > 0;
    }

    public function releaseByOrderId(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='available', buyer_id=NULL, order_id=NULL, sold_at=NULL
             WHERE order_id=? AND status='sold'"
        );
        $stmt->execute([$orderId]);
        return $stmt->rowCount();
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

    /**
     * Claim one available stock item using the current DB transaction.
     * PurchaseService opens/commits the transaction, so this method must not
     * start/commit/rollback by itself.
     */
    public function claimOneInCurrentTransaction(int $productId, int $buyerId, ?int $orderId = null): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE product_id = ? AND status = 'available'
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $update = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'sold', buyer_id = ?, order_id = ?, sold_at = ?
             WHERE id = ? AND status = 'available'"
        );
        $update->execute([$buyerId, $orderId, $now, (int) $row['id']]);

        if ($update->rowCount() < 1) {
            return null;
        }

        $row['status'] = 'sold';
        $row['buyer_id'] = $buyerId;
        $row['order_id'] = $orderId;
        $row['sold_at'] = $now;
        return $row;
    }
}


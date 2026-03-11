<?php

/**
 * ProductStock Model
 * Manages warehouse stock items for every stock-managed product.
 */
class ProductStock extends Model
{
    protected $table = 'product_stock';

    protected ?TimeService $timeService = null;
    private ?CryptoService $crypto = null;

    public function __construct()
    {
        parent::__construct();
        if (class_exists('TimeService')) {
            $this->timeService = TimeService::instance();
        }
        if (class_exists('CryptoService')) {
            $this->crypto = new CryptoService();
        }
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

        $sql = "SELECT t.*, u.username as buyer_username, o.order_code 
                FROM {$this->table} t 
                LEFT JOIN users u ON t.buyer_id = u.id 
                LEFT JOIN orders o ON t.order_id = o.id
                WHERE t.product_id = ?";
        $params = [$productId];

        if ($status !== '') {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (
                t.content LIKE ?
                OR u.username LIKE ?
                OR o.order_code LIKE ?
                OR UPPER(SUBSTRING(SHA2(o.order_code, 256), 1, 8)) LIKE ?
            )";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . strtoupper($search) . '%';
        }

        if ($dateFilter !== '' && $dateFilter !== 'all') {
            $days = (int) $dateFilter;
            if ($days > 0) {
                $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
                $sql .= " AND t.created_at >= DATE_SUB(?, INTERVAL ? DAY)";
                $params[] = $nowSql;
                $params[] = $days;
            }
        }

        $startDate = $filters['start_date'] ?? '';
        $endDate = $filters['end_date'] ?? '';
        if ($startDate !== '' && $endDate !== '') {
            $sql .= " AND t.created_at BETWEEN ? AND ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
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
                "SELECT id
                 FROM {$this->table}
                 WHERE product_id = ? AND status = 'available'
                 ORDER BY
                    CASE WHEN created_at IS NULL THEN 1 ELSE 0 END ASC,
                    created_at ASC,
                    id ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                return null;
            }

            $now = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
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
            $this->syncOrderContentForSoldItem($existing, $content);
            return true;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET content=? WHERE id=?"
        );
        $stmt->execute([$content, $id]);
        $updated = $stmt->rowCount() > 0;
        $this->syncOrderContentForSoldItem($existing, $content);
        return $updated;
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
             ORDER BY
                CASE WHEN created_at IS NULL THEN 1 ELSE 0 END ASC,
                created_at ASC,
                id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            return null;
        }

        $now = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
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

    private function syncOrderContentForSoldItem(array $stockRow, string $content): void
    {
        $status = (string) ($stockRow['status'] ?? '');
        $orderId = (int) ($stockRow['order_id'] ?? 0);
        if ($status !== 'sold' || $orderId <= 0) {
            return;
        }

        $storedContent = ($this->crypto instanceof CryptoService && $this->crypto->isEnabled())
            ? $this->crypto->encryptString($content)
            : $content;

        try {
            $stmt = $this->db->prepare(
                "UPDATE `orders`
                 SET `stock_content` = ?
                 WHERE `id` = ?
                 LIMIT 1"
            );
            $stmt->execute([$storedContent, $orderId]);
        } catch (Throwable $e) {
            // Keep stock update non-blocking if order sync fails.
        }

        // Notify buyer via Telegram if they have a linked account
        $this->notifyTelegramUserOfContentUpdate($orderId, $content);
    }

    private function notifyTelegramUserOfContentUpdate(int $orderId, string $newContent): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT o.order_code, o.product_name, l.telegram_id
                FROM `orders` o
                LEFT JOIN `user_telegram_links` l ON l.user_id = o.user_id
                WHERE o.id = ?
                LIMIT 1
            ");
            $stmt->execute([$orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$row || empty($row['telegram_id'])) {
                return;
            }

            $orderCode = strtoupper(substr(hash('sha256', (string) ($row['order_code'] ?? '')), 0, 8));
            $productName = htmlspecialchars((string) ($row['product_name'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
            $contentEsc = htmlspecialchars($newContent, ENT_QUOTES, 'UTF-8');

            if (!class_exists('TelegramService')) {
                return;
            }

            $msg = "🔄 <b>CẬP NHẬT NỘI DUNG ĐƠN HÀNG</b>\n\n"
                 . "📦 Sản phẩm: <b>{$productName}</b>\n"
                 . "🔖 Mã đơn: <code>{$orderCode}</code>\n\n"
                 . "🔑 Nội dung mới:\n<code>{$contentEsc}</code>";

            (new TelegramService())->sendTo((string) $row['telegram_id'], $msg);
        } catch (Throwable $e) {
            // Non-blocking — Telegram notification failure must not affect stock update.
        }
    }
}


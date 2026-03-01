<?php

/**
 * TelegramOutbox Model
 * Handles asynchronous notification delivery
 */
class TelegramOutbox extends Model
{
    protected $table = 'telegram_outbox';

    public function enqueue(int $telegramId, string $message, string $parseMode = 'HTML'): bool
    {
        return $this->create([
            'telegram_id' => $telegramId,
            'message' => $message,
            'parse_mode' => $parseMode,
            'status' => 'pending',
            'try_count' => 0
        ]) > 0;
    }

    /**
     * Alias for enqueue() — shorter name used in TelegramBotService::cmdBroadcast()
     */
    public function push(int $telegramId, string $message, string $parseMode = 'HTML'): bool
    {
        return $this->enqueue($telegramId, $message, $parseMode);
    }

    public function fetchPending(int $limit = 20): array
    {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `status` = 'pending' AND `try_count` < 3 
                ORDER BY `created_at` ASC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markSent(int $id): void
    {
        $this->update($id, [
            'status' => 'sent',
            'sent_at' => TimeService::instance()->nowSql()

        ]);
    }

    public function markFailed(int $id, string $error): void
    {
        $sql = "UPDATE `{$this->table}` 
                SET `try_count` = `try_count` + 1, 
                    `last_error` = ?, 
                    `status` = IF(`try_count` + 1 >= 3, 'fail', 'pending') 
                WHERE `id` = ?";
        $this->db->prepare($sql)->execute([$error, $id]);
    }

    public function cleanOldSent(int $days = 7): int
    {
        $ts = TimeService::instance()->nowTs() - ($days * 86400);
        $threshold = TimeService::instance()->formatDb($ts);

        $sql = "DELETE FROM `{$this->table}` WHERE `status` = 'sent' AND `sent_at` < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threshold]);

        return $stmt->rowCount();
    }

    /**
     * Fetch recent messages for dashboard
     */
    public function fetchRecent(int $limit = 10): array
    {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `id` DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregate stats for dashboard
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN `status` = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN `status` = 'fail' THEN 1 ELSE 0 END) as failed
                FROM `{$this->table}`";
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }

    /**
     * Fetch messages filtered by status for admin panel
     */
    public function fetchByStatus(string $status, int $limit = 50): array
    {
        if ($status === 'all') {
            $sql = "SELECT * FROM `{$this->table}` ORDER BY `id` DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            $sql = "SELECT * FROM `{$this->table}` WHERE `status` = ? ORDER BY `id` DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reset all 'fail' messages back to 'pending' for retry
     */
    public function bulkResetFail(): int
    {
        $sql = "UPDATE `{$this->table}` SET `status`='pending', `try_count`=0 WHERE `status`='fail'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Delete messages by IDs
     */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids))
            return 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `id` IN ({$placeholders})");
        $stmt->execute(array_map('intval', $ids));
        return $stmt->rowCount();
    }
}


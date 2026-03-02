<?php

/**
 * TelegramLog Model
 * Records all Bot activity for the terminal log viewer.
 */
class TelegramLog extends Model
{
    protected $table = 'telegram_logs';

    /**
     * Write a log entry.
     *
     * @param string      $message  Human-readable description.
     * @param string      $level    INFO | WARN | ERROR
     * @param string      $type     INCOMING | OUTGOING
     * @param string      $category GENERAL | AUTH | PURCHASE | DEPOSIT | CALLBACK | API | etc.
     * @param mixed       $data     Optional structured payload (array or string).
     */
    public function log(
        string $message,
        string $level = 'INFO',
        string $type = 'INCOMING',
        string $category = 'GENERAL',
        $data = null
    ): bool {
        return $this->create([
            'level' => $level,
            'type' => $type,
            'category' => $category,
            'message' => $message,
            'data' => is_array($data) || is_object($data)
                ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $data,
        ]) > 0;
    }

    /**
     * Fetch the most recent N entries (oldest-first for terminal display).
     */
    public function fetchRecent(int $limit = 150): array
    {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `id` DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Fetch entries newer than a given ID (for real-time AJAX polling).
     */
    public function fetchAfter(int $afterId, int $limit = 200): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `id` > ? ORDER BY `id` ASC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$afterId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the maximum `id` currently in the table (used by frontend to track position).
     */
    public function maxId(): int
    {
        return (int) $this->db->query("SELECT COALESCE(MAX(`id`), 0) FROM `{$this->table}`")->fetchColumn();
    }

    /**
     * Purge entries older than $days days (housekeeping).
     */
    public function purgeOld(int $days = 3): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `created_at` < ?");
        $stmt->execute([$threshold]);
        return $stmt->rowCount();
    }
}

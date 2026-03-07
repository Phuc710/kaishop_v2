<?php

/**
 * UserTelegramLink Model
 * Handles mapping between Web User ID and Telegram Chat ID
 */
class UserTelegramLink extends Model
{
    protected $table = 'user_telegram_links';
    private ?bool $hasBinanceUidColumn = null;

    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = ? LIMIT 1";
        return $this->query($sql, [$userId])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `telegram_id` = ? LIMIT 1";
        return $this->query($sql, [$telegramId])->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function linkUser(int $userId, int $telegramId, ?string $username = null, ?string $firstName = null): bool
    {
        // Check if telegram_id is already linked to another user
        $existing = $this->findByTelegramId($telegramId);
        if ($existing && (int) $existing['user_id'] !== $userId) {
            // Unlink the old user first if we want one telegram per user
            $this->unlinkByTelegramId($telegramId);
        }

        // Check if user is already linked to another telegram
        $existingUser = $this->findByUserId($userId);
        if ($existingUser) {
            return $this->update($existingUser['id'], [
                'telegram_id' => $telegramId,
                'telegram_username' => $username,
                'first_name' => $firstName,
                'linked_at' => TimeService::instance()->nowSql()

            ]);
        }

        return $this->create([
            'user_id' => $userId,
            'telegram_id' => $telegramId,
            'telegram_username' => $username,
            'first_name' => $firstName,
            'linked_at' => TimeService::instance()->nowSql()
        ]) > 0;
    }

    public function unlinkUser(int $userId): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `user_id` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    public function unlinkByTelegramId(int $telegramId): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `telegram_id` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$telegramId]);
    }
    public function updateLastActive(int $telegramId, ?string $username = null, ?string $firstName = null): bool
    {
        $existing = $this->findByTelegramId($telegramId);
        if (!$existing)
            return false;

        $now = TimeService::instance()->nowSql();
        $sql = "UPDATE `{$this->table}` SET 
                `telegram_username` = ?, 
                `first_name` = ?, 
                `last_active` = ? 
                WHERE `id` = ?";
        return $this->db->prepare($sql)->execute([$username, $firstName, $now, $existing['id']]);
    }

    public function saveBinanceUidByUserId(int $userId, string $binanceUid): bool
    {
        if (!$this->hasBinanceUidColumn()) {
            return false;
        }
        $uid = trim($binanceUid);
        if ($uid === '' || !preg_match('/^\d{4,20}$/', $uid)) {
            return false;
        }

        $row = $this->findByUserId($userId);
        if (!$row) {
            return false;
        }

        return $this->update((int) $row['id'], [
            'binance_uid' => $uid,
            'last_active' => TimeService::instance()->nowSql(),
        ]);
    }

    public function getBinanceUidByUserId(int $userId): string
    {
        if (!$this->hasBinanceUidColumn()) {
            return '';
        }
        $row = $this->findByUserId($userId);
        return trim((string) ($row['binance_uid'] ?? ''));
    }

    private function hasBinanceUidColumn(): bool
    {
        if ($this->hasBinanceUidColumn !== null) {
            return $this->hasBinanceUidColumn;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'binance_uid'
        ");
        $stmt->execute([$this->table]);
        $this->hasBinanceUidColumn = (int) $stmt->fetchColumn() > 0;
        return $this->hasBinanceUidColumn;
    }

    public function getAdoptionTrend(int $days = 7): array
    {
        $sql = "SELECT DATE(linked_at) as date, COUNT(*) as count 
                FROM `{$this->table}` 
                WHERE `linked_at` >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(linked_at)
                ORDER BY date ASC";
        return $this->query($sql, [$days - 1])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount(): int
    {
        return (int) $this->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
    }
}


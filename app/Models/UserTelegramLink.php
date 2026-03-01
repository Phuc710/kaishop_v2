<?php

/**
 * UserTelegramLink Model
 * Handles mapping between Web User ID and Telegram Chat ID
 */
class UserTelegramLink extends Model
{
    protected $table = 'user_telegram_links';

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
            'first_name' => $firstName
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
}


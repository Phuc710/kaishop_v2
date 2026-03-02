<?php

/**
 * TelegramUser Model
 * Tracks all unique Telegram IDs that hit the bot
 */
class TelegramUser extends Model
{
    protected $table = 'telegram_users';

    /**
     * Upsert a user based on Telegram ID
     */
    public function upsert(int $telegramId, ?string $username = null, ?string $firstName = null): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("
            INSERT INTO `{$this->table}` (`telegram_id`, `username`, `first_name`, `last_seen_at`)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                `username` = VALUES(`username`),
                `first_name` = VALUES(`first_name`),
                `last_seen_at` = NOW()
        ");
        return $stmt->execute([$telegramId, $username, $firstName]);
    }

    /**
     * Get all active (non-blocked) users for broadcasting
     */
    public function getAllActive(): array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT `telegram_id` FROM `{$this->table}` WHERE `is_blocked` = 0");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

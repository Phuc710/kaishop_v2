<?php

/**
 * TelegramNotificationChannel Model
 * Tracks extra Chat IDs/Channels for order notifications
 */
class TelegramNotificationChannel extends Model
{
    protected $table = 'telegram_notification_channels';

    /**
     * Get all active channels
     */
    public function getActive(): array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `{$this->table}` WHERE `is_active` = 1 ORDER BY `id` ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all channels for admin management
     */
    public function fetchAll(): array
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `{$this->table}` ORDER BY `id` ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggle status
     */
    public function toggle(int $id): bool
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("UPDATE `{$this->table}` SET `is_active` = 1 - `is_active` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Add channel
     */
    public function add(string $chatId, ?string $label = null): bool
    {
        return $this->create([
            'chat_id' => $chatId,
            'label' => $label,
            'is_active' => 1
        ]);
    }
}

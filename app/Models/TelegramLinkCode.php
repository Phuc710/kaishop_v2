<?php

/**
 * TelegramLinkCode Model
 * Handles temporary OTP codes for account linking
 */
class TelegramLinkCode extends Model
{
    protected $table = 'telegram_link_codes';

    private function nowDbSql(): string
    {
        $time = TimeService::instance();
        return $time->nowSql($time->getDbTimezone());
    }

    public function createCode(int $userId): string
    {
        // Clean up old codes for this user
        $this->db->prepare("DELETE FROM `{$this->table}` WHERE `user_id` = ?")->execute([$userId]);

        $code = (string) random_int(100000, 999999);
        $ts = TimeService::instance()->nowTs() + (5 * 60);
        $expiresAt = TimeService::instance()->formatDb($ts);


        $this->create([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => $expiresAt
        ]);

        return $code;
    }

    public function verifyCode(string $code): ?int
    {
        $now = $this->nowDbSql();

        $sql = "SELECT `user_id` FROM `{$this->table}` 
                WHERE `code` = ? AND `expires_at` > ? AND `used_at` IS NULL 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code, $now]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        $userId = (int) $row['user_id'];

        // Mark as used
        $upd = $this->db->prepare("UPDATE `{$this->table}` SET `used_at` = ? WHERE `code` = ?");
        $upd->execute([$now, $code]);


        return $userId;
    }

    public function getActiveCode(int $userId): ?array
    {
        $now = $this->nowDbSql();

        $sql = "SELECT `code`, `expires_at` FROM `{$this->table}` 
                WHERE `user_id` = ? AND `expires_at` > ? AND `used_at` IS NULL 
                ORDER BY `expires_at` DESC, `id` DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $now]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $time = TimeService::instance();
        $expiresAtTs = $time->toTimestamp((string) ($row['expires_at'] ?? ''), $time->getDbTimezone());
        $row['expires_at_ts'] = $expiresAtTs ?? null;

        return $row;
    }

    public function cleanExpired(): int
    {
        $now = $this->nowDbSql();

        $sql = "DELETE FROM `{$this->table}` WHERE `expires_at` < ? OR `used_at` IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$now]);
        return $stmt->rowCount();
    }

}

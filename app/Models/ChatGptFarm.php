<?php

/**
 * ChatGptFarm Model
 * Manages farm records: CRUD + slot tracking + farm selection logic
 */
class ChatGptFarm extends Model
{
    protected $table = 'chatgpt_farms';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all farms with optional status filter
     */
    public function getAll($status = '')
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE `status` = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY `id` ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get farms that should be monitored by the guard.
     */
    public function getGuardableFarms()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE `status` IN ('active', 'full')
             ORDER BY `id` ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get a single farm by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all active farms that still have available seats
     */
    public function getActiveFarms()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE `status` = 'active' AND `seat_used` < `seat_total`
             ORDER BY `seat_used` ASC, `id` ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get best available farm (least used first, still active)
     */
    public function getBestAvailableFarm()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE `status` = 'active' AND `seat_used` < `seat_total`
             ORDER BY `seat_used` ASC, `id` ASC
             LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new farm
     */
    public function create($data)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
             (`farm_name`, `admin_email`, `admin_api_key`, `seat_total`, `seat_used`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, 0, 'active', ?, ?)"
        );
        $stmt->execute([
            $data['farm_name'],
            $data['admin_email'],
            $data['admin_api_key'],
            (int) ($data['seat_total'] ?? 4),
            $nowSql,
            $nowSql,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a farm
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        $allowed = ['farm_name', 'admin_email', 'admin_api_key', 'seat_total', 'status'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "`{$f}` = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) {
            return false;
        }
        $fields[] = '`updated_at` = ?';
        $params[] = $this->nowSql();
        $params[] = $id;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE `id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Increment seat_used (when an invite is sent)
     */
    public function incrementSeatUsed($farmId)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET `seat_used` = `seat_used` + 1,
                 `status` = IF(`seat_used` + 1 >= `seat_total`, 'full', 'active'),
                 `updated_at` = ?
             WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$nowSql, $farmId]);
    }

    /**
     * Decrement seat_used (when a member is removed or invite revoked)
     */
    public function decrementSeatUsed($farmId)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET `seat_used` = GREATEST(0, `seat_used` - 1),
                 `status` = IF(`status` = 'full', 'active', `status`),
                 `updated_at` = ?
             WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$nowSql, $farmId]);
    }

    /**
     * Update last_sync_at timestamp
     */
    public function touchSyncAt($farmId)
    {
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET `last_sync_at` = ?, `updated_at` = ? WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$nowSql, $nowSql, $farmId]);
    }

    /**
     * Sync seat_used to the truth returned by OpenAI and refresh farm status.
     */
    public function syncSeatUsageFromLiveData($farmId, $seatTotal, $liveMembers, $liveInvites, $adminEmail)
    {
        $seatTotal = max(1, (int) $seatTotal);
        $adminEmail = strtolower(trim((string) $adminEmail));

        $nonAdminCount = 0;
        foreach ((array) $liveMembers as $member) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));
            $role = trim((string) ($member['role'] ?? ''));
            if ($email === '' || $email === $adminEmail || $role === 'owner') {
                continue;
            }
            $nonAdminCount++;
        }

        $pendingInviteCount = 0;
        foreach ((array) $liveInvites as $invite) {
            if (trim((string) ($invite['status'] ?? '')) === 'pending') {
                $pendingInviteCount++;
            }
        }

        $totalUsed = $nonAdminCount + $pendingInviteCount;
        $status = $totalUsed >= $seatTotal ? 'full' : 'active';
        $nowSql = $this->nowSql();

        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET `seat_used` = ?,
                 `status` = IF(`status` = 'locked', 'locked', ?),
                 `last_sync_at` = ?,
                 `updated_at` = ?
             WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$totalUsed, $status, $nowSql, $nowSql, $farmId]);

        return $totalUsed;
    }

    /**
     * Get aggregate stock: total available seats across all active farms
     */
    public function getTotalAvailableSeats()
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(`seat_total` - `seat_used`), 0) as available
             FROM `{$this->table}` WHERE `status` IN ('active', 'full')"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get summary stats for admin dashboard
     */
    public function getStats()
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) as total_farms,
                SUM(`seat_total`) as total_seats,
                SUM(`seat_used`) as used_seats,
                SUM(`seat_total` - `seat_used`) as available_seats,
                SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) as active_farms,
                SUM(CASE WHEN `status` = 'full' THEN 1 ELSE 0 END) as full_farms,
                SUM(CASE WHEN `status` = 'locked' THEN 1 ELSE 0 END) as locked_farms
             FROM `{$this->table}`"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lock/unlock a farm
     */
    public function setStatus($farmId, $status)
    {
        $allowed = ['active', 'full', 'locked'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $nowSql = $this->nowSql();
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET `status` = ?, `updated_at` = ? WHERE `id` = ? LIMIT 1"
        );
        $stmt->execute([$status, $nowSql, $farmId]);
        return $stmt->rowCount() > 0;
    }

    private function nowSql(): string
    {
        if ($this->timeService) {
            return $this->timeService->nowSql($this->timeService->getDbTimezone());
        }

        return date('Y-m-d H:i:s');
    }
}

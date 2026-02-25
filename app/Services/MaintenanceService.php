<?php

class MaintenanceService
{
    private $connection;
    private static $schemaEnsured = false;

    public function __construct($connection = null)
    {
        if ($connection instanceof mysqli) {
            $this->connection = $connection;
        } else {
            global $connection;
            $this->connection = $connection instanceof mysqli ? $connection : null;
        }
    }

    public function ensureSchema()
    {
        if (self::$schemaEnsured || !$this->connection) {
            self::$schemaEnsured = true;
            return;
        }

        $columns = [
            'maintenance_enabled' => "ALTER TABLE `setting` ADD COLUMN `maintenance_enabled` TINYINT(1) NOT NULL DEFAULT 0",
            'maintenance_start_at' => "ALTER TABLE `setting` ADD COLUMN `maintenance_start_at` DATETIME NULL DEFAULT NULL",
            'maintenance_duration_minutes' => "ALTER TABLE `setting` ADD COLUMN `maintenance_duration_minutes` INT NOT NULL DEFAULT 60",
            'maintenance_notice_minutes' => "ALTER TABLE `setting` ADD COLUMN `maintenance_notice_minutes` INT NOT NULL DEFAULT 5",
            'maintenance_message' => "ALTER TABLE `setting` ADD COLUMN `maintenance_message` TEXT NULL DEFAULT NULL",
        ];

        foreach ($columns as $name => $sql) {
            if (!$this->columnExists($name)) {
                @$this->connection->query($sql);
            }
        }

        self::$schemaEnsured = true;
    }

    public function getConfig()
    {
        $this->ensureSchema();

        if (!$this->connection) {
            return $this->defaultConfig();
        }

        $row = $this->connection->query("SELECT * FROM `setting` ORDER BY `id` ASC LIMIT 1");
        $data = $row ? $row->fetch_assoc() : null;

        return $this->normalizeConfig(is_array($data) ? $data : []);
    }

    public function saveConfig(array $input)
    {
        $this->ensureSchema();
        if (!$this->connection) {
            return ['success' => false, 'message' => 'Không kết nối được cơ sở dữ liệu'];
        }

        $enabled = !empty($input['maintenance_enabled']) ? 1 : 0;
        $duration = (int) ($input['maintenance_duration_minutes'] ?? 60);
        $notice = (int) ($input['maintenance_notice_minutes'] ?? 5);
        $message = trim((string) ($input['maintenance_message'] ?? ''));
        $startAt = $this->normalizeDateTime($input['maintenance_start_at'] ?? null);

        if (($input['maintenance_start_at'] ?? '') !== '' && $startAt === null) {
            return ['success' => false, 'message' => 'Thời gian bắt đầu không hợp lệ'];
        }

        if ($duration < 1) {
            $duration = 1;
        } elseif ($duration > 10080) {
            $duration = 10080;
        }

        if ($notice < 1) {
            $notice = 1;
        } elseif ($notice > 60) {
            $notice = 60;
        }

        $safeMessage = $this->connection->real_escape_string($message);
        $safeStart = $startAt !== null ? "'" . $this->connection->real_escape_string($startAt) . "'" : "NULL";

        $sql = "UPDATE `setting` SET 
            `maintenance_enabled` = {$enabled},
            `maintenance_start_at` = {$safeStart},
            `maintenance_duration_minutes` = {$duration},
            `maintenance_notice_minutes` = {$notice},
            `maintenance_message` = '{$safeMessage}'
            ORDER BY `id` ASC LIMIT 1";

        $ok = $this->connection->query($sql);
        if (!$ok) {
            return ['success' => false, 'message' => 'Không thể lưu cấu hình bảo trì'];
        }

        return ['success' => true, 'message' => 'Đã lưu cấu hình bảo trì'];
    }

    public function clearNow()
    {
        $this->ensureSchema();
        if (!$this->connection) {
            return ['success' => false, 'message' => 'Không kết nối được cơ sở dữ liệu'];
        }

        $sql = "UPDATE `setting` SET
            `maintenance_enabled` = 0,
            `maintenance_start_at` = NULL
            ORDER BY `id` ASC LIMIT 1";
        $ok = $this->connection->query($sql);

        if (!$ok) {
            return ['success' => false, 'message' => 'Không thể kết thúc bảo trì'];
        }

        return ['success' => true, 'message' => 'Đã kết thúc bảo trì và xóa lịch'];
    }

    public function getState($refresh = false)
    {
        $config = $this->getConfig();
        $now = time();
        $startTs = $config['start_at'] ? strtotime($config['start_at']) : null;
        $durationSeconds = $config['duration_minutes'] * 60;
        $noticeSeconds = $config['notice_minutes'] * 60;

        $manualMode = (bool) $config['enabled'];
        $hasSchedule = $startTs !== null;
        $active = false;
        $scheduledAutoMode = !$manualMode && $hasSchedule;

        if ($manualMode) {
            $active = !$hasSchedule || $now >= $startTs;
        } elseif ($hasSchedule) {
            $active = ($now >= $startTs) && ($now < ($startTs + $durationSeconds));
        }

        $secondsUntilStart = null;
        if ($hasSchedule && $now < $startTs) {
            $secondsUntilStart = $startTs - $now;
        }

        $noticeActive = $hasSchedule && !$active && $secondsUntilStart !== null && $secondsUntilStart <= $noticeSeconds;
        $noticeLeft = $noticeActive ? $secondsUntilStart : 0;

        $endTs = $hasSchedule ? ($startTs + $durationSeconds) : null;
        $secondsUntilEnd = null;
        if ($endTs !== null && $active) {
            $secondsUntilEnd = max(0, $endTs - $now);
        }

        $manualOverdue = $active && $manualMode && $endTs !== null && $now >= $endTs;
        $showEndCountdown = $active && $endTs !== null && $now < $endTs;

        $statusKey = 'idle';
        $statusText = 'Không bảo trì';
        if ($manualOverdue) {
            $statusKey = 'active_manual_overdue';
            $statusText = 'Đang bảo trì (thủ công, đã quá thời lượng dự kiến)';
        } elseif ($active && $manualMode) {
            $statusKey = 'active_manual';
            $statusText = 'Đang bảo trì (thủ công)';
        } elseif ($active) {
            $statusKey = 'active_scheduled';
            $statusText = 'Đang bảo trì theo lịch';
        } elseif ($noticeActive) {
            $statusKey = 'notice';
            $statusText = 'Đếm ngược ' . (int) $config['notice_minutes'] . ' phút trước bảo trì';
        } elseif ($hasSchedule && $now < $startTs) {
            $statusKey = 'scheduled';
            $statusText = 'Đã đặt lịch';
        } elseif ($scheduledAutoMode && $endTs !== null && $now >= $endTs) {
            $statusKey = 'finished';
            $statusText = 'Đã hết thời lượng dự kiến';
        } elseif ($manualMode && $hasSchedule) {
            $statusKey = 'waiting_manual';
            $statusText = 'Đã bật bảo trì, chờ tới giờ bắt đầu';
        }

        return [
            'enabled' => $manualMode,
            'active' => $active,
            'scheduled' => $hasSchedule,
            'manual_mode' => $manualMode,
            'auto_mode' => !$manualMode,
            'start_at' => $config['start_at'],
            'end_at' => $endTs ? date('Y-m-d H:i:s', $endTs) : null,
            'duration_minutes' => $config['duration_minutes'],
            'notice_minutes' => $config['notice_minutes'],
            'notice_active' => $noticeActive,
            'notice_seconds_left' => $noticeLeft,
            'seconds_until_start' => $secondsUntilStart,
            'seconds_until_end' => $secondsUntilEnd,
            'show_end_countdown' => $showEndCountdown,
            'manual_overdue' => $manualOverdue,
            'message' => $config['message'],
            'server_time' => date('Y-m-d H:i:s', $now),
            'status_key' => $statusKey,
            'status_text' => $statusText,
        ];
    }

    private function columnExists($column)
    {
        if (!$this->connection) {
            return false;
        }

        $safe = $this->connection->real_escape_string($column);
        $result = $this->connection->query("SHOW COLUMNS FROM `setting` LIKE '{$safe}'");
        return $result && $result->num_rows > 0;
    }

    private function defaultConfig()
    {
        return [
            'enabled' => 0,
            'start_at' => null,
            'duration_minutes' => 60,
            'notice_minutes' => 5,
            'message' => 'Hệ thống đang bảo trì để nâng cấp dịch vụ. Vui lòng quay lại sau ít phút.',
        ];
    }

    private function normalizeConfig(array $row)
    {
        $defaults = $this->defaultConfig();
        return [
            'enabled' => (int) ($row['maintenance_enabled'] ?? $defaults['enabled']),
            'start_at' => $this->normalizeDateTime($row['maintenance_start_at'] ?? $defaults['start_at']),
            'duration_minutes' => max(1, (int) ($row['maintenance_duration_minutes'] ?? $defaults['duration_minutes'])),
            'notice_minutes' => max(1, (int) ($row['maintenance_notice_minutes'] ?? $defaults['notice_minutes'])),
            'message' => trim((string) ($row['maintenance_message'] ?? $defaults['message'])) ?: $defaults['message'],
        ];
    }

    private function normalizeDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}

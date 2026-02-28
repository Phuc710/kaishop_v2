<?php

class MaintenanceService
{
    private $connection;
    private DateTimeZone $appTz;
    private DateTimeZone $displayTz;

    public function __construct($connection = null)
    {
        if ($connection instanceof mysqli) {
            $this->connection = $connection;
        } else {
            global $connection;
            $this->connection = $connection instanceof mysqli ? $connection : null;
        }

        $appTz = function_exists('app_timezone') ? app_timezone() : date_default_timezone_get();
        $displayTz = function_exists('app_display_timezone') ? app_display_timezone() : $appTz;
        $this->appTz = $this->resolveTimezone($appTz);
        $this->displayTz = $this->resolveTimezone($displayTz);
    }

    public function getConfig(): array
    {
        if (!$this->connection) {
            return $this->defaultConfig();
        }

        $row = $this->connection->query("SELECT * FROM `setting` ORDER BY `id` ASC LIMIT 1");
        $data = $row ? $row->fetch_assoc() : null;

        return $this->normalizeConfig(is_array($data) ? $data : []);
    }

    public function saveConfig(array $input): array
    {
        if (!$this->connection) {
            return ['success' => false, 'message' => 'Không kết nối được cơ sở dữ liệu'];
        }

        $enabled = !empty($input['maintenance_enabled']) ? 1 : 0;
        $notice = max(1, min(60, (int) ($input['maintenance_notice_minutes'] ?? 5)));
        $message = trim((string) ($input['maintenance_message'] ?? ''));

        $startAt = $this->normalizeInputDateTime($input['maintenance_start_at'] ?? null);
        $endAt = $this->normalizeInputDateTime($input['maintenance_end_at'] ?? null);

        if (($input['maintenance_start_at'] ?? '') !== '' && $startAt === null) {
            return ['success' => false, 'message' => 'Thời gian bắt đầu không hợp lệ'];
        }
        if (($input['maintenance_end_at'] ?? '') !== '' && $endAt === null) {
            return ['success' => false, 'message' => 'Thời gian kết thúc không hợp lệ'];
        }
        if ($startAt === null && $endAt !== null) {
            return ['success' => false, 'message' => 'Vui lòng chọn thời gian bắt đầu trước'];
        }

        if ($startAt !== null && $endAt === null) {
            $startTs = $this->parseStoredToTimestamp($startAt);
            $endAt = $startTs !== null ? $this->formatAppDateTime($startTs + 3600) : null;
        }

        if ($startAt !== null && $endAt !== null) {
            $startTs = $this->parseStoredToTimestamp($startAt);
            $endTs = $this->parseStoredToTimestamp($endAt);
            if ($endTs !== null && $startTs !== null && $endTs <= $startTs) {
                return ['success' => false, 'message' => 'Thời gian kết thúc phải sau thời gian bắt đầu'];
            }
        }

        $safeMessage = $this->connection->real_escape_string($message);
        $safeStart = $startAt !== null ? "'" . $this->connection->real_escape_string($startAt) . "'" : "NULL";
        $safeEnd = $endAt !== null ? "'" . $this->connection->real_escape_string($endAt) . "'" : "NULL";

        $sql = "UPDATE `setting` SET
            `maintenance_enabled` = {$enabled},
            `maintenance_start_at` = {$safeStart},
            `maintenance_end_at` = {$safeEnd},
            `maintenance_notice_minutes` = {$notice},
            `maintenance_message` = '{$safeMessage}'
            ORDER BY `id` ASC LIMIT 1";

        if (!$this->connection->query($sql)) {
            return ['success' => false, 'message' => 'Không thể lưu cấu hình bảo trì: ' . $this->connection->error];
        }

        return ['success' => true, 'message' => 'Đã lưu cấu hình bảo trì'];
    }

    public function clearNow(): array
    {
        if (!$this->connection) {
            return ['success' => false, 'message' => 'Không kết nối được cơ sở dữ liệu'];
        }

        $sql = "UPDATE `setting` SET
            `maintenance_enabled` = 0,
            `maintenance_start_at` = NULL,
            `maintenance_end_at` = NULL
            ORDER BY `id` ASC LIMIT 1";

        if (!$this->connection->query($sql)) {
            return ['success' => false, 'message' => 'Không thể tắt chế độ bảo trì'];
        }

        return ['success' => true, 'message' => 'Đã tắt chế độ bảo trì'];
    }

    public function setManualMode(bool $enabled): array
    {
        if (!$this->connection) {
            return ['success' => false, 'message' => 'Không kết nối được cơ sở dữ liệu'];
        }

        $value = $enabled ? 1 : 0;
        $sql = "UPDATE `setting` SET `maintenance_enabled` = {$value} ORDER BY `id` ASC LIMIT 1";

        if (!$this->connection->query($sql)) {
            return ['success' => false, 'message' => 'Không thể cập nhật chế độ bảo trì'];
        }

        return [
            'success' => true,
            'message' => $enabled ? 'Đã bật bảo trì thủ công' : 'Đã tắt bảo trì thủ công',
            'maintenance' => $this->getState(true),
        ];
    }

    public function getState($refresh = false): array
    {
        $config = $this->getConfig();
        $nowTs = $this->nowTimestamp();
        $enabled = !empty($config['enabled']);
        $noticeMinutes = (int) $config['notice_minutes'];
        $noticeSeconds = $noticeMinutes * 60;

        $startTs = $this->parseStoredToTimestamp($config['start_at']);
        $endTs = $this->parseStoredToTimestamp($config['end_at']);
        $scheduleConfigured = $startTs !== null;

        if ($scheduleConfigured && $endTs === null) {
            $endTs = $startTs + 3600;
        }

        $phase = 'off';
        $active = false;
        $secondsUntilStart = null;
        $secondsUntilEnd = null;
        $noticeActive = false;
        $noticeLeft = null;
        $statusText = 'Đang tắt bảo trì';
        $scheduleActive = false;
        $scheduleFinished = false;
        $manualOverdue = false;
        $activeByManual = false;
        $activeBySchedule = false;

        if ($enabled) {
            $active = true;
            $activeByManual = true;
            $phase = 'active';
            $secondsUntilStart = 0;

            if ($endTs !== null) {
                $manualOverdue = $scheduleConfigured && $nowTs >= $endTs;
            }

            $statusText = $manualOverdue
                ? 'Hệ thống vẫn đang bảo trì do đang bật thủ công'
                : 'Hệ thống đang bảo trì thủ công';
        } elseif ($scheduleConfigured && $endTs !== null) {
            if ($nowTs < $startTs) {
                $secondsUntilStart = $startTs - $nowTs;
                $noticeActive = $secondsUntilStart <= $noticeSeconds;
                $noticeLeft = $noticeActive ? $secondsUntilStart : null;
                $phase = $noticeActive ? 'countdown' : 'scheduled';
                $statusText = $noticeActive
                    ? 'Đếm ngược trước khi bảo trì'
                    : 'Đã lên lịch bảo trì';
            } elseif ($nowTs < $endTs) {
                $active = true;
                $scheduleActive = true;
                $activeBySchedule = true;
                $secondsUntilStart = 0;
                $secondsUntilEnd = max(0, $endTs - $nowTs);
                $phase = 'active';
                $statusText = 'Hệ thống đang bảo trì';
            } else {
                $scheduleFinished = true;
                $phase = 'finished';
                $statusText = 'Đã kết thúc thời gian bảo trì';
            }
        }

        $durationMinutes = ($startTs !== null && $endTs !== null)
            ? max(1, (int) round(($endTs - $startTs) / 60))
            : 60;

        return [
            'enabled' => $enabled,
            'active' => $active,
            'scheduled' => $scheduleConfigured,
            'schedule_configured' => $scheduleConfigured,
            'schedule_active' => $scheduleActive,
            'schedule_finished' => $scheduleFinished,
            'active_by_manual' => $activeByManual,
            'active_by_schedule' => $activeBySchedule,
            'manual_overdue' => $manualOverdue,
            'phase' => $phase,
            'start_at' => $startTs !== null ? $this->formatAppDateTime($startTs) : null,
            'start_at_ts' => $startTs,
            'start_at_display' => $startTs !== null ? $this->formatDisplayDateTime($startTs) : null,
            'end_at' => $endTs !== null ? $this->formatAppDateTime($endTs) : null,
            'end_at_ts' => $endTs,
            'end_at_display' => $endTs !== null ? $this->formatDisplayDateTime($endTs) : null,
            'duration_minutes' => $durationMinutes,
            'notice_minutes' => $noticeMinutes,
            'notice_active' => $noticeActive,
            'notice_seconds_left' => $noticeLeft,
            'seconds_until_start' => $secondsUntilStart,
            'seconds_until_end' => $secondsUntilEnd,
            'show_end_countdown' => $activeBySchedule && $secondsUntilEnd !== null && $secondsUntilEnd > 0,
            'message' => $config['message'],
            'server_time' => $this->formatAppDateTime($nowTs),
            'server_time_ts' => $nowTs,
            'server_timezone' => $this->appTz->getName(),
            'display_timezone' => $this->displayTz->getName(),
            'status_text' => $statusText,
        ];
    }

    public function toDateTimeLocalInput($storedDateTime): string
    {
        $ts = $this->parseStoredToTimestamp($storedDateTime);
        if ($ts === null) {
            return '';
        }

        return (new DateTimeImmutable('@' . $ts))
            ->setTimezone($this->displayTz)
            ->format('Y-m-d\TH:i');
    }

    private function defaultConfig(): array
    {
        return [
            'enabled' => 0,
            'start_at' => null,
            'end_at' => null,
            'notice_minutes' => 5,
            'message' => 'Hệ thống đang bảo trì để nâng cấp dịch vụ. Vui lòng quay lại sau ít phút.',
        ];
    }

    private function normalizeConfig(array $row): array
    {
        $defaults = $this->defaultConfig();

        return [
            'enabled' => (int) ($row['maintenance_enabled'] ?? $defaults['enabled']),
            'start_at' => $this->normalizeStoredDateTime($row['maintenance_start_at'] ?? $defaults['start_at']),
            'end_at' => $this->normalizeStoredDateTime($row['maintenance_end_at'] ?? $defaults['end_at']),
            'notice_minutes' => max(1, min(60, (int) ($row['maintenance_notice_minutes'] ?? $defaults['notice_minutes']))),
            'message' => trim((string) ($row['maintenance_message'] ?? $defaults['message'])) ?: $defaults['message'],
        ];
    }

    private function normalizeStoredDateTime($value)
    {
        $dt = $this->parseDateTime($value, $this->appTz);
        if (!$dt) {
            return null;
        }

        return $dt->setTimezone($this->appTz)->format('Y-m-d H:i:s');
    }

    private function normalizeInputDateTime($value)
    {
        $dt = $this->parseDateTime($value, $this->displayTz);
        if (!$dt) {
            return null;
        }

        return $dt->setTimezone($this->appTz)->format('Y-m-d H:i:s');
    }

    private function parseStoredToTimestamp($value)
    {
        $dt = $this->parseDateTime($value, $this->appTz);
        if (!$dt) {
            return null;
        }

        return (int) $dt->setTimezone($this->appTz)->format('U');
    }

    private function parseDateTime($value, DateTimeZone $sourceTimezone)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace('T', ' ', $raw);
        if (strlen($raw) === 16) {
            $raw .= ':00';
        }

        $hasOffset = preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/i', $raw) === 1;

        try {
            if ($hasOffset) {
                return new DateTimeImmutable($raw);
            }

            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, $sourceTimezone);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }

            return new DateTimeImmutable($raw, $sourceTimezone);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function nowTimestamp(): int
    {
        return (int) (new DateTimeImmutable('now', $this->appTz))->format('U');
    }

    private function formatAppDateTime(int $timestamp): string
    {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($this->appTz)
            ->format('Y-m-d H:i:s');
    }

    private function formatDisplayDateTime(int $timestamp): string
    {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($this->displayTz)
            ->format('Y-m-d H:i:s');
    }

    private function resolveTimezone($timezone): DateTimeZone
    {
        try {
            $name = trim((string) $timezone);
            if ($name === '') {
                $name = 'UTC';
            }

            return new DateTimeZone($name);
        } catch (Throwable $e) {
            return new DateTimeZone('UTC');
        }
    }
}

<?php

/**
 * TimeService
 * Centralized time parsing/formatting for API + UI (supports UTC app time and separate DB/display timezones).
 */
class TimeService
{
    private static ?self $instance = null;

    private DateTimeZone $appTz;
    private DateTimeZone $displayTz;
    private DateTimeZone $dbTz;

    public function __construct(?string $appTimezone = null, ?string $displayTimezone = null, ?string $dbTimezone = null)
    {
        $this->appTz = $this->makeTimezone($appTimezone ?: (function_exists('app_timezone') ? app_timezone() : date_default_timezone_get()));
        $this->displayTz = $this->makeTimezone($displayTimezone ?: (function_exists('app_display_timezone') ? app_display_timezone() : $this->appTz->getName()));
        $this->dbTz = $this->makeTimezone($dbTimezone ?: (function_exists('app_db_timezone') ? app_db_timezone() : $this->appTz->getName()));
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAppTimezone(): string
    {
        return $this->appTz->getName();
    }

    public function getDisplayTimezone(): string
    {
        return $this->displayTz->getName();
    }

    public function getDbTimezone(): string
    {
        return $this->dbTz->getName();
    }

    public function nowTs(): int
    {
        return time();
    }

    public function nowSql(?string $timezone = null): string
    {
        return $this->nowDateTime($timezone)->format('Y-m-d H:i:s');
    }

    public function nowDateTime(?string $timezone = null): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->resolveTimezone($timezone, $this->appTz));
    }

    /**
     * Parse mixed input to unix timestamp.
     * If string has no timezone offset, it is interpreted in $assumeTimezone (defaults to DB timezone).
     */
    public function toTimestamp($value, ?string $assumeTimezone = null): ?int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if ($value === null) {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            $num = (int) trim((string) $value);
            // handle milliseconds timestamp
            if ($num > 9999999999) {
                $num = (int) floor($num / 1000);
            }
            return $num > 0 ? $num : null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return null;
        }

        $assumeTz = $this->resolveTimezone($assumeTimezone, $this->dbTz);

        // Explicit common DB format without timezone
        $knownFormats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd-m-Y H:i:s',
            'd/m/Y H:i:s',
            'H:i d-m-Y',
        ];
        foreach ($knownFormats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $raw, $assumeTz);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->getTimestamp();
            }
        }

        // ISO-like strings may already contain timezone.
        try {
            $dt = new DateTimeImmutable($raw, $assumeTz);
            return $dt->getTimestamp();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function format($value, string $format = 'Y-m-d H:i:s', ?string $targetTimezone = null, ?string $assumeTimezone = null): string
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return '';
        }
        $tz = $this->resolveTimezone($targetTimezone, $this->displayTz);
        return (new DateTimeImmutable('@' . $ts))
            ->setTimezone($tz)
            ->format($format);
    }

    public function formatDisplay($value, string $format = 'Y-m-d H:i:s', ?string $assumeTimezone = null): string
    {
        return $this->format($value, $format, $this->displayTz->getName(), $assumeTimezone);
    }

    public function formatApp($value, string $format = 'Y-m-d H:i:s', ?string $assumeTimezone = null): string
    {
        return $this->format($value, $format, $this->appTz->getName(), $assumeTimezone);
    }

    public function formatDb($value, string $format = 'Y-m-d H:i:s', ?string $assumeTimezone = null): string
    {
        return $this->format($value, $format, $this->dbTz->getName(), $assumeTimezone);
    }

    public function toIso8601($value, ?string $targetTimezone = null, ?string $assumeTimezone = null): string
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return '';
        }
        $tz = $this->resolveTimezone($targetTimezone, $this->displayTz);
        return (new DateTimeImmutable('@' . $ts))
            ->setTimezone($tz)
            ->format(DateTimeInterface::ATOM);
    }

    public function toIso8601Utc($value, ?string $assumeTimezone = null): string
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return '';
        }
        return gmdate('c', $ts);
    }

    /**
     * Normalize time fields for API payloads.
     *
     * @return array{ts:int|null,iso:string,iso_utc:string,display:string}
     */
    public function normalizeApiTime($value, ?string $assumeTimezone = null, string $displayFormat = 'Y-m-d H:i:s'): array
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return [
                'ts' => null,
                'iso' => '',
                'iso_utc' => '',
                'display' => '',
            ];
        }

        return [
            'ts' => $ts,
            'iso' => $this->toIso8601($ts, $this->displayTz->getName()),
            'iso_utc' => $this->toIso8601Utc($ts),
            'display' => $this->format($ts, $displayFormat, $this->displayTz->getName()),
        ];
    }

    public function diffForHumans($value, ?string $assumeTimezone = null): string
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return '';
        }

        $diff = max(0, $this->nowTs() - $ts);
        $units = [
            ['năm', 31536000],
            ['tháng', 2592000],
            ['tuần', 604800],
            ['ngày', 86400],
            ['giờ', 3600],
            ['phút', 60],
            ['giây', 1],
        ];

        foreach ($units as [$label, $seconds]) {
            $n = (int) floor($diff / $seconds);
            if ($n > 0) {
                return $n . ' ' . $label . ' trước';
            }
        }

        return 'vừa xong';
    }

    private function makeTimezone(string $name): DateTimeZone
    {
        try {
            return new DateTimeZone($name);
        } catch (Throwable $e) {
            return new DateTimeZone('Asia/Ho_Chi_Minh');
        }
    }

    private function resolveTimezone(?string $name, DateTimeZone $fallback): DateTimeZone
    {
        if ($name === null || trim($name) === '') {
            return $fallback;
        }
        return $this->makeTimezone($name);
    }
}

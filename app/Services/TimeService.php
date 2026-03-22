<?php

/**
 * TimeService
 * Centralized time parsing/formatting for API + UI (supports UTC app time and separate DB/display timezones).
 */
class TimeService
{
    private static $instance = null;

    private $appTz;
    private $displayTz;
    private $dbTz;

    public function __construct($appTimezone = null, $displayTimezone = null, $dbTimezone = null)
    {
        $this->appTz = $this->makeTimezone($appTimezone ?: (function_exists('app_timezone') ? app_timezone() : date_default_timezone_get()));
        $this->displayTz = $this->makeTimezone($displayTimezone ?: (function_exists('app_display_timezone') ? app_display_timezone() : $this->appTz->getName()));
        $this->dbTz = $this->makeTimezone($dbTimezone ?: (function_exists('app_db_timezone') ? app_db_timezone() : $this->appTz->getName()));
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAppTimezone()
    {
        return $this->appTz->getName();
    }

    public function getDisplayTimezone()
    {
        return $this->displayTz->getName();
    }

    public function getDbTimezone()
    {
        return $this->dbTz->getName();
    }

    public function nowTs()
    {
        return time();
    }

    public function nowSql($timezone = null)
    {
        return $this->nowDateTime($timezone)->format('Y-m-d H:i:s');
    }

    public function nowDateTime($timezone = null)
    {
        return new DateTimeImmutable('now', $this->resolveTimezone($timezone, $this->appTz));
    }

    /**
     * Parse mixed input to unix timestamp.
     * If string has no timezone offset, it is interpreted in $assumeTimezone (defaults to DB timezone).
     */
    public function toTimestamp($value, $assumeTimezone = null)
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
            'd-m-Y H:i',
            'd/m/Y H:i',
            'd-m-Y',
            'd/m/Y',
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

    public function format($value, $format = 'd/m/Y H:i', $targetTimezone = null, $assumeTimezone = null)
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

    public function formatDisplay($value, $format = 'd/m/Y H:i', $assumeTimezone = null)
    {
        return $this->format($value, $format, $this->displayTz->getName(), $assumeTimezone);
    }

    public function formatApp($value, $format = 'Y-m-d H:i:s', $assumeTimezone = null)
    {
        return $this->format($value, $format, $this->appTz->getName(), $assumeTimezone);
    }

    public function formatDb($value, $format = 'Y-m-d H:i:s', $assumeTimezone = null)
    {
        return $this->format($value, $format, $this->dbTz->getName(), $assumeTimezone);
    }

    public function toIso8601($value, $targetTimezone = null, $assumeTimezone = null)
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

    public function toIso8601Utc($value, $assumeTimezone = null)
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
    public function normalizeApiTime($value, $assumeTimezone = null, $displayFormat = 'd/m/Y H:i')
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

    public function diffForHumans($value, $assumeTimezone = null)
    {
        $ts = $this->toTimestamp($value, $assumeTimezone);
        if ($ts === null) {
            return '';
        }

        $isFuture = ($ts > $this->nowTs());
        $diff = abs($this->nowTs() - $ts);

        $units = [
            ['năm', 31536000],
            ['tháng', 2592000],
            ['tuần', 604800],
            ['ngày', 86400],
            ['giờ', 3600],
            ['phút', 60],
            ['giây', 1],
        ];

        foreach ($units as $unit) {
            $label = $unit[0];
            $seconds = $unit[1];
            $n = (int) floor($diff / $seconds);
            if ($n > 0) {
                return $n . ' ' . $label . ($isFuture ? ' tới' : ' trước');
            }
        }

        return $isFuture ? 'sắp tới' : 'vừa xong';
    }

    private function makeTimezone($name)
    {
        try {
            return new DateTimeZone($name);
        } catch (Throwable $e) {
            return new DateTimeZone('Asia/Ho_Chi_Minh');
        }
    }

    private function resolveTimezone($name, $fallback)
    {
        if ($name === null || trim($name) === '') {
            return $fallback;
        }
        return $this->makeTimezone($name);
    }
}

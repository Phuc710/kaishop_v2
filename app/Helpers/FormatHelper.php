<?php

/**
 * FormatHelper
 * Quản lý định dạng UI chung cho toàn bộ Admin (chuẩn OOP)
 * Xử lý màu sắc giá trị (Tiền), Nhãn trạng thái, Ngày tháng.
 */
class FormatHelper
{
    /**
     * Định dạng số dư biến động (Số dư thay đổi / Biến động)
     * + Dấu dương: Màu Xanh Lục (Green)
     * - Dấu âm: Màu Đỏ (Red)
     */
    public static function balanceChange($amount): string
    {
        if ($amount === null || $amount === '')
            return '--';
        $val = (int) preg_replace('/[^0-9-]/', '', (string) $amount);
        if ($val === 0)
            return '<span class="font-weight-bold text-dark">0đ</span>';

        $formatted = number_format(abs($val), 0, '.', ',') . 'đ';
        if ($val > 0) {
            return '<span class="font-weight-bold" style="color: #23d71c;">+' . $formatted . '</span>';
        }
        return '<span class="font-weight-bold" style="color: #ff0000;">-' . $formatted . '</span>';
    }

    /**
     * Định dạng số dư hiện tại (Số dư sau khi thực hiện)
     * Màu Xanh Dương (Blue)
     */
    public static function currentBalance($amount): string
    {
        if ($amount === null || $amount === '')
            return '--';
        $val = (int) preg_replace('/[^0-9-]/', '', (string) $amount);
        $formatted = number_format(abs($val), 0, '.', ',') . 'đ';
        $prefix = $val < 0 ? '-' : '';
        return '<span class="font-weight-bold" style="color: #1200ff;">' . $prefix . $formatted . '</span>';
    }

    /**
     * Định dạng số dư ban đầu (Số dư trước)
     * Màu Tối (Dark / Màu mặc định)
     */
    public static function initialBalance($amount): string
    {
        if ($amount === null || $amount === '')
            return '--';
        $val = (int) preg_replace('/[^0-9-]/', '', (string) $amount);
        $formatted = number_format(abs($val), 0, '.', ',') . 'đ';
        $prefix = $val < 0 ? '-' : '';
        return '<span class="font-weight-bold text-dark">' . $prefix . $formatted . '</span>';
    }

    /**
     * Định dạng cho "Thành Tiền" (Giao dịch trừ tiền)
     */
    public static function price($amount): string
    {
        return self::balanceChange($amount); // Reuse balance logic as user stated all amounts should follow the +Green / -Red scheme
    }

    /**
     * Định dạng thời gian kèm Time Ago (dạng badge tooltip)
     */
    public static function eventTime($eventTime, $rawTime): string
    {
        if (class_exists('TimeService')) {
            try {
                $timeService = TimeService::instance();
                $meta = $timeService->normalizeApiTime($eventTime);
                if (($meta['ts'] ?? null) === null) {
                    $meta = $timeService->normalizeApiTime($rawTime);
                }
                if (($meta['ts'] ?? null) !== null) {
                    $display = (string) ($meta['display'] ?? '');
                    if ($display === '') {
                        $display = $timeService->formatDisplay((int) $meta['ts']);
                    }
                    $timeAgo = self::timeAgo((int) $meta['ts']);
                    return sprintf(
                        '<span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="%s">%s</span>',
                        htmlspecialchars($timeAgo),
                        htmlspecialchars($display)
                    );
                }
            } catch (Throwable $e) {
                // Fallback to legacy normalization below.
            }
        }

        $normalized = trim((string) $eventTime);
        if ($normalized === '' || $normalized === '0000-00-00 00:00:00') {
            $raw = trim((string) $rawTime);
            if ($raw === '') {
                return '--';
            }
            if (ctype_digit($raw)) {
                $normalized = date('Y-m-d H:i:s', (int) $raw);
            } else {
                $formats = ['H:i d-m-Y', 'Y-m-d H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i:s'];
                foreach ($formats as $format) {
                    $date = DateTime::createFromFormat($format, $raw);
                    if ($date instanceof DateTime) {
                        $normalized = $date->format('Y-m-d H:i:s');
                        break;
                    }
                }
                if ($normalized === '') {
                    $normalized = $raw;
                }
            }
        }

        if ($normalized === '' || $normalized === '--') {
            return '--';
        }

        $timeAgo = self::timeAgo($normalized);
        return sprintf(
            '<span class="badge date-badge" data-toggle="tooltip" data-placement="top" title="%s">%s</span>',
            htmlspecialchars($timeAgo),
            htmlspecialchars($normalized)
        );
    }

    /**
     * Tính toán khoảng thời gian trôi qua (Time Ago)
     */
    public static function timeAgo($datetime, $full = false): string
    {
        if (!$full && class_exists('TimeService')) {
            try {
                $timeService = TimeService::instance();
                $human = (string) $timeService->diffForHumans($datetime);
                if ($human !== '') {
                    return $human;
                }
            } catch (Throwable $e) {
                // Fallback to legacy logic below for compatibility.
            }
        }

        $now = new DateTime();
        try {
            $ago = new DateTime($datetime);
        } catch (Exception $e) {
            return $datetime;
        }
        $diff = $now->diff($ago);

        $days = (int) $diff->d;
        $weeks = (int) floor($days / 7);
        $remainingDays = $days % 7;

        $parts = [];
        if ($diff->y)
            $parts['y'] = $diff->y . ' năm';
        if ($diff->m)
            $parts['m'] = $diff->m . ' tháng';
        if ($weeks)
            $parts['w'] = $weeks . ' tuần';
        if ($remainingDays)
            $parts['d'] = $remainingDays . ' ngày';
        if ($diff->h)
            $parts['h'] = $diff->h . ' tiếng';
        if ($diff->i)
            $parts['i'] = $diff->i . ' phút';
        if ($diff->s)
            $parts['s'] = $diff->s . ' giây';

        if (!$full) {
            $parts = array_slice($parts, 0, 1);
        }
        return $parts ? implode(', ', $parts) . ' trước' : 'vừa xong';
    }

    /**
     * Tạo slug từ chuỗi (URL friendly)
     */
    public static function toSlug(string $str): string
    {
        $str = trim(mb_strtolower($str, 'UTF-8'));
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);
        $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
        $str = preg_replace('/([\s]+)/', '-', $str);
        return trim($str, '-');
    }
}

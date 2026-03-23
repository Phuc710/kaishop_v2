<?php

class SeoContentHelper
{
    public static function cleanText($value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    public static function excerpt($value, int $limit = 160): string
    {
        $text = self::cleanText($value);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }

            $slice = mb_substr($text, 0, $limit, 'UTF-8');
            $lastSpace = mb_strrpos($slice, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > (int) floor($limit * 0.6)) {
                $slice = mb_substr($slice, 0, $lastSpace, 'UTF-8');
            }

            return rtrim($slice, " ,.;:-") . '...';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        $slice = substr($text, 0, $limit);
        $lastSpace = strrpos($slice, ' ');
        if ($lastSpace !== false && $lastSpace > (int) floor($limit * 0.6)) {
            $slice = substr($slice, 0, $lastSpace);
        }

        return rtrim($slice, " ,.;:-") . '...';
    }

    public static function keywordString(array $phrases, int $limit = 12): string
    {
        $unique = [];
        $seen = [];

        foreach ($phrases as $phrase) {
            $clean = self::cleanText($phrase);
            if ($clean === '') {
                continue;
            }

            $normalized = function_exists('mb_strtolower')
                ? mb_strtolower($clean, 'UTF-8')
                : strtolower($clean);

            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $unique[] = $clean;

            if (count($unique) >= $limit) {
                break;
            }
        }

        return implode(', ', $unique);
    }

    public static function namesFromRows(array $rows, string $field = 'name', int $limit = 6): array
    {
        $names = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = self::cleanText($row[$field] ?? '');
            if ($name === '') {
                continue;
            }

            $names[] = $name;
            if (count($names) >= $limit) {
                break;
            }
        }

        return $names;
    }
}

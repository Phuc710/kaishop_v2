<?php

class ClientIpHelper
{
    /**
     * @return string[]
     */
    public static function candidates(array $server): array
    {
        $candidates = [];

        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $raw = trim((string) ($server[$key] ?? ''));
            if ($raw === '') {
                continue;
            }

            $parts = $key === 'HTTP_X_FORWARDED_FOR' ? explode(',', $raw) : [$raw];
            foreach ($parts as $part) {
                $candidate = trim(trim((string) $part), "[] \t\n\r\0\x0B");
                if ($candidate !== '') {
                    $candidates[] = $candidate;
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    public static function detect(array $server, string $fallback = '0.0.0.0'): string
    {
        foreach (self::candidates($server) as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return $fallback;
    }
}

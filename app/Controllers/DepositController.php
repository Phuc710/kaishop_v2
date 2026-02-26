<?php

/**
 * Deposit Controller (User-facing)
 * Bank deposit endpoints (UI page moved into Profile)
 */
class DepositController extends Controller
{
    private const ROUTE_METHOD_MAP = [
        'bank' => DepositService::METHOD_BANK_SEPAY,
        'bank_sepay' => DepositService::METHOD_BANK_SEPAY,
        'binance' => 'binance',
        'momo' => 'momo',
    ];

    private AuthService $authService;
    private PendingDeposit $depositModel;
    private DepositService $depositService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->depositModel = new PendingDeposit();
        $this->depositService = new DepositService($this->depositModel);
    }

    /**
     * Require user login and return current user
     *
     * @return array<string,mixed>
     */
    private function requireUser(): array
    {
        $this->authService->requireAuth();
        return $this->authService->getCurrentUser();
    }

    private function methodCodeToRouteSegment(string $methodCode): string
    {
        return $methodCode === DepositService::METHOD_BANK_SEPAY ? 'bank' : $methodCode;
    }

    private function routeSegmentToMethodCode(?string $segment): string
    {
        $key = strtolower(trim((string) $segment));
        return self::ROUTE_METHOD_MAP[$key] ?? DepositService::METHOD_BANK_SEPAY;
    }

    /**
     * @param array<string,mixed> $user
     */
    private function findUserDepositByCode(array $user, string $code): ?array
    {
        $deposit = $this->depositModel->findByCode($code);
        if (!$deposit || (int) ($deposit['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return null;
        }
        return $deposit;
    }

    private function getUserBalanceValue(int $userId): int
    {
        global $connection;
        $stmt = $connection->query('SELECT `money` FROM `users` WHERE `id` = ' . $userId);
        $row = $stmt ? $stmt->fetch_assoc() : null;
        return (int) ($row['money'] ?? 0);
    }

    /**
     * @param array<string,mixed> $deposit
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    private function buildDepositStatusResponseData(array $deposit, array $user): array
    {
        $timeService = TimeService::instance();
        $createdAtRaw = (string) ($deposit['created_at'] ?? '');
        $createdAt = $timeService->toTimestamp($createdAtRaw);
        $now = time();
        $elapsed = $createdAt ? ($now - $createdAt) : 0;
        $ttlSeconds = $this->depositModel->getPendingTtlSeconds();
        $serverNowMeta = $timeService->normalizeApiTime($now, 'UTC');
        $createdMeta = $timeService->normalizeApiTime($createdAtRaw);

        if (($deposit['status'] ?? '') === 'pending' && $elapsed >= $ttlSeconds) {
            $this->depositModel->markExpired();
            $deposit['status'] = 'expired';
        }

        $responseData = [
            'success' => true,
            'status' => (string) ($deposit['status'] ?? 'pending'),
            'remaining' => max(0, $ttlSeconds - $elapsed),
            'ttl_seconds' => $ttlSeconds,
            'server_now_ts' => $now,
            'server_now_iso' => (string) ($serverNowMeta['iso'] ?? ''),
            'server_now_iso_utc' => (string) ($serverNowMeta['iso_utc'] ?? ''),
            'created_at_ts' => $createdAt ?: 0,
            'created_at_iso' => (string) ($createdMeta['iso'] ?? ''),
            'created_at_iso_utc' => (string) ($createdMeta['iso_utc'] ?? ''),
            'created_at_display' => (string) ($createdMeta['display'] ?? ''),
            'expires_at_ts' => $createdAt ? ($createdAt + $ttlSeconds) : 0,
        ];

        if (($deposit['status'] ?? '') === 'completed') {
            $completedAtRaw = (string) ($deposit['completed_at'] ?? '');
            $completedAtTs = $timeService->toTimestamp($completedAtRaw);
            $completedMeta = $timeService->normalizeApiTime($completedAtRaw);
            $processingSeconds = ($createdAt && $completedAtTs) ? max(0, $completedAtTs - $createdAt) : 0;

            $responseData['new_balance'] = $this->getUserBalanceValue((int) ($user['id'] ?? 0));
            $responseData['deposit_info'] = [
                'method' => DepositService::METHOD_BANK_SEPAY,
                'deposit_code' => (string) ($deposit['deposit_code'] ?? ''),
                'content' => (string) ($deposit['deposit_code'] ?? ''),
                'amount' => (int) ($deposit['amount'] ?? 0),
                'bonus_percent' => (int) ($deposit['bonus_percent'] ?? 0),
                'created_at' => $createdAtRaw,
                'created_at_ts' => $createdAt ?: 0,
                'created_at_iso' => (string) ($createdMeta['iso'] ?? ''),
                'created_at_iso_utc' => (string) ($createdMeta['iso_utc'] ?? ''),
                'created_at_display' => (string) ($createdMeta['display'] ?? ''),
                'completed_at' => $completedAtRaw,
                'completed_at_ts' => $completedAtTs,
                'completed_at_iso' => (string) ($completedMeta['iso'] ?? ''),
                'completed_at_iso_utc' => (string) ($completedMeta['iso_utc'] ?? ''),
                'completed_at_display' => (string) ($completedMeta['display'] ?? ''),
                'processing_seconds' => $processingSeconds,
            ];
        }

        return $responseData;
    }

    private function renderBalancePage(string $routeMethod = 'bank')
    {
        $user = $this->requireUser();
        $siteConfig = Config::getSiteConfig();
        $methodCode = $this->routeSegmentToMethodCode($routeMethod);
        $routeSegment = $this->methodCodeToRouteSegment($methodCode);

        $depositPanel = $this->depositService->getProfilePanelData($siteConfig, $user, $methodCode);

        return $this->view('profile/index', [
            'user' => $user,
            'username' => (string) ($user['username'] ?? ''),
            'chungapi' => $siteConfig,
            'activePage' => 'balance',
            'profileSection' => 'balance',
            'depositPanel' => $depositPanel,
            'depositRouteMethod' => $routeSegment,
        ]);
    }

    /**
     * GET /deposit-bank (legacy)
     * Redirect to new pretty route
     */
    public function index()
    {
        $this->requireUser();
        return $this->redirect(url('balance/bank'));
    }

    /**
     * GET /balance
     */
    public function balance()
    {
        return $this->redirect(url('balance/bank'));
    }

    /**
     * GET /balance/{method}
     */
    public function balanceMethod($method)
    {
        $input = strtolower(trim((string) $method));
        $methodCode = $this->routeSegmentToMethodCode($input);
        $canonical = $this->methodCodeToRouteSegment($methodCode);
        if ($input !== $canonical) {
            return $this->redirect(url('balance/' . $canonical));
        }
        return $this->renderBalancePage($canonical);
    }

    /**
     * POST /deposit/create
     */
    public function create()
    {
        $user = $this->requireUser();
        if (!$this->validateCsrf()) {
            return $this->json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang.'], 403);
        }
        $siteConfig = Config::getSiteConfig();
        $amount = (int) $this->post('amount', 0);

        $result = $this->depositService->createBankDeposit($user, $amount, $siteConfig);
        if (empty($result['success'])) {
            return $this->json([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Không thể tạo giao dịch, vui lòng thử lại'),
            ]);
        }

        $payload = (array) ($result['data'] ?? []);

        Logger::info('Billing', 'deposit_created', "User {$user['username']} tạo giao dịch nạp tiền", [
            'amount' => $amount,
            'bonus' => (int) ($payload['bonus_percent'] ?? 0),
            'deposit_code' => (string) ($payload['deposit_code'] ?? ''),
            'method' => (string) ($payload['method'] ?? DepositService::METHOD_BANK_SEPAY),
        ]);

        return $this->json($result);
    }

    /**
     * GET /deposit/status/{code}
     */
    public function status($code)
    {
        $user = $this->requireUser();

        $deposit = $this->depositModel->findByCode((string) $code);
        if (!$deposit || (int) $deposit['user_id'] !== (int) $user['id']) {
            return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại']);
        }

        $createdAt = strtotime((string) ($deposit['created_at'] ?? ''));
        $now = time();
        $elapsed = $createdAt ? ($now - $createdAt) : 0;
        $ttlSeconds = $this->depositModel->getPendingTtlSeconds();

        if ($deposit['status'] === 'pending' && $elapsed >= $ttlSeconds) {
            $this->depositModel->markExpired();
            $deposit['status'] = 'expired';
        }

        $responseData = [
            'success' => true,
            'status' => (string) ($deposit['status'] ?? 'pending'),
            'remaining' => max(0, $ttlSeconds - $elapsed),
            'ttl_seconds' => $ttlSeconds,
            'server_now_ts' => $now,
            'created_at_ts' => $createdAt ?: 0,
            'expires_at_ts' => $createdAt ? ($createdAt + $ttlSeconds) : 0,
        ];

        if (($deposit['status'] ?? '') === 'completed') {
            global $connection;
            $stmt = $connection->query('SELECT `money` FROM `users` WHERE `id` = ' . (int) $user['id']);
            $row = $stmt ? $stmt->fetch_assoc() : null;
            $completedAtRaw = (string) ($deposit['completed_at'] ?? '');
            $completedAtTs = $completedAtRaw !== '' ? (strtotime($completedAtRaw) ?: 0) : 0;
            $processingSeconds = ($createdAt && $completedAtTs) ? max(0, $completedAtTs - $createdAt) : 0;

            $responseData['new_balance'] = (int) ($row['money'] ?? 0);
            $responseData['deposit_info'] = [
                'method' => DepositService::METHOD_BANK_SEPAY,
                'deposit_code' => (string) ($deposit['deposit_code'] ?? ''),
                'content' => (string) ($deposit['deposit_code'] ?? ''),
                'amount' => (int) ($deposit['amount'] ?? 0),
                'bonus_percent' => (int) ($deposit['bonus_percent'] ?? 0),
                'created_at' => (string) ($deposit['created_at'] ?? ''),
                'created_at_ts' => $createdAt ?: 0,
                'completed_at' => $completedAtRaw,
                'completed_at_ts' => $completedAtTs,
                'processing_seconds' => $processingSeconds,
            ];
        }

        return $this->json($responseData);
    }

    /**
     * GET /deposit/status-wait/{code}
     * Long polling endpoint for production-friendly realtime updates.
     */
    public function statusWait($code)
    {
        $user = $this->requireUser();
        $depositCode = trim((string) $code);
        $deposit = $this->findUserDepositByCode($user, $depositCode);
        if (!$deposit) {
            return $this->json(['success' => false, 'message' => 'Giao dá»‹ch khÃ´ng tá»“n táº¡i'], 404);
        }

        $since = strtolower(trim((string) $this->get('since', '')));
        $timeoutSeconds = max(5, min(30, (int) $this->get('timeout', 25)));
        $startedAt = microtime(true);
        $deadline = $startedAt + $timeoutSeconds;
        $pollIntervalUs = 800000;
        $payload = $this->buildDepositStatusResponseData($deposit, $user);

        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit($timeoutSeconds + 5);
        @ignore_user_abort(true);

        while (true) {
            $currentStatus = strtolower((string) ($payload['status'] ?? ''));
            $isTerminal = in_array($currentStatus, ['completed', 'expired', 'cancelled'], true);
            $statusChanged = ($since !== '' && $currentStatus !== $since);

            if ($since === '' || $isTerminal || $statusChanged) {
                $payload['long_poll'] = [
                    'timed_out' => false,
                    'waited_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ];
                return $this->json($payload);
            }

            if (microtime(true) >= $deadline) {
                break;
            }

            usleep($pollIntervalUs);
            $deposit = $this->findUserDepositByCode($user, $depositCode);
            if (!$deposit) {
                return $this->json(['success' => false, 'message' => 'Giao dá»‹ch khÃ´ng tá»“n táº¡i'], 404);
            }
            $payload = $this->buildDepositStatusResponseData($deposit, $user);
        }

        $payload['long_poll'] = [
            'timed_out' => true,
            'waited_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
        return $this->json($payload);
    }

    /**
     * POST /deposit/cancel
     */
    public function cancel()
    {
        $user = $this->requireUser();
        if (!$this->validateCsrf()) {
            return $this->json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang.'], 403);
        }
        $depositCode = trim((string) $this->post('deposit_code', ''));

        if ($depositCode === '') {
            return $this->json(['success' => false, 'message' => 'Thiếu mã giao dịch']);
        }

        $deposit = $this->depositModel->findByCode($depositCode);
        if (!$deposit || (int) $deposit['user_id'] !== (int) $user['id']) {
            return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại']);
        }

        if (($deposit['status'] ?? '') !== 'pending') {
            return $this->json(['success' => false, 'message' => 'Giao dịch đã được xử lý']);
        }

        $this->depositModel->cancelByUser((int) $deposit['id'], (int) $user['id']);

        Logger::info('Billing', 'deposit_cancelled', "User {$user['username']} huỷ giao dịch nạp tiền", [
            'deposit_code' => $depositCode,
        ]);

        return $this->json(['success' => true, 'message' => 'Đã huỷ giao dịch']);
    }
}

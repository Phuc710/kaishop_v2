<?php

/**
 * Deposit Controller (User-facing)
 */
class DepositController extends Controller
{
    private const ROUTE_METHOD_MAP = [
        'bank' => DepositService::METHOD_BANK_SEPAY,
        'bank_sepay' => DepositService::METHOD_BANK_SEPAY,
        'binance' => DepositService::METHOD_BINANCE,
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

    private function normalizeDepositMethod(?string $method): string
    {
        $method = strtolower(trim((string) $method));
        if ($method === DepositService::METHOD_BINANCE) {
            return DepositService::METHOD_BINANCE;
        }
        return DepositService::METHOD_BANK_SEPAY;
    }

    private function isEnglishStorefront(): bool
    {
        return function_exists('app_is_english') && app_is_english();
    }

    private function preferredRouteSegment(): string
    {
        return $this->isEnglishStorefront() ? 'binance' : 'bank';
    }

    private function isMethodAllowedForStorefront(string $methodCode): bool
    {
        return in_array($methodCode, [
            DepositService::METHOD_BANK_SEPAY,
            DepositService::METHOD_BINANCE,
            'momo',
        ], true);
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
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT `money` FROM `users` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$userId]);
        return (int) ($stmt->fetchColumn() ?? 0);
    }

    /**
     * @param array<string,mixed> $deposit
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    private function maybeProcessBinanceDeposit(array $deposit, array $user): array
    {
        $method = $this->normalizeDepositMethod((string) ($deposit['method'] ?? ''));
        if ($method !== DepositService::METHOD_BINANCE || (string) ($deposit['status'] ?? '') !== 'pending') {
            return $deposit;
        }
        if ($this->depositModel->isLogicallyExpired($deposit)) {
            $this->depositModel->markExpired();
            $freshExpired = $this->depositModel->findByCode((string) ($deposit['deposit_code'] ?? ''));
            return is_array($freshExpired) ? $freshExpired : $deposit;
        }

        $siteConfig = Config::getSiteConfig();
        $binanceService = $this->depositService->makeBinanceService($siteConfig);
        if (!$binanceService || !$binanceService->isEnabled()) {
            return $deposit;
        }

        try {
            $tx = $binanceService->findMatchingTransaction($deposit);
            if ($tx) {
                $binanceService->processTransaction($tx, $deposit, $user);
                $fresh = $this->depositModel->findByCode((string) ($deposit['deposit_code'] ?? ''));
                if (is_array($fresh)) {
                    return $fresh;
                }
            }
        } catch (Throwable $e) {
            Logger::warning('Billing', 'binance_status_refresh_error', 'Could not refresh Binance pending status', [
                'deposit_code' => (string) ($deposit['deposit_code'] ?? ''),
                'error' => $e->getMessage(),
            ]);
        }

        return $deposit;
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
        $now = $timeService->nowTs();
        $elapsed = $createdAt ? ($now - $createdAt) : 0;
        $ttlSeconds = $this->depositModel->getPendingTtlSeconds();
        $serverNowMeta = $timeService->normalizeApiTime($now, 'UTC');
        $createdMeta = $timeService->normalizeApiTime($createdAtRaw);

        if (($deposit['status'] ?? '') === 'pending' && $elapsed >= $ttlSeconds) {
            $this->depositModel->markExpired();
            $deposit['status'] = 'expired';
        }

        $method = $this->normalizeDepositMethod((string) ($deposit['method'] ?? ''));

        $responseData = [
            'success' => true,
            'status' => (string) ($deposit['status'] ?? 'pending'),
            'method' => $method,
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

            $depositInfo = [
                'method' => $method,
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

            if ($method === DepositService::METHOD_BINANCE) {
                $siteConfig = Config::getSiteConfig();
                $binanceService = $this->depositService->makeBinanceService($siteConfig);
                $transferNote = $binanceService
                    ? $binanceService->getTransferNote((string) ($deposit['deposit_code'] ?? ''))
                    : '';
                $depositInfo['usdt_amount'] = round((float) ($deposit['usdt_amount'] ?? 0), 8);
                $depositInfo['usd_amount'] = round((float) ($deposit['usdt_amount'] ?? 0), 8);
                $depositInfo['payer_uid'] = (string) ($deposit['payer_uid'] ?? '');
                $depositInfo['binance_uid'] = (string) ($siteConfig['binance_uid'] ?? '');
                $depositInfo['transfer_note'] = $transferNote;

                // Fetch real Binance transaction ID
                if ($binanceService) {
                    $btx = $binanceService->getTransactionByDepositId((int) $deposit['id']);
                    if ($btx) {
                        $depositInfo['transaction_id'] = (string) ($btx['tx_id'] ?? '');
                    }
                }
            } else {
                // Fetch SePay/Bank transaction ID from history table
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT `trans_id` FROM `history_nap_bank` WHERE `username` = ? AND `type` != 'Binance' ORDER BY `id` DESC LIMIT 1");
                $stmt->execute([$user['username']]);
                $bankTransId = $stmt->fetchColumn();
                if ($bankTransId) {
                    $depositInfo['transaction_id'] = (string) $bankTransId;
                }
            }

            $responseData['new_balance'] = $this->getUserBalanceValue((int) ($user['id'] ?? 0));
            $responseData['deposit_info'] = $depositInfo;
        }

        return $responseData;
    }

    private function renderBalancePage(string $routeMethod = 'bank')
    {
        $this->setNoCache();
        $user = $this->requireUser();
        $siteConfig = Config::getSiteConfig();
        $methodCode = $this->routeSegmentToMethodCode($routeMethod);
        $routeSegment = $this->methodCodeToRouteSegment($methodCode);

        $depositPanel = $this->depositService->getProfilePanelData($siteConfig, $user, $methodCode);
        $resolvedMethodCode = strtolower(trim((string) ($depositPanel['active_method'] ?? $methodCode)));
        if (!in_array($resolvedMethodCode, [DepositService::METHOD_BANK_SEPAY, DepositService::METHOD_BINANCE, 'momo'], true)) {
            $resolvedMethodCode = $methodCode;
        }
        $resolvedRouteSegment = $this->methodCodeToRouteSegment($resolvedMethodCode);
        if ($resolvedRouteSegment !== $routeSegment) {
            return $this->redirect(url('balance/' . $resolvedRouteSegment));
        }

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

    public function index()
    {
        $this->requireUser();
        return $this->redirect(url('balance/' . $this->preferredRouteSegment()));
    }

    public function legacyRedirect()
    {
        return $this->redirect(url('balance/' . $this->preferredRouteSegment()));
    }

    public function balance()
    {
        return $this->redirect(url('balance/' . $this->preferredRouteSegment()));
    }

    public function balanceMethod($method)
    {
        $input = strtolower(trim((string) $method));
        $methodCode = $this->routeSegmentToMethodCode($input);
        if (!$this->isMethodAllowedForStorefront($methodCode)) {
            return $this->redirect(url('balance/' . $this->preferredRouteSegment()));
        }
        $canonical = $this->methodCodeToRouteSegment($methodCode);
        if ($input !== $canonical) {
            return $this->redirect(url('balance/' . $canonical));
        }
        return $this->renderBalancePage($canonical);
    }

    public function create()
    {
        $user = $this->requireUser();
        if (!$this->validateCsrfOrSameOrigin()) {
            return $this->json(['success' => false, 'message' => 'Session expired. Please reload and try again.'], 403);
        }

        $siteConfig = Config::getSiteConfig();
        if (((int) ($siteConfig['bank_pay_enabled'] ?? 1) !== 1)) {
            return $this->json(['success' => false, 'message' => 'Phương thức nạp ngân hàng hiện đang bảo trì.']);
        }

        $amount = (int) $this->post('amount', 0);
        $result = $this->depositService->createBankDeposit($user, $amount, $siteConfig, SourceChannelHelper::WEB);

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
            'source_channel' => SourceChannelHelper::WEB,
        ]);

        return $this->json($result);
    }

    public function createBinance()
    {
        $user = $this->requireUser();
        if (!$this->validateCsrfOrSameOrigin()) {
            return $this->json(['success' => false, 'message' => 'Session expired. Please reload and try again.'], 403);
        }

        $siteConfig = Config::getSiteConfig();
        if (((int) ($siteConfig['binance_pay_enabled'] ?? 0) !== 1)) {
            return $this->json(['success' => false, 'message' => 'Binance Pay is unavailable right now.']);
        }

        // Rate limit: max 1 deposit creation per 3 seconds per user
        $db = Database::getInstance()->getConnection();
        $rlStmt = $db->prepare("
            SELECT COUNT(*) FROM `pending_deposits`
            WHERE `user_id` = ? AND `method` = 'binance'
              AND `created_at` >= DATE_SUB(NOW(), INTERVAL 3 SECOND)
        ");
        $rlStmt->execute([(int) ($user['id'] ?? 0)]);
        if ((int) $rlStmt->fetchColumn() > 0) {
            return $this->json(['success' => false, 'message' => 'Please wait a few seconds before creating a new payment.']);
        }

        $amountRaw = trim((string) $this->post('amount', '0'));
        $amount = (float) preg_replace('/[^0-9.]/', '', $amountRaw);
        $payerUid = trim((string) $this->post('payer_uid', ''));
        $result = $this->depositService->createBinanceDeposit($user, $amount, $payerUid, $siteConfig, SourceChannelHelper::WEB);

        if (empty($result['success'])) {
            return $this->json([
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Could not create a Binance Pay payment.'),
            ]);
        }

        $payload = (array) ($result['data'] ?? []);
        Logger::info('Billing', 'deposit_created', "Binance deposit created: {$user['username']}", [
            'amount_usd' => $amount,
            'usdt_amount' => (float) ($payload['usdt_amount'] ?? 0),
            'payer_uid' => $payerUid,
            'bonus' => (int) ($payload['bonus_percent'] ?? 0),
            'deposit_code' => (string) ($payload['deposit_code'] ?? ''),
            'method' => (string) ($payload['method'] ?? DepositService::METHOD_BINANCE),
            'source_channel' => SourceChannelHelper::WEB,
        ]);

        return $this->json($result);
    }

    public function status($code)
    {
        $user = $this->requireUser();

        $deposit = $this->findUserDepositByCode($user, (string) $code);
        if (!$deposit) {
            return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại']);
        }

        $deposit = $this->maybeProcessBinanceDeposit($deposit, $user);
        $responseData = $this->buildDepositStatusResponseData($deposit, $user);
        return $this->json($responseData);
    }

    public function statusWait($code)
    {
        $user = $this->requireUser();
        $depositCode = trim((string) $code);

        $deposit = $this->findUserDepositByCode($user, $depositCode);
        if (!$deposit) {
            return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại'], 404);
        }

        $deposit = $this->maybeProcessBinanceDeposit($deposit, $user);

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
                return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại'], 404);
            }
            $deposit = $this->maybeProcessBinanceDeposit($deposit, $user);
            $payload = $this->buildDepositStatusResponseData($deposit, $user);
        }

        $payload['long_poll'] = [
            'timed_out' => true,
            'waited_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
        return $this->json($payload);
    }

    public function cancel()
    {
        $user = $this->requireUser();
        if (!$this->validateCsrfOrSameOrigin()) {
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

        return $this->json(['success' => true, 'message' => 'Đã hủy giao dịch']);
    }
}

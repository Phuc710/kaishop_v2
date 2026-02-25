<?php

/**
 * Deposit Controller (User-facing)
 * Bank deposit endpoints (UI page moved into Profile)
 */
class DepositController extends Controller
{
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

    /**
     * GET /deposit
     * Legacy route -> redirect to profile deposit section
     */
    public function index()
    {
        $this->requireUser();
        return $this->redirect(url('profile?section=deposit#profile-deposit-card'));
    }

    /**
     * POST /deposit/create
     */
    public function create()
    {
        $user = $this->requireUser();
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
        ];

        if (($deposit['status'] ?? '') === 'completed') {
            global $connection;
            $stmt = $connection->query('SELECT `money` FROM `users` WHERE `id` = ' . (int) $user['id']);
            $row = $stmt ? $stmt->fetch_assoc() : null;
            $responseData['new_balance'] = (int) ($row['money'] ?? 0);
        }

        return $this->json($responseData);
    }

    /**
     * POST /deposit/cancel
     */
    public function cancel()
    {
        $user = $this->requireUser();
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

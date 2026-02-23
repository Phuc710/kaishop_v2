<?php

/**
 * Deposit Controller (User-facing)
 * Handles bank deposit creation, status polling, and cancellation.
 */
class DepositController extends Controller
{
    private $authService;
    private $depositModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->depositModel = new PendingDeposit();
    }

    /**
     * Require user to be logged in.
     * @return array Current user data
     */
    private function requireUser(): array
    {
        $this->authService->requireAuth();
        return $this->authService->getCurrentUser();
    }

    /**
     * GET /deposit — Show deposit page.
     */
    public function index()
    {
        $user = $this->requireUser();
        global $chungapi;

        // Check if user has an active pending deposit
        $activeDeposit = $this->depositModel->getActiveByUser((int) $user['id']);

        // Pass bonus tiers to the view for dynamic button rendering
        $bonusTiers = [
            ['amount' => (int) ($chungapi['bonus_1_amount'] ?? 100000), 'percent' => (int) ($chungapi['bonus_1_percent'] ?? 10)],
            ['amount' => (int) ($chungapi['bonus_2_amount'] ?? 200000), 'percent' => (int) ($chungapi['bonus_2_percent'] ?? 15)],
            ['amount' => (int) ($chungapi['bonus_3_amount'] ?? 500000), 'percent' => (int) ($chungapi['bonus_3_percent'] ?? 20)],
        ];

        $this->view('deposit/index', [
            'chungapi' => $chungapi,
            'user' => $user,
            'activeDeposit' => $activeDeposit,
            'bankName' => $chungapi['bank_name'] ?? 'MB Bank',
            'bankAccount' => $chungapi['bank_account'] ?? '',
            'bankOwner' => $chungapi['bank_owner'] ?? '',
            'bonusTiers' => $bonusTiers,
        ]);
    }

    /**
     * POST /deposit/create — Create a pending deposit (AJAX).
     */
    public function create()
    {
        $user = $this->requireUser();
        global $chungapi;

        $amount = (int) $this->post('amount', 0);

        if ($amount < 10000) {
            return $this->json(['success' => false, 'message' => 'Số tiền nạp tối thiểu 10.000đ']);
        }

        if ($amount > 50000000) {
            return $this->json(['success' => false, 'message' => 'Số tiền nạp tối đa 50.000.000đ']);
        }

        // Calculate bonus based on dynamic tiers from settings
        $bonusPercent = 0;
        $tiers = [
            ['amount' => (int) ($chungapi['bonus_1_amount'] ?? 100000), 'percent' => (int) ($chungapi['bonus_1_percent'] ?? 10)],
            ['amount' => (int) ($chungapi['bonus_2_amount'] ?? 200000), 'percent' => (int) ($chungapi['bonus_2_percent'] ?? 15)],
            ['amount' => (int) ($chungapi['bonus_3_amount'] ?? 500000), 'percent' => (int) ($chungapi['bonus_3_percent'] ?? 20)],
        ];

        // Sort tiers by amount descending so we check highest first
        usort($tiers, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        foreach ($tiers as $tier) {
            if ($tier['amount'] > 0 && $amount >= $tier['amount']) {
                $bonusPercent = $tier['percent'];
                break;
            }
        }

        $result = $this->depositModel->createDeposit(
            (int) $user['id'],
            $user['username'],
            $amount,
            $bonusPercent
        );

        if (!$result) {
            return $this->json(['success' => false, 'message' => 'Không thể tạo giao dịch, vui lòng thử lại']);
        }

        Logger::info('Billing', 'deposit_created', "User {$user['username']} tạo giao dịch nạp tiền", [
            'amount' => $amount,
            'bonus' => $bonusPercent,
            'deposit_code' => $result['deposit_code'],
        ]);

        return $this->json([
            'success' => true,
            'data' => [
                'deposit_code' => $result['deposit_code'],
                'amount' => $amount,
                'bonus_percent' => $bonusPercent,
                'bonus_amount' => (int) ($amount * $bonusPercent / 100),
                'total_receive' => $amount + (int) ($amount * $bonusPercent / 100),
                'expires_at' => $result['expires_at'],
                'bank_name' => $chungapi['bank_name'] ?? 'MB Bank',
                'bank_account' => $chungapi['bank_account'] ?? '',
                'bank_owner' => $chungapi['bank_owner'] ?? '',
            ],
        ]);
    }

    /**
     * GET /deposit/status/{code} — Long polling to check deposit status.
     */
    public function status($code)
    {
        $user = $this->requireUser();

        $deposit = $this->depositModel->findByCode($code);

        if (!$deposit || (int) $deposit['user_id'] !== (int) $user['id']) {
            return $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại']);
        }

        // Check if expired
        $createdAt = strtotime($deposit['created_at']);
        $now = time();
        $elapsed = $now - $createdAt;

        if ($deposit['status'] === 'pending' && $elapsed >= 300) {
            $this->depositModel->markExpired();
            $deposit['status'] = 'expired';
        }

        $responseData = [
            'success' => true,
            'status' => $deposit['status'],
            'remaining' => max(0, 300 - $elapsed),
        ];

        // If completed, include new balance
        if ($deposit['status'] === 'completed') {
            global $connection;
            $stmt = $connection->query("SELECT `money` FROM `users` WHERE `id` = " . (int) $user['id']);
            $row = $stmt->fetch_assoc();
            $responseData['new_balance'] = (int) ($row['money'] ?? 0);
        }

        return $this->json($responseData);
    }

    /**
     * POST /deposit/cancel — Cancel a pending deposit (AJAX).
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

        if ($deposit['status'] !== 'pending') {
            return $this->json(['success' => false, 'message' => 'Giao dịch đã được xử lý']);
        }

        $this->depositModel->cancelByUser((int) $deposit['id'], (int) $user['id']);

        Logger::info('Billing', 'deposit_cancelled', "User {$user['username']} huỷ giao dịch nạp tiền", [
            'deposit_code' => $depositCode,
        ]);

        return $this->json(['success' => true, 'message' => 'Đã huỷ giao dịch']);
    }
}

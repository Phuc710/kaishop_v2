<?php

/**
 * TelegramAccountService
 * Centralized logic for Telegram-Web account linking, unlinking, 
 * shadow account management, and balance transfers.
 */
class TelegramAccountService
{
    private User $userModel;
    private UserTelegramLink $linkModel;
    private BalanceChangeService $balanceService;
    private PDO $db;

    public function __construct()
    {
        $this->userModel = new User();
        $this->linkModel = new UserTelegramLink();
        $this->balanceService = new BalanceChangeService();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ensure a Telegram user has a shadow account.
     * Returns the user_id (either existing or newly created).
     */
    public function ensureShadowAccount(int $telegramId, ?string $telegramUsername = null, ?string $firstName = null): int
    {
        // 1. Check if already linked
        $link = $this->linkModel->findByTelegramId($telegramId);
        if ($link) {
            return (int) $link['user_id'];
        }

        // 2. Check if shadow user record exists by username
        $shadowUsername = 'tg_' . $telegramId;
        $user = $this->userModel->findByUsername($shadowUsername);

        if (!$user) {
            // Create new shadow user
            $uid = $this->userModel->create([
                'username' => $shadowUsername,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'email' => "{$shadowUsername}@telegram.bot",
                'money' => 0,
                'level' => 0,
                'bannd' => 0,
            ]);
            $uid = (int) $uid;
        } else {
            $uid = (int) $user['id'];
        }

        // 3. Link them
        if ($uid > 0) {
            $this->linkModel->linkUser($uid, $telegramId, $telegramUsername, $firstName);
        }

        return $uid;
    }

    /**
     * Unlink a user with a balance choice.
     * 
     * @param int $telegramId
     * @param string $destination 'web' or 'bot'
     * @return array{success:bool, message:string}
     */
    public function unlinkWithChoice(int $telegramId, string $destination): array
    {
        $link = $this->linkModel->findByTelegramId($telegramId);
        if (!$link) {
            return ['success' => false, 'message' => 'Không tìm thấy liên kết cho tài khoản này.'];
        }

        $userId = (int) $link['user_id'];
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Không tìm thấy người dùng.'];
        }

        $balance = (int) ($user['money'] ?? 0);

        try {
            $this->db->beginTransaction();

            if ($destination === 'bot' && $balance > 0) {
                // Determine shadow account
                $shadowUid = $this->ensureShadowAccount($telegramId, $link['telegram_username'], $link['first_name']);

                // If the current link is NOT the shadow account (it's a real web account)
                if ($userId !== $shadowUid) {
                    // Transfer money: User -> Shadow
                    $this->userModel->update($userId, ['money' => 0]);

                    $shadowUser = $this->userModel->findById($shadowUid);
                    $newShadowBalance = (int) ($shadowUser['money'] ?? 0) + $balance;
                    $this->userModel->update($shadowUid, ['money' => $newShadowBalance]);

                    // Log for real user
                    $this->balanceService->record(
                        $userId,
                        $user['username'],
                        $balance,
                        -$balance,
                        0,
                        "Hủy liên kết Telegram: Chuyển tiền sang Bot ({$telegramId})"
                    );

                    // Log for shadow user
                    $this->balanceService->record(
                        $shadowUid,
                        $shadowUser['username'],
                        (int) ($shadowUser['money'] ?? 0),
                        $balance,
                        $newShadowBalance,
                        "Hủy liên kết Telegram: Nhận tiền từ Web ({$user['username']})"
                    );
                }
            }

            if ($destination === 'web') {
                // Perform unlinking (will return to shadow on next interaction)
                $this->linkModel->unlinkByTelegramId($telegramId);
            }
            // If destination is 'bot', they are already linked to shadow by ensureShadowAccount() above.
            // No need to unlink further.

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã hủy liên kết thành công.'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
}

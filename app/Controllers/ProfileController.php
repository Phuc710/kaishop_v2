<?php

/**
 * Profile Controller
 * Handles user profile operations + embedded deposit panel
 */
class ProfileController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private UserValidator $validator;
    private DepositService $depositService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new UserValidator();
        $this->depositService = new DepositService();
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $profileSection = trim((string) $this->get('section', ''));
        $allowedSections = ['profile', 'balance', 'deposit', 'telegram'];
        if (!in_array($profileSection, $allowedSections, true)) {
            $profileSection = 'profile';
        }
        if ($profileSection === 'deposit') {
            $profileSection = 'balance';
        }

        $requestedDepositMethod = trim((string) $this->get('method', ''));
        $depositPanel = $this->depositService->getProfilePanelData($siteConfig, $user, $requestedDepositMethod !== '' ? $requestedDepositMethod : null);

        $depositMethodCode = (string) ($depositPanel['active_method'] ?? DepositService::METHOD_BANK_SEPAY);
        $depositRouteMethod = $depositMethodCode === DepositService::METHOD_BANK_SEPAY ? 'bank' : $depositMethodCode;

        $otpModel = new TelegramLinkCode();
        $activeOtp = $otpModel->getActiveCode($user['id']);

        $this->view('profile/index', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => $profileSection === 'balance'
                ? 'balance'
                : ($profileSection === 'telegram' ? 'telegram' : 'profile'),
            'profileSection' => $profileSection,
            'depositPanel' => $depositPanel,
            'depositRouteMethod' => $depositRouteMethod,
            'activeTgOtp' => $activeOtp,
        ]);
    }

    /**
     * Update profile (AJAX endpoint)
     */
    public function update()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập'
            ], 401);
        }

        $user = $this->authService->getCurrentUser();
        $newEmail = trim((string) $this->post('email', ''));
        $twofaFieldProvided = array_key_exists('twofa_enabled', $_POST);
        $twofaEnabled = $twofaFieldProvided
            ? in_array((string) $this->post('twofa_enabled', '0'), ['1', 'true', 'on'], true)
            : ((int) ($user['twofa_enabled'] ?? 0) === 1);

        $errors = $this->validator->validateEmail($newEmail);
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => $errors[0]
            ], 400);
        }

        if ($this->userModel->emailExists($newEmail, $user['id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email đã được sử dụng bởi tài khoản khác'
            ], 400);
        }

        $success = $this->userModel->update($user['id'], [
            'email' => $newEmail,
            'twofa_enabled' => $twofaEnabled ? 1 : 0,
        ]);

        if ($success) {
            return $this->json([
                'success' => true,
                'message' => 'Cập nhật thông tin bảo mật thành công',
                'twofa_enabled' => $twofaEnabled ? 1 : 0,
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra, vui lòng thử lại'
        ], 500);
    }

    /**
     * Generate Telegram Link OTP (AJAX)
     */
    public function generateTelegramLink()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Bạn chưa đăng nhập'], 401);
        }

        $userId = $this->authService->getUserId();
        $otpModel = new TelegramLinkCode();
        $code = $otpModel->createCode($userId);

        // Fetch the actual expiry time just saved
        $activeOtp = $otpModel->getActiveCode($userId);

        return $this->json([
            'success' => true,
            'message' => 'Đã tạo mã liên kết',
            'code' => $code,
            'bot_username' => get_setting('telegram_bot_user', 'KaiShopBot'),
            'expires_at' => $activeOtp['expires_at'] ?? null,
            'expires_in_minutes' => 5
        ]);
    }

    /**
     * Unlink Telegram account (AJAX)
     */
    public function unlinkTelegram()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Bạn chưa đăng nhập'], 401);
        }

        $userId = $this->authService->getUserId();
        $linkModel = new UserTelegramLink();
        $success = $linkModel->unlinkUser($userId);

        if ($success) {
            return $this->json(['success' => true, 'message' => 'Đã hủy liên kết Telegram']);
        }

        return $this->json(['success' => false, 'message' => 'Không thể hủy liên kết lúc này']);
    }
}

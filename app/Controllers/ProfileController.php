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
        $allowedSections = ['profile', 'balance', 'deposit'];
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

        $this->view('profile/index', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => $profileSection === 'balance' ? 'balance' : 'profile',
            'profileSection' => $profileSection,
            'depositPanel' => $depositPanel,
            'depositRouteMethod' => $depositRouteMethod,
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
}

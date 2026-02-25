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
        $allowedSections = ['profile', 'deposit'];
        if (!in_array($profileSection, $allowedSections, true)) {
            $profileSection = 'profile';
        }

        $depositPanel = $this->depositService->getProfilePanelData($siteConfig, $user);

        $this->view('profile/index', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => $profileSection === 'deposit' ? 'deposit' : 'profile',
            'profileSection' => $profileSection,
            'depositPanel' => $depositPanel,
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
        $newEmail = trim($this->post('email', ''));

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

        $success = $this->userModel->updateEmail($user['id'], $newEmail);
        if ($success) {
            return $this->json([
                'success' => true,
                'message' => 'Cập nhật email thành công'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra, vui lòng thử lại'
        ], 500);
    }
}

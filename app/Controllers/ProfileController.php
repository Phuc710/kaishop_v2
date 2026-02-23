<?php

/**
 * Profile Controller
 * Handles user profile operations
 */
class ProfileController extends Controller
{
    private $userModel;
    private $authService;
    private $validator;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new UserValidator();
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $this->view('profile/index', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => 'profile'
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
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra, vui lòng thử lại'
            ], 500);
        }
    }
}

<?php

/**
 * Password Controller
 * Handles password change operations via MVC
 */
class PasswordController extends Controller
{
    private $authService;
    private $validator;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->validator = new AuthValidator();
    }

    /**
     * Show password change page
     */
    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $this->view('profile/password', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => 'password'
        ]);
    }

    /**
     * Update password (AJAX endpoint)
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
        $username = $user['username'];

        $password1 = trim($this->post('password1', ''));
        $password2 = trim($this->post('password2', ''));
        $password3 = trim($this->post('password3', ''));

        // Validation
        $errors = $this->validator->validateChangePassword($password1, $password2, $password3);
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => $errors[0]
            ], 400);
        }

        // Verify current password
        if ($user['password'] !== sha1(md5($password1))) {
            return $this->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại chưa chính xác'
            ], 400);
        }

        // Update password
        $userModel = new User();
        $newPass = sha1(md5($password2));
        $userModel->update($user['id'], ['password' => $newPass]);

        // Send Telegram notification
        sendTele("$username đã đổi mật khẩu thành công");

        return $this->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại!'
        ]);
    }
}

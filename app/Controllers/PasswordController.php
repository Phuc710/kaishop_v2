<?php

/**
 * Password Controller
 * Handles password change and 2FA security settings
 */
class PasswordController extends Controller
{
    private $authService;
    private $validator;
    private $telegramService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->validator = new AuthValidator();
        $this->telegramService = class_exists('TelegramService') ? new TelegramService() : null;
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
            'activePage' => 'password',
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
                'message' => 'Bạn chưa đăng nhập',
            ], 401);
        }

        $user = $this->authService->getCurrentUser();
        $username = (string) ($user['username'] ?? '');

        $password1 = trim((string) $this->post('password1', ''));
        $password2 = trim((string) $this->post('password2', ''));
        $password3 = trim((string) $this->post('password3', ''));

        $errors = $this->validator->validateChangePassword($password1, $password2, $password3);
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => (string) $errors[0],
            ], 400);
        }

        if ((string) ($user['password'] ?? '') !== sha1(md5($password1))) {
            return $this->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại chưa chính xác',
            ], 400);
        }

        $userModel = new User();
        $newPass = sha1(md5($password2));
        $userModel->update((int) ($user['id'] ?? 0), ['password' => $newPass]);

        $teleMessage = $username . ' da doi mat khau thanh cong';
        if ($this->telegramService instanceof TelegramService) {
            $this->telegramService->send($teleMessage);
        } else {
            $service = telegram_service();
            if ($service) {
                $service->send($teleMessage);
            }
        }


        return $this->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại!',
        ]);
    }

    /**
     * Update OTP Gmail 2FA setting (AJAX endpoint)
     */
    public function updateSecurity()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập',
            ], 401);
        }

        $user = $this->authService->getCurrentUser();
        $enabled = in_array((string) $this->post('twofa_enabled', '0'), ['1', 'true', 'on'], true);

        $userModel = new User();
        $ok = $userModel->updateTwoFactorSetting((int) ($user['id'] ?? 0), $enabled);

        if (!$ok) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật cài đặt bảo mật',
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => $enabled ? 'Đã bật OTP Gmail khi đăng nhập' : 'Đã tắt OTP Gmail khi đăng nhập',
            'twofa_enabled' => $enabled ? 1 : 0,
        ]);
    }
}

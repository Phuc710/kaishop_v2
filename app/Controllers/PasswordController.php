<?php

/**
 * Password Controller
 * Handles password change and login 2FA (OTP Mail) setting.
 */
class PasswordController extends Controller
{
    private AuthService $authService;
    private AuthValidator $validator;
    private User $userModel;
    private ?AuthSecurityService $authSecurity = null;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->validator = new AuthValidator();
        $this->userModel = new User();
    }

    private function security(): AuthSecurityService
    {
        if ($this->authSecurity === null) {
            $this->authSecurity = new AuthSecurityService();
        }
        return $this->authSecurity;
    }

    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $this->view('profile/password', [
            'user' => $user,
            'username' => (string) ($user['username'] ?? ''),
            'chungapi' => $siteConfig,
            'activePage' => 'password',
        ]);
    }

    public function update()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $username = (string) ($user['username'] ?? '');
        $security = $this->security();

        $rateLimit = $security->checkRateLimit('change_password', $username);
        if ($rateLimit !== null) {
            return $this->json(['success' => false, 'message' => (string) ($rateLimit['message'] ?? 'Bạn thao tác quá nhanh.')], 429);
        }

        $currentPassword = trim((string) $this->post('password1', ''));
        $newPassword = trim((string) $this->post('password2', ''));
        $confirmPassword = trim((string) $this->post('password3', ''));

        $errors = $this->validator->validateChangePassword($currentPassword, $newPassword, $confirmPassword);
        if (!empty($errors)) {
            $security->recordLoginAttempt('change_password', $username, false, 'validation_failed');
            return $this->json(['success' => false, 'message' => (string) $errors[0]], 400);
        }

        if (!$security->verifyPassword($user, $currentPassword)) {
            $security->recordLoginAttempt('change_password', $username, false, 'wrong_current_password');
            return $this->json(['success' => false, 'message' => 'Mật khẩu hiện tại chưa chính xác.'], 400);
        }

        $updated = $this->userModel->update((int) ($user['id'] ?? 0), [
            'password' => $security->hashPassword($newPassword),
            'password_updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            $security->recordLoginAttempt('change_password', $username, false, 'update_failed');
            return $this->json(['success' => false, 'message' => 'Không thể cập nhật mật khẩu. Vui lòng thử lại.'], 500);
        }

        $security->recordLoginAttempt('change_password', $username, true, 'password_changed');

        try {
            if (function_exists('sendTele')) {
                sendTele($username . ' đã đổi mật khẩu thành công');
            }
        } catch (Throwable $e) {
            // non-blocking
        }

        return $this->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại!',
        ]);
    }

    public function updateSecurity()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $username = (string) ($user['username'] ?? '');
        $security = $this->security();

        $rateLimit = $security->checkRateLimit('password_security_toggle', $username);
        if ($rateLimit !== null) {
            return $this->json(['success' => false, 'message' => (string) ($rateLimit['message'] ?? 'Bạn thao tác quá nhanh.')], 429);
        }

        $enabled = in_array((string) $this->post('twofa_enabled', '0'), ['1', 'true', 'on'], true);

        $updated = $this->userModel->update((int) ($user['id'] ?? 0), [
            'twofa_enabled' => $enabled ? 1 : 0,
        ]);

        if (!$updated) {
            $security->recordLoginAttempt('password_security_toggle', $username, false, 'update_failed');
            return $this->json(['success' => false, 'message' => 'Không thể cập nhật cài đặt bảo mật.'], 500);
        }

        $security->recordLoginAttempt('password_security_toggle', $username, true, $enabled ? 'twofa_enabled' : 'twofa_disabled');

        return $this->json([
            'success' => true,
            'message' => $enabled
                ? 'Đã bật xác minh đăng nhập bằng OTP Mail.'
                : 'Đã tắt xác minh đăng nhập bằng OTP Mail.',
            'twofa_enabled' => $enabled ? 1 : 0,
        ]);
    }
}

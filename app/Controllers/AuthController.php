<?php

/**
 * Auth Controller
 * Handles authentication (login, register, logout, password reset)
 */
class AuthController extends Controller
{
    private $userModel;
    private $authService;
    private $validator;
    private $fingerprintModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new AuthValidator();
        $this->fingerprintModel = new UserFingerprint();
    }

    /**
     * Show login page
     */
    public function showLogin()
    {
        // Already logged in? Redirect to home
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        global $chungapi;
        $this->view('auth/login', ['chungapi' => $chungapi]);
    }

    /**
     * Process login (AJAX)
     */
    public function login()
    {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));

        // Validate
        $errors = $this->validator->validateLogin($username, $password);
        if (!empty($errors)) {
            return $this->json(['success' => false, 'message' => $errors[0]], 400);
        }

        // Check credentials
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
        }

        // Verify password (using sha1(md5()) as in old code)
        if ($user['password'] !== sha1(md5($password))) {
            return $this->json(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
        }

        // Generate new session
        $sessionToken = $this->generateSessionToken();
        $fingerprintHash = trim($this->post('fingerprint', ''));

        $updateData = [
            'session' => $sessionToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'last_login' => date('Y-m-d H:i:s')
        ];
        if ($fingerprintHash !== '') {
            $updateData['fingerprint'] = $fingerprintHash;
        }
        $this->userModel->update($user['id'], $updateData);

        // Save fingerprint history
        if ($fingerprintHash !== '') {
            try {
                $fpComponents = $this->post('fp_components', '');
                $this->fingerprintModel->saveFingerprint(
                    $user['id'],
                    $user['username'],
                    $fingerprintHash,
                    $fpComponents
                );
            } catch (Exception $e) {
                // Silent fail — don't block login
            }
        }

        // Set session
        $_SESSION['session'] = $sessionToken;

        Logger::info('Auth', 'login_success', 'Đăng nhập thành công', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'fingerprint' => $fingerprintHash ?: 'none'
        ]);

        return $this->json(['success' => true, 'message' => 'Đăng nhập thành công.']);
    }

    /**
     * Show register page
     */
    public function showRegister()
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        global $chungapi;
        $this->view('auth/register', ['chungapi' => $chungapi]);
    }

    /**
     * Process registration (AJAX)
     */
    public function register()
    {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        $email = trim($this->post('email', ''));

        // Validate
        $errors = $this->validator->validateRegister($username, $password, $email);
        if (!empty($errors)) {
            return $this->json(['success' => false, 'message' => $errors[0]], 400);
        }

        // Check if username exists
        if ($this->userModel->findByUsername($username)) {
            return $this->json(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại.'], 400);
        }

        // Check if email exists
        if ($this->userModel->emailExists($email)) {
            return $this->json(['success' => false, 'message' => 'Email đã được sử dụng.'], 400);
        }

        // Create user
        global $ip_address;
        $hashedPassword = sha1(md5($password));
        $apiKey = md5(bin2hex(random_bytes(16)));
        $time = date('h:i d-m-Y');
        $randomId = $this->generateUniqueUserId();

        $userId = $this->userModel->create([
            'id' => $randomId,
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'level' => ($this->userModel->count() == 0) ? '9' : '0',
            'tong_nap' => '0',
            'money' => '0',
            'bannd' => '0',
            'ip' => $ip_address,
            'api_key' => $apiKey,
            'time' => $time
        ]);

        if ($userId) {
            // Auto login after register
            $sessionToken = $this->generateSessionToken();
            $fingerprintHash = trim($this->post('fingerprint', ''));

            $updateData = [
                'session' => $sessionToken,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'last_login' => date('Y-m-d H:i:s'),
            ];
            if ($fingerprintHash !== '') {
                $updateData['fingerprint'] = $fingerprintHash;
            }
            $this->userModel->update($userId, $updateData);
            $_SESSION['session'] = $sessionToken;

            // Save fingerprint history
            if ($fingerprintHash !== '') {
                try {
                    $fpComponents = $this->post('fp_components', '');
                    $this->fingerprintModel->saveFingerprint(
                        $userId,
                        $username,
                        $fingerprintHash,
                        $fpComponents
                    );
                } catch (Exception $e) {
                    // Silent fail
                }
            }

            Logger::info('Auth', 'register_success', 'Đăng ký tài khoản thành công', [
                'username' => $username,
                'email' => $email,
                'fingerprint' => $fingerprintHash ?: 'none'
            ]);

            return $this->json(['success' => true, 'message' => 'Đăng ký thành công.']);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.'], 500);
    }

    /**
     * Logout
     */
    public function logout()
    {
        session_destroy();
        $this->redirect(BASE_URL . '/login');
    }

    /**
     * Show forgot password page
     */
    public function showForgotPassword()
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        global $chungapi;
        $this->view('auth/forgot_password', ['chungapi' => $chungapi]);
    }

    /**
     * Process forgot password request (AJAX)
     */
    public function processForgotPassword()
    {
        $username = trim($this->post('username', ''));

        if (empty($username)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }

        $user = $this->userModel->findByUsernameOrEmail($username);

        if ($user) {
            $otpcode = bin2hex(random_bytes(16)); // Mã reset

            $guitoi = $user['email'];
            $subject = 'Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản ' . $user['username'] . '.';
            $bcc = 'Đặt Lại Mật Khẩu';
            $hoten = 'Hỗ trợ hệ thống';

            $serverName = $_SERVER['SERVER_NAME'];
            $noi_dung = '
            <p>Kính chào quý khách hàng <b>' . $user['username'] . '</b>,</p>
            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu của bạn. Nếu bạn là người thực hiện yêu cầu này, hãy nhấp vào liên kết bên dưới để đặt lại mật khẩu.</p>
            <p><b>Lưu ý:</b> Nếu bạn không thực hiện yêu cầu này, vui lòng không nhấp vào liên kết và bỏ qua email này.</p>
            <p>🔗 <a href="https://' . $serverName . '/password-reset/' . $otpcode . '" target="_blank"><b>ĐẶT LẠI MẬT KHẨU</b></a></p>
            <p>Website: <a href="https://' . $serverName . '/" target="_blank"><b>' . $serverName . '</b></a></p>
            <p>Trân trọng,<br>Hỗ trợ khách hàng</p>';

            require_once __DIR__ . '/../../hethong/config.php';
            $send_status = sendCSM($guitoi, $hoten, $subject, $noi_dung, $bcc);

            if ($send_status) {
                $this->userModel->update($user['id'], ['otpcode' => $otpcode]);
                Logger::warning('Auth', 'forgot_password_request', 'Yêu cầu quên mật khẩu', ['username' => $user['username'], 'email' => $guitoi]);
                return $this->json(['success' => true, 'message' => 'Email đặt lại mật khẩu đã được gửi']);
            } else {
                return $this->json(['success' => false, 'message' => 'Không thể gửi email, vui lòng thử lại']);
            }
        }

        return $this->json(['success' => false, 'message' => 'Tài khoản không tồn tại'], 404);
    }

    /**
     * Show reset password page
     * @param string $id OTP Code
     */
    public function showResetPassword($id)
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        global $chungapi;
        $this->view('auth/reset_password', ['chungapi' => $chungapi, 'otpcode' => $id]);
    }

    /**
     * Process reset password (AJAX)
     * @param string $id OTP Code
     */
    public function processResetPassword($id)
    {
        $password = trim($this->post('password', ''));
        $otpcode = $id;

        if (empty($otpcode) || empty($password)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        }

        $user = $this->userModel->findByOtpcode($otpcode);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Mã khôi phục không hợp lệ hoặc đã hết hạn (OTP Code không đúng)']);
        }

        $new_pass = sha1(md5($password));
        $this->userModel->update($user['id'], [
            'password' => $new_pass,
            'otpcode' => ''
        ]);

        Logger::info('Auth', 'reset_password_success', 'Đặt lại mật khẩu thành công', ['username' => $user['username']]);

        return $this->json(['success' => true, 'message' => 'Mật khẩu đã được cập nhật thành công']);
    }

    /**
     * Update fingerprint silently (AJAX)
     */
    public function updateFingerprint()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false]);
        }

        $user = $this->authService->getCurrentUser();
        $fpHash = trim($this->post('fingerprint', ''));

        if ($fpHash !== '') {
            $this->userModel->update($user['id'], ['fingerprint' => $fpHash]);

            // Log history if we haven't seen this hash lately (to prevent spam)
            $latest = $this->fingerprintModel->getByUserId($user['id'], 1);
            if (empty($latest) || $latest[0]['fingerprint_hash'] !== $fpHash) {
                $fpComponents = $this->post('fp_components', '');
                try {
                    $this->fingerprintModel->saveFingerprint($user['id'], $user['username'], $fpHash, $fpComponents);
                } catch (Exception $e) {
                }
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * Generate random session token
     */
    private function generateSessionToken()
    {
        $characters = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZCVBNM';
        $token = '';
        for ($i = 0; $i < 32; $i++) {
            $token .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $token;
    }

    /**
     * Generate a unique random 6-digit User ID (100000 - 999999)
     */
    private function generateUniqueUserId(): int
    {
        $maxAttempts = 50;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $id = random_int(100000, 999999);
            $existing = $this->userModel->findById($id);
            if (!$existing) {
                return $id;
            }
        }
        // Fallback: use timestamp-based if all random attempts collide
        return (int) substr((string) time(), -6);
    }
}

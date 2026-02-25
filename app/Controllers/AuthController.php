<?php

/**
 * Auth Controller
 * Handles authentication (login, register, logout, password reset)
 */
class AuthController extends Controller
{
    private const RESET_PASSWORD_TOKEN_TTL_SECONDS = 900; // 15 minutes
    private const LOGIN_OTP_TTL_SECONDS = 300; // 5 minutes
    private const LOGIN_OTP_RESEND_COOLDOWN_SECONDS = 60;
    private $userModel;
    private $authService;
    private $authSecurity;
    private $validator;
    private $fingerprintModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authService = new AuthService();
        // Avoid schema checks / extra DB work on GET pages (login/register/forgot views)
        $this->authSecurity = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET')
            ? null
            : new AuthSecurityService();
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

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/login', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig]);
    }

    /**
     * Show dedicated OTP page for login 2FA
     */
    public function showLoginOtp()
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        $challengeId = trim((string) $this->get('challenge_id', ''));
        if ($challengeId === '') {
            $this->redirect(BASE_URL . '/login');
        }

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/login_otp', [
            'chungapi' => $siteConfig,
            'siteConfig' => $siteConfig,
            'challengeId' => $challengeId,
        ]);
    }

    /**
     * Process login (AJAX)
     */
    public function login()
    {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        $rememberMe = in_array((string) $this->post('remember', '0'), ['1', 'true', 'on'], true);
        $fingerprintHash = trim($this->post('fingerprint', ''));
        $fpComponents = (string) $this->post('fp_components', '');

        if (($limit = $this->authSecurity->checkRateLimit('login', $username)) !== null) {
            return $this->json(['success' => false, 'message' => $limit['message']], 429);
        }

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            $this->authSecurity->recordLoginAttempt('login', $username, false, 'turnstile_failed');
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

        // Validate
        $errors = $this->validator->validateLogin($username, $password);
        if (!empty($errors)) {
            $this->authSecurity->recordLoginAttempt('login', $username, false, 'validation_failed');
            return $this->json(['success' => false, 'message' => $errors[0]], 400);
        }

        // Check credentials
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            $this->authSecurity->recordLoginAttempt('login', $username, false, 'user_not_found');
            return $this->json(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
        }

        if (!$this->authSecurity->verifyPassword($user, $password)) {
            $this->authSecurity->recordLoginAttempt('login', $username, false, 'wrong_password');
            return $this->json(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
        }

        if ($this->authSecurity->needsPasswordRehash($user)) {
            $this->userModel->update($user['id'], [
                'password' => $this->authSecurity->hashPassword($password),
                'password_updated_at' => date('Y-m-d H:i:s')
            ]);
            $user = $this->userModel->findById((int) $user['id']) ?: $user;
        }

        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        if ($this->authSecurity->shouldRequireTwoFactor($user, $device)) {
            try {
                $challenge = $this->authSecurity->createOtpChallenge($user, 'login_2fa', $device, [
                    'remember_me' => $rememberMe ? 1 : 0,
                    'fingerprint' => $fingerprintHash,
                    'fp_components' => $fpComponents,
                ]);
            } catch (Throwable $e) {
                $this->authSecurity->recordLoginAttempt('login', $username, false, 'otp_send_failed');
                Logger::danger('Auth', 'login_otp_send_failed', 'Không thể gửi OTP đăng nhập', [
                    'username' => $username,
                    'error' => $e->getMessage(),
                ]);
                return $this->json(['success' => false, 'message' => 'Không thể gửi OTP đăng nhập. Vui lòng kiểm tra cấu hình mail hoặc thử lại sau.'], 500);
            }

            $this->authSecurity->recordLoginAttempt('login', $username, true, 'otp_sent');
            return $this->json([
                'success' => true,
                'requires_2fa' => true,
                'challenge_id' => $challenge['challenge_id'],
                'message' => 'Đã gửi mã OTP đến email của bạn. Vui lòng nhập mã để hoàn tất đăng nhập.'
            ]);
        }

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe, 'login');
        $this->authSecurity->recordLoginAttempt('login', $username, true, 'login_success');

        Logger::info('Auth', 'login_success', 'Đăng nhập thành công', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'fingerprint' => $fingerprintHash ?: 'none'
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'access_expires_in' => 900,
            'refresh_expires_in' => $rememberMe ? 1209600 : 86400
        ]);
    }

    /**
     * Show register page
     */
    public function showRegister()
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/register', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig]);
    }

    /**
     * Process registration (AJAX)
     */
    public function register()
    {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        $email = trim($this->post('email', ''));

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

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
        $hashedPassword = $this->authSecurity->hashPassword($password);
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
            $fingerprintHash = trim($this->post('fingerprint', ''));
            $newUser = $this->userModel->findById((int) $userId);
            if ($newUser) {
                $this->completeAuthenticatedSession($newUser, $fingerprintHash, $this->post('fp_components', ''), false, 'register');

                // Gửi email chào mừng
                try {
                    if (!class_exists('MailService')) {
                        require_once __DIR__ . '/../Services/MailService.php';
                    }
                    (new MailService())->sendWelcomeRegister($newUser);
                } catch (Throwable $e) {
                    // Non-blocking — không chặn đăng ký nếu mail lỗi
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
        $this->authService->logout();
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

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/forgot_password', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig]);
    }

    /**
     * Process forgot password request (AJAX)
     */
    public function processForgotPassword()
    {
        $username = trim($this->post('username', ''));
        $challengeId = trim((string) $this->post('challenge_id', ''));
        $otpCode = trim((string) $this->post('otp_code', ''));

        if (($limit = $this->authSecurity->checkRateLimit('forgot_password', $username)) !== null) {
            return $this->json(['success' => false, 'message' => $limit['message']], 429);
        }

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'turnstile_failed');
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

        if (empty($username)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
        }

        $user = $this->userModel->findByUsernameOrEmail($username);

        if ($user) {
            if (trim((string) ($user['email'] ?? '')) === '') {
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'missing_email');
                return $this->json([
                    'success' => false,
                    'message' => 'Tài khoản này chưa có email khôi phục. Nếu bạn đăng nhập bằng Google, hãy dùng đúng Gmail đã liên kết hoặc liên hệ hỗ trợ.'
                ], 400);
            }

            $device = $this->authSecurity->getDeviceContext(trim((string) $this->post('fingerprint', '')));

            $otpcode = bin2hex(random_bytes(16));
            $otpcodeExpiresAt = date('Y-m-d H:i:s', time() + self::RESET_PASSWORD_TOKEN_TTL_SECONDS);
            $send_status = $this->authSecurity->sendPasswordResetMail($user, $otpcode);

            if ($send_status) {
                $this->userModel->update($user['id'], [
                    'otpcode' => $otpcode,
                    'otpcode_expires_at' => $otpcodeExpiresAt,
                ]);
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, true, 'mail_sent');
                Logger::warning('Auth', 'forgot_password_request', 'Yêu cầu quên mật khẩu', ['username' => $user['username'], 'email' => ($user['email'] ?? '')]);
                return $this->json(['success' => true, 'message' => 'Email đặt lại mật khẩu đã được gửi (hiệu lực 15 phút)']);
            } else {
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'mail_send_failed');
                return $this->json(['success' => false, 'message' => 'Không thể gửi email, vui lòng thử lại']);
            }
        }

        $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'user_not_found');
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

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/reset_password', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig, 'otpcode' => $id]);
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

        if (strlen($password) < 6) {
            return $this->json(['success' => false, 'message' => 'Mật khẩu phải từ 6']);
        }

        $user = $this->userModel->findByOtpcode($otpcode);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Mã khôi phục không hợp lệ hoặc đã hết hạn (OTP Code không đúng)']);
        }

        $expiresAt = trim((string) ($user['otpcode_expires_at'] ?? ''));
        if ($expiresAt === '' || strtotime($expiresAt) === false || strtotime($expiresAt) < time()) {
            $this->userModel->update($user['id'], ['otpcode' => '', 'otpcode_expires_at' => null]);
            return $this->json(['success' => false, 'message' => 'Mã khôi phục đã hết hạn. Vui lòng gửi lại yêu cầu (hiệu lực 15 phút).']);
        }

        $new_pass = $this->authSecurity->hashPassword($password);
        $this->userModel->update($user['id'], [
            'password' => $new_pass,
            'password_updated_at' => date('Y-m-d H:i:s'),
            'otpcode' => '',
            'otpcode_expires_at' => null,
            'session' => ''
        ]);

        try {
            $this->authSecurity->revokeAllUserSessions((int) $user['id'], 'password_reset');
            $this->authSecurity->clearAuthCookies();
        } catch (Throwable $e) {
            // Non-blocking: password has been updated already.
        }

        Logger::info('Auth', 'reset_password_success', 'Đặt lại mật khẩu thành công', ['username' => $user['username']]);

        return $this->json([
            'success' => true,
            'message' => 'Mật khẩu đã được cập nhật thành công. Bạn có thể đăng nhập bằng mật khẩu mới (hoặc tiếp tục dùng Google).'
        ]);
    }

    /**
     * Verify OTP for login 2FA and complete login
     */
    public function verifyLoginOtp()
    {
        $challengeId = trim((string) $this->post('challenge_id', ''));
        $otpCode = trim((string) $this->post('otp_code', ''));

        if ($challengeId === '' || $otpCode === '') {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập mã OTP.'], 400);
        }

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

        $verified = $this->authSecurity->verifyOtpChallenge($challengeId, $otpCode, 'login_2fa');
        if (!$verified['ok']) {
            return $this->json(['success' => false, 'message' => $verified['message']], 400);
        }

        $row = $verified['row'];
        $user = $this->userModel->findById((int) $row['user_id']);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Tài khoản không tồn tại.'], 404);
        }

        $meta = $verified['meta'];
        $fingerprintHash = (string) ($meta['fingerprint'] ?? '');
        $fpComponents = (string) ($meta['fp_components'] ?? '');
        $rememberMe = !empty($meta['remember_me']);
        $currentDevice = $this->authSecurity->getDeviceContext($fingerprintHash);
        if (!empty($row['device_hash']) && !hash_equals((string) $row['device_hash'], (string) $currentDevice['device_hash'])) {
            return $this->json(['success' => false, 'message' => 'Thiết bị xác minh không khớp. Vui lòng đăng nhập lại.'], 400);
        }

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe, 'login_2fa');
        $this->authSecurity->recordLoginAttempt('login', (string) ($user['username'] ?? ''), true, '2fa_verified');

        return $this->json([
            'success' => true,
            'message' => 'Xác minh OTP thành công. Đăng nhập thành công.',
            'redirect' => BASE_URL . '/',
            'access_expires_in' => 900,
            'refresh_expires_in' => $rememberMe ? 1209600 : 86400
        ]);
    }

    /**
     * Resend login OTP (AJAX) with anti-spam throttling.
     */
    public function resendLoginOtp()
    {
        $challengeId = trim((string) $this->post('challenge_id', ''));
        if ($challengeId === '') {
            return $this->json(['success' => false, 'message' => 'Thiếu phiên xác minh OTP. Vui lòng đăng nhập lại.'], 400);
        }

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

        $fingerprintHash = trim((string) $this->post('fingerprint', ''));
        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        $result = $this->authSecurity->resendLoginOtpChallenge(
            $challengeId,
            $device,
            self::LOGIN_OTP_TTL_SECONDS,
            self::LOGIN_OTP_RESEND_COOLDOWN_SECONDS
        );

        if (empty($result['ok'])) {
            $status = (int) ($result['status'] ?? 400);
            $payload = [
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Không thể gửi lại OTP.'),
            ];
            if (isset($result['retry_after_seconds'])) {
                $payload['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            return $this->json($payload, $status);
        }

        return $this->json([
            'success' => true,
            'message' => (string) ($result['message'] ?? 'Đã gửi lại OTP.'),
            'challenge_id' => (string) ($result['challenge_id'] ?? ''),
            'expires_in' => (int) ($result['expires_in'] ?? self::LOGIN_OTP_TTL_SECONDS),
            'cooldown_seconds' => (int) ($result['cooldown_seconds'] ?? self::LOGIN_OTP_RESEND_COOLDOWN_SECONDS),
        ]);
    }

    /**
     * Optional separate endpoint for forgot-password 2FA OTP verify (reuses processForgotPassword flow)
     */
    public function verifyForgotPasswordOtp()
    {
        return $this->processForgotPassword();
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
                    $this->fingerprintModel->saveFingerprint(
                        $user['id'],
                        $user['username'],
                        $fpHash,
                        $fpComponents,
                        ['action' => 'fingerprint_heartbeat']
                    );
                } catch (Exception $e) {
                }
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * Google login/register via Firebase ID token (AJAX)
     */
    public function googleLogin()
    {
        $rememberMe = in_array((string) $this->post('remember', '0'), ['1', 'true', 'on'], true);

        if (($limit = $this->authSecurity->checkRateLimit('login_google', (string) $this->post('email_hint', 'google'))) !== null) {
            return $this->json(['success' => false, 'message' => $limit['message']], 429);
        }

        if (($turnstileError = $this->requireTurnstileToken()) !== null) {
            return $this->json(['success' => false, 'message' => $turnstileError], 400);
        }

        $idToken = trim((string) $this->post('id_token', ''));
        if ($idToken === '') {
            return $this->json(['success' => false, 'message' => 'Thiếu ID token Google.'], 400);
        }

        $googleUser = $this->verifyFirebaseGoogleIdToken($idToken);
        if (!$googleUser || empty($googleUser['email'])) {
            return $this->json(['success' => false, 'message' => 'Không xác minh được tài khoản Google.'], 401);
        }

        $email = strtolower(trim((string) $googleUser['email']));
        $displayName = trim((string) ($googleUser['displayName'] ?? ''));
        $googleAvatarUrl = $this->sanitizeRemoteImageUrl((string) ($googleUser['photoUrl'] ?? ($googleUser['photoURL'] ?? '')));
        $fingerprintHash = trim($this->post('fingerprint', ''));
        $fpComponents = $this->post('fp_components', '');

        $user = $this->userModel->findByEmail($email);
        $isNewGoogleUser = false;

        if (!$user) {
            $isNewGoogleUser = true;
            global $ip_address;
            $baseSeed = $this->extractPreferredGoogleUsernameSeed($displayName, $email);
            $username = $this->generateUniqueUsernameFromSeed((string) $baseSeed);
            $randomId = $this->generateUniqueUserId();
            $apiKey = md5(bin2hex(random_bytes(16)));
            $time = date('h:i d-m-Y');
            $newUserData = [
                'id' => $randomId,
                'username' => $username,
                'password' => $this->authSecurity->hashPassword(bin2hex(random_bytes(16))),
                'password_updated_at' => date('Y-m-d H:i:s'),
                'email' => $email,
                'level' => ($this->userModel->count() == 0) ? '9' : '0',
                'tong_nap' => '0',
                'money' => '0',
                'bannd' => '0',
                'ip' => $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                'api_key' => $apiKey,
                'time' => $time
            ];
            if ($googleAvatarUrl !== '' && $this->userColumnExists('avatar_url')) {
                $newUserData['avatar_url'] = $googleAvatarUrl;
            }

            $newId = $this->userModel->create($newUserData);

            $user = $this->userModel->findById((int) $newId);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'Không thể tạo tài khoản từ Google.'], 500);
            }

            // Gửi email chào mừng Google
            try {
                if (!class_exists('MailService')) {
                    require_once __DIR__ . '/../Services/MailService.php';
                }
                (new MailService())->sendWelcomeRegister($user);
            } catch (Throwable $e) {
                // Non-blocking
            }

            Logger::info('Auth', 'register_google_success', 'Đăng ký bằng Google thành công', [
                'username' => $user['username'],
                'email' => $email
            ]);
        }

        // Keep avatar fresh on each Google login (safe: only if column exists)
        if (!empty($user['id']) && $googleAvatarUrl !== '' && $this->userColumnExists('avatar_url')) {
            $currentAvatar = trim((string) ($user['avatar_url'] ?? ''));
            if ($currentAvatar !== $googleAvatarUrl) {
                try {
                    $this->userModel->update((int) $user['id'], ['avatar_url' => $googleAvatarUrl]);
                    $user['avatar_url'] = $googleAvatarUrl;
                } catch (Throwable $e) {
                    // Non-blocking: avatar sync failure must not break login
                }
            }
        }

        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        if (!$isNewGoogleUser && $this->authSecurity->shouldRequireTwoFactor($user, $device)) {
            try {
                $challenge = $this->authSecurity->createOtpChallenge($user, 'login_2fa', $device, [
                    'remember_me' => $rememberMe ? 1 : 0,
                    'fingerprint' => $fingerprintHash,
                    'fp_components' => $fpComponents,
                    'source' => 'google'
                ]);
            } catch (Throwable $e) {
                Logger::danger('Auth', 'login_google_otp_send_failed', 'Không thể gửi OTP đăng nhập Google', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                return $this->json(['success' => false, 'message' => 'Không thể gửi OTP đăng nhập Google. Vui lòng kiểm tra cấu hình mail hoặc thử lại sau.'], 500);
            }

            return $this->json([
                'success' => true,
                'requires_2fa' => true,
                'challenge_id' => $challenge['challenge_id'],
                'message' => 'Đã gửi mã OTP đến email của bạn. Vui lòng nhập mã để hoàn tất đăng nhập Google.'
            ]);
        }

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe, 'login_google');

        Logger::info('Auth', 'login_google_success', 'Đăng nhập bằng Google thành công', [
            'username' => $user['username'],
            'email' => $user['email'] ?? $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Đăng nhập Google thành công.',
            'redirect' => BASE_URL . '/',
            'access_expires_in' => 900,
            'refresh_expires_in' => $rememberMe ? 1209600 : 86400
        ]);
    }

    /**
     * Turnstile verify (bypass on localhost/dev)
     */
    private function requireTurnstileToken(): ?string
    {
        if ($this->isLocalhostRequest()) {
            return null;
        }

        $secretKey = trim((string) EnvHelper::get('TURNSTILE_SECRET_KEY', ''));
        $siteKey = trim((string) EnvHelper::get('TURNSTILE_SITE_KEY', ''));

        // If not configured, skip silently to avoid breaking auth on servers not yet configured.
        if ($secretKey === '' || $siteKey === '') {
            return null;
        }

        $token = trim((string) $this->post('turnstile_token', ''));
        if ($token === '') {
            return 'Vui lòng xác minh bạn là người thật.';
        }

        $verify = $this->postForm('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        if (empty($verify['success'])) {
            return 'Xác minh Cloudflare Turnstile thất bại. Vui lòng thử lại.';
        }

        return null;
    }

    private function isLocalhostRequest(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if (
            $host === 'localhost' || strpos($host, 'localhost:') === 0 ||
            $host === '127.0.0.1' || strpos($host, '127.0.0.1:') === 0 ||
            $host === '[::1]' || strpos($host, '[::1]:') === 0
        ) {
            return true;
        }

        return in_array($ip, ['127.0.0.1', '::1'], true);
    }

    /**
     * Verify Firebase ID token via Identity Toolkit accounts:lookup
     */
    private function verifyFirebaseGoogleIdToken(string $idToken): ?array
    {
        $apiKey = trim((string) EnvHelper::get('FIREBASE_API_KEY', ''));
        if ($apiKey === '') {
            return null;
        }

        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode($apiKey);
        $response = $this->postJson($url, ['idToken' => $idToken]);
        if (empty($response['users'][0]) || !is_array($response['users'][0])) {
            return null;
        }

        return $response['users'][0];
    }

    private function completeAuthenticatedSession(array $user, string $fingerprintHash = '', string $fpComponents = '', bool $rememberMe = false, string $fingerprintAction = 'auth_login'): void
    {
        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        $this->authSecurity->issueLoginTokens($user, $device, $rememberMe);

        if ($fingerprintHash !== '') {
            $this->userModel->update($user['id'], ['fingerprint' => $fingerprintHash]);
        }

        if ($fingerprintHash === '') {
            return;
        }

        try {
            $this->fingerprintModel->saveFingerprint(
                $user['id'],
                $user['username'] ?? '',
                $fingerprintHash,
                $fpComponents,
                ['action' => $fingerprintAction]
            );
        } catch (Exception $e) {
            // Ignore fingerprint logging errors
        }
    }

    private function generateUniqueUsernameFromSeed(string $seed): string
    {
        $seed = trim($seed);
        if (function_exists('xoadau')) {
            $seed = (string) xoadau($seed);
        } elseif (class_exists('FormatHelper') && method_exists('FormatHelper', 'toSlug')) {
            $seed = (string) FormatHelper::toSlug($seed);
        }

        $seed = strtolower((string) $seed);
        $seed = str_replace('-', '', $seed);
        $seed = preg_replace('/[^a-z0-9]+/i', '', $seed);
        if ($seed === null || $seed === '') {
            $seed = 'user';
        }

        $base = substr($seed, 0, 20);
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $i = 0;
        while ($this->userModel->findByUsername($candidate)) {
            $i++;
            $suffix = (string) random_int(100, 999);
            $candidate = substr($base, 0, max(3, 20 - strlen($suffix))) . $suffix;
            if ($i > 30) {
                $candidate = 'user' . random_int(100000, 999999);
                if (!$this->userModel->findByUsername($candidate)) {
                    break;
                }
            }
        }

        return $candidate;
    }

    private function extractPreferredGoogleUsernameSeed(string $displayName, string $email): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $displayName) ?? '');
        if ($name !== '') {
            $parts = preg_split('/\s+/u', $name) ?: [];
            $last = trim((string) end($parts));
            if ($last !== '') {
                return $last;
            }
            return $name;
        }

        $emailLocal = (string) strstr($email, '@', true);
        return $emailLocal !== '' ? $emailLocal : 'user';
    }

    private function sanitizeRemoteImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private function userColumnExists(string $column): bool
    {
        static $cache = [];
        $column = trim($column);
        if ($column === '') {
            return false;
        }
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute(['users', $column]);
            $cache[$column] = ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }

    private function postJson(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $raw = curl_exec($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function postForm(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $raw = curl_exec($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
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


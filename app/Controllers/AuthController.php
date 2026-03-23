<?php

/**
 * Auth Controller
 * Handles authentication (login, register, logout, password reset)
 */
class AuthController extends Controller
{
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
        $this->setNoCache();
        // Already logged in? Redirect to home
        if ($this->authService->isLoggedIn()) {
            $this->redirect(url(''));
        }

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/login', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig]);
    }

    /**
     * Show dedicated OTP page for login 2FA
     */
    public function showLoginOtp()
    {
        $this->setNoCache();
        if ($this->authService->isLoggedIn()) {
            $this->redirect(url(''));
        }

        $challengeId = trim((string) $this->get('challenge_id', ''));
        if ($challengeId === '') {
            $this->redirect(url('login'));
        }

        $challengeRow = $this->findOtpChallenge($challengeId, 'login_2fa');
        if (!$challengeRow) {
            $this->redirect(url('login'));
        }

        if (!empty($challengeRow['consumed_at']) || !empty($challengeRow['verified_at'])) {
            $this->redirect(url('login'));
        }

        $otpEmail = trim((string) ($challengeRow['email'] ?? ''));
        if ($otpEmail === '') {
            $user = $this->userModel->findById((int) ($challengeRow['user_id'] ?? 0));
            $otpEmail = trim((string) ($user['email'] ?? ''));
        }

        $secondsLeft = max(0, strtotime((string) ($challengeRow['expires_at'] ?? '')) - time());
        $expiresMinutes = max(1, (int) ceil($secondsLeft / 60));

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/login_otp', [
            'chungapi' => $siteConfig,
            'siteConfig' => $siteConfig,
            'challengeId' => $challengeId,
            'otpEmail' => $otpEmail,
            'otpEmailMasked' => $this->maskEmailAddress($otpEmail),
            'otpExpiresMinutes' => $expiresMinutes,
            'otpExpiresSeconds' => $secondsLeft,
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
            $retryAfterSeconds = max(0, (int) ($limit['retry_after_seconds'] ?? 0));
            return $this->json([
                'success' => false,
                'message' => (string) ($limit['message'] ?? 'Bạn đã thử quá số lần cho phép.'),
                'retry_after_seconds' => $retryAfterSeconds,
                'lockout_until' => $retryAfterSeconds > 0 ? time() + $retryAfterSeconds : null,
                'window_minutes' => (int) ($limit['window_minutes'] ?? 5),
                'max_attempts' => (int) ($limit['limit'] ?? 5),
                'attempts_left' => (int) ($limit['attempts_left'] ?? 0),
            ], 429);
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
            return $this->respondLoginFailure($username);
        }

        if (!$this->authSecurity->verifyPassword($user, $password)) {
            $this->authSecurity->recordLoginAttempt('login', $username, false, 'wrong_password');
            return $this->respondLoginFailure($username);
        }

        if ($this->authSecurity->needsPasswordRehash($user)) {
            $this->userModel->update($user['id'], [
                'password' => $this->authSecurity->hashPassword($password),
                'password_updated_at' => class_exists('TimeService') ? TimeService::instance()->nowSql() : date('Y-m-d H:i:s')
            ]);
            $user = $this->userModel->findById((int) $user['id']) ?: $user;
        }

        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        if ($this->authSecurity->shouldRequireTwoFactor($user, $device)) {
            $challenge = $this->authSecurity->createOtpChallenge($user, 'login_2fa', $device, [
                'remember_me' => $rememberMe ? 1 : 0,
                'fingerprint' => $fingerprintHash,
                'fp_components' => $fpComponents,
            ]);

            $this->authSecurity->recordLoginAttempt('login', $username, true, 'otp_sent');
            return $this->json([
                'success' => true,
                'requires_2fa' => true,
                'challenge_id' => $challenge['challenge_id'],
                'message' => 'Đã gửi mã OTP đến email của bạn. Vui lòng nhập mã để hoàn tất đăng nhập.'
            ]);
        }

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe);
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

    private function respondLoginFailure(string $username)
    {
        $status = $this->authSecurity->getFailedAttemptStatus('login', $username);
        $attemptsLeft = (int) ($status['attempts_left'] ?? 0);
        $maxAttempts = (int) ($status['limit'] ?? 5);
        $windowMinutes = (int) ($status['window_minutes'] ?? 5);
        $retryAfterSeconds = max(0, (int) ($status['retry_after_seconds'] ?? 0));

        if ($attemptsLeft <= 0) {
            $limit = $this->authSecurity->checkRateLimit('login', $username);
            if (is_array($limit)) {
                $retryAfterSeconds = max(0, (int) ($limit['retry_after_seconds'] ?? $retryAfterSeconds));
                $windowMinutes = (int) ($limit['window_minutes'] ?? $windowMinutes);
                $maxAttempts = (int) ($limit['limit'] ?? $maxAttempts);
            }
            if (!is_array($limit) && $retryAfterSeconds <= 0) {
                $latestStatus = $this->authSecurity->getFailedAttemptStatus('login', $username);
                $attemptsLeft = max(0, (int) ($latestStatus['attempts_left'] ?? 0));
                $maxAttempts = (int) ($latestStatus['limit'] ?? $maxAttempts);
                $windowMinutes = (int) ($latestStatus['window_minutes'] ?? $windowMinutes);

                return $this->json([
                    'success' => false,
                    'message' => "Thông tin đăng nhập không chính xác.",
                    'attempts_left' => $attemptsLeft,
                    'max_attempts' => $maxAttempts,
                    'window_minutes' => $windowMinutes,
                    'retry_after_seconds' => 0,
                    'lockout_until' => null,
                ], 401);
            }

            $message = (string) ($limit['message'] ?? ('Tài khoản này đã bị khóa tạm thời ' . $windowMinutes . ' phút do nhập sai quá ' . $maxAttempts . ' lần.'));
            return $this->json([
                'success' => false,
                'message' => $message,
                'attempts_left' => 0,
                'max_attempts' => $maxAttempts,
                'window_minutes' => $windowMinutes,
                'retry_after_seconds' => $retryAfterSeconds,
                'lockout_until' => $retryAfterSeconds > 0 ? time() + $retryAfterSeconds : null,
            ], 429);
        }

        return $this->json([
            'success' => false,
            'message' => "Thông tin đăng nhập không chính xác.",
            'attempts_left' => $attemptsLeft,
            'max_attempts' => $maxAttempts,
            'window_minutes' => $windowMinutes,
            'retry_after_seconds' => 0,
            'lockout_until' => null,
        ], 401);
    }

    /**
     * Show register page
     */
    public function showRegister()
    {
        $this->setNoCache();
        if ($this->authService->isLoggedIn()) {
            $this->redirect(url(''));
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
        $timeService = class_exists('TimeService') ? TimeService::instance() : null;
        $time = $timeService ? $timeService->nowSql() : date('Y-m-d H:i:s');
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
            if (!$newUser) {
                return $this->json(['success' => false, 'message' => 'Không thể khởi tạo tài khoản vừa đăng ký.'], 500);
            }

            if (!$this->tryCompleteAuthenticatedSession($newUser, $fingerprintHash, (string) $this->post('fp_components', ''), false, 'register')) {
                return $this->json(['success' => false, 'message' => 'Không thể đăng nhập ngay sau khi đăng ký. Vui lòng thử lại.'], 500);
            }


            Logger::info('Auth', 'register_success', 'Đăng ký tài khoản thành công', [
                'username' => $username,
                'email' => $email,
                'fingerprint' => $fingerprintHash ?: 'none'
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Đăng ký thành công.',
                'redirect' => url(''),
            ]);
        }

        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.'], 500);
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->authService->logout();
        $this->redirect(url('login'));
    }

    /**
     * Show forgot password page
     */
    public function showForgotPassword()
    {
        $this->setNoCache();
        if ($this->authService->isLoggedIn()) {
            $this->redirect(url(''));
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
            $device = $this->authSecurity->getDeviceContext(trim((string) $this->post('fingerprint', '')));

            if ((int) ($user['twofa_enabled'] ?? 0) === 1) {
                if ($challengeId === '' || $otpCode === '') {
                    $challenge = $this->authSecurity->createOtpChallenge($user, 'forgot_password', $device, []);
                    $this->authSecurity->recordLoginAttempt('forgot_password', $username, true, 'otp_sent');
                    return $this->json([
                        'success' => true,
                        'requires_2fa' => true,
                        'challenge_id' => $challenge['challenge_id'],
                        'message' => 'Tài khoản đang bật 2FA. Đã gửi OTP đến email, vui lòng nhập mã để tiếp tục.'
                    ]);
                }

                $verifyOtp = $this->authSecurity->verifyOtpChallenge($challengeId, $otpCode, 'forgot_password');
                if (!$verifyOtp['ok']) {
                    return $this->json(['success' => false, 'message' => $verifyOtp['message']], 400);
                }
            }

            $otpcode = bin2hex(random_bytes(16)); // Mã reset
            $send_status = $this->authSecurity->sendPasswordResetMail($user, $otpcode);

            if ($send_status) {
                $this->userModel->update($user['id'], ['otpcode' => $otpcode]);
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, true, 'mail_sent');
                Logger::warning('Auth', 'forgot_password_request', 'Yêu cầu quên mật khẩu', ['username' => $user['username'], 'email' => ($user['email'] ?? '')]);
                return $this->json(['success' => true, 'message' => 'Email đặt lại mật khẩu đã được gửi']);
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
        $this->setNoCache();
        if ($this->authService->isLoggedIn()) {
            $this->redirect(url(''));
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
            return $this->json(['success' => false, 'message' => 'Mật khẩu phải từ 6 ký tự']);
        }

        $user = $this->userModel->findByOtpcode($otpcode);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Mã khôi phục không hợp lệ hoặc đã hết hạn (OTP Code không đúng)']);
        }

        $new_pass = $this->authSecurity->hashPassword($password);
        $this->userModel->update($user['id'], [
            'password' => $new_pass,
            'password_updated_at' => class_exists('TimeService') ? TimeService::instance()->nowSql() : date('Y-m-d H:i:s'),
            'otpcode' => ''
        ]);

        Logger::info('Auth', 'reset_password_success', 'Đặt lại mật khẩu thành công', ['username' => $user['username']]);

        return $this->json(['success' => true, 'message' => 'Mật khẩu đã được cập nhật thành công']);
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

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe);
        $this->authSecurity->recordLoginAttempt('login', (string) ($user['username'] ?? ''), true, '2fa_verified');

        return $this->json([
            'success' => true,
            'message' => 'Xác minh OTP thành công. Đăng nhập thành công.',
            'redirect' => url(''),
            'access_expires_in' => 900,
            'refresh_expires_in' => $rememberMe ? 1209600 : 86400
        ]);
    }

    /**
     * Resend OTP for login 2FA challenge
     */
    public function resendLoginOtp()
    {
        $challengeId = trim((string) $this->post('challenge_id', ''));
        if ($challengeId === '') {
            return $this->json(['success' => false, 'message' => 'Thiếu phiên xác minh OTP.'], 400);
        }

        $challengeRow = $this->findOtpChallenge($challengeId, 'login_2fa');
        if (!$challengeRow) {
            return $this->json(['success' => false, 'message' => 'Phiên OTP không hợp lệ. Vui lòng đăng nhập lại.'], 400);
        }

        if (!empty($challengeRow['consumed_at']) || !empty($challengeRow['verified_at'])) {
            return $this->json(['success' => false, 'message' => 'Mã OTP này đã được sử dụng. Vui lòng đăng nhập lại.'], 400);
        }

        $createdTs = strtotime((string) ($challengeRow['created_at'] ?? ''));
        if ($createdTs > 0) {
            $cooldownLeft = max(0, 30 - (time() - $createdTs));
            if ($cooldownLeft > 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Vui lòng chờ trước khi gửi lại OTP.',
                    'cooldown_seconds' => $cooldownLeft,
                ], 429);
            }
        }

        $user = $this->userModel->findById((int) ($challengeRow['user_id'] ?? 0));
        if (!$user || empty($user['email'])) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy tài khoản để gửi OTP.'], 404);
        }

        $meta = [];
        $metadataRaw = (string) ($challengeRow['metadata_json'] ?? '');
        if ($metadataRaw !== '') {
            $decodedMeta = json_decode($metadataRaw, true);
            if (is_array($decodedMeta)) {
                $meta = $decodedMeta;
            }
        }

        $fingerprintHash = trim((string) ($meta['fingerprint'] ?? ''));
        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        $newChallenge = $this->authSecurity->createOtpChallenge($user, 'login_2fa', $device, $meta);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE auth_otp_codes SET consumed_at = NOW() WHERE id = ? AND consumed_at IS NULL');
        $stmt->execute([(int) $challengeRow['id']]);

        $expiresIn = max(60, (int) ($newChallenge['expires_in'] ?? 300));
        return $this->json([
            'success' => true,
            'message' => 'Đã gửi lại mã OTP mới.',
            'challenge_id' => (string) ($newChallenge['challenge_id'] ?? ''),
            'expires_in' => $expiresIn,
            'expires_minutes' => max(1, (int) ceil($expiresIn / 60)),
            'email' => (string) ($user['email'] ?? ''),
            'email_masked' => $this->maskEmailAddress((string) ($user['email'] ?? '')),
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

        $this->rejectInvalidCsrf('', true);

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
     * Google login/register via Firebase ID token (AJAX)
     */
    public function googleLogin()
    {
        $rememberMe = in_array((string) $this->post('remember', '0'), ['1', 'true', 'on'], true);


        error_log('[GoogleAuth] googleLogin() called. IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' HOST=' . ($_SERVER['HTTP_HOST'] ?? '?'));
        if (($limit = $this->authSecurity->checkRateLimit('login_google', (string) $this->post('email_hint', 'google'))) !== null) {
            return $this->json(['success' => false, 'message' => $limit['message']], 429);
        }

        // NOTE: Google auth uses Firebase signInWithRedirect flow — no Turnstile widget is present after redirect.
        // Security is guaranteed by Firebase ID token verification via Google Identity Toolkit.

        $idToken = trim((string) $this->post('id_token', ''));
        if ($idToken === '') {
            error_log('[GoogleAuth] ERROR: id_token empty. POST keys=' . implode(',', array_keys($_POST)));
            return $this->json(['success' => false, 'message' => 'Thiếu ID token Google.'], 400);
        }

        error_log('[GoogleAuth] id_token length=' . strlen($idToken) . ', calling verifyFirebaseGoogleIdToken...');
        $googleUser = $this->verifyFirebaseGoogleIdToken($idToken);
        if (!$googleUser || empty($googleUser['email'])) {
            error_log('[GoogleAuth] ERROR: verifyFirebaseGoogleIdToken failed. Token prefix=' . substr($idToken, 0, 40));
            return $this->json(['success' => false, 'message' => 'Không xác minh được tài khoản Google.'], 401);
        }

        $email = strtolower(trim((string) $googleUser['email']));

        // Prefer explicit Google claims when available.
        $googleName = $this->normalizeDisplayName((string) $this->post('google_name', ''));
        if ($googleName === '') {
            $googleName = $this->normalizeDisplayName((string) ($googleUser['name'] ?? ''));
        }

        $familyName = $this->normalizeDisplayName((string) $this->post('google_family_name', ''));
        if ($familyName === '') {
            $familyName = $this->normalizeDisplayName((string) ($googleUser['family_name'] ?? ''));
        }

        // Priority 1: explicit Google full name
        // Priority 2: client-side Firebase displayName
        // Priority 3: Identity Toolkit displayName
        // Priority 4: family_name
        $displayName = $googleName;
        if ($displayName === '') {
            $displayName = trim((string) $this->post('display_name', ''));
        }
        if ($displayName === '') {
            $displayName = $this->normalizeDisplayName((string) ($googleUser['displayName'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = $familyName;
        }

        $photoUrl = trim((string) $this->post('photo_url', ''));
        if ($photoUrl === '') {
            $photoUrl = trim((string) ($googleUser['photoUrl'] ?? ''));
        }
        $fingerprintHash = trim($this->post('fingerprint', ''));
        $fpComponents = $this->post('fp_components', '');

        $user = $this->userModel->findByEmail($email);
        $isNewGoogleUser = false;

        if (!$user) {
            $isNewGoogleUser = true;
            global $ip_address;
            $baseSeed = $familyName !== '' ? $familyName : ($displayName !== '' ? $this->extractLastName($displayName) : strstr($email, '@', true));
            $username = $this->generateUniqueUsernameFromSeed((string) $baseSeed);
            $randomId = $this->generateUniqueUserId();
            $apiKey = md5(bin2hex(random_bytes(16)));
            $timeService = class_exists('TimeService') ? TimeService::instance() : null;
            $time = $timeService ? $timeService->nowSql() : date('Y-m-d H:i:s');

            $newId = $this->userModel->create([
                'id' => $randomId,
                'username' => $username,
                'password' => $this->authSecurity->hashPassword(bin2hex(random_bytes(16))),
                'password_updated_at' => class_exists('TimeService') ? TimeService::instance()->nowSql() : date('Y-m-d H:i:s'),
                'email' => $email,
                'full_name' => $displayName,
                'avatar_url' => $photoUrl,
                'level' => ($this->userModel->count() == 0) ? '9' : '0',
                'tong_nap' => '0',
                'money' => '0',
                'bannd' => '0',
                'ip' => $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                'api_key' => $apiKey,
                'time' => $time
            ]);

            $user = $this->userModel->findById((int) $newId);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'Không thể tạo tài khoản từ Google.'], 500);
            }

            Logger::info('Auth', 'register_google_success', 'Đăng ký bằng Google thành công', [
                'username' => $user['username'],
                'email' => $email
            ]);
        } else {
            // Update existing user profile if needed
            $updateData = [];
            if ($displayName !== '' && trim((string) ($user['full_name'] ?? '')) !== $displayName) {
                $updateData['full_name'] = $displayName;
            }
            if ($photoUrl !== '' && (empty($user['avatar_url']) || strpos($user['avatar_url'], 'googleusercontent.com') !== false)) {
                $updateData['avatar_url'] = $photoUrl;
            }
            if (!empty($updateData)) {
                $this->userModel->update($user['id'], $updateData);
                $user = array_merge($user, $updateData);
            }
        }

        $device = $this->authSecurity->getDeviceContext($fingerprintHash);
        if (!$isNewGoogleUser && $this->authSecurity->shouldRequireTwoFactor($user, $device)) {
            $challenge = $this->authSecurity->createOtpChallenge($user, 'login_2fa', $device, [
                'remember_me' => $rememberMe ? 1 : 0,
                'fingerprint' => $fingerprintHash,
                'fp_components' => $fpComponents,
                'source' => 'google'
            ]);

            return $this->json([
                'success' => true,
                'requires_2fa' => true,
                'challenge_id' => $challenge['challenge_id'],
                'message' => 'Đã gửi mã OTP đến email của bạn. Vui lòng nhập mã để hoàn tất đăng nhập Google.'
            ]);
        }

        if (!$this->tryCompleteAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe, 'google_login')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể khởi tạo phiên đăng nhập. Vui lòng thử lại.'
            ], 500);
        }

        Logger::info('Auth', 'login_google_success', 'Đăng nhập bằng Google thành công', [
            'username' => $user['username'],
            'email' => $user['email'] ?? $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Đăng nhập Google thành công.',
            'redirect' => url(''),
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
            error_log('[GoogleAuth] verifyFirebaseGoogleIdToken: FIREBASE_API_KEY is empty!');
            return null;
        }

        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . rawurlencode($apiKey);
        error_log('[GoogleAuth] Calling Identity Toolkit: ' . preg_replace('/key=[^&]+/', 'key=***', $url));

        $response = $this->postJson($url, ['idToken' => $idToken]);

        if (isset($response['error'])) {
            error_log('[GoogleAuth] Identity Toolkit error: ' . json_encode($response['error']));
            return null;
        }

        if (empty($response['users'][0]) || !is_array($response['users'][0])) {
            error_log('[GoogleAuth] Identity Toolkit: users empty. Response keys=' . implode(',', array_keys($response)));
            return null;
        }

        error_log('[GoogleAuth] Identity Toolkit OK. email=' . ($response['users'][0]['email'] ?? '?'));
        return $response['users'][0];
    }

    private function completeAuthenticatedSession(array $user, string $fingerprintHash = '', string $fpComponents = '', bool $rememberMe = false): void
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
                $fpComponents
            );
        } catch (Exception $e) {
            // Ignore fingerprint logging errors
        }
    }

    private function tryCompleteAuthenticatedSession(array $user, string $fingerprintHash = '', string $fpComponents = '', bool $rememberMe = false, string $context = 'auth'): bool
    {
        try {
            $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe);
            return true;
        } catch (Throwable $e) {
            error_log('[Auth] session bootstrap failed: context=' . $context . ' user_id=' . (int) ($user['id'] ?? 0) . ' username=' . (string) ($user['username'] ?? '') . ' message=' . $e->getMessage());
            Logger::warning('Auth', 'session_bootstrap_failed', 'Khởi tạo phiên đăng nhập thất bại', [
                'context' => $context,
                'user_id' => (int) ($user['id'] ?? 0),
                'username' => (string) ($user['username'] ?? ''),
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendWelcomeRegisterEmail(array $user): void
    {
        try {
            if (!class_exists('MailService')) {
                require_once __DIR__ . '/../Services/MailService.php';
            }
            (new MailService())->sendWelcomeRegister($user);
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    private function findOtpChallenge(string $challengeId, string $purpose): ?array
    {
        if ($challengeId === '' || $purpose === '') {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM auth_otp_codes WHERE challenge_id = ? AND purpose = ? LIMIT 1');
        $stmt->execute([$challengeId, $purpose]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function maskEmailAddress(string $email): string
    {
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $localLen = strlen($local);
        if ($localLen <= 2) {
            $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(1, $localLen - 1));
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(2, $localLen - 2));
        }

        return $maskedLocal . '@' . $domain;
    }

    private function generateUniqueUsernameFromSeed(string $seed): string
    {
        $seed = trim($seed);
        if (function_exists('xoadau')) {
            $seed = xoadau($seed);
        }
        $seed = strtolower($seed);
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

    private function normalizeDisplayName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
        return $name;
    }

    private function extractLastName(string $fullName): string
    {
        $fullName = $this->normalizeDisplayName($fullName);
        if ($fullName === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $fullName) ?: [];
        $lastName = end($parts);
        return is_string($lastName) ? $lastName : $fullName;
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
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log('[postJson] curl error: ' . $curlError . ' url=' . $url);
        }

        if (!is_string($raw) || $raw === '') {
            error_log('[postJson] empty response. HTTP=' . $httpCode . ' url=' . $url);
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
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log('[postJson] curl error: ' . $curlError . ' url=' . $url);
        }

        if (!is_string($raw) || $raw === '') {
            error_log('[postJson] empty response. HTTP=' . $httpCode . ' url=' . $url);
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

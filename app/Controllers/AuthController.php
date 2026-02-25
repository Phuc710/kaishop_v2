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
        // Already logged in? Redirect to home
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }

        $siteConfig = Config::getSiteConfig();
        $this->view('auth/login', ['chungapi' => $siteConfig, 'siteConfig' => $siteConfig]);
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

        Logger::info('Auth', 'login_success', 'ÄÄƒng nháº­p thÃ nh cÃ´ng', [
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
            return $this->json(['success' => false, 'message' => 'TÃªn Ä‘Äƒng nháº­p Ä‘Ã£ tá»“n táº¡i.'], 400);
        }

        // Check if email exists
        if ($this->userModel->emailExists($email)) {
            return $this->json(['success' => false, 'message' => 'Email Ä‘Ã£ Ä‘Æ°á»£c sá»­ dá»¥ng.'], 400);
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
                $this->completeAuthenticatedSession($newUser, $fingerprintHash, $this->post('fp_components', ''), false);

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

            Logger::info('Auth', 'register_success', 'ÄÄƒng kÃ½ tÃ i khoáº£n thÃ nh cÃ´ng', [
                'username' => $username,
                'email' => $email,
                'fingerprint' => $fingerprintHash ?: 'none'
            ]);

            return $this->json(['success' => true, 'message' => 'ÄÄƒng kÃ½ thÃ nh cÃ´ng.']);
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
            return $this->json(['success' => false, 'message' => 'Vui lÃ²ng nháº­p Ä‘áº§y Ä‘á»§ thÃ´ng tin'], 400);
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

            $otpcode = bin2hex(random_bytes(16)); // MÃ£ reset
            $send_status = $this->authSecurity->sendPasswordResetMail($user, $otpcode);

            if ($send_status) {
                $this->userModel->update($user['id'], ['otpcode' => $otpcode]);
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, true, 'mail_sent');
                Logger::warning('Auth', 'forgot_password_request', 'YÃªu cáº§u quÃªn máº­t kháº©u', ['username' => $user['username'], 'email' => ($user['email'] ?? '')]);
                return $this->json(['success' => true, 'message' => 'Email Ä‘áº·t láº¡i máº­t kháº©u Ä‘Ã£ Ä‘Æ°á»£c gá»­i']);
            } else {
                $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'mail_send_failed');
                return $this->json(['success' => false, 'message' => 'KhÃ´ng thá»ƒ gá»­i email, vui lÃ²ng thá»­ láº¡i']);
            }
        }

        $this->authSecurity->recordLoginAttempt('forgot_password', $username, false, 'user_not_found');
        return $this->json(['success' => false, 'message' => 'TÃ i khoáº£n khÃ´ng tá»“n táº¡i'], 404);
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
            return $this->json(['success' => false, 'message' => 'Vui lÃ²ng nháº­p Ä‘áº§y Ä‘á»§ thÃ´ng tin']);
        }

        if (strlen($password) < 6) {
            return $this->json(['success' => false, 'message' => 'Máº­t kháº©u pháº£i tá»« 6']);
        }

        $user = $this->userModel->findByOtpcode($otpcode);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'MÃ£ khÃ´i phá»¥c khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ háº¿t háº¡n (OTP Code khÃ´ng Ä‘Ãºng)']);
        }

        $new_pass = $this->authSecurity->hashPassword($password);
        $this->userModel->update($user['id'], [
            'password' => $new_pass,
            'password_updated_at' => date('Y-m-d H:i:s'),
            'otpcode' => ''
        ]);

        Logger::info('Auth', 'reset_password_success', 'Äáº·t láº¡i máº­t kháº©u thÃ nh cÃ´ng', ['username' => $user['username']]);

        return $this->json(['success' => true, 'message' => 'Máº­t kháº©u Ä‘Ã£ Ä‘Æ°á»£c cáº­p nháº­t thÃ nh cÃ´ng']);
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
            'redirect' => BASE_URL . '/',
            'access_expires_in' => 900,
            'refresh_expires_in' => $rememberMe ? 1209600 : 86400
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
        $fingerprintHash = trim($this->post('fingerprint', ''));
        $fpComponents = $this->post('fp_components', '');

        $user = $this->userModel->findByEmail($email);
        $isNewGoogleUser = false;

        if (!$user) {
            $isNewGoogleUser = true;
            global $ip_address;
            $baseSeed = $displayName !== '' ? $displayName : strstr($email, '@', true);
            $username = $this->generateUniqueUsernameFromSeed((string) $baseSeed);
            $randomId = $this->generateUniqueUserId();
            $apiKey = md5(bin2hex(random_bytes(16)));
            $time = date('h:i d-m-Y');

            $newId = $this->userModel->create([
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
            ]);

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

        $this->completeAuthenticatedSession($user, $fingerprintHash, $fpComponents, $rememberMe);

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

    private function generateUniqueUsernameFromSeed(string $seed): string
    {
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

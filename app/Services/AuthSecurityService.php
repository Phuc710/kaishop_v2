<?php

use App\Helpers\UserAgentParser;

/**
 * Production-style auth security service:
 * - Access/Refresh token cookies (rotation)
 * - Session DB (auth_sessions)
 * - Device trust / device check
 * - Email OTP (2FA login / forgot password)
 * - Login rate limiting
 */
class AuthSecurityService
{
    private PDO $db;

    private const ACCESS_COOKIE = 'ks_at';
    private const REFRESH_COOKIE = 'ks_rt';
    private const DEVICE_COOKIE = 'ks_dv';

    private const ACCESS_TTL = 900; // 15 minutes
    private const REFRESH_TTL_REMEMBER = 1209600; // 14 days
    private const REFRESH_TTL_DEFAULT = 86400; // 1 day
    private const TRUSTED_DEVICE_DAYS = 30;

    private static bool $pruneAttempted = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->maybePruneExpiredAuthData();
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public function verifyPassword(array $user, string $plain): bool
    {
        $hash = (string) ($user['password'] ?? '');
        if ($hash === '') {
            return false;
        }

        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
            return password_verify($plain, $hash);
        }

        // Legacy fallback sha1(md5())
        return hash_equals($hash, sha1(md5($plain)));
    }

    public function needsPasswordRehash(array $user): bool
    {
        $hash = (string) ($user['password'] ?? '');
        return !($hash !== '' && (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0));
    }

    public function recordLoginAttempt(string $action, string $usernameOrEmail, bool $success, string $reason = ''): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO auth_login_attempts (action, username_or_email, ip_address, success, reason, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $action,
                mb_substr($usernameOrEmail, 0, 190),
                $this->clientIp(),
                $success ? 1 : 0,
                mb_substr($reason, 0, 190),
                mb_substr($this->userAgent(), 0, 1000),
            ]);
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    public function checkRateLimit(string $action, string $usernameOrEmail = ''): ?array
    {
        // IP burst: 10 attempts / 15 minutes
        $ip = $this->clientIp();
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM auth_login_attempts WHERE action = ? AND ip_address = ? AND created_at >= (NOW() - INTERVAL 15 MINUTE)");
        $stmt->execute([$action, $ip]);
        $ipCount = (int) $stmt->fetchColumn();
        if ($ipCount >= 10) {
            return ['blocked' => true, 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau 15 phút.'];
        }

        if ($usernameOrEmail !== '') {
            $stmt = $this->db->prepare("SELECT COUNT(*) c FROM auth_login_attempts WHERE action = ? AND username_or_email = ? AND ip_address = ? AND success = 0 AND created_at >= (NOW() - INTERVAL 15 MINUTE)");
            $stmt->execute([$action, mb_substr($usernameOrEmail, 0, 190), $ip]);
            $failCount = (int) $stmt->fetchColumn();
            if ($failCount >= 5) {
                // Send email warning on first lockout trigger
                $this->sendLockoutWarningEmail($usernameOrEmail, $ip, $failCount);
                return ['blocked' => true, 'message' => 'Tài khoản này đã bị khoá tạm thời 15 phút do nhập sai quá 5 lần.'];
            }
        }

        return null;
    }

    /**
     * Send a security warning email when a user account is locked out due to too many failed login attempts.
     */
    private function sendLockoutWarningEmail(string $usernameOrEmail, string $ip, int $attemptCount): void
    {
        try {
            // Avoid sending duplicate lockout emails within the same lockout window
            $cacheKey = 'lockout_email_' . md5($usernameOrEmail . $ip);
            if (!empty($_SESSION[$cacheKey]) && (time() - (int) $_SESSION[$cacheKey]) < 900) {
                return; // Already sent within 15 minutes
            }

            // Find user by username or email
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || empty($user['email'])) {
                return;
            }

            $mailService = $this->mailService();
            $username = (string) $user['username'];
            $email = (string) $user['email'];
            $time = date('d/m/Y H:i:s');

            $subject = '⚠️ Cảnh báo bảo mật - Tài khoản bị khoá tạm thời';
            $headline = 'Cảnh báo bảo mật';
            $content = '
                <p style="font-size:15px;color:#334155;">Xin chào <strong>' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                <p style="font-size:15px;color:#334155;">Tài khoản của bạn vừa bị <strong style="color:#dc2626;">khoá tạm thời 15 phút</strong> do nhập sai mật khẩu <strong>' . (int) $attemptCount . ' lần liên tiếp</strong>.</p>
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px 20px;margin:16px 0;">
                    <p style="margin:0 0 8px;font-weight:700;color:#991b1b;">📌 Chi tiết:</p>
                    <p style="margin:0;font-size:14px;color:#7f1d1d;">• Thời gian: ' . $time . '</p>
                    <p style="margin:0;font-size:14px;color:#7f1d1d;">• Địa chỉ IP: ' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '</p>
                    <p style="margin:0;font-size:14px;color:#7f1d1d;">• Số lần thử sai: ' . (int) $attemptCount . '</p>
                </div>
                <p style="font-size:15px;color:#334155;">Nếu <strong>không phải bạn</strong> thực hiện, hãy đổi mật khẩu ngay lập tức để bảo vệ tài khoản.</p>
                <p style="font-size:13px;color:#94a3b8;margin-top:20px;">Email này được gửi tự động từ hệ thống bảo mật. Vui lòng không trả lời.</p>
            ';

            $body = $mailService->buildLayout($subject, $headline, $content);
            $mailService->send($email, $username, $subject, $body);

            $_SESSION[$cacheKey] = time();

            Logger::warning('Auth', 'lockout_email_sent', "Email cảnh báo khoá tạm gửi cho {$username}", [
                'username' => $username,
                'ip' => $ip,
                'attempts' => $attemptCount,
            ]);
        } catch (Throwable $e) {
            // Non-blocking: don't let email failure affect the lockout
            error_log('sendLockoutWarningEmail failed: ' . $e->getMessage());
        }
    }

    public function getDeviceContext(string $fingerprintHash = ''): array
    {
        if (!class_exists('App\\Helpers\\UserAgentParser')) {
            require_once __DIR__ . '/../Helpers/UserAgentParser.php';
        }

        $ua = $this->userAgent();
        $parsed = UserAgentParser::parse($ua);
        $ip = $this->clientIp();
        $cookieDevice = trim((string) ($_COOKIE[self::DEVICE_COOKIE] ?? ''));
        if ($cookieDevice === '' || !preg_match('/^[a-f0-9]{20,64}$/', $cookieDevice)) {
            $cookieDevice = bin2hex(random_bytes(18));
            $this->setCookie(self::DEVICE_COOKIE, $cookieDevice, time() + 31536000, false);
        }
        $fp = trim($fingerprintHash);

        $deviceSource = 'cookie:' . $cookieDevice . '|ua:' . $ua;
        $deviceHash = hash('sha256', $deviceSource);

        return [
            'ip_address' => $ip,
            'user_agent' => $ua,
            'os' => (string) ($parsed['os'] ?? 'Unknown OS'),
            'browser' => (string) ($parsed['browser'] ?? 'Unknown Browser'),
            'device_type' => (string) ($parsed['type'] ?? 'Desktop'),
            'fingerprint' => $fp,
            'device_hash' => $deviceHash,
        ];
    }

    public function shouldRequireTwoFactor(array $user, array $device): bool
    {
        // Latest business rule: only accounts that explicitly enable 2FA require OTP at login.
        return (int) ($user['twofa_enabled'] ?? 0) === 1;
    }

    public function createOtpChallenge(array $user, string $purpose, array $device, array $meta = [], int $ttlSeconds = 300): array
    {
        $challengeId = bin2hex(random_bytes(16));
        $otpCode = (string) random_int(100000, 999999);
        $codeHash = hash('sha256', $challengeId . '|' . $otpCode . '|' . (string) ($user['id'] ?? 0));

        $stmt = $this->db->prepare(
            "INSERT INTO auth_otp_codes (challenge_id, user_id, purpose, email, code_hash, attempts, max_attempts, expires_at, ip_address, user_agent, device_hash, metadata_json, created_at)
             VALUES (?, ?, ?, ?, ?, 0, 5, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $challengeId,
            (int) $user['id'],
            $purpose,
            (string) ($user['email'] ?? ''),
            $codeHash,
            $ttlSeconds,
            (string) $device['ip_address'],
            mb_substr((string) $device['user_agent'], 0, 1000),
            (string) $device['device_hash'],
            json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);

        $this->sendOtpEmail((string) $user['email'], (string) ($user['username'] ?? ''), $otpCode, $purpose, $ttlSeconds);

        return [
            'challenge_id' => $challengeId,
            'expires_in' => $ttlSeconds,
        ];
    }

    public function verifyOtpChallenge(string $challengeId, string $otpCode, string $purpose): array
    {
        $stmt = $this->db->prepare("SELECT * FROM auth_otp_codes WHERE challenge_id = ? AND purpose = ? LIMIT 1");
        $stmt->execute([$challengeId, $purpose]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            return ['ok' => false, 'message' => 'Mã xác minh không hợp lệ.'];
        }
        if (!empty($row['consumed_at']) || !empty($row['verified_at'])) {
            return ['ok' => false, 'message' => 'Mã xác minh đã được sử dụng.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'message' => 'Mã xác minh đã hết hạn.'];
        }
        if ((int) $row['attempts'] >= (int) $row['max_attempts']) {
            return ['ok' => false, 'message' => 'Bạn đã nhập sai quá số lần cho phép.'];
        }

        $expected = hash('sha256', $challengeId . '|' . $otpCode . '|' . (string) $row['user_id']);
        if (!hash_equals((string) $row['code_hash'], $expected)) {
            $this->db->prepare("UPDATE auth_otp_codes SET attempts = attempts + 1 WHERE id = ?")->execute([(int) $row['id']]);
            return ['ok' => false, 'message' => 'Mã OTP không đúng.'];
        }

        $this->db->prepare("UPDATE auth_otp_codes SET verified_at = NOW(), consumed_at = NOW() WHERE id = ?")->execute([(int) $row['id']]);

        $meta = [];
        if (!empty($row['metadata_json'])) {
            $tmp = json_decode((string) $row['metadata_json'], true);
            if (is_array($tmp)) {
                $meta = $tmp;
            }
        }

        return ['ok' => true, 'row' => $row, 'meta' => $meta];
    }

    public function issueLoginTokens(array $user, array $device, bool $rememberMe = false): array
    {
        $this->revokeAllSessionsForUser((int) $user['id']);

        $selector = bin2hex(random_bytes(12));
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(48));
        $legacySession = $this->generateLegacySessionToken();

        $refreshTtl = $rememberMe ? self::REFRESH_TTL_REMEMBER : self::REFRESH_TTL_DEFAULT;
        $accessExpiresAt = date('Y-m-d H:i:s', time() + self::ACCESS_TTL);
        $refreshExpiresAt = date('Y-m-d H:i:s', time() + $refreshTtl);

        $stmt = $this->db->prepare(
            "INSERT INTO auth_sessions (
                user_id, session_selector, access_token_hash, refresh_token_hash,
                legacy_session_token, access_expires_at, refresh_expires_at,
                ip_address, user_agent, device_fingerprint, device_hash, device_os, device_browser, device_type,
                remember_me, status, last_rotated_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW(), NOW())"
        );
        $stmt->execute([
            (int) $user['id'],
            $selector,
            hash('sha256', $accessToken),
            hash('sha256', $refreshToken),
            $legacySession,
            $accessExpiresAt,
            $refreshExpiresAt,
            (string) $device['ip_address'],
            mb_substr((string) $device['user_agent'], 0, 1000),
            (string) ($device['fingerprint'] ?? ''),
            (string) $device['device_hash'],
            (string) $device['os'],
            (string) $device['browser'],
            (string) $device['device_type'],
            $rememberMe ? 1 : 0,
        ]);

        $this->db->prepare("UPDATE users SET session = ?, ip_address = ?, user_agent = ?, last_login = NOW() WHERE id = ?")
            ->execute([$legacySession, (string) $device['ip_address'], (string) $device['user_agent'], (int) $user['id']]);

        $this->trustDevice((int) $user['id'], $device, self::TRUSTED_DEVICE_DAYS);
        $this->setAuthCookies($selector, $accessToken, $refreshToken, $refreshTtl);

        $_SESSION['session'] = $legacySession;
        $_SESSION['username'] = $user['username'] ?? null;

        return [
            'legacy_session' => $legacySession,
            'access_expires_in' => self::ACCESS_TTL,
            'refresh_expires_in' => $refreshTtl,
        ];
    }

    public function bootstrapFromCookies(): ?array
    {
        // Session already present -> return current user if valid
        if (!empty($_SESSION['session'])) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE session = ? LIMIT 1");
            $stmt->execute([(string) $_SESSION['session']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($user) {
                if ($this->isUserBanned($user)) {
                    $this->kickBannedUser($user);
                    return null;
                }
                $_SESSION['username'] = $user['username'] ?? null;
                return $user;
            }
            unset($_SESSION['session']);
        }

        $device = $this->getDeviceContext('');

        $access = $this->parseCookieToken((string) ($_COOKIE[self::ACCESS_COOKIE] ?? ''));
        if ($access) {
            $row = $this->findSessionBySelector($access['selector']);
            if ($row && $this->sessionUsable($row, $device) && strtotime((string) $row['access_expires_at']) >= time()) {
                if (hash_equals((string) $row['access_token_hash'], hash('sha256', $access['token']))) {
                    return $this->resumeLegacySessionFromAuthRow($row);
                }
            }
        }

        $refresh = $this->parseCookieToken((string) ($_COOKIE[self::REFRESH_COOKIE] ?? ''));
        if (!$refresh) {
            return null;
        }

        $row = $this->findSessionBySelector($refresh['selector']);
        if (!$row || !$this->sessionUsable($row, $device)) {
            $this->clearAuthCookies();
            return null;
        }
        if (strtotime((string) $row['refresh_expires_at']) < time()) {
            $this->revokeSessionById((int) $row['id'], 'expired_refresh');
            $this->clearAuthCookies();
            return null;
        }
        if (!hash_equals((string) $row['refresh_token_hash'], hash('sha256', $refresh['token']))) {
            // Suspected token replay/theft: revoke immediately
            $this->revokeSessionById((int) $row['id'], 'refresh_mismatch');
            $this->clearAuthCookies();
            return null;
        }

        $this->rotateSessionTokens((int) $row['id'], (bool) $row['remember_me']);
        $row = $this->findSessionById((int) $row['id']);
        if (!$row) {
            return null;
        }

        return $this->resumeLegacySessionFromAuthRow($row);
    }

    public function clearAuthCookies(): void
    {
        $this->setCookie(self::ACCESS_COOKIE, '', time() - 3600, true);
        $this->setCookie(self::REFRESH_COOKIE, '', time() - 3600, true);
        $this->setCookie(self::DEVICE_COOKIE, '', time() - 3600, false);
    }

    public function revokeCurrentAuthSessionFromCookies(): void
    {
        foreach ([self::ACCESS_COOKIE, self::REFRESH_COOKIE] as $cookieName) {
            $parsed = $this->parseCookieToken((string) ($_COOKIE[$cookieName] ?? ''));
            if ($parsed) {
                $row = $this->findSessionBySelector($parsed['selector']);
                if ($row) {
                    $this->revokeSessionById((int) $row['id'], 'logout');
                    break;
                }
            }
        }
        $this->clearAuthCookies();
    }

    public function trustDevice(int $userId, array $device, int $days = self::TRUSTED_DEVICE_DAYS): void
    {
        $deviceHash = (string) ($device['device_hash'] ?? '');
        if ($deviceHash === '') {
            return;
        }

        $stmt = $this->db->prepare("SELECT id FROM user_trusted_devices WHERE user_id = ? AND device_hash = ? LIMIT 1");
        $stmt->execute([$userId, $deviceHash]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $stmt = $this->db->prepare("UPDATE user_trusted_devices SET ip_address = ?, user_agent = ?, os = ?, browser = ?, device_type = ?, trusted_until = DATE_ADD(NOW(), INTERVAL ? DAY), last_seen_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $device['ip_address'] ?? '',
                mb_substr((string) ($device['user_agent'] ?? ''), 0, 1000),
                $device['os'] ?? '',
                $device['browser'] ?? '',
                $device['device_type'] ?? '',
                $days,
                (int) $existingId,
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_trusted_devices (user_id, device_hash, ip_address, user_agent, os, browser, device_type, trusted_until, last_seen_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW(), NOW(), NOW())");
            $stmt->execute([
                $userId,
                $deviceHash,
                $device['ip_address'] ?? '',
                mb_substr((string) ($device['user_agent'] ?? ''), 0, 1000),
                $device['os'] ?? '',
                $device['browser'] ?? '',
                $device['device_type'] ?? '',
                $days,
            ]);
        }
    }

    public function isTrustedDevice(int $userId, string $deviceHash): bool
    {
        if ($deviceHash === '') {
            return false;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_trusted_devices WHERE user_id = ? AND device_hash = ? AND trusted_until >= NOW()");
        $stmt->execute([$userId, $deviceHash]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function sendPasswordResetMail(array $user, string $otpcode): bool
    {
        return $this->mailService()->sendPasswordReset($user, $otpcode);
    }

    private function sendOtpEmail(string $email, string $username, string $otpCode, string $purpose, int $ttlSeconds): void
    {
        $this->mailService()->sendOtp($email, $username, $otpCode, $purpose, $ttlSeconds);
    }

    /** @return MailService */
    private function mailService(): MailService
    {
        static $svc = null;
        if ($svc === null) {
            if (!class_exists('MailService')) {
                require_once __DIR__ . '/MailService.php';
            }
            $svc = new MailService();
        }
        return $svc;
    }

    private function resumeLegacySessionFromAuthRow(array $row): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $row['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$user) {
            return null;
        }

        // Block banned accounts and banned devices at every token restore path
        if ($this->isUserBanned($user)) {
            $this->revokeSessionById((int) $row['id'], 'banned');
            $this->kickBannedUser($user);
            return null;
        }

        $legacySession = (string) ($row['legacy_session_token'] ?? '');
        if ($legacySession === '') {
            return null;
        }

        $_SESSION['session'] = $legacySession;
        $_SESSION['username'] = $user['username'] ?? null;

        // Keep users.session in sync for legacy code paths.
        if (($user['session'] ?? '') !== $legacySession) {
            $this->db->prepare("UPDATE users SET session = ? WHERE id = ?")->execute([$legacySession, (int) $user['id']]);
        }

        return $user;
    }

    private function rotateSessionTokens(int $sessionId, bool $rememberMe): void
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(48));
        $refreshTtl = $rememberMe ? self::REFRESH_TTL_REMEMBER : self::REFRESH_TTL_DEFAULT;

        $stmt = $this->db->prepare("UPDATE auth_sessions SET access_token_hash = ?, refresh_token_hash = ?, access_expires_at = ?, refresh_expires_at = ?, last_rotated_at = NOW(), updated_at = NOW() WHERE id = ? AND status = 'active'");
        $stmt->execute([
            hash('sha256', $accessToken),
            hash('sha256', $refreshToken),
            date('Y-m-d H:i:s', time() + self::ACCESS_TTL),
            date('Y-m-d H:i:s', time() + $refreshTtl),
            $sessionId,
        ]);

        $selectorRow = $this->findSessionById($sessionId);
        if ($selectorRow) {
            $this->setAuthCookies((string) $selectorRow['session_selector'], $accessToken, $refreshToken, $refreshTtl);
        }
    }

    private function revokeAllSessionsForUser(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE auth_sessions SET status = 'revoked', revoked_at = NOW(), revoke_reason = 'new_login', updated_at = NOW() WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
    }

    private function revokeSessionById(int $id, string $reason): void
    {
        $stmt = $this->db->prepare("UPDATE auth_sessions SET status = 'revoked', revoked_at = NOW(), revoke_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $id]);
    }

    private function findSessionBySelector(string $selector): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM auth_sessions WHERE session_selector = ? LIMIT 1");
        $stmt->execute([$selector]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function findSessionById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM auth_sessions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function sessionUsable(array $row, array $device): bool
    {
        if (($row['status'] ?? '') !== 'active') {
            return false;
        }

        $storedHash = (string) ($row['device_hash'] ?? '');
        if ($storedHash !== '' && !hash_equals($storedHash, (string) $device['device_hash'])) {
            return false;
        }

        return true;
    }

    private function parseCookieToken(string $value): ?array
    {
        $value = trim($value);
        if ($value === '' || strpos($value, '.') === false) {
            return null;
        }
        [$selector, $token] = explode('.', $value, 2);
        if (!preg_match('/^[a-f0-9]{24}$/', $selector)) {
            return null;
        }
        if (!preg_match('/^[a-f0-9]{32,128}$/', $token)) {
            return null;
        }
        return ['selector' => $selector, 'token' => $token];
    }

    private function setAuthCookies(string $selector, string $accessToken, string $refreshToken, int $refreshTtl): void
    {
        $this->setCookie(self::ACCESS_COOKIE, $selector . '.' . $accessToken, time() + self::ACCESS_TTL, true);
        $this->setCookie(self::REFRESH_COOKIE, $selector . '.' . $refreshToken, time() + $refreshTtl, true);
        if (empty($_COOKIE[self::DEVICE_COOKIE])) {
            $this->setCookie(self::DEVICE_COOKIE, bin2hex(random_bytes(18)), time() + 31536000, false);
        }
    }

    private function setCookie(string $name, string $value, int $expiresAt, bool $httpOnly): void
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $path = (string) EnvHelper::get('APP_DIR', '');
        $cookiePath = $path !== '' ? rtrim($path, '/') . '/' : '/';

        setcookie($name, $value, [
            'expires' => $expiresAt,
            'path' => $cookiePath,
            'secure' => $isHttps,
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);

        if ($value === '') {
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
    }

    private function clientIp(): string
    {
        return (string) ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function userAgent(): string
    {
        return (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    }

    private function generateLegacySessionToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /**
     * Check if a user is banned (account ban or device fingerprint ban)
     */
    private function isUserBanned(array $user): bool
    {
        // Account ban
        if (!empty($user['bannd']) && (int) $user['bannd'] !== 0) {
            return true;
        }

        // Device fingerprint ban
        $fp = (string) ($user['fingerprint'] ?? '');
        if ($fp !== '') {
            $stmt = $this->db->prepare("SELECT id FROM banned_fingerprints WHERE fingerprint_hash = ? LIMIT 1");
            $stmt->execute([$fp]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear all auth state for a banned user.
     * Does NOT redirect — that is handled by SecurityMiddleware or the caller.
     */
    private function kickBannedUser(array $user): void
    {
        $banReason = (string) ($user['ban_reason'] ?? 'Tài khoản/thiết bị bị khoá');
        $_SESSION['banned_reason'] = $banReason;
        unset($_SESSION['session'], $_SESSION['username']);
        $this->clearAuthCookies();

        Logger::danger('Auth', 'banned_session_blocked', 'Phiên đăng nhập bị chặn do ban', [
            'user_id' => (int) ($user['id'] ?? 0),
            'username' => (string) ($user['username'] ?? ''),
            'ip' => $this->clientIp(),
        ]);
    }

    private function maybePruneExpiredAuthData(): void
    {
        if (self::$pruneAttempted) {
            return;
        }
        self::$pruneAttempted = true;

        // Run lightweight cleanup on a small percentage of requests to avoid request-time overhead.
        try {
            if (random_int(1, 100) > 3) {
                return;
            }

            $this->db->exec("DELETE FROM auth_otp_codes WHERE (consumed_at IS NOT NULL AND consumed_at < (NOW() - INTERVAL 1 DAY)) OR (verified_at IS NOT NULL AND verified_at < (NOW() - INTERVAL 1 DAY)) OR (expires_at < (NOW() - INTERVAL 1 DAY))");
            $this->db->exec("DELETE FROM auth_login_attempts WHERE created_at < (NOW() - INTERVAL 14 DAY)");
            $this->db->exec("DELETE FROM user_trusted_devices WHERE trusted_until IS NOT NULL AND trusted_until < (NOW() - INTERVAL 30 DAY)");
            $this->db->exec("DELETE FROM auth_sessions WHERE (status <> 'active' AND COALESCE(updated_at, created_at) < (NOW() - INTERVAL 30 DAY)) OR (refresh_expires_at < (NOW() - INTERVAL 30 DAY))");
        } catch (Throwable $e) {
            // non-blocking
        }
    }
}

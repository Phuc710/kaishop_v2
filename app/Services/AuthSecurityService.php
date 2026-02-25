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

    private static bool $schemaReady = false;
    private static bool $pruneAttempted = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSchema();
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
        // IP burst: 10 attempts / 10 minutes
        $ip = $this->clientIp();
        $stmt = $this->db->prepare("SELECT COUNT(*) c FROM auth_login_attempts WHERE action = ? AND ip_address = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt->execute([$action, $ip]);
        $ipCount = (int) $stmt->fetchColumn();
        if ($ipCount >= 10) {
            return ['blocked' => true, 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.'];
        }

        if ($usernameOrEmail !== '') {
            $stmt = $this->db->prepare("SELECT COUNT(*) c FROM auth_login_attempts WHERE action = ? AND username_or_email = ? AND ip_address = ? AND success = 0 AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
            $stmt->execute([$action, mb_substr($usernameOrEmail, 0, 190), $ip]);
            $failCount = (int) $stmt->fetchColumn();
            if ($failCount >= 5) {
                return ['blocked' => true, 'message' => 'Tài khoản này đang bị giới hạn đăng nhập tạm thời do nhập sai nhiều lần.'];
            }
        }

        return null;
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

        $sent = $this->sendOtpEmail((string) $user['email'], (string) ($user['username'] ?? ''), $otpCode, $purpose, $ttlSeconds);
        if (!$sent) {
            $this->db->prepare("DELETE FROM auth_otp_codes WHERE challenge_id = ?")->execute([$challengeId]);
            throw new RuntimeException('Khong the gui OTP qua email. Vui long kiem tra cau hinh SMTP.');
        }

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

    /**
     * Resend login OTP by issuing a new challenge for the same user/device.
     * Applies server-side anti-spam cooldown and burst limits.
     *
     * @return array{ok:bool,status?:int,message:string,challenge_id?:string,expires_in?:int,cooldown_seconds?:int,retry_after_seconds?:int}
     */
    public function resendLoginOtpChallenge(string $challengeId, array $currentDevice, int $ttlSeconds = 300, int $cooldownSeconds = 60): array
    {
        $stmt = $this->db->prepare("SELECT * FROM auth_otp_codes WHERE challenge_id = ? AND purpose = 'login_2fa' LIMIT 1");
        $stmt->execute([$challengeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            return ['ok' => false, 'status' => 404, 'message' => 'Phien xac minh OTP khong hop le. Vui long dang nhap lai.'];
        }

        if (!empty($row['consumed_at']) || !empty($row['verified_at'])) {
            return ['ok' => false, 'status' => 400, 'message' => 'Ma OTP nay da duoc su dung. Vui long dang nhap lai de nhan ma moi.'];
        }

        $expectedDeviceHash = (string) ($row['device_hash'] ?? '');
        $actualDeviceHash = (string) ($currentDevice['device_hash'] ?? '');
        if ($expectedDeviceHash !== '' && $actualDeviceHash !== '' && !hash_equals($expectedDeviceHash, $actualDeviceHash)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Thiet bi gui lai OTP khong khop. Vui long dang nhap lai.'];
        }

        $userStmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([(int) $row['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$user) {
            return ['ok' => false, 'status' => 404, 'message' => 'Tai khoan khong ton tai.'];
        }
        if (trim((string) ($user['email'] ?? '')) === '') {
            return ['ok' => false, 'status' => 400, 'message' => 'Tai khoan chua co email de nhan OTP.'];
        }

        $identity = (string) (($user['username'] ?? '') !== '' ? $user['username'] : ($user['email'] ?? ''));
        if (($limit = $this->checkRateLimit('login_otp_resend')) !== null) {
            return ['ok' => false, 'status' => 429, 'message' => (string) ($limit['message'] ?? 'Ban thao tac qua nhanh.')];
        }

        // Cooldown: only allow 1 successful resend per cooldown window.
        $cooldownStmt = $this->db->prepare("
            SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
            FROM auth_login_attempts
            WHERE action = 'login_otp_resend'
              AND username_or_email = ?
              AND success = 1
              AND created_at >= (NOW() - INTERVAL 10 MINUTE)
            ORDER BY id DESC
            LIMIT 1
        ");
        $cooldownStmt->execute([mb_substr($identity, 0, 190)]);
        $lastAge = $cooldownStmt->fetchColumn();
        if ($lastAge !== false) {
            $ageSeconds = max(0, (int) $lastAge);
            if ($ageSeconds < $cooldownSeconds) {
                $retryAfter = max(1, $cooldownSeconds - $ageSeconds);
                $this->recordLoginAttempt('login_otp_resend', $identity, false, 'cooldown_active');
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Ban vua yeu cau gui lai OTP. Vui long doi them truoc khi thu lai.',
                    'retry_after_seconds' => $retryAfter,
                ];
            }
        }

        // Additional success burst limit (per user+IP) for resend action.
        $burstStmt = $this->db->prepare("
            SELECT COUNT(*) c
            FROM auth_login_attempts
            WHERE action = 'login_otp_resend'
              AND username_or_email = ?
              AND ip_address = ?
              AND success = 1
              AND created_at >= (NOW() - INTERVAL 10 MINUTE)
        ");
        $burstStmt->execute([mb_substr($identity, 0, 190), $this->clientIp()]);
        if ((int) $burstStmt->fetchColumn() >= 5) {
            $this->recordLoginAttempt('login_otp_resend', $identity, false, 'burst_limited');
            return [
                'ok' => false,
                'status' => 429,
                'message' => 'Ban da gui lai OTP qua nhieu lan. Vui long thu lai sau it phut.',
                'retry_after_seconds' => 120,
            ];
        }

        $meta = [];
        if (!empty($row['metadata_json'])) {
            $tmp = json_decode((string) $row['metadata_json'], true);
            if (is_array($tmp)) {
                $meta = $tmp;
            }
        }
        $meta['resend_of'] = $challengeId;
        $meta['resend_count'] = (int) (($meta['resend_count'] ?? 0)) + 1;

        try {
            $newChallenge = $this->createOtpChallenge($user, 'login_2fa', $currentDevice, $meta, $ttlSeconds);
        } catch (Throwable $e) {
            $this->recordLoginAttempt('login_otp_resend', $identity, false, 'send_failed');
            return ['ok' => false, 'status' => 500, 'message' => 'Khong the gui lai OTP luc nay. Vui long thu lai sau.'];
        }

        // Invalidate previous challenge after the new OTP has been sent successfully.
        $this->db->prepare("UPDATE auth_otp_codes SET consumed_at = NOW() WHERE id = ? AND consumed_at IS NULL AND verified_at IS NULL")
            ->execute([(int) $row['id']]);

        $this->recordLoginAttempt('login_otp_resend', $identity, true, 'resent');

        return [
            'ok' => true,
            'message' => 'Da gui lai ma OTP moi den email cua ban.',
            'challenge_id' => (string) $newChallenge['challenge_id'],
            'expires_in' => (int) ($newChallenge['expires_in'] ?? $ttlSeconds),
            'cooldown_seconds' => $cooldownSeconds,
        ];
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
        $this->syncLegacyAdminSession($user);

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
                $this->syncLegacyAdminSession($user);
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

    private function sendOtpEmail(string $email, string $username, string $otpCode, string $purpose, int $ttlSeconds): bool
    {
        return $this->mailService()->sendOtp($email, $username, $otpCode, $purpose, $ttlSeconds);
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
        $this->syncLegacyAdminSession($user);

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

    public function revokeAllUserSessions(int $userId, string $reason = 'manual'): void
    {
        if ($userId <= 0) {
            return;
        }

        $stmt = $this->db->prepare("UPDATE auth_sessions SET status = 'revoked', revoked_at = NOW(), revoke_reason = ?, updated_at = NOW() WHERE user_id = ? AND status = 'active'");
        $stmt->execute([mb_substr($reason, 0, 190), $userId]);

        // Legacy single-session token still exists in users table; clear it too.
        $this->db->prepare("UPDATE users SET session = '' WHERE id = ?")->execute([$userId]);
    }

    private function revokeAllSessionsForUser(int $userId): void
    {
        $this->revokeAllUserSessions($userId, 'new_login');
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

    private function syncLegacyAdminSession(array $user): void
    {
        if ((int) ($user['level'] ?? 0) === 9) {
            $_SESSION['admin'] = (string) ($user['username'] ?? 'admin');
            return;
        }
        unset($_SESSION['admin']);
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
        unset($_SESSION['session'], $_SESSION['username'], $_SESSION['admin']);
        $this->clearAuthCookies();

        Logger::danger('Auth', 'banned_session_blocked', 'Phiên đăng nhập bị chặn do ban', [
            'user_id' => (int) ($user['id'] ?? 0),
            'username' => (string) ($user['username'] ?? ''),
            'ip' => $this->clientIp(),
        ]);
    }

    private function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }
        self::$schemaReady = true;

        $this->ensureIndex('auth_sessions', 'idx_auth_sessions_status_refresh', "ALTER TABLE auth_sessions ADD INDEX idx_auth_sessions_status_refresh (status, refresh_expires_at)");
        $this->ensureIndex('auth_sessions', 'idx_auth_sessions_selector_status', "ALTER TABLE auth_sessions ADD INDEX idx_auth_sessions_selector_status (session_selector, status)");
        $this->ensureIndex('auth_sessions', 'idx_auth_sessions_legacy_session', "ALTER TABLE auth_sessions ADD INDEX idx_auth_sessions_legacy_session (legacy_session_token)");

        $this->ensureIndex('auth_otp_codes', 'idx_auth_otp_challenge_purpose', "ALTER TABLE auth_otp_codes ADD INDEX idx_auth_otp_challenge_purpose (challenge_id, purpose)");
        $this->ensureIndex('auth_otp_codes', 'idx_auth_otp_expires', "ALTER TABLE auth_otp_codes ADD INDEX idx_auth_otp_expires (expires_at)");

        $this->ensureIndex('user_trusted_devices', 'idx_user_device_user_until', "ALTER TABLE user_trusted_devices ADD INDEX idx_user_device_user_until (user_id, trusted_until)");

        $this->ensureIndex('auth_login_attempts', 'idx_auth_attempt_action_user_ip_success_time', "ALTER TABLE auth_login_attempts ADD INDEX idx_auth_attempt_action_user_ip_success_time (action, username_or_email, ip_address, success, created_at)");

        $this->ensureUserColumn('twofa_enabled', "ALTER TABLE users ADD COLUMN twofa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER email");
        $this->ensureUserColumn('password_updated_at', "ALTER TABLE users ADD COLUMN password_updated_at DATETIME DEFAULT NULL AFTER password");
        $this->ensureUserColumn('otpcode_expires_at', "ALTER TABLE users ADD COLUMN otpcode_expires_at DATETIME DEFAULT NULL AFTER otpcode");
    }

    private function ensureIndex(string $table, string $indexName, string $sql): void
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
            $stmt->execute([$table, $indexName]);
            if ((int) $stmt->fetchColumn() === 0) {
                $this->db->exec($sql);
            }
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    private function ensureUserColumn(string $column, string $sql): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
        $stmt->execute([$column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec($sql);
        }
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
            $this->db->exec("UPDATE users SET otpcode = NULL, otpcode_expires_at = NULL WHERE otpcode IS NOT NULL AND otpcode_expires_at IS NOT NULL AND otpcode_expires_at < NOW()");
        } catch (Throwable $e) {
            // non-blocking
        }
    }
}

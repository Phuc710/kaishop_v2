<?php

/**
 * AntiFloodService — Smart Anti-Bot / Anti-Scraping / Rate Limiting + Device Ban
 *
 * Thuật toán: Sliding Window Counter + Progressive Penalty + Auto IP & Device Blacklist
 *
 * Cơ chế hoạt động:
 * 1. Check device fingerprint ban (ks_dv cookie → banned_fingerprints) — TRƯỚC IP
 * 2. Check IP blacklist (ip_blacklist table + file cache)
 * 3. Honeypot: route ẩn → ban IP + device ngay
 * 4. Suspicious header detection → ghi nhận suspicion
 * 5. Rate limit per route group (sliding window)
 * 6. Burst detection (15 req / 5s)
 * 7. Khi bị ban → redirect tới banned.php (không show lý do)
 * 8. Khi device bị ban >= 3 IP khác nhau → ban device vĩnh viễn
 */
class AntiFloodService
{
    private string $storageDir;
    private ?PDO $db = null;

    // ─── Configurable Thresholds ───────────────────────────
    /** @var array<string, array{window: int, softLimit: int, hardLimit: int}> */
    private array $profiles = [
        'public' => ['window' => 60, 'softLimit' => 60, 'hardLimit' => 150],
        'api' => ['window' => 60, 'softLimit' => 30, 'hardLimit' => 80],
        'search' => ['window' => 60, 'softLimit' => 20, 'hardLimit' => 50],
        'admin' => ['window' => 60, 'softLimit' => 120, 'hardLimit' => 300],
        'global' => ['window' => 60, 'softLimit' => 100, 'hardLimit' => 200],
    ];

    private int $burstWindow = 5;
    private int $burstLimit = 15;
    private int $autoBanHours = 24;

    // Device ban threshold: if same device gets banned from N different IPs → permanent device ban
    private int $deviceBanIpThreshold = 3;

    private array $honeypotPaths = [
        '/wp-admin',
        '/wp-login.php',
        '/administrator',
        '/phpmyadmin',
        '/.env.bak',
        '/xmlrpc.php',
        '/api/v1/debug',
    ];

    public function __construct()
    {
        $this->storageDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/storage/antiflood';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        if (class_exists('EnvHelper')) {
            $publicSoft = (int) EnvHelper::get('ANTIFLOOD_PUBLIC_SOFT', 0);
            if ($publicSoft > 0) {
                $this->profiles['public']['softLimit'] = $publicSoft;
            }
            $apiBurstLimit = (int) EnvHelper::get('ANTIFLOOD_BURST_LIMIT', 0);
            if ($apiBurstLimit > 0) {
                $this->burstLimit = $apiBurstLimit;
            }
            $banHours = (int) EnvHelper::get('ANTIFLOOD_BAN_HOURS', 0);
            if ($banHours > 0) {
                $this->autoBanHours = $banHours;
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // PUBLIC — Main Entry Point
    // ═══════════════════════════════════════════════════════

    public function inspect(string $requestPath, string $method): void
    {
        // Skip static assets and banned page itself
        if ($this->isStaticAsset($requestPath) || $requestPath === '/banned') {
            return;
        }

        $ip = $this->clientIp();
        $deviceHash = $this->getDeviceHash();

        // 1. DEVICE BAN CHECK — Stronger than IP, can't be bypassed by VPN
        if ($deviceHash !== '' && $this->isDeviceBanned($deviceHash)) {
            $this->log('device_blocked', $ip, ['device_hash' => $deviceHash]);
            $this->redirectToBannedPage();
            return;
        }

        // 2. IP BLACKLIST CHECK
        if ($this->isBlacklisted($ip)) {
            $this->log('ip_blocked', $ip, ['device_hash' => $deviceHash]);
            $this->redirectToBannedPage();
            return;
        }

        // 3. HONEYPOT — instant ban IP + device
        if ($this->isHoneypotPath($requestPath)) {
            $this->triggerHoneypot($ip, $deviceHash, $requestPath);
            return;
        }

        // 4. SUSPICIOUS HEADERS
        if ($this->hasSuspiciousHeaders()) {
            $this->recordSuspicion($ip, $deviceHash, 'suspicious_headers', $requestPath);
        }

        // 5. RATE LIMIT
        $profileKey = $this->resolveProfile($requestPath, $method);
        $profile = $this->profiles[$profileKey] ?? $this->profiles['global'];
        $counts = $this->recordHit($ip, $profileKey, $profile['window']);

        // 6. BURST DETECTION
        $burstCount = $this->getBurstCount($ip);
        if ($burstCount > $this->burstLimit) {
            $this->recordSuspicion($ip, $deviceHash, 'burst_detected', $requestPath);
            if ($burstCount > $this->burstLimit * 3) {
                $this->autoBlacklist($ip, $deviceHash, "Burst flood: {$burstCount} req/{$this->burstWindow}s on {$requestPath}");
                $this->redirectToBannedPage();
                return;
            }
            $this->blockWith429($profile['window']);
            return;
        }

        // 7. HARD LIMIT → auto-ban IP + track device
        if ($counts >= $profile['hardLimit']) {
            $this->autoBlacklist($ip, $deviceHash, "Hard limit: {$counts}/{$profile['hardLimit']} [{$profileKey}] on {$requestPath}");
            $this->redirectToBannedPage();
            return;
        }

        // 8. SOFT LIMIT → 429
        if ($counts >= $profile['softLimit']) {
            $this->blockWith429($profile['window']);
            return;
        }
    }

    // ═══════════════════════════════════════════════════════
    // DEVICE FINGERPRINT — ks_dv cookie + banned_fingerprints table
    // ═══════════════════════════════════════════════════════

    /**
     * Get device hash from the ks_dv cookie (set by AuthSecurityService).
     * This creates a stable device identifier across sessions and IP changes.
     */
    private function getDeviceHash(): string
    {
        $cookieDevice = trim((string) ($_COOKIE['ks_dv'] ?? ''));
        if ($cookieDevice === '' || !preg_match('/^[a-f0-9]{20,64}$/', $cookieDevice)) {
            return '';
        }
        // Hash the device cookie + user-agent for a more robust fingerprint
        return hash('sha256', 'device:' . $cookieDevice . '|ua:' . $this->userAgent());
    }

    /**
     * Check if a device fingerprint is banned.
     * Uses banned_fingerprints table (same table as admin device ban).
     */
    private function isDeviceBanned(string $deviceHash): bool
    {
        if ($deviceHash === '') {
            return false;
        }

        // File cache for speed
        $cacheFile = $this->storageDir . '/devban_' . substr($deviceHash, 0, 16) . '.flag';
        if (is_file($cacheFile)) {
            $expiresAt = (int) @file_get_contents($cacheFile);
            if ($expiresAt > time()) {
                return true;
            }
            @unlink($cacheFile);
        }

        try {
            $db = $this->getDb();
            if (!$db) {
                return false;
            }
            $stmt = $db->prepare("SELECT id FROM banned_fingerprints WHERE fingerprint_hash = ? LIMIT 1");
            $stmt->execute([$deviceHash]);
            if ($stmt->fetchColumn()) {
                // Cache for 24 hours
                @file_put_contents($cacheFile, (string) (time() + 86400), LOCK_EX);
                return true;
            }
        } catch (Throwable $e) {
            // Non-blocking — table might not exist
        }

        return false;
    }

    /**
     * Auto-ban a device fingerprint.
     * Called when the same device triggers bans from multiple different IPs.
     */
    private function autoDeviceBan(string $deviceHash, string $reason): void
    {
        if ($deviceHash === '') {
            return;
        }

        try {
            $db = $this->getDb();
            if (!$db) {
                return;
            }

            // Check if already banned
            $stmt = $db->prepare("SELECT id FROM banned_fingerprints WHERE fingerprint_hash = ? LIMIT 1");
            $stmt->execute([$deviceHash]);
            if ($stmt->fetchColumn()) {
                return; // Already banned
            }

            $stmt = $db->prepare("INSERT INTO banned_fingerprints (fingerprint_hash, reason, banned_by, created_at) VALUES (?, ?, 'antiflood_system', NOW())");
            $stmt->execute([$deviceHash, mb_substr($reason, 0, 500)]);

            // File cache
            $cacheFile = $this->storageDir . '/devban_' . substr($deviceHash, 0, 16) . '.flag';
            @file_put_contents($cacheFile, (string) (time() + 86400 * 365), LOCK_EX);

            $this->log('device_auto_banned', $this->clientIp(), [
                'device_hash' => $deviceHash,
                'reason' => $reason,
            ]);
        } catch (Throwable $e) {
            error_log('AntiFloodService::autoDeviceBan failed: ' . $e->getMessage());
        }
    }

    /**
     * Track how many different IPs have been banned for this device.
     * If threshold is reached, ban the device permanently.
     */
    private function checkDeviceBanEscalation(string $deviceHash, string $ip): void
    {
        if ($deviceHash === '') {
            return;
        }

        $trackFile = $this->storageDir . '/devtrack_' . substr($deviceHash, 0, 16) . '.json';
        $ips = [];
        if (is_file($trackFile)) {
            $raw = @file_get_contents($trackFile);
            $ips = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        // Add this IP if not already tracked
        if (!in_array($ip, $ips, true)) {
            $ips[] = $ip;
            @file_put_contents($trackFile, json_encode($ips), LOCK_EX);
        }

        // If device has been banned on N+ different IPs → permanent device ban
        if (count($ips) >= $this->deviceBanIpThreshold) {
            $this->autoDeviceBan($deviceHash, "Device banned: triggered from " . count($ips) . " different IPs: " . implode(', ', array_slice($ips, 0, 10)));
        }
    }

    // ═══════════════════════════════════════════════════════
    // IP BLACKLIST
    // ═══════════════════════════════════════════════════════

    public function isBlacklisted(string $ip): bool
    {
        $cacheFile = $this->storageDir . '/blacklist_' . md5($ip) . '.flag';
        if (is_file($cacheFile)) {
            $expiresAt = (int) @file_get_contents($cacheFile);
            if ($expiresAt > time()) {
                return true;
            }
            @unlink($cacheFile);
        }

        try {
            $db = $this->getDb();
            if (!$db) {
                return false;
            }
            $stmt = $db->prepare("SELECT id, expires_at FROM ip_blacklist WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
            $stmt->execute([$ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $expiresTs = $row['expires_at'] ? strtotime($row['expires_at']) : (time() + 86400 * 365);
                @file_put_contents($cacheFile, (string) $expiresTs, LOCK_EX);
                return true;
            }
        } catch (Throwable $e) {
            // Non-blocking
        }

        return false;
    }

    private function autoBlacklist(string $ip, string $deviceHash, string $reason): void
    {
        try {
            $db = $this->getDb();
            if (!$db) {
                return;
            }

            $stmt = $db->prepare("SELECT id FROM ip_blacklist WHERE ip_address = ? LIMIT 1");
            $stmt->execute([$ip]);
            if ($stmt->fetchColumn()) {
                $stmt = $db->prepare("UPDATE ip_blacklist SET reason = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR), updated_at = NOW(), hit_count = hit_count + 1 WHERE ip_address = ?");
                $stmt->execute([mb_substr($reason, 0, 500), $this->autoBanHours, $ip]);
            } else {
                $stmt = $db->prepare("INSERT INTO ip_blacklist (ip_address, reason, banned_at, expires_at, hit_count, user_agent, created_at, updated_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR), 1, ?, NOW(), NOW())");
                $stmt->execute([$ip, mb_substr($reason, 0, 500), $this->autoBanHours, mb_substr($this->userAgent(), 0, 1000)]);
            }

            $cacheFile = $this->storageDir . '/blacklist_' . md5($ip) . '.flag';
            @file_put_contents($cacheFile, (string) (time() + $this->autoBanHours * 3600), LOCK_EX);

            // Track device → escalate to device ban if needed
            $this->checkDeviceBanEscalation($deviceHash, $ip);

            $this->log('ip_auto_banned', $ip, [
                'reason' => $reason,
                'ban_hours' => $this->autoBanHours,
                'device_hash' => $deviceHash,
            ]);
        } catch (Throwable $e) {
            error_log('AntiFloodService::autoBlacklist failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════
    // BLOCK RESPONSES — Redirect to banned.php or 429
    // ═══════════════════════════════════════════════════════

    /**
     * Redirect to the banned page (no reason shown, no JSON).
     * This is used for permanent/semi-permanent bans (403).
     */
    private function redirectToBannedPage(): void
    {
        if (!headers_sent()) {
            $bannedUrl = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/banned.php';
            // Use direct include instead of redirect to avoid redirect loops
            http_response_code(403);
        }

        // Include banned.php directly (cleaner than redirect, no loop risk)
        $bannedFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/banned.php';
        if (is_file($bannedFile)) {
            require $bannedFile;
        } else {
            echo '<h1>Truy cập bị hạn chế</h1><p>Quyền truy cập của bạn đã bị hạn chế.</p>';
        }
        exit;
    }

    /**
     * Block with 429 Too Many Requests (temporary, not a ban).
     * Shows JSON for API compatibility.
     */
    private function blockWith429(int $window): void
    {
        $remaining = max(1, $window - (time() % $window));
        if (!headers_sent()) {
            http_response_code(429);
            header('Retry-After: ' . $remaining);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau ' . $remaining . ' giây.',
            'retry_after' => $remaining,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ═══════════════════════════════════════════════════════
    // RATE LIMIT (file-based sliding window)
    // ═══════════════════════════════════════════════════════

    private function recordHit(string $ip, string $profile, int $window): int
    {
        $bucket = (int) floor(time() / $window);
        $file = $this->storageDir . '/' . md5($ip . '_' . $profile . '_' . $bucket) . '.cnt';
        $count = is_file($file) ? (int) @file_get_contents($file) : 0;
        $count++;
        @file_put_contents($file, (string) $count, LOCK_EX);
        return $count;
    }

    private function getBurstCount(string $ip): int
    {
        $bucket = (int) floor(time() / $this->burstWindow);
        $file = $this->storageDir . '/burst_' . md5($ip . '_' . $bucket) . '.cnt';
        $count = is_file($file) ? (int) @file_get_contents($file) : 0;
        $count++;
        @file_put_contents($file, (string) $count, LOCK_EX);
        return $count;
    }

    // ═══════════════════════════════════════════════════════
    // PROFILE RESOLUTION
    // ═══════════════════════════════════════════════════════

    private function resolveProfile(string $path, string $method): string
    {
        if (strpos($path, '/admin') === 0)
            return 'admin';
        if (strpos($path, '/search') === 0 || strpos($path, '/tim-kiem') === 0)
            return 'search';
        if (strpos($path, '/api/') === 0 || $method === 'POST')
            return 'api';
        if (preg_match('#^/product/\d+$#', $path))
            return 'api';
        return 'public';
    }

    private function isStaticAsset(string $path): bool
    {
        return (bool) preg_match('/\.(css|js|png|jpe?g|gif|webp|svg|woff2?|ttf|eot|ico|map)$/i', $path);
    }

    // ═══════════════════════════════════════════════════════
    // HONEYPOT — ban IP + device
    // ═══════════════════════════════════════════════════════

    private function isHoneypotPath(string $path): bool
    {
        foreach ($this->honeypotPaths as $hp) {
            if ($path === $hp || strpos($path, $hp) === 0) {
                return true;
            }
        }
        return false;
    }

    private function triggerHoneypot(string $ip, string $deviceHash, string $path): void
    {
        $this->autoBlacklist($ip, $deviceHash, "Honeypot: {$path}");

        // Honeypot = instant device ban too (very aggressive)
        if ($deviceHash !== '') {
            $this->autoDeviceBan($deviceHash, "Honeypot triggered: {$path} from IP {$ip}");
        }

        $this->log('honeypot_triggered', $ip, [
            'path' => $path,
            'device_hash' => $deviceHash,
        ]);

        $this->redirectToBannedPage();
    }

    // ═══════════════════════════════════════════════════════
    // SUSPICIOUS HEADERS
    // ═══════════════════════════════════════════════════════

    private function hasSuspiciousHeaders(): bool
    {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || strlen($ua) < 15) {
            return true;
        }
        $botMarkers = ['headlesschrome', 'phantomjs', 'slimerjs', 'puppeteer', 'selenium', 'webdriver', 'httpclient'];
        $uaLower = strtolower($ua);
        foreach ($botMarkers as $marker) {
            if (strpos($uaLower, $marker) !== false) {
                return true;
            }
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════
    // SUSPICION TRACKING — tracks per IP+device
    // ═══════════════════════════════════════════════════════

    private function recordSuspicion(string $ip, string $deviceHash, string $type, string $path): void
    {
        $file = $this->storageDir . '/suspicion_' . md5($ip . $deviceHash) . '.json';
        $data = [];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $data = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        $data[] = ['type' => $type, 'path' => $path, 'time' => date('Y-m-d H:i:s')];
        if (count($data) > 50) {
            $data = array_slice($data, -50);
        }

        $recentCount = 0;
        $oneHourAgo = strtotime('-1 hour');
        foreach ($data as $entry) {
            if (isset($entry['time']) && strtotime($entry['time']) >= $oneHourAgo) {
                $recentCount++;
            }
        }

        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);

        if ($recentCount >= 10) {
            $this->autoBlacklist($ip, $deviceHash, "Suspicion accumulated: {$recentCount}/1h (latest: {$type} on {$path})");
        }
    }

    // ═══════════════════════════════════════════════════════
    // LOGGING
    // ═══════════════════════════════════════════════════════

    private function log(string $action, string $ip, array $extra = []): void
    {
        try {
            if (class_exists('Logger')) {
                Logger::danger('Security', $action, "AntiFlood: {$action}", array_merge([
                    'ip' => $ip,
                    'user_agent' => mb_substr($this->userAgent(), 0, 200),
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                ], $extra));
            }
        } catch (Throwable $e) {
            // Non-blocking
        }
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    private function clientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        return (string) ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function userAgent(): string
    {
        return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    private function getDb(): ?PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }
        try {
            if (class_exists('Database')) {
                $this->db = Database::getInstance()->getConnection();
            }
        } catch (Throwable $e) {
            $this->db = null;
        }
        return $this->db;
    }

    // ═══════════════════════════════════════════════════════
    // CLEANUP
    // ═══════════════════════════════════════════════════════

    public function cleanup(): void
    {
        try {
            foreach (['/*.cnt', '/burst_*.cnt'] as $pattern) {
                $files = glob($this->storageDir . $pattern);
                if (is_array($files)) {
                    $cutoff = time() - 300;
                    foreach ($files as $file) {
                        if (@filemtime($file) < $cutoff)
                            @unlink($file);
                    }
                }
            }

            $flags = glob($this->storageDir . '/blacklist_*.flag');
            if (is_array($flags)) {
                foreach ($flags as $flag) {
                    $expiresAt = (int) @file_get_contents($flag);
                    if ($expiresAt > 0 && $expiresAt < time())
                        @unlink($flag);
                }
            }

            $suspicions = glob($this->storageDir . '/suspicion_*.json');
            if (is_array($suspicions)) {
                $cutoff = time() - 604800;
                foreach ($suspicions as $file) {
                    if (@filemtime($file) < $cutoff)
                        @unlink($file);
                }
            }

            $db = $this->getDb();
            if ($db) {
                $db->exec("DELETE FROM ip_blacklist WHERE expires_at IS NOT NULL AND expires_at < NOW()");
            }
        } catch (Throwable $e) {
            // Non-blocking
        }
    }
}

<?php

class BlacklistController extends Controller
{
    private PDO $db;
    private AuthService $authService;
    private BanService $banService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->authService = new AuthService();
        $this->banService = new BanService();
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            exit('Truy cap bi tu choi');
        }
    }

    public function index(): void
    {
        $this->requireAdmin();

        $search = trim((string) ($_GET['q'] ?? ''));
        $tab = (string) ($_GET['tab'] ?? 'ip');
        if (!in_array($tab, ['ip', 'device', 'admin'], true)) {
            $tab = 'ip';
        }

        $ipBans = $this->fetchIpBans($search);
        $deviceBans = $this->fetchDeviceBans($search);
        $adminBans = $this->banService->getAdminHistory($search);

        $totalIp = (int) $this->db->query("SELECT COUNT(*) FROM `ip_blacklist`")->fetchColumn();
        $activeIp = (int) $this->db->query("SELECT COUNT(*) FROM `ip_blacklist` WHERE `expires_at` IS NULL OR `expires_at` > NOW()")->fetchColumn();
        $totalDevice = (int) $this->db->query("SELECT COUNT(*) FROM `banned_fingerprints`")->fetchColumn();
        $activeDevice = (int) $this->db->query("SELECT COUNT(*) FROM `banned_fingerprints` WHERE `expires_at` IS NULL OR `expires_at` > NOW()")->fetchColumn();
        $adminActive = (int) $this->db->query("SELECT COUNT(*) FROM `ban_history` WHERE `source` = 'admin' AND `status` = 'active' AND (`expires_at` IS NULL OR `expires_at` > NOW())")->fetchColumn();

        require BASE_PATH . '/views/admin/blacklist/index.php';
    }

    public function unban(): void
    {
        $this->requireAdmin();

        $type = (string) ($_POST['type'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $ref = trim((string) ($_POST['ref'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $fingerprint = trim((string) ($_POST['fingerprint'] ?? ''));
        $adminName = (string) ($_SESSION['admin'] ?? 'Admin');

        if ($type === 'ip') {
            $ip = '';
            if ($ref !== '') {
                $stmt = $this->db->prepare("SELECT `ip_address` FROM `ip_blacklist` WHERE `ref_hash` = ? LIMIT 1");
                $stmt->execute([$ref]);
                $ip = (string) ($stmt->fetchColumn() ?: '');
                $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `ref_hash` = ?");
                $stmt->execute([$ref]);
                $this->banService->closeIpBanHistoryByRef($ref, $adminName);
            } elseif ($id > 0) {
                $stmt = $this->db->prepare("SELECT `ip_address` FROM `ip_blacklist` WHERE `id` = ? LIMIT 1");
                $stmt->execute([$id]);
                $ip = (string) ($stmt->fetchColumn() ?: '');
                $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `id` = ?");
                $stmt->execute([$id]);
                if ($ip !== '') {
                    $this->banService->closeIpBanHistoryByIp($ip, $adminName);
                }
            }
            if ($ip !== '') {
                $cacheFile = BASE_PATH . '/storage/antiflood/blacklist_' . md5($ip) . '.flag';
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);
                }
            }
        } elseif ($type === 'device') {
            if ($fingerprint === '' && $id > 0) {
                $stmt = $this->db->prepare("SELECT `fingerprint_hash` FROM `banned_fingerprints` WHERE `id` = ? LIMIT 1");
                $stmt->execute([$id]);
                $fingerprint = (string) ($stmt->fetchColumn() ?: '');
            }
            if ($fingerprint !== '') {
                $this->banService->releaseDeviceBan($fingerprint, $adminName);
                $cacheFile = BASE_PATH . '/storage/antiflood/devban_' . substr($fingerprint, 0, 16) . '.flag';
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);
                }
            }
        } elseif ($type === 'account' && $username !== '') {
            $this->banService->releaseAccountBan($username, $adminName);
        } elseif ($type === 'ref' && $ref !== '') {
            $stmt = $this->db->prepare("SELECT `ip_address` FROM `ip_blacklist` WHERE `ref_hash` = ? LIMIT 1");
            $stmt->execute([$ref]);
            $ip = (string) ($stmt->fetchColumn() ?: '');
            $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `ref_hash` = ?");
            $stmt->execute([$ref]);
            $this->banService->closeIpBanHistoryByRef($ref, $adminName);
            if ($ip !== '') {
                $cacheFile = BASE_PATH . '/storage/antiflood/blacklist_' . md5($ip) . '.flag';
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);
                }
            }
        }

        header('Location: ' . url('admin/blacklist') . '?unban=ok&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
        exit;
    }

    public function clearExpired(): void
    {
        $this->requireAdmin();

        $this->banService->syncExpiredState();
        $this->db->exec("DELETE FROM `ip_blacklist` WHERE `expires_at` IS NOT NULL AND `expires_at` < NOW()");

        $dir = BASE_PATH . '/storage/antiflood';
        $files = glob($dir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        header('Location: ' . url('admin/blacklist') . '?cleared=ok');
        exit;
    }

    private function fetchIpBans(string $search): array
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $this->db->prepare("SELECT * FROM `ip_blacklist` WHERE `ip_address` LIKE ? OR `ref_hash` LIKE ? OR `reason` LIKE ? OR `banned_by` LIKE ? ORDER BY `banned_at` DESC LIMIT 200");
            $stmt->execute([$like, $like, $like, $like]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->db->query("SELECT * FROM `ip_blacklist` ORDER BY `banned_at` DESC LIMIT 200");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchDeviceBans(string $search): array
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $this->db->prepare("SELECT * FROM `banned_fingerprints` WHERE `fingerprint_hash` LIKE ? OR `reason` LIKE ? OR `target_username` LIKE ? OR `banned_by` LIKE ? ORDER BY `created_at` DESC LIMIT 200");
            $stmt->execute([$like, $like, $like, $like]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->db->query("SELECT * FROM `banned_fingerprints` ORDER BY `created_at` DESC LIMIT 200");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

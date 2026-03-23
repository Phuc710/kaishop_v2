<?php

class BlacklistController extends Controller
{
    private PDO $db;
    private AuthService $authService;
    private BanService $banService;
    private ?TimeService $timeService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->authService = new AuthService();
        $this->banService = new BanService();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            exit('Truy cập bị từ chối');
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
        foreach ($ipBans as &$b) {
            $b = $this->attachTimeMeta($b, 'banned_at');
            $b = $this->attachTimeMeta($b, 'expires_at');
        }
        unset($b);

        $deviceBans = $this->fetchDeviceBans($search);
        foreach ($deviceBans as &$b) {
            $b = $this->attachTimeMeta($b, 'created_at');
            $b = $this->attachTimeMeta($b, 'expires_at');
        }
        unset($b);

        $adminBans = $this->banService->getAdminHistory($search);
        foreach ($adminBans as &$b) {
            $b = $this->attachTimeMeta($b, 'started_at');
            $b = $this->attachTimeMeta($b, 'expires_at');
            $b = $this->attachTimeMeta($b, 'ended_at');
        }
        unset($b);

        $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');

        $totalIp = (int) $this->db->query("SELECT COUNT(*) FROM `ip_blacklist`")->fetchColumn();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `ip_blacklist` WHERE `expires_at` IS NULL OR `expires_at` > ?");
        $stmt->execute([$nowSql]);
        $activeIp = (int) $stmt->fetchColumn();

        $totalDevice = (int) $this->db->query("SELECT COUNT(*) FROM `banned_fingerprints`")->fetchColumn();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `banned_fingerprints` WHERE `expires_at` IS NULL OR `expires_at` > ?");
        $stmt->execute([$nowSql]);
        $activeDevice = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `ban_history` WHERE `source` = 'admin' AND `status` = 'active' AND (`expires_at` IS NULL OR `expires_at` > ?)");
        $stmt->execute([$nowSql]);
        $adminActive = (int) $stmt->fetchColumn();

        $summary = [
            'active_ip' => $activeIp,
            'total_ip' => $totalIp,
            'active_device' => $activeDevice,
            'total_device' => $totalDevice,
            'admin_active' => $adminActive
        ];

        require BASE_PATH . '/views/admin/blacklist/index.php';
    }

    private function attachTimeMeta(array $row, string $field): array
    {
        $val = $row[$field] ?? null;
        if ($this->timeService) {
            $meta = $this->timeService->normalizeApiTime($val);
        } else {
            $ts = $val ? strtotime((string) $val) : null;
            $meta = [
                'ts' => $ts,
                'iso' => $ts ? date('c', $ts) : '',
                'display' => $ts ? date('Y-m-d H:i:s', $ts) : ''
            ];
        }
        $row[$field . '_ts'] = $meta['ts'];
        $row[$field . '_iso'] = $meta['iso'];
        $row[$field . '_display'] = $meta['display'];
        return $row;
    }

    public function unban(): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/blacklist'));

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

                if ($ip === '') {
                    header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
                    exit;
                }

                $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `ref_hash` = ?");
                $stmt->execute([$ref]);
                $this->banService->closeIpBanHistoryByRef($ref, $adminName);
            } elseif ($id > 0) {
                $stmt = $this->db->prepare("SELECT `ip_address` FROM `ip_blacklist` WHERE `id` = ? LIMIT 1");
                $stmt->execute([$id]);
                $ip = (string) ($stmt->fetchColumn() ?: '');

                if ($ip === '') {
                    header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
                    exit;
                }

                $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `id` = ?");
                $stmt->execute([$id]);
                $this->banService->closeIpBanHistoryByIp($ip, $adminName);
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

            if ($fingerprint === '' || !$this->banService->isDeviceBanned($fingerprint)) {
                header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'device')));
                exit;
            }

            $this->banService->releaseDeviceBan($fingerprint, $adminName);
            $cacheFile = BASE_PATH . '/storage/antiflood/devban_' . substr($fingerprint, 0, 16) . '.flag';
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
        } elseif ($type === 'account' && $username !== '') {
            if (!$this->banService->isAccountBanned($username)) {
                header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
                exit;
            }
            $this->banService->releaseAccountBan($username, $adminName);
        } elseif ($type === 'ref' && $ref !== '') {
            $stmt = $this->db->prepare("SELECT `ip_address` FROM `ip_blacklist` WHERE `ref_hash` = ? LIMIT 1");
            $stmt->execute([$ref]);
            $ip = (string) ($stmt->fetchColumn() ?: '');

            if ($ip === '') {
                header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
                exit;
            }

            $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `ref_hash` = ?");
            $stmt->execute([$ref]);
            $this->banService->closeIpBanHistoryByRef($ref, $adminName);
            if ($ip !== '') {
                $cacheFile = BASE_PATH . '/storage/antiflood/blacklist_' . md5($ip) . '.flag';
                if (is_file($cacheFile)) {
                    @unlink($cacheFile);
                }
            }
        } else {
            // General fallback if no type/id/ref provided
            header('Location: ' . url('admin/blacklist') . '?unban=error&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
            exit;
        }

        header('Location: ' . url('admin/blacklist') . '?unban=ok&tab=' . urlencode((string) ($_POST['tab'] ?? 'ip')));
        exit;
    }

    public function clearExpired(): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf(url('admin/blacklist'));

        $nowSql = $this->timeService ? $this->timeService->nowSql($this->timeService->getDbTimezone()) : date('Y-m-d H:i:s');
        $this->banService->syncExpiredState();
        $stmt = $this->db->prepare("DELETE FROM `ip_blacklist` WHERE `expires_at` IS NOT NULL AND `expires_at` < ?");
        $stmt->execute([$nowSql]);

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

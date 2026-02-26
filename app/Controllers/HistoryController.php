<?php

/**
 * History Controller
 * Handles user balance history page
 */
class HistoryController extends Controller
{
    private $historyModel;
    private $userModel;
    private $authService;

    public function __construct()
    {
        $this->historyModel = new History();
        $this->userModel = new User();
        $this->authService = new AuthService();
    }

    /**
     * Show history page
     */
    public function index()
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $this->view('profile/history-balance', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => 'history'
        ]);
    }

    /**
     * Data endpoint for DataTables (AJAX)
     */
    public function data()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $this->authService->getCurrentUser();
        $username = $user['username'];

        // DataTables parameters
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
        $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;

        // Custom filters
        $filters = [
            'search' => $_POST['search']['value'] ?? ($_POST['reason'] ?? ''),
            'time_range' => $_POST['time_range'] ?? '',
            'sort_date' => $_POST['sort_date'] ?? 'all'
        ];

        // Get data
        $recordsTotal = $this->historyModel->countUserHistory($username, []);
        $allFilteredRows = $this->historyModel->getAllUserHistory($username, $filters);
        $recordsFiltered = count($allFilteredRows);

        // Fetch fresh user balance
        $userData = $this->userModel->findByUsername($username);
        $currentBalance = (int) ($userData['money'] ?? 0);

        // Stable running balances from current balance in DESC chronological order
        $allCalculatedRows = $this->historyModel->calculateRunningBalances($allFilteredRows, $currentBalance);
        $data = array_slice($allCalculatedRows, $start, $length);

        // Format data for DataTables
        $formattedData = [];
        $timeService = TimeService::instance();
        foreach ($data as $row) {
            $change = (int) ($row['change_amount'] ?? 0);
            $beforeBalance = (int) ($row['before_balance'] ?? 0);
            $afterBalance = (int) ($row['after_balance'] ?? 0);
            $reasonText = (string) ($row['reason_text'] ?? '');
            $eventTime = trim((string) ($row['event_time'] ?? ''));
            $rawTime = $row['raw_time'] ?? null;
            $normalizedTime = $eventTime;
            if ($normalizedTime === '' || $normalizedTime === '0000-00-00 00:00:00') {
                $raw = trim((string) $rawTime);
                if ($raw !== '') {
                    if (ctype_digit($raw)) {
                        $normalizedTime = date('Y-m-d H:i:s', (int) $raw);
                    } else {
                        $normalizedTime = $raw;
                    }
                }
            }
            $timeMeta = $timeService->normalizeApiTime($normalizedTime !== '' ? $normalizedTime : $rawTime);
            $timeDisplay = (string) ($timeMeta['display'] ?? '');
            if ($timeDisplay === '' && $normalizedTime !== '') {
                $timeDisplay = $normalizedTime;
            }
            $timeAgo = $timeMeta['ts'] ? $timeService->diffForHumans($timeMeta['ts']) : ($normalizedTime !== '' ? FormatHelper::timeAgo($normalizedTime) : '');

            $formattedData[] = [
                'time' => FormatHelper::eventTime($row['event_time'] ?? null, $rawTime),
                'time_raw' => $timeDisplay,
                'time_ts' => $timeMeta['ts'],
                'time_iso' => (string) ($timeMeta['iso'] ?? ''),
                'time_iso_utc' => (string) ($timeMeta['iso_utc'] ?? ''),
                'time_ago' => $timeAgo,
                'before' => FormatHelper::initialBalance($beforeBalance),
                'before_amount' => $beforeBalance,
                'change' => FormatHelper::balanceChange($change),
                'change_amount' => $change,
                'after' => FormatHelper::currentBalance($afterBalance),
                'after_amount' => $afterBalance,
                'reason' => htmlspecialchars($reasonText, ENT_QUOTES, 'UTF-8')
            ];
        }

        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);
    }
}

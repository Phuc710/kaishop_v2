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

        $this->view('profile/history', [
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
        $recordsFiltered = $this->historyModel->countUserHistory($username, $filters);
        $data = $this->historyModel->getUserHistory($username, $filters, $length, $start);

        // Fetch fresh user balance
        $userData = $this->userModel->findByUsername($username);
        $currentBalance = (int) ($userData['money'] ?? 0);

        // Format data for DataTables
        $formattedData = [];
        foreach ($data as $row) {
            $id = (int) $row['id'];
            $change = (int) $row['gia'];

            // Calculate Balance After this record
            // Balance_After = Current_Balance - (Sum of changes for all records newer than this one)
            $sumNewer = $this->historyModel->getSumGiaAfter($username, $id);
            $afterBalance = $currentBalance - $sumNewer;
            $beforeBalance = $afterBalance - $change;

            // Format amount with color
            if ($change > 0) {
                $changeHtml = '<span style="color: #198754; font-weight: bold;">+' . tien($change) . 'đ</span>';
            } elseif ($change < 0) {
                // Đỏ đô (Dark Red)
                $changeHtml = '<span style="color: #a52a2a; font-weight: bold;">' . tien($change) . 'đ</span>';
            } else {
                $changeHtml = '<span class="fw-bold">' . tien($change) . 'đ</span>';
            }

            $formattedData[] = [
                'time' => $row['created_at'] ?? $row['time'],
                'before' => '<span style="color: #000000; font-weight: 500;">' . tien($beforeBalance) . 'đ</span>',
                'change' => $changeHtml,
                'after' => '<span style="color: #0000ff; font-weight: bold;">' . tien($afterBalance) . 'đ</span>',
                'reason' => htmlspecialchars($row['hoatdong'])
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

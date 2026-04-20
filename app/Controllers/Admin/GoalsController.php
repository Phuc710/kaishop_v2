<?php

/**
 * Admin Goals Controller
 * Quản lý mục tiêu tài chính — Financial Goal Tracking System
 * Route prefix: /admin/goals
 */
class GoalsController extends Controller
{
    private PDO $db;
    private $authService;
    private ?TimeService $timeService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->db = Database::getInstance()->getConnection();
        $this->timeService = class_exists('TimeService') ? TimeService::instance() : null;
    }

    private function requireAdmin(): void
    {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if (!isset($user['level']) || (int) $user['level'] !== 9) {
            http_response_code(403);
            die('Truy cập bị từ chối — Chỉ dành cho quản trị viên.');
        }
    }

    /**
     * GET /admin/goals — Trang chính danh sách mục tiêu
     */
    public function index(): void
    {
        $this->requireAdmin();
        global $chungapi;

        $statusFilter = $this->get('status', 'all');
        $goals        = $this->fetchGoals($statusFilter);

        $stats = $this->fetchGoalStats();

        $this->view('admin/goals/index', [
            'chungapi'     => $chungapi,
            'goals'        => $goals,
            'stats'        => $stats,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * GET /admin/goals/detail/{id} — JSON detail + transactions
     */
    public function detail(int $id): void
    {
        $this->requireAdmin();

        $goal = $this->findGoal($id);
        if (!$goal) {
            $this->json(['success' => false, 'message' => 'Mục tiêu không tồn tại!']);
            return;
        }

        $transactions = $this->fetchTransactions($id);
        $tags         = $this->fetchTags($id);

        $this->json([
            'success'      => true,
            'goal'         => $this->enrichGoal($goal),
            'transactions' => $transactions,
            'tags'         => $tags,
        ]);
    }

    /**
     * POST /admin/goals/create — Tạo mục tiêu mới
     */
    public function create(): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $name    = trim((string) $this->post('name'));
        $target  = (int) $this->post('target_amount');
        $deadline = trim((string) $this->post('deadline'));
        $emoji   = trim((string) $this->post('emoji')) ?: '🎯';
        $color   = trim((string) $this->post('color')) ?: '#845adf';
        $tagsRaw = trim((string) $this->post('tags'));

        if ($name === '') {
            $this->json(['success' => false, 'message' => 'Tên mục tiêu không được trống!']);
            return;
        }

        if ($target <= 0) {
            $this->json(['success' => false, 'message' => 'Số tiền mục tiêu phải lớn hơn 0!']);
            return;
        }

        $deadlineValue = ($deadline !== '' && strtotime($deadline) !== false) ? $deadline : null;

        $stmt = $this->db->prepare(
            "INSERT INTO `goals` (`name`, `target_amount`, `deadline`, `emoji`, `color`, `created_at`, `updated_at`)
             VALUES (:name, :target, :deadline, :emoji, :color, NOW(), NOW())"
        );
        $stmt->execute([
            'name'     => $name,
            'target'   => $target,
            'deadline' => $deadlineValue,
            'emoji'    => $emoji,
            'color'    => $color,
        ]);
        $goalId = (int) $this->db->lastInsertId();

        // Save tags
        if ($tagsRaw !== '') {
            $this->saveTags($goalId, $tagsRaw);
        }

        $goal = $this->findGoal($goalId);
        $this->json([
            'success' => true,
            'message' => 'Tạo mục tiêu thành công!',
            'goal'    => $this->enrichGoal($goal),
            'stats'   => $this->fetchGoalStats(),
        ]);
    }

    /**
     * POST /admin/goals/update/{id} — Cập nhật mục tiêu
     */
    public function update(int $id): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $goal = $this->findGoal($id);
        if (!$goal) {
            $this->json(['success' => false, 'message' => 'Mục tiêu không tồn tại!']);
            return;
        }

        $name     = trim((string) $this->post('name'));
        $target   = (int) $this->post('target_amount');
        $deadline = trim((string) $this->post('deadline'));
        $emoji    = trim((string) $this->post('emoji')) ?: '🎯';
        $color    = trim((string) $this->post('color')) ?: '#845adf';
        $tagsRaw  = trim((string) $this->post('tags'));

        if ($name === '' || $target <= 0) {
            $this->json(['success' => false, 'message' => 'Thông tin không hợp lệ!']);
            return;
        }

        $deadlineValue = ($deadline !== '' && strtotime($deadline) !== false) ? $deadline : null;

        $this->db->prepare(
            "UPDATE `goals` SET `name`=:name, `target_amount`=:target, `deadline`=:deadline,
             `emoji`=:emoji, `color`=:color, `updated_at`=NOW() WHERE `id`=:id"
        )->execute([
            'name' => $name, 'target' => $target, 'deadline' => $deadlineValue,
            'emoji' => $emoji, 'color' => $color, 'id' => $id,
        ]);

        // Update tags
        $this->db->prepare("DELETE FROM `goal_tags` WHERE `goal_id` = ?")->execute([$id]);
        if ($tagsRaw !== '') {
            $this->saveTags($id, $tagsRaw);
        }

        // Re-sync status
        $this->syncGoalStatus($id);

        $goal = $this->findGoal($id);
        $this->json([
            'success' => true, 
            'message' => 'Cập nhật thành công!', 
            'goal' => $this->enrichGoal($goal),
            'stats' => $this->fetchGoalStats()
        ]);
    }

    /**
     * POST /admin/goals/delete/{id} — Xóa mục tiêu
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $goal = $this->findGoal($id);
        if (!$goal) {
            $this->json(['success' => false, 'message' => 'Mục tiêu không tồn tại!']);
            return;
        }

        $this->db->prepare("DELETE FROM `goal_tags` WHERE `goal_id` = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM `goal_transactions` WHERE `goal_id` = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM `goals` WHERE `id` = ?")->execute([$id]);

        $this->json([
            'success' => true, 
            'message' => 'Đã xóa mục tiêu!',
            'stats'   => $this->fetchGoalStats()
        ]);
    }

    /**
     * POST /admin/goals/transaction/{id} — Thêm giao dịch + / -
     */
    public function addTransaction(int $id): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $goal = $this->findGoal($id);
        if (!$goal) {
            $this->json(['success' => false, 'message' => 'Mục tiêu không tồn tại!']);
            return;
        }

        $type   = $this->post('type') === 'subtract' ? 'subtract' : 'add';
        $amount = (int) $this->post('amount');
        $note   = trim((string) $this->post('note'));

        if ($amount <= 0) {
            $this->json(['success' => false, 'message' => 'Số tiền phải lớn hơn 0!']);
            return;
        }

        // Insert transaction
        $this->db->prepare(
            "INSERT INTO `goal_transactions` (`goal_id`, `type`, `amount`, `note`, `created_at`)
             VALUES (:goal_id, :type, :amount, :note, NOW())"
        )->execute(['goal_id' => $id, 'type' => $type, 'amount' => $amount, 'note' => $note ?: null]);

        // Recalculate current_amount from transactions
        $this->recalcCurrentAmount($id);
        $this->syncGoalStatus($id);

        $goal = $this->findGoal($id);
        $this->json([
            'success' => true,
            'message' => ($type === 'add' ? '+ Đã thêm' : '- Đã trừ') . ' ' . number_format($amount, 0, ',', '.') . 'đ',
            'goal'    => $this->enrichGoal($goal),
            'stats'   => $this->fetchGoalStats(),
        ]);
    }

    /**
     * POST /admin/goals/transaction/delete/{id} — Xóa 1 transaction
     */
    public function deleteTransaction(int $txId): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $stmt = $this->db->prepare("SELECT * FROM `goal_transactions` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$txId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại!']);
            return;
        }

        $this->db->prepare("DELETE FROM `goal_transactions` WHERE `id` = ?")->execute([$txId]);

        $goalId = (int) $tx['goal_id'];
        $this->recalcCurrentAmount($goalId);
        $this->syncGoalStatus($goalId);

        $goal = $this->findGoal($goalId);
        $this->json([
            'success' => true,
            'message' => 'Đã xóa giao dịch!',
            'goal'    => $goal ? $this->enrichGoal($goal) : null,
            'stats'   => $this->fetchGoalStats(),
        ]);
    }

    /**
     * POST /admin/goals/transaction/update/{id} — Cập nhật giao dịch
     */
    public function updateTransaction(int $txId): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $stmt = $this->db->prepare("SELECT * FROM `goal_transactions` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$txId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            $this->json(['success' => false, 'message' => 'Giao dịch không tồn tại!']);
            return;
        }

        $amount = (int) $this->post('amount');
        $note   = trim((string) $this->post('note'));

        if ($amount <= 0) {
            $this->json(['success' => false, 'message' => 'Số tiền phải lớn hơn 0!']);
            return;
        }

        $this->db->prepare(
            "UPDATE `goal_transactions` SET `amount` = :amount, `note` = :note WHERE `id` = :id"
        )->execute(['amount' => $amount, 'note' => $note, 'id' => $txId]);

        $goalId = (int) $tx['goal_id'];
        $this->recalcCurrentAmount($goalId);
        $this->syncGoalStatus($goalId);

        $goal = $this->findGoal($goalId);
        $this->json([
            'success' => true,
            'message' => 'Đã cập nhật giao dịch!',
            'goal'    => $goal ? $this->enrichGoal($goal) : null,
            'stats'   => $this->fetchGoalStats(),
        ]);
    }

    /**
     * POST /admin/goals/note/{id} — Lưu ghi chú
     */
    public function saveNote(int $id): void
    {
        $this->requireAdmin();
        $this->rejectInvalidCsrf('', true);

        $goal = $this->findGoal($id);
        if (!$goal) {
            $this->json(['success' => false, 'message' => 'Mục tiêu không tồn tại!']);
            return;
        }

        $note = (string) $this->post('note');
        $this->db->prepare("UPDATE `goals` SET `note`=:note, `updated_at`=NOW() WHERE `id`=:id")
                 ->execute(['note' => $note, 'id' => $id]);

        $this->json(['success' => true, 'message' => 'Đã lưu ghi chú!']);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * @return array<int, array<string,mixed>>
     */
    private function fetchGoals(string $statusFilter): array
    {
        $where  = '1=1';
        $params = [];

        if ($statusFilter === 'active') {
            $where = "`status` = 'active'";
        } elseif ($statusFilter === 'completed') {
            $where = "`status` = 'completed'";
        }

        $stmt = $this->db->prepare(
            "SELECT g.*,
                    (SELECT GROUP_CONCAT(t.`tag_name` ORDER BY t.`id` SEPARATOR ',')
                     FROM `goal_tags` t WHERE t.`goal_id` = g.`id`) AS tags_csv,
                    (SELECT MAX(gt.`created_at`)
                     FROM `goal_transactions` gt WHERE gt.`goal_id` = g.`id`) AS last_tx_at
             FROM `goals` g
             WHERE {$where}
             ORDER BY
                CASE WHEN g.`status` = 'completed' THEN 1 ELSE 0 END ASC,
                g.`created_at` DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'enrichGoal'], $rows);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findGoal(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `goals` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Enrich a goal row with computed fields.
     * @param array<string,mixed> $goal
     * @return array<string,mixed>
     */
    private function enrichGoal(array $goal): array
    {
        $target  = max(1, (int) ($goal['target_amount'] ?? 1));
        $current = (int) ($goal['current_amount'] ?? 0);
        $current = max(0, $current);

        $percent  = min(100, round(($current / $target) * 100, 1));
        $shortage = max(0, $target - $current);

        // AI Suggest: days left
        $suggest = '';
        $deadline = (string) ($goal['deadline'] ?? '');
        if ($deadline && strtotime($deadline) > time() && $shortage > 0) {
            $daysLeft = (int) round((strtotime($deadline) - time()) / 86400);
            if ($daysLeft > 0) {
                $perDay = (int) ceil($shortage / $daysLeft);
                $suggest = "Còn {$daysLeft} ngày → cần tiết kiệm " . number_format($perDay, 0, ',', '.') . 'đ/ngày';
            }
        }

        // Dead goal: no update in 7 days (check last_tx_at or updated_at)
        $lastActivity = $goal['last_tx_at'] ?? $goal['updated_at'] ?? null;
        $isDead = (string) ($goal['status'] ?? '') === 'active'
               && $lastActivity
               && (time() - strtotime((string) $lastActivity)) > (7 * 86400);

        $isHot = (string) ($goal['status'] ?? '') === 'active' && $percent >= 80 && $percent < 100;

        // Format tags
        $tagsCsv = (string) ($goal['tags_csv'] ?? '');
        $tags    = $tagsCsv !== '' ? explode(',', $tagsCsv) : [];

        return array_merge($goal, [
            'current_amount'  => $current,
            'target_amount'   => $target,
            'percent'         => $percent,
            'shortage'        => $shortage,
            'shortage_fmt'    => number_format($shortage, 0, ',', '.') . 'đ',
            'current_fmt'     => number_format($current, 0, ',', '.') . 'đ',
            'target_fmt'      => number_format($target, 0, ',', '.') . 'đ',
            'suggest'         => $suggest,
            'insights'        => $this->generateInsights($goal, $current, $target, $shortage),
            'is_dead'         => $isDead,
            'is_hot'          => $isHot,
            'tags'            => $tags,
            'deadline_fmt'    => $deadline ? date('d/m/Y', strtotime($deadline)) : null,
        ]);
    }

    /**
     * AI Insight Engine (Đỉnh cao)
     * Phân tích dữ liệu và đưa ra lời khuyên thông minh.
     */
    private function generateInsights(array $goal, int $current, int $target, int $shortage): array
    {
        $insights = [];
        $pct      = $target > 0 ? ($current / $target) * 100 : 0;
        $deadline = (string) ($goal['deadline'] ?? '');
        $status   = (string) ($goal['status'] ?? 'active');
        $isDone   = $status === 'completed';

        if ($isDone) {
            $insights[] = ['level' => 'success', 'icon' => 'fas fa-trophy', 'text' => 'Chúc mừng! Bạn đã hoàn thành xuất sắc mục tiêu này.'];
            return $insights;
        }

        // 1. Milestone messages
        if ($pct >= 75) {
            $insights[] = ['level' => 'success', 'icon' => 'fas fa-rocket', 'text' => 'Sắp về đích rồi! Bạn đã đi được 3/4 chặng đường.'];
        } elseif ($pct >= 50) {
            $insights[] = ['level' => 'info', 'icon' => 'fas fa-medal', 'text' => 'Tuyệt vời! Bạn đã vượt qua một nửa chặng đường.'];
        } elseif ($pct >= 25) {
            $insights[] = ['level' => 'info', 'icon' => 'fas fa-flag', 'text' => 'Bắt đầu có đà rồi đấy! Cố gắng duy trì nhé.'];
        }

        // 2. Deadline & Pace Analysis
        if ($deadline && strtotime($deadline) > time() && $shortage > 0) {
            $daysLeft = (int) round((strtotime($deadline) - time()) / 86400);
            if ($daysLeft > 0) {
                // Pace check
                $dailyNeeded = (int) ceil($shortage / $daysLeft);
                
                if ($daysLeft <= 7) {
                    $insights[] = ['level' => 'danger', 'icon' => 'fas fa-exclamation-triangle', 'text' => "KHẨN CẤP: Chỉ còn {$daysLeft} ngày! Cần tập trung ngân sách cho mục tiêu này."];
                }

                // Smart Tip: Nhịn trà sữa / cafe
                if ($dailyNeeded > 50000) {
                    $insights[] = ['level' => 'info', 'icon' => 'fas fa-lightbulb', 'text' => 'Mẹo: Nếu bớt chi tiêu lặt vặt (cafe, trà sữa), bạn sẽ giảm bớt áp lực tài chính mỗi ngày.'];
                }
            } else {
                $insights[] = ['level' => 'danger', 'icon' => 'fas fa-clock', 'text' => 'Mục tiêu đã quá hạn nhưng chưa hoàn thành! Hãy cập nhật hạn mới.'];
            }
        }

        // 3. Activity Analysis
        $lastActivity = $goal['last_tx_at'] ?? $goal['updated_at'] ?? null;
        if ($lastActivity && (time() - strtotime((string) $lastActivity)) > (10 * 86400)) {
            $insights[] = ['level' => 'warning', 'icon' => 'fas fa-bed', 'text' => 'Mục tiêu này đang bị đóng băng lâu ngày. Đừng bỏ cuộc giữa chừng nhé!'];
        }



        return $insights;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function fetchTransactions(int $goalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `goal_transactions` WHERE `goal_id` = ? ORDER BY `created_at` DESC LIMIT 200"
        );
        $stmt->execute([$goalId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['amount_fmt'] = number_format((int) $row['amount'], 0, ',', '.') . 'đ';
            $row['created_fmt'] = $row['created_at']
                ? date('H:i d/m/Y', strtotime($row['created_at']))
                : '--';
        }
        unset($row);

        return $rows;
    }

    /**
     * @return string[]
     */
    private function fetchTags(int $goalId): array
    {
        $stmt = $this->db->prepare("SELECT `tag_name` FROM `goal_tags` WHERE `goal_id` = ?");
        $stmt->execute([$goalId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');
    }

    private function saveTags(int $goalId, string $tagsRaw): void
    {
        $tags = array_filter(array_map('trim', explode(',', str_replace(['#', ';'], [' ', ','], $tagsRaw))));
        $stmt = $this->db->prepare("INSERT IGNORE INTO `goal_tags` (`goal_id`, `tag_name`) VALUES (?, ?)");
        foreach (array_unique($tags) as $tag) {
            if ($tag !== '') {
                $stmt->execute([$goalId, mb_substr($tag, 0, 50)]);
            }
        }
    }

    private function recalcCurrentAmount(int $goalId): void
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN `type` = 'add'      THEN `amount` ELSE 0 END), 0) AS add_total,
                COALESCE(SUM(CASE WHEN `type` = 'subtract' THEN `amount` ELSE 0 END), 0) AS sub_total
             FROM `goal_transactions` WHERE `goal_id` = ?"
        );
        $stmt->execute([$goalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $current = max(0, (int) ($row['add_total'] ?? 0) - (int) ($row['sub_total'] ?? 0));

        $this->db->prepare(
            "UPDATE `goals` SET `current_amount` = :current, `updated_at` = NOW() WHERE `id` = :id"
        )->execute(['current' => $current, 'id' => $goalId]);
    }

    private function syncGoalStatus(int $goalId): void
    {
        $goal = $this->findGoal($goalId);
        if (!$goal) {
            return;
        }

        $target  = (int) ($goal['target_amount'] ?? 0);
        $current = (int) ($goal['current_amount'] ?? 0);
        $status  = (string) ($goal['status'] ?? 'active');

        if ($status === 'archived') {
            return; // Never auto-change archived
        }

        $newStatus = ($target > 0 && $current >= $target) ? 'completed' : 'active';
        if ($newStatus !== $status) {
            $this->db->prepare("UPDATE `goals` SET `status`=:s, `updated_at`=NOW() WHERE `id`=:id")
                     ->execute(['s' => $newStatus, 'id' => $goalId]);
        }
    }

    /**
     * @return array<string,int>
     */
    private function fetchGoalStats(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN `status` = 'active'    THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) AS completed,
                    COALESCE(SUM(`target_amount`),  0) AS total_target,
                    COALESCE(SUM(`current_amount`), 0) AS total_current
                 FROM `goals`"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            return [
                'total'         => (int) ($row['total']         ?? 0),
                'active'        => (int) ($row['active']        ?? 0),
                'completed'     => (int) ($row['completed']     ?? 0),
                'total_target'  => (int) ($row['total_target']  ?? 0),
                'total_current' => (int) ($row['total_current'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'active' => 0, 'completed' => 0, 'total_target' => 0, 'total_current' => 0];
        }
    }
}

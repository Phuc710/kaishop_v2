<?php

/**
 * ChatGptAdminViewService
 * Prepares shared UI data for GPT Business admin pages.
 */
class ChatGptAdminViewService
{
    /**
     * @param array<int, array<string, mixed>> $farms
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $orderStats
     * @return array<string, mixed>
     */
    public function buildFarmPageData($farms, $stats, $orderStats)
    {
        return [
            'farms' => $this->decorateFarms($farms),
            'summaryCards' => [
                $this->makeSummaryCard('Tong farm', (int) ($stats['total_farms'] ?? 0), 'fas fa-server', 'primary', 'So farm dang quan ly'),
                $this->makeSummaryCard('Tong slot', (int) ($stats['total_seats'] ?? 0), 'fas fa-layer-group', 'info', 'Tong suc chua cua tat ca farm'),
                $this->makeSummaryCard('Da su dung', (int) ($stats['used_seats'] ?? 0), 'fas fa-user-check', 'warning', 'Slot da giao cho khach'),
                $this->makeSummaryCard('Con trong', (int) ($stats['available_seats'] ?? 0), 'fas fa-chair', 'success', 'Slot san sang ban tiep'),
            ],
            'quickLinks' => [
                [
                    'href' => url('admin/chatgpt/orders'),
                    'label' => 'Don hang GPT',
                    'description' => 'Theo doi don dang moi va da kich hoat.',
                    'icon' => 'fas fa-box-open',
                    'meta' => (int) ($orderStats['total'] ?? 0) . ' don',
                ],
                [
                    'href' => url('admin/chatgpt/members'),
                    'label' => 'Thanh vien Farm',
                    'description' => 'Snapshot thanh vien hien co trong tung farm.',
                    'icon' => 'fas fa-users',
                    'meta' => (int) ($stats['used_seats'] ?? 0) . ' slot dang dung',
                ],
                [
                    'href' => url('admin/chatgpt/logs'),
                    'label' => 'Audit Logs',
                    'description' => 'Lich su invite, sync va thao tac quan tri.',
                    'icon' => 'fas fa-clipboard-list',
                    'meta' => (int) ($orderStats['failed_count'] ?? 0) . ' loi can xem',
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    public function buildOrdersPageData($orders, $stats)
    {
        return [
            'orders' => $this->decorateOrders($orders),
            'summaryCards' => [
                $this->makeSummaryCard('Tong don', (int) ($stats['total'] ?? 0), 'fas fa-shopping-cart', 'primary', 'Tong so don GPT Business'),
                $this->makeSummaryCard('Dang active', (int) ($stats['active_count'] ?? 0), 'fas fa-check-circle', 'success', 'Don da vao farm thanh cong'),
                $this->makeSummaryCard('Dang moi', (int) ($stats['inviting_count'] ?? 0), 'fas fa-paper-plane', 'info', 'Don da gui invite cho khach'),
                $this->makeSummaryCard('Cho xu ly', (int) ($stats['pending_count'] ?? 0), 'fas fa-clock', 'warning', 'Don dang cho he thong xu ly'),
                $this->makeSummaryCard('Su co', (int) (($stats['failed_count'] ?? 0) + ($stats['revoked_count'] ?? 0)), 'fas fa-exclamation-triangle', 'danger', 'Don loi hoac bi revoke'),
            ],
            'orderStatusOptions' => $this->getOrderStatusOptions(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $members
     * @return array<string, mixed>
     */
    public function buildMembersPageData($members)
    {
        $approved = 0;
        $unknown = 0;
        $removed = 0;

        foreach ($members as $member) {
            if (($member['source'] ?? '') === 'approved') {
                $approved++;
            } else {
                $unknown++;
            }
            if (($member['status'] ?? '') === 'removed') {
                $removed++;
            }
        }

        return [
            'members' => $this->decorateMembers($members),
            'summaryCards' => [
                $this->makeSummaryCard('Tong thanh vien', count($members), 'fas fa-users', 'primary', 'So ban ghi snapshot hien co'),
                $this->makeSummaryCard('Da duyet', $approved, 'fas fa-user-shield', 'success', 'Thanh vien dung luong hop le'),
                $this->makeSummaryCard('Unknown', $unknown, 'fas fa-user-secret', 'warning', 'Thanh vien phat hien chua duyet'),
                $this->makeSummaryCard('Da roi farm', $removed, 'fas fa-user-minus', 'danger', 'Ban ghi da bi danh dau removed'),
            ],
            'memberSourceOptions' => $this->getMemberSourceOptions(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $logs
     * @param array<int, string> $actionTypes
     * @return array<string, mixed>
     */
    public function buildLogsPageData($logs, $actionTypes)
    {
        $okCount = 0;
        $failCount = 0;
        foreach ($logs as $log) {
            if (strtoupper((string) ($log['result'] ?? '')) === 'FAIL') {
                $failCount++;
            } else {
                $okCount++;
            }
        }

        return [
            'logs' => $this->decorateLogs($logs),
            'summaryCards' => [
                $this->makeSummaryCard('Tong log', count($logs), 'fas fa-clipboard-list', 'primary', 'Ban ghi audit dang hien thi'),
                $this->makeSummaryCard('Ket qua OK', $okCount, 'fas fa-check-double', 'success', 'Tac vu hoan tat thanh cong'),
                $this->makeSummaryCard('Ket qua FAIL', $failCount, 'fas fa-bug', 'danger', 'Su co can kiem tra them'),
                $this->makeSummaryCard('Loai action', count($actionTypes), 'fas fa-sitemap', 'info', 'So action type khac nhau'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getOrderStatusOptions()
    {
        return [
            'pending' => 'Chờ xử lý',
            'inviting' => 'Đã mời',
            'active' => 'Đang active',
            'failed' => 'Lỗi',
            'revoked' => 'Đã revoke',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getMemberSourceOptions()
    {
        return [
            'approved' => 'Approved',
            'detected_unknown' => 'Unknown',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $farms
     * @return array<int, array<string, mixed>>
     */
    private function decorateFarms($farms)
    {
        $output = [];
        foreach ($farms as $farm) {
            $used = (int) ($farm['seat_used'] ?? 0);
            $total = max(1, (int) ($farm['seat_total'] ?? 0));
            $percent = (int) round(($used / $total) * 100);
            $statusTone = $this->resolveFarmStatusTone((string) ($farm['status'] ?? 'active'));
            $meterTone = $percent >= 100 ? 'danger' : ($percent >= 75 ? 'warning' : 'success');

            $farm['seat_summary'] = $used . ' / ' . $total;
            $farm['seat_percent'] = $percent;
            $farm['status_label'] = $this->titleize((string) ($farm['status'] ?? 'active'));
            $farm['status_badge_class'] = 'gptb-badge gptb-badge--' . $statusTone;
            $farm['seat_fill_class'] = 'gptb-seat-fill gptb-seat-fill--' . $meterTone;
            $farm['last_sync_display'] = $this->formatDateTime($farm['last_sync_at'] ?? null, 'd/m/Y H:i');
            $output[] = $farm;
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     * @return array<int, array<string, mixed>>
     */
    private function decorateOrders($orders)
    {
        $statusMap = [
            'pending' => ['label' => 'Chờ xử lý', 'tone' => 'warning'],
            'inviting' => ['label' => 'Đã mời', 'tone' => 'info'],
            'active' => ['label' => 'Active', 'tone' => 'success'],
            'failed' => ['label' => 'Lỗi', 'tone' => 'danger'],
            'revoked' => ['label' => 'Revoked', 'tone' => 'muted'],
        ];

        $output = [];
        foreach ($orders as $order) {
            $status = (string) ($order['status'] ?? 'pending');
            $meta = $statusMap[$status] ?? ['label' => $status, 'tone' => 'muted'];
            $order['status_label'] = $meta['label'];
            $order['status_badge_class'] = 'gptb-badge gptb-badge--' . $meta['tone'];
            $order['farm_display'] = trim((string) ($order['farm_name'] ?? '')) !== '' ? (string) $order['farm_name'] : 'Chưa gán farm';
            $order['expires_at_display'] = $this->formatDateTime($order['expires_at'] ?? null, 'd/m/Y');
            $order['created_at_display'] = $this->formatDateTime($order['created_at'] ?? null, 'd/m/Y H:i');
            $output[] = $order;
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $members
     * @return array<int, array<string, mixed>>
     */
    private function decorateMembers($members)
    {
        $output = [];
        foreach ($members as $member) {
            $source = (string) ($member['source'] ?? 'detected_unknown');
            $status = (string) ($member['status'] ?? 'active');

            $member['source_label'] = $source === 'approved' ? 'Approved' : 'Unknown';
            $member['source_badge_class'] = 'gptb-badge gptb-badge--' . ($source === 'approved' ? 'success' : 'warning');
            $member['status_label'] = $status === 'removed' ? 'Removed' : 'Active';
            $member['status_badge_class'] = 'gptb-badge gptb-badge--' . ($status === 'removed' ? 'danger' : 'info');
            $member['role_badge_class'] = 'gptb-badge gptb-badge--primary';
            $member['first_seen_display'] = $this->formatDateTime($member['first_seen_at'] ?? null, 'd/m/Y H:i');
            $member['last_seen_display'] = $this->formatDateTime($member['last_seen_at'] ?? null, 'd/m/Y H:i');
            $output[] = $member;
        }

        return $output;
    }

    /**
     * @param array<int, array<string, mixed>> $logs
     * @return array<int, array<string, mixed>>
     */
    private function decorateLogs($logs)
    {
        $output = [];
        foreach ($logs as $log) {
            $action = (string) ($log['action'] ?? '');
            $actionTone = $this->resolveAuditActionTone($action);
            $result = strtoupper((string) ($log['result'] ?? 'OK'));

            $log['action_badge_class'] = 'gptb-badge gptb-badge--' . $actionTone;
            $log['result_badge_class'] = 'gptb-badge gptb-badge--' . ($result === 'FAIL' ? 'danger' : 'success');
            $log['result_label'] = $result !== '' ? $result : 'OK';
            $log['created_at_display'] = $this->formatDateTime($log['created_at'] ?? null, 'd/m/Y H:i:s');
            $output[] = $log;
        }

        return $output;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeSummaryCard($label, $value, $icon, $tone, $hint)
    {
        return [
            'label' => $label,
            'value' => $value,
            'icon' => $icon,
            'tone' => $tone,
            'hint' => $hint,
        ];
    }

    private function resolveFarmStatusTone($status)
    {
        switch ($status) {
            case 'full':
                return 'warning';
            case 'locked':
                return 'danger';
            default:
                return 'success';
        }
    }

    private function resolveAuditActionTone($action)
    {
        switch ($action) {
            case 'ORDER_ACTIVATED':
            case 'FARM_ADDED':
                return 'success';
            case 'SYSTEM_INVITE_CREATED':
                return 'info';
            case 'SYSTEM_INVITE_FAILED':
            case 'INVITE_REVOKED_UNAUTHORIZED':
                return 'warning';
            case 'MEMBER_REMOVED_UNAUTHORIZED':
            case 'MEMBER_REMOVED_POLICY':
                return 'danger';
            default:
                return 'muted';
        }
    }

    private function titleize($value)
    {
        $value = trim(str_replace('_', ' ', $value));
        return $value !== '' ? ucfirst($value) : '--';
    }

    /**
     * @param mixed $value
     */
    private function formatDateTime($value, $format)
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return '--';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return $raw;
        }

        return date($format, $timestamp);
    }
}

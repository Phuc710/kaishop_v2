<?php

/**
 * ChatGptAdminViewService
 * Prepares shared UI data for GPT Business admin pages.
 */
class ChatGptAdminViewService
{
    public function buildFarmPageData($farms, $stats, $orderStats)
    {
        return [
            'farms' => $this->decorateFarms($farms),
            'summaryCards' => [
                $this->makeSummaryCard('Tổng farm', (int) ($stats['total_farms'] ?? 0), 'fas fa-server', 'primary', 'Số farm đang quản lý'),
                $this->makeSummaryCard('Tổng slot', (int) ($stats['total_seats'] ?? 0), 'fas fa-layer-group', 'info', 'Tổng sức chứa của tất cả farm'),
                $this->makeSummaryCard('Đã sử dụng', (int) ($stats['used_seats'] ?? 0), 'fas fa-user-check', 'warning', 'Slot đã giao cho khách'),
                $this->makeSummaryCard('Còn trống', (int) ($stats['available_seats'] ?? 0), 'fas fa-chair', 'success', 'Slot sẵn sàng bán tiếp'),
            ],
            'quickLinks' => [
                [
                    'href' => url('admin/chatgpt/orders'),
                    'label' => 'Đơn hàng GPT',
                    'description' => 'Theo dõi đơn đang mời và đã kích hoạt.',
                    'icon' => 'fas fa-box-open',
                    'meta' => (int) ($orderStats['total'] ?? 0) . ' đơn',
                ],
                [
                    'href' => url('admin/chatgpt/members'),
                    'label' => 'Thành viên farm',
                    'description' => 'Snapshot thành viên hiện có trong từng farm.',
                    'icon' => 'fas fa-users',
                    'meta' => (int) ($stats['used_seats'] ?? 0) . ' slot đang dùng',
                ],
                [
                    'href' => url('admin/chatgpt/logs'),
                    'label' => 'Audit logs',
                    'description' => 'Lịch sử invite, sync và thao tác quản trị.',
                    'icon' => 'fas fa-clipboard-list',
                    'meta' => (int) ($orderStats['failed_count'] ?? 0) . ' lỗi cần xem',
                ],
                [
                    'href' => url('admin/chatgpt/violations'),
                    'label' => 'Violations',
                    'description' => 'Theo dõi vi phạm, kick, revoke và xử lý thủ công.',
                    'icon' => 'fas fa-shield-alt',
                    'meta' => 'Xem chi tiết',
                ],
            ],
        ];
    }

    public function buildOrdersPageData($orders, $stats)
    {
        return [
            'orders' => $this->decorateOrders($orders),
            'summaryCards' => [
                $this->makeSummaryCard('Tổng đơn', (int) ($stats['total'] ?? 0), 'fas fa-shopping-cart', 'primary', 'Tổng số đơn GPT Business'),
                $this->makeSummaryCard('Đã kích hoạt', (int) ($stats['active_count'] ?? 0), 'fas fa-check-circle', 'success', 'Đơn đã vào farm thành công'),
                $this->makeSummaryCard('Đang xử lý', (int) (($stats['inviting_count'] ?? 0) + ($stats['pending_count'] ?? 0)), 'fas fa-hourglass-half', 'warning', 'Đang mời hoặc chờ hệ thống xử lý'),
                $this->makeSummaryCard('Sự cố', (int) (($stats['failed_count'] ?? 0) + ($stats['revoked_count'] ?? 0)), 'fas fa-exclamation-triangle', 'danger', 'Đơn lỗi hoặc bị thu hồi'),
            ],
            'orderStatusOptions' => $this->getOrderStatusOptions(),
        ];
    }

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
                $this->makeSummaryCard('Tổng thành viên', count($members), 'fas fa-users', 'primary', 'Số bản ghi snapshot hiện có'),
                $this->makeSummaryCard('Đã duyệt', $approved, 'fas fa-user-shield', 'success', 'Thành viên dùng lượng hợp lệ'),
                $this->makeSummaryCard('Chưa xác định', $unknown, 'fas fa-user-secret', 'warning', 'Thành viên phát hiện chưa duyệt'),
                $this->makeSummaryCard('Đã rời farm', $removed, 'fas fa-user-minus', 'danger', 'Bản ghi đã bị đánh dấu đã rời'),
            ],
            'memberSourceOptions' => $this->getMemberSourceOptions(),
        ];
    }

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

        $actionTypeOptions = [];
        foreach ($actionTypes as $type) {
            $actionTypeOptions[$type] = $this->translateAuditAction($type);
        }

        return [
            'logs' => $this->decorateLogs($logs),
            'actionTypeOptions' => $actionTypeOptions,
            'summaryCards' => [
                $this->makeSummaryCard('Tổng log', count($logs), 'fas fa-clipboard-list', 'primary', 'Bản ghi audit đang hiển thị'),
                $this->makeSummaryCard('Kết quả thành công', $okCount, 'fas fa-check-double', 'success', 'Tác vụ hoàn tất thành công'),
                $this->makeSummaryCard('Kết quả thất bại', $failCount, 'fas fa-bug', 'danger', 'Sự cố cần kiểm tra thêm'),
                $this->makeSummaryCard('Loại tác vụ', count($actionTypes), 'fas fa-sitemap', 'info', 'Số action type khác nhau'),
            ],
        ];
    }

    public function buildViolationsPageData($violations, $stats, $types)
    {
        return [
            'violations' => $this->decorateViolations($violations),
            'violationTypeOptions' => $this->buildViolationTypeOptions($types),
            'summaryCards' => [
                $this->makeSummaryCard('Tổng vi phạm', (int) ($stats['total'] ?? 0), 'fas fa-shield-alt', 'primary', 'Tổng số vi phạm đã ghi nhận'),
                $this->makeSummaryCard('Nghiêm trọng', (int) ($stats['critical_count'] ?? 0), 'fas fa-radiation', 'danger', 'Vi phạm mức critical'),
                $this->makeSummaryCard('Mức cao', (int) ($stats['high_count'] ?? 0), 'fas fa-exclamation-circle', 'warning', 'Vi phạm cần xử lý gấp'),
                $this->makeSummaryCard('Mức trung bình/thấp', (int) (($stats['medium_count'] ?? 0) + ($stats['low_count'] ?? 0)), 'fas fa-clipboard-check', 'info', 'Vi phạm đã được ghi nhận'),
            ],
        ];
    }

    public function getOrderStatusOptions()
    {
        return [
            'pending' => 'Chờ xử lý',
            'inviting' => 'Đang chờ chấp nhận',
            'active' => 'Đã kích hoạt',
            'failed' => 'Lỗi kết nối/vận hành',
            'revoked' => 'Đã thu hồi',
        ];
    }

    public function getMemberSourceOptions()
    {
        return [
            'approved' => 'Đã duyệt (hợp lệ)',
            'detected_unknown' => 'Chưa xác định (lạ)',
        ];
    }

    private function decorateFarms($farms)
    {
        $output = [];
        foreach ($farms as $farm) {
            $used = (int) ($farm['seat_used'] ?? 0);
            $total = max(1, (int) ($farm['seat_total'] ?? 0));
            $percent = (int) round(($used / $total) * 100);
            $status = (string) ($farm['status'] ?? 'active');

            $farm['seat_summary'] = $used . ' / ' . $total;
            $farm['seat_percent'] = $percent;
            $farm['status_label'] = [
                'active' => 'Đang hoạt động',
                'full' => 'Đã đầy',
                'locked' => 'Bị khóa',
            ][$status] ?? ucfirst($status);
            $farm['status_badge_class'] = 'gptb-badge gptb-badge--' . $this->resolveFarmStatusTone($status);
            $farm['seat_fill_class'] = 'gptb-seat-fill gptb-seat-fill--' . ($percent >= 100 ? 'danger' : ($percent >= 75 ? 'warning' : 'success'));
            $farm['last_sync_display'] = $this->formatDateTime($farm['last_sync_at'] ?? null, 'd/m/Y H:i');
            $output[] = $farm;
        }

        return $output;
    }

    private function decorateOrders($orders)
    {
        $statusMap = [
            'pending' => ['label' => 'Chờ xử lý', 'tone' => 'warning'],
            'inviting' => ['label' => 'Đã mời', 'tone' => 'info'],
            'active' => ['label' => 'Đã kích hoạt', 'tone' => 'success'],
            'failed' => ['label' => 'Lỗi', 'tone' => 'danger'],
            'revoked' => ['label' => 'Đã thu hồi', 'tone' => 'muted'],
        ];

        $output = [];
        foreach ($orders as $order) {
            $status = (string) ($order['status'] ?? 'pending');
            $meta = $statusMap[$status] ?? ['label' => $status, 'tone' => 'muted'];
            $order['status_label'] = $meta['label'];
            $order['status_badge_class'] = 'gptb-badge gptb-badge--' . $meta['tone'];
            $order['farm_display'] = trim((string) ($order['farm_name'] ?? '')) !== '' ? (string) $order['farm_name'] : 'Chưa gán farm';
            $order['note_display'] = trim((string) ($order['note'] ?? '')) !== '' ? (string) $order['note'] : '--';
            $order['expires_at_display'] = $this->formatDateTime($order['expires_at'] ?? null, 'd/m/Y H:i');
            $order['created_at_display'] = $this->formatDateTime($order['created_at'] ?? null, 'd/m/Y H:i');
            $output[] = $order;
        }

        return $output;
    }

    private function decorateMembers($members)
    {
        $output = [];
        foreach ($members as $member) {
            $source = (string) ($member['source'] ?? 'detected_unknown');
            $status = (string) ($member['status'] ?? 'active');
            $role = (string) ($member['role'] ?? 'reader');

            $member['source_label'] = $source === 'approved' ? 'Hợp lệ' : 'Lạ/Chưa duyệt';
            $member['source_badge_class'] = 'gptb-badge gptb-badge--' . ($source === 'approved' ? 'success' : 'warning');
            $member['status_label'] = $status === 'removed' ? 'Đã rời farm' : 'Đang hoạt động';
            $member['status_badge_class'] = 'gptb-badge gptb-badge--' . ($status === 'removed' ? 'danger' : 'info');
            $member['role_label'] = $role === 'owner' ? 'Chủ sở hữu' : ($role === 'admin' ? 'Quản trị' : 'Thành viên');
            $member['role_badge_class'] = 'gptb-badge gptb-badge--primary';
            $member['first_seen_display'] = $this->formatDateTime($member['first_seen_at'] ?? null, 'd/m/Y H:i');
            $member['last_seen_display'] = $this->formatDateTime($member['last_seen_at'] ?? null, 'd/m/Y H:i');
            $output[] = $member;
        }

        return $output;
    }

    private function decorateLogs($logs)
    {
        $output = [];
        foreach ($logs as $log) {
            $action = (string) ($log['action'] ?? '');
            $result = strtoupper((string) ($log['result'] ?? 'OK'));

            $log['action_badge_class'] = 'gptb-badge gptb-badge--' . $this->resolveAuditActionTone($action);
            $log['action_label'] = $this->translateAuditAction($action);
            $log['result_badge_class'] = 'gptb-badge gptb-badge--' . ($result === 'FAIL' ? 'danger' : 'success');
            $log['result_label'] = $result === 'FAIL' ? 'THẤT BẠI' : 'THÀNH CÔNG';
            $log['meta_display'] = $this->formatMetaJson($log['meta_json'] ?? null);
            $log['created_at_display'] = $this->formatDateTime($log['created_at'] ?? null, 'd/m/Y H:i:s');
            $output[] = $log;
        }

        return $output;
    }

    private function decorateViolations($violations)
    {
        $output = [];
        foreach ($violations as $violation) {
            $severity = (string) ($violation['severity'] ?? 'high');
            $violation['severity_badge_class'] = 'gptb-badge gptb-badge--' . ([
                'critical' => 'danger',
                'high' => 'warning',
                'medium' => 'info',
                'low' => 'success',
            ][$severity] ?? 'muted');
            $violation['severity_label'] = strtoupper($severity);
            $violation['type_label'] = $this->translateViolationType((string) ($violation['type'] ?? ''));
            $violation['created_at_display'] = $this->formatDateTime($violation['created_at'] ?? null, 'd/m/Y H:i:s');
            $output[] = $violation;
        }

        return $output;
    }

    private function buildViolationTypeOptions($types)
    {
        $options = [];
        foreach ($types as $type) {
            $options[$type] = $this->translateViolationType($type);
        }
        return $options;
    }

    private function translateAuditAction($action)
    {
        $map = [
            'ORDER_ACTIVATED' => 'Kích hoạt đơn hàng',
            'ORDER_EXPIRED' => 'Đơn hàng hết hạn',
            'FARM_ADDED' => 'Thêm farm mới',
            'FARM_UPDATED' => 'Cập nhật farm',
            'FARM_SYNCED' => 'Đồng bộ farm',
            'SYSTEM_INVITE_CREATED' => 'Tạo lời mời hệ thống',
            'SYSTEM_INVITE_FAILED' => 'Lỗi tạo lời mời',
            'INVITE_REVOKED_UNAUTHORIZED' => 'Thu hồi invite',
            'MEMBER_REMOVED_UNAUTHORIZED' => 'Xóa thành viên không hợp lệ',
            'MEMBER_REMOVED_POLICY' => 'Xóa thành viên theo chính sách',
            'MEMBER_UPSERTED' => 'Cập nhật snapshot thành viên',
            'INVITE_UPSERTED' => 'Cập nhật snapshot invite',
            'UNKNOWN' => 'Không xác định',
        ];

        return $map[$action] ?? $this->titleize($action);
    }

    private function translateViolationType($type)
    {
        $map = [
            'unauthorized_invite' => 'Invite không hợp lệ',
            'unauthorized_member' => 'Thành viên không hợp lệ',
            'self_invite_violation' => 'Tự mời hoặc trùng email bất thường',
            'expired_access' => 'Truy cập hết hạn 30 ngày',
            'manual_policy_action' => 'Quản trị viên xử lý thủ công',
        ];

        return $map[$type] ?? $this->titleize($type);
    }

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
            case 'ORDER_EXPIRED':
            case 'FARM_ADDED':
            case 'FARM_UPDATED':
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

    private function formatMetaJson($value)
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '--';
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $raw;
        }

        $parts = [];
        foreach ($decoded as $key => $item) {
            if (is_array($item)) {
                $item = json_encode($item, JSON_UNESCAPED_UNICODE);
            } elseif ($item === null || $item === '') {
                $item = '--';
            } elseif (is_bool($item)) {
                $item = $item ? 'true' : 'false';
            }
            $parts[] = $this->titleize((string) $key) . ': ' . (string) $item;
        }

        return implode(' | ', $parts);
    }

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

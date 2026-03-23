<?php

/**
 * ChatGptGuardService
 * Shared guard pipeline for cron, manual sync, and admin actions.
 */
class ChatGptGuardService
{
    private $farmModel;
    private $orderModel;
    private $allowModel;
    private $snapModel;
    private $auditLog;
    private $farmService;
    private $violationModel;

    public function __construct()
    {
        $this->farmModel = new ChatGptFarm();
        $this->orderModel = new ChatGptOrder();
        $this->allowModel = new ChatGptAllowedInvite();
        $this->snapModel = new ChatGptSnapshot();
        $this->auditLog = new ChatGptAuditLog();
        $this->farmService = new ChatGptFarmService();
        $this->violationModel = new ChatGptViolation();
    }

    public function processFarmById($farmId, $actorEmail = 'system_guard', $source = 'manual_sync')
    {
        $farm = $this->farmModel->getById((int) $farmId);
        if (!$farm) {
            return [
                'success' => false,
                'message' => 'Farm không tồn tại.',
            ];
        }

        return $this->processFarm($farm, $actorEmail, $source);
    }

    public function processFarm($farm, $actorEmail = 'system_guard', $source = 'cron')
    {
        $farmId = (int) ($farm['id'] ?? 0);
        $farmName = (string) ($farm['farm_name'] ?? ('Farm #' . $farmId));
        $result = [
            'success' => true,
            'farm_id' => $farmId,
            'farm_name' => $farmName,
            'members_total' => 0,
            'invites_total' => 0,
            'members_removed' => 0,
            'invites_revoked' => 0,
            'orders_activated' => 0,
            'orders_expired' => 0,
            'violations_logged' => 0,
            'seat_used' => 0,
            'messages' => [],
        ];

        $liveMembers = $this->farmService->listMembers($farm);
        $liveInvites = $this->farmService->listInvites($farm);

        if (!is_array($liveMembers)) {
            $liveMembers = [];
        }
        if (!is_array($liveInvites)) {
            $liveInvites = [];
        }

        $result['members_total'] = count($liveMembers);
        $result['invites_total'] = count($liveInvites);

        $expiredCleanup = $this->expireOrders($farm, $liveMembers, $liveInvites, $actorEmail, $source);
        $result['members_removed'] += $expiredCleanup['members_removed'];
        $result['invites_revoked'] += $expiredCleanup['invites_revoked'];
        $result['orders_expired'] += $expiredCleanup['orders_expired'];
        $result['violations_logged'] += $expiredCleanup['violations_logged'];

        if (!empty($expiredCleanup['removed_member_emails'])) {
            $removedEmails = $expiredCleanup['removed_member_emails'];
            $liveMembers = array_values(array_filter($liveMembers, function ($member) use ($removedEmails) {
                $email = strtolower(trim((string) ($member['email'] ?? '')));
                return $email === '' || !isset($removedEmails[$email]);
            }));
        }

        if (!empty($expiredCleanup['revoked_invite_ids'])) {
            $revokedInviteIds = $expiredCleanup['revoked_invite_ids'];
            $liveInvites = array_values(array_filter($liveInvites, function ($invite) use ($revokedInviteIds) {
                $inviteId = (string) ($invite['id'] ?? '');
                return $inviteId === '' || !isset($revokedInviteIds[$inviteId]);
            }));
        }

        $allowedRows = $this->allowModel->getAllowedEmailsForFarm($farmId);
        $allowedEmails = [];
        $allowedInviteIds = [];
        $allowedStatusByEmail = [];
        foreach ($allowedRows as $allowedRow) {
            $email = strtolower(trim((string) ($allowedRow['target_email'] ?? '')));
            if ($email !== '') {
                $allowedEmails[$email] = true;
                $allowedStatusByEmail[$email] = (string) ($allowedRow['status'] ?? 'pending');
            }
            $inviteId = trim((string) ($allowedRow['invite_id'] ?? ''));
            if ($inviteId !== '') {
                $allowedInviteIds[$inviteId] = true;
            }
        }

        $liveMemberEmails = [];
        $liveInviteIds = [];
        $memberRowsByEmail = [];
        $inviteRowsById = [];
        $removedMemberEmails = [];
        $revokedInviteIds = [];

        foreach ($liveMembers as $member) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));
            $userId = trim((string) ($member['id'] ?? ''));
            $role = trim((string) ($member['role'] ?? 'reader'));
            if ($email === '') {
                continue;
            }

            $liveMemberEmails[$email] = true;
            $memberRowsByEmail[$email] = $member;
            $sourceLabel = isset($allowedEmails[$email]) ? 'approved' : 'detected_unknown';
            $this->snapModel->upsertMember($farmId, $userId, $email, $role, $sourceLabel);
        }

        foreach ($liveInvites as $invite) {
            $inviteId = trim((string) ($invite['id'] ?? ''));
            $email = strtolower(trim((string) ($invite['email'] ?? '')));
            $status = trim((string) ($invite['status'] ?? 'pending'));
            $role = trim((string) ($invite['role'] ?? 'reader'));
            if ($inviteId === '' || $email === '') {
                continue;
            }

            $liveInviteIds[$inviteId] = true;
            $inviteRowsById[$inviteId] = $invite;
            $sourceLabel = (isset($allowedInviteIds[$inviteId]) || isset($allowedEmails[$email])) ? 'approved' : 'detected_unknown';
            $this->snapModel->upsertInvite($farmId, $inviteId, $email, $status, $sourceLabel, $role);
        }

        $this->snapModel->markMissingMembers($farmId, array_keys($liveMemberEmails));
        $this->snapModel->markMissingInvites($farmId, array_keys($liveInviteIds), $this->allowModel->getSnapshotResolutionMap($farmId));

        foreach ($liveInvites as $invite) {
            $inviteId = trim((string) ($invite['id'] ?? ''));
            $email = strtolower(trim((string) ($invite['email'] ?? '')));
            if ($inviteId === '' || $email === '') {
                continue;
            }

            if (!isset($allowedInviteIds[$inviteId]) && !isset($allowedEmails[$email])) {
                $revoke = $this->farmService->revokeInvite($farm, $inviteId);
                $actionTaken = $revoke['success'] ? 'revoke_invite' : 'revoke_invite_failed';

                if ($revoke['success']) {
                    $result['invites_revoked']++;
                    $this->snapModel->markInviteGone($farmId, $inviteId);
                    unset($liveInviteIds[$inviteId]);
                    $revokedInviteIds[$inviteId] = true;
                }

                $this->logViolation([
                    'farm_id' => $farmId,
                    'email' => $email,
                    'type' => 'unauthorized_invite',
                    'severity' => 'high',
                    'reason' => 'Phát hiện invite không có trong danh sách hợp lệ.',
                    'action_taken' => $actionTaken,
                ]);
                $result['violations_logged']++;

                $this->auditLog->log([
                    'farm_id' => $farmId,
                    'farm_name' => $farmName,
                    'action' => 'INVITE_REVOKED_UNAUTHORIZED',
                    'actor_email' => $actorEmail,
                    'target_email' => $email,
                    'result' => $revoke['success'] ? 'OK' : 'FAIL',
                    'reason' => 'invite_không_hợp_lệ',
                    'meta' => [
                        'source' => $source,
                        'invite_id' => $inviteId,
                        'api_error' => $revoke['error'] ?? null,
                    ],
                ]);

                if (isset($liveMemberEmails[$email]) && $email !== strtolower(trim((string) ($farm['admin_email'] ?? '')))) {
                    $member = $memberRowsByEmail[$email] ?? null;
                    $userId = trim((string) ($member['id'] ?? ''));
                    if ($userId !== '') {
                        $remove = $this->farmService->removeMember($farm, $userId);
                        if ($remove['success']) {
                            $result['members_removed']++;
                            $this->snapModel->markMemberGone($farmId, $email);
                            $removedMemberEmails[$email] = true;
                        }

                        $this->logViolation([
                            'farm_id' => $farmId,
                            'email' => $email,
                            'type' => 'self_invite_violation',
                            'severity' => 'critical',
                            'reason' => 'Email thành viên đang hoạt động trùng với invite trái phép trong cùng farm.',
                            'action_taken' => $remove['success'] ? 'remove_member_and_revoke_invite' : 'remove_member_failed',
                        ]);
                        $result['violations_logged']++;

                        $this->auditLog->log([
                            'farm_id' => $farmId,
                            'farm_name' => $farmName,
                            'action' => 'MEMBER_REMOVED_POLICY',
                            'actor_email' => $actorEmail,
                            'target_email' => $email,
                            'result' => $remove['success'] ? 'OK' : 'FAIL',
                            'reason' => 'self_invite_violation',
                            'meta' => [
                                'source' => $source,
                                'openai_user_id' => $userId,
                                'api_error' => $remove['error'] ?? null,
                            ],
                        ]);
                    }
                }
            }
        }

        foreach ($liveMembers as $member) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));
            $userId = trim((string) ($member['id'] ?? ''));
            $role = trim((string) ($member['role'] ?? ''));
            if ($email === '' || $userId === '') {
                continue;
            }

            if ($email === strtolower(trim((string) ($farm['admin_email'] ?? ''))) || $role === 'owner') {
                continue;
            }

            if (!isset($allowedEmails[$email])) {
                $remove = $this->farmService->removeMember($farm, $userId);
                if ($remove['success']) {
                    $result['members_removed']++;
                    $this->snapModel->markMemberGone($farmId, $email);
                    $removedMemberEmails[$email] = true;
                }

                $this->logViolation([
                    'farm_id' => $farmId,
                    'email' => $email,
                    'type' => 'unauthorized_member',
                    'severity' => 'critical',
                    'reason' => 'Phát hiện thành viên không có whitelist hợp lệ trong farm.',
                    'action_taken' => $remove['success'] ? 'remove_member' : 'remove_member_failed',
                ]);
                $result['violations_logged']++;

                $this->auditLog->log([
                    'farm_id' => $farmId,
                    'farm_name' => $farmName,
                    'action' => 'MEMBER_REMOVED_UNAUTHORIZED',
                    'actor_email' => $actorEmail,
                    'target_email' => $email,
                    'result' => $remove['success'] ? 'OK' : 'FAIL',
                    'reason' => 'member_không_hợp_lệ',
                    'meta' => [
                        'source' => $source,
                        'openai_user_id' => $userId,
                        'api_error' => $remove['error'] ?? null,
                    ],
                ]);
            }
        }

        foreach ($this->orderModel->getInvitingOrders($farmId) as $order) {
            $email = strtolower(trim((string) ($order['customer_email'] ?? '')));
            if ($email === '') {
                continue;
            }
            if (!isset($liveMemberEmails[$email])) {
                continue;
            }
            if (!empty($order['expires_at']) && strtotime((string) $order['expires_at']) <= time()) {
                continue;
            }

            $this->orderModel->updateStatus((int) $order['id'], 'active', [
                'note' => 'Khách đã vào farm thành công.',
            ]);
            $this->allowModel->markAcceptedByEmail($farmId, $email);
            $this->snapModel->markMemberApproved($farmId, $email);
            $result['orders_activated']++;

            $this->auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farmName,
                'action' => 'ORDER_ACTIVATED',
                'actor_email' => $actorEmail,
                'target_email' => $email,
                'result' => 'OK',
                'reason' => 'Khách đã chấp nhận invite và xuất hiện trong danh sách thành viên.',
                'meta' => [
                    'source' => $source,
                    'order_id' => (int) $order['id'],
                    'expires_at' => $order['expires_at'] ?? null,
                ],
            ]);
        }

        $effectiveLiveMembers = array_values(array_filter($liveMembers, function ($member) use ($removedMemberEmails) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));
            return $email === '' || !isset($removedMemberEmails[$email]);
        }));

        $effectiveLiveInvites = array_values(array_filter($liveInvites, function ($invite) use ($revokedInviteIds) {
            $inviteId = trim((string) ($invite['id'] ?? ''));
            return $inviteId === '' || !isset($revokedInviteIds[$inviteId]);
        }));

        $result['seat_used'] = $this->farmModel->syncSeatUsageFromLiveData(
            $farmId,
            $farm['seat_total'] ?? 0,
            $effectiveLiveMembers,
            $effectiveLiveInvites,
            $farm['admin_email'] ?? ''
        );
        $this->farmModel->touchSyncAt($farmId);

        $this->auditLog->log([
            'farm_id' => $farmId,
            'farm_name' => $farmName,
            'action' => 'FARM_SYNCED',
            'actor_email' => $actorEmail,
            'target_email' => null,
            'result' => 'OK',
            'reason' => 'Hoàn tất đồng bộ farm.',
            'meta' => [
                'source' => $source,
                'members_total' => $result['members_total'],
                'invites_total' => $result['invites_total'],
                'members_removed' => $result['members_removed'],
                'invites_revoked' => $result['invites_revoked'],
                'orders_activated' => $result['orders_activated'],
                'orders_expired' => $result['orders_expired'],
                'violations_logged' => $result['violations_logged'],
                'seat_used' => $result['seat_used'],
            ],
        ]);

        return $result;
    }

    public function removeMemberBySnapshotId($snapshotId, $actorEmail = 'admin')
    {
        $member = $this->snapModel->getMemberById((int) $snapshotId);
        if (!$member) {
            return ['success' => false, 'message' => 'Không tìm thấy thành viên.'];
        }

        $farm = $this->farmModel->getById((int) $member['farm_id']);
        if (!$farm) {
            return ['success' => false, 'message' => 'Farm không tồn tại.'];
        }

        $email = strtolower(trim((string) ($member['email'] ?? '')));
        $role = trim((string) ($member['role'] ?? ''));
        if ($email === strtolower(trim((string) ($farm['admin_email'] ?? ''))) || $role === 'owner') {
            return ['success' => false, 'message' => 'Không thể xóa tài khoản chủ farm.'];
        }

        $remove = $this->farmService->removeMember($farm, (string) ($member['openai_user_id'] ?? ''));
        if (!$remove['success']) {
            return ['success' => false, 'message' => 'Không thể xóa thành viên khỏi OpenAI.', 'error' => $remove['error'] ?? null];
        }

        $this->snapModel->markMemberGone((int) $member['farm_id'], $email);
        $this->logViolation([
            'farm_id' => (int) $member['farm_id'],
            'email' => $email,
            'type' => 'manual_policy_action',
            'severity' => 'medium',
            'reason' => 'Quản trị viên xóa thành viên thủ công.',
            'action_taken' => 'manual_remove_member',
        ]);
        $this->auditLog->log([
            'farm_id' => (int) $member['farm_id'],
            'farm_name' => $farm['farm_name'],
            'action' => 'MEMBER_REMOVED_POLICY',
            'actor_email' => $actorEmail,
            'target_email' => $email,
            'result' => 'OK',
            'reason' => 'Quản trị viên xóa thành viên thủ công.',
            'meta' => [
                'openai_user_id' => $member['openai_user_id'] ?? null,
                'snapshot_id' => (int) $snapshotId,
            ],
        ]);

        return ['success' => true, 'message' => 'Đã xóa thành viên khỏi farm.'];
    }

    public function revokeInviteBySnapshotId($snapshotId, $actorEmail = 'admin')
    {
        $invite = $this->snapModel->getInviteById((int) $snapshotId);
        if (!$invite) {
            return ['success' => false, 'message' => 'Không tìm thấy invite.'];
        }

        $farm = $this->farmModel->getById((int) $invite['farm_id']);
        if (!$farm) {
            return ['success' => false, 'message' => 'Farm không tồn tại.'];
        }

        $inviteId = trim((string) ($invite['invite_id'] ?? ''));
        if ($inviteId === '') {
            return ['success' => false, 'message' => 'Invite không có mã OpenAI hợp lệ.'];
        }

        $revoke = $this->farmService->revokeInvite($farm, $inviteId);
        if (!$revoke['success']) {
            return ['success' => false, 'message' => 'Không thể thu hồi invite.', 'error' => $revoke['error'] ?? null];
        }

        $email = strtolower(trim((string) ($invite['email'] ?? '')));
        $this->snapModel->markInviteGone((int) $invite['farm_id'], $inviteId);
        $this->allowModel->markRevokedByInviteId($inviteId);
        $this->logViolation([
            'farm_id' => (int) $invite['farm_id'],
            'email' => $email,
            'type' => 'manual_policy_action',
            'severity' => 'low',
            'reason' => 'Quản trị viên thu hồi invite thủ công.',
            'action_taken' => 'manual_revoke_invite',
        ]);
        $this->auditLog->log([
            'farm_id' => (int) $invite['farm_id'],
            'farm_name' => $farm['farm_name'],
            'action' => 'INVITE_REVOKED_UNAUTHORIZED',
            'actor_email' => $actorEmail,
            'target_email' => $email,
            'result' => 'OK',
            'reason' => 'Quản trị viên thu hồi invite thủ công.',
            'meta' => [
                'invite_id' => $inviteId,
                'snapshot_id' => (int) $snapshotId,
            ],
        ]);

        return ['success' => true, 'message' => 'Đã thu hồi invite.'];
    }

    public function retryOrderInvite($orderId, $actorEmail = 'admin')
    {
        $order = $this->orderModel->getById((int) $orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        }

        if (!empty($order['expires_at']) && strtotime((string) $order['expires_at']) <= time()) {
            return ['success' => false, 'message' => 'Đơn hàng đã hết hạn 30 ngày, không thể gửi lại invite.'];
        }

        if (in_array((string) ($order['status'] ?? ''), ['active', 'inviting'], true)) {
            return ['success' => false, 'message' => 'Đơn hàng đang hoạt động hoặc đã có invite chờ xử lý.'];
        }

        $farm = null;
        if (!empty($order['assigned_farm_id'])) {
            $farm = $this->farmModel->getById((int) $order['assigned_farm_id']);
            if ($farm && !in_array((string) ($farm['status'] ?? ''), ['active', 'full'], true)) {
                $farm = null;
            }
        }
        if (!$farm) {
            $farm = $this->farmModel->getBestAvailableFarm();
        }
        if (!$farm) {
            return ['success' => false, 'message' => 'Không còn farm phù hợp để gửi lại invite.'];
        }

        $email = strtolower(trim((string) ($order['customer_email'] ?? '')));
        $invite = $this->farmService->createInvite($farm, $email, 'reader');
        if (!$invite['success']) {
            $this->orderModel->updateStatus((int) $order['id'], 'failed', [
                'note' => 'Gửi lại invite thất bại: ' . (string) ($invite['error'] ?? 'unknown'),
            ]);
            $this->auditLog->log([
                'farm_id' => (int) $farm['id'],
                'farm_name' => $farm['farm_name'],
                'action' => 'SYSTEM_INVITE_FAILED',
                'actor_email' => $actorEmail,
                'target_email' => $email,
                'result' => 'FAIL',
                'reason' => 'Gửi lại invite thất bại.',
                'meta' => [
                    'order_id' => (int) $order['id'],
                    'api_error' => $invite['error'] ?? null,
                ],
            ]);

            return ['success' => false, 'message' => 'OpenAI từ chối gửi invite.', 'error' => $invite['error'] ?? null];
        }

        $this->allowModel->createInvite((int) $order['id'], (int) $farm['id'], $email, $invite['invite_id'] ?? null);
        $this->orderModel->updateStatus((int) $order['id'], 'inviting', [
            'assigned_farm_id' => (int) $farm['id'],
            'note' => 'Đã gửi lại invite thành công.',
        ]);
        $this->farmModel->incrementSeatUsed((int) $farm['id']);
        $this->auditLog->log([
            'farm_id' => (int) $farm['id'],
            'farm_name' => $farm['farm_name'],
            'action' => 'SYSTEM_INVITE_CREATED',
            'actor_email' => $actorEmail,
            'target_email' => $email,
            'result' => 'OK',
            'reason' => 'Quản trị viên gửi lại invite cho đơn hàng.',
            'meta' => [
                'order_id' => (int) $order['id'],
                'invite_id' => $invite['invite_id'] ?? null,
            ],
        ]);

        return ['success' => true, 'message' => 'Đã gửi lại invite thành công.'];
    }

    private function expireOrders($farm, $liveMembers, $liveInvites, $actorEmail, $source)
    {
        $farmId = (int) ($farm['id'] ?? 0);
        $farmName = (string) ($farm['farm_name'] ?? '');
        $expiredOrders = $this->orderModel->getExpiredOrders($farmId);

        $memberMap = [];
        foreach ($liveMembers as $member) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));
            if ($email !== '') {
                $memberMap[$email] = $member;
            }
        }

        $inviteMapByEmail = [];
        $inviteMapById = [];
        foreach ($liveInvites as $invite) {
            $email = strtolower(trim((string) ($invite['email'] ?? '')));
            $inviteId = trim((string) ($invite['id'] ?? ''));
            if ($email !== '') {
                $inviteMapByEmail[$email][] = $invite;
            }
            if ($inviteId !== '') {
                $inviteMapById[$inviteId] = $invite;
            }
        }

        $summary = [
            'members_removed' => 0,
            'invites_revoked' => 0,
            'orders_expired' => 0,
            'violations_logged' => 0,
            'removed_member_emails' => [],
            'revoked_invite_ids' => [],
        ];

        foreach ($expiredOrders as $order) {
            $email = strtolower(trim((string) ($order['customer_email'] ?? '')));
            $orderId = (int) ($order['id'] ?? 0);
            $memberRemoved = false;
            $inviteRevoked = false;

            if ($email !== '' && isset($memberMap[$email])) {
                $member = $memberMap[$email];
                $userId = trim((string) ($member['id'] ?? ''));
                if ($userId !== '') {
                    $remove = $this->farmService->removeMember($farm, $userId);
                    if ($remove['success']) {
                        $memberRemoved = true;
                        $summary['members_removed']++;
                        $summary['removed_member_emails'][$email] = true;
                        $this->snapModel->markMemberGone($farmId, $email);
                    }

                    $this->auditLog->log([
                        'farm_id' => $farmId,
                        'farm_name' => $farmName,
                        'action' => 'MEMBER_REMOVED_POLICY',
                        'actor_email' => $actorEmail,
                        'target_email' => $email,
                        'result' => $remove['success'] ? 'OK' : 'FAIL',
                        'reason' => 'Hết hạn sử dụng 30 ngày.',
                        'meta' => [
                            'source' => $source,
                            'order_id' => $orderId,
                            'expires_at' => $order['expires_at'] ?? null,
                            'openai_user_id' => $userId,
                            'api_error' => $remove['error'] ?? null,
                        ],
                    ]);
                }
            }

            $allowRows = $this->allowModel->getOpenInvitesByOrder($orderId);
            foreach ($allowRows as $allowRow) {
                $inviteId = trim((string) ($allowRow['invite_id'] ?? ''));
                if ($inviteId !== '' && isset($inviteMapById[$inviteId])) {
                    $revoke = $this->farmService->revokeInvite($farm, $inviteId);
                    if ($revoke['success']) {
                        $inviteRevoked = true;
                        $summary['invites_revoked']++;
                        $summary['revoked_invite_ids'][$inviteId] = true;
                        $this->snapModel->markInviteGone($farmId, $inviteId);
                        $this->allowModel->markRevokedByInviteId($inviteId, 'expired');
                    }

                    $this->auditLog->log([
                        'farm_id' => $farmId,
                        'farm_name' => $farmName,
                        'action' => 'INVITE_REVOKED_UNAUTHORIZED',
                        'actor_email' => $actorEmail,
                        'target_email' => $email,
                        'result' => $revoke['success'] ? 'OK' : 'FAIL',
                        'reason' => 'Invite bị thu hồi do hết hạn sử dụng 30 ngày.',
                        'meta' => [
                            'source' => $source,
                            'order_id' => $orderId,
                            'expires_at' => $order['expires_at'] ?? null,
                            'invite_id' => $inviteId,
                            'api_error' => $revoke['error'] ?? null,
                        ],
                    ]);
                }
            }

            $this->allowModel->markExpiredByOrder($orderId);
            $this->orderModel->updateStatus($orderId, 'revoked', [
                'note' => 'Đơn hàng đã hết hạn sau 30 ngày tính từ lúc mua.',
            ]);
            $summary['orders_expired']++;

            $this->logViolation([
                'farm_id' => $farmId,
                'email' => $email,
                'type' => 'expired_access',
                'severity' => 'medium',
                'reason' => 'Đơn hàng hết hạn sau 30 ngày kể từ lúc mua.',
                'action_taken' => $memberRemoved ? 'remove_member' : ($inviteRevoked ? 'revoke_invite' : 'mark_order_revoked'),
            ]);
            $summary['violations_logged']++;

            $this->auditLog->log([
                'farm_id' => $farmId,
                'farm_name' => $farmName,
                'action' => 'ORDER_EXPIRED',
                'actor_email' => $actorEmail,
                'target_email' => $email,
                'result' => 'OK',
                'reason' => 'Đơn hàng hết hạn sau 30 ngày kể từ lúc mua.',
                'meta' => [
                    'source' => $source,
                    'order_id' => $orderId,
                    'expires_at' => $order['expires_at'] ?? null,
                    'member_removed' => $memberRemoved,
                    'invite_revoked' => $inviteRevoked,
                ],
            ]);
        }

        return $summary;
    }

    private function logViolation($data)
    {
        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $this->violationModel->createViolation($data);
    }
}

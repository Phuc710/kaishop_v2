<?php

/**
 * TelegramBotServiceAdminTrait
 *
 * Lệnh admin:
 *  - /stats  — Thống kê hệ thống
 *  - /broadcast — Gửi thông báo hàng loạt
 *  - /maintenance on|off — Bật/tắt bảo trì
 *  - /setbank — Cập nhật thông tin ngân hàng nhanh
 */
trait TelegramBotServiceAdminTrait
{
    /**
     * /stats — Thống kê toàn hệ thống
     */
    private function cmdStats(string $chatId, int $telegramId): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⛔ Bạn không có quyền quản trị.', '⛔ You do not have admin permission.'));
            return;
        }

        $conn = $this->userModel->getConnection();
        $today = $this->timeService
            ? $this->timeService->formatDb($this->timeService->nowTs(), 'Y-m-d')
            : date('Y-m-d');

        $userCount = $this->userModel->count();
        $tgCount = (int) $conn->query("SELECT COUNT(*) FROM `user_telegram_links`")->fetchColumn();
        $newTgToday = (int) $conn->query("SELECT COUNT(*) FROM `user_telegram_links` WHERE DATE(`linked_at`)='{$today}'")->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(`price`),0) AS rev FROM `orders` WHERE DATE(`created_at`)=? AND `status`='completed'");
        $stmt->execute([$today]);
        $todayOrders = $stmt->fetch(PDO::FETCH_ASSOC);

        $depositPending = (int) $conn->query("SELECT COUNT(*) FROM `pending_deposits` WHERE `status`='pending'")->fetchColumn();

        $outboxStats = (new TelegramOutbox())->getStats();

        $lastCron = trim((string) get_setting('last_cron_run', ''));
        $workerStatus = $lastCron === ''
            ? $this->tgChoice($telegramId, '❌ Chưa chạy', '❌ Not running yet')
            : "✅ {$lastCron}";

        $msg = $this->tgChoice($telegramId, "📊 <b>THỐNG KÊ HỆ THỐNG</b> ({$today})\n\n", "📊 <b>SYSTEM STATISTICS</b> ({$today})\n\n");
        $msg .= $this->tgChoice($telegramId, '👥 Tổng user web', '👥 Total web users') . ": <b>{$userCount}</b>\n";
        $msg .= $this->tgChoice($telegramId, '🔗 Đã liên kết TG', '🔗 Linked Telegram users') . ": <b>{$tgCount}</b> <i>" . $this->tgChoice($telegramId, "(+{$newTgToday} hôm nay)", "(+{$newTgToday} today)") . "</i>\n\n";
        $msg .= $this->tgChoice($telegramId, "🛍 <b>Đơn hàng hôm nay:</b>\n", "🛍 <b>Today's orders:</b>\n");
        $msg .= '   ' . $this->tgChoice($telegramId, 'Số đơn', 'Count') . ": <b>" . $todayOrders['cnt'] . "</b>\n";
        $msg .= '   ' . $this->tgChoice($telegramId, 'Doanh thu', 'Revenue') . ": <b>" . number_format((float) $todayOrders['rev']) . "đ</b>\n\n";
        $msg .= $this->tgChoice($telegramId, '💰 Nạp chờ duyệt', '💰 Pending deposits') . ": <b>{$depositPending}</b>\n\n";
        $msg .= "📤 <b>Outbox:</b>\n";
        $msg .= '   ' . $this->tgChoice($telegramId, 'Chờ gửi', 'Pending') . ": <b>{$outboxStats['pending']}</b>\n";
        $msg .= '   ' . $this->tgChoice($telegramId, 'Đã gửi', 'Sent') . ":  <b>{$outboxStats['sent']}</b>\n";
        $msg .= '   ' . $this->tgChoice($telegramId, 'Lỗi', 'Failed') . ":     <b>{$outboxStats['failed']}</b>\n\n";
        $msg .= "⚙️ Worker: {$workerStatus}";

        $this->telegram->sendTo($chatId, $msg);
    }

    /**
     * /broadcast <nội_dung> — Push thông báo tới tất cả user đã link (qua Outbox)
     */
    private function cmdBroadcast(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⛔ Bạn không có quyền quản trị.', '⛔ You do not have admin permission.'));
            return;
        }

        $content = trim(implode(' ', $args));
        if ($content === '') {
            $this->telegram->sendTo(
                $chatId,
                $this->tgChoice($telegramId, "📢 <b>BROADCAST</b>\n\nCú pháp:\n<code>/broadcast &lt;nội dung&gt;</code>\n\nVí dụ:\n<code>/broadcast ⚡ Flash sale 50% trong 24h!</code>", "📢 <b>BROADCAST</b>\n\nSyntax:\n<code>/broadcast &lt;content&gt;</code>\n\nExample:\n<code>/broadcast ⚡ Flash sale 50% in 24 hours!</code>")
            );
            return;
        }

        $conn = $this->userModel->getConnection();
        $links = $conn->query("SELECT `telegram_id` FROM `user_telegram_links`")->fetchAll(PDO::FETCH_COLUMN);

        if (empty($links)) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⚠️ Chưa có user nào liên kết Telegram.', '⚠️ No users have linked Telegram yet.'));
            return;
        }

        $outbox = new TelegramOutbox();
        $msgText = $this->tgChoice($telegramId, "📢 <b>THÔNG BÁO HỆ THỐNG</b>\n\n", "📢 <b>SYSTEM ANNOUNCEMENT</b>\n\n") . $content;
        $count = 0;

        foreach ($links as $tid) {
            $outbox->push((int) $tid, $msgText);
            $count++;
        }

        $this->telegram->sendTo(
            $chatId,
            $this->tgChoice($telegramId, "✅ Đã xếp hàng <b>{$count}</b> tin vào Outbox.\nSẽ gửi trong ít phút tới.", "✅ Queued <b>{$count}</b> messages in the Outbox.\nThey will be sent in the next few minutes.")
        );
    }

    /**
     * /maintenance on|off — Bật/tắt bảo trì hệ thống
     */
    private function cmdMaintenance(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⛔ Bạn không có quyền quản trị.', '⛔ You do not have admin permission.'));
            return;
        }

        $action = strtolower($args[0] ?? '');
        if ($action !== 'on' && $action !== 'off') {
            $status = TelegramConfig::isMaintenanceEnabled()
                ? $this->tgChoice($telegramId, '🔴 Đang BẬT', '🔴 ENABLED')
                : $this->tgChoice($telegramId, '🟢 Đang TẮT', '🟢 DISABLED');
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, "🛠 <b>CÀI ĐẶT BẢO TRÌ</b>\n\nSử dụng:\n<code>/maintenance bot on|off</code> — Chỉ Bot\n<code>/maintenance web on|off</code> — Chỉ Web\n<code>/maintenance all on|off</code> — Cả hai", "🛠 <b>MAINTENANCE SETTINGS</b>\n\nUsage:\n<code>/maintenance bot on|off</code>\n<code>/maintenance web on|off</code>\n<code>/maintenance all on|off</code>"));
            return;
        }

        $svc = new MaintenanceService();
        $db = $this->userModel->getConnection();

        try {
            if ($action === 'on') {
                $svc->saveConfig(['maintenance_enabled' => '1']);
                $db->prepare("UPDATE `setting` SET `telegram_maintenance_enabled` = 1 ORDER BY `id` ASC LIMIT 1")->execute();
                Config::clearSiteConfigCache();
                $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '🔧 <b>Đã bật bảo trì TOÀN HỆ THỐNG!</b>\nWebsite và Bot hiện đã tạm dừng phục vụ.', '🔧 <b>SYSTEM-WIDE MAINTENANCE ENABLED!</b>\nThe website and bot are now temporarily unavailable.'));
            } else {
                $svc->clearNow();
                $db->prepare("UPDATE `setting` SET `telegram_maintenance_enabled` = 0 ORDER BY `id` ASC LIMIT 1")->execute();
                Config::clearSiteConfigCache();
                $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '✅ <b>Đã tắt bảo trì TOÀN HỆ THỐNG!</b>\nWebsite và Bot đã hoạt động trở lại.', '✅ <b>SYSTEM-WIDE MAINTENANCE DISABLED!</b>\nThe website and bot are available again.'));
            }
        } catch (Throwable $e) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '❌ Lỗi: ', '❌ Error: ') . $e->getMessage());
        }
    }

    /**
     * /setbank <Ngân hàng>|<STK>|<Chủ TK> — Cập nhật thông tin ngân hàng nhanh
     */
    private function cmdSetBank(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, $this->tgChoice($telegramId, '⛔ Bạn không có quyền quản trị.', '⛔ You do not have admin permission.'));
            return;
        }

        $payload = implode(' ', $args);
        $parts = explode('|', $payload);

        if (count($parts) < 3) {
            $this->telegram->sendTo(
                $chatId,
                $this->tgChoice($telegramId, "🏦 <b>SETBANK</b>\n\nCú pháp:\n<code>/setbank Ngân hàng|Số TK|Chủ TK</code>\n\nVí dụ:\n<code>/setbank MB Bank|0123456789|NGUYEN THANH PHUC</code>", "🏦 <b>SETBANK</b>\n\nSyntax:\n<code>/setbank Bank Name|Account Number|Account Holder</code>\n\nExample:\n<code>/setbank MB Bank|0123456789|NGUYEN THANH PHUC</code>")
            );
            return;
        }

        [$bankName, $bankAcc, $bankOwner] = array_map('trim', $parts);

        $conn = $this->userModel->getConnection();
        $stmt = $conn->prepare("UPDATE `setting` SET `bank_name`=?, `bank_account`=?, `bank_owner`=? ORDER BY `id` ASC LIMIT 1");
        $stmt->execute([$bankName, $bankAcc, $bankOwner]);
        Config::clearSiteConfigCache();

        $this->telegram->sendTo(
            $chatId,
            $this->tgChoice($telegramId, "✅ <b>Đã cập nhật ngân hàng!</b>\n\n", "✅ <b>Bank information updated!</b>\n\n")
            . "🏦 " . htmlspecialchars($bankName) . "\n"
            . "💳 " . htmlspecialchars($bankAcc) . "\n"
            . "👤 " . htmlspecialchars($bankOwner)
        );
    }
}

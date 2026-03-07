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
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
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
        $workerStatus = $lastCron === '' ? '❌ Chưa chạy' : "✅ {$lastCron}";

        $msg = "📊 <b>THỐNG KÊ HỆ THỐNG</b> ({$today})\n\n";
        $msg .= "👥 Tổng user web: <b>{$userCount}</b>\n";
        $msg .= "🔗 Đã liên kết TG: <b>{$tgCount}</b> <i>(+{$newTgToday} hôm nay)</i>\n\n";
        $msg .= "🛍 <b>Đơn hàng hôm nay:</b>\n";
        $msg .= "   Số đơn: <b>" . $todayOrders['cnt'] . "</b>\n";
        $msg .= "   Doanh thu: <b>" . number_format((float) $todayOrders['rev']) . "đ</b>\n\n";
        $msg .= "💰 Nạp chờ duyệt: <b>{$depositPending}</b>\n\n";
        $msg .= "📤 <b>Outbox:</b>\n";
        $msg .= "   Chờ gửi: <b>{$outboxStats['pending']}</b>\n";
        $msg .= "   Đã gửi:  <b>{$outboxStats['sent']}</b>\n";
        $msg .= "   Lỗi:     <b>{$outboxStats['failed']}</b>\n\n";
        $msg .= "⚙️ Worker: {$workerStatus}";

        $this->telegram->sendTo($chatId, $msg);
    }

    /**
     * /broadcast <nội_dung> — Push thông báo tới tất cả user đã link (qua Outbox)
     */
    private function cmdBroadcast(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $content = trim(implode(' ', $args));
        if ($content === '') {
            $this->telegram->sendTo(
                $chatId,
                "📢 <b>BROADCAST</b>\n\nCú pháp:\n<code>/broadcast &lt;nội dung&gt;</code>\n\nVí dụ:\n<code>/broadcast ⚡ Flash sale 50% trong 24h!</code>"
            );
            return;
        }

        $conn = $this->userModel->getConnection();
        $links = $conn->query("SELECT `telegram_id` FROM `user_telegram_links`")->fetchAll(PDO::FETCH_COLUMN);

        if (empty($links)) {
            $this->telegram->sendTo($chatId, "⚠️ Chưa có user nào liên kết Telegram.");
            return;
        }

        $outbox = new TelegramOutbox();
        $msgText = "📢 <b>THÔNG BÁO HỆ THỐNG</b>\n\n" . $content;
        $count = 0;

        foreach ($links as $tid) {
            $outbox->push((int) $tid, $msgText);
            $count++;
        }

        $this->telegram->sendTo(
            $chatId,
            "✅ Đã xếp hàng <b>{$count}</b> tin vào Outbox.\nSẽ gửi trong ít phút tới."
        );
    }

    /**
     * /maintenance on|off — Bật/tắt bảo trì hệ thống
     */
    private function cmdMaintenance(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $action = strtolower($args[0] ?? '');
        if ($action !== 'on' && $action !== 'off') {
            $status = TelegramConfig::isMaintenanceEnabled() ? "🔴 Đang BẬT" : "🟢 Đang TẮT";
            $this->telegram->sendTo($chatId, "🛠 <b>BẢO TRÌ HỆ THỐNG</b>\nTrạng thái hiện tại: {$status}\n\nSử dụng:\n<code>/maintenance on</code> — Bật bảo trì toàn hệ thống\n<code>/maintenance off</code> — Tắt bảo trì toàn hệ thống");
            return;
        }

        $svc = new MaintenanceService();
        $db = $this->userModel->getConnection();

        try {
            if ($action === 'on') {
                $svc->saveConfig(['maintenance_enabled' => '1']);
                $db->prepare("UPDATE `setting` SET `telegram_maintenance_enabled` = 1 ORDER BY `id` ASC LIMIT 1")->execute();
                Config::clearSiteConfigCache();
                $this->telegram->sendTo($chatId, "🔧 <b>Đã bật bảo trì TOÀN HỆ THỐNG!</b>\nWebsite và Bot hiện đã tạm dừng phục vụ.");
            } else {
                $svc->clearNow();
                $db->prepare("UPDATE `setting` SET `telegram_maintenance_enabled` = 0 ORDER BY `id` ASC LIMIT 1")->execute();
                Config::clearSiteConfigCache();
                $this->telegram->sendTo($chatId, "✅ <b>Đã tắt bảo trì TOÀN HỆ THỐNG!</b>\nWebsite và Bot đã hoạt động trở lại.");
            }
        } catch (Throwable $e) {
            $this->telegram->sendTo($chatId, "❌ Lỗi: " . $e->getMessage());
        }
    }

    /**
     * /setbank <Ngân hàng>|<STK>|<Chủ TK> — Cập nhật thông tin ngân hàng nhanh
     */
    private function cmdSetBank(string $chatId, int $telegramId, array $args): void
    {
        if (!TelegramConfig::isAdmin($telegramId)) {
            $this->telegram->sendTo($chatId, "⛔ Bạn không có quyền quản trị.");
            return;
        }

        $payload = implode(' ', $args);
        $parts = explode('|', $payload);

        if (count($parts) < 3) {
            $this->telegram->sendTo(
                $chatId,
                "🏦 <b>SETBANK</b>\n\nCú pháp:\n<code>/setbank Ngân hàng|Số TK|Chủ TK</code>\n\nVí dụ:\n<code>/setbank MB Bank|0123456789|NGUYEN THANH PHUC</code>"
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
            "✅ <b>Đã cập nhật ngân hàng!</b>\n\n"
            . "🏦 " . htmlspecialchars($bankName) . "\n"
            . "💳 " . htmlspecialchars($bankAcc) . "\n"
            . "👤 " . htmlspecialchars($bankOwner)
        );
    }
}

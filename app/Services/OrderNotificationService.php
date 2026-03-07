<?php

/**
 * OrderNotificationService
 * Handles sending Telegram notifications when orders are placed or status changes.
 */
class OrderNotificationService
{
    private TelegramService $telegram;
    private TelegramOutbox $outbox;

    public function __construct()
    {
        $this->telegram = new TelegramService();
        $this->outbox = new TelegramOutbox();
    }

    /**
     * Notify admins about a new "Pending" order that needs manual fulfillment.
     */
    public function notifyAdminPendingOrder(array $order): void
    {
        $adminIds = TelegramConfig::adminIds();
        $extraIds = $this->getExtraChannelIds();
        $allTargets = array_unique(array_merge($adminIds, $extraIds));

        if (empty($allTargets)) {
            return;
        }

        $orderId = $order['id'] ?? 0;
        $orderCode = $order['order_code'] ?? '???';
        $productName = $order['product_name'] ?? 'Sản phẩm';
        $username = $order['username'] ?? 'Khách';
        $price = (int) ($order['price'] ?? 0);
        $qty = max(1, (int) ($order['quantity'] ?? 1));
        $total = $price * $qty;
        $input = trim((string) ($order['customer_input'] ?? ''));
        $createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
        $source = SourceChannelHelper::label(SourceChannelHelper::fromOrderRow($order));

        $msg = "🔔 <b>ĐƠN HÀNG CHỜ XỬ LÝ MỚI</b>\n\n";
        $msg .= "🔢 ID đơn: <code>#{$orderId}</code>\n";
        $msg .= "🎫 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "👤 Khách hàng: <b>{$username}</b>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "💰 Đơn giá: <b>" . number_format($price) . "đ</b>\n";
        $msg .= "💠 Số lượng: <b>{$qty}</b>\n";
        $msg .= "💳 Tổng tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "🌐 Nguồn: <b>{$source}</b>\n";
        $msg .= "🕐 Thời gian: <code>{$createdAt}</code>\n";

        if ($input !== '') {
            $msg .= "\n📝 <b>Nội dung khách gửi:</b>\n<code>" . htmlspecialchars($input) . "</code>\n";
        }

        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "👉 Vui lòng vào Admin Panel để xử lý đơn hàng.";

        foreach ($allTargets as $tid) {
            $this->outbox->enqueue((int) $tid, $msg);
        }
    }

    /**
     * Notify admins about a successful "Instant" order.
     */
    public function notifyAdminNewOrder(array $order): void
    {
        $adminIds = TelegramConfig::adminIds();
        $extraIds = $this->getExtraChannelIds();
        $allTargets = array_unique(array_merge($adminIds, $extraIds));

        if (empty($allTargets)) {
            return;
        }

        $orderId = $order['id'] ?? 0;
        $orderCode = $order['order_code'] ?? '???';
        $productName = $order['product_name'] ?? 'Sản phẩm';
        $username = $order['username'] ?? 'Khách';
        $price = (int) ($order['price'] ?? 0);
        $qty = max(1, (int) ($order['quantity'] ?? 1));
        $total = (int) ($order['total_price'] ?? ($price * $qty));
        $createdAt = $order['ordered_at'] ?? date('H:i:s d/m/Y');
        $source = $order['source_label'] ?? 'Web';
        $delivery = trim((string) ($order['delivery_content'] ?? ''));

        $msg = "💰 <b>CÓ ĐƠN HÀNG MỚI</b>\n\n";
        $msg .= "🎫 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "👤 Khách hàng: <b>{$username}</b>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "💰 Đơn giá: <b>" . number_format($price) . "đ</b>\n";
        $msg .= "💠 Số lượng: <b>{$qty}</b>\n";
        $msg .= "💳 Tổng tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "🌐 Nguồn: <b>{$source}</b>\n";
        $msg .= "🕐 Thời gian: <code>{$createdAt}</code>\n";

        if ($delivery !== '') {
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "📦 <b>NỘI DUNG BÀN GIAO:</b>\n";
            $msg .= "<code>" . htmlspecialchars($delivery) . "</code>\n";
        }

        $msg .= "━━━━━━━━━━━━━━\n";
        $msg .= "✅ Hệ thống đã tự động giao hàng.";

        foreach ($allTargets as $tid) {
            $this->outbox->enqueue((int) $tid, $msg);
        }
    }

    /**
     * Get IDs of extra employee/group channels
     */
    private function getExtraChannelIds(): array
    {
        try {
            $channels = (new TelegramNotificationChannel())->getActive();
            return array_column($channels, 'chat_id');
        } catch (\Throwable $e) {
            return [];
        }
    }
}

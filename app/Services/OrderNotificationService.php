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
        $allTargets = $this->getNotificationTargets();

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

        $msg = "🔔 <b>ĐƠN HÀNG ĐANG XỬ LÝ MỚI</b>\n\n";
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
        $allTargets = $this->getNotificationTargets();

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
        $source = !empty($order['source_label']) ? $order['source_label'] : SourceChannelHelper::label(SourceChannelHelper::fromOrderRow($order));
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
     * Notify admins/groups when a pending order is completed manually.
     */
    public function notifyAdminCompletedOrder(array $order): void
    {
        $allTargets = $this->getNotificationTargets();
        if (empty($allTargets)) {
            return;
        }

        $orderId = $order['id'] ?? 0;
        $orderCode = $order['order_code'] ?? '???';
        $productName = $order['product_name'] ?? 'San pham';
        $username = $order['username'] ?? 'Khach';
        $price = (int) ($order['price'] ?? 0);
        $qty = max(1, (int) ($order['quantity'] ?? 1));
        $total = (int) ($order['total_price'] ?? ($price * $qty));
        $fulfilledBy = trim((string) ($order['fulfilled_by'] ?? 'Admin'));
        $fulfilledAt = $order['fulfilled_at'] ?? date('Y-m-d H:i:s');
        $delivery = trim((string) ($order['delivery_content'] ?? ''));
        $source = SourceChannelHelper::label(SourceChannelHelper::fromOrderRow($order));

        $msg = "✅ <b>ĐƠN HÀNG ĐÃ HOÀN TẤT</b>\n\n";
        $msg .= "🔢 ID đơn: <code>#{$orderId}</code>\n";
        $msg .= "🎫 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "👤 Khách hàng: <b>{$username}</b>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "💰 Đơn giá: <b>" . number_format($price) . "đ</b>\n";
        $msg .= "💠 Số lượng: <b>{$qty}</b>\n";
        $msg .= "💳 Tổng tiền: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "🌐 Nguồn: <b>{$source}</b>\n";
        $msg .= "👨‍💼 Xử lý bởi: <b>" . htmlspecialchars($fulfilledBy, ENT_QUOTES, 'UTF-8') . "</b>\n";
        $msg .= "🕐 Hoàn tất lúc: <code>{$fulfilledAt}</code>\n";

        if ($delivery !== '') {
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "📦 <b>NỘI DUNG BÀN GIAO:</b>\n";
            $msg .= "<code>" . htmlspecialchars($delivery, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }

        foreach ($allTargets as $tid) {
            $this->outbox->enqueue((int) $tid, $msg);
        }
    }

    /**
     * Notify admins/groups when a pending order is cancelled/refunded.
     */
    public function notifyAdminCancelledOrder(array $order): void
    {
        $allTargets = $this->getNotificationTargets();
        if (empty($allTargets)) {
            return;
        }

        $orderId = $order['id'] ?? 0;
        $orderCode = $order['order_code'] ?? '???';
        $productName = $order['product_name'] ?? 'San pham';
        $username = $order['username'] ?? 'Khach';
        $price = (int) ($order['price'] ?? 0);
        $qty = max(1, (int) ($order['quantity'] ?? 1));
        $total = (int) ($order['total_price'] ?? ($price * $qty));
        $fulfilledBy = trim((string) ($order['fulfilled_by'] ?? 'Admin'));
        $fulfilledAt = $order['fulfilled_at'] ?? date('Y-m-d H:i:s');
        $reason = trim((string) ($order['cancel_reason'] ?? ''));
        $source = SourceChannelHelper::label(SourceChannelHelper::fromOrderRow($order));

        $msg = "❌ <b>ĐƠN HÀNG ĐÃ HỦY</b>\n\n";
        $msg .= "🔢 ID đơn: <code>#{$orderId}</code>\n";
        $msg .= "🎫 Mã đơn: <code>{$orderCode}</code>\n";
        $msg .= "👤 Khách hàng: <b>{$username}</b>\n";
        $msg .= "📦 Sản phẩm: <b>{$productName}</b>\n";
        $msg .= "💰 Đơn giá: <b>" . number_format($price) . "đ</b>\n";
        $msg .= "💠 Số lượng: <b>{$qty}</b>\n";
        $msg .= "💳 Tổng tiền hoàn: <b>" . number_format($total) . "đ</b>\n";
        $msg .= "🌐 Nguồn: <b>{$source}</b>\n";
        $msg .= "👨‍💼 Xử lý bởi: <b>" . htmlspecialchars($fulfilledBy, ENT_QUOTES, 'UTF-8') . "</b>\n";
        $msg .= "🕐 Hủy lúc: <code>{$fulfilledAt}</code>\n";

        if ($reason !== '') {
            $msg .= "━━━━━━━━━━━━━━\n";
            $msg .= "📝 <b>LÝ DO / PHẢN HỒI:</b>\n";
            $msg .= "<code>" . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "</code>\n";
        }

        foreach ($allTargets as $tid) {
            $this->outbox->enqueue((int) $tid, $msg);
        }
    }

    /**
     * @return array<int,string>
     */
    private function getNotificationTargets(): array
    {
        $adminIds = TelegramConfig::adminIds();
        $extraIds = $this->getExtraChannelIds();

        $targets = [];
        foreach (array_merge($adminIds, $extraIds) as $target) {
            $target = trim((string) $target);
            if ($target === '') {
                continue;
            }
            $targets[$target] = $target;
        }

        return array_values($targets);
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

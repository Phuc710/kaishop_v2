<?php

class SourceLinkInventoryHandler extends BaseInventoryHandler
{
    private Order $orderModel;

    public function __construct(array $product)
    {
        parent::__construct($product);
        $this->orderModel = new Order();
    }

    public function getItems(array $filters): array
    {
        // For Source Link, we also use the order history as items
        $items = $this->orderModel->getProductOrdersQueue((int) $this->product['id'], $filters);
        return $this->hydrateItems($items);
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as sold
            FROM `orders` 
            WHERE `product_id` = ?
        ");
        $stmt->execute([$this->product['id']]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'sold' => 0];

        return [
            'total' => (int) $counts['total'],
            'available' => (int) $counts['total'], // Luôn có link
            'sold' => (int) $counts['sold'],
            'is_manual_queue' => true,
            'is_source_link' => true
        ];
    }

    public function getPartialView(): string
    {
        // Source links are simple links, currently we reuse order list to show history
        return 'admin/products/stock/_order_list';
    }

    public function handleImport(string $content): array
    {
        return ['success' => false, 'message' => 'Sản phẩm này không hỗ trợ nhập kho hàng loạt.'];
    }

    public function handleAction(string $action, int $id, array $params = []): array
    {
        $adminUsername = trim((string) ($params['admin_username'] ?? 'admin')) ?: 'admin';

        switch ($action) {
            case 'fulfill':
                // For source link, fulfilling is usually just editing content (warranty)
                $content = trim((string) ($params['content'] ?? ''));
                if ($content === '') {
                    return ['success' => false, 'message' => 'Nội dung bàn giao không được để trống'];
                }
                $res = $this->orderModel->fulfillPendingOrder($id, $content, $adminUsername);
                return $res;

            case 'cancel':
                $reason = trim((string) ($params['reason'] ?? ''));
                if ($reason === '') {
                    return ['success' => false, 'message' => 'Lý do hủy không được để trống'];
                }
                $res = $this->orderModel->cancelPendingOrder($id, $adminUsername, $reason);
                return $res;

            default:
                return ['success' => false, 'message' => "Hành động '{$action}' không được hỗ trợ bởi Source Link Handler"];
        }
    }
}

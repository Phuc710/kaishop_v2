<?php

class ManualInfoInventoryHandler extends BaseInventoryHandler
{
    private Order $orderModel;

    public function __construct(array $product)
    {
        parent::__construct($product);
        $this->orderModel = new Order();
    }

    public function getItems(array $filters): array
    {
        $items = $this->orderModel->getProductOrdersQueue((int) $this->product['id'], $filters);
        return $this->hydrateItems($items);
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed
            FROM `orders` 
            WHERE `product_id` = ?
        ");
        $stmt->execute([$this->product['id']]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['pending' => 0, 'completed' => 0];

        return [
            'total' => (int) ($counts['pending'] + $counts['completed']),
            'available' => (int) $counts['pending'], // Chờ xử ký
            'sold' => (int) $counts['completed'], // Đã xử lý
            'is_manual_queue' => true
        ];
    }

    public function getPartialView(): string
    {
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
                return ['success' => false, 'message' => "Hành động '{$action}' không được hỗ trợ bởi Manual Handler"];
        }
    }
}

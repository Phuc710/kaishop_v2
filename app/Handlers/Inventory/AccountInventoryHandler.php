<?php

class AccountInventoryHandler extends BaseInventoryHandler
{
    private ProductStock $stockModel;

    public function __construct(array $product)
    {
        parent::__construct($product);
        $this->stockModel = new ProductStock();
    }

    public function getItems(array $filters): array
    {
        $items = $this->stockModel->getByProduct((int) $this->product['id'], $filters);
        return $this->hydrateItems($items);
    }

    public function getStats(): array
    {
        $stats = $this->stockModel->getStatsForProducts([(int) $this->product['id']]);
        $res = $stats[(int) $this->product['id']] ?? ['available' => 0, 'sold' => 0];

        return [
            'total' => (int) ($res['available'] + $res['sold']),
            'available' => (int) $res['available'],
            'sold' => (int) $res['sold'],
            'is_manual_queue' => false
        ];
    }

    public function getPartialView(): string
    {
        return 'admin/products/stock/_account_list';
    }

    public function handleImport(string $content): array
    {
        $res = $this->stockModel->importBulk((int) $this->product['id'], $content);
        return [
            'success' => true,
            'added' => (int) ($res['added'] ?? 0),
            'skipped' => (int) ($res['skipped'] ?? 0)
        ];
    }

    public function handleAction(string $action, int $id, array $params = []): array
    {
        switch ($action) {
            case 'update':
                $content = trim((string) ($params['content'] ?? ''));
                if ($content === '') {
                    return ['success' => false, 'message' => 'Nội dung không được để trống'];
                }
                if ($this->stockModel->updateContent($id, $content)) {
                    return ['success' => true, 'message' => 'Đã cập nhật nội dung kho'];
                }
                return ['success' => false, 'message' => 'Không thể cập nhật (có thể đã bán)'];

            case 'delete':
                if ($this->stockModel->deleteAvailable($id)) {
                    return ['success' => true, 'message' => 'Đã xóa mục khỏi kho'];
                }
                return ['success' => false, 'message' => 'Không thể xóa (có thể đã bán)'];

            case 'clean':
                // For 'clean', $id is the productId itself
                $count = $this->stockModel->deleteAllAvailable($id);
                return ['success' => true, 'message' => "Đã dọn sạch {$count} item chưa bán."];

            default:
                return ['success' => false, 'message' => "Hành động '{$action}' không được hỗ trợ bởi Account Handler"];
        }
    }
}

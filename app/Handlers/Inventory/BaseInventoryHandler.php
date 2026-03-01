<?php

abstract class BaseInventoryHandler implements InventoryHandlerInterface
{
    protected PDO $db;
    protected array $product;

    public function __construct(array $product)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->product = $product;
    }

    /**
     * Shared hydration logic for items (timestamps, meta)
     */
    protected function hydrateItems(array $items): array
    {
        foreach ($items as &$item) {
            $item = FormatHelper::attachTimeMeta($item, 'created_at');
            if (isset($item['sold_at'])) {
                $item = FormatHelper::attachTimeMeta($item, 'sold_at');
            }
            if (isset($item['fulfilled_at'])) {
                $item = FormatHelper::attachTimeMeta($item, 'fulfilled_at');
            }
        }
        return $items;
    }

    abstract public function getItems(array $filters): array;
    abstract public function getStats(): array;
    abstract public function getPartialView(): string;
    abstract public function handleImport(string $content): array;
}

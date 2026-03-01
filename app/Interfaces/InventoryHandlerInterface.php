<?php

interface InventoryHandlerInterface
{
    /**
     * Get stock items or order queue based on product type
     */
    public function getItems(array $filters): array;

    /**
     * Get inventory statistics
     */
    public function getStats(): array;

    /**
     * Return the name of the partial view file to render the table/list
     */
    public function getPartialView(): string;

    /**
     * Handle bulk import or other product-specific actions
     */
    public function handleImport(string $content): array;

    /**
     * Handle stock-related actions (edit, delete, fulfill, cancel, etc.)
     * 
     * @param string $action The action name
     * @param int $id The item ID (stock ID or order ID)
     * @param array $params Additional parameters (content, reason, etc.)
     * @return array Response payload with success and message
     */
    public function handleAction(string $action, int $id, array $params = []): array;
}

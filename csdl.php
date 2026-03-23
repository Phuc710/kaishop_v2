<?php
/**
 * Database migration for business_invite_auto feature
 */
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "--- 1. Updating products table ---\n";
    // Check if columns already exist
    $columns = $db->query("SHOW COLUMNS FROM `products`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('duration_days', $columns)) {
        $db->exec("ALTER TABLE `products` ADD COLUMN `duration_days` INT NOT NULL DEFAULT 30 AFTER `info_instructions` ");
        echo "Added 'duration_days' to products\n";
    }
    if (!in_array('auto_invite', $columns)) {
        $db->exec("ALTER TABLE `products` ADD COLUMN `auto_invite` TINYINT(1) NOT NULL DEFAULT 1 AFTER `duration_days` ");
        echo "Added 'auto_invite' to products\n";
    }
    if (!in_array('farm_id', $columns)) {
        $db->exec("ALTER TABLE `products` ADD COLUMN `farm_id` INT NULL AFTER `auto_invite` ");
        echo "Added 'farm_id' to products\n";
    }

    // Update product_type enum if possible, or just accept the new type if it's VARCHAR
    // Based on diagnostic_schema, it's VARCHAR(50)
    echo "Product type column is handled via model constants.\n";

    echo "\n--- 2. Updating chatgpt_orders table ---\n";
    $cgColumns = $db->query("SHOW COLUMNS FROM `chatgpt_orders`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('source_order_id', $cgColumns)) {
        $db->exec("ALTER TABLE `chatgpt_orders` ADD COLUMN `source_order_id` BIGINT UNSIGNED NULL AFTER `assigned_farm_id` ");
        echo "Added 'source_order_id' to chatgpt_orders\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

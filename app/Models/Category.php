<?php

/**
 * Category Model
 * Handles category data operations
 */
class Category extends Model
{
    protected $table = 'categories';

    /**
     * Get all categories ordered by display_order
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY display_order ASC, id DESC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get only active (ON) categories for storefront
     * @return array
     */
    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'ON' ORDER BY display_order ASC, id ASC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find category by name
     * @param string $name
     * @return array|null
     */
    public function findByName($name)
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = ? LIMIT 1";
        $result = $this->query($sql, [$name])->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get category stats
     * @return array
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(status = 'ON') as active,
                    SUM(status = 'OFF') as inactive
                FROM {$this->table}";
        return $this->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Count products in a category
     * @param int $categoryId
     * @return int
     */
    public function countProducts($categoryId)
    {
        $sql = "SELECT COUNT(*) as cnt FROM products WHERE category_id = ?";
        $row = $this->query($sql, [$categoryId])->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['cnt'] ?? 0);
    }
}

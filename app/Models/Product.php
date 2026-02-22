<?php

/**
 * Product Model
 * Handles product data operations
 */
class Product extends Model
{
    protected $table = 'products';

    /**
     * Get all active products (for storefront)
     * @return array
     */
    public function getAvailable()
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'ON' AND p.is_hidden = 0 
                ORDER BY p.is_pinned DESC, p.display_order ASC, p.id DESC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get filtered products for admin panel
     * @param array $filters
     * @return array
     */
    public function getFiltered(array $filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            if (is_numeric($search)) {
                $where[] = "(p.name LIKE ? OR p.id = ?)";
                $params[] = "%{$search}%";
                $params[] = (int) $search;
            } else {
                $where[] = "(p.name LIKE ? OR p.slug LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = (int) $filters['category_id'];
        }

        if (isset($filters['hidden']) && $filters['hidden'] !== '') {
            $where[] = "p.is_hidden = ?";
            $params[] = (int) $filters['hidden'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause} 
                ORDER BY p.is_pinned DESC, p.display_order ASC, p.id DESC";
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find product by slug
     * @param string $slug
     * @return array|null
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? LIMIT 1";
        $result = $this->query($sql, [$slug])->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Generate unique slug from name
     * @param string $name
     * @param int|null $excludeId
     * @return string
     */
    public function generateSlug($name, $excludeId = null)
    {
        $slug = $this->toSlug($name);
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
            $params = [$slug];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $existing = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
            if (!$existing)
                break;

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Convert Vietnamese string to URL slug
     * @param string $str
     * @return string
     */
    private function toSlug($str)
    {
        $map = [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A',
            'Á' => 'A',
            'Ạ' => 'A',
            'Ả' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ầ' => 'A',
            'Ấ' => 'A',
            'Ậ' => 'A',
            'Ẩ' => 'A',
            'Ẫ' => 'A',
            'Ă' => 'A',
            'Ằ' => 'A',
            'Ắ' => 'A',
            'Ặ' => 'A',
            'Ẳ' => 'A',
            'Ẵ' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ẹ' => 'E',
            'Ẻ' => 'E',
            'Ẽ' => 'E',
            'Ê' => 'E',
            'Ề' => 'E',
            'Ế' => 'E',
            'Ệ' => 'E',
            'Ể' => 'E',
            'Ễ' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Ị' => 'I',
            'Ỉ' => 'I',
            'Ĩ' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ọ' => 'O',
            'Ỏ' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ồ' => 'O',
            'Ố' => 'O',
            'Ộ' => 'O',
            'Ổ' => 'O',
            'Ỗ' => 'O',
            'Ơ' => 'O',
            'Ờ' => 'O',
            'Ớ' => 'O',
            'Ợ' => 'O',
            'Ở' => 'O',
            'Ỡ' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Ụ' => 'U',
            'Ủ' => 'U',
            'Ũ' => 'U',
            'Ư' => 'U',
            'Ừ' => 'U',
            'Ứ' => 'U',
            'Ự' => 'U',
            'Ử' => 'U',
            'Ữ' => 'U',
            'Ỳ' => 'Y',
            'Ý' => 'Y',
            'Ỵ' => 'Y',
            'Ỷ' => 'Y',
            'Ỹ' => 'Y',
            'Đ' => 'D',
        ];

        $str = strtr($str, $map);
        $str = mb_strtoupper($str, 'UTF-8');
        $str = preg_replace('/[^A-Z0-9\s_]/', '', $str);
        $str = preg_replace('/[\s_]+/', '_', $str);
        return trim($str, '-');
    }

    /**
     * Toggle a boolean field (is_hidden, is_pinned, is_active)
     * @param int $id
     * @param string $field
     * @return array
     */
    public function toggleField($id, $field)
    {
        $allowed = ['is_hidden', 'is_pinned', 'is_active'];
        if (!in_array($field, $allowed)) {
            return ['success' => false, 'message' => 'Invalid field'];
        }

        $product = $this->find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $newValue = $product[$field] ? 0 : 1;
        $success = $this->update($id, [$field => $newValue]);

        return ['success' => $success, 'new_value' => $newValue];
    }

    /**
     * Toggle product status ON/OFF
     * @param int $id
     * @return array
     */
    public function toggleStatus($id)
    {
        $product = $this->find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $newStatus = ($product['status'] === 'ON') ? 'OFF' : 'ON';
        $success = $this->update($id, ['status' => $newStatus]);

        return ['success' => $success, 'new_value' => $newStatus];
    }

    /**
     * Get categories list from categories table
     * @return array
     */
    public function getCategories()
    {
        $sql = "SELECT id, name FROM categories WHERE status = 'ON' ORDER BY display_order ASC, name ASC";
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product stats
     * @return array
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(status = 'ON') as active,
                    SUM(is_hidden = 1) as hidden,
                    SUM(is_pinned = 1) as pinned
                FROM {$this->table}";
        return $this->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
}

<?php

/**
 * GiftCode Model
 * Quản lý mã giảm giá - CRUD + thống kê
 * Table: gift_code
 */
class GiftCode extends Model
{
    protected $table = 'gift_code';
    protected $primaryKey = 'id';

    /**
     * Lấy tất cả mã giảm giá, sắp xếp mới nhất
     */
    public function getAll()
    {
        return $this->query(
            "SELECT * FROM {$this->table} ORDER BY id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm mã theo ID
     */
    public function findById($id)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Tìm mã theo code string
     */
    public function findByCode($code)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE giftcode = ?",
            [$code]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Thêm mã giảm giá mới
     */
    public function store($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (giftcode, giamgia, type, product_ids, min_order, max_order, soluong, dadung, status, time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'ON', NOW())";

        $this->query($sql, [
            $data['giftcode'],
            (int) $data['giamgia'],
            $data['type'] ?? 'all',
            $data['product_ids'] ?? null,
            (int) ($data['min_order'] ?? 0),
            (int) ($data['max_order'] ?? 0),
            (int) ($data['soluong'] ?? 1),
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Cập nhật mã giảm giá
     */
    public function updateById($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                giftcode = ?, giamgia = ?, type = ?, product_ids = ?,
                min_order = ?, max_order = ?, soluong = ?, status = ?
                WHERE id = ?";

        return $this->query($sql, [
            $data['giftcode'],
            (int) $data['giamgia'],
            $data['type'] ?? 'all',
            $data['product_ids'] ?? null,
            (int) ($data['min_order'] ?? 0),
            (int) ($data['max_order'] ?? 0),
            (int) ($data['soluong'] ?? 1),
            $data['status'] ?? 'ON',
            (int) $id,
        ]);
    }

    /**
     * Xóa mã giảm giá
     */
    public function deleteById($id)
    {
        return $this->query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [(int) $id]
        );
    }

    /**
     * Lấy tên sản phẩm theo product_ids (comma-separated)
     */
    public function getProductNames($productIds)
    {
        if (empty($productIds))
            return [];

        $ids = array_filter(array_map('intval', explode(',', $productIds)));
        if (empty($ids))
            return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $this->query(
            "SELECT id, name FROM products WHERE id IN ($placeholders)",
            $ids
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy nhật ký sử dụng mã giảm giá
     * Tìm trong lich_su_hoat_dong theo keyword giftcode
     */
    public function getUsageLog($giftcode)
    {
        return $this->query(
            "SELECT * FROM lich_su_hoat_dong 
             WHERE hoatdong LIKE ? 
             ORDER BY id DESC",
            ['%' . $giftcode . '%']
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra mã có hợp lệ không
     */
    public function isValid($giftcode)
    {
        $code = $this->findByCode($giftcode);
        if (!$code)
            return false;
        if ($code['status'] !== 'ON')
            return false;
        if ($code['soluong'] > 0 && $code['dadung'] >= $code['soluong'])
            return false;
        if (!empty($code['expired_at']) && strtotime($code['expired_at']) < time())
            return false;
        return true;
    }

    /**
     * Tăng số lượng đã dùng
     */
    public function incrementUsage($id)
    {
        return $this->query(
            "UPDATE {$this->table} SET dadung = dadung + 1 WHERE id = ?",
            [(int) $id]
        );
    }
}

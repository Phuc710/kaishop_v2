<?php

/**
 * Product Model
 * Handles product data operations
 */
class Product extends Model {
    protected $table = 'products';
    
    /**
     * Get all active products
     * @return array
     */
    public function getAvailable() {
        return $this->query("SELECT * FROM {$this->table} WHERE status = 'ON' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    

}

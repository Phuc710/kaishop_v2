<?php

/**
 * SourceCode Model
 */
class SourceCode extends Model {
    protected $table = 'lich_su_mua_code';
    
    public function getAvailable() {
        return $this->query("SELECT * FROM `list_code` WHERE `status` = 'ON'")->fetchAll();
    }
    
    public function getUserPurchases($username) {
        return $this->query(
            "SELECT * FROM `lich_su_mua_code` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
}

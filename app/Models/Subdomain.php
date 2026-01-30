<?php

/**
 * Subdomain Model
 */
class Subdomain extends Model {
    protected $table = 'lich_su_subdomain';
    
    public function getAvailable() {
        return $this->query("SELECT * FROM `list_subdomain` WHERE `status` = 'ON'")->fetchAll();
    }
    
    public function getUserSubdomains($username) {
        return $this->query(
            "SELECT * FROM `lich_su_subdomain` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
}

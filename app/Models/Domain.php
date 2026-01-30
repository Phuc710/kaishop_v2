<?php

/**
 * Domain Model
 * Handles domain data
 */
class Domain extends Model {
    protected $table = 'history_domain';
    
    /**
     * Get available domain extensions
     */
    public function getAvailableExtensions() {
        return $this->query("SELECT * FROM `ds_domain` WHERE `status` = 'ON'")->fetchAll();
    }
    
    /**
     * Get user's domains
     */
    public function getUserDomains($username) {
        return $this->query(
            "SELECT * FROM `history_domain` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
    
    /**
     * Create domain purchase record
     */
    public function createDomain($data) {
        return $this->create([
            'username' => $data['username'],
            'domain' => $data['domain'],
            'ten_mien' => $data['ten_mien'],
            'duoimien' => $data['duoimien'],
            'zone_id' => $data['zone_id'],
            'nameserver' => $data['nameserver'],
            'ngay_mua' => $data['ngay_mua'],
            'ngay_het' => $data['ngay_het'],
            'status' => 'hoatdong'
        ]);
    }
    
    /**
     * Check if domain exists for user
     */
    public function userHasDomain($username, $domain) {
        $result = $this->query(
            "SELECT COUNT(*) FROM `history_domain` WHERE `username` = ? AND `domain` = ?",
            [$username, $domain]
        )->fetchColumn();
        
        return $result > 0;
    }
}

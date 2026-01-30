<?php

/**
 * Hosting Model
 * Handles hosting data
 */
class Hosting extends Model {
    protected $table = 'lich_su_mua_host';
    
    /**
     * Get hosting packages
     */
    public function getPackages() {
        return $this->query("SELECT * FROM `list_host` WHERE `status` = 'ON'")->fetchAll();
    }
    
    /**
     * Get user's hostings
     */
    public function getUserHostings($username) {
        return $this->query(
            "SELECT * FROM `lich_su_mua_host` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
    
    /**
     * Create hosting purchase record
     */
    public function createHosting($data) {
        return $this->create([
            'username' => $data['username'],
            'domain' => $data['domain'],
            'email' => $data['email'],
            'goi_host' => $data['goi_host'],
            'server_host' => $data['server_host'],
            'gia_host' => $data['gia_host'],
            'tk_host' => $data['tk_host'],
            'mk_host' => $data['mk_host'],
            'ngay_mua' => $data['ngay_mua'],
            'ngay_het' => $data['ngay_het'],
            'status' => 'dangtao',
            'time' => date('h:i d-m-Y')
        ]);
    }
    
    /**
     * Get hosting by domain
     */
    public function getByDomain($username, $domain) {
        return $this->query(
            "SELECT * FROM `lich_su_mua_host` WHERE `username` = ? AND `domain` = ? LIMIT 1",
            [$username, $domain]
        )->fetch();
    }
}

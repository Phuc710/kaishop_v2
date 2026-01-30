<?php

/**
 * Logo Model
 */
class Logo extends Model {
    protected $table = 'lich_su_tao_logo';
    
    public function getUserLogos($username) {
        return $this->query(
            "SELECT * FROM `lich_su_tao_logo` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
    
    public function createOrder($data) {
        return $this->create([
            'username' => $data['username'],
            'name' => $data['name'],
            'request' => $data['request'],
            'status' => $data['status'],
            'time' => date('h:i d-m-Y')
        ]);
    }
}

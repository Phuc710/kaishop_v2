<?php

/**
 * Website Model
 */
class Website extends Model {
    protected $table = 'lich_su_tao_web';
    
    public function getTemplates() {
        return $this->query("SELECT * FROM `ds_mau_web` WHERE `status` = 'ON'")->fetchAll();
    }
    
    public function getUserWebsites($username) {
        return $this->query(
            "SELECT * FROM `lich_su_tao_web` WHERE `username` = ? ORDER BY `id` DESC",
            [$username]
        )->fetchAll();
    }
    
    public function createOrder($data) {
        return $this->create([
            'username' => $data['username'],
            'template_id' => $data['template_id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'time' => date('h:i d-m-Y')
        ]);
    }
}

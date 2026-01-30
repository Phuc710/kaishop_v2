<?php

/**
 * Transaction Model
 * Handles payment transactions
 */
class Transaction extends Model {
    protected $table = 'history_nap_bank'; // Can be bank or card table
    
    /**
     * Get user transactions
     */
    public function getUserTransactions($userId) {
        // Get from multiple tables and combine
        $bankTransactions = $this->query(
            "SELECT * FROM `history_nap_bank` WHERE `username` = ? ORDER BY `id` DESC",
            [$userId]
        )->fetchAll();
        
        $cardTransactions = $this->query(
            "SELECT * FROM `history_nap_the` WHERE `username` = ? ORDER BY `id` DESC",
            [$userId]
        )->fetchAll();
        
        // Merge and sort
        return array_merge($bankTransactions, $cardTransactions);
    }
    
    /**
     * Get bank list
     */
    public function getBankList() {
        return $this->query("SELECT * FROM `list_bank` WHERE `status` = 'ON'")->fetchAll();
    }
    
    /**
     * Create bank transaction
     */
    public function createBankTransaction($data) {
        return $this->query(
            "INSERT INTO `history_nap_bank` SET
                `trans_id` = ?,
                `username` = ?,
                `type` = ?,
                `stk` = ?,
                `ctk` = ?,
                `thucnhan` = ?,
                `status` = ?,
                `time` = ?",
            [
                $data['trans_id'],
                $data['username'],
                $data['type'],
                $data['stk'],
                $data['ctk'],
                $data['thucnhan'],
                $data['status'],
                $data['time']
            ]
        );
    }
}

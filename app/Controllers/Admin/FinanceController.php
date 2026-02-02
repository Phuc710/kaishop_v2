<?php

/**
 * Admin Finance Controller
 */
class FinanceController extends Controller {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Check admin access
     */
    private function requireAdmin() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Access denied - Admin only');
        }
    }
    
    // ========== BANK MANAGEMENT ==========
    
    /**
     * List all banks
     */
    public function banks() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        $banks = $connection->query("SELECT * FROM `list_bank` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/finance/banks', [
            'banks' => $banks,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add bank
     */
    public function addBank() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/finance/add_bank', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add bank
     */
    public function storeBank() {
        $this->requireAdmin();
        global $connection;
        
        $ctk = $this->post('ctk');
        $stk = $this->post('stk');
        $status = $this->post('status');
        $stk_id = $this->post('stk_id');
        $user_id = $this->post('user_id');
        $password = $this->post('password');
        $token = $this->post('token');
        $type = $this->post('type');

        $ctk = $connection->real_escape_string($ctk);
        $stk = $connection->real_escape_string($stk);
        $stk_id = $connection->real_escape_string($stk_id);
        $user_id = $connection->real_escape_string($user_id);
        $password = $connection->real_escape_string($password);
        $token = $connection->real_escape_string($token);
        $type = $connection->real_escape_string($type);
        
        $sql = "INSERT INTO `list_bank` (`ctk`, `stk`, `status`, `stk_id`, `user_id`, `password`, `token`, `type`) 
                VALUES ('$ctk', '$stk', '$status', '$stk_id', '$user_id', '$password', '$token', '$type')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm bank thành công'];
            $this->redirect(url('admin/finance/banks'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/finance/banks/add'));
        }
    }
    
    /**
     * Form to edit bank
     */
    public function editBank($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $bank = $connection->query("SELECT * FROM `list_bank` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$bank) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Bank không tồn tại'];
            $this->redirect(url('admin/finance/banks'));
        }
        
        $this->view('admin/finance/edit_bank', [
            'chungapi' => $chungapi,
            'bank' => $bank
        ]);
    }
    
    /**
     * Process edit bank
     */
    public function updateBank($id) {
        $this->requireAdmin();
        global $connection;
        
        $ctk = $this->post('ctk');
        $stk = $this->post('stk');
        $status = $this->post('status');
        $stk_id = $this->post('stk_id');
        $user_id = $this->post('user_id');
        $password = $this->post('password');
        $token = $this->post('token');
        $type = $this->post('type');

        $ctk = $connection->real_escape_string($ctk);
        $stk = $connection->real_escape_string($stk);
        $stk_id = $connection->real_escape_string($stk_id);
        $user_id = $connection->real_escape_string($user_id);
        $password = $connection->real_escape_string($password);
        $token = $connection->real_escape_string($token);
        $type = $connection->real_escape_string($type);
        
        $sql = "UPDATE `list_bank` SET 
                `ctk` = '$ctk', 
                `stk` = '$stk', 
                `status` = '$status', 
                `stk_id` = '$stk_id', 
                `user_id` = '$user_id', 
                `password` = '$password', 
                `token` = '$token', 
                `type` = '$type' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/finance/banks'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/finance/banks/edit/' . $id));
        }
    }
    
    // ========== HISTORY MANAGEMENT ==========
    
    /**
     * Bank histories
     */
    public function historyBanks() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        $histories = $connection->query("SELECT * FROM `history_nap_bank` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/finance/history_banks', [
            'histories' => $histories,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Card histories
     */
    public function historyCards() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        $histories = $connection->query("SELECT * FROM `history_nap_the` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/finance/history_cards', [
            'histories' => $histories,
            'chungapi' => $chungapi
        ]);
    }
    
    // ========== GIFTCODE MANAGEMENT ==========
    
    /**
     * List all giftcodes
     */
    public function giftcodes() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        $giftcodes = $connection->query("SELECT * FROM `gift_code` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/finance/giftcodes', [
            'giftcodes' => $giftcodes,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add giftcode
     */
    public function addGiftcode() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/finance/add_giftcode', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add giftcode
     */
    public function storeGiftcode() {
        $this->requireAdmin();
        global $connection;
        
        $giftcode = $this->post('giftcode');
        $giamgia = $this->post('giamgia');
        $type = $this->post('type');
        $soluong = $this->post('soluong');
        $dadung = $this->post('dadung');
        $status = $this->post('status');

        $giftcode = $connection->real_escape_string($giftcode);
        $type = $connection->real_escape_string($type);
        
        $sql = "INSERT INTO `gift_code` (`giftcode`, `giamgia`, `type`, `soluong`, `dadung`, `status`) 
                VALUES ('$giftcode', '$giamgia', '$type', '$soluong', '$dadung', '$status')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm giftcode thành công'];
            $this->redirect(url('admin/finance/giftcodes'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/finance/giftcodes/add'));
        }
    }
    
    /**
     * Form to edit giftcode
     */
    public function editGiftcode($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $giftcode = $connection->query("SELECT * FROM `gift_code` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$giftcode) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Giftcode không tồn tại'];
            $this->redirect(url('admin/finance/giftcodes'));
        }
        
        $this->view('admin/finance/edit_giftcode', [
            'chungapi' => $chungapi,
            'giftcode' => $giftcode
        ]);
    }
    
    /**
     * Process update giftcode
     */
    public function updateGiftcode($id) {
        $this->requireAdmin();
        global $connection;
        
        $giftcode = $this->post('giftcode');
        $giamgia = $this->post('giamgia');
        $type = $this->post('type');
        $soluong = $this->post('soluong');
        $dadung = $this->post('dadung');
        $status = $this->post('status');

        $giftcode = $connection->real_escape_string($giftcode);
        $type = $connection->real_escape_string($type);
        
        $sql = "UPDATE `gift_code` SET 
                `giftcode` = '$giftcode', 
                `giamgia` = '$giamgia', 
                `type` = '$type', 
                `soluong` = '$soluong', 
                `dadung` = '$dadung', 
                `status` = '$status' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/finance/giftcodes'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/finance/giftcodes/edit/' . $id));
        }
    }
}

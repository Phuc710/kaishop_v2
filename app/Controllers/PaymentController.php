<?php

/**
 * Payment Controller
 * Handles card and bank deposits
 */
class PaymentController extends Controller {
    private $authService;
    private $transactionModel;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->transactionModel = new Transaction();
    }
    
    /**
     * Show card payment page
     */
    public function showCard() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();
        
        $this->view('payment/card', [
            'user' => $user,
            'siteConfig' => $siteConfig
        ]);
    }
    
    /**
     * Show bank payment page
     */
    public function showBank() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();
        
        // Get bank list
        $banks = $this->transactionModel->getBankList();
        
        $this->view('payment/bank', [
            'user' => $user,
            'siteConfig' => $siteConfig,
            'banks' => $banks
        ]);
    }
    
    /**
     * Process card payment (AJAX)
     */
    public function processCard() {
        if (!$this->authService->isLoggedIn()) {
            return $this->json(['success' => false, 'message' => 'Not logged in'], 401);
        }
        
        // Card payment logic here (integrate with card service)
        // This would depend on your card payment provider
        
        return $this->json(['success' => true, 'message' => 'Processing...']);
    }
    
    /**
     * Get transaction history
     */
    public function history() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        $transactions = $this->transactionModel->getUserTransactions($user['id']);
        
        $this->view('payment/history', [
            'user' => $user,
            'transactions' => $transactions
        ]);
    }
}

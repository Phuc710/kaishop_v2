<?php

/**
 * Auth Controller
 * Handles authentication (login, register, logout, password reset)
 */
class AuthController extends Controller {
    private $userModel;
    private $authService;
    private $validator;
    
    public function __construct() {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new AuthValidator();
    }
    
    /**
     * Show login page
     */
    public function showLogin() {
        // Already logged in? Redirect to home
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }
        
        global $chungapi;
        $this->view('auth/login', ['chungapi' => $chungapi]);
    }
    
    /**
     * Process login (AJAX)
     */
    public function login() {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        
        // Validate
        $errors = $this->validator->validateLogin($username, $password);
        if (!empty($errors)) {
            return $this->json(['success' => false, 'message' => $errors[0]], 400);
        }
        
        // Check credentials
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác'], 401);
        }
        
        // Verify password (using sha1(md5()) as in old code)
        if ($user['password'] !== sha1(md5($password))) {
            return $this->json(['success' => false, 'message' => 'Mật khẩu không chính xác'], 401);
        }
        
        // Generate new session
        $sessionToken = $this->generateSessionToken();
        $this->userModel->update($user['id'], ['session' => $sessionToken]);
        
        // Set session
        $_SESSION['session'] = $sessionToken;
        
        return $this->json(['success' => true]);
    }
    
    /**
     * Show register page
     */
    public function showRegister() {
        if ($this->authService->isLoggedIn()) {
            $this->redirect(BASE_URL . '/');
        }
        
        global $chungapi;
        $this->view('auth/register', ['chungapi' => $chungapi]);
    }
    
    /**
     * Process registration (AJAX)
     */
    public function register() {
        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        $email = trim($this->post('email', ''));
        
        // Validate
        $errors = $this->validator->validateRegister($username, $password, $email);
        if (!empty($errors)) {
            return $this->json(['success' => false, 'message' => $errors[0]], 400);
        }
        
        // Check if username exists
        if ($this->userModel->findByUsername($username)) {
            return $this->json(['success' => false, 'message' => 'Tên đăng nhập đã được sử dụng'], 400);
        }
        
        // Check if email exists
        if ($this->userModel->emailExists($email)) {
            return $this->json(['success' => false, 'message' => 'Email đã được sử dụng'], 400);
        }
        
        // Create user
        global $ip_address;
        $hashedPassword = sha1(md5($password));
        $apiKey = md5(bin2hex(random_bytes(16)));
        $time = date('h:i d-m-Y');
        
        $userId = $this->userModel->create([
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'level' => '0',
            'tong_nap' => '0',
            'money' => '0',
            'bannd' => '0',
            'ip' => $ip_address,
            'api_key' => $apiKey,
            'time' => $time
        ]);
        
        if ($userId) {
            // Auto login after register
            $sessionToken = $this->generateSessionToken();
            $this->userModel->update($userId, ['session' => $sessionToken]);
            $_SESSION['session'] = $sessionToken;
            
            // Send welcome email (optional - using existing function)
            // sendCSM($email, $username, 'Welcome', $noi_dung, 'server');
            
            return $this->json(['success' => true]);
        }
        
        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        $this->redirect(BASE_URL . '/login');
    }
    
    /**
     * Generate random session token
     */
    private function generateSessionToken() {
        $characters = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $token = '';
        for ($i = 0; $i < 32; $i++) {
            $token .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $token;
    }
}

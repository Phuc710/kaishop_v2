<?php

/**
 * Profile Controller
 * Handles user profile operations + embedded deposit panel
 */
class ProfileController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private UserValidator $validator;
    private DepositService $depositService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->validator = new UserValidator();
        $this->depositService = new DepositService();
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $this->setNoCache();
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        $siteConfig = Config::getSiteConfig();

        $profileSection = trim((string) $this->get('section', ''));
        $allowedSections = ['profile', 'balance', 'deposit', 'telegram'];
        if (!in_array($profileSection, $allowedSections, true)) {
            $profileSection = 'profile';
        }
        if ($profileSection === 'deposit') {
            $profileSection = 'balance';
        }

        $requestedDepositMethod = trim((string) $this->get('method', ''));
        $depositPanel = $this->depositService->getProfilePanelData($siteConfig, $user, $requestedDepositMethod !== '' ? $requestedDepositMethod : null);

        $depositMethodCode = (string) ($depositPanel['active_method'] ?? DepositService::METHOD_BANK_SEPAY);
        $depositRouteMethod = $depositMethodCode === DepositService::METHOD_BANK_SEPAY ? 'bank' : $depositMethodCode;

        $this->view('profile/index', [
            'user' => $user,
            'username' => $user['username'],
            'chungapi' => $siteConfig,
            'activePage' => $profileSection === 'balance'
                ? 'balance'
                : ($profileSection === 'telegram' ? 'telegram' : 'profile'),
            'profileSection' => $profileSection,
            'depositPanel' => $depositPanel,
            'depositRouteMethod' => $depositRouteMethod,
        ]);
    }

    /**
     * Update profile (AJAX endpoint)
     */
    public function update()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập'
            ], 401);
        }

        $user = $this->authService->getCurrentUser();
        $newEmail = trim((string) $this->post('email', ''));
        $fullName = trim((string) $this->post('full_name', ''));
        $avatarUrl = trim((string) $this->post('avatar_url', ''));
        $twofaFieldProvided = array_key_exists('twofa_enabled', $_POST);
        $twofaEnabled = $twofaFieldProvided
            ? in_array((string) $this->post('twofa_enabled', '0'), ['1', 'true', 'on'], true)
            : ((int) ($user['twofa_enabled'] ?? 0) === 1);

        $errors = $this->validator->validateEmail($newEmail);
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => $errors[0]
            ], 400);
        }

        if ($this->userModel->emailExists($newEmail, $user['id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email đã được sử dụng bởi tài khoản khác'
            ], 400);
        }

        $updateData = [
            'email' => $newEmail,
            'full_name' => $fullName,
            'twofa_enabled' => $twofaEnabled ? 1 : 0,
        ];

        if ($avatarUrl !== '') {
            $updateData['avatar_url'] = $avatarUrl;
        }

        $success = $this->userModel->update($user['id'], $updateData);

        if ($success) {
            return $this->json([
                'success' => true,
                'message' => 'Cập nhật thông tin bảo mật thành công',
                'twofa_enabled' => $twofaEnabled ? 1 : 0,
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra, vui lòng thử lại'
        ], 500);
    }

    /**
     * Upload user avatar (AJAX endpoint)
     */
    public function uploadAvatar()
    {
        if (!$this->authService->isLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập'
            ], 401);
        }

        if (empty($_FILES['avatar_file'])) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy file tải lên'
            ], 400);
        }

        $user = $this->authService->getCurrentUser();
        $file = $_FILES['avatar_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->json([
                'success' => false,
                'message' => 'Lỗi upload file (code: ' . $file['error'] . ')'
            ], 400);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Định dạng file không hỗ trợ. Vui lòng chọn JPG, PNG, GIF hoặc WebP.'
            ], 400);
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'message' => 'Kích thước file quá lớn (tối đa 10MB).'
            ], 400);
        }

        $uploadDir = __DIR__ . '/../../assets/uploads/images/';
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể tạo thư mục lưu trữ'
            ], 500);
        }

        $newFilename = 'avatar_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
        $destination = $uploadDir . $newFilename;

        $success = $this->resizeAvatarToWebP($file['tmp_name'], $destination, 300, 85);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Lỗi xử lý và chuyển đổi hình ảnh'
            ], 500);
        }

        $avatarUrl = rtrim((string) APP_DIR, '/') . '/assets/uploads/images/' . $newFilename;

        return $this->json([
            'success' => true,
            'message' => 'Tải ảnh đại diện thành công',
            'avatar_url' => $avatarUrl
        ]);
    }

    /**
     * Helper to crop and resize avatar to square WebP
     */
    private function resizeAvatarToWebP($source, $destination, $targetSize = 300, $quality = 85): bool
    {
        $info = @getimagesize($source);
        if (!$info || empty($info['mime'])) {
            return false;
        }
        $mime = $info['mime'];
        $srcW = (int) $info[0];
        $srcH = (int) $info[1];

        switch ($mime) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($source);
                break;
            case 'image/gif':
                $srcImage = @imagecreatefromgif($source);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$srcImage) {
            return false;
        }

        $canvas = imagecreatetruecolor($targetSize, $targetSize);
        if (!$canvas) {
            imagedestroy($srcImage);
            return false;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        if ($srcW > $srcH) {
            $cropSize = $srcH;
            $srcX = (int) round(($srcW - $srcH) / 2);
            $srcY = 0;
        } else {
            $cropSize = $srcW;
            $srcX = 0;
            $srcY = (int) round(($srcH - $srcW) / 2);
        }

        imagecopyresampled($canvas, $srcImage, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $cropSize, $cropSize);
        imagedestroy($srcImage);

        $result = @imagewebp($canvas, $destination, $quality);
        imagedestroy($canvas);
        return (bool) $result;
    }
}

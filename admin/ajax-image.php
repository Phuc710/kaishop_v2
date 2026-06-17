<?php
header('Content-Type: application/json; charset=utf-8');

// Increase upload limits for this endpoint
@ini_set('upload_max_filesize', '32M');
@ini_set('post_max_size', '64M');
@ini_set('memory_limit', '256M');

ob_start();
require_once __DIR__ . '/../config/app.php';
ob_end_clean();

// Detect when post_max_size is exceeded (PHP silently drops $_POST and $_FILES)
$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
    http_response_code(413);
    echo json_encode([
        'success' => false,
        'error' => 'File upload quá lớn. Giới hạn tối đa là 32MB mỗi file.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function image_json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function image_is_admin(): bool
{
    if (!empty($_SESSION['admin'])) {
        return true;
    }

    if (class_exists('AuthService')) {
        try {
            $auth = new AuthService();
            if ($auth->isLoggedIn()) {
                $u = $auth->getCurrentUser();
                return is_array($u) && (int) ($u['level'] ?? 0) === 9;
            }
        } catch (Throwable $e) {
            return false;
        }
    }

    return false;
}

if (!image_is_admin()) {
    image_json_response(403, ['success' => false, 'error' => 'Access denied']);
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    image_json_response(405, ['success' => false, 'error' => 'Method not allowed']);
}

if (function_exists('csrf_validate_request') && !csrf_validate_request()) {
    image_json_response(419, ['success' => false, 'error' => 'CSRF token invalid']);
}

$imageDir = __DIR__ . '/../assets/images/products/';
$imageUrlPrefix = rtrim((string) APP_DIR, '/') . '/assets/images/products/';

if (!is_dir($imageDir) && !@mkdir($imageDir, 0777, true) && !is_dir($imageDir)) {
    image_json_response(500, ['success' => false, 'error' => 'Khong the tao thu muc anh']);
}

$action = trim((string) ($_POST['action'] ?? ''));

function convertToWebP($source, $destination, $quality = 82, $maxW = 1000, $maxH = 1000)
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

    // Calculate new dimensions keeping the aspect ratio
    $scale = min($maxW / $srcW, $maxH / $srcH);
    if ($scale < 1) {
        $newW = (int) round($srcW * $scale);
        $newH = (int) round($srcH * $scale);
    } else {
        $newW = $srcW;
        $newH = $srcH;
    }

    // Create canvas at new size
    $canvas = imagecreatetruecolor($newW, $newH);
    if (!$canvas) {
        imagedestroy($srcImage);
        return false;
    }

    // Preserve transparency for PNG, WebP and GIF
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    imagealphablending($canvas, true);

    imagecopyresampled($canvas, $srcImage, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($srcImage);

    $result = @imagewebp($canvas, $destination, $quality);
    imagedestroy($canvas);
    return (bool) $result;
}

if ($action === 'list') {
    $images = [];
    $files = @scandir($imageDir) ?: [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $fullPath = $imageDir . $file;
        if (!is_file($fullPath)) {
            continue;
        }
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif', 'svg'], true)) {
            continue;
        }
        $images[] = [
            'name' => $file,
            'url' => $imageUrlPrefix . rawurlencode($file),
            'path' => 'assets/images/products/' . $file,
            'mtime' => @filemtime($fullPath) ?: 0,
        ];
    }

    usort($images, static function (array $a, array $b): int {
        return (int) ($b['mtime'] ?? 0) <=> (int) ($a['mtime'] ?? 0);
    });

    foreach ($images as &$img) {
        unset($img['mtime']);
    }
    unset($img);

    image_json_response(200, ['success' => true, 'data' => $images]);
}

if ($action === 'upload') {
    if (empty($_FILES['files'])) {
        image_json_response(400, ['success' => false, 'error' => 'Khong co file duoc gui len']);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $uploadedFiles = [];
    $errors = [];
    $files = $_FILES['files'];
    $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;

    for ($i = 0; $i < $count; $i++) {
        $name = (string) ($files['name'][$i] ?? '');
        $tmpName = (string) ($files['tmp_name'][$i] ?? '');
        $errorCode = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        if ($errorCode !== UPLOAD_ERR_OK) {
            $errors[] = 'Loi upload file: ' . $name . ' (code ' . $errorCode . ')';
            continue;
        }
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $errors[] = 'File tam khong hop le: ' . $name;
            continue;
        }
        if ($ext === '' || !in_array($ext, $allowedExts, true)) {
            $errors[] = 'Dinh dang file khong ho tro: ' . $name;
            continue;
        }

        try {
            $success = false;
            $newFileName = '';

            if ($ext !== 'svg' && function_exists('imagewebp') && function_exists('getimagesize')) {
                $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                $targetPath = $imageDir . $newFileName;
                $success = convertToWebP($tmpName, $targetPath);
            }

            if (!$success) {
                $newFileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $imageDir . $newFileName;
                $success = @move_uploaded_file($tmpName, $targetPath);
            }

            if ($success) {
                $uploadedFiles[] = $imageUrlPrefix . rawurlencode($newFileName);
            } else {
                $errors[] = 'Khong the xu ly anh: ' . $name;
            }
        } catch (Throwable $e) {
            $errors[] = 'Loi he thong voi anh ' . $name . ': ' . $e->getMessage();
        }
    }

    if (count($uploadedFiles) > 0) {
        image_json_response(200, [
            'success' => true,
            'message' => 'Upload thanh cong ' . count($uploadedFiles) . ' anh.',
            'files' => $uploadedFiles,
            'errors' => $errors,
        ]);
    }

    image_json_response(400, [
        'success' => false,
        'error' => count($errors) > 0 ? implode("\n", $errors) : 'Khong co file nao duoc upload thanh cong.',
        'details' => $errors,
    ]);
}

if ($action === 'delete') {
    $incoming = $_POST['files'] ?? [];
    if (!is_array($incoming)) {
        image_json_response(400, ['success' => false, 'error' => 'Danh sach file khong hop le']);
    }

    $deletedCount = 0;
    foreach ($incoming as $fileUrl) {
        $fileName = rawurldecode(basename((string) $fileUrl));
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            continue;
        }
        $filePath = $imageDir . $fileName;
        if (is_file($filePath) && @unlink($filePath)) {
            $deletedCount++;
        }
    }

    image_json_response(200, ['success' => true, 'message' => 'Da xoa ' . $deletedCount . ' anh.']);
}

image_json_response(400, ['success' => false, 'error' => 'Invalid action']);

<?php
require_once('../hethong/config.php');

// Check if user is admin (security check)
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$imageDir = '../assets/images/products/';

// Ensure directory exists
if (!file_exists($imageDir)) {
    mkdir($imageDir, 0777, true);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Helper function to convert image to WebP
function convertToWebP($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            return copy($source, $destination); // Already WebP
        default:
            return false;
    }

    // Save as WebP
    $result = imagewebp($image, $destination, $quality);
    imagedestroy($image);
    return $result;
}

if ($action == 'list') {
    $images = [];
    $files = scandir($imageDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            // Only list WebP images (since we convert everything) or allow others for legacy
            if (in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                $images[] = [
                    'name' => $file,
                    'url' => '/kaishop_v2/assets/images/products/' . $file,
                    'path' => 'assets/images/products/' . $file
                ];
            }
        }
    }
    // Sort by newest first
    usort($images, function($a, $b) use ($imageDir) {
        return filemtime($imageDir . $b['name']) - filemtime($imageDir . $a['name']);
    });
    
    echo json_encode(['data' => $images]);
    exit;
}

if ($action == 'upload') {
    if (isset($_FILES['files'])) {
        $uploadedFiles = [];
        $errors = [];
        $files = $_FILES['files'];
        
        // Loop through multiple files
        $count = count($files['name']);
        
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] == 0) {
                $fileName = pathinfo($files['name'][$i], PATHINFO_FILENAME);
                $tmpName = $files['tmp_name'][$i];
                
                // Generate unique name
                $newFileName = time() . '_' . uniqid() . '.webp';
                $targetPath = $imageDir . $newFileName;
                
                if (convertToWebP($tmpName, $targetPath)) {
                    $uploadedFiles[] = '/kaishop_v2/assets/images/products/' . $newFileName;
                } else {
                    $errors[] = "Không thể chuyển đổi file: " . $files['name'][$i];
                }
            } else {
                 $errors[] = "Lỗi upload file: " . $files['name'][$i];
            }
        }
        
        if (!empty($uploadedFiles)) {
            echo json_encode(['success' => true, 'message' => 'Upload thành công ' . count($uploadedFiles) . ' ảnh.', 'errors' => $errors]);
        } else {
            echo json_encode(['error' => 'Không có file nào được upload thành công.', 'details' => $errors]);
        }
    } else {
        echo json_encode(['error' => 'Không có file được gửi lên']);
    }
    exit;
}

if ($action == 'delete') {
    if (isset($_POST['files']) && is_array($_POST['files'])) {
        $deletedCount = 0;
        foreach ($_POST['files'] as $fileUrl) {
            $fileName = basename($fileUrl);
            $filePath = $imageDir . $fileName;
            
            // Security check to prevent directory traversal
            if (file_exists($filePath) && is_file($filePath)) {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'Đã xóa ' . $deletedCount . ' ảnh.']);
    } else {
        echo json_encode(['error' => 'Danh sách file không hợp lệ']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);

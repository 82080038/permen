<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['photo'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG and PNG allowed.']);
    exit;
}

// Validate file size (max 2MB)
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 2MB.']);
    exit;
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../uploads/profile_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Load and resize image
try {
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new Exception('Invalid image file');
    }

    $sourceImage = null;
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        default:
            throw new Exception('Unsupported image type');
    }

    if (!$sourceImage) {
        throw new Exception('Failed to create image from file');
    }

    // Get original dimensions
    $width = imagesx($sourceImage);
    $height = imagesy($sourceImage);

    // Calculate new dimensions (max 300x300)
    $maxSize = 300;
    if ($width > $height) {
        if ($width > $maxSize) {
            $newWidth = $maxSize;
            $newHeight = (int)($height * ($maxSize / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
    } else {
        if ($height > $maxSize) {
            $newHeight = $maxSize;
            $newWidth = (int)($width * ($maxSize / $height));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
    }

    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG
    if ($imageInfo[2] == IMAGETYPE_PNG) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save image
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $filepath, 8);
            break;
    }

    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);

    // Delete old photo if exists
    $stmt = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldPhoto = $stmt->fetchColumn();
    
    if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
        unlink($uploadDir . $oldPhoto);
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
    $stmt->execute([$filename, $userId]);

    echo json_encode([
        'success' => true,
        'data' => [
            'filename' => $filename,
            'url' => '/permen/uploads/profile_photos/' . $filename
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to process image: ' . $e->getMessage()]);
}

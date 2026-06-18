<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    ApiResponse::forbidden('Unauthorized');
}

$action = $_POST['action'] ?? '';

if ($action === 'upload_media') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        ApiResponse::validationError(['file' => 'No file uploaded or upload error'], 'No file uploaded or upload error');
    }
    
    $file = $_FILES['file'];
    $folder = sanitizeInput($_POST['folder'] ?? 'general');
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        ApiResponse::validationError(['file_type' => 'Invalid file type'], 'Invalid file type');
    }
    
    // Validate file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        ApiResponse::validationError(['file_size' => 'File too large (max 10MB)'], 'File too large (max 10MB)');
    }
    
    // Determine file type category
    $fileType = 'document';
    if (strpos($file['type'], 'image') === 0) $fileType = 'image';
    elseif (strpos($file['type'], 'video') === 0) $fileType = 'video';
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $uploadDir = '../uploads/media/' . $folder . '/';
    
    // Create directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $filename;
    $fileUrl = '/uploads/media/' . $folder . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        ApiResponse::serverError('Failed to move uploaded file');
    }
    
    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO media_library (filename, original_name, file_path, file_url, file_type, file_size, mime_type, folder, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $filename,
        $file['name'],
        $filePath,
        $fileUrl,
        $fileType,
        $file['size'],
        $file['type'],
        $folder,
        $_SESSION['user_id']
    ]);
    
    ApiResponse::success(['url' => $fileUrl], 'File uploaded successfully');
    
} elseif ($action === 'delete_media') {
    $mediaId = (int)($_POST['media_id'] ?? 0);
    
    if (!$mediaId) {
        ApiResponse::validationError(['media_id' => 'Invalid media ID'], 'Invalid media ID');
    }
    
    // Get file info
    $stmt = $pdo->prepare("SELECT file_path FROM media_library WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch();
    
    if (!$media) {
        ApiResponse::notFound('Media not found');
    }
    
    // Delete physical file
    if (file_exists($media['file_path'])) {
        unlink($media['file_path']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
    $stmt->execute([$mediaId]);
    
    ApiResponse::success([], 'Media deleted successfully');
    
} elseif ($_GET['action'] === 'get_media_list') {
    $fileType = $_GET['file_type'] ?? '';
    $folder = $_GET['folder'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($fileType) {
        $whereClause .= " AND file_type = ?";
        $params[] = $fileType;
    }
    if ($folder) {
        $whereClause .= " AND folder = ?";
        $params[] = $folder;
    }
    if ($search) {
        $whereClause .= " AND (original_name LIKE ? OR filename LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $stmt = $pdo->prepare("SELECT * FROM media_library $whereClause ORDER BY created_at DESC LIMIT 100");
    $stmt->execute($params);
    $mediaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['media' => $mediaList], 'Media list retrieved');
    
} elseif ($_GET['action'] === 'get_folders') {
    $stmt = $pdo->query("SELECT DISTINCT folder FROM media_library ORDER BY folder");
    $folders = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    ApiResponse::success(['folders' => $folders], 'Folders retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}

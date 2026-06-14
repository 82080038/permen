<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'upload_media') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['file'];
    $folder = sanitizeInput($_POST['folder'] ?? 'general');
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    
    // Validate file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['error' => 'File too large (max 10MB)']);
        exit;
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
        echo json_encode(['error' => 'Failed to move uploaded file']);
        exit;
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
    
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully', 'url' => $fileUrl]);
    
} elseif ($action === 'delete_media') {
    $mediaId = (int)($_POST['media_id'] ?? 0);
    
    if (!$mediaId) {
        echo json_encode(['error' => 'Invalid media ID']);
        exit;
    }
    
    // Get file info
    $stmt = $pdo->prepare("SELECT file_path FROM media_library WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch();
    
    if (!$media) {
        echo json_encode(['error' => 'Media not found']);
        exit;
    }
    
    // Delete physical file
    if (file_exists($media['file_path'])) {
        unlink($media['file_path']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
    $stmt->execute([$mediaId]);
    
    echo json_encode(['success' => true, 'message' => 'Media deleted successfully']);
    
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
    
    echo json_encode(['success' => true, 'media' => $mediaList]);
    
} elseif ($_GET['action'] === 'get_folders') {
    $stmt = $pdo->query("SELECT DISTINCT folder FROM media_library ORDER BY folder");
    $folders = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'folders' => $folders]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}

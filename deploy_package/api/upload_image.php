<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $uploadDir = __DIR__ . '/../assets/soal/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (empty($_FILES['gambar'])) {
        echo json_encode(['error' => 'Tidak ada file yang diupload']);
        exit;
    }

    $file = $_FILES['gambar'];

    // Use enhanced file validation
    require '../helpers.php';
    $validation = validateUploadedFile($file);
    if (!$validation['valid']) {
        echo json_encode(['error' => $validation['error']]);
        exit;
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if ($ext === 'jpeg') $ext = 'jpg';
    $filename = 'soal_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    // Optimize image before saving
    if (!optimizeImage($file['tmp_name'], $targetPath, 1200, 85)) {
        // If optimization fails, try direct upload
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(['error' => 'Gagal menyimpan file']);
            exit;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}

$relativePath = 'assets/soal/' . $filename;

echo json_encode([
    'success' => true,
    'url' => $relativePath,
    'filename' => $filename,
    'mime' => $mime,
    'size' => $file['size']
]);

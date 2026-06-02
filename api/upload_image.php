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

$uploadDir = __DIR__ . '/../assets/soal/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_FILES['gambar'])) {
    echo json_encode(['error' => 'Tidak ada file yang diupload']);
    exit;
}

$file = $_FILES['gambar'];

// Validasi error upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error: ' . $file['error']]);
    exit;
}

// Validasi tipe file
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['error' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, SVG, atau WEBP.']);
    exit;
}

// Validasi ukuran (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['error' => 'Ukuran file maksimal 2MB']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$ext = strtolower($ext);
if ($ext === 'jpeg') $ext = 'jpg';
$filename = 'soal_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['error' => 'Gagal menyimpan file']);
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

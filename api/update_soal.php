<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

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

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

// Fields
$pertanyaan = $_POST['pertanyaan'] ?? '';
$pilihan_a = $_POST['pilihan_a'] ?? '';
$pilihan_b = $_POST['pilihan_b'] ?? '';
$pilihan_c = $_POST['pilihan_c'] ?? '';
$pilihan_d = $_POST['pilihan_d'] ?? '';
$pilihan_e = $_POST['pilihan_e'] ?? '';
$jawaban_benar = strtoupper($_POST['jawaban_benar'] ?? '');
$pembahasan = $_POST['pembahasan'] ?? '';

if (!in_array($jawaban_benar, ['A','B','C','D','E'])) {
    echo json_encode(['error' => 'Jawaban benar tidak valid']);
    exit;
}

// Handle image upload
$imageUrl = null;
if (!empty($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../assets/soal/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['gambar']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => 'Tipe file tidak diizinkan']);
        exit;
    }
    if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['error' => 'Ukuran file maksimal 2MB']);
        exit;
    }

    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext === 'jpeg' ? 'jpg' : $ext);
    $filename = 'soal_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
        $imageUrl = 'assets/soal/' . $filename;
    }
}

// Build update SQL
$fields = [
    'pertanyaan' => $pertanyaan,
    'pilihan_a' => $pilihan_a,
    'pilihan_b' => $pilihan_b,
    'pilihan_c' => $pilihan_c,
    'pilihan_d' => $pilihan_d,
    'pilihan_e' => $pilihan_e,
    'jawaban_benar' => $jawaban_benar,
    'pembahasan' => $pembahasan,
];

if ($imageUrl !== null) {
    $fields['image_url'] = $imageUrl;
}

$sql = "UPDATE questions SET ";
$sets = [];
$params = [];
foreach ($fields as $col => $val) {
    $sets[] = "$col = ?";
    $params[] = $val;
}
$sql .= implode(', ', $sets);
$sql .= " WHERE id = ?";
$params[] = $id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['success' => true, 'image_url' => $imageUrl]);

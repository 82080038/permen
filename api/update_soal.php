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

try {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    // Fields
    require '../helpers.php';
    $pertanyaan = sanitizeInput($_POST['pertanyaan'] ?? '');
    $pilihan_a = sanitizeInput($_POST['pilihan_a'] ?? '');
    $pilihan_b = sanitizeInput($_POST['pilihan_b'] ?? '');
    $pilihan_c = sanitizeInput($_POST['pilihan_c'] ?? '');
    $pilihan_d = sanitizeInput($_POST['pilihan_d'] ?? '');
    $pilihan_e = sanitizeInput($_POST['pilihan_e'] ?? '');
    $jawaban_benar = strtoupper(sanitizeInput($_POST['jawaban_benar'] ?? ''));
    $pembahasan = sanitizeInput($_POST['pembahasan'] ?? '');

    if (!in_array($jawaban_benar, ['A','B','C','D','E'])) {
        echo json_encode(['error' => 'Jawaban benar tidak valid']);
        exit;
    }

    // Handle image upload
    $imageUrl = null;
    if (!empty($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/soal/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Use enhanced file validation
        $validation = validateUploadedFile($_FILES['gambar']);
        if (!$validation['valid']) {
            echo json_encode(['error' => $validation['error']]);
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

    // Log admin action
    logAdminAction($userId, 'UPDATE_QUESTION', 'question', $id, json_encode(['image_updated' => $imageUrl !== null]));

    echo json_encode(['success' => true, 'image_url' => $imageUrl]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server']);
    exit;
}

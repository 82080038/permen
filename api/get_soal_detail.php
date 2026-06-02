<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$id]);
$soal = $stmt->fetch();

if (!$soal) {
    echo json_encode(['error' => 'Soal tidak ditemukan']);
    exit;
}

echo json_encode(['success' => true, 'soal' => $soal]);

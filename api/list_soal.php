<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$q = $_GET['q'] ?? '';
$subtes = $_GET['subtes'] ?? '';
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
$offset = (int)($_GET['offset'] ?? 0);

$needsRevision = $_GET['needs_revision'] ?? '';
$isActive = $_GET['is_active'] ?? '';

$sql = "SELECT id, subtes, tipe, topik, pertanyaan, jawaban_benar, image_url, needs_revision, revision_status, is_active FROM questions WHERE 1=1";
$params = [];

if ($q) {
    $sql .= " AND (pertanyaan LIKE ? OR topik LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($subtes) {
    $sql .= " AND subtes = ?";
    $params[] = $subtes;
}
if ($needsRevision !== '') {
    $sql .= " AND needs_revision = ?";
    $params[] = (int)$needsRevision;
}
if ($isActive !== '') {
    $sql .= " AND is_active = ?";
    $params[] = (int)$isActive;
}

$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$soal = $stmt->fetchAll();

echo json_encode(['success' => true, 'soal' => $soal]);

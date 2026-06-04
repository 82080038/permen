<?php
require '../config.php';
require '../helpers.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// CSRF validation for API endpoints
if (!validateCsrfApi()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

$q = $_GET['q'] ?? '';
$subtes = $_GET['subtes'] ?? '';
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
$offset = (int)($_GET['offset'] ?? 0);

$needsRevision = $_GET['needs_revision'] ?? '';
$isActive = $_GET['is_active'] ?? '';

// Build WHERE clause
$whereClause = "WHERE 1=1";
$params = [];

if ($q) {
    $whereClause .= " AND (pertanyaan LIKE ? OR topik LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($subtes) {
    $whereClause .= " AND subtes = ?";
    $params[] = $subtes;
}
if ($needsRevision !== '') {
    $whereClause .= " AND needs_revision = ?";
    $params[] = (int)$needsRevision;
}
if ($isActive !== '') {
    $whereClause .= " AND is_active = ?";
    $params[] = (int)$isActive;
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM questions $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

// Get paginated data
$sql = "SELECT id, subtes, tipe, topik, pertanyaan, jawaban_benar, image_url, needs_revision, revision_status, is_active FROM questions $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$soal = $stmt->fetchAll();

// Calculate pagination metadata
$totalPages = ceil($total / $limit);
$currentPage = floor($offset / $limit) + 1;
$hasNext = $currentPage < $totalPages;
$hasPrev = $currentPage > 1;

echo json_encode(['success' => true, 'data' => [
    'soal' => $soal,
    'pagination' => [
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'has_next' => $hasNext,
        'has_prev' => $hasPrev
    ]
]]);

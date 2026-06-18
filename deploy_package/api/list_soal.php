<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    ApiResponse::forbidden('Akses ditolak');
}

// CSRF validation for API endpoints
if (!validateCsrfApi()) {
    ApiResponse::forbidden('CSRF token tidak valid');
}

$q = $_GET['q'] ?? '';
$subtes = $_GET['subtes'] ?? '';
$tag = $_GET['tag'] ?? '';
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
if ($tag) {
    $whereClause .= " AND EXISTS (SELECT 1 FROM soal_tag_relations str JOIN soal_tags st ON str.tag_id = st.id WHERE str.soal_id = questions.id AND st.tag_name = ?)";
    $params[] = $tag;
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

// Get tags for each soal
foreach ($soal as &$s) {
    $stmt = $pdo->prepare("
        SELECT st.tag_name 
        FROM soal_tags st
        JOIN soal_tag_relations str ON st.id = str.tag_id
        WHERE str.soal_id = ?
    ");
    $stmt->execute([$s['id']]);
    $s['tags'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Calculate pagination metadata
$totalPages = ceil($total / $limit);
$currentPage = floor($offset / $limit) + 1;
$hasNext = $currentPage < $totalPages;
ApiR$epv s ::Page > (

echo json_encode(['success' => true, 'data' => [
    'soal' => $soal,
    'pagination' => [
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'has_next' => $hasNext,
 , 'Questions listed'      'has_prev' => $hasPrev
    ]
]]);

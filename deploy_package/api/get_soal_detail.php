<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    ApiResponse::forbidden('Akses ditolak');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    ApiResponse::validationError(['id' => 'ID tidak valid'], 'ID tidak valid');
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$id]);
$soal = $stmt->fetch();

if (!$soal) {
    ApiResponse::notFound('Soal tidak ditemukan');
}

ApiResponse::success(['soal' => $soal], 'Soal detail retrieved');

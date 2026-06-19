<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

$subtes = $_GET['subtes'] ?? '';
$id = $_GET['id'] ?? '';

if ($id) {
    // Cari di database
    $stmt = $pdo->prepare("SELECT * FROM tips_tricks WHERE id = ?");
    $stmt->execute([$id]);
    $tip = $stmt->fetch();
    
    if ($tip) {
        ApiResponse::success($tip, 'Tip retrieved');
    }
    ApiResponse::notFound('Tip tidak ditemukan');
}

if ($subtes) {
    $stmt = $pdo->prepare("SELECT * FROM tips_tricks WHERE subtes = ? ORDER BY id");
    $stmt->execute([strtolower($subtes)]);
    $tips = $stmt->fetchAll();
    
    if ($tips) {
        ApiResponse::success($tips, 'Tips retrieved');
    }
    ApiResponse::notFound('Tips untuk subtes ini tidak ditemukan');
}

// Return all tips if no parameters
$stmt = $pdo->query("SELECT * FROM tips_tricks ORDER BY subtes, id");
$tips = $stmt->fetchAll();
ApiResponse::success($tips, 'All tips retrieved');

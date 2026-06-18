<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

$subtes = $_GET['subtes'] ?? '';
$id = $_GET['id'] ?? '';
$response = [];

if ($id) {
    // Cari di semua file
    foreach (['twk','tiu','tkp'] as $s) {
        $data = require "../content/materi_{$s}.php";
        foreach ($data as $item) {
            if ($item['id'] === $id) {
                ApiResponse::success($item, 'Materi retrieved');
            }
        }
    }
    ApiResponse::notFound('Materi tidak ditemukan');
}

if ($subtes) {
    $file = "../content/materi_" . strtolower($subtes) . ".php";
    if (file_exists($file)) {
        $data = require $file;
        ApiResponse::success(['subtes' => $subtes, 'materi' => $data], 'Materi retrieved');
    } else {
        ApiResponse::notFound('Subtes tidak ditemukan');
    }
}

// Jika tanpa parameter, kembalikan semua
$all = [];
foreach (['twk','tiu','tkp'] as $s) {
    $data = require "../content/materi_{$s}.php";
    $all[$s] = $data;
}
ApiResponse::success($all, 'All materi retrieved');

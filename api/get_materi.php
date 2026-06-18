<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

$subtes = $_GET['subtes'] ?? '';
$id = $_GET['id'] ?? '';

if ($id) {
    // Cari di semua file
    foreach (['twk','tiu','tkp'] as $s) {
        $file = "../content/materi_{$s}.php";
        if (file_exists($file)) {
            $data = require $file;
            foreach ($data as $item) {
                if ($item['id'] === $id) {
                    ApiResponse::success($item, 'Materi retrieved');
                }
            }
        }
    }
    ApiResponse::notFound('Materi tidak ditemukan');
}

if ($subtes) {
    $file = "../content/materi_" . strtolower($subtes) . ".php";
    if (file_exists($file)) {
        $data = require $file;
        ApiResponse::success($data, 'Materi retrieved');
    }
    ApiResponse::notFound('Subtes tidak ditemukan');
}

// Return all materi if no parameters
$allMateri = [];
foreach (['twk','tiu','tkp'] as $s) {
    $file = "../content/materi_{$s}.php";
    if (file_exists($file)) {
        $data = require $file;
        $allMateri = array_merge($allMateri, $data);
    }
}
ApiResponse::success($allMateri, 'All materi retrieved');

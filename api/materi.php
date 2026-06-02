<?php
require '../config.php';
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
                echo json_encode($item);
                exit;
            }
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'Materi tidak ditemukan']);
    exit;
}

if ($subtes) {
    $file = "../content/materi_" . strtolower($subtes) . ".php";
    if (file_exists($file)) {
        $data = require $file;
        echo json_encode(['subtes' => $subtes, 'materi' => $data]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Subtes tidak ditemukan']);
    }
    exit;
}

// Jika tanpa parameter, kembalikan semua
$all = [];
foreach (['twk','tiu','tkp'] as $s) {
    $data = require "../content/materi_{$s}.php";
    $all[$s] = $data;
}
echo json_encode($all);

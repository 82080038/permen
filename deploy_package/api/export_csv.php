<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: text/csv; charset=utf-8');

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    ApiResponse::forbidden('Akses ditolak. Hanya admin yang bisa export.');
}

$type = $_GET['type'] ?? 'tryouts';

if ($type === 'tryouts') {
    header('Content-Disposition: attachment; filename="riwayat_tryout_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    // BOM untuk Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Nama Tryout', 'Peserta', 'Nilai TWK', 'Nilai TIU', 'Nilai TKP', 'Total Nilai', 'Status', 'Waktu Mulai', 'Waktu Selesai']);

    $stmt = $pdo->query("SELECT ts.id, ts.nama, u.nama as peserta, ts.skor_twk, ts.skor_tiu, ts.skor_tkp, ts.skor_total, ts.status, ts.waktu_mulai, ts.waktu_selesai 
        FROM tryout_sessions ts LEFT JOIN users u ON ts.user_id = u.id ORDER BY ts.id DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['nama'],
            $row['peserta'] ?? 'Anonim',
            $row['skor_twk'],
            $row['skor_tiu'],
            $row['skor_tkp'],
            $row['skor_total'],
            $row['status'],
            $row['waktu_mulai'],
            $row['waktu_selesai'] ?? '-'
        ]);
    }
    fclose($output);
} elseif ($type === 'users') {
    header('Content-Disposition: attachment; filename="daftar_peserta_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Nama', 'Email', 'Instansi', 'Terdaftar']);

    $stmt = $pdo->query("SELECT id, nama, email, instansi, created_at FROM users WHERE role='user' ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [$row['id'], $row['nama'], $row['email'], $row['instansi'] ?? '-', $row['created_at']]);
    }
    fclose($output);
} else {
    ApiResponse::validationError(['type' => 'Tipe export tidak valid'], 'Tipe export tidak valid');
}

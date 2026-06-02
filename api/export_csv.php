<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: text/csv; charset=utf-8');

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Akses ditolak. Hanya admin yang bisa export.";
    exit;
}

$type = $_GET['type'] ?? 'tryouts';

if ($type === 'tryouts') {
    header('Content-Disposition: attachment; filename="riwayat_tryout_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    // BOM untuk Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Nama Tryout', 'Peserta', 'Nilai TWK', 'Nilai TIU', 'Nilai TKP', 'Total Nilai', 'Status', 'Waktu Mulai', 'Waktu Selesai']);

    $stmt = $pdo->query("SELECT ts.id, ts.nama, u.nama as peserta, ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp, ts.total_nilai, ts.status, ts.waktu_mulai, ts.waktu_selesai 
        FROM tryout_sessions ts LEFT JOIN users u ON ts.user_id = u.id ORDER BY ts.id DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['nama'],
            $row['peserta'] ?? 'Anonim',
            $row['nilai_twk'],
            $row['nilai_tiu'],
            $row['nilai_tkp'],
            $row['total_nilai'],
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
    http_response_code(400);
    echo "Tipe export tidak valid.";
}

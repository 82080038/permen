<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: text/csv; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$type = $_GET['type'] ?? 'users';
$filename = 'export_' . $type . '_' . date('Y-m-d') . '.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

switch ($type) {
    case 'users':
        fputcsv($output, ['ID', 'Nama', 'No HP', 'Email', 'Role', 'Status', 'Instansi', 'Created At']);
        $stmt = $pdo->query("
            SELECT u.id, u.nama, u.no_hp, u.email, u.role, u.status, i.nama_instansi, u.created_at
            FROM users u
            LEFT JOIN instansi i ON u.instansi_id = i.id
            ORDER BY u.id
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    case 'tryout_results':
        fputcsv($output, ['Session ID', 'User', 'No HP', 'Status', 'Nilai TWK', 'Nilai TIU', 'Nilai TKP', 'Total', 'Waktu Mulai', 'Waktu Selesai']);
        $stmt = $pdo->query("
            SELECT ts.id, u.nama, u.no_hp, ts.status, ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp, ts.total_nilai, ts.waktu_mulai, ts.waktu_selesai
            FROM tryout_sessions ts
            JOIN users u ON ts.user_id = u.id
            ORDER BY ts.id DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    case 'questions':
        fputcsv($output, ['ID', 'Subtes', 'Topik', 'Pertanyaan', 'Jawaban Benar', 'Needs Revision', 'Is Active']);
        $stmt = $pdo->query("SELECT id, subtes, topik, pertanyaan, jawaban_benar, needs_revision, is_active FROM questions ORDER BY id");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    default:
        fputcsv($output, ['Error: Unknown export type']);
        break;
}

fclose($output);

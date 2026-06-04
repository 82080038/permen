<?php
/**
 * API: Export Tryout Results to CSV
 * 
 * Exports tryout session results to CSV format
 * 
 * @param int $_GET['session_id'] The session ID to export
 * @param string $_GET['format'] - Export format (csv only for now)
 * @return CSV file download
 */
require '../config.php';
require '../helpers.php';

$sessionId = (int)($_GET['session_id'] ?? 0);
$format = $_GET['format'] ?? 'csv';

if (!$sessionId) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Session ID diperlukan';
    exit;
}

// Get session data
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    header('HTTP/1.0 404 Not Found');
    echo 'Sesi tidak ditemukan';
    exit;
}

// Get subtes data
$stmt = $pdo->prepare("SELECT subtes, nilai, passing_grade, jumlah_soal FROM session_subtes WHERE session_id = ?");
$stmt->execute([$sessionId]);
$subData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (empty($subData)) {
    // Fallback to flat columns
    $subData = [
        'TKP' => ['nilai'=>$session['nilai_tkp'],'passing_grade'=>$session['passing_tkp'],'jumlah_soal'=>$session['jumlah_tkp']],
        'TIU' => ['nilai'=>$session['nilai_tiu'],'passing_grade'=>$session['passing_tiu'],'jumlah_soal'=>$session['jumlah_tiu']],
        'TWK' => ['nilai'=>$session['nilai_twk'],'passing_grade'=>$session['passing_twk'],'jumlah_soal'=>$session['jumlah_twk']],
    ];
}

$nilaiTkp = $subData['TKP']['nilai'] ?? 0;
$nilaiTiu = $subData['TIU']['nilai'] ?? 0;
$nilaiTwk = $subData['TWK']['nilai'] ?? 0;
$totalNilai = $session['total_nilai'] ?? ($nilaiTkp + $nilaiTiu + $nilaiTwk);

$passingTkp = $subData['TKP']['passing_grade'] ?? 126;
$passingTiu = $subData['TIU']['passing_grade'] ?? 80;
$passingTwk = $subData['TWK']['passing_grade'] ?? 65;
$passingTotal = $session['passing_total'] ?? 271;

$statusTkp = $nilaiTkp >= $passingTkp ? 'LULUS' : 'TIDAK LULUS';
$statusTiu = $nilaiTiu >= $passingTiu ? 'LULUS' : 'TIDAK LULUS';
$statusTwk = $nilaiTwk >= $passingTwk ? 'LULUS' : 'TIDAK LULUS';
$statusTotal = $totalNilai >= $passingTotal ? 'LULUS' : 'TIDAK LULUS';

// Generate CSV
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hasil_tryout_' . $sessionId . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['Hasil Tryout SKD CAT-BKN'], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['Session ID', $session['id']], ';');
    fputcsv($output, ['Nama Tryout', $session['nama']], ';');
    fputcsv($output, ['Waktu Mulai', date('d/m/Y H:i:s', strtotime($session['waktu_mulai']))], ';');
    fputcsv($output, ['Waktu Selesai', date('d/m/Y H:i:s', strtotime($session['waktu_selesai']))], ';');
    fputcsv($output, [], ';');
    
    // Total Score
    fputcsv($output, ['NILAI TOTAL SKD'], ';');
    fputcsv($output, ['Nilai Total', $totalNilai], ';');
    fputcsv($output, ['Ambang Batas', $passingTotal], ';');
    fputcsv($output, ['Status', $statusTotal], ';');
    fputcsv($output, [], ';');
    
    // Subtes Scores
    fputcsv($output, ['RINCIAN NILAI PER SUBTES'], ';');
    fputcsv($output, ['Subtes', 'Nilai', 'Ambang Batas', 'Status'], ';');
    fputcsv($output, ['TKP', $nilaiTkp, $passingTkp, $statusTkp], ';');
    fputcsv($output, ['TIU', $nilaiTiu, $passingTiu, $statusTiu], ';');
    fputcsv($output, ['TWK', $nilaiTwk, $passingTwk, $statusTwk], ';');
    fputcsv($output, [], ';');
    
    // Instansi Eligibility
    fputcsv($output, ['KELAYAKAN INSTANSI'], ';');
    fputcsv($output, ['Instansi', 'Passing TKP', 'Passing TIU', 'Passing TWK', 'Passing Total', 'Status'], ';');
    
    $instansiList = $pdo->query("SELECT * FROM instansi WHERE aktif = 1 ORDER BY urutan")->fetchAll();
    foreach ($instansiList as $ins) {
        $lulusTkp = $nilaiTkp >= $ins['passing_tkp'];
        $lulusTiu = $nilaiTiu >= $ins['passing_tiu'];
        $lulusTwk = $nilaiTwk >= $ins['passing_twk'];
        $lulusTotal = $totalNilai >= $ins['passing_total'];
        $status = ($lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal) ? 'LAYAK' : 'TIDAK LAYAK';
        
        fputcsv($output, [
            $ins['nama'],
            $ins['passing_tkp'],
            $ins['passing_tiu'],
            $ins['passing_twk'],
            $ins['passing_total'],
            $status
        ], ';');
    }
    
    fclose($output);
    exit;
}

header('HTTP/1.0 400 Bad Request');
echo 'Format tidak didukung';

<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

$sessionId = (int)($_GET['session_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID diperlukan']);
    exit;
}
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

// Ambil session + validasi kepemilikan
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak ditemukan atau bukan milik Anda']);
    exit;
}

// Cek apakah session masih berjalan
if ($session['status'] !== 'berjalan') {
    http_response_code(403);
    echo json_encode(['error' => 'Session sudah selesai atau tidak aktif']);
    exit;
}

// Ambil konfigurasi subtes dari tabel normalisasi
$stmt = $pdo->prepare("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, nilai FROM session_subtes WHERE session_id = ? ORDER BY urutan");
$stmt->execute([$sessionId]);
$subtesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$subtesConfig = [];
foreach ($subtesRows as $row) {
    $subtesConfig[$row['subtes']] = [
        'durasi_menit' => (int)$row['durasi_menit'],
        'jumlah_soal'  => (int)$row['jumlah_soal'],
        'passing_grade'=> (int)$row['passing_grade'],
        'nilai'        => (int)$row['nilai'],
    ];
}
// Jika belum ada (session lama sebelum normalisasi), fallback ke kolom flat
if (empty($subtesConfig)) {
    $subtesConfig = [
        'TWK' => ['durasi_menit'=>$session['durasi_twk'],'jumlah_soal'=>$session['jumlah_twk'],'passing_grade'=>$session['passing_twk'],'nilai'=>$session['nilai_twk']],
        'TIU' => ['durasi_menit'=>$session['durasi_tiu'],'jumlah_soal'=>$session['jumlah_tiu'],'passing_grade'=>$session['passing_tiu'],'nilai'=>$session['nilai_tiu']],
        'TKP' => ['durasi_menit'=>$session['durasi_tkp'],'jumlah_soal'=>$session['jumlah_tkp'],'passing_grade'=>$session['passing_tkp'],'nilai'=>$session['nilai_tkp']],
    ];
}

// Cek apakah soal sudah di-generate
$stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE session_id = ?");
$stmt->execute([$sessionId]);
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Generate soal acak dari session_subtes (shuffle di PHP, bukan ORDER BY RAND())
    $insert = $pdo->prepare("INSERT INTO answers (session_id, question_id) VALUES (?, ?)");
    foreach (['TWK','TIU','TKP'] as $sub) {
        $jumlah = isset($subtesConfig[$sub]) ? (int)$subtesConfig[$sub]['jumlah_soal'] : 30;
        if ($jumlah > 0) {
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE subtes = ? AND is_active = 1");
            $stmt->execute([$sub]);
            $soalIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            shuffle($soalIds);
            $pilih = array_slice($soalIds, 0, $jumlah);
            foreach ($pilih as $qid) {
                $insert->execute([$sessionId, $qid]);
            }
        }
    }
    // Set waktu mulai subtes pertama (urutan terkecil)
    $pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND urutan = (SELECT MIN(urutan) FROM session_subtes WHERE session_id = ?)")
        ->execute([$sessionId, $sessionId]);
}

// Ambil soal dengan jawaban user + passage (bacaan)
$stmt = $pdo->prepare("SELECT a.id as answer_id, a.jawaban_user, q.*, p.id as passage_id_real, p.judul as passage_judul, p.bacaan as passage_bacaan FROM answers a JOIN questions q ON a.question_id = q.id LEFT JOIN passages p ON q.passage_id = p.id WHERE a.session_id = ? AND q.is_active = 1 ORDER BY FIELD(q.subtes,'TKP','TIU','TWK'), q.passage_id, q.passage_order, a.id");
$stmt->execute([$sessionId]);
$soal = $stmt->fetchAll();

// Build passage index untuk frontend
$passages = [];
foreach ($soal as $s) {
    if ($s['passage_id_real'] && !isset($passages[$s['passage_id_real']])) {
        $passages[$s['passage_id_real']] = [
            'judul' => $s['passage_judul'],
            'bacaan' => $s['passage_bacaan']
        ];
    }
}

echo json_encode(['session'=>$session, 'soal'=>$soal, 'passages'=>$passages]);

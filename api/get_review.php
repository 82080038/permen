<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

$sessionId = (int)($_GET['session_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$sessionId || !$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

// Validasi kepemilikan
$stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Session tidak ditemukan']);
    exit;
}

// Ambil soal dengan jawaban user + passage
$stmt = $pdo->prepare("
    SELECT 
        a.id as answer_id, a.jawaban, a.skor,
        q.id as question_id, q.subtes, q.tipe, q.topik, q.pertanyaan,
        q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, q.pilihan_e,
        q.jawaban_benar, q.pembahasan, q.tips_trick, q.image_url, q.related_links,
        q.materi_id, q.passage_id, q.passage_order,
        p.judul as passage_judul, p.bacaan as passage_bacaan,
        m.judul as materi_judul, m.url as materi_url
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    LEFT JOIN passages p ON q.passage_id = p.id
    LEFT JOIN materi m ON q.materi_id = m.id
    WHERE a.session_id = ?
    ORDER BY FIELD(q.subtes,'TWK','TIU','TKP'), q.passage_id, q.passage_order, a.id
");
$stmt->execute([$sessionId]);
$soal = $stmt->fetchAll();

// Build passages
$passages = [];
foreach ($soal as $s) {
    if ($s['passage_id'] && !isset($passages[$s['passage_id']])) {
        $passages[$s['passage_id']] = [
            'judul' => $s['passage_judul'],
            'bacaan' => $s['passage_bacaan']
        ];
    }
}

// Stats per subtes
$stats = ['TWK'=>['benar'=>0,'salah'=>0,'kosong'=>0,'total'=>0,'skor'=>0],
          'TIU'=>['benar'=>0,'salah'=>0,'kosong'=>0,'total'=>0,'skor'=>0],
          'TKP'=>['benar'=>0,'salah'=>0,'kosong'=>0,'total'=>0,'skor'=>0]];

foreach ($soal as $s) {
    $sub = $s['subtes'];
    $stats[$sub]['total']++;
    $stats[$sub]['skor'] += (int)($s['skor'] ?? 0);
    if (empty($s['jawaban'])) {
        $stats[$sub]['kosong']++;
    } elseif ($s['jawaban'] === $s['jawaban_benar']) {
        $stats[$sub]['benar']++;
    } else {
        $stats[$sub]['salah']++;
    }
}

echo json_encode(['success' => true, 'data' => [
    'soal' => $soal,
    'passages' => $passages,
    'stats' => $stats
]]);

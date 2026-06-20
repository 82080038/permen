<?php
/**
 * API: Get weakness analysis per topic + auto materi recommendations
 * Returns topics with lowest accuracy and recommended materi links
 * 
 * @param int $_GET['session_id'] - Tryout session ID
 */
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Autentikasi diperlukan']);
    exit;
}

$sessionId = (int)($_GET['session_id'] ?? 0);
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID diperlukan']);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ? AND status = 'selesai'");
$stmt->execute([$sessionId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Sesi tidak ditemukan atau belum selesai']);
    exit;
}

// Get per-topic accuracy
$topicStmt = $pdo->prepare("SELECT q.subtes, q.topik,
    COUNT(*) as total,
    SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar,
    SUM(CASE WHEN a.jawaban_user IS NOT NULL AND a.jawaban_user != q.jawaban_benar THEN 1 ELSE 0 END) as salah,
    SUM(CASE WHEN a.jawaban_user IS NULL OR a.jawaban_user = '' THEN 1 ELSE 0 END) as kosong
    FROM answers a 
    JOIN questions q ON a.question_id = q.id 
    WHERE a.session_id = ? 
    GROUP BY q.subtes, q.topik
    ORDER BY (SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) / COUNT(*)) ASC");
$topicStmt->execute([$sessionId]);
$topics = $topicStmt->fetchAll();

// Find recommended materi for weak topics
$weakTopics = [];
foreach ($topics as $t) {
    $acc = $t['total'] > 0 ? round(($t['benar'] / $t['total']) * 100) : 0;
    $isWeak = $acc < 70;
    
    // Find materi for this topic
    $materiStmt = $pdo->prepare("SELECT id, judul, subtes FROM materi WHERE subtes = ? AND (judul LIKE ? OR judul LIKE ?) LIMIT 3");
    $searchTerm = '%' . $t['topik'] . '%';
    $materiStmt->execute([$t['subtes'], $searchTerm, '%' . strtolower($t['topik']) . '%']);
    $materi = $materiStmt->fetchAll();
    
    // Also find tips for this topic
    $tipsStmt = $pdo->prepare("SELECT id, tips, topik FROM tips_tricks WHERE subtes = ? AND (topik LIKE ? OR topik LIKE ?) LIMIT 2");
    $tipsStmt->execute([$t['subtes'], $searchTerm, '%' . strtolower($t['topik']) . '%']);
    $tips = $tipsStmt->fetchAll();
    
    $weakTopics[] = [
        'subtes' => $t['subtes'],
        'topik' => $t['topik'],
        'total' => (int)$t['total'],
        'benar' => (int)$t['benar'],
        'salah' => (int)$t['salah'],
        'kosong' => (int)$t['kosong'],
        'akurasi' => $acc,
        'is_weak' => $isWeak,
        'materi' => array_map(function($m) use ($baseUrl) {
            return ['id' => $m['id'], 'judul' => $m['judul'], 'url' => ($baseUrl ?? '/permen') . '/pages/materi.php?subtes=' . $m['subtes'] . '#materi_' . $m['id']];
        }, $materi),
        'tips' => array_map(function($tp) {
            return ['id' => $tp['id'], 'trik' => $tp['tips'], 'topik' => $tp['topik']];
        }, $tips),
    ];
}

// Overall summary
$weakCount = count(array_filter($weakTopics, fn($t) => $t['is_weak']));
$strongCount = count($weakTopics) - $weakCount;

echo json_encode([
    'success' => true,
    'data' => [
        'topics' => $weakTopics,
        'summary' => [
            'total_topics' => count($weakTopics),
            'weak_topics' => $weakCount,
            'strong_topics' => $strongCount,
        ],
    ],
]);

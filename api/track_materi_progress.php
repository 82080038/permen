<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $materiId = sanitizeInput($input['materi_id'] ?? '');
    $subtes = strtoupper(sanitizeInput($input['subtes'] ?? ''));
    $progress = (int)($input['progress_percent'] ?? 0);

    if (!$materiId || !in_array($subtes, ['TWK', 'TIU', 'TKP'])) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
        exit;
    }

    $progress = max(0, min(100, $progress));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO materi_progress (user_id, materi_id, subtes, progress_percent)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE progress_percent = VALUES(progress_percent), last_read_at = NOW()
        ");
        $stmt->execute([$userId, $materiId, $subtes, $progress]);

        echo json_encode(['success' => true, 'message' => 'Progress disimpan']);
    } catch (PDOException $e) {
        error_log("Materi progress error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan progress']);
    }
} elseif ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT materi_id, subtes, progress_percent FROM materi_progress WHERE user_id = ?");
        $stmt->execute([$userId]);
        $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalMateri = count($progress);
        $completed = count(array_filter($progress, fn($p) => $p['progress_percent'] >= 100));
        $avgProgress = $totalMateri > 0 ? round(array_sum(array_column($progress, 'progress_percent')) / $totalMateri) : 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'items' => $progress,
                'total_materi' => $totalMateri,
                'completed' => $completed,
                'avg_progress' => $avgProgress,
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil progress']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}

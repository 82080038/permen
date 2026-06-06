<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

$subtes = $_GET['subtes'] ?? 'TWK';
$topik = $_GET['topik'] ?? '';
$jumlah = (int)($_GET['jumlah'] ?? 3);

$validSubtes = ['TWK', 'TIU', 'TKP'];
if (!in_array($subtes, $validSubtes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid subtes']);
    exit;
}

if (!$topik) {
    echo json_encode(['success' => false, 'error' => 'Topic required']);
    exit;
}

if ($jumlah < 1 || $jumlah > 5) {
    $jumlah = 3; // Default to 3 questions
}

try {
    // Get questions for the topic
    $stmt = $pdo->prepare("
        SELECT id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, 
               jawaban_benar, pembahasan, tips_trick, related_links
        FROM questions
        WHERE subtes = ? AND topik = ? AND is_active = 1
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$subtes, $topik, $jumlah]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        echo json_encode(['success' => false, 'error' => 'No questions found for this topic']);
        exit;
    }

    // Parse related_links if it's JSON
    foreach ($questions as &$q) {
        if (!empty($q['related_links'])) {
            $links = json_decode($q['related_links'], true);
            $q['related_links'] = is_array($links) ? $links : [];
        } else {
            $q['related_links'] = [];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'subtes' => $subtes,
            'topik' => $topik,
            'jumlah' => count($questions),
            'soal' => $questions
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch questions']);
}

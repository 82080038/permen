<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

$subtes = $_GET['subtes'] ?? 'TWK';
$topik = $_GET['topik'] ?? '';
$jumlah = (int)($_GET['jumlah'] ?? 3);

$validSubtes = ['TWK', 'TIU', 'TKP'];
if (!in_array($subtes, $validSubtes)) {
    ApiResponse::validationError(['subtes' => 'Invalid subtes'], 'Invalid subtes');
}

if (!$topik) {
    ApiResponse::validationError(['topik' => 'Topic required'], 'Topic required');
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
        ApiResponse::notFound('No questions found for this topic');
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

    ApiResponse::success([
        'subtes' => $subtes,
        'topik' => $topik,
        'jumlah' => count($questions),
        'soal' => $questions
    ], 'Questions fetched');
} catch (Exception $e) {
    ApiResponse::serverError('Failed to fetch questions');
}

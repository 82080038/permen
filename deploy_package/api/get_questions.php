<?php
/**
 * API: Get Questions for Practice/Quiz
 * 
 * Returns questions for practice sessions without requiring full tryout session
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffer
ob_start();

// Load environment without config.php error handlers
require '../env_loader.php';

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    ob_end_clean();
    require_once '../src/Http/ApiResponse.php';
    \App\Http\ApiResponse::serverError('Database connection failed');
}

// Session configuration
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

$secureCookie = (($_ENV['APP_ENV'] ?? 'development') === 'production') && 
                 (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Load helpers
require '../helpers.php';

// Load ApiResponse
require_once '../src/Http/ApiResponse.php';

try {
    // Authentication check
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        \App\Http\ApiResponse::unauthorized('Autentikasi diperlukan');
    }

    // Get parameters
    $subtes = $_GET['subtes'] ?? 'TIU';
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $difficulty = $_GET['difficulty'] ?? 'sedang';
    $topic = $_GET['topic'] ?? '';

    // Validate subtes
    $validSubtes = ['TWK', 'TIU', 'TKP'];
    if (!in_array($subtes, $validSubtes)) {
        \App\Http\ApiResponse::validationError(['subtes' => 'Subtes tidak valid'], 'Subtes tidak valid');
    }

    // Build query - use working structure from generate_user_soal.php
    $sql = "SELECT q.id, q.pertanyaan, q.pembahasan, q.subtes, q.topik, 
                   qo.pilihan_a, qo.pilihan_b, qo.pilihan_c, qo.pilihan_d, qo.pilihan_e, qo.jawaban_benar
            FROM questions q 
            LEFT JOIN question_options qo ON q.id = qo.question_id 
            WHERE q.subtes = ?";
    
    $params = [$subtes];
    
    // Remove problematic filters for now
    // if ($topic) {
    //     $sql .= " AND q.topik LIKE ?";
    //     $params[] = "%$topic%";
    // }
    
    // if ($difficulty !== 'semua') {
    //     $sql .= " AND q.kesulitan = ?";
    //     $params[] = $difficulty;
    // }
    
    $sql .= " ORDER BY RAND() LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $questions = [];
    while ($row = $stmt->fetch()) {
        $questionId = $row['id'];
        
        if (!isset($questions[$questionId])) {
            $questions[$questionId] = [
                'id' => $row['id'],
                'pertanyaan' => $row['pertanyaan'],
                'pembahasan' => $row['pembahasan'],
                'subtes' => $row['subtes'],
                'topik' => $row['topik'],
                'kesulitan' => 'sedang', // Default value
                'tipe' => 'pilihan_ganda', // Default value
                'options' => [
                    'A' => $row['pilihan_a'],
                    'B' => $row['pilihan_b'],
                    'C' => $row['pilihan_c'],
                    'D' => $row['pilihan_d'],
                    'E' => $row['pilihan_e']
                ],
                'correct_answer' => $row['jawaban_benar']
            ];
        }
    }
    
    // Convert to indexed array and fix field mapping
    $questions = array_values($questions);
    
    // Fix field names to match expected format
    foreach ($questions as &$question) {
        if (isset($question['soal'])) {
            $question['pertanyaan'] = $question['soal'];
            unset($question['soal']);
        }
        if (!isset($question['kesulitan'])) {
            $question['kesulitan'] = 'sedang';
        }
        if (!isset($question['tipe'])) {
            $question['tipe'] = 'pilihan_ganda';
        }
    }
    
    \App\Http\ApiResponse::success([
        'subtes' => $subtes,
        'count' => count($questions),
        'questions' => $questions
    ], 'Questions retrieved');
    
} catch (Exception $e) {
    \App\Http\ApiResponse::serverError('Server error: ' . $e->getMessage());
}
?>

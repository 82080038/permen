<?php
/**
 * API: Get Questions for Practice/Quiz - Fixed Version
 * Based on working generate_user_soal.php structure
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
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

header('Content-Type: application/json; charset=utf-8');

try {
    // Authentication check
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }

    // Get parameters
    $subtes = $_GET['subtes'] ?? 'TIU';
    $limit = min((int)($_GET['limit'] ?? 10), 50);

    // Validate subtes
    $validSubtes = ['TWK', 'TIU', 'TKP'];
    if (!in_array($subtes, $validSubtes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Subtes tidak valid']);
        exit;
    }

    // Use simple query that works
    $sql = "SELECT q.id, q.pertanyaan, q.pembahasan, q.subtes, 
                   qo.pilihan_a, qo.pilihan_b, qo.pilihan_c, qo.pilihan_d, qo.pilihan_e, qo.jawaban_benar
            FROM questions q 
            LEFT JOIN question_options qo ON q.id = qo.question_id 
            WHERE q.subtes = ?
            ORDER BY RAND() LIMIT ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$subtes, $limit]);
    
    $questions = [];
    while ($row = $stmt->fetch()) {
        $questions[] = [
            'id' => $row['id'],
            'pertanyaan' => $row['pertanyaan'],
            'pembahasan' => $row['pembahasan'],
            'subtes' => $row['subtes'],
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'subtes' => $subtes,
            'count' => count($questions),
            'questions' => $questions
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>

<?php
/**
 * API: Get Questions for Practice/Quiz - Final Working Version
 * Based on actual database structure
 */

// Disable error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffer
ob_start();

// Load environment without config.php error handlers
require '../env_loader.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

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
    ApiResponse::serverError('Database connection failed');
}

// Session configuration - must match config.php exactly for session sharing
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);
$secureCookie = $isHttps && (($_ENV['APP_ENV'] ?? 'development') === 'production');

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
        ApiResponse::unauthorized('Autentikasi diperlukan');
    }

    // Get parameters
    $subtes = $_GET['subtes'] ?? 'TIU';
    $limit = min((int)($_GET['limit'] ?? 10), 50);
    $difficulty = $_GET['difficulty'] ?? 'semua';
    $topic = $_GET['topic'] ?? '';

    // Validate subtes
    $validSubtes = ['TWK', 'TIU', 'TKP'];
    if (!in_array($subtes, $validSubtes)) {
        ApiResponse::validationError(['subtes' => 'Subtes tidak valid'], 'Subtes tidak valid');
    }

    // Build query based on actual database structure
    $sql = "SELECT id, pertanyaan, pembahasan, subtes, topik, difficulty,
                   pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar,
                   tips_trick, related_links, materi_id
            FROM questions 
            WHERE subtes = ? AND is_active = 1";
    
    $params = [$subtes];
    
    if ($topic) {
        $sql .= " AND topik LIKE ?";
        $params[] = "%$topic%";
    }
    
    if ($difficulty !== 'semua') {
        $sql .= " AND difficulty = ?";
        $params[] = $difficulty;
    }
    
    $sql .= " ORDER BY RAND() LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $questions = [];
    while ($row = $stmt->fetch()) {
        // Parse related links if they exist
        $relatedLinks = [];
        if ($row['related_links']) {
            $relatedLinks = json_decode($row['related_links'], true) ?: [];
        }
        
        $questions[] = [
            'id' => $row['id'],
            'pertanyaan' => $row['pertanyaan'],
            'pembahasan' => $row['pembahasan'],
            'subtes' => $row['subtes'],
            'topik' => $row['topik'],
            'difficulty' => $row['difficulty'],
            'materi_id' => $row['materi_id'],
            'options' => [
                'A' => $row['pilihan_a'],
                'B' => $row['pilihan_b'],
                'C' => $row['pilihan_c'],
                'D' => $row['pilihan_d'],
                'E' => $row['pilihan_e']
            ],
            'correct_answer' => $row['jawaban_benar'],
            'tips_trick' => $row['tips_trick'],
            'related_links' => $relatedLinks
        ];
    }
    
    ApiResponse::success([
        'subtes' => $subtes,
        'count' => count($questions),
        'questions' => $questions
    ], 'Questions retrieved');
    
} catch (Exception $e) {
    ApiResponse::serverError('Server error: ' . $e->getMessage());
}
?>
<?php
/**
 * API: Create Tryout Session
 * Alternative endpoint for creating sessions with more options
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

// Load ApiResponse
require_once '../src/Http/ApiResponse.php';

try {
    // Authentication check
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        \App\Http\ApiResponse::unauthorized('Autentikasi diperlukan');
    }

    // CSRF validation - temporarily disabled for debugging
    // if (!validateCsrfApi()) {
    //     \App\Http\ApiResponse::forbidden('CSRF token tidak valid');
    // }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $sessionName = $data['session_name'] ?? 'Tryout SKD';
    $subtesConfig = $data['subtes_config'] ?? [
        ['subtes' => 'TWK', 'duration' => 30, 'questions' => 30],
        ['subtes' => 'TIU', 'duration' => 35, 'questions' => 35],
        ['subtes' => 'TKP', 'duration' => 45, 'questions' => 45]
    ];

    // Check for ongoing sessions
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id = ? AND status = 'berjalan'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        \App\Http\ApiResponse::error('Anda memiliki sesi yang sedang berjalan', 409);
    }

    // Create session
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status) VALUES (?, ?, NOW(), 'berjalan')");
        $stmt->execute([$userId, $sessionName]);
        $sessionId = $pdo->lastInsertId();
        
        // Insert subtes
        $insertSubtes = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?, ?, ?, ?, 70, ?)");
        
        foreach ($subtesConfig as $index => $subtes) {
            $insertSubtes->execute([
                $sessionId,
                $subtes['subtes'],
                $subtes['duration'],
                $subtes['questions'],
                $index + 1
            ]);
        }
        
        $pdo->commit();
        
        \App\Http\ApiResponse::success([
            'session_id' => $sessionId,
            'redirect' => "/pages/tryout.php?session_id=$sessionId"
        ], 'Session berhasil dibuat');
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    \App\Http\ApiResponse::serverError('Server error: ' . $e->getMessage());
}
?>

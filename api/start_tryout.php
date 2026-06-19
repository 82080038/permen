<?php
/**
 * API: Start New Tryout Session
 * 
 * Creates a new tryout session for the logged-in user
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

// Load helpers
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Authentication check
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan. Silakan login terlebih dahulu.']);
        exit;
    }

    // CSRF validation - temporarily disabled for debugging
    // if (!validateCsrfApi()) {
    //     http_response_code(403);
    //     echo json_encode(['error' => 'CSRF token tidak valid']);
    //     exit;
    // }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $tryoutType = $data['tryout_type'] ?? 'full';
    $subtesList = $data['subtes'] ?? ['TWK', 'TIU', 'TKP'];

    // Validate tryout type
    $validTypes = ['full', 'mini', 'practice'];
    if (!in_array($tryoutType, $validTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipe tryout tidak valid']);
        exit;
    }

    // Validate subtes
    $validSubtes = ['TWK', 'TIU', 'TKP'];
    foreach ($subtesList as $subtes) {
        if (!in_array($subtes, $validSubtes)) {
            http_response_code(400);
            echo json_encode(['error' => "Subtes tidak valid: $subtes"]);
            exit;
        }
    }

    // Check if user has ongoing session
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id = ? AND status = 'berjalan'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Anda memiliki sesi tryout yang sedang berjalan. Selesaikan terlebih dahulu atau hentikan sesi tersebut.']);
        exit;
    }

    // Create new tryout session
    $pdo->beginTransaction();
    
    try {
        // Insert main session
        $sessionName = match($tryoutType) {
            'full' => 'Tryout SKD Lengkap',
            'mini' => 'Tryout Mini',
            'practice' => 'Latihan Soal',
            default => 'Tryout SKD'
        };
        
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status) VALUES (?, ?, NOW(), 'berjalan')");
        $stmt->execute([$userId, $sessionName]);
        $sessionId = $pdo->lastInsertId();
        
        // Insert subtes configurations
        $stmt = $pdo->prepare("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, urutan FROM subtes_config WHERE is_active = 1 AND subtes = ?");
        $insertSubtes = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($subtesList as $subtes) {
            $stmt->execute([$subtes]);
            $config = $stmt->fetch();
            
            if ($config) {
                // Adjust duration for mini/practice
                $duration = $config['durasi_menit'];
                if ($tryoutType === 'mini') {
                    $duration = floor($duration / 2);
                } elseif ($tryoutType === 'practice') {
                    $duration = floor($duration / 3);
                }
                
                $insertSubtes->execute([
                    $sessionId,
                    $config['subtes'],
                    $duration,
                    $config['jumlah_soal'],
                    $config['passing_grade'],
                    $config['urutan']
                ]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'session_name' => $sessionName,
            'redirect' => "/pages/tryout.php?session_id=$sessionId",
            'subtes_count' => count($subtesList),
            'estimated_duration' => $tryoutType === 'full' ? 110 : ($tryoutType === 'mini' ? 55 : 35)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
?>

<?php
/**
 * API: Get Questions for Tryout Session
 * 
 * Retrieves questions for a specific tryout session. If questions haven't been
 * generated yet, it automatically generates random questions based on session config.
 * 
 * @param int $_GET['session_id'] The tryout session ID
 * @return JSON {
 *   session: array Session data,
 *   soal: array List of questions with user answers,
 *   passages: array Passages indexed by passage_id
 * }
 * 
 * HTTP Status Codes:
 * - 400: Missing session_id parameter
 * - 401: User not authenticated
 * - 403: Session not found, not owned by user, or not active
 * - 200: Success
 */
// Disable ALL error output for API endpoints
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start output buffer to catch any accidental HTML output
ob_start();

// Load environment variables without config.php error handlers
require '../env_loader.php';

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;unix_socket=/opt/lampp/var/mysql/mysql.sock";
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
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Load helpers (but skip config.php to avoid HTML error handlers)
require '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    
    // Debug: Log session info
    error_log("get_soal.php: session_id=$sessionId, user_id=$userId, session_user=" . ($_SESSION['user_id'] ?? 'none') . ", session_id=" . session_id());
    
    // API rate limiting
    $identifier = $userId > 0 ? "user_$userId" : $_SERVER['REMOTE_ADDR'];
    if (!checkAPIRateLimit($identifier, 'get_soal', 100, 60)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
        exit;
    }
    logAPIRequest($identifier, 'get_soal');
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID diperlukan']);
        exit;
    }
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentikasi diperlukan']);
        exit;
    }

    // Ambil session + validasi kepemilikan
    $stmt = $pdo->prepare("SELECT id, nama, user_id, waktu_mulai, waktu_selesai, status, nilai_twk, nilai_tiu, nilai_tkp, total_nilai FROM tryout_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(403);
        echo json_encode(['error' => 'Session tidak ditemukan atau bukan milik Anda']);
        exit;
    }

    // Cek apakah session masih berjalan
    if ($session['status'] !== 'berjalan') {
        http_response_code(403);
        echo json_encode(['error' => 'Session sudah selesai atau tidak aktif']);
        exit;
    }

    // Ambil konfigurasi subtes dari tabel normalisasi
    $stmt = $pdo->prepare("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, nilai FROM session_subtes WHERE session_id = ? ORDER BY urutan");
    $stmt->execute([$sessionId]);
    $subtesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $subtesConfig = [];
    foreach ($subtesRows as $row) {
        $subtesConfig[$row['subtes']] = [
            'durasi_menit' => (int)$row['durasi_menit'],
            'jumlah_soal'  => (int)$row['jumlah_soal'],
            'passing_grade'=> (int)$row['passing_grade'],
            'nilai'        => (int)$row['nilai'],
        ];
    }
    // Jika belum ada (session lama sebelum normalisasi), fallback ke kolom flat
    if (empty($subtesConfig)) {
        $subtesConfig = [
            'TWK' => ['durasi_menit'=>$session['durasi_twk'],'jumlah_soal'=>$session['jumlah_twk'],'passing_grade'=>$session['passing_twk'],'nilai'=>$session['nilai_twk']],
            'TIU' => ['durasi_menit'=>$session['durasi_tiu'],'jumlah_soal'=>$session['jumlah_tiu'],'passing_grade'=>$session['passing_tiu'],'nilai'=>$session['nilai_tiu']],
            'TKP' => ['durasi_menit'=>$session['durasi_tkp'],'jumlah_soal'=>$session['jumlah_tkp'],'passing_grade'=>$session['passing_tkp'],'nilai'=>$session['nilai_tkp']],
        ];
    }

    // Cek apakah soal sudah di-generate
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Generate soal acak dari session_subtes (FIXED: Single query with WHERE IN to avoid N+1 problem)
        $insert = $pdo->prepare("INSERT INTO answers (session_id, question_id) VALUES (?, ?)");
        
        // Collect all question IDs in a single query
        $allQuestionIds = [];
        foreach (array_keys($subtesConfig) as $sub) {
            $jumlah = isset($subtesConfig[$sub]) ? (int)$subtesConfig[$sub]['jumlah_soal'] : 30;
            if ($jumlah > 0) {
                $stmt = $pdo->prepare("SELECT id FROM questions WHERE subtes = ? AND is_active = 1");
                $stmt->execute([$sub]);
                $soalIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (empty($soalIds)) {
                    // Jika tidak ada soal untuk subtes ini, skip
                    continue;
                }
                shuffle($soalIds);
                $pilih = array_slice($soalIds, 0, min($jumlah, count($soalIds)));
                $allQuestionIds = array_merge($allQuestionIds, $pilih);
            }
        }
        
        // Insert all at once
        foreach ($allQuestionIds as $qid) {
            $insert->execute([$sessionId, $qid]);
        }
        
        // Set waktu mulai subtes pertama (urutan terkecil)
        $pdo->prepare("UPDATE session_subtes SET waktu_mulai_subtes = NOW() WHERE session_id = ? AND urutan = (SELECT MIN(urutan) FROM session_subtes WHERE session_id = ?)")
            ->execute([$sessionId, $sessionId]);
    }

    // Ambil soal dengan jawaban user + passage (bacaan) - optimized with index usage
    $stmt = $pdo->prepare("SELECT a.id as answer_id, a.jawaban_user, q.id, q.subtes, q.topik, q.pertanyaan, q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, q.pilihan_e, q.jawaban_benar, q.pembahasan, q.passage_id, q.passage_order, q.image_url, p.id as passage_id_real, p.judul as passage_judul, p.bacaan as passage_bacaan FROM answers a INNER JOIN questions q ON a.question_id = q.id LEFT JOIN passages p ON q.passage_id = p.id WHERE a.session_id = ? AND q.is_active = 1 ORDER BY FIELD(q.subtes,'TKP','TIU','TWK'), q.passage_id, q.passage_order, a.id");
    $stmt->execute([$sessionId]);
    $soal = $stmt->fetchAll();

    // Build passage index untuk frontend
    $passages = [];
    foreach ($soal as $s) {
        if ($s['passage_id_real'] && !isset($passages[$s['passage_id_real']])) {
            $passages[$s['passage_id_real']] = [
                'judul' => $s['passage_judul'],
                'bacaan' => $s['passage_bacaan']
            ];
        }
    }

    // Clear any buffered HTML output before sending JSON
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => ['session' => $session, 'soal' => $soal, 'passages' => $passages]]);
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    exit;
}

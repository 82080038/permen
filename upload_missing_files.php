<?php
/**
 * Upload Script for Missing Files
 * This script will create the missing API files directly on the server
 */

// Security check - only allow admin or specific execution
if (!isset($_GET['upload_key']) || $_GET['upload_key'] !== 'PERMEN_UPLOAD_2026') {
    die('Access denied. Invalid upload key.');
}

echo "<h2>SKD CAT-BKN - Upload Missing Files</h2>";
echo "<p>Starting upload process...</p>";

// Files to create with their content
$files_to_create = [
    'api/start_tryout.php' => '<?php
/**
 * API: Start New Tryout Session
 */

// Disable error display for clean JSON output
ini_set("display_errors", 0);
error_reporting(0);

// Start output buffer
ob_start();

// Load environment without config.php error handlers
require "../env_loader.php";

$host    = $_ENV["DB_HOST"]    ?? "localhost";
$db      = $_ENV["DB_NAME"]    ?? "skd_cat_bkn";
$user    = $_ENV["DB_USER"]    ?? "root";
$pass    = $_ENV["DB_PASS"]    ?? "";
$charset = $_ENV["DB_CHARSET"]  ?? "utf8mb4";

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
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Session configuration
ini_set("session.gc_maxlifetime", 3600);
ini_set("session.cookie_httponly", 1);
ini_set("session.cookie_samesite", "Lax");
ini_set("session.use_strict_mode", 1);

$secureCookie = (($_ENV["APP_ENV"] ?? "development") === "production") && 
                 (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");

session_set_cookie_params([
    "lifetime" => 3600,
    "path" => "/",
    "domain" => "",
    "secure" => $secureCookie,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

// Load helpers
require "../helpers.php";

header("Content-Type: application/json; charset=utf-8");

try {
    $userId = (int)($_SESSION["user_id"] ?? 0);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(["error" => "Autentikasi diperlukan. Silakan login terlebih dahulu."]);
        exit;
    }

    if (!validateCsrfApi()) {
        http_response_code(403);
        echo json_encode(["error" => "CSRF token tidak valid"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $tryoutType = $data["tryout_type"] ?? "full";
    $subtesList = $data["subtes"] ?? ["TWK", "TIU", "TKP"];

    $validTypes = ["full", "mini", "practice"];
    if (!in_array($tryoutType, $validTypes)) {
        http_response_code(400);
        echo json_encode(["error" => "Tipe tryout tidak valid"]);
        exit;
    }

    $validSubtes = ["TWK", "TIU", "TKP"];
    foreach ($subtesList as $subtes) {
        if (!in_array($subtes, $validSubtes)) {
            http_response_code(400);
            echo json_encode(["error" => "Subtes tidak valid: $subtes"]);
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id = ? AND status = \"berjalan\"");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Anda memiliki sesi tryout yang sedang berjalan. Selesaikan terlebih dahulu atau hentikan sesi tersebut."]);
        exit;
    }

    $pdo->beginTransaction();
    
    try {
        $sessionName = match($tryoutType) {
            "full" => "Tryout SKD Lengkap",
            "mini" => "Tryout Mini",
            "practice" => "Latihan Soal",
            default => "Tryout SKD"
        };
        
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status) VALUES (?, ?, NOW(), \"berjalan\")");
        $stmt->execute([$userId, $sessionName]);
        $sessionId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, urutan FROM subtes_config WHERE is_active = 1 AND subtes = ?");
        $insertSubtes = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($subtesList as $subtes) {
            $stmt->execute([$subtes]);
            $config = $stmt->fetch();
            
            if ($config) {
                $duration = $config["durasi_menit"];
                if ($tryoutType === "mini") {
                    $duration = floor($duration / 2);
                } elseif ($tryoutType === "practice") {
                    $duration = floor($duration / 3);
                }
                
                $insertSubtes->execute([
                    $sessionId,
                    $config["subtes"],
                    $duration,
                    $config["jumlah_soal"],
                    $config["passing_grade"],
                    $config["urutan"]
                ]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            "success" => true,
            "session_id" => $sessionId,
            "session_name" => $sessionName,
            "redirect" => "/pages/tryout.php?session_id=$sessionId",
            "subtes_count" => count($subtesList),
            "estimated_duration" => $tryoutType === "full" ? 110 : ($tryoutType === "mini" ? 55 : 35)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Terjadi kesalahan server: " . $e->getMessage()]);
}
?>',
    
    'pages/materi_twk.php' => '<?php
require "../config.php";
require "../helpers.php";

if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$file = "../content/materi_twk.php";
$materi = file_exists($file) ? require $file : [];

$userId = $_SESSION["user_id"];
$bookmarkedIds = [];
$progressData = [];

if ($userId) {
    $stmt = $pdo->prepare("SELECT materi_id FROM materi_bookmarks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $bookmarkedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT materi_id, progress_percent FROM materi_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $progressData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$pageTitle = "Materi TWK - Tes Wawasan Kebangsaan";
$activePage = "materi";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<link rel="stylesheet" href="/assets/style.css">
<title><?php echo $pageTitle; ?></title>
</head>
<body>
<?php require "../includes/navigation.php"; ?>

<div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
    <div class="card">
        <div class="card-header">
            <h1>📚 Materi Tes Wawasan Kebangsaan (TWK)</h1>
            <p>Berdasarkan Permen PANRB No. 20 Tahun 2021 & KepmenPANRB No. 208/2025</p>
        </div>
        
        <div class="card-body">
            <div class="materi-navigation">
                <a href="materi.php?subtes=TWK" class="btn btn-primary">← Kembali ke Materi</a>
                <div class="subtest-tabs">
                    <a href="materi_twk.php" class="btn btn-primary active">TWK</a>
                    <a href="materi_tiu.php" class="btn btn-secondary">TIU</a>
                    <a href="materi_tkp.php" class="btn btn-secondary">TKP</a>
                </div>
            </div>

            <?php if (empty($materi)): ?>
                <div class="alert alert-warning">
                    <p>📝 Materi TWK sedang dalam pengembangan. Silakan kembali lagi nanti.</p>
                </div>
            <?php else: ?>
                <?php foreach ($materi as $item): ?>
                    <div class="materi-section" id="<?php echo $item["id"]; ?>">
                        <div class="materi-header">
                            <h2><?php echo $item["judul"]; ?></h2>
                            <div class="materi-actions">
                                <button class="btn btn-sm btn-outline" onclick="toggleBookmark("<?php echo $item["id"]; ?>")">
                                    <span id="bookmark-<?php echo $item["id"]; ?>">
                                        <?php echo in_array($item["id"], $bookmarkedIds) ? "❤️" : "🤍"; ?>
                                    </span>
                                </button>
                                <button class="btn btn-sm btn-outline" onclick="markProgress("<?php echo $item["id"]; ?>")">
                                    ✓ Tandai Selesai
                                </button>
                            </div>
                        </div>
                        
                        <div class="materi-content">
                            <?php echo $item["konten"]; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleBookmark(materiId) {
    fetch("/api/bookmark_question.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({materi_id: materiId})
    })
    .then(response => response.json())
    .then(data => {
        const bookmarkEl = document.getElementById("bookmark-" + materiId);
        bookmarkEl.textContent = data.bookmarked ? "❤️" : "🤍";
    });
}

function markProgress(materiId) {
    fetch("/api/update_materi_progress.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({materi_id: materiId, progress: 100})
    })
    .then(response => response.json())
    .then(data => {
        alert("Progress berhasil disimpan!");
    });
}
</script>

</body>
</html>'
];

// Create files
$success_count = 0;
$error_count = 0;

foreach ($files_to_create as $filepath => $content) {
    $full_path = __DIR__ . '/' . $filepath;
    $directory = dirname($full_path);
    
    // Create directory if it doesn't exist
    if (!is_dir($directory)) {
        if (mkdir($directory, 0755, true)) {
            echo "<p>✅ Created directory: $directory</p>";
        } else {
            echo "<p>❌ Failed to create directory: $directory</p>";
            $error_count++;
            continue;
        }
    }
    
    // Write file
    if (file_put_contents($full_path, $content)) {
        echo "<p>✅ Created file: $filepath</p>";
        $success_count++;
    } else {
        echo "<p>❌ Failed to create file: $filepath</p>";
        $error_count++;
    }
}

echo "<h3>Upload Summary:</h3>";
echo "<p>✅ Success: $success_count files</p>";
echo "<p>❌ Errors: $error_count files</p>";

if ($error_count === 0) {
    echo "<p>🎉 All files uploaded successfully!</p>";
    echo "<p><a href='/api/start_tryout.php'>Test start_tryout.php</a></p>";
    echo "<p><a href='/pages/materi_twk.php'>Test materi_twk.php</a></p>";
} else {
    echo "<p>⚠️ Some files failed to upload. Please check permissions.</p>";
}

?>

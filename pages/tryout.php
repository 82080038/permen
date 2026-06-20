<?php
require '../config.php';
require '../helpers.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $baseUrl . '/pages/login.php');
    exit;
}
if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . $baseUrl . '/pages/admin_dashboard.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

// Cek session aktif user, jika tidak ada buat baru
// Jika session_id dari GET (mode latihan), gunakan session tersebut
// Jika scheduled dari GET, buat session dari scheduled tryout
$sessionId = 0;
$scheduledTryoutId = 0;

if (!empty($_GET['session_id'])) {
    $sessionId = (int)$_GET['session_id'];
    // Validasi kepemilikan
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    if (!$stmt->fetchColumn()) {
        $sessionId = 0; // invalid, buat baru
    }
} elseif (!empty($_GET['scheduled'])) {
    // Check if scheduled_tryouts table exists
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'scheduled_tryouts'")->fetch();
    } catch (PDOException $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        // Table doesn't exist, redirect to regular tryout
        header('Location: ' . $baseUrl . '/pages/tryout.php');
        exit;
    }
    
    // Create session from scheduled tryout
    $scheduledTryoutId = (int)$_GET['scheduled'];
    $stmt = $pdo->prepare("SELECT s.*, r.id as registration_id FROM scheduled_tryouts s 
        JOIN scheduled_tryout_registrations r ON s.id = r.scheduled_tryout_id 
        WHERE s.id = ? AND r.user_id = ? AND r.status = 'registered' AND s.waktu_mulai <= NOW()");
    $stmt->execute([$scheduledTryoutId, $userId]);
    $scheduledTryout = $stmt->fetch();
    
    if ($scheduledTryout) {
        // Create tryout session from scheduled tryout
        // Detect valid status value for this database
        $insertStatus = 'berjalan'; // default local
        try {
            $colInfo = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = 'status'")->fetch();
            if ($colInfo && strpos($colInfo['Type'], 'ongoing') !== false) {
                $insertStatus = 'ongoing';
            }
        } catch (PDOException $e) {}
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$userId, $scheduledTryout['nama'], $insertStatus]);
        $sessionId = $pdo->lastInsertId();
        
        // Insert session_subtes from scheduled tryout duration
        try {
            $cfg = $pdo->query("SELECT subtes, durasi_menit, jumlah_soal, passing_grade FROM subtes_config WHERE is_active = 1 ORDER BY id");
        } catch (PDOException $e) {
            $cfg = $pdo->query("SELECT subtes, durasi_menit, jumlah_soal, passing_grade FROM subtes_config WHERE aktif = 1 ORDER BY id");
        }
        $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?,?,?,?,?,?)");
        foreach ($cfg as $c) {
            // Use scheduled tryout duration divided by 3 for each subtest
            $duration = floor($scheduledTryout['durasi_menit'] / 3);
            $ins->execute([$sessionId, $c['subtes'], $duration, $c['jumlah_soal'], $c['passing_grade'], $c['urutan']]);
        }
        
        // Update registration status
        $pdo->prepare("UPDATE scheduled_tryout_registrations SET status='joined' WHERE id=?")->execute([$scheduledTryout['registration_id']]);
    }
}

if (!$sessionId) {
    // Check if tryout_sessions table exists
    try {
        $tryoutSessionsExists = $pdo->query("SHOW TABLES LIKE 'tryout_sessions'")->fetch();
    } catch (PDOException $e) {
        $tryoutSessionsExists = false;
    }
    if (!$tryoutSessionsExists) {
        // Table doesn't exist, show error page
        die("Database setup incomplete. Please contact administrator.");
    }
    
    // Check which status value is valid for this database
    $validStatus = 'ongoing'; // Default to production value
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = 'status'");
        $columnInfo = $testStmt->fetch();
        if ($columnInfo && strpos($columnInfo['Type'], 'berjalan') !== false) {
            $validStatus = 'berjalan'; // Local database uses 'berjalan'
        }
    } catch (PDOException $e) {
        // Assume production default
    }
    
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id = ? AND status = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $validStatus]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        // Check if this is a fresh page load (not from a form submission)
        // Show a confirmation dialog to the user about resuming
        $sessionId = $existing;
        // Pass a flag to JS to show resume notice
        $resumingSession = true;
    } else {
        // Check for strict_mode parameter from POST (when starting new tryout)
        $strictMode = 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['strict_mode'])) {
            $strictMode = (int)$_POST['strict_mode'];
        }

        // Check for package_id parameter from POST
        $packageId = 0;
        $packageName = 'Try Out SKD';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
            $packageId = (int)$_POST['package_id'];
            // Check if tryout_packages table exists
            $packagesExists = $pdo->query("SHOW TABLES LIKE 'tryout_packages'")->fetch();
            if ($packagesExists) {
                // Get package details
                $stmt = $pdo->prepare("SELECT * FROM tryout_packages WHERE id=? AND aktif=1");
                $stmt->execute([$packageId]);
                $package = $stmt->fetch();
                if ($package) {
                    $packageName = $package['nama'];
                }
            }
        }

        // Reuse $validStatus already determined above
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai, status) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$userId, $packageName, $validStatus]);
        $sessionId = $pdo->lastInsertId();
        
        // Check if session_subtes table exists
        try {
            $sessionSubtesExists = $pdo->query("SHOW TABLES LIKE 'session_subtes'")->fetch();
        } catch (PDOException $e) {
            $sessionSubtesExists = false;
        }
        if ($sessionSubtesExists) {
            // Insert ke tabel normalisasi session_subtes dari package atau konfigurasi global
            if ($packageId > 0 && isset($package)) {
                // Use package configuration
                $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade) VALUES (?,?,?,?,?)");
                $ins->execute([$sessionId, 'TWK', $package['durasi_twk'], $package['jumlah_soal_twk'], $package['passing_grade_twk']]);
                $ins->execute([$sessionId, 'TIU', $package['durasi_tiu'], $package['jumlah_soal_tiu'], $package['passing_grade_tiu']]);
                $ins->execute([$sessionId, 'TKP', $package['durasi_tkp'], $package['jumlah_soal_tkp'], $package['passing_grade_tkp']]);
            } else {
                // Use global configuration
                try {
                    $cfg = $pdo->query("SELECT subtes, durasi_menit, jumlah_soal, passing_grade FROM subtes_config WHERE is_active = 1 ORDER BY id");
                } catch (PDOException $e) {
                    try {
                        $cfg = $pdo->query("SELECT subtes, durasi_menit, jumlah_soal, passing_grade FROM subtes_config WHERE aktif = 1 ORDER BY id");
                    } catch (PDOException $e2) {
                        $cfg = [];
                    }
                }
                $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade) VALUES (?,?,?,?,?)");
                $urutan = 1;
                foreach ($cfg as $c) {
                    $ins->execute([$sessionId, $c['subtes'], $c['durasi_menit'], $c['jumlah_soal'], $c['passing_grade']]);
                    $urutan++;
                }
            }
        }
    }
}

// Ambil data session untuk timer dari session_subtes (normalisasi)
$sessionSubtesExists = false;
try {
    $sessionSubtesExists = $pdo->query("SHOW TABLES LIKE 'session_subtes'")->fetch();
} catch (PDOException $e) {
    $sessionSubtesExists = false;
}

if ($sessionSubtesExists) {
    try {
        $stmt = $pdo->prepare("SELECT subtes, durasi_menit FROM session_subtes WHERE session_id = ? ORDER BY id");
        $stmt->execute([$sessionId]);
        $subtesRows = $stmt->fetchAll();
    } catch (PDOException $e) {
        $subtesRows = [];
    }
} else {
    $subtesRows = [];
}
$durasiMap = ['TWK'=>30,'TIU'=>35,'TKP'=>45];
foreach ($subtesRows as $row) {
    $durasiMap[$row['subtes']] = (int)$row['durasi_menit'];
}
$totalDuration = array_sum($durasiMap);

// Ambil waktu mulai per subtes untuk timer per subtes
if ($sessionSubtesExists) {
    try {
        $stmt = $pdo->prepare("SELECT subtes, UNIX_TIMESTAMP(waktu_mulai_subtes) as start_ts, durasi_menit FROM session_subtes WHERE session_id = ? ORDER BY id");
        $stmt->execute([$sessionId]);
        $subtesTimers = [];
        $currentSubtes = '';
        foreach ($stmt as $row) {
            $sub = $row['subtes'];
            $dur = (int)$row['durasi_menit'];
            $start = $row['start_ts'] ? (int)$row['start_ts'] : 0;
            $elapsed = $start ? time() - $start : 0;
            $remaining = max(0, $dur * 60 - $elapsed);
            $subtesTimers[$sub] = ['durasi'=>$dur, 'start'=>$start, 'remaining'=>$remaining];
            if (!$currentSubtes && $start) $currentSubtes = $sub; // subtes yang sudah dimulai = aktif
            if (!$currentSubtes) $currentSubtes = $sub; // fallback ke subtes pertama
        }
    } catch (PDOException $e) {
        $subtesTimers = [];
        $currentSubtes = '';
    }
} else {
    $subtesTimers = [];
    $currentSubtes = '';
}
// Kalau tidak ada subtes yang dimulai, set subtes pertama
$firstSubtes = array_key_first($subtesTimers) ?: 'TWK';
if (!$currentSubtes) $currentSubtes = $firstSubtes;

// Ensure subtesTimers is never empty (fallback to defaults)
if (empty($subtesTimers)) {
    $subtesTimers = [
        'TWK' => ['durasi' => 30, 'start' => 0, 'remaining' => 1800],
        'TIU' => ['durasi' => 35, 'start' => 0, 'remaining' => 2100],
        'TKP' => ['durasi' => 45, 'start' => 0, 'remaining' => 2700]
    ];
    $currentSubtes = 'TWK';
}

// Debug: log variables before rendering (commented out to prevent any output)
// error_log("tryout.php: sessionId=$sessionId, currentSubtes=$currentSubtes, subtesTimers=" . json_encode($subtesTimers));

// Hitung total remaining untuk display utama (sum semua subtes yang belum/belum selesai)
$remainingSeconds = 0;
foreach ($subtesTimers as $sub => $t) {
    $remainingSeconds += $t['remaining'];
}
$timerM = str_pad(floor($remainingSeconds / 60), 2, '0', STR_PAD_LEFT);
$timerS = str_pad($remainingSeconds % 60, 2, '0', STR_PAD_LEFT);

// Session info
$strictMode = 0; // Default non-strict
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Aplikasi Try Out SKD CAT-BKN untuk persiapan masuk Sekolah Kedinasan.">
<meta name="theme-color" content="#1a5276">
<link rel="icon" href="data:,">
<title>Try Out SKD — CAT BKN</title>
<style>
:root{--bg-body:#f0f2f5;--bg-sidebar:#fff;--bg-content:#fff;--bg-passage:#f0f7ff;--bg-pembahasan:#fffbea;--bg-question:#fafafa;--bg-option-hover:#eaf2f8;--bg-option-selected:#d4edda;--bg-number:#f8f9fa;--text-main:#222;--text-muted:#555;--text-passage:#333;--text-heading:#1a5276;--text-info:#555;--img-bg:#fff;--img-border:#ddd;--border-light:#ddd;--border-passage:#b8d4f0;--header-bg:#1a5276;--nav-bg:#2980b9;--timer-bg:#e74c3c}
[data-theme="dark"]{--bg-body:#1a1a2e;--bg-sidebar:#16213e;--bg-content:#16213e;--bg-passage:#1a1a3e;--bg-pembahasan:#2a2a4e;--bg-question:#1e1e3f;--bg-option-hover:#1a5276;--bg-option-selected:#27ae60;--bg-number:#16213e;--text-main:#e8e8e8;--text-muted:#d0d0d0;--text-passage:#e8e8e8;--text-heading:#74b9ff;--text-info:#d0d0d0;--img-bg:#1a1a2e;--img-border:#555;--border-light:#555;--border-passage:#555;--header-bg:#0f3460;--nav-bg:#1a5276;--timer-bg:#c0392b}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg-body);color:var(--text-main);-webkit-text-size-adjust:100%;transition:background .2s,color .2s}
.topbar{background:var(--header-bg);color:#fff;padding:.5rem 1rem;font-size:.8rem;display:flex;flex-wrap:wrap;gap:.4rem .6rem;align-items:center}
.topbar a{color:#fff;text-decoration:none;margin-right:.6rem;min-height:44px;display:flex;align-items:center;font-size:.8rem}
.header{background:var(--header-bg);color:#fff;padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1rem}.timer{font-size:1rem;font-weight:bold;background:var(--timer-bg);padding:.25rem .6rem;border-radius:4px;white-space:nowrap}
.nav{background:var(--nav-bg);padding:.4rem 1rem;color:#fff;font-size:.85rem}
.main{display:flex;max-width:1200px;margin:.8rem auto;gap:.8rem;padding:0 .8rem;flex-wrap:wrap}
.sidebar{width:240px;background:var(--bg-sidebar);border-radius:6px;padding:.8rem;box-shadow:0 1px 4px rgba(0,0,0,.08);height:fit-content}
.sidebar h3{font-size:.9rem;margin-bottom:.5rem;color:var(--text-heading)}
.number-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.35rem}
.number-grid button{border:1px solid var(--border-light);background:var(--bg-number);color:var(--text-main);padding:.35rem;border-radius:4px;cursor:pointer;font-size:.75rem;min-height:36px}
.number-grid button.answered{background:#27ae60;color:#fff;border-color:#27ae60}
.number-grid button.marked{background:#f1c40f;color:#222;border-color:#f1c40f}
.number-grid button.active{background:#2980b9;color:#fff;border-color:#2980b9}
.content{flex:1;background:var(--bg-content);border-radius:6px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,.08);min-height:300px}
.question{font-size:1rem;margin-bottom:1rem;line-height:1.6}
.question-scrollable{max-height:220px;overflow-y:auto;padding-right:.5rem;border:1px solid var(--border-light);border-radius:4px;padding:.6rem;background:var(--bg-question)}
.question-image{display:block;margin:.6rem 0;max-width:100%;max-height:200px;border:1px solid var(--img-border);border-radius:4px;padding:.3rem;background:var(--img-bg)}
.passage-box{background:var(--bg-passage);border:1px solid var(--border-passage);border-radius:6px;padding:.8rem;margin-bottom:1rem}
.passage-judul{font-weight:bold;color:var(--text-heading);font-size:.95rem;margin-bottom:.4rem}
.passage-bacaan{font-size:.9rem;color:var(--text-passage);line-height:1.7;max-height:250px;overflow-y:auto;padding-right:.5rem}
.passage-info{font-size:.8rem;color:var(--text-info);margin-bottom:.5rem;font-style:italic}
.options label{display:block;padding:.9rem 1rem;border:1px solid var(--border-light);border-radius:6px;margin-bottom:.6rem;cursor:pointer;transition:.2s;font-size:.95rem;min-height:44px}
.options label:hover{background:var(--bg-option-hover)}
.options input{margin-right:.6rem;min-width:22px;min-height:22px;width:22px;height:22px;cursor:pointer}
.options label.selected{background:var(--bg-option-selected);border-color:#27ae60}
.btn-group{margin-top:1.2rem;display:flex;gap:.5rem;flex-wrap:wrap}
.btn{background:#2980b9;color:#fff;border:none;padding:.65rem 1rem;border-radius:5px;cursor:pointer;font-size:.9rem;min-height:44px;min-width:44px}
.btn.finish{background:#e74c3c}.btn:disabled{opacity:.5;cursor:not-allowed}
.pembahasan{margin-top:1rem;padding:.8rem;background:var(--bg-pembahasan);border-left:4px solid #f1c40f;border-radius:4px;font-size:.9rem}
#sidebarToggle{display:none}
.theme-toggle{background:transparent;border:1px solid rgba(255,255,255,.4);color:#fff;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:.8rem;margin-left:.5rem}
.font-toggle{background:transparent;border:1px solid rgba(255,255,255,.4);color:#fff;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:.8rem;margin-left:.3rem}
/* Font sizes */
[data-font-size="small"] .question{font-size:.85rem}
[data-font-size="small"] .options label{font-size:.82rem}
[data-font-size="large"] .question{font-size:1.15rem}
[data-font-size="large"] .options label{font-size:1.1rem}
/* Image zoom modal */
#imgZoomModal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.85);z-index:2000;justify-content:center;align-items:center;flex-direction:column;padding:1rem}
#imgZoomModal.show{display:flex}
#imgZoomModal img{max-width:95%;max-height:80vh;object-fit:contain}
#imgZoomModal .zoom-close{position:absolute;top:1rem;right:1rem;background:#fff;color:#333;border:none;padding:.5rem 1rem;border-radius:4px;cursor:pointer;font-size:1rem}
#imgZoomModal .zoom-hint{color:#fff;font-size:.85rem;margin-top:.5rem}
@media(max-width:600px){
.main{flex-direction:column;padding:0 .6rem}
.sidebar{width:100%;position:relative}
.sidebar.collapsed .number-grid{display:none}
#sidebarToggle{display:block;background:#1a5276;color:#fff;border:none;padding:.4rem .8rem;border-radius:4px;font-size:.8rem;margin-bottom:.5rem;cursor:pointer}
.content{padding:.8rem}
.question{font-size:.95rem}
.options label{padding:1.1rem 1rem;font-size:1rem}
.options input{min-width:22px;min-height:22px}
.number-grid button{min-height:44px;font-size:.85rem}
.btn-group{flex-direction:column}
.btn{width:100%;margin-bottom:.3rem;font-size:1rem}
.passage-bacaan{max-height:200px}
.question-scrollable{max-height:180px}
/* Sticky timer on mobile */
.header{position:sticky;top:0;z-index:100;box-shadow:0 2px 4px rgba(0,0,0,.15)}
.timer{font-size:1.1rem;padding:.35rem .8rem}
}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Try Out SKD CAT-BKN'; $activePage = 'tryout'; $showThemeToggle = true; ?>
<?php require '../includes/navigation.php'; ?>
<?php 
$breadcrumbs = [
    ['label' => 'Beranda', 'url' => '/index.php'],
    ['label' => 'Try Out', 'url' => '']
];
require '../includes/breadcrumbs.php'; 
?>
<div class="header">
<h1>Try Out SKD CAT-BKN</h1>
<div class="timer" id="timer"><?= $timerM ?>:<?= $timerS ?></div>
</div>
<div class="nav">
<span id="subtes-info">Memuat soal...</span>
</div>
<div class="main" id="main-content">
<div class="sidebar" id="sidebar">
<button id="sidebarToggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')" aria-label="Toggle sidebar navigation">Sembunyikan/Tampilkan Navigasi</button>
<h3>Navigasi Soal</h3>
<div id="navStatus" style="font-size:.8rem;color:var(--text-muted);margin-bottom:.4rem">Memuat...</div>
<div style="margin-bottom:.4rem">
    <button onclick="filterRagu()" style="padding:.4rem .6rem;font-size:.8rem;background:#f39c12;color:#fff;border:none;border-radius:3px;cursor:pointer;margin-right:.5rem;min-height:44px;min-width:44px">🔍 Ragu-ragu</button>
    <button onclick="filterUnanswered()" style="padding:.4rem .6rem;font-size:.8rem;background:#95a5a6;color:#fff;border:none;border-radius:3px;cursor:pointer;min-height:44px;min-width:44px">🔍 Belum Dijawab</button>
    <button onclick="showAll()" style="padding:.4rem .6rem;font-size:.8rem;background:#7f8c8d;color:#fff;border:none;border-radius:3px;cursor:pointer;min-height:44px;min-width:44px">🔍 Semua</button>
</div>
<div class="number-grid" id="numberGrid">
<!-- Placeholder buttons shown during loading -->
<button style="opacity:.5;cursor:default" disabled>1</button>
<button style="opacity:.5;cursor:default" disabled>2</button>
<button style="opacity:.5;cursor:default" disabled>3</button>
<button style="opacity:.5;cursor:default" disabled>4</button>
<button style="opacity:.5;cursor:default" disabled>5</button>
<button style="opacity:.5;cursor:default" disabled>6</button>
<button style="opacity:.5;cursor:default" disabled>7</button>
<button style="opacity:.5;cursor:default" disabled>8</button>
<button style="opacity:.5;cursor:default" disabled>9</button>
<button style="opacity:.5;cursor:default" disabled>10</button>
</div>
<div style="margin-top:1rem;font-size:.85rem;color:var(--text-muted)">
<div><span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:2px;margin-right:4px"></span> Sudah dijawab</div>
<div><span style="display:inline-block;width:12px;height:12px;background:#f1c40f;border-radius:2px;margin-right:4px"></span> Ragu-ragu</div>
<div><span style="display:inline-block;width:12px;height:12px;background:#2980b9;border-radius:2px;margin-right:4px"></span> Soal aktif</div>
<div><span style="display:inline-block;width:12px;height:12px;background:var(--bg-number);border:1px solid var(--border-light);border-radius:2px;margin-right:4px"></span> Belum dijawab</div>
</div>
</div>
<div class="content">
<div id="loadingIndicator" style="text-align:center;padding:3rem;color:#777">
<div style="font-size:2rem;margin-bottom:1rem">⏳</div>
<div>Memuat soal...</div>
</div>
<div class="passage-box" id="passageBox" style="display:none">
    <div class="passage-judul" id="passageJudul"></div>
    <div class="passage-bacaan" id="passageBacaan"></div>
</div>
<div id="soalContainer">
<p style="color:var(--text-muted)">Memuat soal, mohon tunggu...</p>
</div>
<div class="btn-group">
<button class="btn" id="btnPrev" onclick="prevSoal()" aria-label="Soal sebelumnya">Sebelumnya</button>
<button class="btn" id="btnNext" onclick="nextSoal()" aria-label="Soal selanjutnya">Selanjutnya</button>
<button class="btn" style="background:#f1c40f;color:#333" id="btnMark" onclick="toggleMark()" aria-label="Tandai ragu-ragu">Ragu (M)</button>
<button class="btn" style="background:#9b59b6;color:#fff" id="btnBookmark" onclick="toggleBookmark()" aria-label="Simpan ke favorit">⭐ Favorit</button>
<button class="btn" style="background:#e67e22;color:#fff" id="btnPause" onclick="togglePause()" aria-label="Pause/Resume tryout">⏸ Pause</button>
<button class="btn finish" onclick="finishTryout()" aria-label="Selesaikan tryout">Selesai</button>
</div>
<div id="strictModeIndicator" style="display:none;text-align:center;padding:.5rem;background:#e74c3c;color:#fff;font-size:.9rem;margin-top:.5rem;border-radius:4px">
⚠️ Strict Mode Aktif: Tidak bisa kembali ke soal sebelumnya
</div>
<div id="pauseIndicator" style="display:none;text-align:center;padding:.5rem;background:#e67e22;color:#fff;font-size:.9rem;margin-top:.5rem;border-radius:4px">
⏸ Tryout Dipause - Klik Resume untuk melanjutkan
</div>
<div class="pembahasan" id="pembahasanBox"></div>
</div>
</div>

<!-- Image Zoom Modal -->
<div id="imgZoomModal" onclick="closeZoom()">
    <button class="zoom-close" onclick="event.stopPropagation();closeZoom()">Tutup</button>
    <img id="zoomImg" src="" alt="Zoomed">
    <div class="zoom-hint">Ketuk gambar atau di luar area untuk menutup</div>
</div>

<base href="<?php echo $baseUrl ?? '/permen'; ?>">
<script>window.APP_BASE_URL = '<?php echo rtrim($baseUrl ?? '/permen', '/'); ?>';</script>
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/app.js"></script>
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/js/dist/tryout.js"></script>
<script>
// Initialize TryoutManager with PHP variables
document.addEventListener('DOMContentLoaded', function() {
<?php if (!empty($resumingSession)): ?>
    // Show resume notice for concurrent/existing session
    var resumeBanner = document.createElement('div');
    resumeBanner.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#f39c12;color:#fff;padding:.8rem;text-align:center;z-index:10000;font-size:.9rem';
    resumeBanner.textContent = '⚠️ Anda memiliki sesi tryout yang sedang berjalan. Sesi ini akan dilanjutkan.';
    document.body.appendChild(resumeBanner);
    setTimeout(function() { resumeBanner.style.transition = 'opacity 0.5s'; resumeBanner.style.opacity = '0'; setTimeout(function() { resumeBanner.remove(); }, 500); }, 5000);
<?php endif; ?>
    window.tryoutManager = new TryoutManager({
        sessionId: <?= json_encode($sessionId) ?>,
        csrfToken: <?= json_encode(getCsrfTokenForApi()) ?>,
        strictMode: <?= json_encode($strictMode) ?>,
        baseUrl: '<?= $baseUrl ?>',
        remainingSeconds: <?= json_encode($remainingSeconds) ?>,
        subtesTimers: <?= json_encode($subtesTimers) ?>,
        currentSubtes: <?= json_encode($currentSubtes) ?>
    });
});
</script>
</body>
</html>

<?php
require '../config.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
require '../helpers.php';

// Guard: only admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminName = e($_SESSION['user_nama'] ?? 'Admin');

// Stats - with error handling
$stats = [];
try {
    $stats['total_soal'] = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
} catch (Exception $e) {
    $stats['total_soal'] = 0;
}
try {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
} catch (Exception $e) {
    $stats['total_users'] = 0;
}
try {
    $stats['total_tryout'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions")->fetchColumn();
} catch (Exception $e) {
    $stats['total_tryout'] = 0;
}
try {
    $stats['tryout_selesai'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions WHERE status='selesai'")->fetchColumn();
} catch (Exception $e) {
    $stats['tryout_selesai'] = 0;
}

// Users list with pagination
$usersLimit = min(50, max(10, (int)($_GET['users_limit'] ?? 20)));
$usersOffset = (int)($_GET['users_offset'] ?? 0);

// Get total count
try {
    $usersTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
} catch (Exception $e) {
    $usersTotal = 0;
}

// Get paginated users
try {
    $users = $pdo->prepare("SELECT id, nama, no_hp, sekolah_asal, tahun_tamat, instansi, created_at, status FROM users WHERE role='user' ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $users->execute([$usersLimit, $usersOffset]);
    $users = $users->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// Calculate pagination metadata
$usersTotalPages = ceil($usersTotal / $usersLimit);
$usersCurrentPage = floor($usersOffset / $usersLimit) + 1;
$usersHasNext = $usersCurrentPage < $usersTotalPages;
$usersHasPrev = $usersCurrentPage > 1;

// Recent tryouts with pagination
$tryoutsLimit = min(50, max(10, (int)($_GET['tryouts_limit'] ?? 20)));
$tryoutsOffset = (int)($_GET['tryouts_offset'] ?? 0);

// Get total count
try {
    $tryoutsTotal = $pdo->query("SELECT COUNT(*) FROM tryout_sessions")->fetchColumn();
} catch (Exception $e) {
    $tryoutsTotal = 0;
}

// Get paginated tryouts
try {
    $tryouts = $pdo->prepare("SELECT ts.id, ts.nama, u.nama as peserta, ts.total_nilai, ts.status, ts.waktu_mulai
    FROM tryout_sessions ts LEFT JOIN users u ON ts.user_id = u.id
    ORDER BY ts.id DESC LIMIT ? OFFSET ?");
    $tryouts->execute([$tryoutsLimit, $tryoutsOffset]);
    $tryouts = $tryouts->fetchAll();
} catch (Exception $e) {
    $tryouts = [];
}

// Calculate pagination metadata
$tryoutsTotalPages = ceil($tryoutsTotal / $tryoutsLimit);
$tryoutsCurrentPage = floor($tryoutsOffset / $tryoutsLimit) + 1;
$tryoutsHasNext = $tryoutsCurrentPage < $tryoutsTotalPages;
$tryoutsHasPrev = $tryoutsCurrentPage > 1;

// Soal per subtes
try {
    $soalPerSubtes = $pdo->query("SELECT subtes, COUNT(*) as jumlah FROM questions GROUP BY subtes")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $soalPerSubtes = [];
}

// Subtes config
try {
    $subtesConfig = $pdo->query("SELECT * FROM subtes_config ORDER BY nama")->fetchAll();
} catch (Exception $e) {
    $subtesConfig = [];
}

// Analytics data - simplified for performance
// User registration trend (last 30 days) - only if requested
$userTrend = [];
if (isset($_GET['tab']) && $_GET['tab'] === 'analytics') {
    try {
        $userTrend = $pdo->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users
            WHERE role='user' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll();
    } catch (Exception $e) {
        $userTrend = [];
    }
}

// Tryout completion rate - cached query
try {
    $tryoutStats = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) as completed,
            AVG(CASE WHEN status='selesai' THEN total_nilai ELSE NULL END) as avg_score
        FROM tryout_sessions
    ")->fetch();
} catch (Exception $e) {
    $tryoutStats = ['total' => 0, 'completed' => 0, 'avg_score' => 0];
}

// Average scores by subtes - cached query
try {
    $avgScores = $pdo->query("
        SELECT
            AVG(nilai_tkp) as avg_tkp,
            AVG(nilai_tiu) as avg_tiu,
            AVG(nilai_twk) as avg_twk
        FROM tryout_sessions
        WHERE status='selesai'
    ")->fetch();
} catch (Exception $e) {
    $avgScores = ['avg_tkp' => 0, 'avg_tiu' => 0, 'avg_twk' => 0];
}

// Activity heatmap data (last 30 days) - only load when analytics tab is active
$activityHeatmap = [];
if (isset($_GET['tab']) && $_GET['tab'] === 'analytics') {
    try {
        $activityHeatmap = $pdo->query("
            SELECT DATE(created_at) as date,
                   COUNT(DISTINCT user_id) as active_users
            FROM (
                SELECT user_id, created_at FROM tryout_sessions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT user_id, created_at FROM daily_quiz_sessions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT user_id, waktu_mulai as created_at FROM personal_practice_sessions WHERE waktu_mulai >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ) activities
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll();
    } catch (Exception $e) {
        // Fallback if personal_practice_sessions table doesn't exist
        try {
            $activityHeatmap = $pdo->query("
                SELECT DATE(created_at) as date,
                       COUNT(DISTINCT user_id) as active_users
                FROM (
                    SELECT user_id, created_at FROM tryout_sessions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    UNION ALL
                    SELECT user_id, created_at FROM daily_quiz_sessions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ) activities
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ")->fetchAll();
        } catch (Exception $e2) {
            $activityHeatmap = [];
        }
    }
}

// Top materials accessed - only load when analytics tab is active
$topMaterials = [];
if (isset($_GET['tab']) && $_GET['tab'] === 'analytics') {
    try {
        $topMaterials = $pdo->query("
            SELECT m.judul, COUNT(mp.user_id) as access_count
            FROM materi_progress mp
            JOIN materi m ON mp.materi_id = m.id
            WHERE mp.last_read_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY m.id, m.judul
            ORDER BY access_count DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $topMaterials = [];
    }
}

// Tryout packages for event creation - fetch once to avoid N+1 in HTML rendering
$tryoutPackages = [];
try {
    $tryoutPackages = $pdo->query("SELECT id, nama FROM tryout_packages WHERE is_active = 1 ORDER BY nama")->fetchAll();
} catch (Exception $e) {
    $tryoutPackages = [];
}

// Top questions answered incorrectly - only load when analytics tab is active
$topWrongQuestions = [];
if (isset($_GET['tab']) && $_GET['tab'] === 'analytics') {
    try {
        $topWrongQuestions = $pdo->query("
            SELECT q.id, q.subtes, q.topik, q.pertanyaan,
                   COUNT(*) as wrong_count
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.jawaban_user != q.jawaban_benar
              AND a.jawaban_user IS NOT NULL
              AND a.jawaban_user != ''
            GROUP BY q.id, q.subtes, q.topik, q.pertanyaan
            ORDER BY wrong_count DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $topWrongQuestions = [];
    }
}

// Handle update subtes_config
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_config') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $updateMsg = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $upd = $pdo->prepare("UPDATE subtes_config SET durasi_menit=?, jumlah_soal=?, passing_grade=?, urutan=? WHERE subtes=?");
        foreach ($_POST['config'] as $sub => $cfg) {
            $upd->execute([
                (int)$cfg['durasi_menit'],
                (int)$cfg['jumlah_soal'],
                (int)$cfg['passing_grade'],
                (int)$cfg['urutan'],
                $sub
            ]);
        }
        $updateMsg = 'Konfigurasi berhasil diperbarui.';
        try {
            $subtesConfig = $pdo->query("SELECT * FROM subtes_config ORDER BY nama")->fetchAll();
        } catch (Exception $e) {
            $subtesConfig = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Dashboard Admin — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%;transition:background .2s,color .2s}
:root{--bg-body:#f5f7fa;--bg-card:#fff;--text-main:#222;--text-muted:#555;--header-bg:#1a5276;--border-color:#eee;--link-color:#2980b9;--success-bg:#d4edda;--danger-bg:#f8d7da;--warning-bg:#fff3cd;--nav-bg:#eaf2f8}
[data-theme="dark"]{--bg-body:#1a1a2e;--bg-card:#16213e;--text-main:#f0f0f0;--text-muted:#b0b0b0;--header-bg:#0f3460;--border-color:#555;--link-color:#74b9ff;--success-bg:#1e3a2f;--danger-bg:#3a1e2f;--warning-bg:#3a3010;--nav-bg:#1a5276}
body{background:var(--bg-body);color:var(--text-main)}
.header{background:var(--header-bg);color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:1200px;margin:1.5rem auto;padding:0 1rem}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-bottom:1.5rem}
.stat{background:var(--bg-card);border-radius:8px;padding:1rem;box-shadow:0 2px 6px rgba(0,0,0,.08);text-align:center}
.stat .num{font-size:1.7rem;font-weight:bold;color:#2980b9}
.stat .label{color:#555;font-size:.85rem;margin-top:.3rem}
.section{background:var(--bg-card);border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;overflow:hidden}
.section h2{color:#1a5276;font-size:1.05rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:500px}
th,td{border:1px solid var(--border-color);padding:.4rem .5rem;text-align:left}
th{background:var(--bg-body);color:var(--text-muted)}
tr:hover{background:#f8f9fa}
.badge{display:inline-block;padding:.25rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold}
.badge.selesai{background:var(--success-bg);color:#27ae60}
.badge.berjalan{background:var(--warning-bg);color:#f39c12}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.45rem .7rem;border-radius:5px;text-decoration:none;font-size:.85rem;margin-right:.3rem;min-height:36px;min-width:44px}
.btn.danger{background:#e74c3c}
.btn.success{background:#27ae60}
.nav-tabs{display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;overflow-x:auto;-webkit-overflow-scrolling:touch}
.nav-tabs a{padding:.45rem .8rem;background:var(--nav-bg);color:var(--link-color);text-decoration:none;border-radius:5px;font-size:.85rem;font-weight:600;white-space:nowrap;min-height:36px;display:flex;align-items:center}
.nav-tabs a:hover,.nav-tabs a.active{background:#2980b9;color:#fff}
#soalForm{display:none}
.form-group{margin-bottom:.8rem}
.form-group label{display:block;font-size:.85rem;color:#555;margin-bottom:.3rem;font-weight:600}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:.5rem;border:1px solid var(--border-color);border-radius:5px;font-size:.9rem;background:var(--bg-card);color:var(--text-main)}
.form-group textarea{min-height:60px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:600px){
.grid-2{grid-template-columns:1fr}
.stats{grid-template-columns:repeat(2,1fr)}
.section{padding:1rem}
}
.skip-link:focus{top:0}
.theme-toggle{background:transparent;border:1px solid rgba(255,255,255,.4);color:#fff;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:.8rem;margin-right:.3rem;min-height:44px;min-width:44px}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Dashboard Admin — SKD CAT-BKN'; $activePage = 'admin_dashboard'; $showThemeToggle = true; $showNotifications = true; ?>
<?php require '../includes/nav_admin.php'; ?>

<div class="container" id="main-content">
<div class="stats">
<div class="stat"><div class="num"><?= $stats['total_soal'] ?></div><div class="label">Total Soal</div></div>
<div class="stat"><div class="num"><?= $stats['total_users'] ?></div><div class="label">Peserta</div></div>
<div class="stat"><div class="num"><?= $stats['total_tryout'] ?></div><div class="label">Tryout Dibuat</div></div>
<div class="stat"><div class="num"><?= $stats['tryout_selesai'] ?></div><div class="label">Tryout Selesai</div></div>
<div class="stat"><div class="num"><?= $soalPerSubtes['TWK'] ?? 0 ?></div><div class="label">Soal TWK</div></div>
<div class="stat"><div class="num"><?= $soalPerSubtes['TIU'] ?? 0 ?></div><div class="label">Soal TIU</div></div>
<div class="stat"><div class="num"><?= $soalPerSubtes['TKP'] ?? 0 ?></div><div class="label">Soal TKP</div></div>
</div>

<div class="nav-tabs">
<a href="#analytics" onclick="showTab('analytics')" id="tab-analytics">Analytics</a>
<a href="#feedback" onclick="showTab('feedback')" id="tab-feedback">Feedback</a>
<a href="#moderation" onclick="showTab('moderation')" id="tab-moderation">Moderasi</a>
<a href="#revision" onclick="showTab('revision')" id="tab-revision">Revision Queue</a>
<a href="#users" onclick="showTab('users')" id="tab-users" class="active">Peserta</a>
<a href="#tryouts" onclick="showTab('tryouts')" id="tab-tryouts">Riwayat Tryout</a>
<a href="#events" onclick="showTab('events')" id="tab-events">Event Tryout</a>
<a href="#soal" onclick="showTab('soal')" id="tab-soal">Kelola Soal</a>
<a href="#materi" onclick="showTab('materi')" id="tab-materi">Kelola Materi</a>
<a href="#tips" onclick="showTab('tips')" id="tab-tips">Kelola Tips</a>
<a href="#media" onclick="showTab('media')" id="tab-media">Media Library</a>
<a href="#reports" onclick="showTab('reports')" id="tab-reports">Reports</a>
<a href="#generator" onclick="showTab('generator')" id="tab-generator">Generator Massal</a>
<a href="#config" onclick="showTab('config')" id="tab-config">Konfigurasi</a>
</div>

<div id="panel-analytics" class="section" style="display:none">
<h2>Analytics Dashboard</h2>

<!-- Tryout Completion Rate -->
<div style="margin-bottom:1.5rem">
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Tryout Completion Rate</h3>
<div style="display:flex;gap:1rem;flex-wrap:wrap">
<div style="flex:1;min-width:200px;background:#f8f9fa;padding:1rem;border-radius:6px">
<div style="font-size:.85rem;color:#666">Total Tryout</div>
<div style="font-size:1.5rem;font-weight:bold;color:#2980b9"><?= $tryoutStats['total'] ?? 0 ?></div>
</div>
<div style="flex:1;min-width:200px;background:#f8f9fa;padding:1rem;border-radius:6px">
<div style="font-size:.85rem;color:#666">Selesai</div>
<div style="font-size:1.5rem;font-weight:bold;color:#27ae60"><?= $tryoutStats['completed'] ?? 0 ?></div>
</div>
<div style="flex:1;min-width:200px;background:#f8f9fa;padding:1rem;border-radius:6px">
<div style="font-size:.85rem;color:#666">Completion Rate</div>
<div style="font-size:1.5rem;font-weight:bold;color:#8e44ad"><?= $tryoutStats['total'] > 0 ? round(($tryoutStats['completed'] / $tryoutStats['total']) * 100, 1) : 0 ?>%</div>
</div>
<div style="flex:1;min-width:200px;background:#f8f9fa;padding:1rem;border-radius:6px">
<div style="font-size:.85rem;color:#666">Rata-rata Skor</div>
<div style="font-size:1.5rem;font-weight:bold;color:#e67e22"><?= round($tryoutStats['avg_score'] ?? 0, 1) ?></div>
</div>
</div>
</div>

<!-- Average Scores by Subtes -->
<div style="margin-bottom:1.5rem">
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Rata-rata Skor per Subtes</h3>
<div style="display:flex;gap:1rem;flex-wrap:wrap">
<div style="flex:1;min-width:150px;background:#d4edda;padding:1rem;border-radius:6px;border-left:4px solid #27ae60">
<div style="font-size:.85rem;color:#155724">TKP</div>
<div style="font-size:1.3rem;font-weight:bold;color:#155724"><?= round($avgScores['avg_tkp'] ?? 0, 1) ?></div>
</div>
<div style="flex:1;min-width:150px;background:#fff3cd;padding:1rem;border-radius:6px;border-left:4px solid #f39c12">
<div style="font-size:.85rem;color:#856404">TIU</div>
<div style="font-size:1.3rem;font-weight:bold;color:#856404"><?= round($avgScores['avg_tiu'] ?? 0, 1) ?></div>
</div>
<div style="flex:1;min-width:150px;background:#d1ecf1;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
<div style="font-size:.85rem;color:#0c5460">TWK</div>
<div style="font-size:1.3rem;font-weight:bold;color:#0c5460"><?= round($avgScores['avg_twk'] ?? 0, 1) ?></div>
</div>
</div>
</div>

<!-- User Registration Trend -->
<div style="margin-bottom:1.5rem">
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Trend Pendaftaran Peserta (30 Hari Terakhir)</h3>
<div style="background:#f8f9fa;padding:1rem;border-radius:6px">
<?php if (empty($userTrend)): ?>
<p style="color:#777;font-size:.85rem">Tidak ada data pendaftaran dalam 30 hari terakhir.</p>
<?php else: ?>
<div style="display:flex;gap:.3rem;align-items:flex-end;height:100px;flex-wrap:wrap">
<?php 
$maxCount = max(array_column($userTrend, 'count'));
foreach ($userTrend as $t): 
$height = $maxCount > 0 ? ($t['count'] / $maxCount) * 100 : 0;
?>
<div style="flex:1;min-width:25px;background:#2980b9;height:<?= $height ?>%;border-radius:3px 3px 0 0;position:relative" title="<?= $t['date'] ?>: <?= $t['count'] ?> user">
</div>
<?php endforeach; ?>
</div>
<div style="margin-top:.5rem;font-size:.75rem;color:#666">
Total pendaftaran 30 hari terakhir: <?= array_sum(array_column($userTrend, 'count')) ?> user
</div>
<?php endif; ?>
</div>
</div>

<!-- Activity Heatmap -->
<div style="margin-bottom:1.5rem">
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Heatmap Aktivitas User (30 Hari Terakhir)</h3>
<div style="background:#f8f9fa;padding:1rem;border-radius:6px">
<?php if (empty($activityHeatmap)): ?>
<p style="color:#777;font-size:.85rem">Tidak ada data aktivitas dalam 30 hari terakhir.</p>
<?php else: ?>
<div style="display:flex;gap:.3rem;align-items:flex-end;height:100px;flex-wrap:wrap">
<?php 
$maxActive = max(array_column($activityHeatmap, 'active_users'));
foreach ($activityHeatmap as $a): 
$height = $maxActive > 0 ? ($a['active_users'] / $maxActive) * 100 : 0;
$color = $a['active_users'] > 10 ? '#216e39' : ($a['active_users'] > 5 ? '#30a14e' : ($a['active_users'] > 2 ? '#40c463' : '#9be9a8'));
?>
<div style="flex:1;min-width:25px;background:<?= $color ?>;height:<?= $height ?>%;border-radius:3px 3px 0 0;position:relative" title="<?= $a['date'] ?>: <?= $a['active_users'] ?> user aktif">
</div>
<?php endforeach; ?>
</div>
<div style="margin-top:.5rem;font-size:.75rem;color:#666">
User aktif rata-rata per hari: <?= round(array_sum(array_column($activityHeatmap, 'active_users')) / count($activityHeatmap), 1) ?>
</div>
<?php endif; ?>
</div>
</div>

<!-- Top Materials -->
<div style="margin-bottom:1.5rem">
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Top Materi yang Diakses (30 Hari Terakhir)</h3>
<div style="background:#f8f9fa;padding:1rem;border-radius:6px">
<?php if (empty($topMaterials)): ?>
<p style="color:#777;font-size:.85rem">Tidak ada data akses materi.</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:.5rem">
<?php foreach ($topMaterials as $m): ?>
<div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem;background:#fff;border-radius:4px">
<div style="font-size:.85rem;color:#333"><?= e($m['judul']) ?></div>
<div style="font-size:.85rem;font-weight:bold;color:#2980b9"><?= $m['access_count'] ?> akses</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>

<!-- Top Wrong Questions -->
<div>
<h3 style="color:#555;font-size:.95rem;margin-bottom:.5rem">Top Soal yang Sering Salah Dijawab</h3>
<div style="background:#f8f9fa;padding:1rem;border-radius:6px">
<?php if (empty($topWrongQuestions)): ?>
<p style="color:#777;font-size:.85rem">Tidak ada data jawaban salah.</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:.5rem">
<?php foreach ($topWrongQuestions as $q): ?>
<div style="padding:.5rem;background:#fff;border-radius:4px;border-left:3px solid #e74c3c">
<div style="font-size:.75rem;color:#666;margin-bottom:.2rem">[<?= $q['subtes'] ?>] <?= e($q['topik']) ?></div>
<div style="font-size:.85rem;color:#333;margin-bottom:.3rem"><?= e(substr($q['pertanyaan'], 0, 100)) ?>...</div>
<div style="font-size:.75rem;font-weight:bold;color:#e74c3c"><?= $q['wrong_count'] ?>x salah dijawab</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>

<div id="panel-feedback" class="section" style="display:none">
<h2>Manajemen Feedback User</h2>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
<select id="filterStatus" onchange="loadFeedback()" style="padding:.5rem;border:1px solid #ddd;border-radius:5px;font-size:.85rem">
<option value="">Semua Status</option>
<option value="pending">Pending</option>
<option value="dilihat">Dilihat</option>
<option value="diproses">Diproses</option>
<option value="selesai">Selesai</option>
<option value="ditolak">Ditolak</option>
</select>
<select id="filterCategory" onchange="loadFeedback()" style="padding:.5rem;border:1px solid #ddd;border-radius:5px;font-size:.85rem">
<option value="">Semua Kategori</option>
<option value="saran">Saran</option>
<option value="kritik">Kritik</option>
<option value="bug">Bug</option>
<option value="fitur">Fitur</option>
<option value="lainnya">Lainnya</option>
</select>
<button onclick="loadFeedback()" class="btn" style="padding:.5rem .8rem">🔄 Refresh</button>
</div>

<!-- Feedback List -->
<div id="feedbackList">
<p style="color:#666">Memuat feedback...</p>
</div>
</div>

<div id="panel-moderation" class="section" style="display:none">
<h2>Manajemen Moderasi Konten</h2>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
<select id="moderationStatus" onchange="loadModerationQueue()" style="padding:.5rem;border:1px solid #ddd;border-radius:5px;font-size:.85rem">
<option value="pending">Pending</option>
<option value="approved">Approved</option>
<option value="rejected">Rejected</option>
<option value="deleted">Deleted</option>
</select>
<button onclick="loadModerationQueue()" class="btn" style="padding:.5rem .8rem">🔄 Refresh</button>
</div>

<!-- Moderation Queue -->
<div id="moderationQueue">
<p style="color:#666">Memuat queue...</p>
</div>
</div>

<div id="panel-revision" class="section" style="display:none">
<h2>Revision Queue</h2>

<!-- Stats -->
<div id="revisionStats" style="margin-bottom:1rem;display:flex;gap:1rem;flex-wrap:wrap">
    <div style="background:#f8f9fa;padding:.5rem 1rem;border-radius:4px;border:1px solid #ddd">
        <span style="font-weight:bold">Total: </span><span id="statTotal">0</span>
    </div>
    <div style="background:#fff3cd;padding:.5rem 1rem;border-radius:4px;border:1px solid #f1c40f">
        <span style="font-weight:bold">Pending: </span><span id="statPending">0</span>
    </div>
    <div style="background:#d1ecf1;padding:.5rem 1rem;border-radius:4px;border:1px solid #bee5eb">
        <span style="font-weight:bold">Assigned: </span><span id="statAssigned">0</span>
    </div>
    <div style="background:#d4edda;padding:.5rem 1rem;border-radius:4px;border:1px solid #c3e6cb">
        <span style="font-weight:bold">Completed: </span><span id="statCompleted">0</span>
    </div>
</div>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <select id="filterRevisionStatus" onchange="loadRevisionQueue()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Status</option>
        <option value="pending">Pending</option>
        <option value="assigned">Assigned</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
    </select>
    <select id="filterRevisionPriority" onchange="loadRevisionQueue()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Priority</option>
        <option value="urgent">Urgent</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
    </select>
    <button onclick="loadRevisionQueue()" class="btn" style="font-size:.85rem">🔄 Refresh</button>
    <button onclick="detectRevisionCandidates()" class="btn" style="font-size:.85rem;background:#8e44ad">🔍 Auto-Detect</button>
    <button onclick="addAllCandidates()" class="btn success" style="font-size:.85rem">+ Add All Candidates</button>
</div>

<!-- Auto-Detect Results (hidden by default) -->
<div id="autoDetectResults" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Revision Candidates Detected</h3>
    <div id="candidatesList"></div>
</div>

<!-- Revision Queue List -->
<div id="revisionQueueList">
    <p style="color:#666">Memuat revision queue...</p>
</div>
</div>

<div id="panel-users" class="section">
<h2>Daftar Peserta</h2>
<p style="font-size:.85rem;color:#666;margin-bottom:.5rem">Total: <?= $usersTotal ?> peserta</p>

<!-- Bulk Actions -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <button onclick="bulkAction('suspend')" class="btn" style="background:#e67e22">⚠️ Suspend Terpilih</button>
    <button onclick="bulkAction('activate')" class="btn" style="background:#27ae60">✅ Aktifkan Terpilih</button>
</div>

<div class="table-wrap">
<table>
<thead><tr><th><input type="checkbox" id="selectAllUsers" onchange="toggleAllUsers()"></th><th>ID</th><th>Nama</th><th>No. HP</th><th>Sekolah</th><th>Instansi</th><th>Status</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach ($users as $u): 
$statusBadge = '';
if ($u['status'] === 'active') $statusBadge = '<span class="badge" style="background:#d4edda;color:#155724">Active</span>';
elseif ($u['status'] === 'suspended') $statusBadge = '<span class="badge" style="background:#fff3cd;color:#856404">Suspended</span>';
elseif ($u['status'] === 'banned') $statusBadge = '<span class="badge" style="background:#f8d7da;color:#721c24">Banned</span>';
?>
<tr>
<td><input type="checkbox" class="userCheckbox" value="<?= $u['id'] ?>"></td>
<td><?= $u['id'] ?></td>
<td><?= e($u['nama']) ?></td>
<td><?= e($u['no_hp'] ?? '-') ?></td>
<td><?= e($u['sekolah_asal'] ?? '-') ?><?= $u['tahun_tamat'] ? ' (' . $u['tahun_tamat'] . ')' : '' ?></td>
<td><?= e($u['instansi'] ?? '-') ?></td>
<td><?= $statusBadge ?></td>
<td><?= $u['created_at'] ?></td>
<td>
    <button onclick="editUser(<?= $u['id'] ?>)" style="background:#2980b9;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem;margin-right:.2rem">Edit</button>
    <button onclick="viewActivity(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')" style="background:#8e44ad;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem;margin-right:.2rem">Activity</button>
    <button onclick="resetUserPassword(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')" style="background:#e74c3c;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem;margin-right:.2rem">Reset Pwd</button>
    <button onclick="manageUserStatus(<?= $u['id'] ?>, '<?= e($u['nama']) ?>', '<?= $u['status'] ?>')" style="background:#f39c12;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem">Status</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if ($usersTotalPages > 1): ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;font-size:.85rem">
<div>
Halaman <?= $usersCurrentPage ?> dari <?= $usersTotalPages ?>
</div>
<div style="display:flex;gap:.3rem">
<?php if ($usersHasPrev): ?>
<a href="?users_offset=<?= max(0, $usersOffset - $usersLimit) ?>&users_limit=<?= $usersLimit ?>" class="btn" style="font-size:.8rem">← Sebelumnya</a>
<?php endif; ?>
<?php if ($usersHasNext): ?>
<a href="?users_offset=<?= $usersOffset + $usersLimit ?>&users_limit=<?= $usersLimit ?>" class="btn" style="font-size:.8rem">Selanjutnya →</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
</div>

<div id="panel-tryouts" class="section" style="display:none">
<h2>Riwayat Tryout</h2>
<p style="font-size:.85rem;color:#666;margin-bottom:.5rem">Total: <?= $tryoutsTotal ?> tryout</p>
<a href="/api/export_csv.php?type=tryouts" class="btn success" style="margin-bottom:1rem">Export CSV</a>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Nama</th><th>Peserta</th><th>Total Nilai</th><th>Status</th><th>Waktu Mulai</th></tr></thead>
<tbody>
<?php foreach ($tryouts as $t): ?>
<tr>
<td><?= $t['id'] ?></td>
<td><?= e($t['nama']) ?></td>
<td><?= e($t['peserta'] ?? 'Anonim') ?></td>
<td><?= $t['total_nilai'] ?? 0 ?></td>
<td><span class="badge <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
<td><?= $t['waktu_mulai'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if ($tryoutsTotalPages > 1): ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;font-size:.85rem">
<div>
Halaman <?= $tryoutsCurrentPage ?> dari <?= $tryoutsTotalPages ?>
</div>
<div style="display:flex;gap:.3rem">
<?php if ($tryoutsHasPrev): ?>
<a href="?tryouts_offset=<?= max(0, $tryoutsOffset - $tryoutsLimit) ?>&tryouts_limit=<?= $tryoutsLimit ?>" class="btn" style="font-size:.8rem">← Sebelumnya</a>
<?php endif; ?>
<?php if ($tryoutsHasNext): ?>
<a href="?tryouts_offset=<?= $tryoutsOffset + $tryoutsLimit ?>&tryouts_limit=<?= $tryoutsLimit ?>" class="btn" style="font-size:.8rem">Selanjutnya →</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
</div>

<div id="panel-events" class="section" style="display:none">
<h2>Manajemen Event Tryout</h2>

<!-- Create Event Button -->
<div style="margin-bottom:1rem">
    <button onclick="showCreateEventForm()" class="btn success">+ Buat Event Baru</button>
</div>

<!-- Create Event Form (hidden by default) -->
<div id="createEventForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Buat Event Baru</h3>
    <form onsubmit="createEvent(event)">
        <div class="form-group">
            <label>Nama Event</label>
            <input type="text" id="eventNama" required>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea id="eventDeskripsi"></textarea>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Tanggal Mulai</label>
                <input type="datetime-local" id="eventTanggalMulai" required>
            </div>
            <div class="form-group">
                <label>Tanggal Selesai</label>
                <input type="datetime-local" id="eventTanggalSelesai" required>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Paket Soal</label>
                <select id="eventPaketSoal">
                    <option value="">Default</option>
                    <?php foreach ($tryoutPackages as $pkg): ?>
                    <option value="<?= $pkg['id'] ?>"><?= e($pkg['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Max Peserta (0 = unlimited)</label>
                <input type="number" id="eventMaxParticipants" value="0" min="0">
            </div>
        </div>
        <div class="form-group">
            <label>Passing Grade Custom (0 = default)</label>
            <input type="number" id="eventPassingGrade" value="0" min="0">
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Simpan Event</button>
            <button type="button" class="btn" onclick="hideCreateEventForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
</div>

<!-- Events List -->
<div id="eventsList">
    <p style="color:#666">Memuat events...</p>
</div>
</div>

<div id="panel-soal" class="section" style="display:none">
<h2>Kelola Soal</h2>
<p>Total soal: <?= $stats['total_soal'] ?> (TWK: <?= $soalPerSubtes['TWK'] ?? 0 ?>, TIU: <?= $soalPerSubtes['TIU'] ?? 0 ?>, TKP: <?= $soalPerSubtes['TKP'] ?? 0 ?>)</p>
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <button onclick="showAddSoalForm()" class="btn success">+ Tambah Soal</button>
    <button onclick="showBulkImportForm()" class="btn" style="background:#8e44ad">📥 Bulk Import</button>
    <a href="/api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=5" target="_blank" class="btn">+ Generate Soal (Smart)</a>
</div>

<!-- Add Soal Form (hidden by default) -->
<div id="addSoalForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Tambah Soal Baru</h3>
    <form onsubmit="addSoal(event)">
        <div class="grid-2">
            <div class="form-group">
                <label>Subtes</label>
                <select id="addSubtes" required>
                    <option value="TWK">TWK</option>
                    <option value="TIU">TIU</option>
                    <option value="TKP">TKP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <input type="text" id="addTipe" placeholder="e.g., numerik, verbal, figural">
            </div>
        </div>
        <div class="form-group">
            <label>Topik</label>
            <input type="text" id="addTopik" placeholder="e.g., Deret Angka, Nasionalisme">
        </div>
        <div class="form-group">
            <label>Pertanyaan</label>
            <textarea id="addPertanyaan" required></textarea>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Pilihan A</label><input type="text" id="addA"></div>
            <div class="form-group"><label>Pilihan B</label><input type="text" id="addB"></div>
            <div class="form-group"><label>Pilihan C</label><input type="text" id="addC"></div>
            <div class="form-group"><label>Pilihan D</label><input type="text" id="addD"></div>
            <div class="form-group"><label>Pilihan E</label><input type="text" id="addE"></div>
            <div class="form-group"><label>Jawaban Benar</label>
                <select id="addKey" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Pembahasan</label>
            <textarea id="addPembahasan"></textarea>
        </div>
        <div class="form-group">
            <label>Tips</label>
            <textarea id="addTips"></textarea>
        </div>
        <div class="form-group">
            <label>Related Links</label>
            <input type="text" id="addRelatedLinks" placeholder="URLs separated by comma">
        </div>
        <div class="form-group">
            <label>Materi</label>
            <input type="text" id="addMateri" placeholder="Materi references">
        </div>
        <div class="form-group">
            <label>Bobot TKP</label>
            <input type="number" id="addBobotTkp" value="0" min="0" max="5">
        </div>
        <div class="form-group">
            <label>Tags</label>
            <div id="addTagsContainer" style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.5rem"></div>
            <input type="text" id="addTagInput" placeholder="Ketik tag dan tekan Enter" style="width:100%">
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Simpan Soal</button>
            <button type="button" class="btn" onclick="hideAddSoalForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
</div>

<!-- Bulk Import Form (hidden by default) -->
<div id="bulkImportForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Bulk Import Soal (CSV)</h3>
    <div style="margin-bottom:1rem">
        <a href="#" onclick="downloadTemplate()" style="color:#2980b9;text-decoration:underline;font-size:.85rem">Download Template CSV</a>
    </div>
    <form onsubmit="bulkImportSoal(event)">
        <div class="form-group">
            <label>Upload CSV File</label>
            <input type="file" id="csvFile" accept=".csv" required>
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Import</button>
            <button type="button" class="btn" onclick="hideBulkImportForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
    <div id="importResult" style="margin-top:1rem"></div>
</div>

<!-- Upload Gambar -->
<div style="background:#f0f7ff;border:1px solid #b8d4f0;border-radius:6px;padding:.8rem;margin-bottom:1rem">
<h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.5rem">Upload Gambar Soal</h3>
<form id="uploadForm" enctype="multipart/form-data">
    <input type="file" name="gambar" id="gambarInput" accept="image/*" style="margin-bottom:.5rem">
    <button type="button" class="btn" onclick="uploadGambar()" style="font-size:.85rem" aria-label="Upload gambar soal">Upload</button>
    <div id="uploadResult" style="margin-top:.5rem;font-size:.85rem"></div>
</form>
</div>

<!-- Soal Perlu Revisi -->
<div style="background:#fff3cd;border:1px solid #f1c40f;border-radius:6px;padding:.8rem;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#856404;margin-bottom:.3rem">Soal Perlu Revisi</h3>
    <p style="font-size:.85rem;color:#856404;margin-bottom:.5rem">Soal yang ditandai peserta dengan "M" (ragu-ragu) perlu ditinjau ulang.</p>
    <button class="btn" style="background:#e67e22;font-size:.85rem" onclick="loadRevisionList()" aria-label="Tampilkan soal yang perlu revisi">Tampilkan Soal Perlu Revisi</button>
</div>

<!-- Cari Soal -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <input type="text" id="searchSoal" placeholder="Cari soal..." style="padding:.4rem;border:1px solid #ddd;border-radius:5px;flex:1;min-width:200px">
    <select id="filterSubtes" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Subtes</option>
        <option value="TWK">TWK</option>
        <option value="TIU">TIU</option>
        <option value="TKP">TKP</option>
    </select>
    <select id="filterTag" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Tag</option>
    </select>
    <button class="btn" onclick="loadSoalList()" style="font-size:.85rem" aria-label="Cari soal">Cari</button>
</div>

<!-- Daftar Soal -->
<div id="soalList" style="max-height:500px;overflow-y:auto">
    <p style="color:#666;font-size:.9rem">Klik "Cari" untuk memuat daftar soal.</p>
</div>
</div>

<div id="panel-materi" class="section" style="display:none">
<h2>Kelola Materi</h2>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <select id="filterMateriSubtes" onchange="loadMateriList()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Subtes</option>
        <option value="TWK">TWK</option>
        <option value="TIU">TIU</option>
        <option value="TKP">TKP</option>
    </select>
    <button onclick="loadMateriList()" class="btn" style="font-size:.85rem">🔄 Refresh</button>
    <button onclick="showAddMateriForm()" class="btn success">+ Tambah Materi</button>
</div>

<!-- Add Materi Form (hidden by default) -->
<div id="addMateriForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Tambah Materi Baru</h3>
    <form onsubmit="addMateri(event)">
        <div class="grid-2">
            <div class="form-group">
                <label>Subtes</label>
                <select id="addMateriSubtes" required>
                    <option value="TWK">TWK</option>
                    <option value="TIU">TIU</option>
                    <option value="TKP">TKP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <input type="text" id="addMateriTipe" placeholder="e.g., numerik, verbal">
            </div>
        </div>
        <div class="form-group">
            <label>Judul</label>
            <input type="text" id="addMateriJudul" required>
        </div>
        <div class="form-group">
            <label>Konten (HTML allowed)</label>
            <textarea id="addMateriKonten" required style="min-height:150px"></textarea>
        </div>
        <div class="form-group">
            <label>URL (optional)</label>
            <input type="text" id="addMateriUrl" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Urutan</label>
            <input type="number" id="addMateriUrutan" value="0" min="0">
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Simpan Materi</button>
            <button type="button" class="btn" onclick="hideAddMateriForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
</div>

<!-- Materi List -->
<div id="materiList">
    <p style="color:#666">Memuat materi...</p>
</div>
</div>

<div id="panel-tips" class="section" style="display:none">
<h2>Kelola Tips</h2>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <select id="filterTipsSubtes" onchange="loadTipsList()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Subtes</option>
        <option value="TWK">TWK</option>
        <option value="TIU">TIU</option>
        <option value="TKP">TKP</option>
    </select>
    <button onclick="loadTipsList()" class="btn" style="font-size:.85rem">🔄 Refresh</button>
    <button onclick="showAddTipsForm()" class="btn success">+ Tambah Tips</button>
</div>

<!-- Add Tips Form (hidden by default) -->
<div id="addTipsForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Tambah Tips Baru</h3>
    <form onsubmit="addTips(event)">
        <div class="grid-2">
            <div class="form-group">
                <label>Subtes</label>
                <select id="addTipsSubtes" required>
                    <option value="TWK">TWK</option>
                    <option value="TIU">TIU</option>
                    <option value="TKP">TKP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Topik</label>
                <input type="text" id="addTipsTopik" placeholder="e.g., Deret Angka, Nasionalisme">
            </div>
        </div>
        <div class="form-group">
            <label>Trik (singkat)</label>
            <input type="text" id="addTipsTrik" required placeholder="e.g., 25x4=100">
        </div>
        <div class="form-group">
            <label>Akronim (optional)</label>
            <input type="text" id="addTipsAkronim" placeholder="e.g., KPK, KKN">
        </div>
        <div class="form-group">
            <label>Langkah-langkah</label>
            <textarea id="addTipsLangkah" style="min-height:100px"></textarea>
        </div>
        <div class="form-group">
            <label>Contoh Soal</label>
            <textarea id="addTipsContohSoal" style="min-height:100px"></textarea>
        </div>
        <div class="form-group">
            <label>Penjelasan</label>
            <textarea id="addTipsPenjelasan" style="min-height:80px"></textarea>
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Simpan Tips</button>
            <button type="button" class="btn" onclick="hideAddTipsForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
</div>

<!-- Tips List -->
<div id="tipsList">
    <p style="color:#666">Memuat tips...</p>
</div>
</div>

<div id="panel-media" class="section" style="display:none">
<h2>Media Library</h2>

<!-- Filters -->
<div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
    <select id="filterMediaType" onchange="loadMediaLibrary()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Tipe</option>
        <option value="image">Gambar</option>
        <option value="video">Video</option>
        <option value="document">Dokumen</option>
    </select>
    <select id="filterMediaFolder" onchange="loadMediaLibrary()" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
        <option value="">Semua Folder</option>
    </select>
    <input type="text" id="searchMedia" placeholder="Cari file..." style="padding:.4rem;border:1px solid #ddd;border-radius:5px;flex:1;min-width:150px">
    <button onclick="loadMediaLibrary()" class="btn" style="font-size:.85rem">🔍 Cari</button>
    <button onclick="showUploadForm()" class="btn success">+ Upload</button>
</div>

<!-- Upload Form (hidden by default) -->
<div id="uploadMediaForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Upload Media</h3>
    <form onsubmit="uploadMedia(event)">
        <div class="form-group">
            <label>File</label>
            <input type="file" id="mediaFile" accept="image/*,video/*,.pdf" required>
        </div>
        <div class="form-group">
            <label>Folder</label>
            <input type="text" id="mediaFolder" placeholder="general" value="general">
        </div>
        <div style="display:flex;gap:.5rem;margin-top:1rem">
            <button type="submit" class="btn success">Upload</button>
            <button type="button" class="btn" onclick="hideUploadForm()" style="background:#7f8c8d">Batal</button>
        </div>
    </form>
</div>

<!-- Media Grid -->
<div id="mediaGrid">
    <p style="color:#666">Memuat media...</p>
</div>
</div>

<div id="panel-reports" class="section" style="display:none">
<h2>Admin Reports</h2>

<!-- Generate Report -->
<div style="margin-bottom:1.5rem;background:#f8f9fa;padding:1rem;border-radius:6px">
    <h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.8rem">Generate New Report</h3>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <select id="reportType" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
            <option value="user_activity">User Activity</option>
            <option value="tryout_results">Tryout Results</option>
            <option value="content_performance">Content Performance</option>
            <option value="revenue">Revenue</option>
        </select>
        <button onclick="generateReport()" class="btn success">Generate CSV</button>
    </div>
</div>

<!-- Report List -->
<div style="margin-bottom:1.5rem">
    <h3 style="font-size:.95rem;color:#555;margin-bottom:.8rem">Generated Reports</h3>
    <div id="reportsList">
        <p style="color:#666">Memuat reports...</p>
    </div>
</div>

<!-- Report Schedules -->
<div>
    <h3 style="font-size:.95rem;color:#555;margin-bottom:.8rem">Scheduled Reports</h3>
    <div style="margin-bottom:1rem">
        <button onclick="showScheduleForm()" class="btn">+ Add Schedule</button>
    </div>
    
    <!-- Schedule Form (hidden by default) -->
    <div id="scheduleForm" style="display:none;background:#f8f9fa;padding:1rem;border-radius:6px;margin-bottom:1rem">
        <h4 style="font-size:.9rem;color:#1a5276;margin-bottom:.8rem">Create Schedule</h4>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <select id="scheduleReportType" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
                <option value="user_activity">User Activity</option>
                <option value="tryout_results">Tryout Results</option>
                <option value="content_performance">Content Performance</option>
                <option value="revenue">Revenue</option>
            </select>
            <input type="text" id="scheduleTitle" placeholder="Schedule Title" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
            <select id="scheduleType" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
            <input type="time" id="scheduleTime" value="00:00" style="padding:.4rem;border:1px solid #ddd;border-radius:5px">
            <button onclick="createSchedule()" class="btn success">Create</button>
            <button onclick="hideScheduleForm()" class="btn" style="background:#7f8c8d">Cancel</button>
        </div>
    </div>
    
    <div id="schedulesList">
        <p style="color:#666">Memuat schedules...</p>
    </div>
</div>
</div>

<!-- Modal Edit Soal -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;overflow-y:auto">
    <div style="background:#fff;max-width:600px;margin:2rem auto;padding:1.5rem;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.2)">
        <h3 style="color:#1a5276;margin-bottom:1rem">Edit Soal</h3>
        <input type="hidden" id="editId">
        <div class="form-group">
            <label>Pertanyaan</label>
            <textarea id="editPertanyaan"></textarea>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Pilihan A</label><input type="text" id="editA"></div>
            <div class="form-group"><label>Pilihan B</label><input type="text" id="editB"></div>
            <div class="form-group"><label>Pilihan C</label><input type="text" id="editC"></div>
            <div class="form-group"><label>Pilihan D</label><input type="text" id="editD"></div>
            <div class="form-group"><label>Pilihan E</label><input type="text" id="editE"></div>
            <div class="form-group"><label>Jawaban Benar</label>
                <select id="editKey"><option>A</option><option>B</option><option>C</option><option>D</option><option>E</option></select>
            </div>
        </div>
        <div class="form-group">
            <label>Gambar Soal</label>
            <div id="editCurrentImg" style="margin-bottom:.5rem"></div>
            <input type="file" id="editGambar" accept="image/*" style="margin-bottom:.3rem">
            <div style="font-size:.8rem;color:#666">Kosongkan jika tidak ingin mengubah gambar</div>
        </div>
        <div class="form-group">
            <label>Pembahasan</label>
            <textarea id="editPembahasan" style="min-height:80px"></textarea>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button class="btn" style="background:#7f8c8d" onclick="closeEditModal()" aria-label="Batal edit soal">Batal</button>
            <button class="btn success" onclick="saveSoalEdit()" aria-label="Simpan perubahan soal">Simpan</button>
        </div>
    </div>
</div>
</div>

<div id="panel-generator" class="section" style="display:none">
<h2>Generator Massal Soal</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">Generate soal otomatis dengan pembahasan, tips & trick, link belajar, dan materi terstruktur.</p>

<div style="background:#f0f7ff;border:1px solid #b8d4f0;border-radius:6px;padding:1rem;margin-bottom:1rem">
    <div class="grid-2">
        <div class="form-group">
            <label>Subtes</label>
            <select id="genSubtes" onchange="updateGenTopik()">
                <option value="TIU">TIU</option>
                <option value="TWK">TWK</option>
                <option value="TKP">TKP</option>
            </select>
        </div>
        <div class="form-group">
            <label>Topik</label>
            <select id="genTopik">
                <option value="Deret Angka">Deret Angka</option>
                <option value="Berhitung">Berhitung</option>
                <option value="Perbandingan">Perbandingan</option>
                <option value="Soal Cerita">Soal Cerita</option>
            </select>
        </div>
        <div class="form-group">
            <label>Jumlah Soal</label>
            <input type="number" id="genJumlah" value="10" min="1" max="100">
        </div>
        <div class="form-group">
            <label>Kesulitan</label>
            <select id="genKesulitan">
                <option value="mudah">Mudah</option>
                <option value="sedang" selected>Sedang</option>
                <option value="sulit">Sulit</option>
            </select>
        </div>
    </div>
    <div style="margin-top:1rem">
        <button class="btn success" onclick="runGenerator()" id="genBtn" aria-label="Generate soal massal">Generate Soal</button>
    </div>
</div>

<div id="genResult" style="display:none;background:#fff;border:1px solid #ddd;border-radius:6px;padding:1rem">
    <h3 style="font-size:1rem;color:#1a5276;margin-bottom:.5rem">Hasil Generate</h3>
    <div id="genStats" style="font-size:.9rem;margin-bottom:.5rem"></div>
    <div id="genLog" style="max-height:300px;overflow-y:auto;font-size:.85rem;font-family:monospace;background:#f8f9fa;padding:.5rem;border-radius:4px"></div>
</div>
</div>

<div id="panel-config" class="section" style="display:none">
<h2>Konfigurasi Try Out</h2>
<?php if ($updateMsg): ?><div style="background:#d4edda;color:#155724;padding:.8rem;border-radius:5px;margin-bottom:1rem;font-size:.9rem"><?= e($updateMsg) ?></div><?php endif; ?>
<form method="POST" action="">
<input type="hidden" name="action" value="update_config">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="table-wrap">
<table>
<thead><tr><th>Subtes</th><th>Durasi (menit)</th><th>Jumlah Soal</th><th>Passing Grade</th><th>Urutan</th></tr></thead>
<tbody>
<?php foreach ($subtesConfig as $c): ?>
<tr>
<td><strong><?= $c['subtes'] ?></strong></td>
<td><input type="number" name="config[<?= $c['subtes'] ?>][durasi_menit]" value="<?= (int)$c['durasi_menit'] ?>" style="width:80px;padding:.3rem;border:1px solid #ddd;border-radius:4px"></td>
<td><input type="number" name="config[<?= $c['subtes'] ?>][jumlah_soal]" value="<?= (int)$c['jumlah_soal'] ?>" style="width:80px;padding:.3rem;border:1px solid #ddd;border-radius:4px"></td>
<td><input type="number" name="config[<?= $c['subtes'] ?>][passing_grade]" value="<?= (int)$c['passing_grade'] ?>" style="width:80px;padding:.3rem;border:1px solid #ddd;border-radius:4px"></td>
<td><input type="number" name="config[<?= $c['subtes'] ?>][urutan]" value="<?= (int)$c['urutan'] ?>" style="width:60px;padding:.3rem;border:1px solid #ddd;border-radius:4px"></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<button type="submit" class="btn" style="margin-top:1rem" aria-label="Simpan konfigurasi subtes">Simpan Konfigurasi</button>
</form>
</div>

</div>

<div class="footer">
Dashboard Admin SKD CAT-BKN | Selamat datang, <?= $adminName ?>
</div>

<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/js/dist/admin_dashboard.js"></script>
<script>
const BASE_URL = '<?php echo $baseUrl ?? '/permen'; ?>';
// Admin-specific functions that need to remain inline
// These functions are specific to the admin dashboard and are not in the external file

// --- REVISION QUEUE ---
async function loadRevisionQueue() {
    const status = document.getElementById('filterRevisionStatus').value;
    const priority = document.getElementById('filterRevisionPriority').value;
    
    try {
        let url = BASE_URL + '/api/admin_revision_queue.php?action=get_queue';
        if (status) url += '&status=' + encodeURIComponent(status);
        if (priority) url += '&priority=' + encodeURIComponent(priority);
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            renderRevisionQueue(data.queue);
        } else {
            document.getElementById('revisionQueueList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat queue') + '</p>';
        }
    } catch(e) {
        document.getElementById('revisionQueueList').innerHTML = '<p style="color:#e74c3c">Gagal memuat queue</p>';
    }
    
    loadRevisionStats();
}

async function loadRevisionStats() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_revision_queue.php?action=get_revision_stats');
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('statTotal').textContent = data.stats.total;
            document.getElementById('statPending').textContent = data.stats.pending;
            document.getElementById('statAssigned').textContent = data.stats.assigned;
            document.getElementById('statCompleted').textContent = data.stats.completed;
        }
    } catch(e) {
        console.error('Error loading stats:', e);
    }
}

function renderRevisionQueue(queue) {
    if (queue.length === 0) {
        document.getElementById('revisionQueueList').innerHTML = '<p style="color:#777">Tidak ada item dalam revision queue.</p>';
        return;
    }
    
    const statusLabels = {
        'pending': 'Pending',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    
    const statusColors = {
        'pending': '#fff3cd',
        'assigned': '#d1ecf1',
        'in_progress': '#e2e3e5',
        'completed': '#d4edda',
        'cancelled': '#f8d7da'
    };
    
    const priorityColors = {
        'urgent': '#dc3545',
        'high': '#fd7e14',
        'medium': '#ffc107',
        'low': '#28a745'
    };
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    queue.forEach(item => {
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid ${priorityColors[item.priority] || '#6c757d'}">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <div>
                    <span style="font-weight:bold;color:#333">Soal ID: ${item.soal_id}</span>
                    <span style="margin:0 .5rem;color:#777">•</span>
                    <span style="font-size:.85rem;color:#666">[${item.subtes}] ${item.tipe || '-'}</span>
                </div>
                <div>
                    <span style="background:${priorityColors[item.priority] || '#6c757d'};color:#fff;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold">${item.priority.toUpperCase()}</span>
                    <span style="margin-left:.5rem;background:${statusColors[item.status]};color:#333;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold">${statusLabels[item.status]}</span>
                </div>
            </div>
            <div style="font-size:.85rem;color:#555;margin-bottom:.5rem;max-height:60px;overflow:hidden">${escapeHtml(item.pertanyaan.substring(0, 150))}...</div>
            ${item.reason ? `<div style="font-size:.85rem;color:#666;margin-bottom:.5rem"><strong>Reason:</strong> ${escapeHtml(item.reason)}</div>` : ''}
            <div style="font-size:.75rem;color:#777;margin-bottom:.5rem">
                Created: ${new Date(item.created_at).toLocaleString('id-ID')}
                ${item.assigned_to ? ` • Assigned to: ${escapeHtml(item.assigned_to_name)}` : ''}
            </div>
            ${item.admin_notes ? `
            <div style="margin-top:.5rem;padding:.5rem;background:#e8f5e9;border-radius:4px">
                <div style="font-size:.75rem;color:#155724;font-weight:bold;margin-bottom:.2rem">📢 Admin Notes:</div>
                <div style="font-size:.85rem;color:#333;white-space:pre-wrap">${escapeHtml(item.admin_notes)}</div>
            </div>
            ` : ''}
            <div style="margin-top:.8rem;display:flex;gap:.3rem;flex-wrap:wrap">
                <select id="status-${item.id}" onchange="updateRevisionStatus(${item.id})" style="padding:.3rem;border:1px solid #ddd;border-radius:4px;font-size:.8rem">
                    <option value="pending" ${item.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="assigned" ${item.status === 'assigned' ? 'selected' : ''}>Assigned</option>
                    <option value="in_progress" ${item.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="completed" ${item.status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
                <select id="priority-${item.id}" onchange="updateRevisionPriority(${item.id})" style="padding:.3rem;border:1px solid #ddd;border-radius:4px;font-size:.8rem">
                    <option value="low" ${item.priority === 'low' ? 'selected' : ''}>Low</option>
                    <option value="medium" ${item.priority === 'medium' ? 'selected' : ''}>Medium</option>
                    <option value="high" ${item.priority === 'high' ? 'selected' : ''}>High</option>
                    <option value="urgent" ${item.priority === 'urgent' ? 'selected' : ''}>Urgent</option>
                </select>
                <button onclick="addRevisionNote(${item.id})" class="btn" style="font-size:.8rem;padding:.3rem .5rem">Add Note</button>
                <button onclick="removeFromQueue(${item.id})" class="btn danger" style="font-size:.8rem;padding:.3rem .5rem">Remove</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('revisionQueueList').innerHTML = html;
}
</script>
<script>
async function updateRevisionStatus(queueId) {
    const status = document.getElementById('status-' + queueId).value;
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('queue_id', queueId);
    formData.append('status', status);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_revision_queue.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadRevisionQueue();
        } else {
            alert(data.error || 'Gagal update status');
        }
    } catch(e) {
        alert('Gagal update status');
    }
}

async function updateRevisionPriority(queueId) {
    const priority = document.getElementById('priority-' + queueId).value;
    
    const formData = new FormData();
    formData.append('action', 'update_priority');
    formData.append('queue_id', queueId);
    formData.append('priority', priority);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_revision_queue.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadRevisionQueue();
        } else {
            alert(data.error || 'Gagal update priority');
        }
    } catch(e) {
        alert('Gagal update priority');
    }
}

function addRevisionNote(queueId) {
    const note = prompt('Masukkan admin note:');
    if (!note) return;
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('queue_id', queueId);
    formData.append('status', document.getElementById('status-' + queueId).value);
    formData.append('admin_notes', note);
    
    fetch(BASE_URL + '/api/admin_revision_queue.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            loadRevisionQueue();
        } else {
            alert(data.error || 'Gagal add note');
        }
    }).catch(() => {
        alert('Gagal add note');
    });
}

async function removeFromQueue(queueId) {
    if (!confirm('Yakin ingin menghapus dari queue?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove_from_queue');
    formData.append('queue_id', queueId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_revision_queue.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadRevisionQueue();
        } else {
            alert(data.error || 'Gagal remove from queue');
        }
    } catch(e) {
        alert('Gagal remove from queue');
    }
}

async function detectRevisionCandidates() {
    try {
        const res = await fetch(BASE_URL + '/api/auto_detect_revision.php?action=detect_revision_candidates');
        const data = await res.json();
        
        if (data.success) {
            if (data.candidates.length === 0) {
                alert('Tidak ada kandidat revisi terdeteksi');
                return;
            }
            
            renderCandidates(data.candidates);
            document.getElementById('autoDetectResults').style.display = 'block';
        } else {
            alert(data.error || 'Gagal mendeteksi kandidat');
        }
    } catch(e) {
        alert('Gagal mendeteksi kandidat');
    }
}

function renderCandidates(candidates) {
    const priorityColors = {
        'urgent': '#dc3545',
        'high': '#fd7e14',
        'medium': '#ffc107',
        'low': '#28a745'
    };
    
    let html = `<p style="font-size:.85rem;color:#666;margin-bottom:.5rem">${candidates.length} kandidat terdeteksi:</p>`;
    html += '<div style="display:flex;flex-direction:column;gap:.5rem">';
    candidates.forEach(c => {
        html += `
        <div style="background:#fff;padding:.5rem;border-radius:4px;border:1px solid #ddd">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
                <span style="font-weight:bold;font-size:.85rem">Soal ID: ${c.soal_id}</span>
                <span style="background:${priorityColors[c.priority]};color:#fff;padding:.1rem .3rem;border-radius:10px;font-size:.7rem">${c.priority.toUpperCase()}</span>
            </div>
            <div style="font-size:.8rem;color:#555;margin-bottom:.3rem">${escapeHtml(c.pertanyaan.substring(0, 80))}...</div>
            <div style="font-size:.75rem;color:#666;margin-bottom:.3rem">${escapeHtml(c.reason)}</div>
            <button onclick="addCandidateToQueue(${c.soal_id}, '${c.priority}', '${escapeHtml(c.reason).replace(/'/g, "\\'")}')" class="btn success" style="font-size:.75rem;padding:.2rem .4rem">Add to Queue</button>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('candidatesList').innerHTML = html;
}

async function addCandidateToQueue(soalId, priority, reason) {
    try {
        const res = await fetch(`/api/auto_detect_revision.php?action=add_candidate_to_queue&soal_id=${soalId}&priority=${priority}&reason=${encodeURIComponent(reason)}`);
        const data = await res.json();
        
        if (data.success) {
            alert('Soal ditambahkan ke revision queue');
            loadRevisionQueue();
        } else {
            alert(data.error || 'Gagal menambah ke queue');
        }
    } catch(e) {
        alert('Gagal menambah ke queue');
    }
}

async function addAllCandidates() {
    if (!confirm('Tambahkan semua kandidat terdeteksi ke revision queue?')) return;
    
    try {
        const res = await fetch(BASE_URL + '/api/auto_detect_revision.php?action=add_all_candidates');
        const data = await res.json();
        
        if (data.success) {
            alert(data.message);
            loadRevisionQueue();
            document.getElementById('autoDetectResults').style.display = 'none';
        } else {
            alert(data.error || 'Gagal menambah semua kandidat');
        }
    } catch(e) {
        alert('Gagal menambah semua kandidat');
    }
}

// --- ADMIN REPORTS ---
async function loadReports() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php?action=get_reports');
        const data = await res.json();
        
        if (data.success) {
            renderReportsList(data.reports);
        } else {
            document.getElementById('reportsList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat reports') + '</p>';
        }
    } catch(e) {
        document.getElementById('reportsList').innerHTML = '<p style="color:#e74c3c">Gagal memuat reports</p>';
    }
    
    loadSchedules();
}

function renderReportsList(reports) {
    if (reports.length === 0) {
        document.getElementById('reportsList').innerHTML = '<p style="color:#777">Belum ada report yang digenerate.</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:.5rem">';
    reports.forEach(r => {
        html += `
        <div style="background:#f8f9fa;padding:.8rem;border-radius:6px;border:1px solid #ddd">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
                <span style="font-weight:bold;font-size:.85rem">${escapeHtml(r.title)}</span>
                <span style="font-size:.75rem;color:#666">${new Date(r.generated_at).toLocaleString('id-ID')}</span>
            </div>
            <div style="font-size:.8rem;color:#555;margin-bottom:.5rem">Type: ${escapeHtml(r.report_type)} • Generated by: ${escapeHtml(r.generated_by_name || 'System')}</div>
            <a href="${escapeHtml(r.file_path)}" download class="btn" style="font-size:.8rem;padding:.2rem .4rem">Download CSV</a>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('reportsList').innerHTML = html;
}

async function generateReport() {
    const reportType = document.getElementById('reportType').value;
    
    const formData = new FormData();
    formData.append('action', 'generate_report');
    formData.append('report_type', reportType);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Report berhasil digenerate');
            loadReports();
        } else {
            alert(data.error || 'Gagal generate report');
        }
    } catch(e) {
        alert('Gagal generate report');
    }
}

async function loadSchedules() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php?action=get_schedules');
        const data = await res.json();
        
        if (data.success) {
            renderSchedulesList(data.schedules);
        } else {
            document.getElementById('schedulesList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat schedules') + '</p>';
        }
    } catch(e) {
        document.getElementById('schedulesList').innerHTML = '<p style="color:#e74c3c">Gagal memuat schedules</p>';
    }
}

function renderSchedulesList(schedules) {
    if (schedules.length === 0) {
        document.getElementById('schedulesList').innerHTML = '<p style="color:#777">Belum ada schedule.</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:.5rem">';
    schedules.forEach(s => {
        const statusBadge = s.is_active ? '<span style="background:#d4edda;color:#155724;padding:.1rem .3rem;border-radius:3px">Active</span>' : '<span style="background:#f8d7da;color:#721c24;padding:.1rem .3rem;border-radius:3px">Inactive</span>';
        html += `
        <div style="background:#f8f9fa;padding:.8rem;border-radius:6px;border:1px solid #ddd">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
                <span style="font-weight:bold;font-size:.85rem">${escapeHtml(s.title)}</span>
                ${statusBadge}
            </div>
            <div style="font-size:.8rem;color:#555;margin-bottom:.5rem">
                Type: ${escapeHtml(s.report_type)} • ${escapeHtml(s.schedule_type)} at ${escapeHtml(s.schedule_time)}
            </div>
            <div style="font-size:.75rem;color:#666;margin-bottom:.5rem">
                Next run: ${s.next_run_at ? new Date(s.next_run_at).toLocaleString('id-ID') : 'N/A'}
            </div>
            <div style="display:flex;gap:.3rem">
                <button onclick="toggleSchedule(${s.id}, ${s.is_active ? 0 : 1})" class="btn" style="font-size:.8rem;padding:.2rem .4rem">${s.is_active ? 'Deactivate' : 'Activate'}</button>
                <button onclick="deleteSchedule(${s.id})" class="btn danger" style="font-size:.8rem;padding:.2rem .4rem">Delete</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('schedulesList').innerHTML = html;
}

function showScheduleForm() {
    document.getElementById('scheduleForm').style.display = 'block';
}

function hideScheduleForm() {
    document.getElementById('scheduleForm').style.display = 'none';
}

async function createSchedule() {
    const reportType = document.getElementById('scheduleReportType').value;
    const title = document.getElementById('scheduleTitle').value;
    const scheduleType = document.getElementById('scheduleType').value;
    const scheduleTime = document.getElementById('scheduleTime').value;
    
    if (!title) {
        alert('Masukkan title');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_schedule');
    formData.append('report_type', reportType);
    formData.append('title', title);
    formData.append('schedule_type', scheduleType);
    formData.append('schedule_time', scheduleTime);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Schedule berhasil dibuat');
            hideScheduleForm();
            loadSchedules();
        } else {
            alert(data.error || 'Gagal create schedule');
        }
    } catch(e) {
        alert('Gagal create schedule');
    }
}

async function toggleSchedule(scheduleId, isActive) {
    const formData = new FormData();
    formData.append('action', 'toggle_schedule');
    formData.append('schedule_id', scheduleId);
    formData.append('is_active', isActive);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadSchedules();
        } else {
            alert(data.error || 'Gagal toggle schedule');
        }
    } catch(e) {
        alert('Gagal toggle schedule');
    }
}

async function deleteSchedule(scheduleId) {
    if (!confirm('Yakin ingin menghapus schedule ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('schedule_id', scheduleId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_reports.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadSchedules();
        } else {
            alert(data.error || 'Gagal delete schedule');
        }
    } catch(e) {
        alert('Gagal delete schedule');
    }
}

// --- FEEDBACK MANAGEMENT ---
async function loadFeedback(){
    const status = document.getElementById('filterStatus').value;
    const category = document.getElementById('filterCategory').value;
    
    const params = new URLSearchParams();
    if(status) params.append('status', status);
    if(category) params.append('category', category);
    
    try {
        const res = await fetch(BASE_URL + '/api/get_feedback.php?' + params.toString());
        const data = await res.json();
        
        if(data.success){
            renderFeedbackList(data.feedback);
        } else {
            document.getElementById('feedbackList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat feedback') + '</p>';
        }
    } catch(e){
        document.getElementById('feedbackList').innerHTML = '<p style="color:#e74c3c">Gagal memuat feedback</p>';
    }
}

// --- CONTENT MODERATION ---
async function loadModerationQueue() {
    const status = document.getElementById('moderationStatus').value;
    
    try {
        const res = await fetch(`/api/admin_content_moderation.php?action=get_moderation_queue&status=${status}`);
        const data = await res.json();
        
        if (data.success) {
            renderModerationQueue(data.queue);
        } else {
            document.getElementById('moderationQueue').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat queue') + '</p>';
        }
    } catch(e) {
        document.getElementById('moderationQueue').innerHTML = '<p style="color:#e74c3c">Gagal memuat queue</p>';
    }
}

function renderModerationQueue(queue) {
    if (queue.length === 0) {
        document.getElementById('moderationQueue').innerHTML = '<p style="color:#777">Tidak ada konten dalam queue.</p>';
        return;
    }
    
    const statusLabels = {
        'pending': 'Pending',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'deleted': 'Deleted'
    };
    
    const statusColors = {
        'pending': '#fff3cd',
        'approved': '#d4edda',
        'rejected': '#f8d7da',
        'deleted': '#e2e3e5'
    };
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    queue.forEach(item => {
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                <div>
                    <span style="font-weight:bold;color:#1a5276">[${item.content_type.toUpperCase()}] ID: ${item.content_id}</span>
                    <span style="margin:0 .5rem;color:#777">•</span>
                    <span style="font-size:.85rem;color:#666">Reporter: ${item.reporter_name || 'Auto-flagged'}</span>
                </div>
                <span style="background:${statusColors[item.status]};color:#333;padding:.25rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold">${statusLabels[item.status]}</span>
            </div>
            <p style="color:#333;font-size:.9rem;margin-bottom:.5rem;white-space:pre-wrap">${escapeHtml(item.content_preview || 'Content not found')}</p>
            ${item.reason ? `<div style="font-size:.85rem;color:#666;margin-bottom:.5rem"><strong>Alasan:</strong> ${escapeHtml(item.reason)}</div>` : ''}
            <div style="font-size:.75rem;color:#777;margin-bottom:.5rem">
                Flagged: ${new Date(item.created_at).toLocaleString('id-ID')}
                ${item.reviewed_at ? ` • Reviewed: ${new Date(item.reviewed_at).toLocaleString('id-ID')} by ${item.moderator_name || 'Admin'}` : ''}
            </div>
            ${item.moderator_note ? `
            <div style="margin-top:.5rem;padding:.5rem;background:#e8f5e9;border-radius:4px">
                <div style="font-size:.75rem;color:#155724;font-weight:bold;margin-bottom:.2rem">📢 Moderator Note:</div>
                <div style="font-size:.85rem;color:#333;white-space:pre-wrap">${escapeHtml(item.moderator_note)}</div>
            </div>
            ` : ''}
            ${item.status === 'pending' ? `
            <div style="margin-top:.8rem;display:flex;gap:.5rem;flex-wrap:wrap">
                <textarea id="note-${item.id}" placeholder="Moderator note..." style="flex:1;min-height:40px;padding:.4rem;border:1px solid #ddd;border-radius:4px;font-size:.8rem;resize:vertical"></textarea>
                <button onclick="moderateContent(${item.id}, 'approve')" class="btn success" style="padding:.4rem .8rem;font-size:.8rem">Approve</button>
                <button onclick="moderateContent(${item.id}, 'reject')" class="btn" style="background:#e74c3c;padding:.4rem .8rem;font-size:.8rem">Reject</button>
                <button onclick="moderateContent(${item.id}, 'delete')" class="btn danger" style="padding:.4rem .8rem;font-size:.8rem">Delete</button>
            </div>
            ` : ''}
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('moderationQueue').innerHTML = html;
}

async function moderateContent(moderationId, action) {
    const note = document.getElementById('note-' + moderationId).value;
    
    const formData = new FormData();
    formData.append('action', action + '_content');
    formData.append('moderation_id', moderationId);
    formData.append('note', note);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_content_moderation.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Content berhasil ' + action);
            loadModerationQueue();
        } else {
            alert(data.error || 'Gagal ' + action + ' content');
        }
    } catch(e) {
        alert('Gagal ' + action + ' content');
    }
}

// --- FEEDBACK MANAGEMENT (continued) ---

function renderFeedbackList(feedback){
    if(feedback.length === 0){
        document.getElementById('feedbackList').innerHTML = '<p style="color:#777">Tidak ada feedback.</p>';
        return;
    }
    
    const statusLabels = {
        'pending': 'Pending',
        'dilihat': 'Dilihat',
        'diproses': 'Diproses',
        'selesai': 'Selesai',
        'ditolak': 'Ditolak'
    };
    
    const statusColors = {
        'pending': '#fff3cd',
        'dilihat': '#d1ecf1',
        'diproses': '#fff3cd',
        'selesai': '#d4edda',
        'ditolak': '#f8d7da'
    };
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    feedback.forEach(f => {
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                <div>
                    <span style="font-weight:bold;color:#1a5276">${escapeHtml(f.user_name || 'User #' + f.user_id)}</span>
                    <span style="margin:0 .5rem;color:#777">•</span>
                    <span style="font-size:.85rem;color:#666">${f.category.toUpperCase()}</span>
                </div>
                <span style="background:${statusColors[f.status]};color:#333;padding:.25rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold">${statusLabels[f.status]}</span>
            </div>
            <p style="color:#333;font-size:.9rem;margin-bottom:.5rem;white-space:pre-wrap">${escapeHtml(f.message)}</p>
            <div style="font-size:.75rem;color:#777;margin-bottom:.5rem">
                ${new Date(f.created_at).toLocaleString('id-ID')}
                ${f.updated_at !== f.created_at ? ' • Updated: ' + new Date(f.updated_at).toLocaleString('id-ID') : ''}
            </div>
            ${f.admin_response ? `
            <div style="margin-top:.5rem;padding:.5rem;background:#e8f5e9;border-radius:4px">
                <div style="font-size:.75rem;color:#155724;font-weight:bold;margin-bottom:.2rem">📢 Admin Response:</div>
                <div style="font-size:.85rem;color:#333;white-space:pre-wrap">${escapeHtml(f.admin_response)}</div>
            </div>
            ` : ''}
            <div style="margin-top:.8rem;display:flex;gap:.5rem;flex-wrap:wrap">
                <select id="status-${f.id}" style="padding:.4rem;border:1px solid #ddd;border-radius:4px;font-size:.8rem">
                    <option value="pending" ${f.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="dilihat" ${f.status === 'dilihat' ? 'selected' : ''}>Dilihat</option>
                    <option value="diproses" ${f.status === 'diproses' ? 'selected' : ''}>Diproses</option>
                    <option value="selesai" ${f.status === 'selesai' ? 'selected' : ''}>Selesai</option>
                    <option value="ditolak" ${f.status === 'ditolak' ? 'selected' : ''}>Ditolak</option>
                </select>
                <textarea id="response-${f.id}" placeholder="Admin response..." style="flex:1;min-height:40px;padding:.4rem;border:1px solid #ddd;border-radius:4px;font-size:.8rem;resize:vertical">${f.admin_response || ''}</textarea>
                <button onclick="updateFeedback(${f.id})" class="btn" style="padding:.4rem .8rem;font-size:.8rem">Update</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('feedbackList').innerHTML = html;
}

async function updateFeedback(feedbackId){
    const status = document.getElementById('status-' + feedbackId).value;
    const response = document.getElementById('response-' + feedbackId).value;
    
    const formData = new FormData();
    formData.append('feedback_id', feedbackId);
    formData.append('status', status);
    formData.append('response', response);
    
    try {
        const res = await fetch(BASE_URL + '/api/update_feedback.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if(data.success){
            alert('Feedback berhasil diupdate');
            loadFeedback();
        } else {
            alert(data.error || 'Gagal mengupdate feedback');
        }
    } catch(e){
        alert('Gagal mengupdate feedback');
    }
}

function escapeHtml(text){
    if(!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// --- SOAL CRUD ---
let selectedTags = [];

async function loadTags() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_soal_crud.php?action=get_all_tags');
        const data = await res.json();
        
        if (data.success) {
            const tagSelect = document.getElementById('filterTag');
            tagSelect.innerHTML = '<option value="">Semua Tag</option>';
            data.tags.forEach(tag => {
                tagSelect.innerHTML += `<option value="${tag.tag_name}">${tag.tag_name}</option>`;
            });
        }
    } catch(e) {
        console.error('Error loading tags:', e);
    }
}

function showAddSoalForm() {
    document.getElementById('addSoalForm').style.display = 'block';
    selectedTags = [];
    renderSelectedTags();
}

function hideAddSoalForm() {
    document.getElementById('addSoalForm').style.display = 'none';
}

function renderSelectedTags() {
    const container = document.getElementById('addTagsContainer');
    container.innerHTML = selectedTags.map(tag => 
        `<span style="background:#2980b9;color:#fff;padding:.2rem .5rem;border-radius:12px;font-size:.75rem;cursor:pointer" onclick="removeTag('${tag}')">${tag} ×</span>`
    ).join('');
}

function addTag(tag) {
    if (tag && !selectedTags.includes(tag)) {
        selectedTags.push(tag);
        renderSelectedTags();
    }
}

function removeTag(tag) {
    selectedTags = selectedTags.filter(t => t !== tag);
    renderSelectedTags();
}

document.addEventListener('DOMContentLoaded', function() {
    const tagInput = document.getElementById('addTagInput');
    if (tagInput) {
        tagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag(this.value.trim());
                this.value = '';
            }
        });
    }
});

async function addSoal(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_soal');
    formData.append('subtes', document.getElementById('addSubtes').value);
    formData.append('tipe', document.getElementById('addTipe').value);
    formData.append('topik', document.getElementById('addTopik').value);
    formData.append('pertanyaan', document.getElementById('addPertanyaan').value);
    formData.append('pilihan_a', document.getElementById('addA').value);
    formData.append('pilihan_b', document.getElementById('addB').value);
    formData.append('pilihan_c', document.getElementById('addC').value);
    formData.append('pilihan_d', document.getElementById('addD').value);
    formData.append('pilihan_e', document.getElementById('addE').value);
    formData.append('jawaban_benar', document.getElementById('addKey').value);
    formData.append('pembahasan', document.getElementById('addPembahasan').value);
    formData.append('tips', document.getElementById('addTips').value);
    formData.append('related_links', document.getElementById('addRelatedLinks').value);
    formData.append('materi', document.getElementById('addMateri').value);
    formData.append('bobot_tkp', document.getElementById('addBobotTkp').value);
    selectedTags.forEach(tag => formData.append('tags[]', tag));
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_soal_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Soal berhasil ditambahkan');
            hideAddSoalForm();
            loadSoalList();
        } else {
            alert(data.error || 'Gagal menambah soal');
        }
    } catch(e) {
        alert('Gagal menambah soal');
    }
}

function showBulkImportForm() {
    document.getElementById('bulkImportForm').style.display = 'block';
}

function hideBulkImportForm() {
    document.getElementById('bulkImportForm').style.display = 'none';
}

function downloadTemplate() {
    const template = 'subtes,tipe,topik,pertanyaan,pilihan_a,pilihan_b,pilihan_c,pilihan_d,pilihan_e,jawaban_benar,pembahasan\nTWK,numerik,Deret Angka,"Berapa hasil dari 2 + 2?","3","4","5","6","7","B","2+2=4"';
    const blob = new Blob([template], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'template_soal.csv';
    a.click();
}

async function bulkImportSoal(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('csvFile');
    if (!fileInput.files[0]) {
        alert('Pilih file CSV');
        return;
    }
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    reader.onload = async function(e) {
        const text = e.target.result;
        const lines = text.split('\n');
        
        let successCount = 0;
        let errorCount = 0;
        const errors = [];
        
        for (let i = 1; i < lines.length; i++) {
            if (!lines[i].trim()) continue;
            
            const values = lines[i].split(',');
            if (values.length < 10) {
                errorCount++;
                errors.push(`Baris ${i}: Kolom tidak lengkap`);
                continue;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_soal');
            formData.append('subtes', values[0]?.trim() || 'TWK');
            formData.append('tipe', values[1]?.trim() || '');
            formData.append('topik', values[2]?.trim() || '');
            formData.append('pertanyaan', values[3]?.replace(/"/g, '').trim() || '');
            formData.append('pilihan_a', values[4]?.replace(/"/g, '').trim() || '');
            formData.append('pilihan_b', values[5]?.replace(/"/g, '').trim() || '');
            formData.append('pilihan_c', values[6]?.replace(/"/g, '').trim() || '');
            formData.append('pilihan_d', values[7]?.replace(/"/g, '').trim() || '');
            formData.append('pilihan_e', values[8]?.replace(/"/g, '').trim() || '');
            formData.append('jawaban_benar', values[9]?.trim().toUpperCase() || 'A');
            formData.append('pembahasan', values[10]?.replace(/"/g, '').trim() || '');
            
            try {
                const res = await fetch(BASE_URL + '/api/admin_soal_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    successCount++;
                } else {
                    errorCount++;
                    errors.push(`Baris ${i}: ${data.error}`);
                }
            } catch(err) {
                errorCount++;
                errors.push(`Baris ${i}: Error processing`);
            }
        }
        
        const resultDiv = document.getElementById('importResult');
        resultDiv.innerHTML = `
            <div style="background:${successCount > 0 ? '#d4edda' : '#f8d7da'};padding:1rem;border-radius:4px;margin-top:1rem">
                <div style="font-weight:bold;color:${successCount > 0 ? '#155724' : '#721c24'}">
                    Selesai: ${successCount} berhasil, ${errorCount} gagal
                </div>
                ${errors.length > 0 ? `<div style="font-size:.85rem;color:#721c24;margin-top:.5rem;max-height:200px;overflow-y:auto">${errors.join('<br>')}</div>` : ''}
            </div>
        `;
        
        if (successCount > 0) {
            loadSoalList();
        }
    };
    
    reader.readAsText(file);
}

async function deleteSoal(soalId) {
    if (!confirm('Yakin ingin menghapus soal ini? (soft delete)')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_soal');
    formData.append('soal_id', soalId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_soal_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Soal berhasil dihapus');
            loadSoalList();
        } else {
            alert(data.error || 'Gagal menghapus soal');
        }
    } catch(e) {
        alert('Gagal menghapus soal');
    }
}

async function viewSoalVersions(soalId) {
    try {
        const res = await fetch(`/api/admin_soal_crud.php?action=get_soal_versions&soal_id=${soalId}`);
        const data = await res.json();
        
        if (data.success) {
            if (data.versions.length === 0) {
                alert('Tidak ada versi tersimpan');
                return;
            }
            
            let html = `Versi Soal:\n\n`;
            data.versions.forEach(v => {
                html += `Versi ${v.version} - ${new Date(v.edited_at).toLocaleString('id-ID')}\n`;
                html += `Editor: ${v.editor_name || 'Unknown'}\n`;
                html += `Pertanyaan: ${v.pertanyaan.substring(0, 50)}...\n\n`;
            });
            
            // Add diff option if there are at least 2 versions
            if (data.versions.length >= 2) {
                html += `\n---\n\nPilih 2 versi untuk melihat perbedaan:\n`;
                data.versions.forEach((v, i) => {
                    html += `${i + 1}. Versi ${v.version}\n`;
                });
                
                const choice = prompt(html + '\nMasukkan nomor versi pertama (pisahkan dengan koma untuk 2 versi):');
                if (choice) {
                    const indices = choice.split(',').map(n => parseInt(n.trim()) - 1);
                    if (indices.length === 2 && indices[0] >= 0 && indices[1] >= 0 && indices[0] < data.versions.length && indices[1] < data.versions.length) {
                        viewVersionDiff(soalId, data.versions[indices[0]].version, data.versions[indices[1]].version);
                    } else {
                        alert('Pilihan tidak valid');
                    }
                }
            } else {
                alert(html);
            }
        } else {
            alert(data.error || 'Gagal memuat versi');
        }
    } catch(e) {
        alert('Gagal memuat versi');
    }
}

async function viewVersionDiff(soalId, version1, version2) {
    try {
        const res = await fetch(`/api/admin_soal_crud.php?action=get_version_diff&soal_id=${soalId}&version1=${version1}&version2=${version2}`);
        const data = await res.json();
        
        if (data.success) {
            if (data.diff.length === 0) {
                alert('Tidak ada perbedaan antara versi ini');
                return;
            }
            
            let html = `Perbedaan Versi ${version1} vs ${version2}:\n\n`;
            data.diff.forEach(d => {
                html += `Field: ${d.field}\n`;
                html += `Lama: ${d.old?.substring(0, 100) || '(kosong)'}...\n`;
                html += `Baru: ${d.new?.substring(0, 100) || '(kosong)'}...\n\n`;
            });
            alert(html);
        } else {
            alert(data.error || 'Gagal memuat diff');
        }
    } catch(e) {
        alert('Gagal memuat diff');
    }
}

async function restoreVersion(soalId, version) {
    if (!confirm(`Restore ke versi ${version}?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'restore_version');
    formData.append('soal_id', soalId);
    formData.append('version', version);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_soal_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Versi berhasil direstore');
            loadSoalList();
        } else {
            alert(data.error || 'Gagal restore versi');
        }
    } catch(e) {
        alert('Gagal restore versi');
    }
}

// --- MATERI CRUD ---
async function loadMateriList() {
    const subtes = document.getElementById('filterMateriSubtes').value;
    
    try {
        let url = BASE_URL + '/api/admin_materi_crud.php?action=get_materi_list';
        if (subtes) url += '&subtes=' + encodeURIComponent(subtes);
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            renderMateriList(data.materi);
        } else {
            document.getElementById('materiList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat materi') + '</p>';
        }
    } catch(e) {
        document.getElementById('materiList').innerHTML = '<p style="color:#e74c3c">Gagal memuat materi</p>';
    }
}

function renderMateriList(materiList) {
    if (materiList.length === 0) {
        document.getElementById('materiList').innerHTML = '<p style="color:#777">Belum ada materi.</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    materiList.forEach(m => {
        const statusBadge = m.is_active ? '<span class="badge" style="background:#d4edda;color:#155724">Aktif</span>' : '<span class="badge" style="background:#f8d7da;color:#721c24">Non-aktif</span>';
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <div>
                    <span style="font-weight:bold;color:#1a5276">[${m.subtes}] ${escapeHtml(m.judul)}</span>
                    ${m.tipe ? `<span style="margin:0 .5rem;color:#777">•</span><span style="font-size:.85rem;color:#666">${escapeHtml(m.tipe)}</span>` : ''}
                </div>
                <div>
                    ${statusBadge}
                    <span style="margin-left:.5rem;font-size:.75rem;color:#666">Urutan: ${m.urutan}</span>
                </div>
            </div>
            <div style="font-size:.85rem;color:#555;margin-bottom:.5rem;max-height:80px;overflow:hidden">${escapeHtml(m.konten.substring(0, 200))}...</div>
            ${m.url ? `<div style="font-size:.75rem;color:#2980b9;margin-bottom:.5rem"><a href="${escapeHtml(m.url)}" target="_blank">${escapeHtml(m.url)}</a></div>` : ''}
            <div style="font-size:.75rem;color:#777">Dibuat: ${new Date(m.created_at).toLocaleString('id-ID')}</div>
            <div style="margin-top:.8rem;display:flex;gap:.3rem;flex-wrap:wrap">
                <button onclick="editMateri(${m.id})" class="btn" style="font-size:.8rem;padding:.3rem .5rem">Edit</button>
                <button onclick="moveMateri(${m.id}, -1)" class="btn" style="font-size:.8rem;padding:.3rem .5rem;background:#8e44ad">↑</button>
                <button onclick="moveMateri(${m.id}, 1)" class="btn" style="font-size:.8rem;padding:.3rem .5rem;background:#8e44ad">↓</button>
                <button onclick="deleteMateri(${m.id})" class="btn danger" style="font-size:.8rem;padding:.3rem .5rem">Hapus</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('materiList').innerHTML = html;
}

function showAddMateriForm() {
    document.getElementById('addMateriForm').style.display = 'block';
}

function hideAddMateriForm() {
    document.getElementById('addMateriForm').style.display = 'none';
}

async function addMateri(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_materi');
    formData.append('subtes', document.getElementById('addMateriSubtes').value);
    formData.append('tipe', document.getElementById('addMateriTipe').value);
    formData.append('judul', document.getElementById('addMateriJudul').value);
    formData.append('konten', document.getElementById('addMateriKonten').value);
    formData.append('url', document.getElementById('addMateriUrl').value);
    formData.append('urutan', document.getElementById('addMateriUrutan').value);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_materi_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Materi berhasil ditambahkan');
            hideAddMateriForm();
            loadMateriList();
        } else {
            alert(data.error || 'Gagal menambah materi');
        }
    } catch(e) {
        alert('Gagal menambah materi');
    }
}

async function editMateri(materiId) {
    try {
        const res = await fetch(`/api/admin_materi_crud.php?action=get_materi_detail&materi_id=${materiId}`);
        const data = await res.json();
        
        if (data.success) {
            const m = data.materi;
            document.getElementById('addMateriSubtes').value = m.subtes;
            document.getElementById('addMateriTipe').value = m.tipe || '';
            document.getElementById('addMateriJudul').value = m.judul;
            document.getElementById('addMateriKonten').value = m.konten;
            document.getElementById('addMateriUrl').value = m.url || '';
            document.getElementById('addMateriUrutan').value = m.urutan;
            
            // Change form to edit mode
            const form = document.querySelector('#addMateriForm form');
            form.onsubmit = function(e) {
                e.preventDefault();
                updateMateri(materiId);
            };
            
            document.querySelector('#addMateriForm h3').textContent = 'Edit Materi';
            document.querySelector('#addMateriForm button[type="submit"]').textContent = 'Update Materi';
            document.getElementById('addMateriForm').style.display = 'block';
        } else {
            alert(data.error || 'Gagal memuat materi');
        }
    } catch(e) {
        alert('Gagal memuat materi');
    }
}

async function updateMateri(materiId) {
    const formData = new FormData();
    formData.append('action', 'edit_materi');
    formData.append('materi_id', materiId);
    formData.append('subtes', document.getElementById('addMateriSubtes').value);
    formData.append('tipe', document.getElementById('addMateriTipe').value);
    formData.append('judul', document.getElementById('addMateriJudul').value);
    formData.append('konten', document.getElementById('addMateriKonten').value);
    formData.append('url', document.getElementById('addMateriUrl').value);
    formData.append('urutan', document.getElementById('addMateriUrutan').value);
    formData.append('is_active', '1');
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_materi_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Materi berhasil diupdate');
            hideAddMateriForm();
            resetMateriForm();
            loadMateriList();
        } else {
            alert(data.error || 'Gagal update materi');
        }
    } catch(e) {
        alert('Gagal update materi');
    }
}

function resetMateriForm() {
    const form = document.querySelector('#addMateriForm form');
    form.reset();
    form.onsubmit = function(e) {
        e.preventDefault();
        addMateri(e);
    };
    document.querySelector('#addMateriForm h3').textContent = 'Tambah Materi Baru';
    document.querySelector('#addMateriForm button[type="submit"]').textContent = 'Simpan Materi';
}

async function deleteMateri(materiId) {
    if (!confirm('Yakin ingin menghapus materi ini? (soft delete)')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_materi');
    formData.append('materi_id', materiId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_materi_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Materi berhasil dihapus');
            loadMateriList();
        } else {
            alert(data.error || 'Gagal menghapus materi');
        }
    } catch(e) {
        alert('Gagal menghapus materi');
    }
}

async function moveMateri(materiId, direction) {
    try {
        const res = await fetch(BASE_URL + '/api/admin_materi_crud.php?action=get_materi_list');
        const data = await res.json();
        
        if (data.success) {
            const materiList = data.materi;
            const currentIndex = materiList.findIndex(m => m.id === materiId);
            
            if (currentIndex === -1) return;
            
            const newIndex = currentIndex + direction;
            if (newIndex < 0 || newIndex >= materiList.length) return;
            
            // Swap urutan
            const orders = materiList.map((m, i) => ({
                id: m.id,
                urutan: m.urutan
            }));
            
            orders[currentIndex].urutan = materiList[newIndex].urutan;
            orders[newIndex].urutan = materiList[currentIndex].urutan;
            
            const formData = new FormData();
            formData.append('action', 'reorder_materi');
            orders.forEach((order, i) => {
                formData.append(`orders[${i}][id]`, order.id);
                formData.append(`orders[${i}][urutan]`, order.urutan);
            });
            
            const reorderRes = await fetch(BASE_URL + '/api/admin_materi_crud.php', {
                method: 'POST',
                body: formData
            });
            const reorderData = await reorderRes.json();
            
            if (reorderData.success) {
                loadMateriList();
            } else {
                alert(reorderData.error || 'Gagal mengubah urutan');
            }
        }
    } catch(e) {
        alert('Gagal mengubah urutan');
    }
}

// --- TIPS CRUD ---
async function loadTipsList() {
    const subtes = document.getElementById('filterTipsSubtes').value;
    
    try {
        let url = BASE_URL + '/api/admin_tips_crud.php?action=get_tips_list';
        if (subtes) url += '&subtes=' + encodeURIComponent(subtes);
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            renderTipsList(data.tips);
        } else {
            document.getElementById('tipsList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat tips') + '</p>';
        }
    } catch(e) {
        document.getElementById('tipsList').innerHTML = '<p style="color:#e74c3c">Gagal memuat tips</p>';
    }
}

function renderTipsList(tipsList) {
    if (tipsList.length === 0) {
        document.getElementById('tipsList').innerHTML = '<p style="color:#777">Belum ada tips.</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    tipsList.forEach(t => {
        const statusBadge = t.aktif ? '<span class="badge" style="background:#d4edda;color:#155724">Aktif</span>' : '<span class="badge" style="background:#f8d7da;color:#721c24">Non-aktif</span>';
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #8e44ad">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <div>
                    <span style="font-weight:bold;color:#6c3483">[${t.subtes}] ${escapeHtml(t.trik)}</span>
                    ${t.akronim ? `<span style="margin:0 .5rem;color:#777">•</span><span style="font-size:.85rem;color:#666;italic">${escapeHtml(t.akronim)}</span>` : ''}
                </div>
                ${statusBadge}
            </div>
            ${t.langkah ? `<div style="font-size:.85rem;color:#555;margin-bottom:.5rem"><strong>Langkah:</strong> ${escapeHtml(t.langkah.substring(0, 150))}...</div>` : ''}
            ${t.contoh_soal ? `<div style="font-size:.85rem;color:#555;margin-bottom:.5rem"><strong>Contoh:</strong> ${escapeHtml(t.contoh_soal.substring(0, 100))}...</div>` : ''}
            ${t.penjelasan ? `<div style="font-size:.85rem;color:#555;margin-bottom:.5rem"><strong>Penjelasan:</strong> ${escapeHtml(t.penjelasan.substring(0, 100))}...</div>` : ''}
            <div style="margin-top:.8rem;display:flex;gap:.3rem;flex-wrap:wrap">
                <button onclick="editTips(${t.id})" class="btn" style="font-size:.8rem;padding:.3rem .5rem">Edit</button>
                <button onclick="deleteTips(${t.id})" class="btn danger" style="font-size:.8rem;padding:.3rem .5rem">Hapus</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('tipsList').innerHTML = html;
}

function showAddTipsForm() {
    document.getElementById('addTipsForm').style.display = 'block';
}

function hideAddTipsForm() {
    document.getElementById('addTipsForm').style.display = 'none';
}

async function addTips(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_tips');
    formData.append('subtes', document.getElementById('addTipsSubtes').value);
    formData.append('topik', document.getElementById('addTipsTopik').value);
    formData.append('trik', document.getElementById('addTipsTrik').value);
    formData.append('akronim', document.getElementById('addTipsAkronim').value);
    formData.append('langkah', document.getElementById('addTipsLangkah').value);
    formData.append('contoh_soal', document.getElementById('addTipsContohSoal').value);
    formData.append('penjelasan', document.getElementById('addTipsPenjelasan').value);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tips_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Tips berhasil ditambahkan');
            hideAddTipsForm();
            loadTipsList();
        } else {
            alert(data.error || 'Gagal menambah tips');
        }
    } catch(e) {
        alert('Gagal menambah tips');
    }
}

async function editTips(tipsId) {
    try {
        const res = await fetch(`/api/admin_tips_crud.php?action=get_tips_detail&tips_id=${tipsId}`);
        const data = await res.json();
        
        if (data.success) {
            const t = data.tips;
            document.getElementById('addTipsSubtes').value = t.subtes;
            document.getElementById('addTipsTopik').value = t.topik || '';
            document.getElementById('addTipsTrik').value = t.trik;
            document.getElementById('addTipsAkronim').value = t.akronim || '';
            document.getElementById('addTipsLangkah').value = t.langkah || '';
            document.getElementById('addTipsContohSoal').value = t.contoh_soal || '';
            document.getElementById('addTipsPenjelasan').value = t.penjelasan || '';
            
            // Change form to edit mode
            const form = document.querySelector('#addTipsForm form');
            form.onsubmit = function(e) {
                e.preventDefault();
                updateTips(tipsId);
            };
            
            document.querySelector('#addTipsForm h3').textContent = 'Edit Tips';
            document.querySelector('#addTipsForm button[type="submit"]').textContent = 'Update Tips';
            document.getElementById('addTipsForm').style.display = 'block';
        } else {
            alert(data.error || 'Gagal memuat tips');
        }
    } catch(e) {
        alert('Gagal memuat tips');
    }
}

async function updateTips(tipsId) {
    const formData = new FormData();
    formData.append('action', 'edit_tips');
    formData.append('tips_id', tipsId);
    formData.append('subtes', document.getElementById('addTipsSubtes').value);
    formData.append('topik', document.getElementById('addTipsTopik').value);
    formData.append('trik', document.getElementById('addTipsTrik').value);
    formData.append('akronim', document.getElementById('addTipsAkronim').value);
    formData.append('langkah', document.getElementById('addTipsLangkah').value);
    formData.append('contoh_soal', document.getElementById('addTipsContohSoal').value);
    formData.append('penjelasan', document.getElementById('addTipsPenjelasan').value);
    formData.append('is_active', '1');
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tips_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Tips berhasil diupdate');
            hideAddTipsForm();
            resetTipsForm();
            loadTipsList();
        } else {
            alert(data.error || 'Gagal update tips');
        }
    } catch(e) {
        alert('Gagal update tips');
    }
}

function resetTipsForm() {
    const form = document.querySelector('#addTipsForm form');
    form.reset();
    form.onsubmit = function(e) {
        e.preventDefault();
        addTips(e);
    };
    document.querySelector('#addTipsForm h3').textContent = 'Tambah Tips Baru';
    document.querySelector('#addTipsForm button[type="submit"]').textContent = 'Simpan Tips';
}

async function deleteTips(tipsId) {
    if (!confirm('Yakin ingin menghapus tips ini? (soft delete)')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_tips');
    formData.append('tips_id', tipsId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tips_crud.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Tips berhasil dihapus');
            loadTipsList();
        } else {
            alert(data.error || 'Gagal menghapus tips');
        }
    } catch(e) {
        alert('Gagal menghapus tips');
    }
}

// --- MEDIA LIBRARY ---
async function loadMediaLibrary() {
    const fileType = document.getElementById('filterMediaType').value;
    const folder = document.getElementById('filterMediaFolder').value;
    const search = document.getElementById('searchMedia').value;
    
    try {
        let url = BASE_URL + '/api/admin_media_library.php?action=get_media_list';
        if (fileType) url += '&file_type=' + encodeURIComponent(fileType);
        if (folder) url += '&folder=' + encodeURIComponent(folder);
        if (search) url += '&search=' + encodeURIComponent(search);
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            renderMediaGrid(data.media);
        } else {
            document.getElementById('mediaGrid').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat media') + '</p>';
        }
    } catch(e) {
        document.getElementById('mediaGrid').innerHTML = '<p style="color:#e74c3c">Gagal memuat media</p>';
    }
    
    // Load folders
    loadFolders();
}

async function loadFolders() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_media_library.php?action=get_folders');
        const data = await res.json();
        
        if (data.success) {
            const folderSelect = document.getElementById('filterMediaFolder');
            const currentSelection = folderSelect.value;
            folderSelect.innerHTML = '<option value="">Semua Folder</option>';
            data.folders.forEach(folder => {
                folderSelect.innerHTML += `<option value="${folder}">${folder}</option>`;
            });
            folderSelect.value = currentSelection;
        }
    } catch(e) {
        console.error('Error loading folders:', e);
    }
}

function renderMediaGrid(mediaList) {
    if (mediaList.length === 0) {
        document.getElementById('mediaGrid').innerHTML = '<p style="color:#777">Belum ada media.</p>';
        return;
    }
    
    let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem">';
    mediaList.forEach(m => {
        const typeIcon = m.file_type === 'image' ? '🖼️' : m.file_type === 'video' ? '🎬' : '📄';
        const sizeStr = (m.file_size / 1024).toFixed(1) + ' KB';
        
        html += `
        <div style="background:#f8f9fa;padding:.8rem;border-radius:6px;border:1px solid #ddd">
            <div style="aspect-ratio:1;background:#e9ecef;border-radius:4px;margin-bottom:.5rem;display:flex;align-items:center;justify-content:center;overflow:hidden">
                ${m.file_type === 'image' 
                    ? `<img src="${escapeHtml(m.file_url)}" alt="${escapeHtml(m.original_name)}" style="max-width:100%;max-height:100%;object-fit:cover">`
                    : `<span style="font-size:2rem">${typeIcon}</span>`
                }
            </div>
            <div style="font-size:.8rem;font-weight:bold;margin-bottom:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(m.original_name)}</div>
            <div style="font-size:.75rem;color:#666;margin-bottom:.2rem">
                <span>${typeIcon} ${m.file_type}</span>
                <span style="margin:0 .3rem">•</span>
                <span>${sizeStr}</span>
            </div>
            <div style="font-size:.75rem;color:#777;margin-bottom:.5rem">${escapeHtml(m.folder)}</div>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                <button onclick="copyMediaUrl('${escapeHtml(m.file_url)}')" class="btn" style="font-size:.75rem;padding:.2rem .4rem">Copy URL</button>
                <button onclick="deleteMedia(${m.id})" class="btn danger" style="font-size:.75rem;padding:.2rem .4rem">Hapus</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('mediaGrid').innerHTML = html;
}

function showUploadForm() {
    document.getElementById('uploadMediaForm').style.display = 'block';
}

function hideUploadForm() {
    document.getElementById('uploadMediaForm').style.display = 'none';
}

async function uploadMedia(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('mediaFile');
    if (!fileInput.files[0]) {
        alert('Pilih file');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_media');
    formData.append('file', fileInput.files[0]);
    formData.append('folder', document.getElementById('mediaFolder').value);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_media_library.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('File berhasil diupload');
            hideUploadForm();
            loadMediaLibrary();
        } else {
            alert(data.error || 'Gagal upload');
        }
    } catch(e) {
        alert('Gagal upload');
    }
}

function copyMediaUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('URL berhasil disalin: ' + url);
    }).catch(() => {
        alert('Gagal menyalin URL');
    });
}

async function deleteMedia(mediaId) {
    if (!confirm('Yakin ingin menghapus media ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_media');
    formData.append('media_id', mediaId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_media_library.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Media berhasil dihapus');
            loadMediaLibrary();
        } else {
            alert(data.error || 'Gagal menghapus media');
        }
    } catch(e) {
        alert('Gagal menghapus media');
    }
}

// --- GENERATOR MASSAL ---
const topikMap = {
    'TIU': [
        {v:'Deret Angka', t:'numerik'},
        {v:'Berhitung', t:'numerik'},
        {v:'Perbandingan', t:'numerik'},
        {v:'Soal Cerita', t:'numerik'},
        {v:'Analogi', t:'verbal'},
        {v:'Silogisme', t:'verbal'},
        {v:'Analitis', t:'verbal'}
    ],
    'TWK': [
        {v:'Nasionalisme', t:''},
        {v:'Sejarah', t:''},
        {v:'Pancasila', t:''},
        {v:'Bahasa Indonesia', t:''},
        {v:'UUD 1945', t:''},
        {v:'Pilar Negara', t:''},
        {v:'Integritas', t:''},
        {v:'Bela Negara', t:''}
    ],
    'TKP': [
        {v:'Kepribadian', t:''},
        {v:'Pelayanan Publik', t:''},
        {v:'Jejaring Kerja', t:''},
        {v:'Sosial Budaya', t:''},
        {v:'Profesionalisme', t:''},
        {v:'Teknologi Informasi', t:''}
    ]
};

function updateGenTopik(){
    const subtes = document.getElementById('genSubtes').value;
    const sel = document.getElementById('genTopik');
    sel.innerHTML = '';
    topikMap[subtes].forEach((t, idx)=>{
        const opt = document.createElement('option');
        opt.value = t.v;
        opt.textContent = t.v;
        if (idx === 0) opt.selected = true; // Auto-select first topik
        sel.appendChild(opt);
    });
}

async function runGenerator(){
    const subtes = document.getElementById('genSubtes').value;
    const topik = document.getElementById('genTopik').value;
    const jumlah = document.getElementById('genJumlah').value;
    const kesulitan = document.getElementById('genKesulitan').value;
    const tipe = topikMap[subtes].find(t=>t.v===topik)?.t || '';

    const btn = document.getElementById('genBtn');
    const result = document.getElementById('genResult');
    const stats = document.getElementById('genStats');
    const log = document.getElementById('genLog');

    btn.disabled = true;
    btn.textContent = 'Generating...';
    result.style.display = 'block';
    stats.innerHTML = '<span style="color:#2980b9">Generating ' + jumlah + ' soal ' + subtes + ' / ' + topik + '...</span>';
    log.innerHTML = '';

    try {
        const url = BASE_URL + '/api/generate_soal_smart.php?subtes=' + encodeURIComponent(subtes)
            + '&tipe=' + encodeURIComponent(tipe)
            + '&topik=' + encodeURIComponent(topik)
            + '&jumlah=' + encodeURIComponent(jumlah)
            + '&kesulitan=' + encodeURIComponent(kesulitan);
        const res = await fetch(url);
        const data = await res.json();

        if(data.error){
            stats.innerHTML = '<span style="color:#e74c3c">Error: ' + escapeHtml(data.error) + '</span>';
            log.innerHTML = '';
        } else {
            stats.innerHTML = '<strong style="color:#27ae60">Berhasil!</strong> '
                + 'Generated: ' + data.generated + ' | '
                + 'Inserted: ' + data.inserted + ' | '
                + 'Skipped (duplikat): ' + data.skipped_duplicate;
            let html = '';
            data.soal.forEach((s,i)=>{
                html += (i+1) + '. ' + escapeHtml(s.pertanyaan.substring(0,80)) + '... ';
                html += '[Kunci: ' + s.jawaban_benar + ']<br>';
            });
            log.innerHTML = html;
            // Refresh stat total soal
            document.querySelector('.stat .num').textContent = parseInt(document.querySelector('.stat .num').textContent) + data.inserted;
        }
    } catch(e) {
        stats.innerHTML = '<span style="color:#e74c3c">Error: ' + escapeHtml(e.message) + '</span>';
    }

    btn.disabled = false;
    btn.textContent = 'Generate Soal';
}

// Init topik dropdown
document.addEventListener('DOMContentLoaded', updateGenTopik);

// --- UPLOAD GAMBAR ---
async function uploadGambar(){
    const input = document.getElementById('gambarInput');
    if(!input.files[0]){ alert('Pilih file gambar terlebih dahulu'); return; }
    const form = new FormData();
    form.append('gambar', input.files[0]);
    const res = await fetch(BASE_URL + '/api/upload_image.php', {method:'POST', body:form});
    const data = await res.json();
    const out = document.getElementById('uploadResult');
    if(data.success){
        out.innerHTML = '<span style="color:#27ae60">Berhasil upload: <code>' + escapeHtml(data.url) + '</code></span><br><small>Salin URL di atas dan tempel ke kolom Gambar saat edit soal.</small>';
    } else {
        out.innerHTML = '<span style="color:#e74c3c">Gagal: ' + escapeHtml(data.error) + '</span>';
    }
}

// --- LOAD SOAL LIST ---
async function loadSoalList(){
    const keyword = document.getElementById('searchSoal').value;
    const subtes = document.getElementById('filterSubtes').value;
    const tag = document.getElementById('filterTag').value;
    const container = document.getElementById('soalList');
    container.innerHTML = '<p style="color:#666">Memuat...</p>';

    let url = BASE_URL + '/api/list_soal.php?limit=50';
    if(keyword) url += '&q=' + encodeURIComponent(keyword);
    if(subtes) url += '&subtes=' + encodeURIComponent(subtes);
    if(tag) url += '&tag=' + encodeURIComponent(tag);

    try {
        const res = await fetch(url);
        const data = await res.json();
        if(data.error){ container.innerHTML='<p style="color:#e74c3c">'+data.error+'</p>'; return; }
        if(!data.soal || data.soal.length===0){ container.innerHTML='<p style="color:#666">Tidak ada soal ditemukan.</p>'; return; }

        let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.8rem">';
        data.soal.forEach(s=>{
            html += '<div style="border:1px solid #ddd;border-radius:6px;padding:.8rem;background:#fff">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">';
            html += '<strong style="font-size:.85rem">[' + escapeHtml(s.subtes) + '] ' + (s.tipe||'-') + '</strong>';
            html += '<span style="font-size:.75rem;color:#666">ID ' + s.id + '</span>';
            html += '</div>';
            html += '<div style="font-size:.85rem;margin-bottom:.4rem;max-height:60px;overflow:hidden">' + escapeHtml(s.pertanyaan.substring(0,120)) + '...</div>';
            if(s.image_url){
                html += '<img src="' + escapeHtml(s.image_url) + '" alt="Gambar soal" style="max-width:100%;max-height:100px;border:1px solid #ddd;border-radius:4px;margin-bottom:.4rem">';
            }
            html += '<div style="font-size:.8rem;color:#555">Kunci: <strong>' + s.jawaban_benar + '</strong></div>';
            // Tags
            if(s.tags && s.tags.length > 0){
                html += '<div style="margin-top:.3rem;font-size:.75rem">';
                s.tags.forEach(t => {
                    html += '<span style="background:#2980b9;color:#fff;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem">' + escapeHtml(t) + '</span>';
                });
                html += '</div>';
            }
            // Revision & visibility badges
            if(s.needs_revision || s.revision_status){
                html += '<div style="margin-top:.3rem;font-size:.75rem">';
                if(s.needs_revision) html += '<span style="background:#fff3cd;color:#856404;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem">Perlu Revisi</span>';
                if(s.revision_status) html += '<span style="background:#d4edda;color:#155724;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem">' + escapeHtml(s.revision_status) + '</span>';
                html += '</div>';
            }
            html += '<div style="margin-top:.5rem;display:flex;gap:.3rem;flex-wrap:wrap">';
            html += '<button class="btn" style="font-size:.8rem;padding:.3rem .5rem" onclick="openEditModal(' + s.id + ')">Edit</button>';
            html += '<button class="btn" style="font-size:.8rem;padding:.3rem .5rem;background:#8e44ad" onclick="viewSoalVersions(' + s.id + ')">Versi</button>';
            html += '<button class="btn danger" style="font-size:.8rem;padding:.3rem .5rem" onclick="deleteSoal(' + s.id + ')">Hapus</button>';
            if(s.needs_revision){
                html += '<button class="btn success" style="font-size:.8rem;padding:.3rem .5rem" onclick="markRevised(' + s.id + ')">Sudah Direvisi</button>';
            }
            const activeLabel = s.is_active ? 'Sembunyikan' : 'Tampilkan';
            const activeColor = s.is_active ? '#7f8c8d' : '#27ae60';
            html += '<button class="btn" style="font-size:.8rem;padding:.3rem .5rem;background:' + activeColor + '" onclick="toggleActive(' + s.id + ')">' + activeLabel + '</button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<p style="color:#e74c3c">Error memuat soal.</p>';
    }
}

async function loadRevisionList(){
    const container = document.getElementById('soalList');
    container.innerHTML = '<p style="color:#666">Memuat soal perlu revisi...</p>';
    try {
        const res = await fetch(BASE_URL + '/api/list_soal.php?needs_revision=1&limit=50');
        const data = await res.json();
        if(data.error){ container.innerHTML='<p style="color:#e74c3c">'+data.error+'</p>'; return; }
        if(!data.soal || data.soal.length===0){ container.innerHTML='<p style="color:#666">Tidak ada soal yang perlu direvisi. Bagus!</p>'; return; }

        let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.8rem">';
        data.soal.forEach(s=>{
            html += '<div style="border:1px solid #f1c40f;border-radius:6px;padding:.8rem;background:#fffbe9">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">';
            html += '<strong style="font-size:.85rem;color:#856404">[' + escapeHtml(s.subtes) + '] ' + escapeHtml(s.topik) + '</strong>';
            html += '<span style="font-size:.75rem;color:#666">ID ' + s.id + '</span>';
            html += '</div>';
            html += '<div style="font-size:.85rem;margin-bottom:.4rem;max-height:60px;overflow:hidden">' + escapeHtml(s.pertanyaan.substring(0,120)) + '...</div>';
            html += '<div style="margin-top:.5rem;display:flex;gap:.3rem">';
            html += '<button class="btn" style="font-size:.8rem;padding:.3rem .5rem" onclick="openEditModal(' + s.id + ')">Edit</button>';
            html += '<button class="btn success" style="font-size:.8rem;padding:.3rem .5rem" onclick="markRevised(' + s.id + ')">Sudah Direvisi</button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<p style="color:#e74c3c">Error memuat soal.</p>';
    }
}

async function markRevised(id){
    if(!confirm('Tandai soal ini sudah direvisi?')) return;
    try {
        const res = await fetch(BASE_URL + '/api/update_revision.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({question_id:id, action:'mark_revised'})
        });
        const data = await res.json();
        if(data.success){
            alert('Soal ditandai sudah direvisi');
            loadRevisionList();
        } else {
            alert('Gagal: ' + (data.error || 'Unknown'));
        }
    } catch(e) {
        alert('Error: ' + e.message);
    }
}

async function toggleActive(id){
    try {
        const res = await fetch(BASE_URL + '/api/update_revision.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({question_id:id, action:'toggle_active'})
        });
        const data = await res.json();
        if(data.success){
            loadSoalList();
        } else {
            alert('Gagal: ' + (data.error || 'Unknown'));
        }
    } catch(e) {
        alert('Error: ' + e.message);
    }
}

// --- EDIT MODAL ---
async function openEditModal(id){
    const res = await fetch(BASE_URL + '/api/get_soal_detail.php?id=' + id);
    const data = await res.json();
    if(data.error){ alert(data.error); return; }
    const s = data.soal;
    document.getElementById('editId').value = s.id;
    document.getElementById('editPertanyaan').value = s.pertanyaan;
    document.getElementById('editA').value = s.pilihan_a;
    document.getElementById('editB').value = s.pilihan_b;
    document.getElementById('editC').value = s.pilihan_c;
    document.getElementById('editD').value = s.pilihan_d;
    document.getElementById('editE').value = s.pilihan_e;
    document.getElementById('editKey').value = s.jawaban_benar;
    document.getElementById('editPembahasan').value = s.pembahasan || '';
    const imgDiv = document.getElementById('editCurrentImg');
    if(s.image_url){
        imgDiv.innerHTML = '<img src="' + escapeHtml(s.image_url) + '" alt="Preview gambar soal" style="max-width:150px;max-height:100px;border:1px solid #ddd;border-radius:4px"><div style="font-size:.75rem;color:#666">' + escapeHtml(s.image_url) + '</div>';
    } else {
        imgDiv.innerHTML = '<em style="color:#999;font-size:.8rem">Tidak ada gambar</em>';
    }
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal(){
    document.getElementById('editModal').style.display = 'none';
}

async function saveSoalEdit(){
    const id = document.getElementById('editId').value;
    const fileInput = document.getElementById('editGambar');
    const form = new FormData();
    form.append('id', id);
    form.append('pertanyaan', document.getElementById('editPertanyaan').value);
    form.append('pilihan_a', document.getElementById('editA').value);
    form.append('pilihan_b', document.getElementById('editB').value);
    form.append('pilihan_c', document.getElementById('editC').value);
    form.append('pilihan_d', document.getElementById('editD').value);
    form.append('pilihan_e', document.getElementById('editE').value);
    form.append('jawaban_benar', document.getElementById('editKey').value);
    form.append('pembahasan', document.getElementById('editPembahasan').value);
    if(fileInput.files[0]) form.append('gambar', fileInput.files[0]);

    const res = await fetch(BASE_URL + '/api/update_soal.php', {method:'POST', body:form});
    const data = await res.json();
    if(data.success){
        alert('Soal berhasil diperbarui!');
        closeEditModal();
        loadSoalList();
    } else {
        alert('Gagal: ' + (data.error || 'Unknown error'));
    }
}

async function resetUserPassword(userId, userName){
    if(!confirm(`Reset password untuk user "${userName}"? Password baru akan di-generate otomatis dan dikirim ke user via notifikasi.`)) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        const res = await fetch(BASE_URL + '/api/reset_user_password.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if(data.success){
            alert(`Password berhasil di-reset!\n\nPassword baru: ${data.new_password}\n\nPassword ini juga telah dikirim ke user via notifikasi.`);
        } else {
            alert(data.error || 'Gagal reset password');
        }
    } catch(e){
        alert('Gagal reset password. Silakan coba lagi.');
    }
}

// --- USER MANAGEMENT ---
function toggleAllUsers() {
    const selectAll = document.getElementById('selectAllUsers').checked;
    document.querySelectorAll('.userCheckbox').forEach(cb => cb.checked = selectAll);
}

function getSelectedUserIds() {
    return Array.from(document.querySelectorAll('.userCheckbox:checked')).map(cb => cb.value);
}

async function bulkAction(action) {
    const userIds = getSelectedUserIds();
    if (userIds.length === 0) {
        alert('Pilih user terlebih dahulu');
        return;
    }
    
    if (!confirm(`Anda yakin ingin ${action} ${userIds.length} user?`)) return;
    
    for (const userId of userIds) {
        const formData = new FormData();
        formData.append('action', action === 'suspend' ? 'suspend_user' : 'activate_user');
        formData.append('user_id', userId);
        formData.append('reason', action === 'suspend' ? 'Bulk suspend' : '');
        
        try {
            await fetch(BASE_URL + '/api/admin_user_management.php', {
                method: 'POST',
                body: formData
            });
        } catch(e) {
            console.error('Error:', e);
        }
    }
    
    alert(`${action} selesai untuk ${userIds.length} user`);
    location.reload();
}

async function editUser(userId) {
    const nama = prompt('Masukkan nama baru:');
    if (!nama) return;
    
    const noHp = prompt('Masukkan nomor HP baru:');
    if (!noHp) return;
    
    const formData = new FormData();
    formData.append('action', 'edit_user');
    formData.append('user_id', userId);
    formData.append('nama', nama);
    formData.append('no_hp', noHp);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_user_management.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('User berhasil diupdate');
            location.reload();
        } else {
            alert(data.error || 'Gagal update user');
        }
    } catch(e) {
        alert('Gagal update user');
    }
}

async function viewActivity(userId, userName) {
    try {
        const res = await fetch(`/api/admin_user_management.php?action=get_user_activity&user_id=${userId}`);
        const data = await res.json();
        
        if (data.success) {
            let html = `Activity Log: ${userName}\n\n`;
            data.activities.forEach(a => {
                html += `[${a.created_at}] ${a.action}\n`;
                if (a.details) html += `  ${a.details}\n`;
                if (a.ip_address) html += `  IP: ${a.ip_address}\n`;
                html += '\n';
            });
            alert(html);
        } else {
            alert(data.error || 'Gagal memuat activity');
        }
    } catch(e) {
        alert('Gagal memuat activity');
    }
}

async function manageUserStatus(userId, userName, currentStatus) {
    const actions = {
        'active': ['Suspend', 'Ban'],
        'suspended': ['Activate', 'Ban'],
        'banned': ['Activate']
    };
    
    const availableActions = actions[currentStatus] || [];
    if (availableActions.length === 0) {
        alert('Tidak ada aksi yang tersedia');
        return;
    }
    
    let action = prompt(`Pilih aksi untuk ${userName}:\n\n${availableActions.map((a, i) => `${i + 1}. ${a}`).join('\n')}\n\nMasukkan nomor (1-${availableActions.length}):`);
    
    if (!action) return;
    
    const actionIndex = parseInt(action) - 1;
    if (actionIndex < 0 || actionIndex >= availableActions.length) {
        alert('Pilihan tidak valid');
        return;
    }
    
    const selectedAction = availableActions[actionIndex].toLowerCase();
    let reason = '';
    
    if (selectedAction === 'suspend' || selectedAction === 'ban') {
        reason = prompt('Masukkan alasan:');
        if (!reason) {
            alert('Alasan wajib diisi');
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', selectedAction + '_user');
    formData.append('user_id', userId);
    formData.append('reason', reason);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_user_management.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert(`User berhasil ${selectedAction}`);
            location.reload();
        } else {
            alert(data.error || 'Gagal mengubah status');
        }
    } catch(e) {
        alert('Gagal mengubah status');
    }
}

// --- EVENT MANAGEMENT ---
async function loadEvents() {
    try {
        const res = await fetch(BASE_URL + '/api/admin_tryout_events.php?action=get_events');
        const data = await res.json();
        
        if (data.success) {
            renderEventsList(data.events);
        } else {
            document.getElementById('eventsList').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat events') + '</p>';
        }
    } catch(e) {
        document.getElementById('eventsList').innerHTML = '<p style="color:#e74c3c">Gagal memuat events</p>';
    }
}

function renderEventsList(events) {
    if (events.length === 0) {
        document.getElementById('eventsList').innerHTML = '<p style="color:#777">Belum ada event.</p>';
        return;
    }
    
    let html = '<div style="display:flex;flex-direction:column;gap:1rem">';
    events.forEach(e => {
        const statusBadge = e.aktif ? '<span class="badge" style="background:#d4edda;color:#155724">Aktif</span>' : '<span class="badge" style="background:#f8d7da;color:#721c24">Non-aktif</span>';
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <div>
                    <strong style="color:#1a5276">${escapeHtml(e.nama)}</strong>
                    ${statusBadge}
                </div>
                <div style="font-size:.75rem;color:#666">
                    ${new Date(e.tanggal_mulai).toLocaleString('id-ID')} - ${new Date(e.tanggal_selesai).toLocaleString('id-ID')}
                </div>
            </div>
            ${e.deskripsi ? `<p style="font-size:.85rem;color:#555;margin-bottom:.5rem">${escapeHtml(e.deskripsi)}</p>` : ''}
            <div style="font-size:.8rem;color:#666;margin-bottom:.5rem">
                Peserta: ${e.participant_count}/${e.max_participants || '∞'} | Paket: ${e.paket_nama || 'Default'}
            </div>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                <button onclick="viewEventParticipants(${e.id})" class="btn" style="font-size:.8rem;padding:.3rem .5rem">Lihat Peserta</button>
                <button onclick="viewEventResults(${e.id})" class="btn" style="font-size:.8rem;padding:.3rem .5rem">Lihat Hasil</button>
                <button onclick="toggleEventStatus(${e.id}, ${e.aktif})" class="btn" style="font-size:.8rem;padding:.3rem .5rem;background:${e.aktif ? '#e67e22' : '#27ae60'}">${e.aktif ? 'Non-aktifkan' : 'Aktifkan'}</button>
                <button onclick="deleteEvent(${e.id})" class="btn danger" style="font-size:.8rem;padding:.3rem .5rem">Hapus</button>
            </div>
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('eventsList').innerHTML = html;
}

function showCreateEventForm() {
    document.getElementById('createEventForm').style.display = 'block';
}

function hideCreateEventForm() {
    document.getElementById('createEventForm').style.display = 'none';
}

async function createEvent(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'create_event');
    formData.append('nama', document.getElementById('eventNama').value);
    formData.append('deskripsi', document.getElementById('eventDeskripsi').value);
    formData.append('tanggal_mulai', document.getElementById('eventTanggalMulai').value);
    formData.append('tanggal_selesai', document.getElementById('eventTanggalSelesai').value);
    formData.append('paket_soal_id', document.getElementById('eventPaketSoal').value);
    formData.append('max_participants', document.getElementById('eventMaxParticipants').value);
    formData.append('passing_grade', document.getElementById('eventPassingGrade').value);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tryout_events.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Event berhasil dibuat');
            hideCreateEventForm();
            loadEvents();
        } else {
            alert(data.error || 'Gagal membuat event');
        }
    } catch(e) {
        alert('Gagal membuat event');
    }
}

async function toggleEventStatus(eventId, currentStatus) {
    const formData = new FormData();
    formData.append('action', 'update_event');
    formData.append('event_id', eventId);
    formData.append('nama', 'Event'); // Required but won't change
    formData.append('aktif', currentStatus ? '0' : '1');
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tryout_events.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            loadEvents();
        } else {
            alert(data.error || 'Gagal mengubah status');
        }
    } catch(e) {
        alert('Gagal mengubah status');
    }
}

async function deleteEvent(eventId) {
    if (!confirm('Yakin ingin menghapus event ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_event');
    formData.append('event_id', eventId);
    
    try {
        const res = await fetch(BASE_URL + '/api/admin_tryout_events.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Event berhasil dihapus');
            loadEvents();
        } else {
            alert(data.error || 'Gagal menghapus event');
        }
    } catch(e) {
        alert('Gagal menghapus event');
    }
}

async function viewEventParticipants(eventId) {
    try {
        const res = await fetch(`/api/admin_tryout_events.php?action=get_event_participants&event_id=${eventId}`);
        const data = await res.json();
        
        if (data.success) {
            let html = `Peserta Event:\n\n`;
            data.participants.forEach(p => {
                html += `${p.nama} (${p.no_hp}) - ${p.instansi || '-'}\n`;
                html += `  Terdaftar: ${p.registered_at}\n\n`;
            });
            alert(html);
        } else {
            alert(data.error || 'Gagal memuat peserta');
        }
    } catch(e) {
        alert('Gagal memuat peserta');
    }
}

async function viewEventResults(eventId) {
    try {
        const res = await fetch(`/api/admin_tryout_events.php?action=get_event_results&event_id=${eventId}`);
        const data = await res.json();
        
        if (data.success) {
            let html = `Hasil Event:\n\n`;
            data.results.forEach((r, i) => {
                html += `${i + 1}. ${r.peserta} (${r.instansi || '-'})\n`;
                html += `   Total: ${r.total_nilai} | Status: ${r.status}\n\n`;
            });
            alert(html);
        } else {
            alert(data.error || 'Gagal memuat hasil');
        }
    } catch(e) {
        alert('Gagal memuat hasil');
    }
}

// Dark mode toggle
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}
// Load saved theme
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    document.documentElement.setAttribute('data-theme', savedTheme);
}
</script>
</body>
</html>

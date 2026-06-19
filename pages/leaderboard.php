<?php
require '../config.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
require '../helpers.php';

// Ambil daftar instansi aktif untuk dropdown
try {
    $instansiList = $pdo->query("SELECT id, kode, nama FROM instansi WHERE is_active = 1 ORDER BY nama")->fetchAll();
} catch (PDOException $e) {
    try {
        $instansiList = $pdo->query("SELECT id, kode, nama FROM instansi WHERE aktif = 1 ORDER BY nama")->fetchAll();
    } catch (PDOException $e2) {
        $instansiList = [];
    }
}

$period = $_GET['period'] ?? 'all'; // all, week, month
$subtes = $_GET['subtes'] ?? '';   // TWK, TIU, TKP (optional filter)
$instansiFilter = $_GET['instansi'] ?? ''; // instansi filter

// Check which status values are valid for this database
$validStatuses = ['completed', 'selesai']; // Production uses 'completed', local uses 'selesai'
try {
    $testStmt = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = 'status'");
    $columnInfo = $testStmt->fetch();
    if ($columnInfo && strpos($columnInfo['Type'], 'berjalan') !== false) {
        $validStatuses = ['selesai', 'berjalan']; // Local database
    }
} catch (PDOException $e) {
    // Assume production default
}

$where = "ts.status IN ('" . implode("','", $validStatuses) . "')";
$params = [];

if ($period === 'week') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === 'month') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if ($instansiFilter) {
    try {
        $where .= " AND u.instansi_id = ?";
    } catch (PDOException $e) {
        $where .= " AND u.target_instansi = ?";
    }
    $params[] = $instansiFilter;
}

// Get top 20 by total score
try {
    $sqlTotal = "
        SELECT 
            u.id as user_id,
            u.nama, u.instansi_id as target_instansi,
            ts.skor_total as total_nilai,
            ts.skor_twk as nilai_twk, ts.skor_tiu as nilai_tiu, ts.skor_tkp as nilai_tkp,
            ts.waktu_mulai
        FROM tryout_sessions ts
        JOIN users u ON ts.user_id = u.id
        WHERE $where
        ORDER BY ts.skor_total DESC
        LIMIT 20
    ";
    $totalStmt = $pdo->prepare($sqlTotal);
    $totalStmt->execute($params);
    $topTotal = $totalStmt->fetchAll();
} catch (PDOException $e) {
    // Fallback for older schema - check which column exists
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'instansi_id'");
        $hasInstansiId = $testStmt->fetch() !== false;
        
        if ($hasInstansiId) {
            $sqlTotal = "
                SELECT 
                    u.id as user_id,
                    u.nama, u.instansi_id as target_instansi,
                    ts.total_nilai as total_nilai,
                    ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp,
                    ts.waktu_mulai
                FROM tryout_sessions ts
                JOIN users u ON ts.user_id = u.id
                WHERE $where
                ORDER BY ts.total_nilai DESC
                LIMIT 20
            ";
        } else {
            $sqlTotal = "
                SELECT 
                    u.id as user_id,
                    u.nama, u.instansi_pilihan as target_instansi,
                    ts.total_nilai as total_nilai,
                    ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp,
                    ts.waktu_mulai
                FROM tryout_sessions ts
                JOIN users u ON ts.user_id = u.id
                WHERE $where
                ORDER BY ts.total_nilai DESC
                LIMIT 20
            ";
        }
    } catch (PDOException $e2) {
        // Final fallback without instansi column
        $sqlTotal = "
            SELECT 
                u.id as user_id,
                u.nama, '' as target_instansi,
                ts.total_nilai as total_nilai,
                ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp,
                ts.waktu_mulai
            FROM tryout_sessions ts
            JOIN users u ON ts.user_id = u.id
            WHERE $where
            ORDER BY ts.total_nilai DESC
            LIMIT 20
        ";
    }
    $totalStmt = $pdo->prepare($sqlTotal);
    $totalStmt->execute($params);
    $topTotal = $totalStmt->fetchAll();
}

// Fetch badges for leaderboard users
$userIds = array_column($topTotal, 'user_id');
$badges = [];
if (!empty($userIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $pdo->prepare("
            SELECT user_id, badge_type, badge_name, badge_icon, badge_color
            FROM leaderboard_badges
            WHERE user_id IN ($placeholders)
            ORDER BY earned_at DESC
        ");
        $stmt->execute($userIds);
        $badgeRows = $stmt->fetchAll();
        
        foreach ($badgeRows as $badge) {
            $badges[$badge['user_id']][] = $badge;
        }
    } catch (PDOException $e) {
        $badges = [];
    }
}

// Get top 10 per subtes
$topSubtes = [];
foreach (['TWK','TIU','TKP'] as $s) {
    // Check which score columns exist in tryout_sessions
    $colSkor = "skor_" . strtolower($s);
    $colNilai = "nilai_" . strtolower($s);
    
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = '$colSkor'");
        $hasSkor = $testStmt->fetch() !== false;
    } catch (PDOException $e) {
        $hasSkor = false;
    }
    
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = '$colNilai'");
        $hasNilai = $testStmt->fetch() !== false;
    } catch (PDOException $e) {
        $hasNilai = false;
    }
    
    if ($hasSkor) {
        $scoreCol = $colSkor;
    } elseif ($hasNilai) {
        $scoreCol = $colNilai;
    } else {
        $scoreCol = "0"; // Fallback if neither exists
    }
    
    // Check which status values are valid
    $validStatuses = ['completed', 'selesai'];
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM tryout_sessions WHERE Field = 'status'");
        $columnInfo = $testStmt->fetch();
        if ($columnInfo && strpos($columnInfo['Type'], 'berjalan') !== false) {
            $validStatuses = ['selesai', 'berjalan'];
        }
    } catch (PDOException $e) {
        // Assume production default
    }
    
    $whereSubtes = "ts.status IN ('" . implode("','", $validStatuses) . "') AND ts.$scoreCol > 0";
    $paramsSubtes = [];
    
    if ($instansiFilter) {
        // Check which instansi column exists
        try {
            $testStmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'instansi_id'");
            $hasInstansiId = $testStmt->fetch() !== false;
            if ($hasInstansiId) {
                $whereSubtes .= " AND u.instansi_id = ?";
            } else {
                $testStmt2 = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'instansi_pilihan'");
                $hasInstansiPilihan = $testStmt2->fetch() !== false;
                if ($hasInstansiPilihan) {
                    $whereSubtes .= " AND u.instansi_pilihan = ?";
                } else {
                    $whereSubtes .= " AND u.target_instansi = ?";
                }
            }
        } catch (PDOException $e) {
            $whereSubtes .= " AND u.target_instansi = ?";
        }
        $paramsSubtes[] = $instansiFilter;
    }
    
    // Check which instansi column to select
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'instansi_id'");
        $hasInstansiId = $testStmt->fetch() !== false;
        if ($hasInstansiId) {
            $instansiSelect = "u.instansi_id as target_instansi";
        } else {
            $testStmt2 = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'instansi_pilihan'");
            $hasInstansiPilihan = $testStmt2->fetch() !== false;
            if ($hasInstansiPilihan) {
                $instansiSelect = "u.instansi_pilihan as target_instansi";
            } else {
                $instansiSelect = "'' as target_instansi";
            }
        }
    } catch (PDOException $e) {
        $instansiSelect = "'' as target_instansi";
    }
    
    $stmt = $pdo->prepare("
        SELECT u.nama, $instansiSelect, 
               ts.$scoreCol as nilai, ts.waktu_mulai
        FROM tryout_sessions ts
        JOIN users u ON ts.user_id = u.id
        WHERE $whereSubtes
        ORDER BY nilai DESC
        LIMIT 10
    ");
    $stmt->execute($paramsSubtes);
    $topSubtes[$s] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Leaderboard — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem}.header a{color:#fff;text-decoration:none;font-size:.85rem}
.container{max-width:900px;margin:1.5rem auto;padding:0 1rem}
.filter-bar{background:#fff;border-radius:8px;padding:1rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;display:flex;gap:.5rem;flex-wrap:wrap}
.filter-bar a{display:inline-block;padding:.4rem .8rem;border-radius:5px;text-decoration:none;font-size:.9rem;color:#333;background:#f0f0f0}
.filter-bar a.active{background:#2980b9;color:#fff}
.section{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem}
.section h2{color:#1a5276;font-size:1.1rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.rank-row{display:flex;align-items:center;padding:.6rem .8rem;border-bottom:1px solid #f0f0f0;font-size:.9rem}
.rank-row:last-child{border-bottom:none}
.rank-num{width:40px;font-weight:bold;color:#2980b9;font-size:1.1rem;text-align:center}
.rank-medal{font-size:1.2rem}
.rank-name{flex:1;padding:0 .8rem}
.rank-score{font-weight:bold;color:#27ae60;min-width:60px;text-align:right}
.rank-detail{color:#888;font-size:.8rem;margin-left:auto;padding-left:.5rem}
.empty{color:#777;font-style:italic;text-align:center;padding:2rem}
.grid-3{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Leaderboard SKD CAT-BKN'; $activePage = 'leaderboard'; ?>
<?php require '../includes/navigation.php'; ?>

<div class="container" id="main-content">
<div class="filter-bar">
    <a href="?period=<?= $period ?>&instansi=" class="<?= !$instansiFilter?'active':'' ?>">Semua Instansi</a>
    <?php foreach ($instansiList as $i): ?>
    <a href="?period=<?= $period ?>&instansi=<?= $i['kode'] ?>" class="<?= $instansiFilter===$i['kode']?'active':'' ?>"><?= e($i['kode']) ?></a>
    <?php endforeach; ?>
    <span style="border-left:1px solid #ccc;margin:0 .5rem"></span>
    <a href="?period=all&instansi=<?= $instansiFilter ?>" class="<?= $period==='all'?'active':'' ?>">Semua Waktu</a>
    <a href="?period=month&instansi=<?= $instansiFilter ?>" class="<?= $period==='month'?'active':'' ?>">30 Hari</a>
    <a href="?period=week&instansi=<?= $instansiFilter ?>" class="<?= $period==='week'?'active':'' ?>">7 Hari</a>
</div>

<!-- Top Total -->
<div class="section">
<h2>🏆 Top 20 — Nilai Total Tertinggi</h2>
<?php if (empty($topTotal)): ?>
<div class="empty">Belum ada data tryout yang selesai.</div>
<?php else: ?>
<?php foreach ($topTotal as $i => $r):
    $medal = '';
    if ($i === 0) $medal = '🥇';
    elseif ($i === 1) $medal = '🥈';
    elseif ($i === 2) $medal = '🥉';
    
    $userBadges = $badges[$r['user_id']] ?? [];
    $badgeHtml = '';
    foreach ($userBadges as $badge) {
        $badgeHtml .= '<span style="display:inline-block;background:'.$badge['badge_color'].';color:#fff;padding:.2rem .4rem;border-radius:12px;font-size:.7rem;margin-left:.3rem" title="'.e($badge['badge_name']).'">'.$badge['badge_icon'].'</span>';
    }
?>
<div class="rank-row">
    <div class="rank-num"><?= $medal ?: ($i + 1) ?></div>
    <div class="rank-name">
        <div><strong><?= e($r['nama']) ?></strong><?= $badgeHtml ?></div>
        <div style="font-size:.8rem;color:#888"><?= e($r['instansi'] ?: '-') ?></div>
    </div>
    <div class="rank-score"><?= $r['total_nilai'] ?></div>
    <div class="rank-detail">TWK <?= $r['nilai_twk'] ?> · TIU <?= $r['nilai_tiu'] ?> · TKP <?= $r['nilai_tkp'] ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Top per Subtes -->
<div class="grid-3">
<?php foreach (['TWK'=>'Wawasan Kebangsaan','TIU'=>'Intelegensia Umum','TKP'=>'Karakteristik Pribadi'] as $sub => $label): ?>
<div class="section">
    <h2><?= $sub ?> — <?= $label ?></h2>
    <?php if (empty($topSubtes[$sub])): ?>
    <div class="empty">Belum ada data.</div>
    <?php else: ?>
    <?php foreach ($topSubtes[$sub] as $i => $r):
        $medal = '';
        if ($i === 0) $medal = '🥇';
        elseif ($i === 1) $medal = '🥈';
        elseif ($i === 2) $medal = '🥉';
    ?>
    <div class="rank-row">
        <div class="rank-num"><?= $medal ?: ($i + 1) ?></div>
        <div class="rank-name">
            <div><strong><?= e($r['nama']) ?></strong></div>
            <div style="font-size:.8rem;color:#888"><?= e($r['instansi'] ?: '-') ?></div>
        </div>
        <div class="rank-score"><?= $r['nilai'] ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="footer">
Leaderboard SKD CAT-BKN | Peringkat berdasarkan tryout yang telah selesai
</div>
</body>
</html>

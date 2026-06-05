<?php
require '../config.php';
require '../helpers.php';

$period = $_GET['period'] ?? 'all'; // all, week, month
$subtes = $_GET['subtes'] ?? '';   // TWK, TIU, TKP (optional filter)

$where = "ts.status = 'selesai'";
$params = [];

if ($period === 'week') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === 'month') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Get top 20 by total score
$sqlTotal = "
    SELECT 
        u.nama, u.instansi,
        ts.total_nilai,
        ts.nilai_twk, ts.nilai_tiu, ts.nilai_tkp,
        ts.waktu_mulai
    FROM tryout_sessions ts
    JOIN users u ON ts.user_id = u.id
    WHERE $where
    ORDER BY ts.total_nilai DESC
    LIMIT 20
";
$totalStmt = $pdo->prepare($sqlTotal);
$totalStmt->execute($params);
$topTotal = $totalStmt->fetchAll();

// Get top 10 per subtes
$topSubtes = [];
foreach (['TWK','TIU','TKP'] as $s) {
    $col = "nilai_" . strtolower($s);
    $stmt = $pdo->prepare("
        SELECT u.nama, u.instansi, ts.$col as nilai, ts.waktu_mulai
        FROM tryout_sessions ts
        JOIN users u ON ts.user_id = u.id
        WHERE ts.status = 'selesai' AND ts.$col > 0
        ORDER BY ts.$col DESC
        LIMIT 10
    ");
    $stmt->execute();
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
<div class="header">
<h1>Leaderboard SKD CAT-BKN</h1>
<div>
<a href="../index.php">Beranda</a>
<a href="user_dashboard.php">Dashboard</a>
<a href="daily_quiz.php" style="background:#e74c3c;color:#fff;padding:.2rem .5rem;border-radius:4px">Daily Quiz</a>
<a href="tryout.php">Try Out</a>
<a href="../api/logout.php">Logout</a>
</div>
</div>

<div class="container" id="main-content">
<div class="filter-bar">
    <a href="?period=all" class="<?= $period==='all'?'active':'' ?>">Semua Waktu</a>
    <a href="?period=month" class="<?= $period==='month'?'active':'' ?>">30 Hari</a>
    <a href="?period=week" class="<?= $period==='week'?'active':'' ?>">7 Hari</a>
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
?>
<div class="rank-row">
    <div class="rank-num"><?= $medal ?: ($i + 1) ?></div>
    <div class="rank-name">
        <div><strong><?= e($r['nama']) ?></strong></div>
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

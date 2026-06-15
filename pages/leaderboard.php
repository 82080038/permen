<?php
require '../config.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
require '../helpers.php';

// Ambil daftar instansi aktif untuk dropdown
$instansiList = $pdo->query("SELECT id, nama FROM instansi WHERE is_active = 1 ORDER BY nama")->fetchAll();

$period = $_GET['period'] ?? 'all'; // all, week, month
$subtes = $_GET['subtes'] ?? '';   // TWK, TIU, TKP (optional filter)
$instansiFilter = $_GET['instansi'] ?? ''; // instansi filter

$where = "ts.status = 'completed'";
$params = [];

if ($period === 'week') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === 'month') {
    $where .= " AND ts.waktu_mulai >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if ($instansiFilter) {
    $where .= " AND u.target_instansi = ?";
    $params[] = $instansiFilter;
}

// Get top 20 by total score
$sqlTotal = "
    SELECT 
        u.id as user_id,
        u.nama, u.target_instansi,
        ts.skor_total,
        ts.skor_twk, ts.skor_tiu, ts.skor_tkp,
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

// Fetch badges for leaderboard users
$userIds = array_column($topTotal, 'user_id');
$badges = [];
if (!empty($userIds)) {
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
}

// Get top 10 per subtes
$topSubtes = [];
foreach (['TWK','TIU','TKP'] as $s) {
    $col = "skor_" . strtolower($s);
    $whereSubtes = "ts.status = 'completed' AND ts.$col > 0";
    $paramsSubtes = [];
    
    if ($instansiFilter) {
        $whereSubtes .= " AND u.target_instansi = ?";
        $paramsSubtes[] = $instansiFilter;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.nama, u.target_instansi, ts.$col as nilai, ts.waktu_mulai
        FROM tryout_sessions ts
        JOIN users u ON ts.user_id = u.id
        WHERE $whereSubtes
        ORDER BY ts.$col DESC
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
    <div class="rank-score"><?= $r['skor_total'] ?></div>
    <div class="rank-detail">TWK <?= $r['skor_twk'] ?> · TIU <?= $r['skor_tiu'] ?> · TKP <?= $r['skor_tkp'] ?></div>
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

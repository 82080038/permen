<?php
require '../config.php';
require '../helpers.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$baseUrl = $_ENV['BASE_URL'] ?? '/permen';

// Get user's latest tryout scores
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE user_id = ? AND status = 'selesai' ORDER BY waktu_selesai DESC LIMIT 5");
$stmt->execute([$userId]);
$tryouts = $stmt->fetchAll();

// Get weakness data from latest tryout
$weaknesses = [];
if (!empty($tryouts)) {
    $latestSession = $tryouts[0]['id'];
    $topicStmt = $pdo->prepare("SELECT q.subtes, q.topik,
        COUNT(*) as total,
        SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar
        FROM answers a 
        JOIN questions q ON a.question_id = q.id 
        WHERE a.session_id = ? 
        GROUP BY q.subtes, q.topik
        ORDER BY (SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) / COUNT(*)) ASC
        LIMIT 10");
    $topicStmt->execute([$latestSession]);
    $weaknesses = $topicStmt->fetchAll();
}

// Get available materi grouped by subtes
$materiStmt = $pdo->query("SELECT subtes, COUNT(*) as count FROM materi GROUP BY subtes");
$materiCounts = [];
foreach ($materiStmt as $m) {
    $materiCounts[$m['subtes']] = $m['count'];
}

// Average scores
$avgTwk = 0; $avgTiu = 0; $avgTkp = 0; $avgTotal = 0;
if (!empty($tryouts)) {
    $sumTwk = 0; $sumTiu = 0; $sumTkp = 0;
    foreach ($tryouts as $t) {
        $sumTwk += (int)$t['nilai_twk'];
        $sumTiu += (int)$t['nilai_tiu'];
        $sumTkp += (int)$t['nilai_tkp'];
    }
    $n = count($tryouts);
    $avgTwk = round($sumTwk / $n);
    $avgTiu = round($sumTiu / $n);
    $avgTkp = round($sumTkp / $n);
    $avgTotal = $avgTwk + $avgTiu + $avgTkp;
}

// Generate study plan based on weaknesses
$studyPlan = [];
if (!empty($weaknesses)) {
    $priority = 1;
    foreach ($weaknesses as $w) {
        $acc = $w['total'] > 0 ? round(($w['benar'] / $w['total']) * 100) : 0;
        if ($acc >= 80) continue; // Skip strong topics
        
        // Find materi
        $mStmt = $pdo->prepare("SELECT id, judul, subtes FROM materi WHERE subtes = ? AND (judul LIKE ? OR judul LIKE ?) LIMIT 3");
        $mStmt->execute([$w['subtes'], '%' . $w['topik'] . '%', '%' . strtolower($w['topik']) . '%']);
        $materi = $mStmt->fetchAll();
        
        $estimatedTime = $acc < 50 ? '2 jam' : ($acc < 70 ? '1 jam' : '30 menit');
        
        $studyPlan[] = [
            'priority' => $priority++,
            'subtes' => $w['subtes'],
            'topik' => $w['topik'],
            'akurasi' => $acc,
            'estimasi' => $estimatedTime,
            'materi' => $materi,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Study Plan — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem}
.header h1{font-size:1.1rem}
.container{max-width:900px;margin:1rem auto;padding:0 1rem}
.card{background:#fff;border-radius:8px;padding:1.2rem;margin-bottom:1rem;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.card h2{color:#1a5276;font-size:1rem;margin-bottom:.8rem}
.score-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.8rem}
.score-box{text-align:center;padding:.8rem;border-radius:6px;border:2px solid}
.score-box.twk{border-color:#e74c3c}.score-box.tiu{border-color:#f39c12}.score-box.tkp{border-color:#27ae60}
.score-box .num{font-size:1.5rem;font-weight:bold}
.score-box .lbl{font-size:.75rem;color:#666}
.plan-item{padding:.8rem;border-left:4px solid #f39c12;background:#f8f9fa;border-radius:4px;margin-bottom:.5rem}
.plan-item.high{border-color:#e74c3c}
.plan-item.med{border-color:#f39c12}
.plan-item.low{border-color:#2980b9}
.plan-item .head{display:flex;justify-content:space-between;align-items:center}
.plan-item .prio{font-size:.7rem;background:#1a5276;color:#fff;padding:.1rem .4rem;border-radius:3px}
.plan-item .acc{font-weight:bold}
.plan-item .meta{font-size:.8rem;color:#666;margin-top:.3rem}
.plan-item a{color:#2980b9;text-decoration:none;font-size:.85rem}
.empty{text-align:center;padding:2rem;color:#999}
.btn{display:inline-block;padding:.5rem 1rem;background:#1a5276;color:#fff;border-radius:6px;text-decoration:none;font-size:.85rem}
@media(max-width:768px){.container{padding:0 .5rem}}
</style>
</head>
<body>
<div class="header"><h1>📚 Study Plan — Personalisasi Belajar</h1></div>
<div class="container">
    
<?php if (empty($tryouts)): ?>
    <div class="card">
        <div class="empty">
            <p>Belum ada riwayat tryout.</p>
            <p style="margin-top:.5rem">Selesaikan tryout untuk mendapatkan rekomendasi belajar personal.</p>
            <a href="tryout.php" class="btn" style="margin-top:1rem">Mulai Tryout</a>
        </div>
    </div>
<?php else: ?>

    <div class="card">
        <h2>📊 Rata-rata Skor (<?= count($tryouts) ?> tryout terakhir)</h2>
        <div class="score-grid">
            <div class="score-box twk"><div class="num"><?= $avgTwk ?></div><div class="lbl">TWK</div></div>
            <div class="score-box tiu"><div class="num"><?= $avgTiu ?></div><div class="lbl">TIU</div></div>
            <div class="score-box tkp"><div class="num"><?= $avgTkp ?></div><div class="lbl">TKP</div></div>
            <div class="score-box" style="border-color:#1a5276"><div class="num"><?= $avgTotal ?></div><div class="lbl">Total</div></div>
        </div>
    </div>

    <div class="card">
        <h2>🎯 Adaptive Learning Path</h2>
        <p style="font-size:.85rem;color:#666;margin-bottom:.8rem">Topik diprioritaskan berdasarkan akurasi terendah dari tryout terakhir.</p>
        
        <?php if (empty($studyPlan)): ?>
            <div class="empty">
                <p style="color:#27ae60">✅ Semua topik sudah di atas 80% akurasi!</p>
                <p style="margin-top:.3rem">Fokus pada latihan soal untuk menjaga kecepatan.</p>
            </div>
        <?php else: ?>
            <?php foreach ($studyPlan as $p): 
                $cls = $p['akurasi'] < 50 ? 'high' : ($p['akurasi'] < 70 ? 'med' : 'low');
            ?>
            <div class="plan-item <?= $cls ?>">
                <div class="head">
                    <strong><?= e($p['subtes']) ?> — <?= e($p['topik']) ?></strong>
                    <span class="acc" style="color:<?= $p['akurasi'] < 50 ? '#e74c3c' : '#f39c12' ?>"><?= $p['akurasi'] ?>%</span>
                </div>
                <div class="meta">
                    Prioritas #<?= $p['priority'] ?> • Estimasi: <?= $p['estimasi'] ?>
                </div>
                <?php if (!empty($p['materi'])): ?>
                    <div style="margin-top:.3rem">
                        📚 
                        <?php foreach ($p['materi'] as $m): ?>
                        <a href="materi.php?subtes=<?= $m['subtes'] ?>#materi_<?= $m['id'] ?>"><?= e($m['judul']) ?></a>
                        <?php if (end($p['materi']) !== $m): ?> • <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📋 Rencana Belajar Mingguan</h2>
        <?php
        $days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        $planPerDay = array_chunk($studyPlan, max(1, ceil(count($studyPlan) / 5)), true);
        ?>
        <table style="width:100%;font-size:.85rem;border-collapse:collapse">
        <thead><tr><th style="text-align:left;padding:.4rem;border-bottom:2px solid #1a5276">Hari</th><th style="text-align:left;padding:.4rem;border-bottom:2px solid #1a5276">Aktivitas</th></tr></thead>
        <tbody>
        <?php for ($i = 0; $i < 7; $i++): ?>
        <tr>
            <td style="padding:.4rem;border-bottom:1px solid #eee"><strong><?= $days[$i] ?></strong></td>
            <td style="padding:.4rem;border-bottom:1px solid #eee">
            <?php if ($i < 5 && isset($planPerDay[$i])): ?>
                <?php foreach ($planPerDay[$i] as $p): ?>
                Pelajari <?= e($p['topik']) ?> (<?= $p['estimasi'] ?>)<?php if (end($planPerDay[$i]) !== $p): ?>, <?php endif; ?>
                <?php endforeach; ?>
            <?php elseif ($i === 5): ?>
                📝 Latihan soal per subtes
            <?php else: ?>
                📝 Full tryout + review
            <?php endif; ?>
            </td>
        </tr>
        <?php endfor; ?>
        </tbody>
        </table>
    </div>

    <div style="text-align:center;margin-bottom:2rem">
        <a href="tryout.php" class="btn">Mulai Tryout</a>
        <a href="materi.php?subtes=TWK" class="btn" style="background:#2980b9;margin-left:.5rem">Pelajari Materi</a>
        <a href="user_dashboard.php" class="btn" style="background:#7f8c8d;margin-left:.5rem">Dashboard</a>
    </div>

<?php endif; ?>
</div>
</body>
</html>

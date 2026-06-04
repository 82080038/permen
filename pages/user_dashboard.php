<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');
$userRole = $_SESSION['user_role'] ?? 'user';

// Fetch user info with instansi
$stmt = $pdo->prepare("SELECT u.nama, u.email, u.instansi, u.instansi_id, i.kode as instansi_kode, i.nama as instansi_nama, i.deskripsi as instansi_desk 
    FROM users u LEFT JOIN instansi i ON u.instansi_id = i.id WHERE u.id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();

// Fetch riwayat tryout
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$riwayat = $stmt->fetchAll();

// Calculate stats
$totalTryout = count($riwayat);
$selesai = array_filter($riwayat, fn($r) => $r['status'] === 'selesai');
$rataNilai = 0;
$bestScore = 0;
$subtesTerlemah = '-';

if (count($selesai) > 0) {
    $totalNilai = array_sum(array_column($selesai, 'total_nilai'));
    $rataNilai = round($totalNilai / count($selesai));
    $bestScore = max(array_column($selesai, 'total_nilai'));

    // Cari subtes terlemah (rata-rata nilai per subtes)
    $tkpScores = array_column($selesai, 'nilai_tkp');
    $tiuScores = array_column($selesai, 'nilai_tiu');
    $twkScores = array_column($selesai, 'nilai_twk');
    $avgTkp = count($tkpScores) ? round(array_sum($tkpScores) / count($tkpScores)) : 0;
    $avgTiu = count($tiuScores) ? round(array_sum($tiuScores) / count($tiuScores)) : 0;
    $avgTwk = count($twkScores) ? round(array_sum($twkScores) / count($twkScores)) : 0;
    $min = min($avgTkp, $avgTiu, $avgTwk);
    if ($min === $avgTkp && $min > 0) $subtesTerlemah = 'TKP';
    elseif ($min === $avgTiu && $min > 0) $subtesTerlemah = 'TIU';
    elseif ($min === $avgTwk && $min > 0) $subtesTerlemah = 'TWK';
}

// Passing grades dari subtes_config (dinamis)
$passingMap = $pdo->query("SELECT subtes, passing_grade FROM subtes_config WHERE aktif = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
$passingTkp = (int)($passingMap['TKP'] ?? 126);
$passingTiu = (int)($passingMap['TIU'] ?? 80);
$passingTwk = (int)($passingMap['TWK'] ?? 65);
$passingTotal = (int)($passingMap['TKP'] ?? 126) + (int)($passingMap['TIU'] ?? 80) + (int)($passingMap['TWK'] ?? 65);

// Ambil semua instansi untuk rekomendasi
$instansiList = $pdo->query("SELECT * FROM instansi WHERE aktif = 1 ORDER BY urutan")->fetchAll();

// Ambil rekomendasi materi
$rekomendasiMateri = $pdo->query("SELECT * FROM rekomendasi_materi WHERE aktif = 1 ORDER BY urutan")->fetchAll();

// Analisis akurasi per topik (butuh minimal 1 jawaban)
$akurasiTopik = $pdo->prepare("
    SELECT 
        q.subtes,
        q.topik,
        COUNT(*) as total,
        SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    JOIN tryout_sessions ts ON a.session_id = ts.id
    WHERE ts.user_id = ? AND a.jawaban_user IS NOT NULL AND a.jawaban_user != ''
    GROUP BY q.subtes, q.topik
    HAVING COUNT(*) >= 3
    ORDER BY subtes, benar ASC, total DESC
    LIMIT 10
");
$akurasiTopik->execute([$userId]);
$topikStats = $akurasiTopik->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Dashboard Peserta — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:1000px;margin:1.5rem auto;padding:0 1rem}
.welcome{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.5rem}
.welcome h2{color:#1a5276;font-size:1.15rem;margin-bottom:.3rem}
.welcome p{color:#555;font-size:.9rem}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:8px;padding:.9rem;box-shadow:0 2px 6px rgba(0,0,0,.08);text-align:center}
.stat .num{font-size:1.6rem;font-weight:bold;color:#2980b9}
.stat .label{color:#555;font-size:.85rem;margin-top:.3rem}
.stat .sub{color:#777;font-size:.75rem}
.actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;overflow-x:auto;-webkit-overflow-scrolling:touch}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.55rem .9rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:.9rem;min-height:44px;min-width:44px;white-space:nowrap}
.btn:hover{background:#1a5276}
.btn.success{background:#27ae60}
.btn.warning{background:#e67e22}
.btn.danger{background:#e74c3c}
.section{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;overflow:hidden}
.section h2{color:#1a5276;font-size:1.05rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:500px}
th,td{border:1px solid #eee;padding:.4rem .5rem;text-align:left}
th{background:#f8f9fa;color:#555}
tr:hover{background:#f8f9fa}
.badge{display:inline-block;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold}
.badge.lulus{background:#d4edda;color:#155724}
.badge.gagal{background:#f8d7da;color:#721c24}
.badge.berjalan{background:#fff3cd;color:#856404}
.empty{color:#777;font-style:italic;text-align:center;padding:2rem}
.rekomendasi{background:#eaf2f8;border-left:4px solid #2980b9;padding:1rem;border-radius:0 6px 6px 0;margin-top:1rem}
.rekomendasi h3{color:#1a5276;font-size:1rem;margin-bottom:.3rem}
.rekomendasi p{color:#444;font-size:.9rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:600px){
.stats{grid-template-columns:repeat(2,1fr)}
.actions{flex-wrap:nowrap}
.section{padding:1rem}
}
@media(max-width:380px){
.stats{grid-template-columns:1fr}
}
.topic-bar{margin-bottom:.8rem}
.topic-bar-header{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.2rem}
.topic-bar-track{background:#e9ecef;border-radius:10px;height:20px;overflow:hidden}
.topic-bar-fill{height:100%;border-radius:10px;transition:width .3s ease}
.topic-bar-fill.high{background:#27ae60}.topic-bar-fill.mid{background:#f39c12}.topic-bar-fill.low{background:#e74c3c}
.topic-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
</style>
</head>
<body>
<div class="header">
<h1>Dashboard Peserta — SKD CAT-BKN</h1>
<div>
<a href="../index.php">Beranda</a>
<a href="latihan.php">Latihan</a>
<a href="tryout.php">Try Out</a>
<a href="leaderboard.php">Leaderboard</a>
<?php if ($userRole === 'admin'): ?>
<a href="admin_dashboard.php">Admin</a>
<?php endif; ?>
<a href="../api/logout.php">Logout</a>
</div>
</div>

<div class="container">
<div class="welcome">
<h2>Selamat datang, <?= $userName ?>!</h2>
<p>
<?php if ($userInfo['instansi_nama']): ?>
<strong>Instansi Pilihan:</strong> <?= e($userInfo['instansi_kode']) ?> — <?= e($userInfo['instansi_nama']) ?><br>
<small style="color:#666"><?= e($userInfo['instansi_desk'] ?? '') ?></small>
<?php else: ?>
<strong>Belum memilih instansi.</strong> <a href="register.php" style="color:#2980b9">Pilih instansi di profil</a> untuk mendapatkan rekomendasi.
<?php endif; ?>
</p>
</div>

<div class="actions">
<a href="tryout.php" class="btn danger">Mulai Try Out Penuh</a>
<a href="latihan.php" class="btn success">Latihan per Subtes</a>
<a href="riwayat_soal.php" class="btn" style="background:#8e44ad">Riwayat Soal</a>
<a href="materi.php?subtes=TWK" class="btn warning">Materi TWK</a>
<a href="materi.php?subtes=TIU" class="btn warning">Materi TIU</a>
<a href="materi.php?subtes=TKP" class="btn warning">Materi TKP</a>
</div>

<div class="stats">
<div class="stat"><div class="num"><?= $totalTryout ?></div><div class="label">Total Tryout</div></div>
<div class="stat"><div class="num"><?= count($selesai) ?></div><div class="label">Selesai</div></div>
<div class="stat"><div class="num"><?= $rataNilai ?></div><div class="label">Rata-rata Nilai</div></div>
<div class="stat"><div class="num"><?= $bestScore ?></div><div class="label">Nilai Tertinggi</div></div>
<div class="stat"><div class="num" style="color:#e74c3c"><?= $subtesTerlemah ?></div><div class="label">Subtes Terlemah</div></div>
</div>

<?php if (!empty($selesai)): ?>
<!-- Progress Chart -->
<div class="section">
<h2>Grafik Progress</h2>
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
    <button class="btn" style="font-size:.8rem" onclick="drawChart('total')">Total</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('tkp')">TKP</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('tiu')">TIU</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('twk')">TWK</button>
</div>
<canvas id="progressChart" width="900" height="300" style="max-width:100%;height:auto"></canvas>
</div>

<script>
const chartData = <?= json_encode(array_map(fn($r)=>[
    'date'=>date('d M',strtotime($r['waktu_mulai'])),
    'total'=>(int)($r['total_nilai']??0),
    'tkp'=>(int)($r['nilai_tkp']??0),
    'tiu'=>(int)($r['nilai_tiu']??0),
    'twk'=>(int)($r['nilai_twk']??0)
], array_reverse(array_slice($selesai,-10)))) ?>;

function drawChart(mode){
    const canvas = document.getElementById('progressChart');
    const ctx = canvas.getContext('2d');
    const w = canvas.width, h = canvas.height;
    const pad = {top:30,right:30,bottom:50,left:50};
    ctx.clearRect(0,0,w,h);

    const labels = chartData.map(d=>d.date);
    const values = chartData.map(d=>d[mode]);
    const maxVal = Math.max(...values, 300);
    const minVal = 0;

    // Grid & axes
    ctx.strokeStyle='#eee';ctx.lineWidth=1;
    for(let i=0;i<=5;i++){
        const y=pad.top+(h-pad.top-pad.bottom)*(1-i/5);
        ctx.beginPath();ctx.moveTo(pad.left,y);ctx.lineTo(w-pad.right,y);ctx.stroke();
        ctx.fillStyle='#999';ctx.font='11px Arial';ctx.textAlign='right';
        ctx.fillText(Math.round(maxVal*i/5),pad.left-8,y+4);
    }

    // Bars
    const barW = (w-pad.left-pad.right)/values.length * 0.6;
    const gap = (w-pad.left-pad.right)/values.length;
    const color = mode==='total'?'#2980b9':(mode==='tkp'?'#27ae60':(mode==='tiu'?'#e67e22':'#8e44ad'));

    values.forEach((v,i)=>{
        const x = pad.left + gap*i + gap*0.2;
        const barH = (v/maxVal)*(h-pad.top-pad.bottom);
        const y = h-pad.bottom-barH;
        ctx.fillStyle=color;
        ctx.fillRect(x,y,barW,barH);
        // Value label
        ctx.fillStyle='#333';ctx.font='bold 11px Arial';ctx.textAlign='center';
        ctx.fillText(v, x+barW/2, y-5);
        // Date label
        ctx.save();
        ctx.translate(x+barW/2, h-pad.bottom+15);
        ctx.rotate(-Math.PI/6);
        ctx.fillStyle='#666';ctx.font='10px Arial';
        ctx.fillText(labels[i],0,0);
        ctx.restore();
    });

    // Axis labels
    ctx.fillStyle='#333';ctx.font='bold 12px Arial';ctx.textAlign='center';
    ctx.fillText('Tanggal', w/2, h-5);
    ctx.save();ctx.translate(15,h/2);ctx.rotate(-Math.PI/2);
    ctx.fillText('Nilai ' + mode.toUpperCase(), 0, 0);
    ctx.restore();
}
drawChart('total');
</script>
<?php endif; ?>

<?php if (!empty($topikStats)): ?>
<!-- Analisis Akurasi per Topik -->
<div class="section">
<h2>Analisis Akurasi per Topik</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">Persentase jawaban benar per topik (minimal 3 soal dikerjakan). Semakin tinggi semakin baik.</p>
<div class="topic-grid">
<?php foreach ($topikStats as $ts):
    $pct = round(($ts['benar'] / $ts['total']) * 100);
    $fillClass = $pct >= 70 ? 'high' : ($pct >= 40 ? 'mid' : 'low');
    $badgeColor = $pct >= 70 ? '#27ae60' : ($pct >= 40 ? '#f39c12' : '#e74c3c');
?>
<div style="background:#f8f9fa;border-radius:6px;padding:.8rem">
    <div class="topic-bar">
        <div class="topic-bar-header">
            <span><strong><?= $ts['subtes'] ?></strong> — <?= e($ts['topik']) ?></span>
            <span style="color:<?= $badgeColor ?>;font-weight:bold"><?= $pct ?>% (<?= $ts['benar'] ?>/<?= $ts['total'] ?>)</span>
        </div>
        <div class="topic-bar-track">
            <div class="topic-bar-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
    <div style="text-align:right;margin-top:.3rem">
        <a href="materi.php?subtes=<?= $ts['subtes'] ?>" style="font-size:.8rem;color:#2980b9;text-decoration:none">Latih Topik Ini &rarr;</a>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php if ($subtesTerlemah !== '-'): ?>
<div class="rekomendasi">
<h3>Analisis Kelemahan & Rekomendasi</h3>
<p>Subtes <strong><?= $subtesTerlemah ?></strong> adalah nilai rata-rata terendah Anda (rata-rata: <?= $subtesTerlemah === 'TKP' ? $avgTkp : ($subtesTerlemah === 'TIU' ? $avgTiu : $avgTwk) ?>).</p>
<?php
// Filter rekomendasi materi berdasarkan subtes terlemah
$rekomSub = array_filter($rekomendasiMateri, fn($r) => $r['subtes'] === $subtesTerlemah);
if (!empty($rekomSub)):
?>
<ul style="margin-top:.5rem;padding-left:1.2rem;font-size:.9rem;color:#444">
<?php foreach (array_slice($rekomSub, 0, 3) as $r): ?>
<li><a href="<?= $r['materi_url'] ?>" style="color:#2980b9;text-decoration:none"><?= e($r['topik']) ?></a> — <?= e($r['pesan']) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($selesai)): 
    $latest = reset($selesai); // Tryout terakhir yang selesai
    $latestTkp = $latest['nilai_tkp'] ?? 0;
    $latestTiu = $latest['nilai_tiu'] ?? 0;
    $latestTwk = $latest['nilai_twk'] ?? 0;
    $latestTotal = $latest['total_nilai'] ?? 0;
?>
<div class="section" style="margin-top:1.2rem">
<h2>Kelayakan Instansi</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">Berdasarkan nilai tryout terakhir Anda (TKP: <?= $latestTkp ?>, TIU: <?= $latestTiu ?>, TWK: <?= $latestTwk ?>, Total: <?= $latestTotal ?>):</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem">
<?php foreach ($instansiList as $ins): 
    $lulusTkp = $latestTkp >= $ins['passing_tkp'];
    $lulusTiu = $latestTiu >= $ins['passing_tiu'];
    $lulusTwk = $latestTwk >= $ins['passing_twk'];
    $lulusTotal = $latestTotal >= $ins['passing_total'];
    $lulusSemua = $lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal;
    $cardColor = $lulusSemua ? '#d4edda' : '#fff3cd';
    $borderColor = $lulusSemua ? '#155724' : '#856404';
?>
<div style="background:<?= $cardColor ?>;border:1px solid <?= $borderColor ?>;border-radius:6px;padding:.8rem;font-size:.9rem">
<div style="font-weight:bold;color:#1a5276"><?= e($ins['kode']) ?></div>
<div style="font-size:.8rem;color:#555;margin-bottom:.3rem"><?= e($ins['nama']) ?></div>
<?php if ($lulusSemua): ?>
<div style="color:#155724;font-weight:bold;font-size:.85rem">✅ Memenuhi syarat SKD</div>
<?php else: ?>
<div style="color:#856404;font-size:.8rem">
<?php if (!$lulusTkp) echo 'TKP kurang ' . ($ins['passing_tkp'] - $latestTkp) . '<br>'; ?>
<?php if (!$lulusTiu) echo 'TIU kurang ' . ($ins['passing_tiu'] - $latestTiu) . '<br>'; ?>
<?php if (!$lulusTwk) echo 'TWK kurang ' . ($ins['passing_twk'] - $latestTwk) . '<br>'; ?>
<?php if (!$lulusTotal) echo 'Total kurang ' . ($ins['passing_total'] - $latestTotal) . '<br>'; ?>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<div class="section">
<h2>Riwayat Tryout</h2>
<?php if (empty($riwayat)): ?>
<div class="empty">Belum ada riwayat tryout. Yuk mulai latihan!</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr><th>Nama</th><th>Status</th><th>TKP</th><th>TIU</th><th>TWK</th><th>Total</th><th>Waktu</th></tr>
</thead>
<tbody>
<?php foreach ($riwayat as $r):
    $tkpStatus = ($r['nilai_tkp'] ?? 0) >= $passingTkp ? 'lulus' : 'gagal';
    $tiuStatus = ($r['nilai_tiu'] ?? 0) >= $passingTiu ? 'lulus' : 'gagal';
    $twkStatus = ($r['nilai_twk'] ?? 0) >= $passingTwk ? 'lulus' : 'gagal';
?>
<tr style="cursor:pointer" onclick="window.location.href='hasil.php?session_id=<?= $r['id'] ?>'">
<td><?= e($r['nama']) ?></td>
<td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
<td><span class="badge <?= $tkpStatus ?>"><?= $r['nilai_tkp'] ?? 0 ?></span></td>
<td><span class="badge <?= $tiuStatus ?>"><?= $r['nilai_tiu'] ?? 0 ?></span></td>
<td><span class="badge <?= $twkStatus ?>"><?= $r['nilai_twk'] ?? 0 ?></span></td>
<td><?= $r['total_nilai'] ?? 0 ?></td>
<td><?= date('d M Y H:i', strtotime($r['waktu_mulai'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

</div>

<div class="footer">
Dashboard Peserta SKD CAT-BKN | Latihan persiapan Sekolah Kedinasan
</div>
</body>
</html>

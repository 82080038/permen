<?php
require '../config.php';
require '../helpers.php';
$sessionId = $_GET['session_id'] ?? 0;
if (!$sessionId) {
    header('Location: ../index.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();
if (!$session) {
    header('Location: ../index.php');
    exit;
}

// Ambil data subtes dari tabel normalisasi session_subtes (fallback ke flat columns)
$stmt = $pdo->prepare("SELECT subtes, nilai, passing_grade, jumlah_soal FROM session_subtes WHERE session_id = ?");
$stmt->execute([$sessionId]);
$subData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (empty($subData)) {
    // Fallback: data dari kolom flat (session lama)
    $subData = [
        'TKP' => ['nilai'=>$session['nilai_tkp'],'passing_grade'=>$session['passing_tkp'],'jumlah_soal'=>$session['jumlah_tkp']],
        'TIU' => ['nilai'=>$session['nilai_tiu'],'passing_grade'=>$session['passing_tiu'],'jumlah_soal'=>$session['jumlah_tiu']],
        'TWK' => ['nilai'=>$session['nilai_twk'],'passing_grade'=>$session['passing_twk'],'jumlah_soal'=>$session['jumlah_twk']],
    ];
}

$passingTkp = $subData['TKP']['passing_grade'] ?? 126;
$passingTiu = $subData['TIU']['passing_grade'] ?? 80;
$passingTwk = $subData['TWK']['passing_grade'] ?? 65;
$passingTotal = $session['passing_total'] ?? 271;

$nilaiTkp = $subData['TKP']['nilai'] ?? 0;
$nilaiTiu = $subData['TIU']['nilai'] ?? 0;
$nilaiTwk = $subData['TWK']['nilai'] ?? 0;
$totalNilai = $session['total_nilai'] ?? ($nilaiTkp + $nilaiTiu + $nilaiTwk);

$statusTkp = $nilaiTkp >= $passingTkp ? 'LULUS' : 'TIDAK LULUS';
$statusTiu = $nilaiTiu >= $passingTiu ? 'LULUS' : 'TIDAK LULUS';
$statusTwk = $nilaiTwk >= $passingTwk ? 'LULUS' : 'TIDAK LULUS';
$statusTotal = $totalNilai >= $passingTotal ? 'LULUS' : 'TIDAK LULUS';

$lulusSemua = ($statusTkp === 'LULUS' && $statusTiu === 'LULUS' && $statusTwk === 'LULUS' && $statusTotal === 'LULUS');

// Deteksi mode latihan
$isLatihan = (strpos($session['nama'], 'Latihan') === 0);
$latihanSubtes = '';
if ($isLatihan) {
    if (($subData['TWK']['jumlah_soal']??0) > 0 && ($subData['TIU']['jumlah_soal']??0) == 0 && ($subData['TKP']['jumlah_soal']??0) == 0) $latihanSubtes = 'TWK';
    elseif (($subData['TIU']['jumlah_soal']??0) > 0 && ($subData['TWK']['jumlah_soal']??0) == 0 && ($subData['TKP']['jumlah_soal']??0) == 0) $latihanSubtes = 'TIU';
    elseif (($subData['TKP']['jumlah_soal']??0) > 0 && ($subData['TWK']['jumlah_soal']??0) == 0 && ($subData['TIU']['jumlah_soal']??0) == 0) $latihanSubtes = 'TKP';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title><?= $isLatihan ? "Hasil Latihan $latihanSubtes" : 'Hasil Try Out' ?> — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.topbar{background:#1a5276;color:#fff;padding:.5rem 1rem;font-size:.8rem;display:flex;flex-wrap:wrap;gap:.4rem .6rem;align-items:center}
.topbar a{color:#fff;text-decoration:none;margin-right:.6rem;min-height:44px;display:flex;align-items:center;font-size:.8rem}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;text-align:center}
.header h1{font-size:1.15rem}.header a{color:#fff;text-decoration:none}
.container{max-width:700px;margin:1.5rem auto;padding:0 1rem}
.card{background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem;margin-bottom:1.2rem;text-align:center}
.card h2{color:#1a5276;margin-bottom:.5rem;font-size:1.1rem}
.score{font-size:2.5rem;font-weight:bold;color:#2980b9;margin:.4rem 0}
.score.fail{color:#e74c3c}
.details{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-top:1.2rem}
@media print{
.topbar,.footer,.no-print{display:none!important}
.container{max-width:100%;margin:0;padding:0}
.card{box-shadow:none;border:1px solid #ddd;break-inside:avoid}
}
.detail{background:#f8f9fa;border-radius:6px;padding:.8rem}
.detail h3{font-size:.85rem;color:#555;margin-bottom:.2rem}
.detail .val{font-size:1.4rem;font-weight:bold}
.detail .pass{color:#27ae60}.detail .fail{color:#e74c3c}
.badge{display:inline-block;padding:.4rem .9rem;border-radius:20px;font-weight:bold;margin-top:.8rem;font-size:.9rem}
.badge.lulus{background:#d4edda;color:#155724}.badge.gagal{background:#f8d7da;color:#721c24}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.65rem 1.1rem;border-radius:5px;text-decoration:none;margin-top:.8rem;font-size:.9rem;min-height:44px;min-width:44px}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem}
@media(max-width:480px){
.details{grid-template-columns:1fr}
.score{font-size:2rem}
}
</style>
</head>
<body>
<div class="topbar">
<a href="../index.php">Beranda</a>
<a href="latihan.php">Latihan</a>
<a href="materi.php?subtes=TWK">Materi</a>
<?php if (!empty($_SESSION['user_id'])): ?>
<a href="user_dashboard.php">Dashboard</a>
<a href="../api/logout.php">Logout</a>
<?php else: ?>
<a href="login.php">Login</a>
<?php endif; ?>
</div>
<div class="header">
<h1><?= $isLatihan ? "Hasil Latihan " . htmlspecialchars($latihanSubtes) : 'Hasil Try Out SKD CAT-BKN' ?></h1>
</div>
<div class="container">

<?php if ($isLatihan): ?>
<!-- MODE LATIHAN -->
<div class="card">
<h2>Nilai Latihan <?= $latihanSubtes ?></h2>
<?php
$nilaiSubtes = 0; $passingSubtes = 0; $statusSubtes = '';
if ($latihanSubtes === 'TKP') { $nilaiSubtes = $nilaiTkp; $passingSubtes = $passingTkp; $statusSubtes = $statusTkp; }
elseif ($latihanSubtes === 'TIU') { $nilaiSubtes = $nilaiTiu; $passingSubtes = $passingTiu; $statusSubtes = $statusTiu; }
else { $nilaiSubtes = $nilaiTwk; $passingSubtes = $passingTwk; $statusSubtes = $statusTwk; }
?>
<div class="score <?= $statusSubtes=='LULUS'?'':'fail' ?>"><?= htmlspecialchars($nilaiSubtes) ?></div>
<div class="badge <?= $statusSubtes=='LULUS'?'lulus':'gagal' ?>"><?= $statusSubtes === 'LULUS' ? 'MEMENUHI AMBANG BATAS' : 'BELUM MEMENUHI AMBANG BATAS' ?></div>
<p style="margin-top:.5rem;color:#666;font-size:.9rem">Ambang batas <?= htmlspecialchars($latihanSubtes) ?>: <?= htmlspecialchars($passingSubtes) ?> &middot; Soal: <?= htmlspecialchars($subData[$latihanSubtes]['jumlah_soal'] ?? 0) ?></p>
</div>

<div class="card" style="text-align:center">
<a href="latihan.php?subtes=<?= $latihanSubtes ?>" class="btn">Latihan <?= $latihanSubtes ?> Lagi</a>
<a href="latihan.php" class="btn" style="background:#27ae60;margin-left:.5rem">Latihan Subtes Lain</a>
<a href="tryout.php" class="btn" style="background:#e67e22;margin-left:.5rem">Try Out Penuh</a>
<a href="../index.php" class="btn" style="background:#7f8c8d;margin-left:.5rem">Beranda</a>
</div>

<?php else: ?>
<!-- MODE TRY OUT PENUH -->
<div class="card">
<h2>Nilai Total SKD</h2>
<div class="score <?= $statusTotal=='LULUS'?'':'fail' ?>"><?= htmlspecialchars($totalNilai) ?></div>
<div class="badge <?= $lulusSemua?'lulus':'gagal' ?>"><?= $lulusSemua ? 'ANDA LULUS SKD (SIMULASI)' : 'ANDA BELUM MEMENUHI AMBANG BATAS' ?></div>
<p style="margin-top:.5rem;color:#666;font-size:.9rem">Ambang batas total: <?= htmlspecialchars($passingTotal) ?></p>
</div>

<div class="card">
<h2>Rincian Nilai per Subtes</h2>
<div class="details">
<div class="detail">
<h3>TKP</h3>
<div class="val <?= $statusTkp=='LULUS'?'pass':'fail' ?>"><?= htmlspecialchars($nilaiTkp) ?></div>
<div style="font-size:.8rem;color:#666">Ambang: <?= htmlspecialchars($passingTkp) ?></div>
<div style="font-weight:bold;font-size:.85rem;margin-top:.3rem" class="<?= $statusTkp=='LULUS'?'pass':'fail' ?>"><?= $statusTkp ?></div>
</div>
<div class="detail">
<h3>TIU</h3>
<div class="val <?= $statusTiu=='LULUS'?'pass':'fail' ?>"><?= htmlspecialchars($nilaiTiu) ?></div>
<div style="font-size:.8rem;color:#666">Ambang: <?= htmlspecialchars($passingTiu) ?></div>
<div style="font-weight:bold;font-size:.85rem;margin-top:.3rem" class="<?= $statusTiu=='LULUS'?'pass':'fail' ?>"><?= $statusTiu ?></div>
</div>
<div class="detail">
<h3>TWK</h3>
<div class="val <?= $statusTwk=='LULUS'?'pass':'fail' ?>"><?= htmlspecialchars($nilaiTwk) ?></div>
<div style="font-size:.8rem;color:#666">Ambang: <?= htmlspecialchars($passingTwk) ?></div>
<div style="font-weight:bold;font-size:.85rem;margin-top:.3rem" class="<?= $statusTwk=='LULUS'?'pass':'fail' ?>"><?= $statusTwk ?></div>
</div>
</div>
</div>

<!-- Rekomendasi Instansi -->
<div class="card">
<h2>Kelayakan Instansi</h2>
<?php
$instansiList = $pdo->query("SELECT * FROM instansi WHERE aktif = 1 ORDER BY urutan")->fetchAll();
$eligibleCount = 0;
foreach ($instansiList as $ins) {
    $lulusTkp = $nilaiTkp >= $ins['passing_tkp'];
    $lulusTiu = $nilaiTiu >= $ins['passing_tiu'];
    $lulusTwk = $nilaiTwk >= $ins['passing_twk'];
    $lulusTotal = $totalNilai >= $ins['passing_total'];
    if ($lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal) $eligibleCount++;
}
?>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">
<?php if ($lulusSemua): ?>
🎉 <strong>Selamat!</strong> Anda memenuhi syarat SKD untuk <strong><?= $eligibleCount ?> instansi</strong>.
<?php else: ?>
⚠️ Anda belum memenuhi syarat SKD untuk instansi pilihan. Fokus perbaiki subtes yang belum memenuhi ambang batas.
<?php endif; ?>
</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem">
<?php foreach ($instansiList as $ins):
    $lulusTkp = $nilaiTkp >= $ins['passing_tkp'];
    $lulusTiu = $nilaiTiu >= $ins['passing_tiu'];
    $lulusTwk = $nilaiTwk >= $ins['passing_twk'];
    $lulusTotal = $totalNilai >= $ins['passing_total'];
    $lulusSemuaIns = $lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal;
    $cardBg = $lulusSemuaIns ? '#d4edda' : '#fff3cd';
    $borderCol = $lulusSemuaIns ? '#155724' : '#856404';
?>
<div style="background:<?= $cardBg ?>;border:1px solid <?= $borderCol ?>;border-radius:5px;padding:.6rem;font-size:.85rem">
<div style="font-weight:bold;color:#1a5276"><?= e($ins['kode']) ?></div>
<div style="font-size:.75rem;color:#555"><?= e($ins['nama']) ?></div>
<?php if ($lulusSemuaIns): ?>
<div style="color:#155724;font-size:.75rem;font-weight:bold">✅ Lulus SKD</div>
<?php else: ?>
<div style="color:#856404;font-size:.7rem">
<?= (!$lulusTkp ? 'TKP ' : '') . (!$lulusTiu ? 'TIU ' : '') . (!$lulusTwk ? 'TWK ' : '') . (!$lulusTotal ? 'Total' : '') ?>
<span style="color:#999">kurang</span>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- REKOMENDASI LATIHAN -->
<div class="card no-print" style="text-align:left;display:none" id="rekomendasiCard">
<h2>Rekomendasi Latihan</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:.8rem">Berdasarkan hasil tryout, topik berikut perlu ditingkatkan:</p>
<div id="rekomendasiList" style="display:flex;flex-wrap:wrap;gap:.5rem"></div>
</div>

<!-- REVIEW SOAL -->
<div class="card" style="text-align:left">
<h2>Review Soal</h2>
<div id="reviewStats" style="margin-bottom:1rem;font-size:.9rem"></div>
<div id="reviewContainer" style="max-height:500px;overflow-y:auto;border:1px solid #ddd;border-radius:6px;padding:.8rem">
<p style="color:#666">Memuat review soal...</p>
</div>
</div>

<script>
const reviewSessionId = <?= (int)$sessionId ?>;

async function loadReview(){
    const res = await fetch('../api/get_review.php?session_id='+reviewSessionId);
    const data = await res.json();
    if(data.error){document.getElementById('reviewContainer').innerHTML='<p style="color:#e74c3c">'+data.error+'</p>';return;}

    // Stats
    let statsHtml = '';
    for(const sub of ['TWK','TIU','TKP']){
        const s = data.stats[sub];
        if(s.total > 0){
            statsHtml += '<div style="display:inline-block;margin-right:1.5rem;margin-bottom:.5rem">';
            statsHtml += '<strong>'+sub+'</strong>: ';
            statsHtml += '<span style="color:#27ae60">Benar '+s.benar+'</span> / ';
            statsHtml += '<span style="color:#e74c3c">Salah '+s.salah+'</span> / ';
            statsHtml += '<span style="color:#999">Kosong '+s.kosong+'</span> ';
            statsHtml += '(Skor: '+s.skor+')';
            statsHtml += '</div>';
        }
    }
    document.getElementById('reviewStats').innerHTML = statsHtml;

    // Rekomendasi: hitung topik yang sering salah
    const topikSalah = {};
    data.soal.forEach(q => {
        if(q.jawaban_user !== q.jawaban_benar) {
            const key = q.subtes + ' — ' + q.topik;
            topikSalah[key] = (topikSalah[key] || 0) + 1;
        }
    });
    const sortedTopik = Object.entries(topikSalah).sort((a,b) => b[1] - a[1]).slice(0,5);
    if(sortedTopik.length > 0){
        document.getElementById('rekomendasiCard').style.display = 'block';
        let recHtml = '';
        sortedTopik.forEach(([topik, count]) => {
            const sub = topik.split(' — ')[0];
            recHtml += '<a href="materi.php?subtes='+encodeURIComponent(sub)+'" style="display:inline-block;background:#fff3cd;color:#856404;padding:.4rem .8rem;border-radius:20px;text-decoration:none;font-size:.85rem;border:1px solid #f1c40f">';
            recHtml += escapeHtml(topik) + ' ('+count+' salah)';
            recHtml += '</a>';
        });
        document.getElementById('rekomendasiList').innerHTML = recHtml;
    }

    // Questions
    let html = '';
    let currentPassage = null;
    data.soal.forEach((q,i)=>{
        const isCorrect = q.jawaban_user === q.jawaban_benar;
        const isEmpty = !q.jawaban_user;
        const statusColor = isEmpty ? '#999' : (isCorrect ? '#27ae60' : '#e74c3c');
        const statusText = isEmpty ? 'KOSONG' : (isCorrect ? 'BENAR' : 'SALAH');

        // Show passage if changed
        if(q.passage_id && q.passage_id !== currentPassage){
            currentPassage = q.passage_id;
            const p = data.passages[q.passage_id];
            if(p){
                html += '<div style="background:#f0f7ff;border:1px solid #b8d4f0;border-radius:6px;padding:.6rem;margin-bottom:.6rem">';
                html += '<div style="font-weight:bold;color:#1a5276;font-size:.9rem">'+escapeHtml(p.judul)+'</div>';
                html += '<div style="font-size:.85rem;color:#333">'+escapeHtml(p.bacaan)+'</div>';
                html += '</div>';
            }
        }

        html += '<div style="border:1px solid #e0e0e0;border-radius:6px;padding:.8rem;margin-bottom:.6rem;background:#fff">';
        html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">';
        html += '<strong>Soal '+(i+1)+' ['+q.subtes+']</strong>';
        html += '<span style="font-size:.8rem;font-weight:bold;color:'+statusColor+'">'+statusText+'</span>';
        html += '</div>';
        html += '<div style="font-size:.9rem;margin-bottom:.5rem">'+escapeHtml(q.pertanyaan)+'</div>';
        if(q.image_url){
            html += '<img src="'+escapeHtml(q.image_url)+'" style="max-width:100%;max-height:150px;margin:.4rem 0;border:1px solid #ddd;border-radius:4px">';
        }

        ['A','B','C','D','E'].forEach(opt=>{
            const optText = q['pilihan_'+opt.toLowerCase()];
            const isUser = q.jawaban_user === opt;
            const isKey = q.jawaban_benar === opt;
            let style = 'padding:.3rem .5rem;border-radius:4px;font-size:.85rem;margin-bottom:.2rem;';
            if(isKey) style += 'background:#d4edda;border:1px solid #27ae60;';
            else if(isUser && !isCorrect) style += 'background:#f8d7da;border:1px solid #e74c3c;';
            else style += 'background:#f8f9fa;';
            html += '<div style="'+style+'">'+opt+'. '+escapeHtml(optText);
            if(isKey) html += ' <span style="color:#27ae60;font-weight:bold">(KUNCI)</span>';
            if(isUser) html += ' <span style="color:#666">&larr; Jawaban Anda</span>';
            html += '</div>';
        });

        html += '<div style="margin-top:.5rem;padding:.5rem;background:#fffbea;border-left:3px solid #f1c40f;border-radius:4px;font-size:.85rem">';
        html += '<strong>Pembahasan:</strong> '+escapeHtml(q.pembahasan);
        html += '</div>';

        // Tips & trick
        if(q.tips_trick){
            html += '<div style="margin-top:.4rem;padding:.4rem;background:#eaf8ea;border-left:3px solid #27ae60;border-radius:4px;font-size:.82rem">';
            html += '<strong style="color:#1e8449">Tips & Trick:</strong> '+escapeHtml(q.tips_trick);
            html += '</div>';
        }

        // Related links
        if(q.related_links){
            let links = [];
            try{ links = JSON.parse(q.related_links); }catch(e){}
            if(links.length > 0){
                html += '<div style="margin-top:.4rem;font-size:.8rem">';
                html += '<strong style="color:#1a5276">Pelajari lebih lanjut:</strong> ';
                links.forEach((l,i)=>{
                    html += '<a href="'+escapeHtml(l.url)+'" target="_blank" style="color:#2980b9;text-decoration:none;background:#eaf2f8;padding:.15rem .4rem;border-radius:3px;margin-right:.3rem;display:inline-block;margin-bottom:.2rem">'+escapeHtml(l.label)+'</a>';
                });
                html += '</div>';
            }
        }

        // Materi link
        if(q.materi_judul){
            html += '<div style="margin-top:.4rem;font-size:.8rem">';
            html += '<strong style="color:#8e44ad">Materi Pembelajaran:</strong> ';
            if(q.materi_url){
                html += '<a href="'+escapeHtml(q.materi_url)+'" target="_blank" style="color:#8e44ad;text-decoration:none;background:#f5eef8;padding:.15rem .4rem;border-radius:3px;margin-right:.3rem;display:inline-block;margin-bottom:.2rem">'+escapeHtml(q.materi_judul)+'</a>';
            }else{
                html += '<span style="color:#8e44ad;background:#f5eef8;padding:.15rem .4rem;border-radius:3px;display:inline-block">'+escapeHtml(q.materi_judul)+'</span>';
            }
            html += '</div>';
        }

        // Latih topik ini (hanya untuk soal salah)
        if(!isCorrect){
            html += '<div style="margin-top:.4rem;font-size:.8rem">';
            html += '<a href="materi.php?subtes='+encodeURIComponent(q.subtes)+'" style="display:inline-block;background:#d4edda;color:#155724;padding:.2rem .5rem;border-radius:4px;text-decoration:none;font-size:.8rem;border:1px solid #27ae60">Latih Topik Ini &rarr;</a>';
            html += '</div>';
        }

        html += '</div>';
    });
    document.getElementById('reviewContainer').innerHTML = html;
}

function escapeHtml(text){
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadReview();
</script>

<div class="card no-print" style="text-align:center">
<a href="tryout.php" class="btn">Coba Lagi</a>
<a href="latihan.php" class="btn" style="background:#27ae60;margin-left:.5rem">Latihan per Subtes</a>
<a href="materi.php?subtes=TWK" class="btn" style="background:#2980b9;margin-left:.5rem">Pelajari Materi</a>
<a href="../index.php" class="btn" style="background:#7f8c8d;margin-left:.5rem">Beranda</a>
<button onclick="window.print()" class="btn" style="background:#8e44ad;margin-left:.5rem">Simpan/Cetak PDF</button>
</div>
<?php endif; ?>

</div>
<div class="footer">
Disclaimer: Hasil ini adalah simulasi. Penentuan kelulusan sesuai aturan BKN dan Kementerian/Lembaga terkait.
</div>
</body>
</html>

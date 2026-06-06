<?php
require '../config.php';
require '../helpers.php';
$sessionId = $_GET['session_id'] ?? 0;
if (!$sessionId) {
    header('Location: /permen/index.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();
if (!$session) {
    header('Location: /permen/index.php');
    exit;
}

// Ambil data subtes dari tabel normalisasi session_subtes (fallback ke flat columns)
$stmt = $pdo->prepare("SELECT subtes, nilai, passing_grade, jumlah_soal FROM session_subtes WHERE session_id = ?");
$stmt->execute([$sessionId]);
$subDataRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$subData = [];
foreach ($subDataRows as $row) {
    $subData[$row['subtes']] = $row;
}

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

// Fetch previous tryout for comparison (only for full tryout)
$previousSession = null;
$comparisonData = null;
if (!$isLatihan && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM tryout_sessions 
        WHERE user_id = ? AND id < ? AND status = 'selesai'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $sessionId]);
    $previousSession = $stmt->fetch();
    
    if ($previousSession) {
        // Get previous subtes data
        $stmt = $pdo->prepare("SELECT subtes, nilai FROM session_subtes WHERE session_id = ?");
        $stmt->execute([$previousSession['id']]);
        $prevSubData = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prevSubData[$row['subtes']] = $row['nilai'];
        }
        
        // Calculate differences
        $comparisonData = [
            'total_diff' => $totalNilai - ($previousSession['total_nilai'] ?? 0),
            'tkp_diff' => $nilaiTkp - ($prevSubData['TKP'] ?? 0),
            'tiu_diff' => $nilaiTiu - ($prevSubData['TIU'] ?? 0),
            'twk_diff' => $nilaiTwk - ($prevSubData['TWK'] ?? 0),
            'previous_date' => $previousSession['created_at']
        ];
    }
    
    // Fetch institution average for comparison
    $stmt = $pdo->prepare("
        SELECT AVG(total_nilai) as avg_total, 
               AVG((SELECT nilai FROM session_subtes ss WHERE ss.session_id = ts.id AND ss.subtes = 'TKP')) as avg_tkp,
               AVG((SELECT nilai FROM session_subtes ss WHERE ss.session_id = ts.id AND ss.subtes = 'TIU')) as avg_tiu,
               AVG((SELECT nilai FROM session_subtes ss WHERE ss.session_id = ts.id AND ss.subtes = 'TWK')) as avg_twk
        FROM tryout_sessions ts
        WHERE ts.status = 'selesai' AND ts.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $instansiAvg = $stmt->fetch();
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
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = $isLatihan ? "Hasil Latihan " . htmlspecialchars($latihanSubtes) : 'Hasil Try Out SKD CAT-BKN'; $activePage = $isLatihan ? 'latihan' : 'tryout'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="container" id="main-content">

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
<a href="/permen/index.php" class="btn" style="background:#7f8c8d;margin-left:.5rem">Beranda</a>
<div style="margin-top:1rem">
<a href="/permen/api/export_result.php?session_id=<?= $sessionId ?>&format=csv" class="btn" style="background:#2980b9;font-size:.8rem;padding:.5rem 1rem">📄 Export CSV</a>
<a href="javascript:window.print()" class="btn" style="background:#8e44ad;font-size:.8rem;padding:.5rem 1rem;margin-left:.5rem">🖨️ Cetak/PDF</a>
</div>
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
<h2>📊 Kelayakan SKD</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">
<strong>Passing Grade Standar BKN 2024:</strong> TKP 156, TIU 80, TWK 65, Total 301 (semua instansi menggunakan standar yang sama).
</p>
<?php
$lulusTkp = $nilaiTkp >= 156;
$lulusTiu = $nilaiTiu >= 80;
$lulusTwk = $nilaiTwk >= 65;
$lulusTotal = $totalNilai >= 301;
$lulusSemua = $lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal;
?>
<div style="background:<?= $lulusSemua ? '#d4edda' : '#fff3cd' ?>;border:1px solid <?= $lulusSemua ? '#155724' : '#856404' ?>;border-radius:6px;padding:1rem;margin-bottom:1rem">
<div style="font-weight:bold;color:#1a5276;font-size:1rem;margin-bottom:.5rem">
<?= $lulusSemua ? '✅ Memenuhi Syarat SKD' : '⚠️ Belum Memenuhi Syarat SKD' ?>
</div>
<div style="font-size:.9rem;color:#555">
<?php if ($lulusSemua): ?>
Nilai Anda memenuhi passing grade standar BKN. Anda berpotensi lolos ke tahap seleksi berikutnya untuk semua instansi.
<?php else: ?>
Anda perlu meningkatkan nilai di subtes berikut:
<?php if (!$lulusTkp) echo '<br>• TKP: kurang ' . (156 - $nilaiTkp); ?>
<?php if (!$lulusTiu) echo '<br>• TIU: kurang ' . (80 - $nilaiTiu); ?>
<?php if (!$lulusTwk) echo '<br>• TWK: kurang ' . (65 - $nilaiTwk); ?>
<?php if (!$lulusTotal) echo '<br>• Total: kurang ' . (301 - $totalNilai); ?>
<?php endif; ?>
</div>
</div>
<p style="font-size:.8rem;color:#777;text-align:center">
Sumber: BKN (Badan Kepegawaian Negara) - Seleksi Sekolah Kedinasan 2024
</p>
</div>

<!-- COMPARISON ANALYSIS (Full Tryout Only) -->
<?php if (!$isLatihan && ($comparisonData || $instansiAvg)): ?>
<div class="card" style="text-align:left">
<h2>📊 Analisis Perbandingan</h2>

<?php if ($comparisonData): ?>
<div style="background:#f8f9fa;border-radius:6px;padding:1rem;margin-bottom:1rem">
    <h3 style="color:#1a5276;font-size:.95rem;margin-bottom:.8rem">Perbandingan dengan Tryout Sebelumnya</h3>
    <p style="font-size:.8rem;color:#666;margin-bottom:.5rem">Tanggal: <?= date('d M Y', strtotime($comparisonData['previous_date'])) ?></p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.5rem">
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">Total</div>
            <div style="font-weight:bold;font-size:1.1rem;color:<?= $comparisonData['total_diff'] >= 0 ? '#27ae60' : '#e74c3c' ?>">
                <?= $comparisonData['total_diff'] >= 0 ? '+' : '' ?><?= $comparisonData['total_diff'] ?>
            </div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TKP</div>
            <div style="font-weight:bold;font-size:1.1rem;color:<?= $comparisonData['tkp_diff'] >= 0 ? '#27ae60' : '#e74c3c' ?>">
                <?= $comparisonData['tkp_diff'] >= 0 ? '+' : '' ?><?= $comparisonData['tkp_diff'] ?>
            </div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TIU</div>
            <div style="font-weight:bold;font-size:1.1rem;color:<?= $comparisonData['tiu_diff'] >= 0 ? '#27ae60' : '#e74c3c' ?>">
                <?= $comparisonData['tiu_diff'] >= 0 ? '+' : '' ?><?= $comparisonData['tiu_diff'] ?>
            </div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TWK</div>
            <div style="font-weight:bold;font-size:1.1rem;color:<?= $comparisonData['twk_diff'] >= 0 ? '#27ae60' : '#e74c3c' ?>">
                <?= $comparisonData['twk_diff'] >= 0 ? '+' : '' ?><?= $comparisonData['twk_diff'] ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($instansiAvg): ?>
<div style="background:#f8f9fa;border-radius:6px;padding:1rem">
    <h3 style="color:#1a5276;font-size:.95rem;margin-bottom:.8rem">Perbandingan dengan Rata-rata (30 hari terakhir)</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.5rem">
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">Total</div>
            <div style="font-weight:bold;font-size:1.1rem">
                <?= round($instansiAvg['avg_total'] ?? 0) ?>
            </div>
            <div style="font-size:.7rem;color:#666">Anda: <?= $totalNilai ?></div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TKP</div>
            <div style="font-weight:bold;font-size:1.1rem">
                <?= round($instansiAvg['avg_tkp'] ?? 0) ?>
            </div>
            <div style="font-size:.7rem;color:#666">Anda: <?= $nilaiTkp ?></div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TIU</div>
            <div style="font-weight:bold;font-size:1.1rem">
                <?= round($instansiAvg['avg_tiu'] ?? 0) ?>
            </div>
            <div style="font-size:.7rem;color:#666">Anda: <?= $nilaiTiu ?></div>
        </div>
        <div style="background:#fff;padding:.6rem;border-radius:4px;text-align:center">
            <div style="font-size:.75rem;color:#666">TWK</div>
            <div style="font-weight:bold;font-size:1.1rem">
                <?= round($instansiAvg['avg_twk'] ?? 0) ?>
            </div>
            <div style="font-size:.7rem;color:#666">Anda: <?= $nilaiTwk ?></div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>

<!-- REKOMENDASI LATIHAN -->
<div class="card no-print" style="text-align:left;display:none" id="rekomendasiCard">
<h2>Rekomendasi Latihan</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:.8rem">Berdasarkan hasil tryout, topik berikut perlu ditingkatkan:</p>
<div id="rekomendasiList" style="display:flex;flex-wrap:wrap;gap:.5rem"></div>
</div>

<!-- EXPORT BUTTONS (Full Tryout Mode) -->
<?php if (!$isLatihan): ?>
<div class="card no-print" style="text-align:center">
<h2>Export Hasil</h2>
<div style="display:flex;justify-content:center;gap:.5rem;flex-wrap:wrap">
<a href="/permen/api/export_result.php?session_id=<?= $sessionId ?>&format=csv" class="btn" style="background:#2980b9;font-size:.9rem;padding:.6rem 1.2rem">📄 Export CSV</a>
<a href="javascript:window.print()" class="btn" style="background:#8e44ad;font-size:.9rem;padding:.6rem 1.2rem">🖨️ Cetak/PDF</a>
<button onclick="sendEmailResult()" class="btn" style="background:#27ae60;font-size:.9rem;padding:.6rem 1.2rem">� Kirim Notifikasi</button>
</div>
</div>

<script>
async function sendEmailResult(){
    if (!confirm('Kirim hasil tryout ke notifikasi Anda?')) return;
    
    const formData = new FormData();
    formData.append('session_id', <?= $sessionId ?>);
    
    try {
        const res = await fetch('/permen/api/send_result_notification.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Hasil tryout berhasil dikirim ke notifikasi!');
        } else {
            alert(data.error || 'Gagal mengirim notifikasi');
        }
    } catch (e) {
        alert('Gagal mengirim notifikasi. Silakan coba lagi.');
    }
}
</script>
<?php endif; ?>

<!-- REVIEW SOAL -->
<div class="card" style="text-align:left">
<h2>Review Soal</h2>
<div id="reviewStats" style="margin-bottom:1rem;font-size:.9rem"></div>

<!-- Filter Options -->
<div style="margin-bottom:1rem;padding:.8rem;background:#f8f9fa;border-radius:6px">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <span style="font-weight:bold;font-size:.85rem">Filter:</span>
        <select id="filterSubtes" onchange="applyFilters()" style="padding:.4rem;border-radius:4px;border:1px solid #ddd;font-size:.85rem">
            <option value="">Semua Subtes</option>
            <option value="TWK">TWK</option>
            <option value="TIU">TIU</option>
            <option value="TKP">TKP</option>
        </select>
        <select id="filterStatus" onchange="applyFilters()" style="padding:.4rem;border-radius:4px;border:1px solid #ddd;font-size:.85rem">
            <option value="">Semua Status</option>
            <option value="benar">Benar</option>
            <option value="salah">Salah</option>
            <option value="kosong">Kosong</option>
        </select>
        <select id="filterRagu" onchange="applyFilters()" style="padding:.4rem;border-radius:4px;border:1px solid #ddd;font-size:.85rem">
            <option value="">Semua</option>
            <option value="ragu">Ragu-ragu</option>
            <option value="tidak_ragu">Tidak Ragu</option>
        </select>
        <button onclick="resetFilters()" style="padding:.4rem .8rem;background:#95a5a6;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.85rem">Reset</button>
    </div>
</div>

<div id="reviewContainer" style="max-height:500px;overflow-y:auto;border:1px solid #ddd;border-radius:6px;padding:.8rem">
<p style="color:#666">Memuat review soal...</p>
</div>
</div>

<script>
const reviewSessionId = <?= (int)$sessionId ?>;
let allQuestions = [];

async function loadReview(){
    const res = await fetch('/permen/api/get_review.php?session_id='+reviewSessionId);
    const data = await res.json();
    if(data.error){document.getElementById('reviewContainer').innerHTML='<p style="color:#e74c3c">'+data.error+'</p>';return;}

    allQuestions = data.soal || [];
    
    // Stats
    let statsHtml = '';
    for(const sub of ['TWK','TIU','TKP']){
        const s = data.stats?.[sub];
        if(s && s.total > 0){
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
    (data.soal || []).forEach(q => {
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

    // Render questions with filters
    renderQuestions(allQuestions);
}

function applyFilters() {
    const subtesFilter = document.getElementById('filterSubtes').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const raguFilter = document.getElementById('filterRagu').value;

    const filtered = allQuestions.filter(q => {
        if (subtesFilter && q.subtes !== subtesFilter) return false;
        
        const isCorrect = q.jawaban_user === q.jawaban_benar;
        const isEmpty = !q.jawaban_user;
        
        if (statusFilter === 'benar' && !isCorrect) return false;
        if (statusFilter === 'salah' && (isEmpty || isCorrect)) return false;
        if (statusFilter === 'kosong' && !isEmpty) return false;
        
        if (raguFilter === 'ragu' && !q.is_ragu) return false;
        if (raguFilter === 'tidak_ragu' && q.is_ragu) return false;
        
        return true;
    });

    renderQuestions(filtered);
}

function resetFilters() {
    document.getElementById('filterSubtes').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterRagu').value = '';
    renderQuestions(allQuestions);
}

function renderQuestions(questions) {
    let html = '';
    let currentPassage = null;
    
    if (questions.length === 0) {
        html = '<p style="color:#666;text-align:center">Tidak ada soal yang sesuai dengan filter.</p>';
        document.getElementById('reviewContainer').innerHTML = html;
        return;
    }

    questions.forEach((q,i)=>{
        const isCorrect = q.jawaban_user === q.jawaban_benar;
        const isEmpty = !q.jawaban_user;
        const statusColor = isEmpty ? '#999' : (isCorrect ? '#27ae60' : '#e74c3c');
        const statusText = isEmpty ? 'KOSONG' : (isCorrect ? 'BENAR' : 'SALAH');

        // Show passage if changed
        if(q.passage_id && q.passage_id !== currentPassage){
            currentPassage = q.passage_id;
            const p = allQuestions.find(sq => sq.passage_id === currentPassage);
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
        if(q.is_ragu) html += '<span style="font-size:.75rem;color:#f39c12;margin-left:.5rem">⚠️ Ragu</span>';
        html += '</div>';
        html += '<div style="font-size:.9rem;margin-bottom:.5rem">'+escapeHtml(q.pertanyaan)+'</div>';
        if(q.image_url){
            html += '<img src="'+escapeHtml(q.image_url)+'" alt="Gambar soal" style="max-width:100%;max-height:150px;margin:.4rem 0;border:1px solid #ddd;border-radius:4px">';
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

// Wait for DOM to be ready before loading review
document.addEventListener('DOMContentLoaded', loadReview);
</script>

<div class="card no-print" style="text-align:center">
<a href="tryout.php" class="btn">Coba Lagi</a>
<a href="latihan.php" class="btn" style="background:#27ae60;margin-left:.5rem">Latihan per Subtes</a>
<a href="materi.php?subtes=TWK" class="btn" style="background:#2980b9;margin-left:.5rem">Pelajari Materi</a>
<a href="/permen/index.php" class="btn" style="background:#7f8c8d;margin-left:.5rem">Beranda</a>
<button onclick="window.print()" class="btn" style="background:#8e44ad;margin-left:.5rem" aria-label="Simpan atau cetak hasil sebagai PDF">Simpan/Cetak PDF</button>
</div>
<?php endif; ?>

</div>
<div class="footer">
Disclaimer: Hasil ini adalah simulasi. Penentuan kelulusan sesuai aturan BKN dan Kementerian/Lembaga terkait.
</div>
</body>
</html>

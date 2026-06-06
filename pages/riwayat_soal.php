<?php
require '../config.php';
require '../helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');

// Filters
$filterSubtes = $_GET['subtes'] ?? '';
$filterTopik  = $_GET['topik']  ?? '';
$filterStatus = $_GET['status']  ?? ''; // benar, salah, kosong
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build WHERE
$where = "a.user_id = ?";
$params = [$userId];
if ($filterSubtes) { $where .= " AND q.subtes = ?"; $params[] = $filterSubtes; }
if ($filterTopik)  { $where .= " AND q.topik = ?";  $params[] = $filterTopik; }
if ($filterStatus === 'benar')  { $where .= " AND a.jawaban_user = q.jawaban_benar AND a.jawaban_user IS NOT NULL AND a.jawaban_user != ''"; }
elseif ($filterStatus === 'salah') { $where .= " AND a.jawaban_user IS NOT NULL AND a.jawaban_user != '' AND a.jawaban_user != q.jawaban_benar"; }
elseif ($filterStatus === 'kosong') { $where .= " AND (a.jawaban_user IS NULL OR a.jawaban_user = '')"; }

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM answers a JOIN questions q ON a.question_id = q.id WHERE $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch data
$query = "
    SELECT 
        a.id as answer_id, a.jawaban_user, a.skor,
        q.id as question_id, q.subtes, q.topik, q.pertanyaan,
        q.pilihan_a, q.pilihan_b, q.pilihan_c, q.pilihan_d, q.pilihan_e,
        q.jawaban_benar, q.pembahasan, q.tips_trick, q.related_links,
        ts.nama as session_nama, ts.waktu_mulai
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    JOIN tryout_sessions ts ON a.session_id = ts.id
    WHERE $where
    ORDER BY a.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Distinct subtes & topik for filters
$subtesList = $pdo->query("SELECT DISTINCT subtes FROM questions ORDER BY subtes")->fetchAll(PDO::FETCH_COLUMN);
$topikList = $pdo->query("SELECT DISTINCT topik FROM questions ORDER BY topik")->fetchAll(PDO::FETCH_COLUMN);

// Summary stats
$summary = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar,
        SUM(CASE WHEN a.jawaban_user IS NOT NULL AND a.jawaban_user != '' AND a.jawaban_user != q.jawaban_benar THEN 1 ELSE 0 END) as salah,
        SUM(CASE WHEN a.jawaban_user IS NULL OR a.jawaban_user = '' THEN 1 ELSE 0 END) as kosong
    FROM answers a JOIN questions q ON a.question_id = q.id
    WHERE a.user_id = ?
");
$summary->execute([$userId]);
$sum = $summary->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Riwayat Soal — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:1000px;margin:1.5rem auto;padding:0 1rem}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.8rem;margin-bottom:1.2rem}
.sum-card{background:#fff;border-radius:8px;padding:1rem;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.sum-card .num{font-size:1.5rem;font-weight:bold}
.sum-card.benar .num{color:#27ae60}.sum-card.salah .num{color:#e74c3c}.sum-card.kosong .num{color:#999}
.sum-card .label{font-size:.85rem;color:#555;margin-top:.3rem}
.filter-bar{background:#fff;border-radius:8px;padding:1rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.filter-bar select,.filter-bar input{padding:.4rem .6rem;border:1px solid #ddd;border-radius:5px;font-size:.9rem}
.filter-bar button{background:#2980b9;color:#fff;border:none;padding:.45rem .9rem;border-radius:5px;cursor:pointer;font-size:.9rem}
.section{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem}
.section h2{color:#1a5276;font-size:1.05rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.soal-item{border:1px solid #e0e0e0;border-radius:6px;padding:.8rem;margin-bottom:.6rem;background:#fff}
.soal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem;flex-wrap:wrap;gap:.3rem}
.soal-meta{font-size:.8rem;color:#666}
.status-badge{display:inline-block;padding:.15rem .4rem;border-radius:4px;font-size:.75rem;font-weight:bold}
.status-benar{background:#d4edda;color:#155724}.status-salah{background:#f8d7da;color:#721c24}.status-kosong{background:#f0f0f0;color:#666}
.opt{padding:.25rem .4rem;border-radius:4px;font-size:.85rem;margin:.15rem 0}
.opt-benar{background:#d4edda;border:1px solid #27ae60}.opt-salah{background:#f8d7da;border:1px solid #e74c3c}.opt-normal{background:#f8f9fa}
.pembahasan-box{margin-top:.5rem;padding:.5rem;background:#fffbea;border-left:3px solid #f1c40f;border-radius:4px;font-size:.85rem;display:none}
.pembahasan-box.show{display:block}
.tips-box{margin-top:.4rem;padding:.4rem;background:#eaf8ea;border-left:3px solid #27ae60;border-radius:4px;font-size:.82rem;display:none}
.tips-box.show{display:block}
.toggle-btn{background:none;border:none;color:#2980b9;cursor:pointer;font-size:.85rem;padding:0;text-decoration:underline}
.pagination{display:flex;gap:.3rem;justify-content:center;margin-top:1rem;flex-wrap:wrap}
.pagination a,.pagination span{display:inline-block;padding:.3rem .6rem;border-radius:4px;font-size:.85rem;text-decoration:none}
.pagination a{background:#eaf2f8;color:#1a5276}.pagination a:hover{background:#2980b9;color:#fff}
.pagination span{background:#2980b9;color:#fff}
.empty{color:#777;font-style:italic;text-align:center;padding:2rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:600px){.filter-bar{flex-direction:column;align-items:stretch}.filter-bar>*{width:100%}}
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Riwayat Soal — SKD CAT-BKN'; $activePage = 'latihan'; ?>
<?php require '../includes/navigation.php'; ?>

<div class="container" id="main-content">
<!-- Summary -->
<div class="summary">
    <div class="sum-card benar"><div class="num"><?= $sum['benar'] ?? 0 ?></div><div class="label">Benar</div></div>
    <div class="sum-card salah"><div class="num"><?= $sum['salah'] ?? 0 ?></div><div class="label">Salah</div></div>
    <div class="sum-card kosong"><div class="num"><?= $sum['kosong'] ?? 0 ?></div><div class="label">Kosong</div></div>
    <div class="sum-card"><div class="num"><?= $sum['total'] ?? 0 ?></div><div class="label">Total Dijawab</div></div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;flex:1;align-items:center">
        <select name="subtes">
            <option value="">Semua Subtes</option>
            <?php foreach ($subtesList as $s): ?>
            <option value="<?= $s ?>" <?= $filterSubtes === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <select name="topik">
            <option value="">Semua Topik</option>
            <?php foreach ($topikList as $t): ?>
            <option value="<?= e($t) ?>" <?= $filterTopik === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Semua Status</option>
            <option value="benar" <?= $filterStatus === 'benar' ? 'selected' : '' ?>>Benar</option>
            <option value="salah" <?= $filterStatus === 'salah' ? 'selected' : '' ?>>Salah</option>
            <option value="kosong" <?= $filterStatus === 'kosong' ? 'selected' : '' ?>>Kosong</option>
        </select>
        <button type="submit" aria-label="Filter riwayat soal">Filter</button>
        <a href="riwayat_soal.php" style="color:#666;font-size:.9rem;text-decoration:none;margin-left:.3rem">Reset</a>
    </form>
</div>

<!-- List -->
<div class="section">
<h2>Soal yang Pernah Dikerjakan (<?= $totalRows ?> soal)</h2>
<?php if (empty($riwayat)): ?>
<div class="empty">Belum ada riwayat soal. Yuk mulai latihan!</div>
<?php else: ?>
<?php foreach ($riwayat as $r):
    $isBenar = $r['jawaban_user'] === $r['jawaban_benar'];
    $isKosong = empty($r['jawaban_user']);
    $statusClass = $isKosong ? 'status-kosong' : ($isBenar ? 'status-benar' : 'status-salah');
    $statusText = $isKosong ? 'KOSONG' : ($isBenar ? 'BENAR' : 'SALAH');
    $links = [];
    if ($r['related_links']) { try { $links = json_decode($r['related_links'], true) ?: []; } catch(Throwable $e){} }
?>
<div class="soal-item">
    <div class="soal-header">
        <div>
            <strong>[<?= $r['subtes'] ?>] <?= e($r['topik']) ?></strong>
            <span class="soal-meta"> | <?= date('d M Y', strtotime($r['waktu_mulai'])) ?> | <?= e($r['session_nama']) ?></span>
        </div>
        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
    </div>
    <div style="font-size:.9rem;margin:.3rem 0 .5rem"><?= e($r['pertanyaan']) ?></div>
    <?php foreach (['A','B','C','D','E'] as $opt):
        $optText = $r['pilihan_'.strtolower($opt)];
        $isKey = $r['jawaban_benar'] === $opt;
        $isUser = $r['jawaban_user'] === $opt;
        $optClass = $isKey ? 'opt-benar' : ($isUser ? 'opt-salah' : 'opt-normal');
    ?>
    <div class="opt <?= $optClass ?>"><?= $opt ?>. <?= e($optText) ?>
        <?php if ($isKey): ?><span style="color:#27ae60;font-weight:bold"> (KUNCI)</span><?php endif; ?>
        <?php if ($isUser && !$isKey): ?><span style="color:#e74c3c"> &larr; Jawaban Anda</span><?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:.4rem">
        <button class="toggle-btn" onclick="togglePembahasan(<?= $r['answer_id'] ?>)" aria-label="Lihat pembahasan dan tips untuk soal ini">Lihat Pembahasan & Tips</button>
        <a href="materi.php?subtes=<?= $r['subtes'] ?>" style="font-size:.85rem;color:#2980b9;text-decoration:none;margin-left:.5rem">Latih Topik Ini &rarr;</a>
    </div>

    <div id="pemb-<?= $r['answer_id'] ?>" class="pembahasan-box">
        <strong>Pembahasan:</strong> <?= e($r['pembahasan']) ?><br>
        <?php if ($r['tips_trick']): ?>
        <strong style="color:#1e8449">Tips & Trick:</strong> <?= e($r['tips_trick']) ?><br>
        <?php endif; ?>
        <?php if (!empty($links)): ?>
        <strong style="color:#1a5276">Pelajari lebih lanjut:</strong>
        <?php foreach ($links as $l): ?>
        <a href="<?= e($l['url']) ?>" target="_blank" style="color:#2980b9;text-decoration:none;background:#eaf2f8;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem;font-size:.8rem"><?= e($l['label']) ?></a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">&laquo; Prev</a><?php endif; ?>
    <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
        <?php if ($p == $page): ?><span><?= $p ?></span>
        <?php else: ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">Next &raquo;</a><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>

<script>
function togglePembahasan(id){
    const el = document.getElementById('pemb-' + id);
    el.classList.toggle('show');
}
</script>

<div class="footer">
Riwayat Soal SKD CAT-BKN | Pelajari dari setiap kesalahan
</div>
</body>
</html>

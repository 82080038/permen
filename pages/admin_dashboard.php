<?php
require '../config.php';
require '../helpers.php';

// Guard: only admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminName = e($_SESSION['user_nama'] ?? 'Admin');

// Stats
$stats = [];
$stats['total_soal'] = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$stats['total_tryout'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions")->fetchColumn();
$stats['tryout_selesai'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions WHERE status='selesai'")->fetchColumn();

// Users list with pagination
$usersLimit = min(50, max(10, (int)($_GET['users_limit'] ?? 20)));
$usersOffset = (int)($_GET['users_offset'] ?? 0);

// Get total count
$usersTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

// Get paginated users
$users = $pdo->prepare("SELECT id, nama, no_hp, sekolah_asal, tahun_tamat, instansi, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT ? OFFSET ?");
$users->execute([$usersLimit, $usersOffset]);
$users = $users->fetchAll();

// Calculate pagination metadata
$usersTotalPages = ceil($usersTotal / $usersLimit);
$usersCurrentPage = floor($usersOffset / $usersLimit) + 1;
$usersHasNext = $usersCurrentPage < $usersTotalPages;
$usersHasPrev = $usersCurrentPage > 1;

// Recent tryouts with pagination
$tryoutsLimit = min(50, max(10, (int)($_GET['tryouts_limit'] ?? 20)));
$tryoutsOffset = (int)($_GET['tryouts_offset'] ?? 0);

// Get total count
$tryoutsTotal = $pdo->query("SELECT COUNT(*) FROM tryout_sessions")->fetchColumn();

// Get paginated tryouts
$tryouts = $pdo->prepare("SELECT ts.id, ts.nama, u.nama as peserta, ts.total_nilai, ts.status, ts.waktu_mulai 
    FROM tryout_sessions ts LEFT JOIN users u ON ts.user_id = u.id 
    ORDER BY ts.id DESC LIMIT ? OFFSET ?");
$tryouts->execute([$tryoutsLimit, $tryoutsOffset]);
$tryouts = $tryouts->fetchAll();

// Calculate pagination metadata
$tryoutsTotalPages = ceil($tryoutsTotal / $tryoutsLimit);
$tryoutsCurrentPage = floor($tryoutsOffset / $tryoutsLimit) + 1;
$tryoutsHasNext = $tryoutsCurrentPage < $tryoutsTotalPages;
$tryoutsHasPrev = $tryoutsCurrentPage > 1;

// Soal per subtes
$soalPerSubtes = $pdo->query("SELECT subtes, COUNT(*) as jumlah FROM questions GROUP BY subtes")->fetchAll(PDO::FETCH_KEY_PAIR);

// Subtes config
$subtesConfig = $pdo->query("SELECT * FROM subtes_config ORDER BY urutan")->fetchAll();

// Analytics data
// User registration trend (last 30 days)
$userTrend = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE role='user' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

// Tryout completion rate
$tryoutStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) as completed,
        AVG(CASE WHEN status='selesai' THEN total_nilai ELSE NULL END) as avg_score
    FROM tryout_sessions
")->fetch();

// Average scores by subtes
$avgScores = $pdo->query("
    SELECT 
        AVG(nilai_tkp) as avg_tkp,
        AVG(nilai_tiu) as avg_tiu,
        AVG(nilai_twk) as avg_twk
    FROM tryout_sessions 
    WHERE status='selesai'
")->fetch();

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
        $subtesConfig = $pdo->query("SELECT * FROM subtes_config ORDER BY urutan")->fetchAll();
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
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:1200px;margin:1.5rem auto;padding:0 1rem}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:8px;padding:1rem;box-shadow:0 2px 6px rgba(0,0,0,.08);text-align:center}
.stat .num{font-size:1.7rem;font-weight:bold;color:#2980b9}
.stat .label{color:#555;font-size:.85rem;margin-top:.3rem}
.section{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;overflow:hidden}
.section h2{color:#1a5276;font-size:1.05rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:500px}
th,td{border:1px solid #eee;padding:.4rem .5rem;text-align:left}
th{background:#f8f9fa;color:#555}
tr:hover{background:#f8f9fa}
.badge{display:inline-block;padding:.25rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold}
.badge.selesai{background:#d4edda;color:#155724}
.badge.berjalan{background:#fff3cd;color:#856404}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.45rem .7rem;border-radius:5px;text-decoration:none;font-size:.85rem;margin-right:.3rem;min-height:36px;min-width:44px}
.btn.danger{background:#e74c3c}
.btn.success{background:#27ae60}
.nav-tabs{display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;overflow-x:auto;-webkit-overflow-scrolling:touch}
.nav-tabs a{padding:.45rem .8rem;background:#eaf2f8;color:#1a5276;text-decoration:none;border-radius:5px;font-size:.85rem;font-weight:600;white-space:nowrap;min-height:36px;display:flex;align-items:center}
.nav-tabs a:hover,.nav-tabs a.active{background:#2980b9;color:#fff}
#soalForm{display:none}
.form-group{margin-bottom:.8rem}
.form-group label{display:block;font-size:.85rem;color:#555;margin-bottom:.3rem;font-weight:600}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:.5rem;border:1px solid #ddd;border-radius:5px;font-size:.9rem}
.form-group textarea{min-height:60px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:600px){
.grid-2{grid-template-columns:1fr}
.stats{grid-template-columns:repeat(2,1fr)}
.section{padding:1rem}
}
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="header">
<h1>Dashboard Admin — SKD CAT-BKN</h1>
<div>
<a href="../index.php">Beranda</a>
<a href="user_dashboard.php">User View</a>
<a href="../api/logout.php">Logout</a>
</div>
</div>

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
<a href="#users" onclick="showTab('users')" id="tab-users" class="active">Peserta</a>
<a href="#tryouts" onclick="showTab('tryouts')" id="tab-tryouts">Riwayat Tryout</a>
<a href="#soal" onclick="showTab('soal')" id="tab-soal">Kelola Soal</a>
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
<div>
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

<div id="panel-users" class="section">
<h2>Daftar Peserta</h2>
<p style="font-size:.85rem;color:#666;margin-bottom:.5rem">Total: <?= $usersTotal ?> peserta</p>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Nama</th><th>No. HP</th><th>Sekolah</th><th>Instansi</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= e($u['nama']) ?></td>
<td><?= e($u['no_hp'] ?? '-') ?></td>
<td><?= e($u['sekolah_asal'] ?? '-') ?><?= $u['tahun_tamat'] ? ' (' . $u['tahun_tamat'] . ')' : '' ?></td>
<td><?= e($u['instansi'] ?? '-') ?></td>
<td><?= $u['created_at'] ?></td>
<td>
<button onclick="resetUserPassword(<?= $u['id'] ?>, '<?= e($u['nama']) ?>')" style="background:#e74c3c;color:#fff;border:none;padding:.3rem .6rem;border-radius:4px;cursor:pointer;font-size:.75rem" aria-label="Reset password untuk <?= e($u['nama']) ?>">Reset Password</button>
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
<a href="../api/export_csv.php?type=tryouts" class="btn success" style="margin-bottom:1rem">Export CSV</a>
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

<div id="panel-soal" class="section" style="display:none">
<h2>Kelola Soal</h2>
<p>Total soal: <?= $stats['total_soal'] ?> (TWK: <?= $soalPerSubtes['TWK'] ?? 0 ?>, TIU: <?= $soalPerSubtes['TIU'] ?? 0 ?>, TKP: <?= $soalPerSubtes['TKP'] ?? 0 ?>)</p>
<div style="margin-bottom:1rem">
<a href="../api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=5" target="_blank" class="btn success">+ Generate Soal (Smart)</a>
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
    <button class="btn" onclick="loadSoalList()" style="font-size:.85rem" aria-label="Cari soal">Cari</button>
</div>

<!-- Daftar Soal -->
<div id="soalList" style="max-height:500px;overflow-y:auto">
    <p style="color:#666;font-size:.9rem">Klik "Cari" untuk memuat daftar soal.</p>
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

<script>
function showTab(id){
    ['analytics','feedback','users','tryouts','soal','generator','config'].forEach(t=>{
        document.getElementById('panel-'+t).style.display='none';
        document.getElementById('tab-'+t).classList.remove('active');
    });
    document.getElementById('panel-'+id).style.display='block';
    document.getElementById('tab-'+id).classList.add('active');
    if(id==='soal') loadSoalList();
    if(id==='feedback') loadFeedback();
}

// --- FEEDBACK MANAGEMENT ---
async function loadFeedback(){
    const status = document.getElementById('filterStatus').value;
    const category = document.getElementById('filterCategory').value;
    
    const params = new URLSearchParams();
    if(status) params.append('status', status);
    if(category) params.append('category', category);
    
    try {
        const res = await fetch('../api/get_feedback.php?' + params.toString());
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
        const res = await fetch('../api/update_feedback.php', {
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
    topikMap[subtes].forEach(t=>{
        const opt = document.createElement('option');
        opt.value = t.v;
        opt.textContent = t.v;
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
        const url = '../api/generate_soal_smart.php?subtes=' + encodeURIComponent(subtes)
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
updateGenTopik();

// --- UPLOAD GAMBAR ---
async function uploadGambar(){
    const input = document.getElementById('gambarInput');
    if(!input.files[0]){ alert('Pilih file gambar terlebih dahulu'); return; }
    const form = new FormData();
    form.append('gambar', input.files[0]);
    const res = await fetch('../api/upload_image.php', {method:'POST', body:form});
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
    const container = document.getElementById('soalList');
    container.innerHTML = '<p style="color:#666">Memuat...</p>';

    let url = '../api/list_soal.php?limit=50';
    if(keyword) url += '&q=' + encodeURIComponent(keyword);
    if(subtes) url += '&subtes=' + encodeURIComponent(subtes);

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
                html += '<img src="' + escapeHtml(s.image_url) + '" style="max-width:100%;max-height:100px;border:1px solid #ddd;border-radius:4px;margin-bottom:.4rem">';
            }
            html += '<div style="font-size:.8rem;color:#555">Kunci: <strong>' + s.jawaban_benar + '</strong></div>';
            // Revision & visibility badges
            if(s.needs_revision || s.revision_status){
                html += '<div style="margin-top:.3rem;font-size:.75rem">';
                if(s.needs_revision) html += '<span style="background:#fff3cd;color:#856404;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem">Perlu Revisi</span>';
                if(s.revision_status) html += '<span style="background:#d4edda;color:#155724;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem">' + escapeHtml(s.revision_status) + '</span>';
                html += '</div>';
            }
            html += '<div style="margin-top:.5rem;display:flex;gap:.3rem;flex-wrap:wrap">';
            html += '<button class="btn" style="font-size:.8rem;padding:.3rem .5rem" onclick="openEditModal(' + s.id + ')">Edit</button>';
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
        const res = await fetch('../api/list_soal.php?needs_revision=1&limit=50');
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
        const res = await fetch('../api/update_revision.php', {
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
        const res = await fetch('../api/update_revision.php', {
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
    const res = await fetch('../api/get_soal_detail.php?id=' + id);
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
        imgDiv.innerHTML = '<img src="' + escapeHtml(s.image_url) + '" style="max-width:150px;max-height:100px;border:1px solid #ddd;border-radius:4px"><div style="font-size:.75rem;color:#666">' + escapeHtml(s.image_url) + '</div>';
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

    const res = await fetch('../api/update_soal.php', {method:'POST', body:form});
    const data = await res.json();
    if(data.success){
        alert('Soal berhasil diperbarui!');
        closeEditModal();
        loadSoalList();
    } else {
        alert('Gagal: ' + (data.error || 'Unknown error'));
    }
}

function escapeHtml(text){
    if(!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function resetUserPassword(userId, userName){
    if(!confirm(`Reset password untuk user "${userName}"? Password baru akan di-generate otomatis dan dikirim ke user via notifikasi.`)) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        const res = await fetch('../api/reset_user_password.php', {
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
</script>
</body>
</html>

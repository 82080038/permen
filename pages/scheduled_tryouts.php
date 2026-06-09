<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $scheduledTryoutId = (int)$_POST['scheduled_tryout_id'];
    
    // Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM scheduled_tryout_registrations WHERE scheduled_tryout_id=? AND user_id=?");
    $stmt->execute([$scheduledTryoutId, $userId]);
    if (!$stmt->fetch()) {
        // Check quota
        $stmt = $pdo->prepare("SELECT kuota, (SELECT COUNT(*) FROM scheduled_tryout_registrations WHERE scheduled_tryout_id=?) as registered FROM scheduled_tryouts WHERE id=? AND status='published'");
        $stmt->execute([$scheduledTryoutId, $scheduledTryoutId]);
        $st = $stmt->fetch();
        
        if ($st && $st['registered'] < $st['kuota']) {
            $stmt = $pdo->prepare("INSERT INTO scheduled_tryout_registrations (scheduled_tryout_id, user_id) VALUES (?, ?)");
            $stmt->execute([$scheduledTryoutId, $userId]);
        }
    }
    header('Location: scheduled_tryouts.php');
    exit;
}

// Fetch available scheduled tryouts (published, not started yet)
try {
    $stmt = $pdo->prepare("SELECT s.*, 
        (SELECT COUNT(*) FROM scheduled_tryout_registrations r WHERE r.scheduled_tryout_id = s.id) as registered_count, 
        (SELECT COUNT(*) FROM scheduled_tryout_registrations r WHERE r.scheduled_tryout_id = s.id AND r.user_id = ?) as user_registered 
        FROM scheduled_tryouts s 
        WHERE s.status = 'published' AND s.waktu_mulai > NOW() 
        ORDER BY s.waktu_mulai ASC");
    $stmt->execute([$userId]);
    $availableTryouts = $stmt->fetchAll();
} catch (Throwable $e) {
    $availableTryouts = [];
}

// Fetch user's registered tryouts
try {
    $stmt = $pdo->prepare("SELECT s.*, r.status as registration_status, r.registered_at 
        FROM scheduled_tryout_registrations r 
        JOIN scheduled_tryouts s ON r.scheduled_tryout_id = s.id 
        WHERE r.user_id = ? 
        ORDER BY s.waktu_mulai DESC");
    $stmt->execute([$userId]);
    $registeredTryouts = $stmt->fetchAll();
} catch (Throwable $e) {
    $registeredTryouts = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<base href="/permen/">
<title>Scheduled Tryouts — SKD CAT-BKN</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Scheduled Tryouts — SKD CAT-BKN'; $activePage = 'scheduled_tryouts'; ?>
<?php require '../includes/navigation.php'; ?>
<style>
.st-card{border:1px solid #e0e0e0;border-radius:10px;padding:1.5rem;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;flex-direction:column;gap:.5rem}
.st-card h3{margin:0;color:#1a5276;font-size:1.1rem}
.st-card .st-desc{color:#666;font-size:.9rem;line-height:1.5;flex:1}
.st-card .st-meta{display:flex;flex-wrap:wrap;gap:.5rem 1.2rem;font-size:.88rem;color:#555;margin:.3rem 0}
.st-card .st-meta span{display:flex;align-items:center;gap:.3rem}
.st-quota-bar{background:#eee;border-radius:4px;height:6px;margin:.3rem 0 .6rem}
.st-quota-fill{height:6px;border-radius:4px;background:#27ae60;transition:width .3s}
.st-quota-fill.warn{background:#e67e22}
.st-quota-fill.full{background:#e74c3c}
.empty-state{text-align:center;padding:3rem 1rem;color:#888;background:#fff;border-radius:10px;border:2px dashed #ddd}
.empty-state .icon{font-size:3rem;margin-bottom:.8rem}
.empty-state p{margin:.4rem 0}
.section-header{display:flex;justify-content:space-between;align-items:center;margin:1.5rem 0 1rem;flex-wrap:wrap;gap:.5rem}
.section-header h2{margin:0;color:#1a5276;font-size:1.2rem}
.badge{display:inline-block;padding:.2rem .55rem;border-radius:12px;font-size:.78rem;font-weight:bold;color:#fff}
.badge-registered{background:#3498db}.badge-confirmed{background:#27ae60}.badge-cancelled{background:#e74c3c}
</style>

<div class="container" id="main-content" style="max-width:900px;padding:1rem">

<div style="background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem">
    <h1 style="margin:0 0 .4rem;font-size:1.5rem">Scheduled Tryouts</h1>
    <p style="margin:0;opacity:.85;font-size:.95rem">Daftar dan ikuti try out resmi terjadwal bersama peserta lain secara serentak</p>
    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <a href="admin_scheduled_tryouts.php" style="display:inline-block;margin-top:.8rem;padding:.45rem 1rem;background:rgba(255,255,255,.2);color:#fff;border-radius:5px;text-decoration:none;font-size:.9rem;border:1px solid rgba(255,255,255,.4)">
        + Kelola Scheduled Tryouts
    </a>
    <?php endif; ?>
</div>

<?php
function formatTanggal($dt) {
    $ts = strtotime($dt);
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return $hari[date('w',$ts)] . ', ' . date('j',$ts) . ' ' . $bulan[(int)date('n',$ts)] . ' ' . date('Y',$ts) . ' pukul ' . date('H:i',$ts);
}
function hitung_sisa($waktu_mulai) {
    $diff = strtotime($waktu_mulai) - time();
    if ($diff <= 0) return 'Sudah mulai';
    $hari = floor($diff / 86400);
    $jam  = floor(($diff % 86400) / 3600);
    if ($hari > 0) return "dalam {$hari} hari {$jam} jam";
    $menit = floor(($diff % 3600) / 60);
    if ($jam > 0) return "dalam {$jam} jam {$menit} menit";
    return "dalam {$menit} menit";
}
?>

<!-- Tryout Tersedia -->
<div class="section-header">
    <h2>Tryout Tersedia <span style="color:#666;font-size:.9rem;font-weight:normal">(<?= count($availableTryouts) ?> event)</span></h2>
</div>

<?php if (empty($availableTryouts)): ?>
<div class="empty-state">
    <div class="icon">📅</div>
    <p style="font-size:1.05rem;font-weight:bold;color:#555">Belum ada scheduled tryout yang tersedia</p>
    <p>Cek kembali nanti — admin akan segera menambahkan event baru.</p>
    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <a href="admin_scheduled_tryouts.php" style="display:inline-block;margin-top:1rem;padding:.6rem 1.4rem;background:#1a5276;color:#fff;border-radius:6px;text-decoration:none">+ Buat Scheduled Tryout</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:1.5rem">
<?php foreach ($availableTryouts as $st):
    $pct = $st['kuota'] > 0 ? min(100, round($st['registered_count'] / $st['kuota'] * 100)) : 0;
    $fillClass = $pct >= 100 ? 'full' : ($pct >= 75 ? 'warn' : '');
?>
<div class="st-card">
    <h3><?= e($st['nama']) ?></h3>
    <p class="st-desc"><?= e($st['deskripsi']) ?></p>
    <div class="st-meta">
        <span>🕒 <?= formatTanggal($st['waktu_mulai']) ?></span>
        <span>⏱ <?= (int)$st['durasi_menit'] ?> menit</span>
        <span style="color:#2980b9;font-style:italic"><?= hitung_sisa($st['waktu_mulai']) ?></span>
    </div>
    <div>
        <div style="display:flex;justify-content:space-between;font-size:.83rem;color:#666;margin-bottom:2px">
            <span>Kuota: <?= (int)$st['registered_count'] ?> / <?= (int)$st['kuota'] ?> peserta</span>
            <span><?= $pct ?>%</span>
        </div>
        <div class="st-quota-bar"><div class="st-quota-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div></div>
    </div>
    <?php if ($st['user_registered']): ?>
    <button disabled style="width:100%;padding:.7rem;background:#95a5a6;color:#fff;border:none;border-radius:5px;cursor:not-allowed;font-size:.95rem">✓ Sudah Terdaftar</button>
    <?php elseif ($st['registered_count'] >= $st['kuota']): ?>
    <button disabled style="width:100%;padding:.7rem;background:#e74c3c;color:#fff;border:none;border-radius:5px;cursor:not-allowed;font-size:.95rem">Kuota Penuh</button>
    <?php else: ?>
    <form method="POST">
    <?php if (function_exists('csrfToken')): ?><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><?php endif; ?>
    <input type="hidden" name="scheduled_tryout_id" value="<?= (int)$st['id'] ?>">
    <input type="hidden" name="register" value="1">
    <button type="submit" style="width:100%;padding:.7rem;background:#27ae60;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:.95rem;font-weight:bold">Daftar Sekarang</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Tryout Terdaftar -->
<div class="section-header">
    <h2>Tryout Terdaftar Saya <span style="color:#666;font-size:.9rem;font-weight:normal">(<?= count($registeredTryouts) ?> event)</span></h2>
</div>

<?php if (empty($registeredTryouts)): ?>
<div class="empty-state">
    <div class="icon">📋</div>
    <p style="font-size:1.05rem;font-weight:bold;color:#555">Belum ada tryout yang Anda daftarkan</p>
    <p>Daftar ke salah satu event di atas untuk mulai latihan bersama.</p>
</div>
<?php else: ?>
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.06)">
<thead>
<tr style="background:#1a5276;color:#fff">
<th style="padding:12px 10px;text-align:left">Nama Tryout</th>
<th style="padding:12px 10px;text-align:left">Waktu Mulai</th>
<th style="padding:12px 10px;text-align:center">Durasi</th>
<th style="padding:12px 10px;text-align:center">Status</th>
<th style="padding:12px 10px;text-align:center">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($registeredTryouts as $i => $st): ?>
<tr style="background:<?= $i%2===0?'#fff':'#f8f9fa' ?>;border-bottom:1px solid #eee">
<td style="padding:10px;font-weight:bold;color:#1a5276"><?= e($st['nama']) ?></td>
<td style="padding:10px;font-size:.88rem"><?= formatTanggal($st['waktu_mulai']) ?></td>
<td style="padding:10px;text-align:center;font-size:.88rem"><?= (int)$st['durasi_menit'] ?> mnt</td>
<td style="padding:10px;text-align:center">
<?php
$badge = match($st['registration_status'] ?? 'registered') {
    'confirmed' => ['badge-confirmed','Confirmed'],
    'cancelled' => ['badge-cancelled','Dibatalkan'],
    default     => ['badge-registered','Terdaftar'],
};
?>
<span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
</td>
<td style="padding:10px;text-align:center">
<?php if ($st['waktu_mulai'] <= date('Y-m-d H:i:s') && ($st['registration_status'] ?? '') !== 'cancelled'): ?>
<a href="tryout.php?scheduled=<?= (int)$st['id'] ?>" style="display:inline-block;padding:.4rem .9rem;background:#27ae60;color:#fff;border-radius:5px;text-decoration:none;font-size:.88rem;font-weight:bold">Mulai</a>
<?php else: ?>
<span style="color:#888;font-size:.85rem"><?= hitung_sisa($st['waktu_mulai']) ?></span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

</div>
</body>
</html>

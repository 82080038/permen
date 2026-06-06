<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
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
$stmt = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM scheduled_tryout_registrations r WHERE r.scheduled_tryout_id = s.id) as registered_count, 
    (SELECT COUNT(*) FROM scheduled_tryout_registrations r WHERE r.scheduled_tryout_id = s.id AND r.user_id = ?) as user_registered 
    FROM scheduled_tryouts s 
    WHERE s.status = 'published' AND s.waktu_mulai > NOW() 
    ORDER BY s.waktu_mulai ASC");
$availableTryouts = $stmt->fetchAll();

// Fetch user's registered tryouts
$stmt = $pdo->prepare("SELECT s.*, r.status as registration_status, r.registered_at 
    FROM scheduled_tryout_registrations r 
    JOIN scheduled_tryouts s ON r.scheduled_tryout_id = s.id 
    WHERE r.user_id = ? 
    ORDER BY s.waktu_mulai DESC");
$stmt->execute([$userId]);
$registeredTryouts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Scheduled Tryouts — SKD CAT-BKN</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require '../includes/navigation.php'; ?>
<div class="container">
<h1>Scheduled Tryouts</h1>

<h2>Tryout Tersedia</h2>
<?php if (empty($availableTryouts)): ?>
<p style="color:#777">Tidak ada scheduled tryout yang tersedia saat ini.</p>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;margin-bottom:2rem">
<?php foreach ($availableTryouts as $st): ?>
<div style="border:1px solid #ddd;border-radius:8px;padding:1.5rem;background:#fff">
<h3 style="margin-top:0"><?= e($st['nama']) ?></h3>
<p style="color:#777;font-size:.9rem;margin-bottom:1rem"><?= e($st['deskripsi']) ?></p>
<div style="margin-bottom:.5rem"><strong>Waktu:</strong> <?= $st['waktu_mulai'] ?></div>
<div style="margin-bottom:.5rem"><strong>Durasi:</strong> <?= $st['durasi_menit'] ?> menit</div>
<div style="margin-bottom:1rem"><strong>Kuota:</strong> <?= $st['registered_count'] ?> / <?= $st['kuota'] ?></div>
<?php if ($st['user_registered']): ?>
<button disabled style="width:100%;padding:.75rem;background:#95a5a6;color:#fff;border:none;border-radius:4px;cursor:not-allowed">Sudah Terdaftar</button>
<?php elseif ($st['registered_count'] >= $st['kuota']): ?>
<button disabled style="width:100%;padding:.75rem;background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:not-allowed">Kuota Penuh</button>
<?php else: ?>
<form method="POST">
<input type="hidden" name="scheduled_tryout_id" value="<?= $st['id'] ?>">
<input type="hidden" name="register" value="1">
<button type="submit" style="width:100%;padding:.75rem;background:#27ae60;color:#fff;border:none;border-radius:4px;cursor:pointer">Daftar Sekarang</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h2>Tryout Terdaftar</h2>
<?php if (empty($registeredTryouts)): ?>
<p style="color:#777">Anda belum terdaftar di scheduled tryout manapun.</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse">
<thead>
<tr style="background:#f8f9fa">
<th style="padding:10px;text-align:left;border:1px solid #ddd">Nama</th>
<th style="padding:10px;text-align:left;border:1px solid #ddd">Waktu Mulai</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Status</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Terdaftar Pada</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($registeredTryouts as $st): ?>
<tr>
<td style="padding:10px;border:1px solid #ddd"><?= e($st['nama']) ?></td>
<td style="padding:10px;border:1px solid #ddd"><?= $st['waktu_mulai'] ?></td>
<td style="padding:10px;text-align:center;border:1px solid #ddd">
<span style="padding:4px 8px;border-radius:3px;font-size:.85rem;background:<?= $st['registration_status'] === 'registered' ? '#3498db' : '#27ae60' ?>;color:#fff"><?= $st['registration_status'] ?></span>
</td>
<td style="padding:10px;border:1px solid #ddd"><?= $st['registered_at'] ?></td>
<td style="padding:10px;text-align:center;border:1px solid #ddd">
<?php if ($st['waktu_mulai'] <= date('Y-m-d H:i:s') && $st['registration_status'] === 'registered'): ?>
<a href="tryout.php?scheduled=<?= $st['id'] ?>" class="btn" style="background:#27ae60;color:#fff;padding:.5rem 1rem;text-decoration:none;border-radius:4px">Mulai Tryout</a>
<?php else: ?>
<span style="color:#777">Belum mulai</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</body>
</html>

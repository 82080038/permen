<?php
require '../config.php';
require '../helpers.php';

// Guard: only admin
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle form submission for creating/editing scheduled tryout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $waktu_mulai = $_POST['waktu_mulai'] ?? '';
    $durasi_menit = (int)($_POST['durasi_menit'] ?? 90);
    $kuota = (int)($_POST['kuota'] ?? 100);
    $status = $_POST['status'] ?? 'draft';
    $id = (int)($_POST['id'] ?? 0);

    if ($nama && $waktu_mulai) {
        if ($id > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE scheduled_tryouts SET nama=?, deskripsi=?, waktu_mulai=?, durasi_menit=?, kuota=?, status=? WHERE id=?");
            $stmt->execute([$nama, $deskripsi, $waktu_mulai, $durasi_menit, $kuota, $status, $id]);
        } else {
            // Create new
            $stmt = $pdo->prepare("INSERT INTO scheduled_tryouts (nama, deskripsi, waktu_mulai, durasi_menit, kuota, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $deskripsi, $waktu_mulai, $durasi_menit, $kuota, $status]);
        }
        header('Location: admin_scheduled_tryouts.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM scheduled_tryouts WHERE id=?");
    $stmt->execute([$id]);
    header('Location: admin_scheduled_tryouts.php');
    exit;
}

// Fetch all scheduled tryouts
$stmt = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM scheduled_tryout_registrations r WHERE r.scheduled_tryout_id = s.id) as registered_count FROM scheduled_tryouts s ORDER BY waktu_mulai DESC");
$scheduledTryouts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<base href="/">
<title>Kelola Scheduled Tryouts — Admin SKD CAT-BKN</title>
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/style.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Kelola Scheduled Tryouts — Admin SKD CAT-BKN'; $activePage = 'admin_scheduled_tryouts'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="container">
<h1>Kelola Scheduled Tryouts</h1>

<div style="margin-bottom:2rem">
<button onclick="document.getElementById('formModal').style.display='block'" class="btn" style="background:#27ae60;color:#fff">+ Buat Scheduled Tryout Baru</button>
</div>

<table style="width:100%;border-collapse:collapse">
<thead>
<tr style="background:#f8f9fa">
<th style="padding:10px;text-align:left;border:1px solid #ddd">Nama</th>
<th style="padding:10px;text-align:left;border:1px solid #ddd">Waktu Mulai</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Durasi</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Kuota</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Terdaftar</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Status</th>
<th style="padding:10px;text-align:center;border:1px solid #ddd">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($scheduledTryouts as $st): ?>
<tr>
<td style="padding:10px;border:1px solid #ddd"><?= e($st['nama']) ?></td>
<td style="padding:10px;border:1px solid #ddd"><?= $st['waktu_mulai'] ?></td>
<td style="padding:10px;text-align:center;border:1px solid #ddd"><?= $st['durasi_menit'] ?> menit</td>
<td style="padding:10px;text-align:center;border:1px solid #ddd"><?= $st['kuota'] ?></td>
<td style="padding:10px;text-align:center;border:1px solid #ddd"><?= $st['registered_count'] ?></td>
<td style="padding:10px;text-align:center;border:1px solid #ddd">
<span style="padding:4px 8px;border-radius:3px;font-size:.85rem;background:<?= $st['status'] === 'published' ? '#27ae60' : ($st['status'] === 'draft' ? '#95a5a6' : '#e74c3c') ?>;color:#fff"><?= $st['status'] ?></span>
</td>
<td style="padding:10px;text-align:center;border:1px solid #ddd">
<a href="?edit=<?= $st['id'] ?>" style="color:#2980b9;margin-right:10px">Edit</a>
<a href="?delete=<?= $st['id'] ?>" onclick="return confirm('Yakin ingin menghapus?')" style="color:#e74c3c">Hapus</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Modal Form -->
<div id="formModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000">
<div style="background:#fff;padding:2rem;width:90%;max-width:500px;margin:100px auto;border-radius:8px">
<h2 id="modalTitle">Buat Scheduled Tryout</h2>
<form method="POST">
<input type="hidden" name="id" id="formId" value="0">
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Nama</label>
<input type="text" name="nama" id="formNama" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
</div>
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Deskripsi</label>
<textarea name="deskripsi" id="formDeskripsi" rows="3" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px"></textarea>
</div>
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Waktu Mulai</label>
<input type="datetime-local" name="waktu_mulai" id="formWaktuMulai" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
</div>
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Durasi (menit)</label>
<input type="number" name="durasi_menit" id="formDurasi" value="90" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
</div>
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Kuota</label>
<input type="number" name="kuota" id="formKuota" value="100" required style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
</div>
<div style="margin-bottom:1rem">
<label style="display:block;margin-bottom:.5rem">Status</label>
<select name="status" id="formStatus" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
<option value="draft">Draft</option>
<option value="published">Published</option>
</select>
</div>
<div style="display:flex;gap:1rem">
<button type="submit" class="btn" style="background:#27ae60;color:#fff">Simpan</button>
<button type="button" onclick="document.getElementById('formModal').style.display='none'" class="btn" style="background:#95a5a6;color:#fff">Batal</button>
</div>
</form>
</div>
</div>

<?php if (isset($_GET['edit'])): ?>
<?php
$editId = (int)$_GET['edit'];
$stmt = $pdo->prepare("SELECT * FROM scheduled_tryouts WHERE id=?");
$stmt->execute([$editId]);
$editData = $stmt->fetch();
if ($editData):
?>
<script>
document.getElementById('formModal').style.display='block';
document.getElementById('modalTitle').textContent='Edit Scheduled Tryout';
document.getElementById('formId').value=<?= $editData['id'] ?>;
document.getElementById('formNama').value='<?= e($editData['nama']) ?>';
document.getElementById('formDeskripsi').value='<?= e($editData['deskripsi']) ?>';
document.getElementById('formWaktuMulai').value='<?= $editData['waktu_mulai'] ?>';
document.getElementById('formDurasi').value=<?= $editData['durasi_menit'] ?>;
document.getElementById('formKuota').value=<?= $editData['kuota'] ?>;
document.getElementById('formStatus').value='<?= $editData['status'] ?>';
</script>
<?php endif; ?>
<?php endif; ?>
</body>
</html>

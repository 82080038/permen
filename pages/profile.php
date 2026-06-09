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

$error = '';
$success = '';

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch user's badges
$stmt = $pdo->prepare("
    SELECT badge_type, badge_name, badge_icon, badge_color, earned_at, period_start, period_end
    FROM leaderboard_badges
    WHERE user_id = ?
    ORDER BY earned_at DESC
");
$stmt->execute([$userId]);
$badges = $stmt->fetchAll();

// Ambil daftar instansi aktif untuk dropdown
$instansiList = $pdo->query("SELECT id, kode, nama FROM instansi WHERE aktif = 1 ORDER BY urutan, nama")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? 'profile';
        
        if ($action === 'password') {
            // Change password
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (!$currentPassword || !$newPassword || !$confirmPassword) {
                $error = 'Semua field password wajib diisi.';
            } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                $error = 'Password saat ini salah.';
            } else {
                $pwdValidation = validatePasswordStrength($newPassword);
                if (!$pwdValidation['valid']) {
                    $error = $pwdValidation['error'];
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Password baru dan konfirmasi tidak cocok.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$hash, $userId]);
                    $success = 'Password berhasil diubah!';
                }
            }
        } else {
            // Update profile
            $nama = sanitizeInput($_POST['nama'] ?? '');
            $noHp = sanitizeInput($_POST['no_hp'] ?? '');
            $sekolahAsal = sanitizeInput($_POST['sekolah_asal'] ?? '');
            $tahunTamat = (int)($_POST['tahun_tamat'] ?? 0);
            $instansiId = (int)($_POST['instansi_id'] ?? 0);
            $tanggalLahir = $_POST['tanggal_lahir'] ?? '';
            $jenisKelamin = $_POST['jenis_kelamin'] ?? '';
            $alamat = sanitizeInput($_POST['alamat'] ?? '');
            $showLeaderboard = isset($_POST['show_leaderboard']) ? 1 : 0;

            // Validasi
            if (!$nama || !$noHp) {
                $error = 'Nama dan Nomor HP wajib diisi.';
            } elseif (!isValidPhoneNumber($noHp)) {
                $error = 'Format nomor HP tidak valid. Gunakan format 08xx atau 628xx (minimal 10 digit).';
            } elseif ($tanggalLahir && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
                $error = 'Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD.';
            } elseif ($jenisKelamin && !in_array($jenisKelamin, ['L', 'P'])) {
                $error = 'Jenis kelamin tidak valid.';
            } else {
                // Cek no_hp sudah terdaftar oleh user lain
                $stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = ? AND id != ?");
                $stmt->execute([$noHp, $userId]);
                if ($stmt->fetchColumn()) {
                    $error = 'Nomor HP sudah digunakan oleh user lain.';
                } else {
                    // Update data user
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, no_hp = ?, sekolah_asal = ?, tahun_tamat = ?, instansi_id = ?, tanggal_lahir = ?, jenis_kelamin = ?, alamat = ?, show_leaderboard = ? WHERE id = ?");
                    
                    // Ambil nama instansi untuk backward compatibility
                    $instansiNama = '';
                    foreach ($instansiList as $i) {
                        if ($i['id'] == $instansiId) { 
                            $instansiNama = $i['kode']; 
                            break; 
                        }
                    }
                    
                    $stmt->execute([
                        $nama, 
                        $noHp, 
                        $sekolahAsal ?: null, 
                        $tahunTamat ?: null, 
                        $instansiId ?: null, 
                        $tanggalLahir ?: null, 
                        $jenisKelamin ?: null, 
                        $alamat ?: null, 
                        $showLeaderboard, 
                        $userId
                    ]);
                    
                    // Update instansi field untuk backward compatibility
                    $stmt = $pdo->prepare("UPDATE users SET instansi = ? WHERE id = ?");
                    $stmt->execute([$instansiNama ?: null, $userId]);
                    
                    // Update session
                    $_SESSION['user_nama'] = $nama;
                    
                    $success = 'Profil berhasil diperbarui!';
                    
                    // Refresh data user
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<base href="/permen/">
<title>Profil — SKD CAT-BKN</title>
<link rel="stylesheet" href="assets/form.css">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Edit Profil — SKD CAT-BKN'; $activePage = 'profile'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="container">
<div class="card">
<h2>Edit Data Profil</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

<!-- Profile Form -->
<form method="POST" action="" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="action" value="profile">

<!-- Photo Upload Section -->
<div style="text-align:center;margin-bottom:2rem;padding:1.5rem;background:#f8f9fa;border-radius:8px">
    <div style="margin-bottom:1rem">
        <?php if ($user['foto_profil']): ?>
            <img src="/permen/uploads/profile_photos/<?= e($user['foto_profil']) ?>" alt="Foto Profil" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #2980b9">
        <?php else: ?>
            <div style="width:120px;height:120px;border-radius:50%;background:#e9ecef;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:3rem;color:#999">👤</div>
        <?php endif; ?>
    </div>
    <div style="margin-bottom:1rem">
        <label for="photo" style="display:inline-block;background:#2980b9;color:#fff;padding:.5rem 1rem;border-radius:5px;cursor:pointer;font-size:.9rem">
            📷 Upload Foto
        </label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg" style="display:none" onchange="previewPhoto(this)">
        <button type="button" onclick="uploadPhoto()" style="background:#27ae60;color:#fff;border:none;padding:.5rem 1rem;border-radius:5px;cursor:pointer;font-size:.9rem;margin-left:.5rem">Simpan Foto</button>
    </div>
    <small style="color:#777;font-size:.8rem">Format: JPG/PNG, Max: 2MB. Foto akan di-resize otomatis ke 300x300px.</small>
    <div id="photoError" style="color:#e74c3c;font-size:.85rem;margin-top:.5rem"></div>
</div>

<div class="form-group">
<label for="nama">Nama Lengkap</label>
<input type="text" id="nama" name="nama" value="<?= e($user['nama'] ?? '') ?>" required minlength="2" aria-required="true" aria-label="Nama lengkap">
</div>
<div class="form-group">
<label for="no_hp">Nomor HP</label>
<input type="tel" id="no_hp" name="no_hp" value="<?= e($user['no_hp'] ?? '') ?>" required minlength="10" maxlength="14" aria-required="true" aria-describedby="nohp-help" aria-label="Nomor HP" pattern="[0-9]*" inputmode="numeric">
<small id="nohp-help" style="color:#777;font-size:.8rem">Nomor HP digunakan untuk login (format: 08xx atau 628xx)</small>
</div>
<div class="form-group">
<label for="tanggal_lahir">Tanggal Lahir (opsional)</label>
<input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($user['tanggal_lahir'] ?? '') ?>" aria-label="Tanggal lahir">
</div>
<div class="form-group">
<label for="jenis_kelamin">Jenis Kelamin (opsional)</label>
<select id="jenis_kelamin" name="jenis_kelamin" aria-label="Jenis kelamin">
<option value="">-- Pilih --</option>
<option value="L" <?= ($user['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
<option value="P" <?= ($user['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
</select>
</div>
<div class="form-group">
<label for="alamat">Alamat Lengkap (opsional)</label>
<textarea id="alamat" name="alamat" rows="3" aria-label="Alamat lengkap"><?= e($user['alamat'] ?? '') ?></textarea>
</div>
<div class="form-group">
<label for="sekolah_asal">Sekolah Asal (opsional)</label>
<input type="text" id="sekolah_asal" name="sekolah_asal" value="<?= e($user['sekolah_asal'] ?? '') ?>" aria-describedby="sekolah-help" aria-label="Nama sekolah asal">
<small id="sekolah-help" style="color:#777;font-size:.8rem">Contoh: SMA Negeri 1 Jakarta, SMK Telkom, dll</small>
</div>
<div class="form-group">
<label for="tahun_tamat">Tahun Tamat (opsional)</label>
<input type="number" id="tahun_tamat" name="tahun_tamat" value="<?= e($user['tahun_tamat'] ?? '') ?>" min="1990" max="2030" aria-describedby="tahun-help" aria-label="Tahun tamat sekolah">
<small id="tahun-help" style="color:#777;font-size:.8rem">Contoh: 2024</small>
</div>
<div class="form-group">
<label for="instansi_id">Instansi Pilihan (opsional)</label>
<select id="instansi_id" name="instansi_id" aria-describedby="instansi-help">
<option value="">-- Pilih Instansi --</option>
<?php foreach ($instansiList as $i): ?>
<option value="<?= $i['id'] ?>" <?= ($user['instansi_id'] ?? '') == $i['id'] ? 'selected' : '' ?>><?= e($i['kode']) ?> — <?= e($i['nama']) ?></option>
<?php endforeach; ?>
</select>
<small id="instansi-help" style="color:#777;font-size:.8rem">Pilih instansi yang ingin dilamar untuk mendapatkan rekomendasi</small>
</div>
<div class="form-group">
<label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
    <input type="checkbox" id="show_leaderboard" name="show_leaderboard" <?= ($user['show_leaderboard'] ?? 1) ? 'checked' : '' ?> aria-label="Tampilkan profil di leaderboard">
    <span style="font-size:.9rem;color:#555">Tampilkan profil saya di leaderboard</span>
</label>
<small style="color:#777;font-size:.8rem">Jika dicentang, nama dan nilai Anda akan terlihat oleh pengguna lain di leaderboard.</small>
</div>
<button type="submit" class="btn" aria-label="Simpan perubahan profil">Simpan Perubahan</button>
</form>

<hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">

<!-- Password Change Form -->
<h3 style="margin-bottom: 1rem; color: #1a5276;">Ganti Password</h3>
<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="action" value="password">
<div class="form-group">
<label for="current_password">Password Saat Ini</label>
<input type="password" id="current_password" name="current_password" required aria-required="true" aria-label="Password saat ini">
</div>
<div class="form-group">
<label for="new_password">Password Baru</label>
<input type="password" id="new_password" name="new_password" required minlength="8" aria-required="true" aria-describedby="newpwd-help" aria-label="Password baru">
<small id="newpwd-help" style="color:#777;font-size:.8rem">Minimal 8 karakter, 1 huruf besar, 1 huruf kecil, 1 angka</small>
</div>
<div class="form-group">
<label for="confirm_password">Konfirmasi Password Baru</label>
<input type="password" id="confirm_password" name="confirm_password" required minlength="8" aria-required="true" aria-label="Konfirmasi password baru">
</div>
<button type="submit" class="btn" style="background: #e74c3c;" aria-label="Ganti password">Ganti Password</button>
</form>

<p style="text-align:center;margin-top:1rem;font-size:.9rem">
<a href="user_dashboard.php" class="link">Kembali ke Dashboard</a>
</p>

<!-- Badges Section -->
<?php if (!empty($badges)): ?>
<div class="card" style="margin-top:2rem">
<h3 style="color:#1a5276;margin-bottom:1rem">🏆 Badge & Pencapaian</h3>
<div style="display:flex;flex-wrap:wrap;gap:1rem">
    <?php foreach ($badges as $badge): ?>
    <div style="background:#f8f9fa;padding:1rem;border-radius:8px;border-left:4px solid <?= $badge['badge_color'] ?>;min-width:200px;flex:1">
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
            <span style="font-size:1.5rem"><?= $badge['badge_icon'] ?></span>
            <div style="font-weight:bold;color:#1a5276"><?= e($badge['badge_name']) ?></div>
        </div>
        <div style="font-size:.75rem;color:#666">
            Diperoleh: <?= date('d M Y', strtotime($badge['earned_at'])) ?>
            <?php if ($badge['period_start']): ?>
            <br>Periode: <?= date('d M', strtotime($badge['period_start'])) ?> - <?= date('d M Y', strtotime($badge['period_end'])) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</div>
<?php else: ?>
<div class="card" style="margin-top:2rem;text-align:center;padding:2rem">
<div style="font-size:2rem;margin-bottom:.5rem">🏆</div>
<p style="color:#777;font-size:.9rem">Belum ada badge. Raih pencapaian untuk mendapatkan badge!</p>
</div>
<?php endif; ?>
</div>
</div>
<div class="footer">SKD CAT-BKN Try Out & Bimbel</div>
<script src="assets/app.js"></script>
<script>
function previewPhoto(input) {
    const errorDiv = document.getElementById('photoError');
    errorDiv.textContent = '';
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            errorDiv.textContent = 'File terlalu besar. Maksimal 2MB.';
            input.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            errorDiv.textContent = 'Format file tidak valid. Hanya JPG dan PNG.';
            input.value = '';
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgContainer = document.querySelector('.card form > div > div:first-child');
            imgContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #2980b9">`;
        };
        reader.readAsDataURL(file);
    }
}

async function uploadPhoto() {
    const input = document.getElementById('photo');
    const errorDiv = document.getElementById('photoError');
    
    if (!input.files || !input.files[0]) {
        errorDiv.textContent = 'Pilih foto terlebih dahulu.';
        return;
    }
    
    const formData = new FormData();
    formData.append('photo', input.files[0]);
    
    try {
        const response = await fetch('/permen/api/upload_profile_photo.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Refresh page to show new photo
            window.location.reload();
        } else {
            errorDiv.textContent = result.error || 'Gagal mengupload foto.';
        }
    } catch (error) {
        errorDiv.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}
</script>
</body>
</html>

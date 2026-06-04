<?php
require '../config.php';
require '../helpers.php';

$error = '';
$success = '';

// Ambil daftar instansi aktif untuk dropdown
$instansiList = $pdo->query("SELECT id, kode, nama FROM instansi WHERE aktif = 1 ORDER BY urutan, nama")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $nama = sanitizeInput($_POST['nama'] ?? '');
        $noHp = sanitizeInput($_POST['no_hp'] ?? '');
        $sekolahAsal = sanitizeInput($_POST['sekolah_asal'] ?? '');
        $tahunTamat = (int)($_POST['tahun_tamat'] ?? 0);
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $instansiId = (int)($_POST['instansi_id'] ?? 0);

        // Validasi
        if (!$nama || !$noHp || !$password || !$password2) {
            $error = 'Nama, Nomor HP, dan Password wajib diisi.';
        } elseif (!isValidPhoneNumber($noHp)) {
            $error = 'Format nomor HP tidak valid. Gunakan format 08xx atau 628xx (minimal 10 digit).';
        } else {
            $pwdValidation = validatePasswordStrength($password);
            if (!$pwdValidation['valid']) {
                $error = $pwdValidation['error'];
            } elseif ($password !== $password2) {
                $error = 'Password dan konfirmasi password tidak cocok.';
            } else {
                // Cek no_hp sudah terdaftar
                $stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = ?");
                $stmt->execute([$noHp]);
                if ($stmt->fetchColumn()) {
                    $error = 'Nomor HP sudah terdaftar. Silakan login.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (nama, no_hp, sekolah_asal, tahun_tamat, password_hash, instansi_id, instansi, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'user')");
                    // Ambil nama instansi untuk backward compatibility
                    $instansiNama = '';
                    foreach ($instansiList as $i) {
                        if ($i['id'] == $instansiId) { $instansiNama = $i['kode']; break; }
                    }
                    $stmt->execute([$nama, $noHp, $sekolahAsal ?: null, $tahunTamat ?: null, $hash, $instansiId ?: null, $instansiNama ?: null]);
                    $success = 'Pendaftaran berhasil! Silakan login dengan nomor HP dan password Anda.';
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
<title>Register — SKD CAT-BKN</title>
<link rel="stylesheet" href="../assets/form.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="header"><h1>Daftar Akun SKD CAT-BKN</h1></div>
<div class="container">
<div class="card">
<h2>Buat Akun Baru</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="nama">Nama Lengkap</label>
<input type="text" id="nama" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required minlength="2" aria-required="true" aria-label="Nama lengkap">
</div>
<div class="form-group">
<label for="no_hp">Nomor HP</label>
<input type="tel" id="no_hp" name="no_hp" value="<?= e($_POST['no_hp'] ?? '') ?>" required minlength="10" maxlength="14" aria-required="true" aria-describedby="nohp-help" aria-label="Nomor HP" pattern="[0-9]*" inputmode="numeric">
<small id="nohp-help" style="color:#777;font-size:.8rem">Nomor HP akan digunakan untuk login (format: 08xx atau 628xx)</small>
</div>
<div class="form-group">
<label for="sekolah_asal">Sekolah Asal (opsional)</label>
<input type="text" id="sekolah_asal" name="sekolah_asal" value="<?= e($_POST['sekolah_asal'] ?? '') ?>" aria-describedby="sekolah-help" aria-label="Nama sekolah asal">
<small id="sekolah-help" style="color:#777;font-size:.8rem">Contoh: SMA Negeri 1 Jakarta, SMK Telkom, dll</small>
</div>
<div class="form-group">
<label for="tahun_tamat">Tahun Tamat (opsional)</label>
<input type="number" id="tahun_tamat" name="tahun_tamat" value="<?= e($_POST['tahun_tamat'] ?? '') ?>" min="1990" max="2030" aria-describedby="tahun-help" aria-label="Tahun tamat sekolah">
<small id="tahun-help" style="color:#777;font-size:.8rem">Contoh: 2024</small>
</div>
<div class="form-group">
<label for="instansi_id">Instansi Pilihan (opsional)</label>
<select id="instansi_id" name="instansi_id" aria-describedby="instansi-help">
<option value="">-- Pilih Instansi --</option>
<?php foreach ($instansiList as $i): ?>
<option value="<?= $i['id'] ?>" <?= (($_POST['instansi_id'] ?? '') == $i['id']) ? 'selected' : '' ?>><?= e($i['kode']) ?> — <?= e($i['nama']) ?></option>
<?php endforeach; ?>
</select>
<small id="instansi-help" style="color:#777;font-size:.8rem">Pilih instansi yang ingin dilamar (opsional)</small>
</div>
<div class="form-group">
<label for="password">Password (minimal 8 karakter, 1 huruf besar, 1 huruf kecil, 1 angka, 1 karakter spesial)</label>
<input type="password" id="password" name="password" required minlength="8" aria-required="true" aria-describedby="password-help password-strength" aria-label="Password" oninput="checkPasswordStrength()">
<small id="password-help" style="color:#777;font-size:.8rem">Gunakan kombinasi huruf, angka, dan karakter spesial (!@#$%^&*(),.?":{}|<>) untuk keamanan</small>
<div id="password-strength" style="font-size:.8rem;margin-top:.3rem"></div>
</div>
<div class="form-group">
<label for="password2">Konfirmasi Password</label>
<input type="password" id="password2" name="password2" required minlength="8" aria-required="true" aria-label="Konfirmasi password">
</div>
<button type="submit" class="btn" aria-label="Daftar akun baru">Daftar</button>
</form>
<p style="text-align:center;margin-top:1rem;font-size:.9rem">
Sudah punya akun? <a href="login.php" class="link">Login di sini</a>
</p>
</div>
</div>
<div class="footer">SKD CAT-BKN Try Out & Bimbel</div>
<script src="../assets/app.js"></script>
<script>
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const feedback = document.getElementById('password-strength');
    let issues = [];
    
    if (password.length < 8) issues.push('minimal 8 karakter');
    if (!/[A-Z]/.test(password)) issues.push('1 huruf besar');
    if (!/[a-z]/.test(password)) issues.push('1 huruf kecil');
    if (!/[0-9]/.test(password)) issues.push('1 angka');
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) issues.push('1 karakter spesial');
    
    if (issues.length === 0) {
        feedback.innerHTML = '<span style="color:#27ae60">✓ Password kuat</span>';
    } else {
        feedback.innerHTML = '<span style="color:#e74c3c">Kurang: ' + issues.join(', ') + '</span>';
    }
}
</script>
</body>
</html>

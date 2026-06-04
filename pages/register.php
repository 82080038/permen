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
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $instansiId = (int)($_POST['instansi_id'] ?? 0);

        // Validasi
        if (!$nama || !$email || !$password || !$password2) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $pwdValidation = validatePasswordStrength($password);
        if (!$pwdValidation['valid']) {
            $error = $pwdValidation['error'];
        } elseif ($password !== $password2) {
            $error = 'Password dan konfirmasi password tidak cocok.';
        } else {
        // Cek email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $error = 'Email sudah terdaftar. Silakan login.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password_hash, instansi_id, instansi, role, email_verified) VALUES (?, ?, ?, ?, ?, 'user', 1)");
            // Ambil nama instansi untuk backward compatibility
            $instansiNama = '';
            foreach ($instansiList as $i) {
                if ($i['id'] == $instansiId) { $instansiNama = $i['kode']; break; }
            }
            $stmt->execute([$nama, $email, $hash, $instansiId ?: null, $instansiNama ?: null]);
            $success = 'Pendaftaran berhasil! Silakan login dengan email dan password Anda.';
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
<input type="text" id="nama" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required minlength="2" aria-required="true">
</div>
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required aria-required="true" aria-describedby="email-help">
<small id="email-help" style="color:#777;font-size:.8rem">Email akan digunakan untuk login</small>
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
<label for="password">Password (minimal 8 karakter, 1 huruf besar, 1 huruf kecil, 1 angka)</label>
<input type="password" id="password" name="password" required minlength="8" aria-required="true" aria-describedby="password-help">
<small id="password-help" style="color:#777;font-size:.8rem">Gunakan kombinasi huruf dan angka untuk keamanan</small>
</div>
<div class="form-group">
<label for="password2">Konfirmasi Password</label>
<input type="password" id="password2" name="password2" required minlength="8" aria-required="true">
</div>
<button type="submit" class="btn">Daftar</button>
</form>
<p style="text-align:center;margin-top:1rem;font-size:.9rem">
Sudah punya akun? <a href="login.php" class="link">Login di sini</a>
</p>
</div>
</div>
<div class="footer">SKD CAT-BKN Try Out & Bimbel</div>
<script src="../assets/app.js"></script>
</body>
</html>

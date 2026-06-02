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
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $instansiId = (int)($_POST['instansi_id'] ?? 0);

        // Validasi
        if (!$nama || !$email || !$password || !$password2) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
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
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password_hash, instansi_id, instansi, role) VALUES (?, ?, ?, ?, ?, 'user')");
            // Ambil nama instansi untuk backward compatibility
            $instansiNama = '';
            foreach ($instansiList as $i) {
                if ($i['id'] == $instansiId) { $instansiNama = $i['kode']; break; }
            }
            $stmt->execute([$nama, $email, $hash, $instansiId ?: null, $instansiNama ?: null]);
            $success = 'Pendaftaran berhasil! Silakan login.';
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
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;text-align:center}
.header h1{font-size:1.1rem}
.container{max-width:420px;margin:2rem auto;padding:0 1rem}
.card{background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.5rem}
.card h2{color:#1a5276;font-size:1.15rem;margin-bottom:1rem;text-align:center}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.85rem;color:#555;margin-bottom:.3rem;font-weight:600}
.form-group input,.form-group select{width:100%;padding:.65rem .8rem;border:1px solid #ddd;border-radius:5px;font-size:.9rem;min-height:44px;background:#fff}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#2980b9}
.btn{display:block;width:100%;background:#2980b9;color:#fff;padding:.75rem;border-radius:5px;border:none;font-size:.95rem;font-weight:600;cursor:pointer;min-height:44px}
.btn:hover{background:#1a5276}
.alert{padding:.8rem;border-radius:5px;margin-bottom:1rem;font-size:.9rem}
.alert.error{background:#f8d7da;color:#721c24}
.alert.success{background:#d4edda;color:#155724}
.footer{text-align:center;padding:1.5rem;color:#777;font-size:.85rem}
.link{color:#2980b9;text-decoration:none}
.link:hover{text-decoration:underline}
</style>
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
<label>Nama Lengkap</label>
<input type="text" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required minlength="2">
</div>
<div class="form-group">
<label>Email</label>
<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
</div>
<div class="form-group">
<label>Instansi Pilihan (opsional)</label>
<select name="instansi_id">
<option value="">-- Pilih Instansi --</option>
<?php foreach ($instansiList as $i): ?>
<option value="<?= $i['id'] ?>" <?= (($_POST['instansi_id'] ?? '') == $i['id']) ? 'selected' : '' ?>><?= e($i['kode']) ?> — <?= e($i['nama']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Password</label>
<input type="password" name="password" required minlength="6">
</div>
<div class="form-group">
<label>Konfirmasi Password</label>
<input type="password" name="password2" required minlength="6">
</div>
<button type="submit" class="btn">Daftar</button>
</form>
<p style="text-align:center;margin-top:1rem;font-size:.9rem">
Sudah punya akun? <a href="login.php" class="link">Login di sini</a>
</p>
</div>
</div>
<div class="footer">SKD CAT-BKN Try Out & Bimbel</div>
</body>
</html>

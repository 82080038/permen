<?php
require '../config.php';
require '../helpers.php';

$error = '';

// Quick login for testing (development only) - REMOVED FOR SECURITY
// Use the actual login form instead.

// Proses login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!checkRateLimit($ip, $pdo)) {
        $error = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
    } elseif (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id, nama, email, role, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            session_regenerate_id(true);

            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: user_dashboard.php');
            }
            exit;
        } else {
            incrementRateLimit($ip, $pdo);
            $error = 'Email atau password salah.';
        }
        } else {
            incrementRateLimit($ip, $pdo);
            $error = 'Email dan password wajib diisi.';
        }
    }
}

// Jika sudah login, redirect
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Login — SKD CAT-BKN</title>
<link rel="stylesheet" href="../assets/login.css">
</head>
<body>
<div class="card">
<h2>Login SKD CAT-BKN</h2>

<?php if ($error): ?>
<div class="error"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" placeholder="email@contoh.com" required aria-required="true" aria-describedby="email-help">
<small id="email-help" style="color:#777;font-size:.8rem">Masukkan email yang terdaftar</small>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="Password" required aria-required="true" aria-describedby="password-help">
<small id="password-help" style="color:#777;font-size:.8rem">Minimal 8 karakter, 1 huruf besar, 1 huruf kecil, 1 angka</small>
</div>
<button type="submit" class="btn">Masuk</button>
</form>

<div class="footer">
<a href="../index.php">Kembali ke Beranda</a> &middot; <a href="register.php">Daftar Akun Baru</a> &middot; <a href="forgot_password.php">Lupa Password?</a>
</div>
</div>
<script src="../assets/app.js"></script>
</body>
</html>

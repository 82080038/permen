<?php
require '../config.php';
require '../helpers.php';

$error = '';

// Proses login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!checkRateLimit($ip)) {
        $error = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
    } elseif (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $email = trim($_POST['email'] ?? '');
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
            incrementRateLimit($ip);
            $error = 'Email atau password salah.';
        }
        } else {
            incrementRateLimit($ip);
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
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:.5rem}
.card{background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1);padding:1.5rem;width:100%;max-width:420px;margin:.5rem}
.card h2{text-align:center;color:#1a5276;margin-bottom:1.2rem;font-size:1.25rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.9rem;color:#555;margin-bottom:.3rem;font-weight:600}
.form-group input{width:100%;padding:.75rem;border:1px solid #ddd;border-radius:6px;font-size:1rem;min-height:44px}
.form-group input:focus{outline:none;border-color:#2980b9}
.btn{width:100%;padding:.85rem;background:#2980b9;color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:.5rem;min-height:44px}
.btn:hover{background:#1a5276}
.error{color:#e74c3c;font-size:.9rem;margin-bottom:1rem;text-align:center}
.quick-login{margin-top:1.2rem;border-top:1px solid #eee;padding-top:1.2rem}
.quick-login h3{font-size:.95rem;color:#1a5276;margin-bottom:.8rem;text-align:center}
.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}
.quick-grid a{display:block;text-align:center;padding:.6rem .3rem;border-radius:6px;text-decoration:none;font-size:.85rem;color:#fff;font-weight:600;min-height:44px;display:flex;align-items:center;justify-content:center}
.quick-grid a.admin{background:#e74c3c}
.quick-grid a.user{background:#27ae60}
.quick-grid a:hover{opacity:.9}
.footer{text-align:center;margin-top:1.2rem;font-size:.85rem;color:#777}
.footer a{color:#2980b9;text-decoration:none}
@media(max-width:400px){
.card{padding:1.2rem}
.quick-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
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
<label>Email</label>
<input type="email" name="email" placeholder="email@contoh.com" required>
</div>
<div class="form-group">
<label>Password</label>
<input type="password" name="password" placeholder="Password" required>
</div>
<button type="submit" class="btn">Masuk</button>
</form>

<div class="footer">
<a href="../index.php">Kembali ke Beranda</a> &middot; <a href="register.php">Daftar Akun Baru</a>
</div>
</div>
</body>
</html>

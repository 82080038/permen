<?php
require '../config.php';
require '../helpers.php';

// Guard: user yang sudah login tidak perlu login lagi
if (!empty($_SESSION['user_id'])) {
    $baseUrl = $_ENV['BASE_URL'] ?? '/permen';
    header('Location: ' . $baseUrl . '/pages/user_dashboard.php');
    exit;
}

$error = '';

// Proses login form - simplified version
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noHp = sanitizeInput($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // CSRF validation
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } elseif ($noHp && $password) {
        try {
            // Simple database query - check both password and password_hash columns for compatibility
            $stmt = $pdo->prepare("SELECT id, nama, no_hp, email, role FROM users WHERE no_hp = ? OR email = ?");
            $stmt->execute([$noHp, $noHp]);
            $user = $stmt->fetch();

            // Get password from correct column
            $passwordHash = null;
            if ($user) {
                // Try to get password_hash first (local), then password (production)
                try {
                    $stmt2 = $pdo->prepare("SELECT password_hash, password FROM users WHERE id = ?");
                    $stmt2->execute([$user['id']]);
                    $pwdData = $stmt2->fetch();
                    $passwordHash = $pwdData['password_hash'] ?? $pwdData['password'] ?? '';
                } catch (PDOException $e) {
                    // If password column doesn't exist (local), try only password_hash
                    $stmt2 = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt2->execute([$user['id']]);
                    $pwdData = $stmt2->fetch();
                    $passwordHash = $pwdData['password_hash'] ?? '';
                }
            }
            
            if ($user && password_verify($password, $passwordHash)) {
                // Set ALL session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_no_hp'] = $user['no_hp'];
                $_SESSION['user_email'] = $user['email'] ?? '';
                $_SESSION['user_role'] = $user['role'];
                
                // Compatibility variables
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'] ?? '';
                $_SESSION['role'] = $user['role'];
                $_SESSION['no_hp'] = $user['no_hp'];
                
                // Force session write
                session_write_close();
                session_start();
                
                // Debug: Log session data
                error_log("[LOGIN_DEBUG] Session data after login: " . json_encode($_SESSION));
                
                // Redirect based on role
                $baseUrl = $_ENV['BASE_URL'] ?? '/permen';
                if ($user['role'] === 'admin') {
                    header('Location: ' . $baseUrl . '/pages/admin_dashboard.php');
                } else {
                    header('Location: ' . $baseUrl . '/pages/user_dashboard.php');
                }
                exit;
            } else {
                $error = 'Nomor HP atau password salah.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    } else {
        $error = 'Nomor HP dan password wajib diisi.';
    }
}

// Jika sudah login, redirect
if (!empty($_SESSION['user_id'])) {
    $baseUrl = $_ENV['BASE_URL'] ?? '/permen';
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ' . $baseUrl . '/pages/admin_dashboard.php');
    } else {
        header('Location: ' . $baseUrl . '/pages/user_dashboard.php');
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
<link rel="icon" href="data:,">
<base href="<?php echo rtrim($baseUrl, '/'); ?>">
<title>Login — SKD CAT-BKN</title>
<link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/login.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="card" id="main-content">
<h2>Login SKD CAT-BKN</h2>

<?php if ($error): ?>
<div class="error"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="no_hp">Nomor HP</label>
<input type="tel" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" required minlength="10" maxlength="14" aria-required="true" aria-describedby="nohp-help" aria-label="Nomor HP" pattern="[0-9]*" inputmode="numeric">
<small id="nohp-help" style="color:#777;font-size:.8rem">Masukkan nomor HP yang terdaftar (format: 08xx atau 628xx)</small>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="Password" required aria-required="true" aria-describedby="password-help" aria-label="Password" autocomplete="current-password">
<small id="password-help" style="color:#777;font-size:.8rem">Masukkan password Anda</small>
</div>
<button type="submit" class="btn" aria-label="Masuk ke akun">Masuk</button>
</form>

<?php if (($_ENV['APP_ENV'] ?? 'development') === 'development'): ?>
<div style="margin-top:1.5rem;padding:1rem;background:#fff3cd;border:1px solid #ffeeba;border-radius:6px;text-align:center">
<p style="color:#856404;font-size:.85rem;margin-bottom:.5rem"><strong>⚠️ Development Mode - Quick Login</strong></p>
<div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
<button onclick="quickLogin('081987654321', 'Sihaloho1982')" style="background:#2980b9;color:#fff;border:none;padding:.4rem .8rem;border-radius:4px;cursor:pointer;font-size:.8rem">User (081987654321)</button>
</div>
<p style="color:#856404;font-size:.75rem;margin-top:.5rem">Password: <code>Sihaloho1982</code></p>
</div>
<script>
function quickLogin(noHp, password) {
    document.getElementById('no_hp').value = noHp;
    document.getElementById('password').value = password;
    document.querySelector('form').submit();
}
</script>
<?php endif; ?>

<div class="footer">
<a href="index.php">Kembali ke Beranda</a> &middot; <a href="register.php">Daftar Akun Baru</a>
</div>

<div style="margin-top:1rem;padding:1rem;background:#e8f4fd;border:1px solid #bee5eb;border-radius:6px;text-align:center">
<p style="color:#0c5460;font-size:.85rem;margin:0"><strong>🔑 Lupa Password?</strong></p>
<p style="color:#0c5460;font-size:.8rem;margin:.5rem 0 0 0">Reset password dilakukan oleh Admin. Silakan hubungi Admin melalui kontak pribadi atau hubungan langsung untuk permintaan reset password.</p>
</div>
</div>
<script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $baseUrl; ?>/assets/app.js"></script>
</body>
</html>

<?php
require '../config.php';
require '../helpers.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validate token first
$validToken = false;
$userId = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if ($reset) {
        $validToken = true;
        $userId = $reset['user_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } elseif (!$validToken) {
        $error = 'Token tidak valid atau kadaluarsa.';
    } else {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        
        $pwdValidation = validatePasswordStrength($password);
        if (!$pwdValidation['valid']) {
            $error = $pwdValidation['error'];
        } elseif ($password !== $password2) {
            $error = 'Password dan konfirmasi password tidak cocok.';
        } else {
            // Update password
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $userId]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
                $stmt->execute([$token]);
                
                $pdo->commit();
                $success = 'Password berhasil di-reset. Silakan login dengan password baru.';
                $validToken = false; // Prevent resubmission
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal mereset password. Silakan coba lagi.';
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
<title>Reset Password — SKD CAT-BKN</title>
<link rel="stylesheet" href="../assets/form.css">
</head>
<body>
<div class="header"><h1>Reset Password — SKD CAT-BKN</h1></div>
<div class="container">
<div class="card">
<h2>Reset Password Baru</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?>
<div class="alert success"><?= e($success) ?></div>
<div class="footer">
<a href="login.php" class="link">Login Sekarang</a>
</div>
<?php elseif (!$validToken): ?>
<div class="alert error">Link reset password tidak valid atau kadaluarsa. Silakan <a href="forgot_password.php" class="link">request ulang</a>.</div>
<?php else: ?>
<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="password">Password Baru (minimal 8 karakter, 1 huruf besar, 1 huruf kecil, 1 angka)</label>
<input type="password" id="password" name="password" required minlength="8" aria-required="true">
</div>
<div class="form-group">
<label for="password2">Konfirmasi Password Baru</label>
<input type="password" id="password2" name="password2" required minlength="8" aria-required="true">
</div>
<button type="submit" class="btn">Reset Password</button>
</form>
<?php endif; ?>
</div>
</div>
<script src="../assets/app.js"></script>
</body>
</html>

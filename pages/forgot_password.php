<?php
require '../config.php';
require '../helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (!$email) {
            $error = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = generateVerificationToken();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save to database
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, created_at, expires_at) VALUES (?, ?, NOW(), ?)");
                $stmt->execute([$user['id'], $token, $expiresAt]);
                
                // Send email
                if (sendPasswordResetEmail($email, $token)) {
                    $success = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox (termasuk folder spam).';
                } else {
                    $error = 'Gagal mengirim email. Silakan coba lagi nanti.';
                }
            } else {
                // Don't reveal if email exists or not for security
                $success = 'Jika email terdaftar, link reset password akan dikirim ke email Anda.';
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
<title>Lupa Password — SKD CAT-BKN</title>
<link rel="stylesheet" href="../assets/form.css">
</head>
<body>
<div class="header"><h1>Lupa Password — SKD CAT-BKN</h1></div>
<div class="container">
<div class="card">
<h2>Reset Password</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" placeholder="email@contoh.com" required aria-required="true" aria-describedby="email-help">
<small id="email-help" style="color:#777;font-size:.8rem">Masukkan email yang terdaftar</small>
</div>
<button type="submit" class="btn">Kirim Link Reset</button>
</form>
<div class="footer">
<a href="login.php" class="link">Kembali ke Login</a>
</div>
</div>
</div>
<script src="../assets/app.js"></script>
</body>
</html>

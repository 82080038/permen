<?php
require '../config.php';
require '../helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $noHp = sanitizeInput($_POST['no_hp'] ?? '');
        
        if (!$noHp) {
            $error = 'Nomor HP wajib diisi.';
        } elseif (!isValidPhoneNumber($noHp)) {
            $error = 'Format nomor HP tidak valid. Gunakan format 08xx atau 628xx.';
        } else {
            // Check if no_hp exists (fallback to email for backward compatibility)
            $stmt = $pdo->prepare("SELECT id, nama, no_hp, email FROM users WHERE no_hp = ? OR email = ?");
            $stmt->execute([$noHp, $noHp]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Create password reset request for admin
                $identifier = $user['no_hp'] ?? $user['email'];
                $stmt = $pdo->prepare("INSERT INTO password_reset_requests (user_id, no_hp, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user['id'], $identifier]);
                
                // Notify admin via feedback system
                $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                $adminStmt->execute();
                $admin = $adminStmt->fetch();
                
                if ($admin) {
                    require '../api/create_notification.php';
                    createNotification(
                        $admin['id'],
                        'warning',
                        'Request Reset Password',
                        "User {$user['nama']} ($identifier) meminta reset password. Silakan reset password di admin dashboard.",
                        'admin_dashboard.php'
                    );
                }
                
                $success = 'Request reset password telah dikirim ke admin. Admin akan menghubungi Anda untuk password baru.';
            } else {
                // Don't reveal if no_hp exists or not for security
                $success = 'Jika nomor HP terdaftar, request reset password akan dikirim ke admin.';
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
<base href="<?php echo $baseUrl ?? '/permen'; ?>">
<title>Request Reset Password — SKD CAT-BKN</title>
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/form.css">
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/style.css">
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Request Reset Password — SKD CAT-BKN'; $activePage = 'beranda'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="container" id="main-content">
<div class="card">
<h2>Request Reset Password</h2>
<p style="font-size:.9rem;color:#666;margin-bottom:1rem">Lupa password? Masukkan nomor HP Anda untuk request reset password ke admin. Admin akan memberikan password baru.</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="no_hp">Nomor HP</label>
<input type="tel" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" required minlength="10" maxlength="14" aria-required="true" aria-describedby="nohp-help" aria-label="Nomor HP" pattern="[0-9]*" inputmode="numeric">
<small id="nohp-help" style="color:#777;font-size:.8rem">Masukkan nomor HP yang terdaftar (format: 08xx atau 628xx)</small>
</div>
<button type="submit" class="btn" aria-label="Kirim request reset password ke admin">Kirim Request ke Admin</button>
</form>
<div class="footer">
<a href="login.php" class="link">Kembali ke Login</a>
</div>
</div>
</div>
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/app.js"></script>
</body>
</html>

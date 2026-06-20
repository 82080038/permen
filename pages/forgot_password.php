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
<h2>Reset Password</h2>
<p style="font-size:.9rem;color:#666;margin-bottom:1rem">Lupa password? Masukkan nomor HP Anda. Jika WhatsApp API aktif, Anda akan menerima kode OTP untuk reset password otomatis. Jika belum, request akan dikirim ke admin.</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

<!-- Step 1: Request OTP -->
<form method="POST" action="" id="formRequestOtp">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="form-group">
<label for="no_hp">Nomor HP</label>
<input type="tel" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" required minlength="10" maxlength="14" aria-required="true" aria-label="Nomor HP" pattern="[0-9]*" inputmode="numeric">
<small style="color:#777;font-size:.8rem">Masukkan nomor HP yang terdaftar (format: 08xx atau 628xx)</small>
</div>
<button type="submit" class="btn" aria-label="Kirim request reset password">Kirim Request Reset</button>
</form>

<!-- Step 2: OTP Verification (shown after request) -->
<div id="otpSection" style="display:none;margin-top:1.5rem;border-top:1px solid #eee;padding-top:1rem">
<h3 style="font-size:.95rem;color:#1a5276;margin-bottom:.5rem">Verifikasi OTP & Password Baru</h3>
<div class="form-group">
<label for="otp_code">Kode OTP (6 digit)</label>
<input type="text" id="otp_code" name="otp_code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" aria-label="Kode OTP">
</div>
<div class="form-group">
<label for="new_password">Password Baru</label>
<input type="password" id="new_password" name="new_password" placeholder="Min 8 karakter, huruf besar, kecil, angka" minlength="8" aria-label="Password baru">
</div>
<button type="button" class="btn" style="background:#27ae60" onclick="verifyOtpAndReset()" aria-label="Verifikasi OTP dan set password baru">Verifikasi & Reset Password</button>
<div id="otpResult" style="margin-top:.5rem"></div>
</div>

<div class="footer">
<a href="login.php" class="link">Kembali ke Login</a>
</div>
</div>
</div>
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/app.js"></script>
<script>
var csrfToken = '<?= e(csrfToken()) ?>';
var baseUrl = '<?= $baseUrl ?? '/permen' ?>';

// Check if OTP section should be shown (after successful request)
<?php if ($success && strpos($success, 'OTP') !== false): ?>
document.getElementById('otpSection').style.display = 'block';
<?php endif; ?>

async function verifyOtpAndReset() {
    var otp = document.getElementById('otp_code').value;
    var newPwd = document.getElementById('new_password').value;
    var noHp = document.getElementById('no_hp').value;
    var resultDiv = document.getElementById('otpResult');
    
    if (!otp || !newPwd || !noHp) {
        resultDiv.innerHTML = '<span style="color:#e74c3c">Semua field wajib diisi</span>';
        return;
    }
    
    var formData = new FormData();
    formData.append('no_hp', noHp);
    formData.append('otp', otp);
    formData.append('new_password', newPwd);
    
    try {
        var res = await fetch(baseUrl + '/api/verify_otp.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: formData
        });
        var data = await res.json();
        
        if (data.success) {
            resultDiv.innerHTML = '<span style="color:#27ae60">✅ ' + data.message + '</span>';
            setTimeout(function() { window.location.href = baseUrl + '/pages/login.php'; }, 2000);
        } else {
            resultDiv.innerHTML = '<span style="color:#e74c3c">' + (data.error || 'Gagal') + '</span>';
        }
    } catch(e) {
        resultDiv.innerHTML = '<span style="color:#e74c3c">Terjadi kesalahan. Coba lagi.</span>';
    }
}
</script>
</body>
</html>

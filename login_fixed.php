<?php
// Start session first (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config.php';
require '../helpers.php';

// Guard: user yang sudah login tidak perlu login lagi
if (!empty($_SESSION['user_id'])) {
    header('Location: user_dashboard.php');
    exit;
}

$error = '';

// Proses login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // CSRF validation ALWAYS required (security critical)
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    }
    
    if (!$error) {
        $noHp = sanitize($_POST['no_hp'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($noHp && $password) {
            try {
                // Try no_hp first, fallback to email for backward compatibility
                $stmt = $GLOBALS['pdo']->prepare("SELECT id, nama, no_hp, email, role, password FROM users WHERE no_hp = ? OR email = ?");
                $stmt->execute([$noHp, $noHp]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['no_hp'] = $user['no_hp'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log activity
                    logActivity('login', 'User logged in', $user['id']);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: user_dashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Nomor HP/Email atau password salah.';
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Terjadi kesalahan server. Silakan coba lagi nanti.';
            }
        } else {
            $error = 'Nomor HP/Email dan password harus diisi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SKD CAT-BKN</title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1a5276;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            padding: 10px;
            background: #fdf2f2;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        .success {
            color: #27ae60;
            margin-bottom: 15px;
            padding: 10px;
            background: #f2fdf2;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #1a5276;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login SKD CAT-BKN</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
            
            <div class="form-group">
                <label for="no_hp">Nomor HP atau Email:</label>
                <input type="text" id="no_hp" name="no_hp" required 
                       value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>"
                       placeholder="Contoh: 08123456789">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Masukkan password">
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="links">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            <p><a href="../">Kembali ke halaman utama</a></p>
        </div>
    </div>
    
    <script>
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('no_hp').focus();
        });
    </script>
</body>
</html>

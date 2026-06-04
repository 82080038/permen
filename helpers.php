<?php
/**
 * Helper Functions — SKD CAT-BKN
 * Fungsi reusable untuk seluruh aplikasi
 */

/**
 * Generate CSRF token
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari form POST
 */
function validateCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting: cek apakah IP masih diizinkan login (max 5 per 15 menit)
 * Uses database for storage instead of file system for better security
 */
function checkRateLimit(string $ip, PDO $pdo): bool
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT count, created_at FROM rate_limits WHERE ip = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ip]);
    $data = $stmt->fetch();
    
    if (!$data || time() - strtotime($data['created_at']) > 900) {
        // Reset if expired or doesn't exist
        return true;
    }
    
    return $data['count'] < 5;
}

/**
 * Rate limiting: increment attempt counter untuk IP
 * Uses database for storage instead of file system for better security
 */
function incrementRateLimit(string $ip, PDO $pdo): void
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT count, created_at FROM rate_limits WHERE ip = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ip]);
    $data = $stmt->fetch();
    
    if (!$data || time() - strtotime($data['created_at']) > 900) {
        // Reset if expired or doesn't exist
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, count, created_at) VALUES (?, 1, NOW())");
        $stmt->execute([$ip]);
    } else {
        // Increment
        $stmt = $pdo->prepare("UPDATE rate_limits SET count = count + 1 WHERE ip = ? AND created_at = ?");
        $stmt->execute([$ip, $data['created_at']]);
    }
}

/**
 * Format angka sebagai mata uang Rupiah
 */
function formatRupiah(int $amount): string
{
    return 'Rp' . number_format($amount, 0, ',', '.');
}

/**
 * Format durasi dalam detik ke format MM:SS
 */
function formatDuration(int $seconds): string
{
    $m = str_pad(floor($seconds / 60), 2, '0', STR_PAD_LEFT);
    $s = str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
    return "$m:$s";
}

/**
 * Escape output untuk HTML (XSS prevention shorthand)
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Logging sederhana ke file
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 */
function appLog(string $message, string $level = 'info'): void
{
    $file = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Validasi format email sederhana
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validasi password strength
 * Requirements: minimal 8 karakter, minimal 1 huruf besar, 1 huruf kecil, 1 angka
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'error' => string]
 */
function validatePasswordStrength(string $password): array
{
    if (strlen($password) < 8) {
        return ['valid' => false, 'error' => 'Password minimal 8 karakter'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'error' => 'Password harus mengandung minimal 1 huruf besar'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'error' => 'Password harus mengandung minimal 1 huruf kecil'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Password harus mengandung minimal 1 angka'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Sanitasi input user untuk mencegah XSS dan injection
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput(string $input): string
{
    // Trim whitespace
    $input = trim($input);
    
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    
    // Remove control characters except newlines and tabs
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
    
    return $input;
}

/**
 * Sanitasi array input (misalnya POST data)
 * @param array $data Array to sanitize
 * @return array Sanitized array
 */
function sanitizeArray(array $data): array
{
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $sanitized[$key] = sanitizeInput($value);
        } elseif (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = $value;
        }
    }
    return $sanitized;
}

/**
 * Generate email verification token
 * @return string Verification token
 */
function generateVerificationToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Send verification email
 * @param string $email User email
 * @param string $token Verification token
 * @return bool Success status
 */
function sendVerificationEmail(string $email, string $token): bool
{
    // Generate verification link
    $baseUrl = getBaseUrl();
    $verificationLink = $baseUrl . '/pages/verify_email.php?token=' . $token;
    
    $subject = 'Verifikasi Email - SKD CAT-BKN';
    $message = "
    <html>
    <head>
    <title>Verifikasi Email</title>
    </head>
    <body>
    <h2>Selamat Datang di SKD CAT-BKN</h2>
    <p>Terima kasih telah mendaftar. Silakan verifikasi email Anda dengan mengklik link di bawah ini:</p>
    <p><a href='$verificationLink' style='background:#2980b9;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block'>Verifikasi Email</a></p>
    <p>Atau copy dan paste link ini ke browser:</p>
    <p style='word-break:break-all'>$verificationLink</p>
    <p>Link ini akan kadaluarsa dalam 24 jam.</p>
    <p>Jika Anda tidak mendaftar di SKD CAT-BKN, abaikan email ini.</p>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: SKD CAT-BKN <noreply@skdcatbkn.com>',
        'Reply-To: noreply@skdcatbkn.com'
    ];
    
    // Note: This requires proper SMTP configuration in php.ini
    // For production, use PHPMailer or similar library
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Send password reset email
 * @param string $email User email
 * @param string $token Reset token
 * @return bool Success status
 */
function sendPasswordResetEmail(string $email, string $token): bool
{
    // Generate reset link
    $baseUrl = getBaseUrl();
    $resetLink = $baseUrl . '/pages/reset_password.php?token=' . $token;
    
    $subject = 'Reset Password - SKD CAT-BKN';
    $message = "
    <html>
    <head>
    <title>Reset Password</title>
    </head>
    <body>
    <h2>Reset Password Anda</h2>
    <p>Anda telah meminta reset password untuk akun SKD CAT-BKN Anda.</p>
    <p>Silakan klik link di bawah ini untuk reset password:</p>
    <p><a href='$resetLink' style='background:#e74c3c;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block'>Reset Password</a></p>
    <p>Atau copy dan paste link ini ke browser:</p>
    <p style='word-break:break-all'>$resetLink</p>
    <p>Link ini akan kadaluarsa dalam 1 jam.</p>
    <p>Jika Anda tidak meminta reset password, abaikan email ini dan password Anda tidak akan diubah.</p>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: SKD CAT-BKN <noreply@skdcatbkn.com>',
        'Reply-To: noreply@skdcatbkn.com'
    ];
    
    return mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Log admin action to audit log
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string|null $entityType Type of entity (e.g., 'question', 'user')
 * @param int|null $entityId ID of entity
 * @param string|null $details Additional details
 * @return bool Success status
 */
function logAdminAction(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): bool
{
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip, $userAgent]);
        return true;
    } catch (Exception $e) {
        // Log error but don't fail the operation
        error_log("Failed to log admin action: " . $e->getMessage());
        return false;
    }
}

/**
 * Check API rate limit
 * @param string $identifier IP address or user ID
 * @param string $endpoint API endpoint name
 * @param int $limit Max requests per window
 * @param int $window Window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkAPIRateLimit(string $identifier, string $endpoint, int $limit = 60, int $window = 60): bool
{
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM api_rate_limits WHERE identifier = ? AND endpoint = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$identifier, $endpoint, $window]);
    $result = $stmt->fetch();
    
    return $result['count'] < $limit;
}

/**
 * Log API request for rate limiting
 * @param string $identifier IP address or user ID
 * @param string $endpoint API endpoint name
 * @return void
 */
function logAPIRequest(string $identifier, string $endpoint): void
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO api_rate_limits (identifier, endpoint, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$identifier, $endpoint]);
    } catch (Exception $e) {
        error_log("Failed to log API request: " . $e->getMessage());
    }
}

/**
 * Optimize uploaded image
 * @param string $sourcePath Path to source image
 * @param string $destinationPath Path to save optimized image
 * @param int $maxWidth Maximum width (default 1200)
 * @param int $quality JPEG quality (default 85)
 * @return bool Success status
 */
function optimizeImage(string $sourcePath, string $destinationPath, int $maxWidth = 1200, int $quality = 85): bool
{
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $mime = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // If image is already smaller than max width, just copy
    if ($width <= $maxWidth) {
        return copy($sourcePath, $destinationPath);
    }
    
    // Calculate new dimensions
    $newWidth = $maxWidth;
    $newHeight = (int)($height * ($maxWidth / $width));
    
    // Create image from source based on MIME type
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Create new image with new dimensions
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save optimized image
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($destination, $destinationPath, $quality);
            break;
        case 'image/png':
            $result = imagepng($destination, $destinationPath, 9); // Max compression for PNG
            break;
        case 'image/gif':
            $result = imagegif($destination, $destinationPath);
            break;
        case 'image/webp':
            $result = imagewebp($destination, $destinationPath, $quality);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $result;
}

/**
 * Redirect dengan header location
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Ambil base URL aplikasi dari .env atau fallback
 */
function baseUrl(): string
{
    return rtrim($_ENV['BASE_URL'] ?? 'http://localhost/permen', '/');
}

/**
 * Get CDN URL for static assets
 * @param string $path Relative path to asset
 * @return string Full URL (CDN or local)
 */
function getAssetUrl(string $path): string
{
    $cdnEnabled = ($_ENV['CDN_ENABLED'] ?? 'false') === 'true';
    $cdnUrl = $_ENV['CDN_URL'] ?? '';
    
    if ($cdnEnabled && $cdnUrl) {
        return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
    }
    
    return $path;
}

/**
 * Cek apakah user sudah login (punya session)
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Cek apakah aplikasi berjalan di development
 */
function isDev(): bool
{
    return ($_ENV['APP_ENV'] ?? 'production') === 'development';
}

/**
 * Tampilkan error hanya di development
 */
function devError(string $message): void
{
    if (isDev()) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:1rem;border-radius:5px;margin:1rem'><strong>Dev Error:</strong> " . e($message) . "</div>";
    }
}

/**
 * Validate uploaded file with enhanced security checks
 * @param array $file $_FILES array element
 * @return array ['valid' => bool, 'error' => string]
 */
function validateUploadedFile(array $file): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['valid' => false, 'error' => 'Ukuran file maksimal 2MB'];
    }

    // Check file size minimum (prevent empty files)
    if ($file['size'] < 1) {
        return ['valid' => false, 'error' => 'File kosong tidak diizinkan'];
    }

    // Check MIME type with finfo
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes)) {
        return ['valid' => false, 'error' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, SVG, atau WEBP.'];
    }

    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    if (!in_array($ext, $allowedExts)) {
        return ['valid' => false, 'error' => 'Ekstensi file tidak diizinkan'];
    }

    // Check file signature/magic bytes for extra security
    $handle = fopen($file['tmp_name'], 'rb');
    $bytes = fread($handle, 8);
    fclose($handle);

    // JPEG: FF D8 FF
    if ($ext === 'jpg' || $ext === 'jpeg') {
        if (substr($bytes, 0, 2) !== "\xFF\xD8") {
            return ['valid' => false, 'error' => 'File signature tidak valid untuk JPEG'];
        }
    }
    // PNG: 89 50 4E 47
    elseif ($ext === 'png') {
        if (substr($bytes, 0, 4) !== "\x89PNG") {
            return ['valid' => false, 'error' => 'File signature tidak valid untuk PNG'];
        }
    }
    // GIF: 47 49 46 38
    elseif ($ext === 'gif') {
        if (substr($bytes, 0, 3) !== 'GIF') {
            return ['valid' => false, 'error' => 'File signature tidak valid untuk GIF'];
        }
    }
    // WEBP: 52 49 46 46
    elseif ($ext === 'webp') {
        if (substr($bytes, 0, 4) !== 'RIFF') {
            return ['valid' => false, 'error' => 'File signature tidak valid untuk WEBP'];
        }
    }

    return ['valid' => true, 'error' => ''];
}

/**
 * Hapus file gambar soal dari disk
 */
function deleteQuestionImage(?string $imageUrl): void
{
    if (empty($imageUrl)) return;
    $path = __DIR__ . '/' . $imageUrl;
    if (file_exists($path)) {
        unlink($path);
    }
}

/**
 * Cleanup: hapus file gambar yang tidak direferensi oleh soal mana pun
 */
function cleanupOrphanedImages(PDO $pdo): array
{
    $soalDir = __DIR__ . '/assets/soal/';
    if (!is_dir($soalDir)) return [];

    $stmt = $pdo->query("SELECT image_url FROM questions WHERE image_url IS NOT NULL");
    $usedUrls = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $deleted = [];
    foreach (glob($soalDir . '*') as $file) {
        $relative = 'assets/soal/' . basename($file);
        if (!in_array($relative, $usedUrls, true)) {
            unlink($file);
            $deleted[] = $relative;
        }
    }
    return $deleted;
}

/**
 * Log error dengan context (backtrace, request info)
 */
function logError(string $message, array $context = []): void {
    $logFile = __DIR__ . '/logs/error.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] ERROR: $message\n";
    if (!empty($context)) {
        $entry .= "Context: " . json_encode($context, JSON_UNESCAPED_SLASHES) . "\n";
    }
    $entry .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    $entry .= "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "\n";
    $entry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
    $entry .= str_repeat('-', 80) . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

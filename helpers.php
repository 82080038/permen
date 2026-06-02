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
 */
function checkRateLimit(string $ip): bool
{
    $file = sys_get_temp_dir() . '/skd_rate_' . md5($ip) . '.json';
    $data = ['count' => 0, 'time' => 0];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }
    if (time() - $data['time'] > 900) {
        $data = ['count' => 0, 'time' => time()];
    }
    return $data['count'] < 5;
}

/**
 * Rate limiting: increment attempt counter untuk IP
 */
function incrementRateLimit(string $ip): void
{
    $file = sys_get_temp_dir() . '/skd_rate_' . md5($ip) . '.json';
    $data = ['count' => 0, 'time' => 0];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }
    if (time() - $data['time'] > 900) {
        $data = ['count' => 0, 'time' => time()];
    }
    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
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
 */
function appLog(string $level, string $message): void
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

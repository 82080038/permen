<?php
require __DIR__ . '/env_loader.php';

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Enable slow query logging for development
    if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
        $pdo->exec("SET SESSION long_query_time = 1");
    }
} catch (PDOException $e) {
    // Don't leak credentials in error message
    error_log("DB connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan periksa konfigurasi di .env");
}

// Session configuration for security
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', 1); // Prevent session fixation

session_start();

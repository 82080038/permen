<?php
/**
 * Configuration file — SKD CAT-BKN
 * Compatible with existing helpers.php
 */

// Load environment variables
require __DIR__ . '/env_loader.php';

// Database configuration constants (for compatibility with helpers.php)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'skd_cat_bkn');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Application configuration
define('APP_NAME', 'SKD CAT-BKN');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Security configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-secret-key-here');
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600); // 1 hour

// File upload configuration
define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? __DIR__ . '/uploads/');

// Email configuration
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@skdcatbkn.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'SKD CAT-BKN');

// API configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', $_ENV['API_RATE_LIMIT'] ?? 100); // requests per hour

// Database connection (global)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $GLOBALS['pdo'] = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Log error but don't expose details in production
    error_log("Database connection failed: " . $e->getMessage());
    
    if (APP_ENV === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please try again later.");
    }
}

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Start session (before any output)
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration (must be before session_start)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', APP_ENV === 'production');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    session_start();
}

?>

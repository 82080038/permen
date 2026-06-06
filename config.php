<?php
require __DIR__ . '/env_loader.php';

// Global error handler - ONLY log, never output HTML
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    $errorType = $errorTypes[$errno] ?? 'Unknown Error';

    // Log error ONLY - never output anything
    $errorMsg = "[$errorType] $errstr in $errfile on line $errline";
    error_log($errorMsg);

    // Additional logging to audit_logs table for production monitoring
    if (($_ENV['APP_ENV'] ?? 'development') === 'production' && isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'] ?? 0,
                'PHP_ERROR',
                substr($errorMsg, 0, 500),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (Exception $e) {
            // If logging fails, just continue to avoid infinite loop
        }
    }

    return true;
});

// Global exception handler - ONLY log, never output HTML
set_exception_handler(function($exception) {
    $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($errorMsg);

    // Additional logging to audit_logs table for production monitoring
    if (($_ENV['APP_ENV'] ?? 'development') === 'production' && isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'] ?? 0,
                'PHP_EXCEPTION',
                substr($errorMsg, 0, 500),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (Exception $e) {
            // If logging fails, just continue to avoid infinite loop
        }
    }
});

// Register shutdown function for fatal errors - ONLY log, never output HTML
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
    }
});

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

    // Make $pdo available globally for error handlers
    $GLOBALS['pdo'] = $pdo;

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
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection (Lax allows same-site AJAX)
ini_set('session.use_strict_mode', 1); // Prevent session fixation
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Session IP binding and user-agent validation (skip in development for testing)
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (isset($_SESSION['session_ip']) && $_SESSION['session_ip'] !== $currentIp) {
        session_destroy();
        die("Session invalid: IP address changed. Please login again.");
    }
    
    if (isset($_SESSION['session_ua']) && $_SESSION['session_ua'] !== $currentUa) {
        session_destroy();
        die("Session invalid: User agent changed. Please login again.");
    }
    
    // Store IP and UA on first session creation
    if (!isset($_SESSION['session_ip'])) {
        $_SESSION['session_ip'] = $currentIp;
        $_SESSION['session_ua'] = $currentUa;
    }
}

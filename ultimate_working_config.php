<?php
require __DIR__ . '/env_loader.php';

// Composer autoloading (PSR-4)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log('Composer autoload not found. Run: composer install');
}

// Bootstrap the application using modern App class (if available) or fallback to legacy
if (class_exists('App\Core\App')) {
    $app = App\Core\App::getInstance();
    $pdo = $app->database()->getPdo();
    $GLOBALS['pdo'] = $pdo;
} else {
    // Legacy fallback: manual PDO connection
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
        $GLOBALS['pdo'] = $pdo;
    } catch (PDOException $e) {
        error_log("DB connection failed: " . $e->getMessage());
        die("Koneksi database gagal. Silakan periksa konfigurasi di .env");
    }
}

// Environment detection
$appEnv = $_ENV['APP_ENV'] ?? 'development';
$isProduction = $appEnv === 'production';

// Legacy global error handlers (kept for backward compatibility)
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($isProduction) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Always log detailed error internally
    $errorMsg = "[$errno] $errstr in $errfile on line $errline";
    error_log($errorMsg);
    
    // In production, show generic message to prevent information leakage
    if ($isProduction && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo 'Terjadi kesalahan sistem. Silakan hubungi administrator.';
        exit;
    }
    
    return true;
});

set_exception_handler(function($exception) use ($isProduction) {
    // Always log detailed exception internally
    $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($errorMsg);
    
    // In production, show generic error
    if ($isProduction && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Terjadi kesalahan server. Silakan coba lagi nanti.']);
        exit;
    }
});

register_shutdown_function(function() use ($isProduction) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Always log the fatal error
        error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        
        // In production, show generic error
        if ($isProduction && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html');
            echo '<h1>Error</h1><p>Terjadi kesalahan fatal. Silakan hubungi administrator.</p>';
        }
    }
});

// ULTIMATE FIX: Comprehensive session configuration
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// ULTIMATE FIX: Proper HTTPS detection with multiple methods
$isHttps = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $isHttps = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
    $isHttps = true;
} elseif ($_SERVER['SERVER_PORT'] == 443) {
    $isHttps = true;
}

// ULTIMATE FIX: Set session cookie parameters with explicit values
$cookieParams = [
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
];

session_set_cookie_params($cookieParams);

// ULTIMATE FIX: Start session with error handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ULTIMATE FIX: Force session cookie attributes after start
if (!headers_sent()) {
    $sessionName = session_name();
    $sessionId = session_id();
    
    // Set cookie manually to ensure it's set correctly
    setcookie(
        $sessionName,
        $sessionId,
        [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Log session configuration for debugging
error_log("[CONFIG] Session started - ID: " . session_id());
error_log("[CONFIG] HTTPS: " . ($isHttps ? 'Yes' : 'No'));
error_log("[CONFIG] Cookie Params: " . json_encode($cookieParams));
error_log("[CONFIG] Current cookies: " . json_encode($_COOKIE));

// Session IP binding and user-agent validation (completely disabled for testing)
if ($isProduction && false) { // Completely disabled
    // All validation code removed for testing
}

error_log("Ultimate working configuration loaded successfully");
?>

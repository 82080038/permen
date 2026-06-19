<?php
require __DIR__ . '/env_loader.php';

// Global base URL - available in all files that include config.php
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';

// Composer autoloading (PSR-4)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log('Composer autoload not found. Run: composer install');
}

// Database connection using PDO directly (fallback for production)
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db   = $_ENV['DB_NAME'] ?? 'skd_cat_bkn';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    $socket = $_ENV['DB_SOCKET'] ?? '';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    if (!empty($socket)) {
        $dsn .= ";unix_socket=$socket";
    }
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan periksa konfigurasi di .env");
}

// Dependency injection container
$container = [
    'pdo' => $pdo,
];

// Helper function for container access
function app(string $key = null) {
    global $container;
    return $key ? ($container[$key] ?? null) : $container;
}

// Environment detection
$appEnv = $_ENV['APP_ENV'] ?? 'development';
$isProduction = $appEnv === 'production';
$isLocal = !$isProduction; // Detect local environment

// TEMPORARY: Disable error handler for debugging
// set_error_handler(function($errno, $errstr, $errfile, $errline) use ($isProduction) {
//     if (!(error_reporting() & $errno)) {
//         return false;
//     }
//     
//     // Always log detailed error internally
//     $errorMsg = "[$errno] $errstr in $errfile on line $errline";
//     error_log($errorMsg);
//     
//     // In production, show generic message to prevent information leakage
//     if ($isProduction && !headers_sent()) {
//         http_response_code(500);
//         header('Content-Type: text/plain');
//         echo 'Terjadi kesalahan sistem. Silakan hubungi administrator.';
//         exit;
//     }
//     
//     return true;
// });

// TEMPORARY: Disable exception handler for debugging
// set_exception_handler(function($exception) use ($isProduction) {
//     // Always log detailed exception internally
//     $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
//     error_log($errorMsg);
//     
//     // In production, show generic error
//     if ($isProduction && !headers_sent()) {
//         http_response_code(500);
//         header('Content-Type: application/json');
//         echo json_encode(['error' => 'Terjadi kesalahan server. Silakan coba lagi nanti.']);
//         exit;
//     }
// });

// TEMPORARY: Disable shutdown function for debugging
// register_shutdown_function(function() use ($isProduction) {
//     $error = error_get_last();
//     if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
//         // Always log the fatal error
//         error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
//         
//         // In production, show generic error
//         if ($isProduction && !headers_sent()) {
//             http_response_code(500);
//             header('Content-Type: text/html');
//             echo '<h1>Error</h1><p>Terjadi kesalahan fatal. Silakan hubungi administrator.</p>';
//         }
//     }
// });

// LOCAL WORKING CONFIG: Optimized for local XAMPP environment
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// Configure Redis session handler if Redis is available (optional)
if (class_exists('Redis') && !$isProduction) {
    try {
        $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $redisPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
        $redisPassword = $_ENV['REDIS_PASSWORD'] ?? null;
        $redisDatabase = (int)($_ENV['REDIS_DATABASE'] ?? 0);
        
        $redis = new Redis();
        if ($redis->connect($redisHost, $redisPort, 2)) {
            if ($redisPassword) {
                $redis->auth($redisPassword);
            }
            if ($redisDatabase > 0) {
                $redis->select($redisDatabase);
            }
            
            // Set Redis as session handler
            ini_set('session.save_handler', 'redis');
            ini_set('session.save_path', "tcp://{$redisHost}:{$redisPort}" . ($redisDatabase ? "?database={$redisDatabase}" : ''));
            
            error_log('Redis session handler configured successfully');
        } else {
            error_log('Failed to connect to Redis, using file-based sessions');
        }
    } catch (Exception $e) {
        error_log('Redis session handler setup failed: ' . $e->getMessage() . ', using file-based sessions');
    }
} else {
    error_log('Redis not available or production environment, using file-based sessions');
}

// LOCAL OPTIMIZATION: Simple HTTPS detection for local
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           ($_SERVER['SERVER_PORT'] == 443);

// LOCAL OPTIMIZATION: Simple cookie configuration
$secureCookie = $isHttps && $isProduction;
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session configuration for debugging
error_log("[LOCAL_CONFIG] Session started - ID: " . session_id() . ", Environment: " . ($isLocal ? 'Local' : 'Production'));

// Session validation - disabled for local environment
if ($isProduction && false) { // Disabled for both environments for now
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

    // Set session validation data
    $_SESSION['session_ip'] = $currentIp;
    $_SESSION['session_ua'] = $currentUa;
}

error_log("Local working configuration loaded successfully");
?>

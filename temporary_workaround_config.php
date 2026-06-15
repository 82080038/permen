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

// TEMPORARY WORKAROUND: Token-based Authentication
// This bypasses session persistence issues while maintaining security

// Basic session configuration (minimal)
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// HTTPS detection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
           ($_SERVER['SERVER_PORT'] == 443);

// Session cookie configuration (minimal)
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Try to start session (may fail in production)
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sessionWorking = true;
} catch (Exception $e) {
    error_log("Session start failed: " . $e->getMessage());
    $sessionWorking = false;
}

// TEMPORARY WORKAROUND: Token-based Authentication Functions
class TokenAuth {
    private $pdo;
    private $tokenTable = 'auth_tokens';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTokenTable();
    }
    
    private function createTokenTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->tokenTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(255) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                expires_at INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->pdo->exec($sql);
            error_log("[TOKEN_AUTH] Token table created/verified");
        } catch (Exception $e) {
            error_log("[TOKEN_AUTH_ERROR] Failed to create token table: " . $e->getMessage());
        }
    }
    
    public function generateToken($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = time() + 3600; // 1 hour
            
            $sql = "INSERT INTO {$this->tokenTable} (token, user_id, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$token, $userId, $expiresAt]);
            
            error_log("[TOKEN_AUTH] Generated token for user {$userId}");
            return $token;
        } catch (Exception $e) {
            error_log("[TOKEN_AUTH_ERROR] Failed to generate token: " . $e->getMessage());
            return false;
        }
    }
    
    public function validateToken($token) {
        try {
            $sql = "SELECT user_id, expires_at FROM {$this->tokenTable} WHERE token = ? AND expires_at > ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$token, time()]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("[TOKEN_AUTH] Token validated for user {$result['user_id']}");
                return $result['user_id'];
            } else {
                error_log("[TOKEN_AUTH] Invalid or expired token");
                return false;
            }
        } catch (Exception $e) {
            error_log("[TOKEN_AUTH_ERROR] Failed to validate token: " . $e->getMessage());
            return false;
        }
    }
    
    public function revokeToken($token) {
        try {
            $sql = "DELETE FROM {$this->tokenTable} WHERE token = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$token]);
            
            error_log("[TOKEN_AUTH] Token revoked");
            return true;
        } catch (Exception $e) {
            error_log("[TOKEN_AUTH_ERROR] Failed to revoke token: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupExpiredTokens() {
        try {
            $sql = "DELETE FROM {$this->tokenTable} WHERE expires_at < ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([time()]);
            
            $deletedCount = $stmt->rowCount();
            error_log("[TOKEN_AUTH] Cleaned up {$deletedCount} expired tokens");
            return $deletedCount;
        } catch (Exception $e) {
            error_log("[TOKEN_AUTH_ERROR] Failed to cleanup tokens: " . $e->getMessage());
            return 0;
        }
    }
}

// Initialize Token Auth
$tokenAuth = new TokenAuth($pdo);

// TEMPORARY WORKAROUND: Hybrid Authentication System
class HybridAuth {
    private $tokenAuth;
    private $sessionWorking;
    
    public function __construct($tokenAuth, $sessionWorking) {
        $this->tokenAuth = $tokenAuth;
        $this->sessionWorking = $sessionWorking;
    }
    
    public function login($userId, $userData) {
        if ($this->sessionWorking) {
            // Use session if working
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_nama'] = $userData['nama'];
            $_SESSION['user_no_hp'] = $userData['no_hp'];
            $_SESSION['user_email'] = $userData['email'] ?? '';
            $_SESSION['user_role'] = $userData['role'];
            
            // Compatibility variables
            $_SESSION['nama'] = $userData['nama'];
            $_SESSION['email'] = $userData['email'] ?? '';
            $_SESSION['role'] = $userData['role'];
            $_SESSION['no_hp'] = $userData['no_hp'];
            
            error_log("[HYBRID_AUTH] User logged in via session");
            return true;
        } else {
            // Use token-based authentication
            $token = $this->tokenAuth->generateToken($userId);
            if ($token) {
                // Set token as cookie
                setcookie('auth_token', $token, [
                    'expires' => time() + 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $GLOBALS['isHttps'] ?? false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                
                error_log("[HYBRID_AUTH] User logged in via token");
                return true;
            }
            return false;
        }
    }
    
    public function isLoggedIn() {
        if ($this->sessionWorking) {
            return !empty($_SESSION['user_id']);
        } else {
            $token = $_COOKIE['auth_token'] ?? '';
            if ($token) {
                $userId = $this->tokenAuth->validateToken($token);
                if ($userId) {
                    // Load user data from database
                    try {
                        $sql = "SELECT id, nama, no_hp, email, role FROM users WHERE id = ?";
                        $stmt = $GLOBALS['pdo']->prepare($sql);
                        $stmt->execute([$userId]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userData) {
                            // Set global variables for compatibility
                            $_SESSION['user_id'] = $userData['id'];
                            $_SESSION['user_nama'] = $userData['nama'];
                            $_SESSION['user_no_hp'] = $userData['no_hp'];
                            $_SESSION['user_email'] = $userData['email'] ?? '';
                            $_SESSION['user_role'] = $userData['role'];
                            
                            // Compatibility variables
                            $_SESSION['nama'] = $userData['nama'];
                            $_SESSION['email'] = $userData['email'] ?? '';
                            $_SESSION['role'] = $userData['role'];
                            $_SESSION['no_hp'] = $userData['no_hp'];
                            
                            return true;
                        }
                    } catch (Exception $e) {
                        error_log("[HYBRID_AUTH_ERROR] Failed to load user data: " . $e->getMessage());
                    }
                }
            }
            return false;
        }
    }
    
    public function logout() {
        if ($this->sessionWorking) {
            session_destroy();
        } else {
            $token = $_COOKIE['auth_token'] ?? '';
            if ($token) {
                $this->tokenAuth->revokeToken($token);
                setcookie('auth_token', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $GLOBALS['isHttps'] ?? false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
        }
        
        error_log("[HYBRID_AUTH] User logged out");
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'nama' => $_SESSION['user_nama'] ?? '',
                'no_hp' => $_SESSION['user_no_hp'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? ''
            ];
        }
        return null;
    }
}

// Initialize Hybrid Auth
$hybridAuth = new HybridAuth($tokenAuth, $sessionWorking);

// Cleanup expired tokens
$tokenAuth->cleanupExpiredTokens();

// Log configuration status
error_log("[WORKAROUND] Session working: " . ($sessionWorking ? 'Yes' : 'No'));
error_log("[WORKAROUND] Hybrid auth initialized successfully");

error_log("Temporary workaround configuration loaded successfully");
?>

<?php
require '../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Clear all session data
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        $secure = (($_ENV['APP_ENV'] ?? 'development') === 'production') && 
                 (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logout berhasil',
        'redirect' => '/login.php'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Logout gagal: ' . $e->getMessage()
    ]);
}

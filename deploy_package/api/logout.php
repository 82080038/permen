<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

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

    ApiResponse::success([
        'redirect' => '/login.php'
    ], 'Logout berhasil');
} catch (Exception $e) {
    ApiResponse::serverError('Logout gagal: ' . $e->getMessage());
}

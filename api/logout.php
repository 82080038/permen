<?php
require '../config.php';

// Load ApiResponse class with fallback
if (file_exists(__DIR__ . '/../src/Http/ApiResponse.php')) {
    require_once __DIR__ . '/../src/Http/ApiResponse.php';
}

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

// Redirect to login page instead of returning JSON
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
header('Location: ' . $baseUrl . '/pages/login.php');
exit;

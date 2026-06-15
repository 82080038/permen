<?php
require '../config.php';

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

// Redirect to login page
header('Location: ' . BASE_URL . '/pages/login.php');
exit;

<?php
require_once 'config.php';
require_once 'helpers.php';

echo "Testing CSRF token generation:\n";
echo "Session status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";

$token = csrfToken();
echo "Generated token: " . $token . "\n";
echo "Token length: " . strlen($token) . "\n";

// Test escaping
$escaped = e($token);
echo "Escaped token: " . $escaped . "\n";

echo "CSRF token test completed.\n";
?>

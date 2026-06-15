<?php
// Debug login.php CSRF token issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Debug Login CSRF Token ===\n";

// Test 1: Check if files exist
echo "Files check:\n";
echo "config.php exists: " . (file_exists('../config.php') ? 'YES' : 'NO') . "\n";
echo "helpers.php exists: " . (file_exists('../helpers.php') ? 'YES' : 'NO') . "\n";

// Test 2: Load files like login.php does
try {
    echo "\nLoading config.php...\n";
    require '../config.php';
    echo "config.php loaded successfully\n";
    
    echo "Loading helpers.php...\n";
    require '../helpers.php';
    echo "helpers.php loaded successfully\n";
    
} catch (Exception $e) {
    echo "Error loading files: " . $e->getMessage() . "\n";
}

// Test 3: Check session
echo "\nSession status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";

// Test 4: Check CSRF token
echo "\nTesting CSRF token:\n";
$token = csrfToken();
echo "Generated token: " . $token . "\n";
echo "Token length: " . strlen($token) . "\n";

// Test 5: Check escaping
$escaped = e($token);
echo "Escaped token: " . $escaped . "\n";

// Test 6: Simulate login.php environment
echo "\nSimulating login.php environment:\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "CSRF token in session: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n";

echo "\n=== Debug Complete ===\n";
?>

<?php
// Simple test to debug login.php issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting login test...\n";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session started: " . session_id() . "\n";

try {
    // Load config
    require '../config.php';
    echo "Config loaded\n";
    
    // Load helpers
    require '../helpers.php';
    echo "Helpers loaded\n";
    
    // Test CSRF token
    $token = csrfToken();
    echo "CSRF token generated: " . substr($token, 0, 20) . "...\n";
    
    // Output HTML
    echo "Generating HTML...\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed\n";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
</head>
<body>
    <h1>Login Test Page</h1>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>CSRF Token: <?php echo htmlspecialchars(substr($token ?? '', 0, 20)); ?>...</p>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
        <input type="text" name="test" placeholder="Test">
        <button type="submit">Submit</button>
    </form>
</body>
</html>

<?php
// Simple test for session and CSRF
session_start();

require 'config.php';
require 'helpers.php';

// Generate CSRF token
$token = csrfToken();

// Output HTML with CSRF token
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSRF Test</title>
</head>
<body>
    <h1>CSRF Token Test</h1>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>CSRF Token: <?php echo htmlspecialchars($token); ?></p>
    <p>Token Length: <?php echo strlen($token); ?></p>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="text" name="test" placeholder="Test input">
        <button type="submit">Submit</button>
    </form>
    
    <?php if ($_POST): ?>
        <h2>Form Submitted:</h2>
        <p>CSRF Token: <?php echo htmlspecialchars($_POST['csrf_token'] ?? 'NOT SET'); ?></p>
        <p>Test Value: <?php echo htmlspecialchars($_POST['test'] ?? 'NOT SET'); ?></p>
        <p>CSRF Valid: <?php echo validateCsrf($_POST['csrf_token'] ?? '') ? 'YES' : 'NO'; ?></p>
    <?php endif; ?>
</body>
</html>

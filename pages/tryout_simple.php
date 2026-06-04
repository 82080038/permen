<?php
require '../config.php';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];
$sessionId = 1;
?>
<!DOCTYPE html>
<html>
<head><title>Test</title></head>
<body>
<script>
const sessionId = <?= $sessionId ?>;
console.log('Session ID:', sessionId);
console.log('No errors here');
</script>
</body>
</html>

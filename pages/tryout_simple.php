<?php
require '../config.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Redirect ke tryout.php utama, teruskan semua query string
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: tryout.php' . $query);
exit;

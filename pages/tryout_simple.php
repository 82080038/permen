<?php
require '../config.php';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Redirect ke tryout.php utama, teruskan semua query string
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: tryout.php' . $query);
exit;

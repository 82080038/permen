<?php
/**
 * Navigation Router
 * Backward-compatible wrapper — includes the correct navigation file based on user role.
 *
 * Admin pages should use:  require '../includes/nav_admin.php';
 * User pages should use:   require '../includes/nav_user.php';
 * Or keep using:           require '../includes/navigation.php'; (auto-detects)
 */
$_navRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

if ($_navRole === 'admin') {
    require __DIR__ . '/nav_admin.php';
} else {
    require __DIR__ . '/nav_user.php';
}

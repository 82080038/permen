<?php
/**
 * Navigation Component
 * Menampilkan menu navigasi yang konsisten di seluruh halaman
 *
 * @param string $pageTitle Judul halaman
 * @param string $activePage Halaman yang sedang aktif (beranda, latihan, daily_quiz, tryout, leaderboard, feedback, profile, admin_dashboard, user_dashboard)
 * @param bool $showThemeToggle Tampilkan tombol theme toggle (default: false)
 * @param bool $showNotifications Tampilkan tombol notifikasi (default: false)
 */
$pageTitle = $pageTitle ?? 'SKD CAT-BKN';
$activePage = $activePage ?? '';
$showThemeToggle = $showThemeToggle ?? false;
$showNotifications = $showNotifications ?? false;
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? '';

// Helper untuk menentukan apakah menu aktif
function isActive($page, $active) {
    return $page === $active ? 'style="background:#e74c3c;color:#fff;padding:.2rem .5rem;border-radius:4px"' : '';
}
?>
<div class="header">
<h1><?= $pageTitle ?></h1>
<div style="display:flex;align-items:center;gap:.4rem .8rem;flex-wrap:wrap">
<?php if ($showThemeToggle): ?>
<button class="theme-toggle" onclick="toggleTheme()" title="Dark/Light Mode" aria-label="Toggle dark/light mode">🌙</button>
<?php endif; ?>
<?php if ($showNotifications): ?>
<div style="position:relative">
<button onclick="toggleNotifications()" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:.4rem;min-width:44px;min-height:44px" aria-label="Notifikasi">
🔔
<span id="notifBadge" style="position:absolute;top:0;right:0;background:#e74c3c;color:#fff;font-size:.7rem;padding:.1rem .4rem;border-radius:10px;display:none">0</span>
</button>
<div id="notifDropdown" style="display:none;position:absolute;top:100%;right:0;background:#fff;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:300px;max-height:400px;overflow-y:auto;z-index:1000">
<div id="notifList" style="padding:1rem">
<p style="color:#666;font-size:.85rem">Memuat notifikasi...</p>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($userRole === 'admin'): ?>
<a href="../index.php" <?= isActive('beranda', $activePage) ?>>Beranda</a>
<a href="admin_dashboard.php" <?= isActive('admin_dashboard', $activePage) ?>>Dashboard</a>
<a href="latihan.php" <?= isActive('latihan', $activePage) ?>>Latihan</a>
<a href="tryout.php" <?= isActive('tryout', $activePage) ?>>Try Out</a>
<a href="leaderboard.php" <?= isActive('leaderboard', $activePage) ?>>Leaderboard</a>
<a href="feedback.php" <?= isActive('feedback', $activePage) ?>>Feedback</a>
<a href="../api/logout.php">Logout</a>
<?php elseif ($userId): ?>
<a href="../index.php" <?= isActive('beranda', $activePage) ?>>Beranda</a>
<a href="profile.php" <?= isActive('profile', $activePage) ?>>Profil</a>
<a href="latihan.php" <?= isActive('latihan', $activePage) ?>>Latihan</a>
<a href="daily_quiz.php" <?= isActive('daily_quiz', $activePage) ?>>Daily Quiz</a>
<a href="tryout.php" <?= isActive('tryout', $activePage) ?>>Try Out</a>
<a href="leaderboard.php" <?= isActive('leaderboard', $activePage) ?>>Leaderboard</a>
<a href="feedback.php" <?= isActive('feedback', $activePage) ?>>Feedback</a>
<a href="../api/logout.php">Logout</a>
<?php else: ?>
<a href="../index.php" <?= isActive('beranda', $activePage) ?>>Beranda</a>
<a href="latihan.php" <?= isActive('latihan', $activePage) ?>>Latihan</a>
<a href="tryout.php" <?= isActive('tryout', $activePage) ?>>Try Out</a>
<a href="leaderboard.php" <?= isActive('leaderboard', $activePage) ?>>Leaderboard</a>
<a href="login.php">Login</a>
<a href="register.php">Daftar</a>
<?php endif; ?>
</div>
</div>

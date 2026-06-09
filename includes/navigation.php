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

// Detect if navigation is included from pages/ directory or root
// Since base href="/permen/" is set in all pages, use empty basePath
$basePath = '';
$apiPath = '/permen/api/'; // Use absolute path for API calls

// Helper untuk menentukan apakah menu aktif
function isActive($page, $active) {
    return $page === $active ? 'class="active"' : '';
}
?>
<div class="header" role="banner">
<h1><?= $pageTitle ?></h1>
<nav role="navigation" aria-label="Main navigation">
<div style="display:flex;align-items:center;gap:.4rem .8rem;flex-wrap:wrap">
<?php if ($showThemeToggle): ?>
<button class="theme-toggle" onclick="toggleTheme()" title="Dark/Light Mode" aria-label="Toggle dark/light mode">🌙</button>
<?php endif; ?>
<script>
window.isLoggedIn = <?= $userId ? 'true' : 'false' ?>;
</script>

<?php if ($showNotifications): ?>
<div style="position:relative">
<button onclick="toggleNotifications()" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:.4rem;min-width:44px;min-height:44px" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false">
🔔
<span id="notifBadge" style="position:absolute;top:0;right:0;background:#e74c3c;color:#fff;font-size:.7rem;padding:.1rem .4rem;border-radius:10px;display:none" aria-live="polite">0</span>
</button>
<div id="notifDropdown" role="menu" aria-label="Notifikasi dropdown" style="display:none;position:absolute;top:100%;right:0;background:#fff;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:300px;max-height:400px;overflow-y:auto;z-index:1000">
<div id="notifList" style="padding:1rem">
<p style="color:#666;font-size:.85rem">Memuat notifikasi...</p>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($userRole === 'admin'): ?>
<a href="<?= $basePath ?>index.php" <?= isActive('beranda', $activePage) ?> role="menuitem">Beranda</a>
<a href="<?= $basePath ?>admin_dashboard.php" <?= isActive('admin_dashboard', $activePage) ?> role="menuitem">Dashboard</a>
<a href="<?= $basePath ?>admin_scheduled_tryouts.php" <?= isActive('admin_scheduled_tryouts', $activePage) ?> role="menuitem">Scheduled Tryout</a>
<a href="<?= $basePath ?>latihan.php" <?= isActive('latihan', $activePage) ?> role="menuitem">Latihan</a>
<a href="<?= $basePath ?>tryout.php" <?= isActive('tryout', $activePage) ?> role="menuitem">Try Out</a>
<a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
<a href="<?= $basePath ?>feedback.php" <?= isActive('feedback', $activePage) ?> role="menuitem">Feedback</a>
<a href="<?= $basePath ?>help.php" <?= isActive('help', $activePage) ?> role="menuitem">Bantuan</a>
<a href="<?= $apiPath ?>logout.php" role="menuitem">Logout</a>
<?php elseif ($userId): ?>
<a href="<?= $basePath ?>index.php" <?= isActive('beranda', $activePage) ?> role="menuitem">Beranda</a>
<a href="<?= $basePath ?>profile.php" <?= isActive('profile', $activePage) ?> role="menuitem">Profil</a>
<a href="<?= $basePath ?>settings.php" <?= isActive('settings', $activePage) ?> role="menuitem">Pengaturan</a>
<a href="<?= $basePath ?>latihan.php" <?= isActive('latihan', $activePage) ?> role="menuitem">Latihan</a>
<a href="<?= $basePath ?>daily_quiz.php" <?= isActive('daily_quiz', $activePage) ?> role="menuitem">Daily Quiz</a>
<a href="<?= $basePath ?>scheduled_tryouts.php" <?= isActive('scheduled_tryouts', $activePage) ?> role="menuitem">Scheduled Tryout</a>
<a href="<?= $basePath ?>tryout.php" <?= isActive('tryout', $activePage) ?> role="menuitem">Try Out</a>
<a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
<a href="<?= $basePath ?>feedback.php" <?= isActive('feedback', $activePage) ?> role="menuitem">Feedback</a>
<a href="<?= $basePath ?>help.php" <?= isActive('help', $activePage) ?> role="menuitem">Bantuan</a>
<a href="<?= $apiPath ?>logout.php" role="menuitem">Logout</a>
<?php else: ?>
<a href="<?= $basePath ?>index.php" <?= isActive('beranda', $activePage) ?> role="menuitem">Beranda</a>
<a href="<?= $basePath ?>latihan.php" <?= isActive('latihan', $activePage) ?> role="menuitem">Latihan</a>
<a href="<?= $basePath ?>tryout.php" <?= isActive('tryout', $activePage) ?> role="menuitem">Try Out</a>
<a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
<a href="<?= $basePath ?>help.php" <?= isActive('help', $activePage) ?> role="menuitem">Bantuan</a>
<a href="<?= $basePath ?>login.php" role="menuitem">Login</a>
<a href="<?= $basePath ?>register.php" role="menuitem">Daftar</a>
<?php endif; ?>
</div>
</nav>
</div>

<?php // Service Worker Registration - PWA Support ?>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/permen/assets/js/sw.js')
            .then((registration) => {
                console.log('[SW] Registered:', registration.scope);
                
                // Handle updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            console.log('[SW] New version available');
                            // Optionally show update notification to user
                        }
                    });
                });
            })
            .catch((error) => {
                console.error('[SW] Registration failed:', error);
            });
    });
}
</script>

<?php
/**
 * Admin Navigation
 * Menu navigasi khusus untuk administrator
 */
require_once __DIR__ . '/nav_base.php';
?>

<div class="header" role="banner">
  <h1><?= $pageTitle ?></h1>

  <div class="nav-utils">
    <?php if ($showThemeToggle): ?>
    <button class="theme-toggle" onclick="toggleTheme()" title="Dark/Light Mode" aria-label="Toggle dark/light mode">🌙</button>
    <?php endif; ?>

    <?php if ($showNotifications): ?>
    <div style="position:relative">
      <button onclick="toggleNotifications()" style="background:none;border:1px solid rgba(255,255,255,.35);color:#fff;font-size:1rem;cursor:pointer;min-width:40px;min-height:40px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false">
        🔔
        <span id="notifBadge" style="position:absolute;top:2px;right:2px;background:#e74c3c;color:#fff;font-size:.65rem;padding:.1rem .35rem;border-radius:10px;display:none;pointer-events:none" aria-live="polite">0</span>
      </button>
      <div id="notifDropdown" role="menu" aria-label="Notifikasi dropdown" style="display:none;position:absolute;top:100%;right:0;background:#fff;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:300px;max-height:400px;overflow-y:auto;z-index:1000">
        <div id="notifList" style="padding:1rem">
          <p style="color:#666;font-size:.85rem">Memuat notifikasi...</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <button class="nav-hamburger" id="navHamburger" aria-expanded="false" aria-controls="navMenu" aria-label="Buka menu navigasi">
      <span></span><span></span><span></span>
    </button>
  </div>

  <nav id="navMenu" class="nav-menu" role="navigation" aria-label="Admin navigation">
    <a href="<?= $basePath ?>admin_dashboard.php" <?= isActive('admin_dashboard', $activePage) ?> role="menuitem">Dashboard</a>
    <a href="<?= $basePath ?>admin_users.php" <?= isActive('admin_users', $activePage) ?> role="menuitem">Kelola Pengguna</a>
    <a href="<?= $basePath ?>admin_scheduled_tryouts.php" <?= isActive('admin_scheduled_tryouts', $activePage) ?> role="menuitem">Scheduled Tryout</a>
    <a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
    <a href="<?= $apiPath ?>logout.php" role="menuitem">Logout</a>

<?php require __DIR__ . '/nav_footer.php'; ?>

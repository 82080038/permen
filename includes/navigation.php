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
$_navBase = rtrim($_ENV['BASE_URL'] ?? '/', '/') . '/';
$basePath = $_navBase . 'pages/';
$apiPath = $_navBase . 'api/';

// Helper untuk menentukan apakah menu aktif
function isActive($page, $active) {
    return $page === $active ? 'class="active"' : '';
}
?>
<?php
// Inject Bootstrap hanya sekali — cegah duplikasi jika navigation di-include lebih dari sekali
if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
    echo '<link rel="stylesheet" href="' . $_navBase . 'assets/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="' . $_navBase . 'assets/css/bootstrap-icons.min.css">';
}
?>
<style>
/* ── Navbar Shell ── */
.header{background:#1a5276;color:#fff;padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center;position:relative;z-index:100}
.header h1{font-size:1.1rem;white-space:nowrap;margin:0;flex:1}

/* ── Utility icons (theme/notif) — selalu tampil ── */
.nav-utils{display:flex;align-items:center;gap:.3rem}
.nav-utils button{background:none;border:1px solid rgba(255,255,255,.35);color:#fff;border-radius:4px;cursor:pointer;min-height:40px;min-width:40px;font-size:1rem;display:inline-flex;align-items:center;justify-content:center}

/* ── Hamburger button — hanya tampil di mobile ── */
.nav-hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;background:none;border:1px solid rgba(255,255,255,.4);border-radius:4px;padding:.4rem .5rem;cursor:pointer;min-height:44px;min-width:44px;align-items:center}
.nav-hamburger span{display:block;width:22px;height:2px;background:#fff;border-radius:2px;transition:transform .25s,opacity .25s}
.nav-hamburger[aria-expanded="true"] span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.nav-hamburger[aria-expanded="true"] span:nth-child(2){opacity:0}
.nav-hamburger[aria-expanded="true"] span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* ── Desktop: nav links tampil horizontal ── */
.nav-menu{display:flex;align-items:center;gap:.2rem .5rem;flex-wrap:wrap}
.nav-menu a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;min-width:44px;display:inline-flex;align-items:center;padding:0 .45rem;border-radius:4px;transition:background .15s}
.nav-menu a:hover{background:rgba(255,255,255,.15)}
.nav-menu a.active{background:#e74c3c}

/* ── Mobile (<= 768px): hamburger muncul, nav-menu collapse ── */
@media(max-width:768px){
  .nav-hamburger{display:inline-flex}
  .nav-menu{
    display:none;
    flex-direction:column;
    align-items:stretch;
    position:absolute;
    top:100%;left:0;right:0;
    background:#1a5276;
    border-top:1px solid rgba(255,255,255,.2);
    box-shadow:0 4px 12px rgba(0,0,0,.25);
    padding:.5rem 0;
    z-index:200
  }
  .nav-menu.open{display:flex}
  .nav-menu a{font-size:.95rem;padding:.75rem 1.2rem;min-height:48px;border-radius:0;border-bottom:1px solid rgba(255,255,255,.08)}
  .nav-menu a:last-child{border-bottom:none}
  .nav-menu a.active{background:#c0392b}
  .header{flex-wrap:nowrap}
}
</style>

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

  <nav id="navMenu" class="nav-menu" role="navigation" aria-label="Main navigation">
    <?php if ($userRole === 'admin'): ?>
    <a href="<?= $basePath ?>admin_dashboard.php" <?= isActive('admin_dashboard', $activePage) ?> role="menuitem">Dashboard</a>
    <a href="<?= $basePath ?>admin_users.php" <?= isActive('admin_users', $activePage) ?> role="menuitem">Kelola Pengguna</a>
    <a href="<?= $basePath ?>admin_scheduled_tryouts.php" <?= isActive('admin_scheduled_tryouts', $activePage) ?> role="menuitem">Scheduled Tryout</a>
    <a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
    <a href="<?= $apiPath ?>logout.php" role="menuitem">Logout</a>
    <?php elseif ($userId): ?>
    <a href="<?= $_navBase ?>" <?= isActive('beranda', $activePage) ?> role="menuitem">Beranda</a>
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
    <a href="<?= $_navBase ?>" <?= isActive('beranda', $activePage) ?> role="menuitem">Beranda</a>
    <a href="<?= $basePath ?>latihan.php" <?= isActive('latihan', $activePage) ?> role="menuitem">Latihan</a>
    <a href="<?= $basePath ?>tryout.php" <?= isActive('tryout', $activePage) ?> role="menuitem">Try Out</a>
    <a href="<?= $basePath ?>leaderboard.php" <?= isActive('leaderboard', $activePage) ?> role="menuitem">Leaderboard</a>
    <a href="<?= $basePath ?>help.php" <?= isActive('help', $activePage) ?> role="menuitem">Bantuan</a>
    <a href="<?= $basePath ?>login.php" role="menuitem">Login</a>
    <a href="<?= $basePath ?>register.php" role="menuitem">Daftar</a>
    <?php endif; ?>
  </nav>
</div>

<script>
window.isLoggedIn = <?= $userId ? 'true' : 'false' ?>;
(function(){
  var btn = document.getElementById('navHamburger');
  var menu = document.getElementById('navMenu');
  if (!btn || !menu) return;
  btn.addEventListener('click', function(){
    var open = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // Tutup menu saat klik di luar
  document.addEventListener('click', function(e){
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
  // Tutup menu saat ESC
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      btn.focus();
    }
  });
  // Tutup menu saat link diklik (navigasi mobile)
  menu.querySelectorAll('a').forEach(function(a){
    a.addEventListener('click', function(){
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    });
  });
})();
</script>
<?php // Service Worker Registration - PWA Support ?>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?php echo $_navBase; ?>assets/js/sw.js')
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
<?php if (defined('BOOTSTRAP_LOADED')): ?>
<script src="<?php echo $_navBase; ?>assets/js/bootstrap.bundle.min.js" defer></script>
<?php endif; ?>

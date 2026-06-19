<?php
/**
 * Navigation Base Component
 * Shared variables, styles, and helper functions for all navigation variants.
 *
 * @param string $pageTitle Judul halaman
 * @param string $activePage Halaman yang sedang aktif
 * @param bool $showThemeToggle Tampilkan tombol theme toggle (default: false)
 * @param bool $showNotifications Tampilkan tombol notifikasi (default: false)
 */
$pageTitle = $pageTitle ?? 'SKD CAT-BKN';
$activePage = $activePage ?? '';
$showThemeToggle = $showThemeToggle ?? false;
$showNotifications = $showNotifications ?? false;
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

// Base URL paths
$_navBase = rtrim($_ENV['BASE_URL'] ?? '/', '/') . '/';
$basePath = $_navBase . 'pages/';
$apiPath = $_navBase . 'api/';

// Helper untuk menentukan apakah menu aktif
if (!function_exists('isActive')) {
    function isActive($page, $active) {
        return $page === $active ? 'class="active"' : '';
    }
}
?>
<?php
// Inject Bootstrap hanya sekali
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

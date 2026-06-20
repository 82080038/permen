  </nav>
</div>

<script>
window.isLoggedIn = <?= $userId ? 'true' : 'false' ?>;
window.APP_BASE_URL = '<?php echo rtrim($_navBase, "/"); ?>';
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
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('[SW] New version available');
                            showUpdateBanner();
                        }
                    });
                });
                // Check for updates every 60 seconds
                setInterval(() => registration.update(), 60000);
            })
            .catch((error) => {
                console.error('[SW] Registration failed:', error);
            });
    });
}
function showUpdateBanner() {
    if (document.getElementById('sw-update-banner')) return;
    var banner = document.createElement('div');
    banner.id = 'sw-update-banner';
    banner.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#27ae60;color:#fff;padding:.6rem 1rem;text-align:center;z-index:9999;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:.8rem';
    banner.innerHTML = '<span>Versi baru tersedia!</span><button onclick="applyUpdate()" style="background:#fff;color:#27ae60;border:none;padding:.3rem .8rem;border-radius:4px;cursor:pointer;font-weight:bold">Update Sekarang</button><button onclick="this.parentElement.remove()" style="background:transparent;color:#fff;border:1px solid rgba(255,255,255,.5);padding:.3rem .6rem;border-radius:4px;cursor:pointer">Nanti</button>';
    document.body.insertBefore(banner, document.body.firstChild);
    document.body.style.paddingTop = banner.offsetHeight + 'px';
}
function applyUpdate() {
    if (navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
    }
    window.location.reload();
}
</script>
<?php if (defined('BOOTSTRAP_LOADED')): ?>
<script src="<?php echo $_navBase; ?>assets/js/bootstrap.bundle.min.js" defer></script>
<?php endif; ?>

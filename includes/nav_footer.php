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

<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<link rel="manifest" href="manifest.json">
<title>SKD CAT-BKN Try Out & Bimbel</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;flex-wrap:wrap;gap:.5rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header nav{display:flex;flex-wrap:wrap;gap:.4rem .8rem}
.header nav a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;padding:.25rem 0}
.hero{background:#2980b9;color:#fff;text-align:center;padding:2.5rem 1rem}
.hero h2{font-size:1.7rem;margin-bottom:.5rem}.hero p{font-size:1rem;opacity:.9}
.cta{margin-top:1.2rem}.cta a{display:inline-block;background:#e74c3c;color:#fff;padding:.75rem 1.2rem;border-radius:5px;text-decoration:none;font-weight:bold;margin:.3rem;font-size:.95rem;min-width:44px;min-height:44px}
.cta a.secondary{background:#27ae60}
.features{max-width:1000px;margin:2rem auto;padding:0 1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.2rem}
.feature{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.feature h3{color:#1a5276;margin-bottom:.5rem;font-size:1.05rem}.feature p{color:#555;font-size:.9rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:480px){
.hero{padding:2rem .8rem}
.hero h2{font-size:1.35rem}
.header h1{font-size:1rem}
.cta a{display:block;width:100%;margin:.3rem 0}
.features{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="header">
<h1>SKD CAT-BKN</h1>
<nav>
<a href="pages/materi.php?subtes=TWK">Materi</a>
<a href="pages/latihan.php">Latihan</a>
<a href="pages/tryout.php">Try Out</a>
<?php if (!empty($_SESSION['user_id'])): ?>
<a href="pages/user_dashboard.php">Dashboard</a>
<a href="api/logout.php">Logout</a>
<?php else: ?>
<a href="pages/login.php">Login</a>
<a href="pages/register.php">Daftar</a>
<?php endif; ?>
</nav>
</div>
<div class="hero">
<h2>Siapkan Diri untuk SKD Sekolah Kedinasan</h2>
<p>Aplikasi Try Out & Bimbel berdasarkan Permen PANRB No. 20/2021 & KepmenPANRB No. 208/2025</p>
<div class="cta">
<a href="pages/tryout.php">Mulai Try Out</a>
<a href="pages/latihan.php" class="secondary">Latihan per Subtes</a>
<a href="pages/materi.php?subtes=TWK" style="background:#f39c12;display:inline-block;color:#fff;padding:.8rem 1.5rem;border-radius:5px;text-decoration:none;font-weight:bold;margin:.3rem">Pelajari Materi</a>
</div>
</div>
<div class="features">
<div class="feature">
<h3>TWK — Wawasan Kebangsaan</h3>
<p>Materi lengkap: Nasionalisme, Integritas, Bela Negara, Pilar Negara, dan Bahasa Indonesia yang baik dan benar.</p>
</div>
<div class="feature">
<h3>TIU — Intelegensia Umum</h3>
<p>Panduan verbal (analogi, silogisme, analitis), numerik (berhitung, deret, perbandingan, cerita), dan figural.</p>
</div>
<div class="feature">
<h3>TKP — Karakteristik Pribadi</h3>
<p>Tips skoring 1–5: Pelayanan publik, jejaring kerja, sosial budaya, teknologi informasi, dan profesionalisme.</p>
</div>
<div class="feature">
<h3>Latihan Fokus per Subtes</h3>
<p>Latihan TWK (30 soal/30 menit), TIU (35 soal/35 menit), atau TKP (45 soal/45 menit) secara terpisah untuk memperkuat pemahaman.</p>
</div>
<div class="feature">
<h3>Simulasi CAT BKN</h3>
<p>Try out penuh 110 soal dalam 110 menit dengan timer sinkron database, navigasi soal, dan perhitungan nilai ambang batas otomatis.</p>
</div>
</div>
<div class="footer">
Dibangun berdasarkan Peraturan Menteri PANRB No. 20 Tahun 2021.<br>
Disclaimer: Aplikasi ini merupakan sarana latihan mandiri. Kelulusan ditentukan oleh BKN dan instansi terkait.
</div>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').then(() => {
    console.log('Service Worker registered');
  }).catch(err => {
    console.log('SW registration failed:', err);
  });
}
</script>
</body>
</html>

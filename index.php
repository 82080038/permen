<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<link rel="manifest" href="manifest.json">
<link rel="stylesheet" href="assets/style.css">
<title>SKD CAT-BKN Try Out & Bimbel</title>
<style>
/* Modern Landing Page Styles */
.landing-hero {
    background: linear-gradient(135deg, #1a5276 0%, #2980b9 50%, #3498db 100%);
    color: #fff;
    text-align: center;
    padding: 4rem 1rem 3rem;
    position: relative;
    overflow: hidden;
}

.landing-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.landing-hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.landing-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
    line-height: 1.2;
}

.landing-hero p {
    font-size: 1.1rem;
    opacity: 0.95;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.landing-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

.landing-cta a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    min-width: 160px;
    min-height: 50px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.landing-cta a:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.landing-cta .primary {
    background: #e74c3c;
    color: #fff;
}

.landing-cta .primary:hover {
    background: #c0392b;
}

.landing-cta .secondary {
    background: #27ae60;
    color: #fff;
}

.landing-cta .secondary:hover {
    background: #219150;
}

.landing-cta .tertiary {
    background: #f39c12;
    color: #fff;
}

.landing-cta .tertiary:hover {
    background: #e67e22;
}

/* Statistics Section */
.landing-stats {
    background: #fff;
    padding: 3rem 1rem;
    margin-top: -2rem;
    position: relative;
    z-index: 2;
    box-shadow: 0 -10px 30px rgba(0,0,0,0.1);
}

.stats-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.stat-card {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1a5276;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #555;
    font-weight: 500;
}

/* Features Section */
.landing-features {
    max-width: 1200px;
    margin: 3rem auto;
    padding: 0 1rem;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    color: #1a5276;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 2rem;
    font-size: 1rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.feature-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #2980b9;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.feature-card h3 {
    color: #1a5276;
    margin-bottom: 0.8rem;
    font-size: 1.2rem;
}

.feature-card p {
    color: #555;
    font-size: 0.95rem;
    line-height: 1.6;
}

/* Testimonials Section */
.landing-testimonials {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 4rem 1rem;
    margin: 3rem 0;
}

.testimonials-container {
    max-width: 1200px;
    margin: 0 auto;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.testimonial-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-5px);
}

.testimonial-text {
    font-style: italic;
    color: #555;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.testimonial-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2980b9, #3498db);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.2rem;
}

.testimonial-name {
    font-weight: 600;
    color: #1a5276;
}

.testimonial-instansi {
    font-size: 0.85rem;
    color: #777;
}

/* CTA Section */
.landing-cta-section {
    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
    color: #fff;
    text-align: center;
    padding: 3rem 1rem;
}

.landing-cta-section h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.landing-cta-section p {
    margin-bottom: 2rem;
    opacity: 0.9;
}

.landing-footer {
    text-align: center;
    padding: 2rem 1rem;
    color: #777;
    font-size: 0.9rem;
    background: #f8f9fa;
}

/* Responsive Design */
@media(max-width:768px){
    .landing-hero h1 { font-size: 1.8rem; }
    .landing-hero p { font-size: 1rem; }
    .landing-cta { flex-direction: column; }
    .landing-cta a { width: 100%; }
    .stat-number { font-size: 2rem; }
    .features-grid { grid-template-columns: 1fr; }
    .testimonials-grid { grid-template-columns: 1fr; }
    .section-title { font-size: 1.5rem; }
}

@media(max-width:480px){
    .landing-hero { padding: 3rem 1rem 2rem; }
    .landing-hero h1 { font-size: 1.5rem; }
    .landing-cta a { padding: 0.9rem 1.5rem; font-size: 0.95rem; }
    .stats-grid { grid-template-columns: 1fr 1fr; gap: 1rem; }
    .stat-card { padding: 1rem; }
    .stat-number { font-size: 1.8rem; }
}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'SKD CAT-BKN'; $activePage = 'beranda'; ?>
<?php require 'includes/navigation.php'; ?>

<!-- Hero Section -->
<div class="landing-hero" id="main-content">
    <div class="landing-hero-content">
        <h1>Siapkan Diri untuk SKD Sekolah Kedinasan</h1>
        <p>Platform Try Out & Bimbel berdasarkan Permen PANRB No. 20/2021 & KepmenPANRB No. 208/2025. Latihan dengan simulasi CAT BKN yang akurat.</p>
        <div class="landing-cta">
            <a href="pages/register.php" class="primary">Daftar Sekarang</a>
            <a href="pages/login.php" class="secondary">Masuk</a>
            <a href="pages/tryout.php" class="tertiary">Mulai Try Out</a>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="landing-stats">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number" id="user-count">0</div>
            <div class="stat-label">Peserta Terdaftar</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="tryout-count">0</div>
            <div class="stat-label">Tryout Selesai</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="question-count">0</div>
            <div class="stat-label">Soal Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="active-users">0</div>
            <div class="stat-label">User Aktif (30 hari)</div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="landing-features">
    <h2 class="section-title">Fitur Unggulan</h2>
    <p class="section-subtitle">Semua yang Anda butuhkan untuk persiapan SKD</p>
    <div class="features-grid">
        <div class="feature-card">
            <h3>📚 Materi Lengkap TWK</h3>
            <p>Materi komprehensif: Nasionalisme, Integritas, Bela Negara, Pilar Negara, dan Bahasa Indonesia yang baik dan benar.</p>
        </div>
        <div class="feature-card">
            <h3>🧠 Panduan TIU</h3>
            <p>Panduan verbal (analogi, silogisme, analitis), numerik (berhitung, deret, perbandingan), dan figural dengan contoh soal.</p>
        </div>
        <div class="feature-card">
            <h3>💡 Tips Skoring TKP</h3>
            <p>Strategi menjawab TKP dengan skoring 1-5: Pelayanan publik, jejaring kerja, sosial budaya, dan profesionalisme.</p>
        </div>
        <div class="feature-card">
            <h3>⏱️ Latihan per Subtes</h3>
            <p>Latihan fokus TWK (30 soal/30 menit), TIU (35 soal/35 menit), atau TKP (45 soal/45 menit) untuk memperkuat pemahaman.</p>
        </div>
        <div class="feature-card">
            <h3>🎯 Simulasi CAT BKN</h3>
            <p>Try out penuh 110 soal dalam 110 menit dengan timer sinkron database, navigasi soal, dan perhitungan nilai otomatis.</p>
        </div>
        <div class="feature-card">
            <h3>📊 Dashboard Analisis</h3>
            <p>Tracking performa Anda dengan statistik lengkap, rekomendasi materi berdasarkan kelemahan, dan perbandingan dengan passing grade.</p>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="landing-testimonials">
    <div class="testimonials-container">
        <h2 class="section-title">Apa Kata Peserta?</h2>
        <p class="section-subtitle">Pengalaman dari peserta yang telah menggunakan platform kami</p>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-text">"Platform ini sangat membantu persiapan saya. Simulasi CAT BKN-nya sangat akurat dengan ujian asli. Saya berhasil lulus SKD IPDN!"</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">A</div>
                    <div>
                        <div class="testimonial-name">Ahmad R.</div>
                        <div class="testimonial-instansi">Lulusan IPDN 2024</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-text">"Materi lengkap dan soal-soalnya relevan dengan Permen PANRB. Dashboard analisisnya membantu saya fokus di topik yang lemah."</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">S</div>
                    <div>
                        <div class="testimonial-name">Siti M.</div>
                        <div class="testimonial-instansi">Lulusan STAN 2024</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-text">"Fitur latihan per subtes sangat berguna untuk pemahaman mendalam. Timer sinkron database membuat simulasi lebih realistis."</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">B</div>
                    <div>
                        <div class="testimonial-name">Budi P.</div>
                        <div class="testimonial-instansi">Peserta SKD 2025</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="landing-cta-section">
    <h2>Siap Memulai Persiapan Anda?</h2>
    <p>Bergabung dengan ribuan peserta lainnya dan raih impian Anda masuk sekolah kedinasan.</p>
    <div class="landing-cta">
        <a href="pages/register.php" class="primary" style="background:#fff;color:#1a5276">Daftar Gratis</a>
        <a href="pages/tryout.php" class="secondary" style="background:#e74c3c;color:#fff">Coba Try Out</a>
    </div>
</div>

<!-- Footer -->
<div class="landing-footer">
    Dibangun berdasarkan Peraturan Menteri PANRB No. 20 Tahun 2021.<br>
    Disclaimer: Aplikasi ini merupakan sarana latihan mandiri. Kelulusan ditentukan oleh BKN dan instansi terkait.
</div>

<script>
// Mobile menu toggle
const hamburger = document.querySelector('.hamburger');
const mobileMenu = document.querySelector('.mobile-menu');
if (hamburger && mobileMenu) {
  hamburger.addEventListener('click', () => {
    const isExpanded = hamburger.getAttribute('aria-expanded') === 'true';
    hamburger.setAttribute('aria-expanded', !isExpanded);
    mobileMenu.classList.toggle('active');
  });
}

// Fetch and animate statistics
async function fetchStats() {
    try {
        const response = await fetch('/permen/api/get_landing_stats.php');
        const data = await response.json();
        if (data.success) {
            animateCounter('user-count', data.data.user_count);
            animateCounter('tryout-count', data.data.tryout_count);
            animateCounter('question-count', data.data.question_count);
            animateCounter('active-users', data.data.active_users);
        }
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    }
}

function animateCounter(elementId, target) {
    const element = document.getElementById(elementId);
    const duration = 2000;
    const steps = 60;
    const increment = target / steps;
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toLocaleString('id-ID');
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString('id-ID');
        }
    }, duration / steps);
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.feature-card, .testimonial-card, .stat-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(card);
});

// Initialize
fetchStats();

// Unregister existing service worker (cleanup)
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(registration => {
      registration.unregister();
      console.log('Service Worker unregistered');
    });
  });
}
</script>
</body>
</html>

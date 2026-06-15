<?php
require_once 'config.php';
require_once 'helpers.php';

// Get landing statistics
$stats = getLandingStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#1a5276">
    <link rel="stylesheet" href="/assets/style.css">
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
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
}

.landing-cta .secondary:hover {
    background: #fff;
    color: #1a5276;
}

.landing-cta .tertiary {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
}

.landing-cta .tertiary:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
}

.landing-stats {
    padding: 4rem 1rem;
    background: #f8f9fa;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1a5276;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
}

.landing-features {
    padding: 4rem 1rem;
    background: white;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.feature-card {
    padding: 2rem;
    border-radius: 12px;
    background: #f8f9fa;
    text-align: center;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.feature-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1a5276;
    margin-bottom: 1rem;
}

.feature-description {
    color: #666;
    line-height: 1.6;
}

.landing-testimonials {
    padding: 4rem 1rem;
    background: #f8f9fa;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.testimonial-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.testimonial-text {
    font-style: italic;
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.testimonial-author {
    font-weight: 600;
    color: #1a5276;
}

.testimonial-role {
    color: #999;
    font-size: 0.9rem;
}

.landing-cta-section {
    padding: 4rem 1rem;
    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
    color: white;
    text-align: center;
}

.cta-content h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.1rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .landing-hero h1 {
        font-size: 2rem;
    }
    
    .landing-hero p {
        font-size: 1rem;
    }
    
    .landing-cta {
        flex-direction: column;
        align-items: center;
    }
    
    .landing-cta a {
        width: 100%;
        max-width: 280px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .features-grid,
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
}
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>SKD CAT-BKN</h1>
            </div>
            <nav class="nav">
                <a href="pages/login.php">Masuk</a>
                <a href="pages/register.php" class="btn-primary">Daftar</a>
            </nav>
        </div>
    </header>

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
                <div class="stat-number" id="user-count"><?php echo number_format($stats['user_count'], 0, ',', '.'); ?></div>
                <div class="stat-label">Peserta Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="tryout-count"><?php echo number_format($stats['tryout_count'], 0, ',', '.'); ?></div>
                <div class="stat-label">Tryout Selesai</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="question-count"><?php echo number_format($stats['question_count'], 0, ',', '.'); ?></div>
                <div class="stat-label">Soal Tersedia</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-users"><?php echo number_format($stats['active_users'], 0, ',', '.'); ?></div>
                <div class="stat-label">Pengguna Aktif</div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="landing-features">
        <div class="container">
            <h2 style="text-align: center; color: #1a5276; margin-bottom: 3rem;">Fitur Unggulan</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3 class="feature-title">Tryout CAT BKN</h3>
                    <p class="feature-description">Simulasi tryout dengan sistem CAT BKN yang akurat, 110 soal dalam 110 menit sesuai standar resmi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3 class="feature-title">Materi Lengkap</h3>
                    <p class="feature-description">Materi pembelajaran komprehensif untuk TWK, TIU, dan TKP dengan contoh soal dan pembahasan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Analisis Hasil</h3>
                    <p class="feature-description">Analisis mendalam tentang hasil tryout dengan grafik dan rekomendasi materi perlu dipelajari.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3 class="feature-title">Latihan Targeted</h3>
                    <p class="feature-description">Latihan soal per subtes dengan tingkat kesulitan yang dapat disesuaikan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3 class="feature-title">Leaderboard</h3>
                    <p class="feature-description">Peringkat nasional untuk memotivasi dan melihat perkembangan dibandingkan peserta lain.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 class="feature-title">Mobile Friendly</h3>
                    <p class="feature-description">Akses dimana saja dan kapan saja melalui perangkat mobile dengan desain responsif.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="landing-testimonials">
        <div class="container">
            <h2 style="text-align: center; color: #1a5276; margin-bottom: 3rem;">Apa Kata Mereka</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">"Platform ini sangat membantu saya dalam persiapan SKD. Simulasi CAT yang akurat membuat saya lebih percaya diri."</p>
                    <div class="testimonial-author">Ahmad Rizki</div>
                    <div class="testimonial-role">Peserta STAN</div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Materinya lengkap dan pembahasannya detail. Saya berhasil lolos SKD berkat latihan di sini."</p>
                    <div class="testimonial-author">Siti Nurhaliza</div>
                    <div class="testimonial-role">Peserta IPDN</div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"Analisis hasilnya sangat membantu saya mengetahui kelemahan dan memperbaikinya sebelum hari H."</p>
                    <div class="testimonial-author">Budi Santoso</div>
                    <div class="testimonial-role">Peserta STIS</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="landing-cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Siap Menggapai Cita-cita Anda?</h2>
                <p>Bergabung dengan ribuan peserta lain yang telah berhasil lolos SKD Sekolah Kedinasan</p>
                <div class="landing-cta">
                    <a href="pages/register.php" class="primary">Daftar Gratis</a>
                    <a href="pages/login.php" class="secondary">Masuk Sekarang</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>SKD CAT-BKN</h3>
                    <p>Platform Try Out & Bimbel SKD Sekolah Kedinasan terpercaya</p>
                </div>
                <div class="footer-section">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="pages/login.php">Masuk</a></li>
                        <li><a href="pages/register.php">Daftar</a></li>
                        <li><a href="pages/materi.php">Materi</a></li>
                        <li><a href="pages/leaderboard.php">Leaderboard</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Bantuan</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Cara Penggunaan</a></li>
                        <li><a href="#">Kontak</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 SKD CAT-BKN. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
    // Production-ready JavaScript without console logs
    (function() {
        'use strict';

        // Service Worker Registration (Production)
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/assets/js/sw.js')
                    .then(function(registration) {
                        // Service Worker registered successfully
                        registration.addEventListener('updatefound', function() {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available - could show notification
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        // Service Worker registration failed - silent in production
                    });
            });
        }

        // Statistics Counter Animation
        function animateCounter(elementId, target) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            
            const timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    element.textContent = target.toLocaleString('id-ID');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString('id-ID');
                }
            }, duration / steps);
        }

        // Initialize counters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Animate statistics
            const userCount = parseInt(document.getElementById('user-count').textContent.replace(/\./g, ''));
            const tryoutCount = parseInt(document.getElementById('tryout-count').textContent.replace(/\./g, ''));
            const questionCount = parseInt(document.getElementById('question-count').textContent.replace(/\./g, ''));
            const activeUsers = parseInt(document.getElementById('active-users').textContent.replace(/\./g, ''));

            animateCounter('user-count', userCount);
            animateCounter('tryout-count', tryoutCount);
            animateCounter('question-count', questionCount);
            animateCounter('active-users', activeUsers);
        });

        // Intersection Observer for animations
        if ('IntersectionObserver' in window) {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe feature cards and testimonial cards
            document.querySelectorAll('.feature-card, .testimonial-card, .stat-card').forEach(function(card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Form validation helper
        function validateForm(form) {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });
            
            return isValid;
        }

        // Add form validation to all forms
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                }
            });
        });

    })();
    </script>
    <script src="/assets/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>

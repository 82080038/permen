<?php
/**
 * Onboarding Tour Component
 * Menampilkan tour untuk first-time users
 *
 * @param bool $showOnboarding - Whether to show onboarding
 */
if (!isset($showOnboarding)) {
    $showOnboarding = false;
}

// Check if user has seen onboarding
$onboardingSeen = $_SESSION['onboarding_seen'] ?? false;
$showTour = $showOnboarding && !$onboardingSeen;
?>
<?php if ($showTour): ?>
<div id="onboarding-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:10004;display:flex;align-items:center;justify-content:center">
    <div id="onboarding-modal" style="background:#fff;border-radius:12px;padding:2rem;max-width:500px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.3);animation:fadeIn 0.4s cubic-bezier(0.4,0,0.2,1)">
        <div id="onboarding-step-1" class="onboarding-step">
            <h2 style="color:#1a5276;margin-bottom:1rem;font-size:1.3rem">Selamat Datang di SKD CAT-BKN!</h2>
            <p style="color:#555;line-height:1.6;margin-bottom:1.5rem">
                Aplikasi ini membantu Anda mempersiapkan diri untuk Seleksi Kompetensi Dasar (SKD) Sekolah Kedinasan dengan simulasi CAT BKN yang akurat.
            </p>
            <div style="background:#f0f7ff;padding:1rem;border-radius:8px;margin-bottom:1.5rem">
                <h3 style="color:#2980b9;font-size:1rem;margin-bottom:0.5rem">Fitur Utama:</h3>
                <ul style="color:#555;font-size:0.9rem;margin-left:1.2rem;line-height:1.6">
                    <li>Try Out SKD dengan timer sinkron database</li>
                    <li>Latihan per subtes (TWK, TIU, TKP)</li>
                    <li>Materi lengkap dengan uji pemahaman</li>
                    <li>Dashboard analisis performa</li>
                    <li>Leaderboard untuk kompetisi sehat</li>
                </ul>
            </div>
        </div>
        
        <div id="onboarding-step-2" class="onboarding-step" style="display:none">
            <h2 style="color:#1a5276;margin-bottom:1rem;font-size:1.3rem">Mulai Try Out</h2>
            <p style="color:#555;line-height:1.6;margin-bottom:1.5rem">
                Try Out SKD mensimulasikan ujian CAT BKN yang sebenarnya dengan 110 soal dalam 110 menit. Timer akan sinkron dengan database, jadi pastikan koneksi internet stabil.
            </p>
            <div style="background:#fffbea;padding:1rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #f1c40f">
                <p style="color:#555;font-size:0.9rem;margin:0">
                    <strong>Tips:</strong> Gunakan keyboard shortcut untuk navigasi cepat:
                </p>
                <ul style="color:#555;font-size:0.85rem;margin-left:1.2rem;margin-top:0.5rem">
                    <li>Alt + H: Beranda</li>
                    <li>Alt + L: Latihan</li>
                    <li>Alt + T: Try Out</li>
                    <li>Alt + D: Dashboard</li>
                </ul>
            </div>
        </div>
        
        <div id="onboarding-step-3" class="onboarding-step" style="display:none">
            <h2 style="color:#1a5276;margin-bottom:1rem;font-size:1.3rem">Siap Memulai?</h2>
            <p style="color:#555;line-height:1.6;margin-bottom:1.5rem">
                Anda siap untuk memulai persiapan SKD Anda! Klik tombol di bawah untuk mulai, atau kapan saja Anda ingin melihat tour ini lagi, buka menu Help.
            </p>
            <div style="background:#d4edda;padding:1rem;border-radius:8px;margin-bottom:1.5rem">
                <p style="color:#155724;font-size:0.9rem;margin:0">
                    <strong>Semangat!</strong> Konsisten berlatih adalah kunci kesuksesan.
                </p>
            </div>
        </div>
        
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem">
            <button id="onboarding-prev" onclick="prevOnboardingStep()" style="background:#ddd;color:#555;border:none;padding:0.6rem 1.2rem;border-radius:6px;cursor:pointer;font-size:0.9rem;display:none">Kembali</button>
            <div id="onboarding-dots" style="display:flex;gap:0.5rem">
                <span class="onboarding-dot active" style="width:8px;height:8px;border-radius:50%;background:#2980b9"></span>
                <span class="onboarding-dot" style="width:8px;height:8px;border-radius:50%;background:#ddd"></span>
                <span class="onboarding-dot" style="width:8px;height:8px;border-radius:50%;background:#ddd"></span>
            </div>
            <button id="onboarding-next" onclick="nextOnboardingStep()" style="background:#2980b9;color:#fff;border:none;padding:0.6rem 1.2rem;border-radius:6px;cursor:pointer;font-size:0.9rem">Lanjut</button>
        </div>
        
        <button onclick="closeOnboarding()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.5rem;color:#999;cursor:pointer;padding:0.5rem;line-height:1">×</button>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.onboarding-dot.active {
    background: #2980b9 !important;
}
</style>

<script>
let currentOnboardingStep = 1;
const totalOnboardingSteps = 3;

function nextOnboardingStep() {
    if (currentOnboardingStep < totalOnboardingSteps) {
        document.getElementById('onboarding-step-' + currentOnboardingStep).style.display = 'none';
        currentOnboardingStep++;
        document.getElementById('onboarding-step-' + currentOnboardingStep).style.display = 'block';
        
        // Update dots
        const dots = document.querySelectorAll('.onboarding-dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentOnboardingStep - 1);
            dot.style.background = index === currentOnboardingStep - 1 ? '#2980b9' : '#ddd';
        });
        
        // Update buttons
        document.getElementById('onboarding-prev').style.display = currentOnboardingStep > 1 ? 'block' : 'none';
        document.getElementById('onboarding-next').textContent = currentOnboardingStep === totalOnboardingSteps ? 'Mulai' : 'Lanjut';
    } else {
        closeOnboarding();
    }
}

function prevOnboardingStep() {
    if (currentOnboardingStep > 1) {
        document.getElementById('onboarding-step-' + currentOnboardingStep).style.display = 'none';
        currentOnboardingStep--;
        document.getElementById('onboarding-step-' + currentOnboardingStep).style.display = 'block';
        
        // Update dots
        const dots = document.querySelectorAll('.onboarding-dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentOnboardingStep - 1);
            dot.style.background = index === currentOnboardingStep - 1 ? '#2980b9' : '#ddd';
        });
        
        // Update buttons
        document.getElementById('onboarding-prev').style.display = currentOnboardingStep > 1 ? 'block' : 'none';
        document.getElementById('onboarding-next').textContent = 'Lanjut';
    }
}

function closeOnboarding() {
    document.getElementById('onboarding-overlay').style.display = 'none';
    // Mark as seen
    fetch('/permen/api/mark_onboarding_seen.php', { method: 'POST' });
}
</script>
<?php endif; ?>

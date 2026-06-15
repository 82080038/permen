<?php
require '../config.php';
require '../helpers.php';

$pageTitle = 'Help Center — SKD CAT-BKN';
$activePage = 'help';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#1a5276">
    <base href="/">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/form.css">
</head>
<body>
    <a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php require '../includes/navigation.php'; ?>
    
    <div class="container">
        <div class="card" style="max-width:700px;margin:2rem auto">
            <h1 style="text-align:center;color:#1a5276;margin-bottom:1.5rem">Help Center</h1>
            
            <div id="main-content">
                <!-- Quick Links -->
                <div style="margin-bottom:2rem">
                    <h2 style="color:#1a5276;font-size:1.2rem;margin-bottom:1rem">Dokumentasi</h2>
                    <div style="display:grid;gap:1rem">
                        <a href="docs/USER_MANUAL.md" target="_blank" class="btn" style="display:block;text-align:center;text-decoration:none">
                            📖 User Manual
                        </a>
                        <a href="docs/ADMIN_MANUAL.md" target="_blank" class="btn" style="display:block;text-align:center;text-decoration:none">
                            👨‍💼 Admin Manual
                        </a>
                        <a href="docs/API_DOCUMENTATION.md" target="_blank" class="btn" style="display:block;text-align:center;text-decoration:none">
                            🔌 API Documentation
                        </a>
                    </div>
                </div>
                
                <!-- FAQ Section -->
                <div style="margin-bottom:2rem">
                    <h2 style="color:#1a5276;font-size:1.2rem;margin-bottom:1rem">FAQ (Pertanyaan Umum)</h2>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Bagaimana cara mendaftar?</summary>
                            <p style="margin-top:.5rem;color:#555">Kunjungi halaman pendaftaran, masukkan nama lengkap, nomor HP, dan password. Klik "Daftar" untuk menyelesaikan pendaftaran.</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Bagaimana cara mengambil tryout?</summary>
                            <p style="margin-top:.5rem;color:#555">Login ke dashboard, navigasi ke menu "Tryout", pilih event tryout yang tersedia, dan klik "Mulai Tryout". Ikuti instruksi dan selesaikan dalam waktu yang ditentukan.</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Bagaimana cara melihat hasil tryout?</summary>
                            <p style="margin-top:.5rem;color:#555">Setelah menyelesaikan tryout, hasil akan ditampilkan secara otomatis. Anda juga dapat melihat riwayat tryout di menu "Riwayat".</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Apa itu Daily Quiz?</summary>
                            <p style="margin-top:.5rem;color:#555">Daily Quiz adalah kuis harian yang tersedia setiap hari. Anda dapat mengambil satu kuis per hari untuk berlatih dan mempertahankan streak.</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Bagaimana cara mengubah password?</summary>
                            <p style="margin-top:.5rem;color:#555">Buka menu "Settings" di dashboard, masukkan password lama dan password baru, lalu klik "Simpan Pengaturan".</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Lupa password?</summary>
                            <p style="margin-top:.5rem;color:#555">Klik "Lupa Password" di halaman login, masukkan nomor HP Anda, dan ikuti instruksi untuk mereset password.</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Bagaimana cara mengaktifkan notifikasi?</summary>
                            <p style="margin-top:.5rem;color:#555">Buka menu "Settings", pilih preferensi notifikasi (Browser, Push, atau Keduanya), dan aktifkan kategori notifikasi yang diinginkan.</p>
                        </details>
                    </div>
                    
                    <div class="faq-item" style="margin-bottom:1rem">
                        <details style="background:#f8f9fa;padding:1rem;border-radius:6px;border:1px solid #ddd">
                            <summary style="font-weight:bold;cursor:pointer;color:#1a5276">Apakah platform ini gratis?</summary>
                            <p style="margin-top:.5rem;color:#555">Ya, platform ini gratis untuk digunakan. Anda dapat mengakses semua materi, tryout, dan fitur tanpa biaya.</p>
                        </details>
                    </div>
                </div>
                
                <!-- Contact Support -->
                <div style="margin-bottom:2rem">
                    <h2 style="color:#1a5276;font-size:1.2rem;margin-bottom:1rem">Hubungi Support</h2>
                    <p style="color:#555;margin-bottom:1rem">Jika Anda tidak menemukan jawaban yang Anda cari, silakan hubungi kami:</p>
                    <div style="display:flex;flex-direction:column;gap:.5rem">
                        <a href="feedback.php" class="btn" style="text-align:center;text-decoration:none">Kirim Feedback</a>
                        <a href="mailto:support@example.com" class="btn" style="text-align:center;text-decoration:none;background:#3498db">Email Support</a>
                    </div>
                </div>
                
                <!-- Video Tutorials -->
                <div style="margin-bottom:2rem">
                    <h2 style="color:#1a5276;font-size:1.2rem;margin-bottom:1rem">Video Tutorial</h2>
                    <p style="color:#555;margin-bottom:1rem">Video tutorial akan segera tersedia. Silakan cek kembali nanti.</p>
                </div>
                
                <!-- Back Link -->
                <div style="text-align:center;margin-top:2rem">
                    <a href="index.php" class="link">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer" style="text-align:center;padding:1.5rem;color:#777;font-size:.85rem">SKD CAT-BKN Try Out &amp; Bimbel</div>
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/app.js"></script>
</body>
</html>

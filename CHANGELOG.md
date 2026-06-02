# Changelog SKD CAT-BKN Try Out & Bimbel

Format ini mengikuti [Keep a Changelog](https://keepachangelog.com/) dan [Semantic Versioning](https://semver.org/).

---

## [1.0.0] — 2026-06-02

### Added (Baru)
- Landing page dengan informasi 3 subtes SKD.
- Halaman materi pembelajaran per subtes (TWK, TIU, TKP) dengan accordion.
- Materi lengkap berdasarkan Permen PANRB No. 20/2021 & KepmenPANRB No. 208/2025:
  - TWK: nasionalisme, integritas, bela negara, pilar negara, bahasa Indonesia.
  - TIU: analogi, silogisme, analitis, berhitung, deret angka, perbandingan, soal cerita, figural.
  - TKP: pelayanan publik, jejaring kerja, sosial budaya, TIK, profesionalisme + skoring 1–5.
- Bank soal 60+ soal realistis (TKP 16, TIU 29, TWK 26).
- Tryout CAT simulation dengan timer sinkron database (80 menit).
- Navigasi soal dengan grid nomor di sidebar.
- Submit jawaban real-time ke server via Fetch API.
- Skoring otomatis: TKP (bobot 1–5), TIU & TWK (benar=5, salah=0).
- Halaman hasil dengan scorecard TKP/TIU/TWK dan status lolos/tidak lolos ambang batas.
- Pembahasan soal ditampilkan langsung saat mengerjakan.
- API REST JSON: materi, get soal, submit jawaban, finish tryout.
- Database MySQL dengan skema: users, questions, tryout_sessions, answers, materi, tips.
- Dokumentasi: README, ARCHITECTURE, API, ROADMAP, CHANGELOG.

### Technical
- Stack: PHP 7.4+ vanilla, MySQL, HTML5, CSS3, Vanilla JS.
- PDO prepared statements untuk keamanan SQL injection.
- Responsive design (mobile-friendly).

---

## [Unreleased] — Yang Akan Datang

### Changed
- Reorganisasi struktur folder:
  - `db.sql` dan `seed.sql` dipindah ke folder `sql/`.
  - `data/materi_*.php` dipindah ke folder `content/` dan folder `data/` dihapus.
  - Semua path internal (API, pages, README, docs) diperbarui sesuai struktur baru.
- Update parameter tryout default ke 110 soal / 110 menit (TKP 45, TIU 35, TWK 30) sesuai KepmenPANRB No. 208/2025.

### Added
- Tabel `master_materi` — kisi-kisi per subtes untuk acuan AI generate soal.
- Tabel `tips_tricks` — 18 tips reusable dengan contoh soal + penerapan (format: TRIK, akronim, langkah-langkah).
- Tabel `soal_ai_cache` — cache soal yang dihasilkan AI agar tidak duplicate.
- Batch soal manual Batch 1 (90 soal baru: 30 TKP, 30 TIU, 30 TWK) dengan tingkat kesulitan (mudah/sedang/sulit).
- `api/generate_soal_ai.php` — integrasi Gemini 2.0 Flash API untuk generate soal otomatis per topik (opsional, memerlukan API key).
- `api/generate_soal_smart.php` — **Smart Generator Internal** yang menghasilkan soal via algoritma PHP + template tanpa API eksternal. Dukungan: TIU numerik (deret, berhitung, perbandingan, cerita), TWK, TKP.
- `pages/latihan.php` — Mode latihan per subtes terpisah: TWK (30 soal/30 menit), TIU (35 soal/35 menit), TKP (45 soal/45 menit). Setiap user bisa latihan fokus pada satu subtes.
- `pages/hasil.php` — Mendeteksi mode latihan dan menampilkan hasil yang disederhanakan (hanya subtes yang dilatih).
- `sql/IMPORT_ALL.sql` — file master untuk import semua SQL sekaligus.
- Dokumentasi analisis risalah DeepSeek (`docs/ANALISIS_RISALAH_DEEPSEEK.md`) — diperbarui dengan status selesai dan klarifikasi PermenPANRB No. 20/2021 vs KepmenPANRB No. 208/2025.

### Fixed (Audit Keamanan & Kualitas)
- **XSS**: Semua output database di `pages/hasil.php` kini menggunakan `htmlspecialchars()`.
- **API Ownership**: `get_soal.php`, `submit_jawaban.php`, `finish_tryout.php` memvalidasi bahwa data milik user yang sedang login (via `user_id` session).
- **HTTP Status Codes**: API kini mengembalikan kode HTTP yang tepat: 400 (bad request), 401 (unauthorized), 403 (forbidden).
- **Session Security**: `session_regenerate_id(true)` setelah pembuatan user di `tryout.php`.
- **Timer Display**: Timer di `tryout.php` tidak lagi hardcoded "80:00", melainkan dihitung dari sisa waktu database.
- **Database Integrity**: `UNIQUE` constraint `unique_session_question` ditambahkan di tabel `answers` untuk mencegah duplikat soal per session.
- **Input Validation**: `submit_jawaban.php` memvalidasi bahwa jawaban hanya boleh A-E.
- **Navigasi Konsisten**: Semua halaman (`materi.php`, `latihan.php`, `tryout.php`, `hasil.php`) memiliki navigasi seragam.
- **Dokumentasi API**: `docs/API.md` diperbarui dengan endpoint Smart Generator, AI Generator, dan catatan keamanan.
- **Playwright E2E Testing**: Framework testing terinstall dengan konfigurasi headed. Suite test mencakup: halaman utama, materi, latihan per subtes, quick login admin/user, logout, Smart Generator API, dan keamanan API (401). Semua 8 test passed.

### Added (Sistem Autentikasi & Dashboard)
- **Tabel users diperluas**: Kolom `role` (admin/user), `password_hash` (bcrypt), `instansi`.
- **1 Admin + 5 User Demo**: Admin BKN + 5 peserta (Budi, Dewi, Andi, Rina, Eko) dengan password `password`.
- **`pages/login.php`**: Halaman login dengan validasi password bcrypt. Quick login buttons untuk admin dan 5 user demo.
- **`pages/admin_dashboard.php`**: Dashboard admin dengan statistik soal, peserta, tryout; daftar peserta; riwayat tryout; tab navigasi.
- **`pages/user_dashboard.php`**: Dashboard peserta dengan riwayat tryout, rata-rata nilai, nilai tertinggi, subtes terlemah, dan rekomendasi belajar.
- **`api/logout.php`**: Logout dengan penghapusan session dan cookie.
- **RBAC (Role-Based Access Control)**: Guard di setiap dashboard — admin tidak bisa masuk user dashboard dan sebaliknya.
- **Navigasi login/logout**: Semua halaman (index, materi, latihan, tryout, hasil) menampilkan link Login/Dashboard/Logout sesuai status session.

### Database Normalisasi
- **`subtes_config`**: Tabel baru untuk konfigurasi global per subtes — durasi, jumlah soal, passing grade, urutan. Menghilangkan hardcoded values dan redundansi.
- **`session_subtes`**: Tabel normalisasi 1-N dari `tryout_sessions`. Menggantikan 12+ kolom berulang (`durasi_tkp/tiu/twk`, `jumlah_tkp/tiu/twk`, `passing_tkp/tiu/twk`, `nilai_tkp/tiu/twk`) dengan struktur relasional yang proper.
- **`question_options`**: Tabel normalisasi 1-N dari `questions`. Menggantikan 5 kolom berulang (`pilihan_a`..`pilihan_e`) dengan baris relasional per opsi.
- **Backward Compatibility**: Kolom flat di `tryout_sessions` tetap dipertahankan sementara. Semua kode baru membaca dari tabel normalisasi dengan fallback ke kolom flat.
- **View `v_tryout_sessions_flat`**: View untuk memudahkan query legacy yang membutuhkan struktur flat.

### M2 — Register User & Profil
- **`pages/register.php`**: Halaman pendaftaran user baru dengan validasi email, password bcrypt (min 6 karakter), dan instansi pilihan.
- **Link navigasi**: Header index.php dan login.php menampilkan link "Daftar" untuk user yang belum login.

### M3 — Admin Panel & Export
- **Konfigurasi Subtes CRUD**: Tab "Konfigurasi" di `admin_dashboard.php` untuk edit durasi, jumlah soal, passing grade, dan urutan TWK/TIU/TKP secara real-time.
- **Export CSV**: `api/export_csv.php` mendukung export riwayat tryout (`type=tryouts`) dan daftar peserta (`type=users`) dengan BOM untuk Excel.

### M4 — Anti-Cheat & PWA
- **Timer server-side validation**: `api/finish_tryout.php` memvalidasi minimum waktu pengerjaan (60 detik) menggunakan `UNIX_TIMESTAMP(waktu_mulai)` dari database untuk konsistensi timezone.
- **Service Worker PWA**: `sw.js` caching static assets dan halaman utama. Register di `index.php` dengan fallback offline ke homepage.

### Bug Fixes
- **`api/get_soal.php`**: Memperbaiki `PDO::FETCH_KEY_PAIR` yang hanya bekerja untuk query 2 kolom. Query `session_subtes` punya 5 kolom sehingga menyebabkan error 500 saat tryout mengambil soal. Diubah ke `FETCH_ASSOC` + manual array mapping.
- **`api/finish_tryout.php`**: Memperbaiki perbedaan timezone antara PHP `strtotime()` dan MySQL `NOW()` dengan menggunakan `UNIX_TIMESTAMP()` langsung dari database.

### Testing Komprehensif
- **Batch Test Script** (`batch_test.php`): 34 test — termasuk 5 test normalisasi DB baru (subtes_config, session_subtes, question_options, tryout creates 3 rows, latihan creates 1 row). Semua passed.
- **Playwright E2E**: 19 test (8 skd.spec.js + 11 exploratory.spec.js) — semua passed. Console & network error monitoring aktif.

### Responsive & Mobile-First
- **Semua halaman mobile-friendly**: Header navigasi flex-wrap, font size menyesuaikan layar, padding disesuaikan untuk mobile.
- **Touch targets minimum 44px**: Semua tombol, link, dan opsi soal memenuhi standar aksesibilitas touch target (44x44px minimum).
- **Tabel scrollable**: Semua tabel di admin_dashboard dan user_dashboard dibungkus `.table-wrap` dengan `overflow-x: auto` agar bisa di-scroll horizontal di mobile.
- **Meta tags PWA-ready**: `theme-color`, `maximum-scale=5`, `manifest.json` ditambahkan untuk dukungan Progressive Web App di masa depan.
- **Media queries**: Breakpoint 480px dan 600px untuk layout stacked pada layar kecil (cards 1 kolom, sidebar full-width, button group vertikal).
- **Tryout mobile optimized**: Sidebar pindah ke atas konten di mobile, option labels lebih besar untuk touch, tombol navigation vertikal di mobile.

### Infrastructure & Developer Experience
- **`.env` Support**: Konfigurasi database dan API key dipisahkan dari kode via file `.env`. Parser sederhana di `env_loader.php` tanpa Composer.
- **`helpers.php`**: Fungsi reusable — `e()` (XSS escape), `formatRupiah()`, `formatDuration()`, `appLog()`, `baseUrl()`, `isDev()`.
- **`.htaccess` Proteksi**: File `.env`, `config.php`, folder `sql/`, dan `tests/` dilindungi dari akses web. Gzip compression dan browser caching diaktifkan untuk asset statis.
- **`.gitignore`**: Menghindari commit file `.env`, `node_modules/`, `test-results/`, log, dan file IDE.
- **`package.json` Scripts**: `npm test`, `npm run test:headed`, `npm run test:ui`, `npm run test:debug`.
- **Error Logging**: `logs/` folder untuk logging aplikasi dengan rotasi harian.

### Planned
- Sistem login & register user.
- Admin panel CRUD soal dan materi.
- Leaderboard & ranking antarpeserta.
- Daily quiz / drilling soal.
- PWA (Progressive Web App) support.

---

## Cara Menulis Changelog

Setiap kali ada update:

1. **Tambah baris baru** di bagian `[Unreleased]` atau buat versi baru.
2. **Gunakan kategori**: `Added`, `Changed`, `Fixed`, `Removed`, `Deprecated`.
3. **Deskripsikan perubahan** secara spesifik.
4. **Jangan hapus** riwayat versi lama — itu dokumentasi penting.

### Contoh format perubahan:
```
## [1.1.0] — 2026-06-15
### Added
- Fitur login dengan bcrypt password hashing.
- Halaman riwayat tryout per user.

### Fixed
- Bug timer yang reset saat refresh halaman.
```

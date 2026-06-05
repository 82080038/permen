# Aplikasi Try Out & Bimbel SKD CAT-BKN

Aplikasi latihan Seleksi Kompetensi Dasar (SKD) dengan format Computer Assisted Test (CAT) untuk persiapan masuk Sekolah Kedinasan.

## Dasar Hukum
- **Permen PANRB No. 20 Tahun 2021** tentang Seleksi Penerimaan Mahasiswa/Praja/Taruna Sekolah Kedinasan
- **KepmenPANRB No. 208/2025** tentang Pedoman Seleksi Kompetensi Dasar

## Fitur

### Core Tryout
1. **Try Out CAT** — Timer server-side per subtes (110 soal / 110 menit, KepmenPANRB No. 208/2025)
2. **Auto-Advance** — Pilih jawaban → langsung soal berikutnya (tanpa klik "Berikutnya")
3. **Keyboard Shortcuts** — A-E pilih jawaban, Arrow keys navigasi, M tandai ragu-ragu
4. **Swipe Navigation** — Geser kiri/kanan di mobile untuk navigasi soal
5. **Auto-Save** — Jawaban disimpan ke localStorage + server (survive refresh/crash)
6. **Anti-Cheating** — Disable right-click, copy, devtools, tab-switch detection, back button block

### Learning & Review
7. **Materi Pembelajaran** per subtes (TWK, TIU, TKP) — sesuai PermenPANRB No. 20/2021
8. **Uji Pemahaman** — Peserta generate soal latihan pribadi dari materi yang dipelajari
9. **Pembahasan Lengkap** — Setiap soal: pembahasan + tips & trick + link belajar terkait
10. **Riwayat Soal** — Histori jawaban user dengan filter subtes/topik/status (benar/salah/kosong)
11. **Analisis Akurasi per Topik** — Progress bar akurasi per topik di dashboard user

### Generator Soal
12. **Smart Generator** — Generate soal otomatis tanpa API eksternal (PHP + template)
13. **User Generator** — Peserta generate soal latihan sendiri (max 20 soal, no DB storage)
14. **Generator Massal Admin** — Generate puluhan soal sekaligus dengan parameter subtes/topik/jumlah
15. **Daily Quiz** — 10 soal harian campuran (4 TWK, 3 TIU, 3 TKP) dengan tracking progress

### Admin & Quality Control
16. **Soal Revision Workflow** — Peserta tandai "M" (ragu-ragu) → admin review → tandai "Sudah Direvisi"
17. **Toggle Soal Visibility** — Admin bisa sembunyikan/tampilkan soal per item
18. **Upload Gambar Soal** — Drag & drop upload ke CDN lokal
19. **Edit Soal Inline** — Modal edit pertanyaan, pilihan, jawaban, pembahasan, gambar

### UX & Accessibility
20. **Dark Mode** — Toggle 🌙/☀️ dengan CSS variables, persist localStorage
21. **Font Size Adjustment** — Ukuran font S/M/L, persist localStorage
22. **Tap-to-Zoom Gambar** — Ketuk gambar soal untuk zoom fullscreen (mobile-friendly)
23. **Leaderboard** — Top 20 nilai total + Top 10 per subtes (TWK/TIU/TKP), filter waktu
24. **Predicted Passing Grade** — Cek kemungkinan lolos per instansi pilihan
25. **Grafik Progress** — Trend nilai tryout over time di dashboard

## Persyaratan
- XAMPP / LAMP dengan PHP >= 7.4
- MySQL / MariaDB
- Browser modern (Chrome, Firefox, Edge)

## Instalasi

### 1. Konfigurasi Environment
Duplikat file `.env.example` menjadi `.env` dan sesuaikan:
```bash
cp .env.example .env
# Edit .env — isi password database
```

### 2. Jalankan XAMPP
```bash
sudo /opt/lampp/lampp startmysql
sudo /opt/lampp/lampp startapache
```

### 3. Import Database
Buka terminal dan jalankan:
```bash
cd /opt/lampp/htdocs/permen/sql
/opt/lampp/bin/mysql -u root -proot < IMPORT_ALL.sql
```

Atau buka phpMyAdmin (`http://localhost/phpmyadmin`), buat database `skd_cat_bkn`, lalu impor file `db.sql` dan `seed.sql` secara manual.

### 4. Akses Aplikasi
Buka browser: `http://localhost/permen/`

## Struktur Folder
```

  index.php              # Halaman utama
  config.php             # Koneksi database (baca dari .env)
  env_loader.php         # Parser file .env sederhana
  helpers.php            # Fungsi reusable (format, escape, log, CSRF, rate limit)
  sw.js                  # Service Worker (PWA support)
  manifest.json          # PWA manifest
  .env.example           # Template konfigurasi environment
  .htaccess              # Proteksi file & direktori + security headers
  .gitignore             # File yang dikecualikan dari Git

  assets/
    style.css            # Shared styles
    app.js               # Shared vanilla JS
    soal/                # Gambar soal figural (SVG)

  sql/
    db.sql               # Skema database (DDL)
    seed.sql             # Data awal (master data, tips, materi)
    IMPORT_ALL.sql       # Import semua SQL sekaligus
    batch_*.sql          # Batch insert soal & materi

  content/
    materi_twk.php       # Materi TWK lengkap
    materi_tiu.php       # Materi TIU lengkap
    materi_tkp.php       # Materi TKP lengkap

  api/
    get_soal.php         # Ambil soal tryout (auth + ownership)
    submit_jawaban.php   # Kirim jawaban (auth + timer check)
    finish_tryout.php    # Hitung & simpan nilai (auth)
    get_review.php       # Review hasil tryout (auth)
    next_subtes.php      # Transisi subtes (auth)
    generate_soal_smart.php   # Smart Generator (admin-only)
    generate_soal_ai.php      # AI Generator (admin-only)
    generate_user_soal.php    # User Generator (auth, no DB storage)
    mark_revision.php         # Flag soal perlu revisi (auth)
    update_revision.php     # Admin update revision status (admin-only)
    list_soal.php           # List soal admin (admin-only)
    update_soal.php         # Edit soal (admin-only)
    get_soal_detail.php     # Detail soal (admin-only)
    upload_image.php        # Upload gambar (admin-only)
    materi.php              # API materi

  pages/
    materi.php           # Halaman materi + Uji Pemahaman
    tryout.php           # Halaman try out CAT (dark mode, font size, swipe)
    hasil.php            # Halaman hasil + review + rekomendasi
    latihan.php          # Latihan per subtes + Latihan Personal
    user_dashboard.php   # Dashboard peserta (stats, grafik, analisis topik)
    admin_dashboard.php  # Dashboard admin (generator, soal, revision)
    riwayat_soal.php     # Histori jawaban user
    leaderboard.php      # Peringkat nasional
    login.php            # Login
    register.php         # Registrasi

  tests/
    skd.spec.js          # Test E2E Playwright (dasar)
    comprehensive.spec.js # Test E2E komprehensif
    README.md            # Dokumentasi testing

  docs/
    ARCHITECTURE.md      # Arsitektur & skema DB
    API.md               # Dokumentasi REST API
    ROADMAP.md           # Milestone pengembangan
    CHANGELOG.md         # Riwayat perubahan
    ANALISIS_PRODUCTION.md   # Analisis production & quality report
    stack-reference/     # Referensi teknologi
```

## Testing (Playwright)

### Install dependencies
```bash
npm install
npx playwright install
npx playwright install-deps
```

### Jalankan test
```bash
# Headless (default)
npm test

# Headed (browser terlihat)
npm run test:headed

# Debug mode
npx playwright test --debug

# UI mode
npm run test:ui
```

Lihat `tests/README.md` untuk detail lebih lanjut.

## Catatan
- Jumlah soal default tryout penuh: TKP=45, TIU=35, TWK=30 (total **110 soal / 110 menit**) sesuai KepmenPANRB No. 208/2025.
- Latihan per subtes: TWK 30 soal/30 menit, TIU 35 soal/35 menit, TKP 45 soal/45 menit.
- Passing grade default: TKP=126, TIU=80, TWK=65, Total=271.
- Timer tryout sinkron dengan waktu mulai di database (refresh tidak mereset timer).
- Pembahasan soal ditampilkan langsung saat mengerjakan untuk mode belajar.
- **Smart Generator** — Aplikasi bisa generate soal baru tanpa API eksternal.
- **AI Generator** (opsional) — Integrasi Gemini 2.0 Flash, isi `GEMINI_API_KEY` di `.env`.

## Dokumentasi Teknis

| Dokumen | Deskripsi |
|---------|-----------|
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Arsitektur sistem, diagram alur data, skema DB, skoring |
| [API.md](docs/API.md) | Dokumentasi lengkap endpoint REST API |
| [ROADMAP.md](docs/ROADMAP.md) | Rencana pengembangan per milestone (update berkala) |
| [CHANGELOG.md](CHANGELOG.md) | Riwayat perubahan per versi (update berkala) |

## Stack Reference (Acuan Pengembangan)

Folder `docs/stack-reference/` berisi referensi untuk teknologi dalam ekosistem aplikasi:

| Dokumen | Teknologi | Isi |
|---------|-----------|-----|
| [PHP_NATIVE.md](docs/stack-reference/PHP_NATIVE.md) | PHP 7.4+ | PDO, prepared statements, keamanan (XSS, CSRF, SQLi), session, JSON API |
| [MYSQL.md](docs/stack-reference/MYSQL.md) | MySQL 5.7+ | DDL, DML, indexing, transactions, functions, optimization |
| [JQUERY.md](docs/stack-reference/JQUERY.md) | jQuery 3.7+ | *(Referensi opsional)* — aplikasi aktual menggunakan vanilla JS |
| [BOOTSTRAP.md](docs/stack-reference/BOOTSTRAP.md) | Bootstrap 5.3+ | *(Referensi opsional)* — aplikasi aktual menggunakan CSS vanilla |

> Aplikasi ini menggunakan **vanilla PHP, CSS, dan JavaScript** tanpa framework frontend/library eksternal agar ringan dan mudah di-deploy di shared hosting.

**Gunakan referensi ini sebelum menulis kode baru.**

## Update Berkala

Dokumen berikut wajib diupdate setiap ada perubahan:

1. **CHANGELOG.md** — Setiap commit/merge, tulis perubahan di bagian `[Unreleased]`.
2. **ROADMAP.md** — Update status milestone dan fitur yang sedang dikerjakan.
3. **ARCHITECTURE.md** — Update jika ada perubahan struktur database, API baru, atau stack teknologi.

## Keamanan
- PDO prepared statements untuk semua query (anti SQL injection).
- `htmlspecialchars()` di semua output PHP (anti XSS).
- **CSRF token** di semua form POST (login, register, admin config).
- **Rate limiting login** — max 5 percobaan per IP per 15 menit.
- **Timer server-side enforcement** — `submit_jawaban.php` dan `get_soal.php` memeriksa status session & waktu habis.
- Validasi kepemilikan data di setiap API endpoint (anti unauthorized access).
- Generator soal (smart & AI) dilindungi **admin-only**.
- `session_regenerate_id(true)` setelah pembuatan user (anti session fixation).
- Security headers HTTP via `.htaccess`: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, CSP.
- File `.env`, `config.php`, dan folder `sql/` dilindungi oleh `.htaccess`.
- `UNIQUE` constraint di tabel `answers` untuk mencegah duplikat soal per session.

## Kontribusi & Pengembangan

1. Fork / clone repository.
2. Buat branch fitur: `git checkout -b feature/nama-fitur`.
3. Update CHANGELOG.md sebelum commit.
4. Jalankan `npm test` untuk memastikan tidak ada regresi.
5. Merge ke branch utama setelah review.

## Disclaimer
Aplikasi ini merupakan sarana latihan dan bimbel mandiri. Kelulusan seleksi sepenuhnya ditentukan oleh hasil ujian resmi yang diselenggarakan oleh BKN dan Kementerian/Lembaga terkait sesuai ketentuan yang berlaku.

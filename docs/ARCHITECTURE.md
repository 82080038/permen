# Arsitektur Aplikasi SKD CAT-BKN Try Out & Bimbel

## Ringkasan
Aplikasi web berbasis PHP + MySQL untuk simulasi Seleksi Kompetensi Dasar (SKD) Sekolah Kedinasan, sesuai Permen PANRB No. 20/2021 dan KepmenPANRB No. 208/2025.

Versi aktif dengan fitur lengkap: generator soal, user practice engine, admin revision workflow, dark mode, leaderboard, dan analitik per topik.

---

## Stack Teknologi

| Layer | Teknologi |
|-------|-----------|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (ES6+), CSS Variables (dark mode) |
| **Backend** | PHP 7.4+ (vanilla, tanpa framework), PDO prepared statements |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ |
| **Web Server** | Apache (XAMPP/LAMP) |
| **Session** | PHP Native Session |
| **API** | REST-like JSON over HTTP |
| **Testing** | Playwright E2E |
| **AI** | Gemini 2.0 Flash (opsional, untuk generator soal) |

---

## Struktur Folder

```

├── index.php                  # Landing page
├── config.php               # Koneksi PDO + session start
│
├── sql/                     # File SQL database
│   ├── db.sql               # Skema database (DDL)
│   └── seed.sql             # Data awal soal (DML)
│
├── content/                 # Materi pembelajaran (PHP arrays)
│   ├── materi_twk.php       # TWK: nasionalisme, integritas, dll
│   ├── materi_tiu.php       # TIU: verbal, numerik, figural
│   └── materi_tkp.php       # TKP: pelayanan publik, jejaring kerja, dll
│
├── api/                     # REST API endpoint
│   ├── materi.php           # GET materi by subtes/id
│   ├── get_soal.php         # GET soal by session_id
│   ├── submit_jawaban.php   # POST jawaban user
│   └── finish_tryout.php    # POST finalisasi tryout
│
├── pages/                   # Halaman frontend
│   ├── materi.php           # Halaman materi pembelajaran
│   ├── tryout.php           # Halaman simulasi CAT
│   └── hasil.php            # Halaman hasil & scorecard
│
└── docs/                    # Dokumentasi proyek
    ├── ARCHITECTURE.md      # Dokumen ini
    ├── API.md               # Dokumentasi API lengkap
    ├── ROADMAP.md           # Rencana pengembangan
    └── CHANGELOG.md         # Riwayat perubahan
```

---

## Diagram Alur Data

### 1. Try Out CAT

```
[Peserta]
   |
   v
[index.php] --klik "Mulai Try Out"--> [pages/tryout.php]
                                          |
                                          v
                              [Buat user dummy di tabel users]
                                          |
                                          v
                              [Buat tryout_sessions baru]
                                          |
                                          v
                              [GET ../api/get_soal.php?session_id=X]
                                          |
                                          v
                              [Generate soal acak ke tabel answers]
                                          |
                                          v
                              [Tampilkan soal #1 dengan timer]
                                          |
                         +----------------+----------------+
                         |                                 |
                    [Pilih jawaban]                 [Navigasi soal]
                         |                                 |
                         v                                 v
              [POST ../api/submit_jawaban.php]    [Render soal lain]
                         |                                 |
                         v                                 |
              [Update answers.jawaban_user & skor]        |
                         |                                 |
                         +----------------+----------------+
                                          |
                                          v
                              [Klik "Selesai"]
                                          |
                                          v
                              [POST ../api/finish_tryout.php]
                                          |
                                          v
                              [Hitung nilai per subtes]
                                          |
                                          v
                              [Update tryout_sessions status=selesai]
                                          |
                                          v
                              [Redirect pages/hasil.php?session_id=X]
```

### 2. Materi Pembelajaran

```
[Peserta]
   |
   v
[pages/materi.php?subtes=TWK]
   |
   v
[Load ../content/materi_twk.php]
   |
   v
[Tampilkan accordion per topik]
   |
   v
[Expand/collapse via JavaScript]
```

---

## Skema Database

### Relasi Antar Tabel

```
users (1)
  |
  |--< tryout_sessions (N)
  |      |
  |      |--< answers (N)
  |             |
  |             >-- questions (N)
  |
questions (bank soal, independen)

materi (independen, tidak punya relasi)
tips (independen, tidak punya relasi)
```

### Penjelasan Tabel

| Tabel | Fungsi |
|-------|--------|
| `users` | Peserta dan admin. Kolom `role` (admin/user), `password_hash` (bcrypt), `instansi`. |
| `questions` | Bank soal (2.771+ soal). Kolom: `subtes`, `tipe`, `topik`, `pertanyaan`, `pilihan_a-e`, `jawaban_benar`, `pembahasan`, `tips_trick`, `related_links`, `materi_id`, `image_url`, `passage_id`, `passage_order`, `bobot_tkp`, `needs_revision`, `revision_status`, `is_active`. |
| `tryout_sessions` | Sesi tryout per user. `status` (berjalan/selesai), `total_nilai`, `nilai_twk/tiu/tkp`, `waktu_mulai/selesai`. |
| `answers` | Jawaban per soal per session. `jawaban_user`, `skor`, FK ke `tryout_sessions` & `questions`. |
| `session_subtes` | Normalisasi subtes per session: `subtes`, `durasi_menit`, `jumlah_soal`, `passing_grade`, `waktu_mulai_subtes`. |
| `materi` | Materi pembelajaran terstruktur: `subtes`, `topik`, `judul`, `konten`, `urutan`, `url`. |
| `instansi` | Daftar instansi + passing grade: `nama`, `tkp_passing`, `tiu_passing`, `twk_passing`, `aktif`. |
| `rekomendasi_materi` | Rekomendasi belajar berdasarkan skor lemah. |
| `master_materi` | Kisi-kisi per subtes/tipe/topik untuk acuan generator soal. |
| `tips_tricks` | Tips reusable dengan contoh soal + penerapan per subtes. |
| `soal_ai_cache` | Cache soal yang dihasilkan generator untuk mencegah duplikat. |

---

## Mekanisme Skoring

### TIU & TWK (Benar/Salah)
```
if jawaban_user == jawaban_benar:
    skor = 5
else:
    skor = 0
```

### TKP (Bobot 1–5 per Opsi)
```
Mapping default: A=1, B=2, C=3, D=4, E=5

Selisih = |bobot_jawaban_user - bobot_jawaban_benar|

if selisih == 0: skor = 5
if selisih == 1: skor = 4
if selisih == 2: skor = 3
if selisih == 3: skor = 2
else: skor = 1
```

### Passing Grade & Status Lulus
```
Total = nilai_twk + nilai_tiu + nilai_tkp
Lulus = (nilai_twk >= passing_twk) AND (nilai_tiu >= passing_tiu) AND (nilai_tkp >= passing_tkp) AND (total >= passing_total)
```

**Catatan**: `bobot_tkp` di tabel = bobot dari jawaban_benar. Jawaban B biasanya = 5 (tertinggi) untuk TKP. |

---

## Keamanan

| Aspek | Implementasi |
|-------|------------|
| SQL Injection | PDO prepared statements di **semua** query |
| XSS | `htmlspecialchars()` di output PHP + `escapeHtml()` di frontend JS |
| Session Hijacking | `session_regenerate_id(true)` setelah pembuatan user |
| CSRF | Token di form POST (login, register, admin). API endpoint validasi ownership via `user_id` session |
| API Ownership | Setiap API memvalidasi `user_id` session terhadap `tryout_sessions` / `questions` |
| Password | bcrypt hash |
| RBAC | `user_role` (admin/user) — guard di setiap admin page & API |
| Rate Limiting | Max 5 login attempts per IP per 15 menit |
| Anti-Cheating | Disable right-click, copy, devtools, back button, tab-switch detection |
| File Upload | Validasi MIME type + extension whitelist (jpg, jpeg, png, gif, webp) |
| Security Headers | X-Frame-Options, X-Content-Type-Options, Referrer-Policy, CSP via `.htaccess` |

---

## Keterbatasan Saat Ini

1. **Timer client-side** — Timer berjalan di browser; perlu validasi server-side saat submit untuk cegah manipulasi.
2. **Tidak ada caching** — Load materi dan soal selalu dari disk/DB. Redis bisa ditambah di masa depan.
3. **Video content** — Pembahasan hanya teks, belum ada video.
4. **Mobile app** — Hanya web responsive, belum ada native/PWA app.
5. **SKB module** — Hanya SKD, belum ada SKB (Teknis & Manajerial).

---

## Skalabilitas (Masa Depan)

- **Database**: Dapat dipindah ke PostgreSQL atau cloud MySQL (RDS, Cloud SQL).
- **Caching**: Redis untuk cache soal dan session.
- **Frontend**: Bisa diganti ke React/Vue + REST API.
- **Deployment**: Docker + Nginx reverse proxy.

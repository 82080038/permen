# Roadmap Pengembangan SKD CAT-BKN

Dokumen ini dirancang untuk **diperbarui secara berkala**. Setiap kali ada fitur baru atau milestone tercapai, update tabel di bawah ini.

---

## Versi Saat Ini: v1.0.0 (Current)

### Status: ✅ MVP Selesai
- Landing page, materi pembelajaran, tryout CAT, dan halaman hasil berfungsi.
- 60+ soal realistis (TKP 16, TIU 29, TWK 26).
- Materi lengkap per subtes sesuai Permen PANRB No. 20/2021 & KepmenPANRB No. 208/2025.

---

## Milestone

### M1 — MVP (v1.0.0) ✅ SELESAI
**Target**: Aplikasi bisa latihan dasar

| Fitur | Status |
|-------|--------|
| Landing page | ✅ |
| Materi TWK (5 topik) | ✅ |
| Materi TIU (10 topik) | ✅ |
| Materi TKP (5 topik) | ✅ |
| Bank soal 60+ soal | ✅ |
| Tryout CAT dengan timer | ✅ |
| Submit jawaban & hitung skor | ✅ |
| Halaman hasil & passing grade | ✅ |
| Responsive mobile | ✅ |

---

### M1.5 — Smart Generator & Batch Soal (v1.0.5) ✅ SELESAI
**Target**: Aplikasi bisa menghasilkan soal sendiri tanpa API eksternal

| Fitur | Status | Notes |
|-------|--------|-------|
| Tabel `master_materi` (kisi-kisi AI) | ✅ | 20 materi acuan |
| Tabel `tips_tricks` (reusable tips) | ✅ | 19 tips dengan contoh |
| Batch soal manual 90 soal | ✅ | 30 TKP + 30 TIU + 30 TWK |
| Smart Generator Internal (PHP) | ✅ | `api/generate_soal_smart.php` — tanpa API eksternal |
| AI Generator Eksternal (Gemini) | ✅ | `api/generate_soal_ai.php` — opsional, fallback |
| Mode latihan per subtes terpisah | ✅ | `pages/latihan.php` — TWK 30 soal, TIU 35 soal, TKP 45 soal |
| Parameter tryout 110 soal/110 menit | ✅ | Sesuai KepmenPANRB No. 208/2025 |

---

### M2 — Sistem Autentikasi & User Management (v1.1.0) 🔄 IN PROGRESS
**Target**: User bisa daftar, login, dan melihat riwayat

| Fitur | Status | Notes |
|-------|--------|-------|
| Register user (nama, email, password, instansi) | ✅ | `pages/register.php`, Hash bcrypt |
| Login & logout | ✅ | `pages/login.php`, `api/logout.php`, bcrypt, quick login demo |
| Lupa password (reset via email) | ⬜ | PHPMailer |
| Profil user (edit data, ganti password) | ⬜ | |
| Riwayat tryout per user | ✅ | `pages/user_dashboard.php` |
| Grafik perkembangan nilai | ⬜ | Chart.js |
| Admin dashboard (stats, peserta, riwayat) | ✅ | `pages/admin_dashboard.php` |
| Konfigurasi subtes CRUD | ✅ | Edit durasi, jumlah soal, passing grade di admin_dashboard |
| Export hasil tryout ke CSV | ✅ | `api/export_csv.php` |
| RBAC (role-based access) | ✅ | Guard admin/user di dashboard |

---

### M3 — Admin Panel & CMS Soal (v1.2.0) 🔄 PLANNED
**Target**: Admin bisa kelola soal, materi, dan tips tanpa coding

| Fitur | Status | Notes |
|-------|--------|-------|
| Dashboard admin | ✅ | `pages/admin_dashboard.php` — stats, peserta, riwayat |
| CRUD soal (tambah, edit, hapus) | ⬜ | WYSIWYG editor |
| CRUD materi & tips | ⬜ | HTML editor |
| Upload gambar untuk soal figural | ⬜ | Thumbnail |
| Manajemen tryout (set durasi & passing grade) | ⬜ | Per-event |
| Export hasil tryout ke Excel/CSV | ⬜ | PHPSpreadsheet |

---

### M4 — Peningkatan Try Out (v1.3.0) 🔄 PLANNED
**Target**: Simulasi CAT semakin mendekati BKN asli

| Fitur | Status | Notes |
|-------|--------|-------|
| Timer server-side (anti-cheat) | ✅ | Validasi waktu saat submit — min 60 detik |
| Navigasi soal lanjut (tandai ragu-ragu, review) | ⬜ | Checkbox ragu-ragu |
| Tidak bisa kembali ke soal sebelumnya (mode ketat) | ⬜ | Toggle setting |
| 3 paket tryout (TKP, TIU, TWK) terpisah | ✅ | Bisa latihan per subtes |
| Paket tryout harian / mingguan | ⬜ | Schedule otomatis |
| Bank soal 500+ soal | ⬜ | Prioritas |
| Soal gambar (figural asli) | ⬜ | SVG / PNG |

---

### M5 — Ranking & Komunitas (v1.4.0) 🔄 PLANNED
**Target**: Kompetisi dan belajar bersama

| Fitur | Status | Notes |
|-------|--------|-------|
| Leaderboard global per tryout | ⬜ | Ranking nilai total |
| Leaderboard per instansi (STAN, STIS, dll) | ⬜ | Filter instansi |
| Share hasil ke media sosial | ⬜ | Card image generator |
| Forum diskusi per subtes / topik | ⬜ | Minimal CRUD komentar |
| Grup belajar | ⬜ | Invite link |

---

### M6 — Bimbel & Video (v2.0.0) 🔄 PLANNED
**Target**: Platform bimbel lengkap

| Fitur | Status | Notes |
|-------|--------|-------|
| Video pembelajaran per topik | ⬜ | Embed YouTube / self-host |
| Live class (Zoom/RTC integration) | ⬜ | WebRTC / Jitsi |
| Daily quiz / drilling soal | ⬜ | 5-10 soal/hari |
| Notifikasi push (browser) | ⬜ | Service Worker |
| Kalender event & countdown SKD | ⬜ | Tanggal resmi BKN |
| Panduan seleksi lanjutan per instansi | ⬜ | IPDN, STAN, STIS, dll |

---

### M7 — Mobile & PWA (v2.1.0) 🔄 PLANNED
**Target**: Akses aplikasi via mobile app / PWA

| Fitur | Status | Notes |
|-------|--------|-------|
| Progressive Web App (PWA) | ✅ | Manifest + Service Worker (`sw.js`) |
| Install ke home screen | ✅ | Supported via manifest + SW |
| Offline mode (cache materi) | ✅ | Fallback ke homepage saat offline |
| Mobile app (React Native / Flutter) | ⬜ | Optional |

---

## Log Perubahan

Lihat `CHANGELOG.md` untuk riwayat perubahan detail per versi.

---

## Cara Update Dokumen Ini

1. Setiap kali mulai milestone baru, ubah status dari `🔄 PLANNED` ke `🚧 IN PROGRESS`.
2. Setelah fitur selesai, ubah status ke `✅ DONE`.
3. Saat rilis versi baru, update **Versi Saat Ini** dan pindahkan milestone ke bagian log.
4. Commit dengan pesan: `docs(roadmap): update M{x} — {deskripsi singkat}`.

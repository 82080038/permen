# Prompt Checklist - SKD CAT-BKN Application

**Date**: June 6, 2026  
**Purpose**: Daftar prompt yang diperlukan untuk menyelesaikan aplikasi SKD CAT-BKN secara sempurna  
**Urutan**: Berdasarkan flow aplikasi

---

## FLOW 1: Landing Page & Onboarding

### 1.1 Landing Page Enhancement
- [ ] "Buat landing page yang lebih modern dengan hero section yang menarik, menampilkan statistik aplikasi (jumlah user, tryout selesai, soal tersedia), testimonial user, dan CTA yang jelas untuk register/login. Gunakan desain yang mobile-friendly dengan animasi smooth."

### 1.2 Onboarding Flow
- [ ] "Buat flow onboarding untuk user baru: setelah register, tampilkan modal/slide yang menjelaskan cara menggunakan aplikasi (materi, tryout, daily quiz, leaderboard). Tambahkan opsi 'skip' dan simpan status onboarding_seen di database."

---

## FLOW 2: Authentication System

### 2.1 Login Page Enhancement
- [ ] "Hapus tombol quick login demo dari halaman login.php untuk production. Tambahkan fitur 'Remember Me' dengan cookie yang aman (encrypted). Tambahkan validasi email format yang lebih strict."

### 2.2 Password Reset (Admin-Only)
- [ ] "Password reset dilakukan melalui admin: user request reset via form forgot_password.php → admin menerima notifikasi → admin reset password manual dan beritahu user. Simpan request di tabel password_reset_requests dengan tracking status."

---

## FLOW 3: User Dashboard & Profile

### 3.1 Dashboard Analytics
- [ ] "Enhance user dashboard dengan lebih banyak analytics: grafik perkembangan nilai per subtes (line chart), heatmap aktivitas belajar (hari/jam), comparison dengan rata-rata nasional, dan rekomendasi personal berdasarkan performa lemah."

### 3.2 Profile Enhancement
- [ ] "Tambahkan upload foto profil dengan validasi (jpg/png, max 2MB, resize otomatis). Tambahkan field tambahan: tanggal lahir, jenis kelamin, alamat lengkap. Tambahkan privacy settings untuk menampilkan/sembunyikan profil di leaderboard."

### 3.3 User Settings
- [ ] "Buat halaman settings user: preferensi notifikasi (push/browser), language (ID/EN), tema default (light/dark), default font size. Simpan di tabel user_settings atau kolom tambahan di users."

---

## FLOW 4: Materi Pembelajaran

### 4.1 Materi Enhancement
- [ ] "Tambahkan fitur bookmark materi: user bisa bookmark materi yang ingin dipelajari lagi. Tampilkan list bookmark di dashboard. Tambahkan progress tracking per materi (persentase dibaca)."

### 4.2 Video Content
- [ ] "Integrasikan video pembelajaran: embed YouTube video per topik materi, atau self-host video dengan player HTML5. Tambahkan transkrip video untuk aksesibilitas. Implementasikan video progress tracking."

### 4.3 Interactive Quiz in Materi
- [ ] "Tambahkan mini-quiz di setiap akhir topik materi: 3-5 soal latihan yang langsung bisa dikerjakan untuk menguji pemahaman. Hasil langsung ditampilkan dengan pembahasan singkat."

---

## FLOW 5: Tryout CAT System

### 5.1 Full Tryout Enhancement
- [ ] "Implementasikan fitur 'Ragu-ragu' (doubt flag): user bisa tandai soal dengan tombol M atau checkbox. Tampilkan daftar soal yang diragukan di sidebar untuk review sebelum submit. Simpan status ragu-ragu di tabel answers."

### 5.2 Strict Mode (No Back Navigation)
- [ ] "Tambahkan opsi 'Strict Mode' di tryout: user tidak bisa kembali ke soal sebelumnya setelah menjawab. Implementasikan validasi server-side untuk mencegah navigasi back. Tampilkan warning sebelum memulai strict mode."

### 5.3 Scheduled Tryouts
- [ ] "Buat sistem scheduled tryout: admin bisa jadwalkan tryout dengan waktu tertentu (misal: setiap hari Sabtu jam 09:00). User bisa register untuk scheduled tryout. Timer hanya mulai pada waktu yang dijadwalkan."

### 5.4 Tryout Variants/Packages
- [ ] "Implementasikan multiple tryout packages: Paket A (mudah), Paket B (sedang), Paket C (sulit). Setiap paket memiliki passing grade berbeda. User bisa memilih paket sebelum mulai tryout."

### 5.5 Figural Image Support
- [ ] "Tambahkan dukungan soal figural dengan gambar: upload gambar SVG/PNG untuk soal TIU figural. Implementasikan tap-to-zoom untuk mobile. Tambahkan lazy loading untuk gambar agar performa tetap baik."

### 5.6 Pause/Resume Tryout
- [ ] "Implementasikan fitur pause/resume tryout: user bisa pause tryout (max 3 kali, max 10 menit total). Timer di-pause di server-side. Resume hanya bisa dalam batas waktu yang ditentukan."

---

## FLOW 6: Latihan Per Subtes

### 6.1 Latihan Personal Enhancement
- [ ] "Enhance latihan personal: user bisa memilih topik spesifik, jumlah soal (5-50), dan tingkat kesulitan. Simpan riwayat latihan personal di tabel personal_practice_sessions dengan tracking progress."

### 6.2 Adaptive Learning
- [ ] "Implementasikan adaptive learning: sistem merekomendasikan soal latihan berdasarkan performa user sebelumnya. Jika user lemah di topik X, prioritaskan soal dari topik tersebut."

### 6.3 Timed Practice
- [ ] "Tambahkan opsi timed practice: user bisa set timer per soal (misal: 60 detik) atau total timer untuk latihan. Timer server-side validation seperti tryout penuh."

---

## FLOW 7: Daily Quiz

### 7.1 Daily Quiz Enhancement
- [ ] "Enhance daily quiz: tambahkan streak tracking (berapa hari berturut-turut user mengerjakan), leaderboard daily quiz, dan badge/achievement untuk streak tertentu (7 hari, 30 hari, dll)."

### 7.2 Daily Quiz Topics
- [ ] "Tambahkan daily quiz tematik: setiap hari fokus ke topik tertentu (misal: Senin = TWK Nasionalisme, Selasa = TIU Verbal, dll). Tampilkan jadwal topik di halaman daily quiz."

### 7.3 Daily Quiz Difficulty
- [ ] "Implementasikan difficulty progression: daily quiz menjadi lebih sulit secara bertahap berdasarkan performa user. Jika user consistently high score, tingkatkan difficulty."

---

## FLOW 8: Hasil & Review

### 8.1 Detailed Review Enhancement
- [ ] "Enhance halaman hasil: tambahkan filter review berdasarkan subtes, topik, benar/salah/kosong, ragu-ragu. Tambahkan export review ke PDF dengan format yang rapi."

### 8.2 Comparison Analysis
- [ ] "Tambahkan fitur comparison: user bisa membandingkan hasil tryout dengan tryout sebelumnya (improvement/degradation), atau bandingkan dengan rata-rata instansi pilihan."

### 8.3 Performance Report
- [ ] "Buat laporan performa mingguan/bulanan yang tersedia di dashboard user: ringkasan tryout, daily quiz, latihan, dan rekomendasi belajar. Implementasikan cron job untuk generate laporan otomatis."

---

## FLOW 9: Leaderboard

### 9.1 Social Share
- [ ] "Implementasikan social share: generate card image hasil tryout dengan desain yang menarik (nama, nilai, ranking, instansi). Share ke Facebook, Twitter, WhatsApp dengan pre-filled caption."

### 9.2 Leaderboard Filters
- [ ] "Tambahkan lebih banyak filter leaderboard: filter by instansi (sudah ada), filter by region (provinsi), filter by tryout package, filter by time range (custom date range)."

### 9.3 Leaderboard Badges
- [ ] "Tambahkan badge/achievement di leaderboard: Top 1 minggu ini, Most Improved, Highest Streak, dll. Badge ditampilkan di profil user."

---

## FLOW 10: Admin Dashboard

### 10.1 Admin Analytics
- [ ] "Enhance admin dashboard dengan lebih banyak analytics: grafik registrasi user per hari, grafik tryout completion rate, heatmap aktivitas user, top materi yang diakses, top soal yang salah dijawab."

### 10.2 User Management
- [ ] "Implementasikan full user management: admin bisa edit user data, reset password, suspend/ban user, delete user, view user activity log. Tambahkan bulk action (suspend multiple users)."

### 10.3 Tryout Management
- [ ] "Tambahkan manajemen tryout per-event: admin bisa create tryout event dengan nama, tanggal, paket soal, passing grade khusus. User register ke event. Admin bisa view hasil per event."

### 10.4 Content Moderation
- [ ] "Implementasikan content moderation: admin bisa review flagged content (soal yang dilaporkan user, komentar di forum jika ada), approve/reject dengan reason."

---

## FLOW 11: Admin Panel - CRUD Soal

### 11.1 Full CRUD Soal
- [ ] "Implementasikan full CRUD soal: tambah soal baru dengan form yang lengkap (pertanyaan, pilihan A-E, jawaban benar, pembahasan, tips, related links, materi, gambar, bobot TKP). Edit soal existing. Hapus soal (soft delete dengan is_active=0)."

### 11.2 Bulk Import Soal
- [ ] "Tambahkan bulk import soal dari Excel/CSV: template dengan kolom yang jelas, validasi data sebelum import, preview sebelum konfirmasi, error reporting yang jelas."

### 11.3 Soal Versioning
- [ ] "Implementasikan versioning untuk soal: setiap edit soal menyimpan versi sebelumnya di tabel soal_versions. Admin bisa restore ke versi sebelumnya. Tracking who edited what and when."

### 11.4 Soal Tagging
- [ ] "Tambahkan sistem tagging untuk soal: tag seperti 'sulit', 'populer', 'baru', 'figural', dll. Filter soal berdasarkan tag di admin panel dan user practice."

---

## FLOW 12: Admin Panel - CRUD Materi & Tips

### 12.1 CRUD Materi
- [ ] "Implementasikan full CRUD materi: admin bisa tambah materi baru dengan WYSIWYG editor (CKEditor atau TinyMCE), edit materi existing, hapus materi, reorder materi (drag & drop urutan)."

### 12.2 CRUD Tips
- [ ] "Implementasikan full CRUD tips: admin bisa tambah tips baru dengan contoh soal dan penerapan, edit tips existing, hapus tips, kategorikan tips per subtes/topik."

### 12.3 Media Library
- [ ] "Buat media library untuk admin: upload dan manage gambar/video yang digunakan di materi dan soal. Organize dalam folder, search, filter, delete unused media."

---

## FLOW 13: Admin Panel - Revision Workflow

### 13.1 Revision Queue
- [ ] "Enhance revision workflow: buat queue revision yang menampilkan soal yang ditandai perlu revisi oleh user. Admin bisa assign revision ke editor lain, set priority, add comment."

### 13.2 Revision History
- [ ] "Tambahkan revision history: tracking semua perubahan pada soal (who, when, what changed). View history per soal dengan diff view."

### 13.3 Auto-Revision Detection
- [ ] "Implementasikan auto-detection untuk soal yang mungkin perlu revisi: soal dengan answer rate < 20% (mungkin ambiguous), soal dengan banyak flag ragu-ragu, soal dengan lama tidak di-revisi (> 6 bulan)."

---

## FLOW 14: Forum Diskusi

### 14.1 Forum System
- [ ] "Implementasikan forum diskusi: user bisa buat thread per topik/subtes, reply thread, upvote/downvote reply. Admin bisa moderate (pin thread, lock thread, delete inappropriate content)."

### 14.2 Forum Integration
- [ ] "Integrasikan forum dengan materi: di setiap halaman materi, tampilkan link ke thread diskusi terkait topik tersebut. Di halaman review soal, tampilkan link ke diskusi soal tersebut."

### 14.3 Forum Notifications
- [ ] "Tambahkan notifikasi forum: user dapat notifikasi jika thread di-reply, di-upvote, atau di-mention. Settings untuk notifikasi bisa di-on/off per user."

---

## FLOW 15: Grup Belajar

### 15.1 Study Group System
- [ ] "Implementasikan sistem grup belajar: user bisa create study group, invite member via link, set group name, description, avatar. Member bisa join grup dengan approval atau open join."

### 15.2 Group Features
- [ ] "Tambahkan fitur grup: shared leaderboard dalam grup, group challenge (tryout bersama), group chat, shared notes/materials, group progress tracking."

### 15.3 Group Leaderboard
- [ ] "Buat leaderboard per grup: ranking member dalam grup, comparison dengan grup lain, group achievement badge."

---

## FLOW 16: Bimbel Features

### 16.1 Video Learning Platform
- [ ] "Implementasikan video learning platform: upload video per topik, organize dalam playlist, video progress tracking per user, video bookmark, video speed control, subtitle support."

### 16.2 Live Class Integration
- [ ] "Integrasikan live class: embed Zoom/Jitsi/Google Meet untuk kelas live. Schedule live class di calendar. User bisa register ke live class. Recording live class tersedia untuk replay."

### 16.3 Event Calendar
- [ ] "Buat event calendar: display jadwal tryout, live class, deadline pendaftaran SKD, dll. User bisa add event ke personal calendar (Google Calendar/Outlook integration)."

### 16.4 Institution Guides
- [ ] "Tambahkan panduan seleksi per instansi: IPDN, STAN, STIS, Poltekim, dll. Include syarat, passing grade, tips khusus, alumni sharing, FAQ."

---

## FLOW 17: Mobile & PWA Enhancement

### 17.1 PWA Enhancement
- [ ] "Enhance PWA: tambahkan offline caching untuk materi dan soal agar bisa diakses tanpa internet. Implementasikan background sync untuk jawaban yang tersimpan saat offline. Tambahkan install prompt yang lebih user-friendly."

### 17.2 Push Notifications
- [ ] "Implementasikan push notifications: notifikasi untuk daily quiz reminder, live class starting, new materi available, tryout result ready. User bisa manage notification preferences."

### 17.3 Mobile App (React Native/Flutter)
- [ ] "Develop mobile app native dengan React Native atau Flutter: implementasikan semua fitur web version dengan UX yang dioptimalkan untuk mobile. Offline support, push notifications, biometric login."

---

## FLOW 18: Analytics & Reporting

### 18.1 User Behavior Analytics
- [ ] "Implementasikan user behavior analytics: tracking page views, time spent per page, click heatmap, scroll depth. Gunakan tools seperti Google Analytics atau Matomo."

### 18.2 Learning Analytics
- [ ] "Tambahkan learning analytics: tracking learning path user, materi yang sering diakses, soal yang sering diulang, time spent per topic. Generate learning insights untuk user."

### 18.3 Admin Reports
- [ ] "Buat system admin reports: generate report PDF/Excel untuk user activity, tryout results, content performance, revenue (jika berbayar). Schedule automatic report generation tersedia di admin dashboard."

---

## FLOW 19: Security & Performance

### 19.1 Security Hardening
- [ ] "Implementasikan security hardening: add CAPTCHA di login/register (reCAPTCHA v3), implementasikan rate limiting lebih strict untuk API, add IP whitelist untuk admin panel, implementasikan audit logging untuk semua admin actions."

### 19.2 Performance Optimization
- [ ] "Optimize performance: implementasikan Redis caching untuk soal dan materi, add database indexing untuk query yang sering diakses, implementasikan CDN untuk static assets, optimize images (WebP format, lazy loading)."

### 19.3 Load Testing
- [ ] "Perform load testing: gunakan tools seperti Apache JMeter atau k6 untuk test aplikasi dengan 1000+ concurrent users. Identify bottleneck dan optimize accordingly."

---

## FLOW 20: Production Deployment

### 20.1 Production Checklist
- [ ] "Complete production deployment checklist: set APP_ENV=production di .env, remove all debug code, enable HTTPS, update BASE_URL ke https, change default admin passwords, change database root password, enable database backups, set up monitoring (Uptime monitoring, error tracking)."

### 20.2 CI/CD Pipeline
- [ ] "Implementasikan CI/CD pipeline: GitHub Actions atau GitLab CI untuk automated testing, linting, dan deployment. Auto-deploy ke staging setiap merge ke main, manual approval untuk production."

### 20.3 Monitoring & Alerting
- [ ] "Set up monitoring dan alerting: monitor server metrics (CPU, memory, disk), application metrics (response time, error rate), database metrics (slow query, connection pool). Alert via Slack/WhatsApp jika threshold terlewati."

### 20.4 Backup & Disaster Recovery
- [ ] "Implementasikan backup dan disaster recovery: automated daily database backup, backup ke multiple locations (local + cloud), test restore procedure regularly, document disaster recovery plan."

---

## FLOW 21: Documentation

### 21.1 User Documentation
- [ ] "Buat user documentation lengkap: user manual dengan screenshot, FAQ, video tutorial, troubleshooting guide. Tampilkan di help center atau link di footer."

### 21.2 Admin Documentation
- [ ] "Buat admin documentation: admin manual untuk semua fitur admin, best practices, security guidelines, troubleshooting. Accessible dari admin dashboard."

### 21.3 API Documentation
- [ ] "Update API documentation: gunakan OpenAPI/Swagger untuk generate interactive API docs. Include semua endpoint, request/response examples, error codes, rate limiting info."

---

## FLOW 22: Monetization (Optional)

### 22.1 Subscription System
- [ ] "Implementasikan subscription system: free tier (limited tryout, basic materi), premium tier (unlimited tryout, all materi, video learning, live class). Payment gateway integration (Midtrans, Stripe)."

### 22.2 Coupon System
- [ ] "Tambahkan coupon system: generate discount codes for promotion, set validity period, usage limit, user-specific coupons. Track coupon usage analytics."

### 22.3 Affiliate System
- [ ] "Implementasikan affiliate system: user bisa refer friend dan dapat commission. Track referral links, conversion rate, payout management."

---

## Summary

Total prompts: **64 prompts** organized into **22 flows** based on application workflow.

**Priority Order:**
1. **High Priority**: Flow 2 (Authentication), Flow 5 (Tryout), Flow 10-13 (Admin Panel), Flow 20 (Production Deployment)
2. **Medium Priority**: Flow 3 (Dashboard), Flow 4 (Materi), Flow 8 (Hasil), Flow 9 (Leaderboard)
3. **Low Priority**: Flow 14-17 (Forum, Group, Bimbel, Mobile), Flow 18-19 (Analytics, Security), Flow 21-22 (Documentation, Monetization)

**Estimated Completion Time**: 3-6 months dengan 1-2 developer full-time.

---

**Generated By**: Cascade AI Assistant  
**Date**: June 6, 2026  
**Based On**: SKD CAT-BKN Application Analysis (v1.0.0 - v1.4.0)

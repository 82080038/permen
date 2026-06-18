# Manual Deployment Steps - Hostinger
## SKD CAT-BKN Application

**Deployment Package:** deploy_package_20260618_*.tar.gz (Latest: deploy_package_20260618_*.tar.gz)  
**Location:** /opt/lampp/htdocs/permen/  
**Files to Deploy:**
- pages/user_dashboard.php
- pages/profile.php
- pages/tryout.php
- pages/leaderboard.php
- pages/daily_quiz.php
- pages/scheduled_tryouts.php
- assets/js/sw.js
- scripts/complete_user_simulation.js (optional, for testing)

**Purpose:** Fix database compatibility issues between local and production environments

---

## Step 1: Access Hostinger hPanel

1. Buka browser dan kunjungi: https://hpanel.hostinger.com
2. Login dengan akun Hostinger Anda
3. Navigate ke **Hosting** → **Manage** untuk domain bimbel.bereng.info

---

## Step 2: Backup Current Installation

**PENTING:** Selalu backup sebelum deploy!

### 2.1 Backup Files

1. Di hPanel, klik **File Manager**
2. Navigate ke `public_html`
3. Select semua file dan folder (Ctrl+A atau Cmd+A)
4. Klik **Archive** di toolbar atas
5. Beri nama: `backup_before_deployment_20260618.tar.gz`
6. Klik **Create Archive**
7. Download archive ke komputer lokal Anda

### 2.2 Backup Database

1. Di hPanel, klik **Databases** → **phpMyAdmin**
2. Select database: `u950781813_skd_cat_bkn`
3. Klik tab **Export**
4. Pilih **Quick** export method
5. Klik **Go**
6. Save file SQL ke komputer lokal Anda

---

## Step 3: Upload Deployment Package

### 3.1 Copy Deployment Package

Dari terminal lokal:

```bash
cd /opt/lampp/htdocs/permen
# Copy file ke lokasi yang mudah diakses (misal: Desktop)
cp deploy_package_20260618_213411.tar.gz ~/Desktop/
```

### 3.2 Upload via File Manager

1. Di Hostinger File Manager, navigate ke `public_html`
2. Delete semua file dan folder yang ada (setelah backup!)
3. Klik **Upload** di toolbar atas
4. Select dan upload `deploy_package_20260618_213411.tar.gz`
5. Tunggu upload selesai

### 3.3 Extract Archive

1. Di File Manager, right-click pada `deploy_package_20260618_213411.tar.gz`
2. Pilih **Extract**
3. File akan diekstrak ke `public_html`
4. Delete file archive setelah ekstraksi

---

## Step 4: Verify File Structure

Pastikan struktur file benar:

```
public_html/
├── api/
├── assets/
├── content/
├── includes/
├── pages/
├── scripts/
├── src/
├── sql/
├── .htaccess
├── .env
├── config.php
├── env_loader.php
├── helpers.php
├── index.php
└── 404.php
```

---

## Step 5: Set File Permissions

Di File Manager:

1. Select semua folder
2. Klik **Permissions** atau **Chmod**
3. Set ke **755**
4. Select semua file
5. Set ke **644**
6. Klik **Apply**

Atau via SSH (jika ada akses):

```bash
cd /home/u950781813/public_html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 755 scripts/*.sh
```

---

## Step 6: Configure SSL/HTTPS

### 6.1 Install SSL (jika belum)

1. Di hPanel, navigate ke **Domains** → **SSL**
2. Cari domain bimbel.bereng.info
3. Klik **Setup** atau **Install**
4. Pilih **Free Let's Encrypt SSL**
5. Klik **Install**

### 6.2 Force HTTPS Redirect

1. Di hPanel, navigate ke **Domains** → **Manage**
2. Klik tab **HTTPS**
3. Enable **Force HTTPS**
4. Klik **Save**

### 6.3 Verify SSL

1. Buka browser dan kunjungi: `https://bimbel.bereng.info`
2. Pastikan lock icon muncul di address bar
3. Tidak ada warning mixed content

---

## Step 7: Setup Automated Backups

### 7.1 Via Hostinger Cron Manager

1. Di hPanel, navigate ke **Cron Jobs**
2. Klik **Create New Cron Job**
3. Configure:
   - **Type:** PHP
   - **Run:** `/home/u950781813/public_html/scripts/backup_database.sh`
   - **Schedule:** Daily at 2:00 AM
   - **Output:** Redirect to log file
4. Klik **Save**

### 7.2 Atau Via SSH (jika ada akses)

```bash
# Add to crontab
crontab -e

# Add this line:
0 2 * * * /home/u950781813/public_html/scripts/backup_database.sh >> /home/u950781813/logs/backup.log 2>&1
```

---

## Step 8: Test Deployment

### 8.1 Manual Testing

Buka browser dan test:

1. **Homepage:** https://bimbel.bereng.info
   - [ ] Page loads correctly
   - [ ] HTTPS lock icon present
   - [ ] No mixed content warnings

2. **Login Page:** https://bimbel.bereng.info/pages/login.php
   - [ ] Page loads
   - [ ] Can attempt login

3. **Register Page:** https://bimbel.bereng.info/pages/register.php
   - [ ] Page loads
   - [ ] Can attempt registration

4. **User Dashboard:** (setelah login)
   - [ ] Dashboard loads
   - [ ] Analytics display

5. **Tryout Page:** https://bimbel.bereng.info/pages/tryout.php
   - [ ] Page loads
   - [ ] Can start tryout

6. **Latihan Page:** https://bimbel.bereng.info/pages/latihan.php
   - [ ] Page loads
   - [ ] Can select subtes

7. **Materi Page:** https://bimbel.bereng.info/pages/materi.php
   - [ ] Page loads
   - [ ] Materi displays

8. **Leaderboard:** https://bimbel.bereng.info/pages/leaderboard.php
   - [ ] Page loads
   - [ ] Leaderboard displays

### 8.2 Verify Security Headers

Di terminal lokal:

```bash
curl -I https://bimbel.bereng.info
```

Expected headers:
- `Strict-Transport-Security`
- `X-Frame-Options`
- `X-Content-Type-Options`
- `X-XSS-Protection`
- `Content-Security-Policy`

---

## Step 9: Post-Deployment Verification

### 9.1 Check Error Logs

1. Di hPanel, navigate to **Files** → **Error Logs**
2. Review recent errors
3. Fix jika ada critical errors

### 9.2 Verify Database Connection

1. Test login dengan user yang ada
2. Jika gagal, cek .env credentials
3. Verify database user permissions

### 9.3 Test API Endpoints

Test beberapa API endpoint:

```bash
# Test get_soal API
curl "https://bimbel.bereng.info/api/get_soal.php?subtes=TIU&limit=1"

# Test get_leaderboard API
curl "https://bimbel.bereng.info/api/get_leaderboard.php"
```

---

## Rollback Procedure

Jika ada masalah setelah deployment:

### Option 1: Quick File Rollback

1. Di File Manager, delete semua file saat ini
2. Upload backup archive yang didownload di Step 2
3. Extract archive
4. Restore .env file jika perlu

### Option 2: Database Rollback

1. Di phpMyAdmin, drop semua tabel
2. Import backup SQL file dari Step 2
3. Verify data integrity

### Option 3: Full Rollback

1. Restore files dari backup
2. Restore database dari backup
3. Clear browser cache
4. Test functionality

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Solutions:**
1. Check error logs di hPanel
2. Verify file permissions (755 dirs, 644 files)
3. Check .htaccess syntax
4. Verify PHP version (requires 7.4+)

### Issue: Database Connection Failed

**Solutions:**
1. Verify .env credentials
2. Check database status di hPanel
3. Test via phpMyAdmin
4. Verify env_loader.php exists

### Issue: SSL Not Working

**Solutions:**
1. Install Let's Encrypt SSL
2. Enable Force HTTPS
3. Wait for DNS propagation (max 48 jam)
4. Clear browser cache

### Issue: Login Not Working

**Solutions:**
1. Verify COOKIE_SECURE=true di .env
2. Check session configuration
3. Verify users table exists
4. Check error logs

---

## Contact Support

Jika ada masalah:

- **Hostinger Support:** https://support.hostinger.com
- **Live Chat:** Available di hPanel
- **Documentation:** `docs/PRODUCTION_DEPLOYMENT_GUIDE.md`
- **SSL Guide:** `docs/SSL_HTTPS_VERIFICATION_GUIDE.md`

---

## Deployment Checklist

- [ ] Backup files completed
- [ ] Backup database completed
- [ ] Deployment package uploaded
- [ ] Archive extracted
- [ ] File permissions set
- [ ] SSL installed
- [ ] HTTPS redirect enabled
- [ ] Homepage loads correctly
- [ ] Login page accessible
- [ ] User dashboard loads
- [ ] Tryout page loads
- [ ] Latihan page loads
- [ ] Materi page loads
- [ ] Leaderboard loads
- [ ] Security headers verified
- [ ] Error logs checked
- [ ] Database connection verified
- [ ] API endpoints tested
- [ ] Cron job configured

---

**Status:** Ready for Manual Deployment  
**Last Updated:** 2026-06-18

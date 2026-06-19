---
description: Deploy and setup SKD CAT-BKN application
---

# Deployment Workflow — SKD CAT-BKN

## Production Environment

- **URL**: `https://bimbel.bereng.info`
- **Hosting**: Hostinger
- **PHP**: 8.3.30
- **Database**: MariaDB (u950781813_skd_cat_bkn)
- **DB User**: u950781813_root
- **DB Pass**: Sihaloho1982

## Deploy from Local to Production

### 1. Export Local Database
```powershell
C:\xampp\mysql\bin\mysqldump.exe -u root -proot skd_cat_bkn > sql\skd_cat_bkn_current.sql
```

### 2. Commit & Push to GitHub
```powershell
git add -A
git commit -m "deploy: update for production"
git push origin main
```

### 3. Update Files on Hostinger
- Login Hostinger hPanel → File Manager
- Navigate to `public_html/`
- Upload changed files or use Git deployment

### 4. Import Database on Hostinger
- Login Hostinger hPanel → phpMyAdmin
- Select database `u950781813_skd_cat_bkn`
- Import `sql/skd_cat_bkn_current.sql`

### 5. Verify .env on Production
Production `.env` must contain:
```
DB_HOST=localhost
DB_NAME=u950781813_skd_cat_bkn
DB_USER=u950781813_root
DB_PASS=Sihaloho1982
DB_CHARSET=utf8mb4
APP_ENV=production
BASE_URL=https://bimbel.bereng.info
```

### 6. Verify Deployment
```powershell
# Health check
Invoke-WebRequest -Uri "https://bimbel.bereng.info/api/health.php" -UseBasicParsing | Select-Object -ExpandProperty Content

# Landing stats
Invoke-WebRequest -Uri "https://bimbel.bereng.info/api/get_landing_stats.php" -UseBasicParsing | Select-Object -ExpandProperty Content
```

## Test Users (All Environments)

| Role  | No HP        | Password     |
|-------|--------------|--------------|
| Admin | 081265511982 | Sihaloho1982 |
| User  | 081987654321 | Sihaloho1982 |

## Database Info

- **57 tables**, 2678 questions, 3 subtes (TWK/TIU/TKP)
- Key tables: `users`, `questions`, `tryout_sessions`, `session_subtes`, `answers`, `subtes_config`, `instansi`
- Column differences: local uses `aktif`, production may use `is_active` (virtual column added)

## Security Checklist

- [x] `.env` protected by `.htaccess`
- [x] SQL directory protected
- [x] CSRF on all forms
- [x] Bcrypt password hashing
- [x] Rate limiting on login
- [x] Prepared statements (no SQL injection)
- [x] HTTPS enforced on production

## Rollback

If deployment fails:
1. Restore previous database from Hostinger backup
2. Revert git commit: `git revert HEAD && git push`
3. Re-upload old files via File Manager

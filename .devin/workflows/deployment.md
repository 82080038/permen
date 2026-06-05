---
description: Deploy and setup SKD CAT-BKN application
---

# Deployment Workflow - SKD CAT-BKN

This workflow covers deployment and setup of the SKD CAT-BKN application.

## Initial Setup

### 1. Clone Repository
```bash
cd /opt/lampp/htdocs
git clone https://github.com/82080038/permen.git
cd permen
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 3. Start XAMPP Services
```bash
sudo /opt/lampp/lampp start
```

### 4. Import Database
```bash
# Create database
/opt/lampp/bin/mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS skd_cat_bkn;"

# Import schema and data
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/skd_cat_bkn.sql

# Import additional data (optional)
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_master_materi.sql
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn < sql/batch_tips.sql
```

### 5. Verify Installation
```bash
# Check XAMPP status
sudo /opt/lampp/lampp status

# Check database
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn -e "SHOW TABLES;"
/opt/lampp/bin/mysql -u root -proot skd_cat_bkn -e "SELECT COUNT(*) FROM questions;"
```

## Access Application

Open browser and navigate to:
```
http://localhost/permen/
```

## Default Test User

Email: `budi@skd.test`
Password: `password`

## Database Structure

### Key Tables
- `users` - User accounts
- `questions` - Question bank (2,771 questions)
- `answers` - User answers
- `tryout_sessions` - Tryout sessions
- `session_subtes` - Normalized subtes data
- `subtes_config` - Subtes configuration
- `master_materi` - Material for AI generator
- `tips_tricks` - Tips and tricks (1,601 records)
- `instansi` - Institution data

## File Permissions

Ensure proper permissions:
```bash
chmod 755 /opt/lampp/htdocs/permen
chmod 644 /opt/lampp/htdocs/permen/.env
```

## Security Notes

- `.env` file is protected by `.htaccess`
- SQL files in `sql/` directory are protected
- Config files are protected
- CSRF protection enabled on all forms
- Rate limiting on login (5 attempts per 15 minutes)
- Prepared statements for all SQL queries

## Troubleshooting

### Database Connection Failed
Check `.env` configuration and XAMPP MySQL status.

### 500 Errors
Check PHP error logs:
```bash
tail -f /opt/lampp/logs/php_error_log
```

### Permission Denied
Ensure XAMPP has proper permissions:
```bash
sudo chown -R nobody:nogroup /opt/lampp/htdocs/permen
```

## Backup

### Database Backup
```bash
/opt/lampp/bin/mysqldump -u root -proot skd_cat_bkn > backup_$(date +%Y%m%d).sql
```

### Files Backup
```bash
tar -czf permen_backup_$(date +%Y%m%d).tar.gz /opt/lampp/htdocs/permen
```

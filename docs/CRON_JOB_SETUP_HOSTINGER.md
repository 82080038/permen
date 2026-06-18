# Cron Job Setup for Automated Backups - Hostinger

## Overview
Setup automated database backups on Hostinger using cron jobs.

## Prerequisites
- Hostinger account access
- SSH access to Hostinger
- Database credentials already configured in .env

## Step 1: Upload Backup Script to Hostinger

The backup script is already in the deployment package:
- Location: `/home/u950781813/domains/bimbel.bereng.info/public_html/scripts/backup_database.sh`

## Step 2: Make Script Executable

Via SSH:
```bash
ssh -p 65002 u950781813@153.92.8.148
cd /home/u950781813/domains/bimbel.bereng.info/public_html/scripts
chmod +x backup_database.sh
```

## Step 3: Create Backups Directory

```bash
cd /home/u950781813/domains/bimbel.bereng.info/public_html
mkdir -p backups
chmod 755 backups
```

## Step 4: Setup Cron Job via hPanel

1. Login to Hostinger hPanel
2. Navigate to **Hosting** → **Manage** → **Cron Jobs**
3. Click **Add new cron job**

### Daily Backup (Recommended)
- **Interval:** Once a day
- **Time:** 02:00 AM (low traffic period)
- **Command:**
```bash
cd /home/u950781813/domains/bimbel.bereng.info/public_html && /usr/bin/php scripts/backup_database.sh
```

### Weekly Backup (Optional)
- **Interval:** Once a week
- **Day:** Sunday
- **Time:** 03:00 AM
- **Command:**
```bash
cd /home/u950781813/domains/bimbel.bereng.info/public_html && /usr/bin/php scripts/backup_database.sh
```

## Step 5: Verify Cron Job

Check if backups are being created:
```bash
cd /home/u950781813/domains/bimbel.bereng.info/public_html/backups
ls -la
```

Expected output:
```
-rw-r--r-- 1 u9507813 user  1234567 Jun 19 02:00 backup_20260619_020000.sql.gz
```

## Step 6: Download Backups Locally (Optional)

To download backups to your local machine:
```bash
scp -P 65002 u950781813@153.92.8.148:/home/u950781813/domains/bimbel.bereng.info/public_html/backups/backup_*.sql.gz ~/Desktop/
```

## Backup Retention

The backup script automatically:
- Keeps only the last 7 backups
- Deletes older backups automatically
- Compresses backups with gzip

## Troubleshooting

### Cron job not running
- Check cron job syntax in hPanel
- Verify script permissions: `ls -la scripts/backup_database.sh`
- Check Hostinger cron job logs

### Backup file not created
- Verify database credentials in .env
- Check if backups directory exists and is writable
- Test script manually: `./scripts/backup_database.sh`

### Permission denied
- Ensure script is executable: `chmod +x scripts/backup_database.sh`
- Check directory permissions: `chmod 755 backups`

## Alternative: Manual Backup

If cron jobs are not available, run manual backup:
```bash
ssh -p 65002 u950781813@153.92.8.148
cd /home/u950781813/domains/bimbel.bereng.info/public_html
./scripts/backup_database.sh
```

## Summary

- ✅ Backup script deployed
- ⏳ Make script executable
- ⏳ Create backups directory
- ⏳ Setup cron job in hPanel
- ⏳ Verify backups are created

After completing these steps, your database will be automatically backed up daily.

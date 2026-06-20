#!/bin/bash
# Database backup script for SKD CAT-BKN
# Usage: Add to crontab: 0 2 * * * /path/to/permen/scripts/db_backup.sh

DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-skd_cat_bkn}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"
BACKUP_DIR="${BACKUP_DIR:-/opt/lampp/htdocs/permen/backups}"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="${BACKUP_DIR}/skd_cat_bkn_${DATE}.sql.gz"

# Dump and compress
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --single-transaction --routines --triggers "$DB_NAME" 2>/dev/null | gzip > "$FILENAME"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup created: $FILENAME ($(du -h "$FILENAME" | cut -f1))"
    # Delete backups older than retention period
    find "$BACKUP_DIR" -name "skd_cat_bkn_*.sql.gz" -mtime +$RETENTION_DAYS -delete
    echo "[$(date)] Old backups cleaned (older than ${RETENTION_DAYS} days)"
else
    echo "[$(date)] ERROR: Backup failed!"
    exit 1
fi

#!/bin/bash
# Automated Backup Script for SKD CAT-BKN Application
# This script backs up the database and application files
# Usage: ./scripts/backup.sh

# Configuration
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/lampp/htdocs/permen/backups"
DB_NAME="skd_cat_bkn"
DB_USER="root"
DB_PASS="root"
APP_DIR="/opt/lampp/htdocs/permen"
RETENTION_DAYS=7

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)"

# Backup database
echo "Backing up database..."
mysqldump -u "$DB_USER" -p"$DB_PASS" -S /opt/lampp/var/mysql/mysql.sock "$DB_NAME" > "$BACKUP_DIR/db_$DATE.sql"
if [ $? -eq 0 ]; then
    echo "Database backup successful: db_$DATE.sql"
else
    echo "ERROR: Database backup failed"
    exit 1
fi

# Backup application files
echo "Backing up application files..."
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" -C "$APP_DIR" --exclude='node_modules' --exclude='test-results' --exclude='.git' --exclude='backups' .
if [ $? -eq 0 ]; then
    echo "File backup successful: files_$DATE.tar.gz"
else
    echo "ERROR: File backup failed"
    exit 1
fi

# Remove backups older than retention period
echo "Cleaning up old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "db_*.sql" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "files_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# List current backups
echo "Current backups:"
ls -lh "$BACKUP_DIR"

echo "Backup completed successfully at $(date)"

#!/bin/bash
# Production Deployment Script
# SKD CAT-BKN Application
# Usage: ./deploy_production.sh

set -e

# Configuration
PROJECT_DIR="/opt/lampp/htdocs/permen"
DEPLOY_DIR="$PROJECT_DIR/deploy_package"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$PROJECT_DIR/backups"
ENV_FILE="$PROJECT_DIR/.env.production"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "=========================================="
echo "SKD CAT-BKN Production Deployment"
echo "=========================================="
echo "Timestamp: $TIMESTAMP"
echo "=========================================="
echo ""

# Step 1: Pre-deployment checks
echo -e "${BLUE}[1/7] Pre-deployment checks...${NC}"

# Check if .env.production exists
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}✗ FAIL: .env.production not found${NC}"
    exit 1
fi
echo -e "${GREEN}✓ .env.production exists${NC}"

# Check if backup directory exists
mkdir -p "$BACKUP_DIR"
echo -e "${GREEN}✓ Backup directory ready${NC}"

# Check PHP syntax on critical files only
echo "Checking PHP syntax on critical files..."
CRITICAL_FILES=(
    "config.php"
    "env_loader.php"
    "helpers.php"
    "index.php"
    "api/admin_dashboard.php"
    "api/user_dashboard.php"
    "api/get_soal.php"
    "api/login.php"
    "api/register.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$PROJECT_DIR/$file" ]; then
        php -l "$PROJECT_DIR/$file" > /dev/null 2>&1
        if [ $? -ne 0 ]; then
            echo -e "${RED}✗ FAIL: Syntax error in $file${NC}"
            exit 1
        fi
    fi
done
echo -e "${GREEN}✓ Critical PHP files have valid syntax${NC}"

echo ""

# Step 2: Create deployment package
echo -e "${BLUE}[2/7] Creating deployment package...${NC}"

# Clean previous deploy directory
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# Copy essential files and directories
echo "Copying files..."
cp -r "$PROJECT_DIR/api" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/assets" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/content" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/includes" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/pages" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/scripts" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/src" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/sql" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/vendor" "$DEPLOY_DIR/"  # Include vendor directory
cp "$PROJECT_DIR/.htaccess" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/config.php" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/env_loader.php" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/helpers.php" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/index.php" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/404.php" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/favicon.ico" "$DEPLOY_DIR/" 2>/dev/null || true
cp "$PROJECT_DIR/VERSION" "$DEPLOY_DIR/" 2>/dev/null || true

# Copy .env.production as .env
cp "$ENV_FILE" "$DEPLOY_DIR/.env"

# Remove development-only files
rm -rf "$DEPLOY_DIR/test-results"
rm -rf "$DEPLOY_DIR/node_modules"
rm -rf "$DEPLOY_DIR/.git"
rm -rf "$DEPLOY_DIR/.devin"
rm -rf "$DEPLOY_DIR/.github"
rm -f "$DEPLOY_DIR/.env.example"
rm -f "$DEPLOY_DIR/.env.bereng.info"
rm -f "$DEPLOY_DIR/.env.freehosting"
rm -f "$DEPLOY_DIR/.env.hostinger"
rm -f "$DEPLOY_DIR/composer.json"
rm -f "$DEPLOY_dir/composer.lock"
rm -f "$DEPLOY_DIR/package.json"
rm -f "$DEPLOY_DIR/package-lock.json"
rm -f "$DEPLOY_DIR/playwright.config.js"
rm -f "$DEPLOY_DIR/phpunit.xml"
rm -f "$DEPLOY_DIR/phpstan.neon"
rm -f "$DEPLOY_DIR/.php-cs-fixer.php"
rm -f "$DEPLOY_DIR/test_get_soal.php"
rm -f "$DEPLOY_DIR/ERROR_INVESTIGATION_REPORT.md"

echo -e "${GREEN}✓ Deployment package created${NC}"

# Get package size
SIZE=$(du -sh "$DEPLOY_DIR" | cut -f1)
echo "Package size: $SIZE"

echo ""

# Step 3: Create backup
echo -e "${BLUE}[3/7] Creating backup...${NC}"

# Backup current .env if exists
if [ -f "$PROJECT_DIR/.env" ]; then
    cp "$PROJECT_DIR/.env" "$BACKUP_DIR/.env_backup_$TIMESTAMP"
    echo -e "${GREEN}✓ .env backed up${NC}"
fi

# Skip database backup (production database is on Hostinger)
echo -e "${YELLOW}⚠ Skipping database backup (production DB is on Hostinger)${NC}"
echo -e "${GREEN}✓ Backup step completed${NC}"

echo ""

# Step 4: Verify production configuration
echo -e "${BLUE}[4/7] Verifying production configuration...${NC}"

# Check APP_ENV
if grep -q "APP_ENV=production" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ APP_ENV=production${NC}"
else
    echo -e "${RED}✗ FAIL: APP_ENV not set to production${NC}"
    exit 1
fi

# Check BASE_URL
if grep -q "BASE_URL=https://" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ BASE_URL uses HTTPS${NC}"
else
    echo -e "${RED}✗ FAIL: BASE_URL not using HTTPS${NC}"
    exit 1
fi

# Check COOKIE_SECURE
if grep -q "COOKIE_SECURE=true" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ COOKIE_SECURE=true${NC}"
else
    echo -e "${RED}✗ FAIL: COOKIE_SECURE not set to true${NC}"
    exit 1
fi

# Check DISPLAY_ERRORS
if grep -q "DISPLAY_ERRORS=0" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ DISPLAY_ERRORS=0${NC}"
else
    echo -e "${RED}✗ FAIL: DISPLAY_ERRORS not set to 0${NC}"
    exit 1
fi

echo ""

# Step 5: Create deployment archive
echo -e "${BLUE}[5/7] Creating deployment archive...${NC}"

cd "$PROJECT_DIR"
tar -czf "deploy_package_${TIMESTAMP}.tar.gz" -C "$DEPLOY_DIR" .
echo -e "${GREEN}✓ Archive created: deploy_package_${TIMESTAMP}.tar.gz${NC}"

ARCHIVE_SIZE=$(du -sh "deploy_package_${TIMESTAMP}.tar.gz" | cut -f1)
echo "Archive size: $ARCHIVE_SIZE"

echo ""

# Step 6: Generate deployment instructions
echo -e "${BLUE}[6/7] Generating deployment instructions...${NC}"

cat > "DEPLOY_INSTRUCTIONS_${TIMESTAMP}.txt" << EOF
========================================
SKD CAT-BKN Production Deployment
========================================
Deployment Package: deploy_package_${TIMESTAMP}.tar.gz
Created: $TIMESTAMP
Archive Size: $ARCHIVE_SIZE

========================================
DEPLOYMENT STEPS
========================================

1. Upload to Hostinger:
   - Login to Hostinger hPanel
   - Go to File Manager
   - Navigate to public_html
   - Upload deploy_package_${TIMESTAMP}.tar.gz
   - Extract the archive

2. Set file permissions:
   - All files: 644
   - All directories: 755
   - scripts/*.sh: 755 (if needed)

3. Configure SSL:
   - Follow SSL_HTTPS_VERIFICATION_GUIDE.md
   - Ensure HTTPS is enforced
   - Verify security headers

4. Setup cron job for backups:
   - Copy scripts/backup_cron.conf to server
   - Add to crontab or use Hostinger cron manager
   - Daily backup at 2:00 AM recommended

5. Run smoke tests:
   - Access https://bimbel.bereng.info
   - Test login functionality
   - Test tryout flow
   - Verify all pages load correctly

6. Monitor:
   - Check error logs
   - Verify backups are created
   - Monitor SSL certificate expiration

========================================
ROLLBACK PROCEDURE
========================================

If issues occur:
1. Restore database from backup
2. Revert .env file
3. Restore previous code version

========================================
CONTACT
========================================
For issues, check:
- docs/SSL_HTTPS_VERIFICATION_GUIDE.md
- docs/PRODUCTION_READINESS_ASSESSMENT.md
- docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md

========================================
EOF

echo -e "${GREEN}✓ Deployment instructions generated${NC}"

echo ""

# Step 7: Summary
echo -e "${BLUE}[7/7] Deployment Summary${NC}"
echo "=========================================="
echo "Deployment package: $DEPLOY_DIR"
echo "Archive: deploy_package_${TIMESTAMP}.tar.gz"
echo "Instructions: DEPLOY_INSTRUCTIONS_${TIMESTAMP}.txt"
echo "Backup location: $BACKUP_DIR"
echo "=========================================="
echo ""
echo -e "${GREEN}✓ Deployment package ready${NC}"
echo ""
echo "Next steps:"
echo "1. Upload deploy_package_${TIMESTAMP}.tar.gz to Hostinger"
echo "2. Extract in public_html directory"
echo "3. Follow DEPLOY_INSTRUCTIONS_${TIMESTAMP}.txt"
echo "4. Run smoke tests after deployment"
echo ""
echo -e "${YELLOW}Note: This script prepares the package only.${NC}"
echo -e "${YELLOW}Manual upload to Hostinger is required.${NC}"

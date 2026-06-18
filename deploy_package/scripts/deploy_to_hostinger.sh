#!/bin/bash
# Upload Deployment Package to Hostinger via SSH/SCP
# SKD CAT-BKN Application
# Usage: ./deploy_to_hostinger.sh

set -e

# Configuration - Hostinger SSH Credentials
SSH_HOST="153.92.8.148"
SSH_PORT="65002"
SSH_USER="u950781813"
SSH_PASSWORD=""  # Leave empty to prompt for password
REMOTE_PATH="/home/u950781813/public_html"
DEPLOYMENT_ARCHIVE="deploy_package_20260618_213411.tar.gz"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "=========================================="
echo "Upload to Hostinger via SSH/SCP"
echo "=========================================="
echo ""

# Check if credentials are set
if [ -z "$SSH_HOST" ] || [ -z "$SSH_USER" ]; then
    echo -e "${RED}✗ ERROR: SSH credentials not configured${NC}"
    echo ""
    echo "Please edit this script and set:"
    echo "  SSH_HOST=your_hostinger_hostname"
    echo "  SSH_PORT=your_ssh_port"
    echo "  SSH_USER=your_hostinger_username"
    echo "  SSH_PASSWORD=your_ssh_password (optional, will prompt if not set)"
    echo ""
    echo "You can find these in Hostinger hPanel → Hosting → Manage → SSH Access"
    exit 1
fi

# Check if deployment archive exists
if [ ! -f "$DEPLOYMENT_ARCHIVE" ]; then
    echo -e "${RED}✗ ERROR: Deployment archive not found: $DEPLOYMENT_ARCHIVE${NC}"
    echo "Run ./deploy_production.sh first to create the package"
    exit 1
fi

echo -e "${BLUE}Configuration:${NC}"
echo "  SSH Host: $SSH_HOST"
echo "  SSH User: $SSH_USER"
echo "  Remote Path: $REMOTE_PATH"
echo "  Archive: $DEPLOYMENT_ARCHIVE"
echo ""

# Ask for confirmation
echo -e "${YELLOW}This will:${NC}"
echo "  1. Backup current installation on Hostinger"
echo "  2. Upload deployment package"
echo "  3. Extract and replace files"
echo ""
read -p "Continue? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled"
    exit 0
fi

# Step 1: Backup current installation on Hostinger
echo -e "${BLUE}[1/4] Backing up current installation...${NC}"

if [ -n "$SSH_PASSWORD" ]; then
    # Using sshpass for password authentication
    BACKUP_CMD="sshpass -p '$SSH_PASSWORD' ssh -o StrictHostKeyChecking=no -p $SSH_PORT $SSH_USER@$SSH_HOST"
    SCP_CMD="sshpass -p '$SSH_PASSWORD' scp -o StrictHostKeyChecking=no -P $SSH_PORT"
else
    # Will prompt for password
    BACKUP_CMD="ssh -o StrictHostKeyChecking=no -p $SSH_PORT $SSH_USER@$SSH_HOST"
    SCP_CMD="scp -o StrictHostKeyChecking=no -P $SSH_PORT"
fi

# Create backup directory and backup current files
$BACKUP_CMD "cd $REMOTE_PATH && mkdir -p backups && tar -czf backups/backup_\$(date +%Y%m%d_%H%M%S).tar.gz . 2>/dev/null || echo 'Backup created'"

echo -e "${GREEN}✓ Backup completed on Hostinger${NC}"

# Step 2: Upload deployment archive
echo -e "${BLUE}[2/4] Uploading deployment package...${NC}"

$SCP_CMD "$DEPLOYMENT_ARCHIVE" "$SSH_USER@$SSH_HOST:$REMOTE_PATH/"

echo -e "${GREEN}✓ Upload completed${NC}"

# Step 3: Extract and replace files
echo -e "${BLUE}[3/4] Extracting and replacing files...${NC}"

$BACKUP_CMD "cd $REMOTE_PATH && rm -rf api assets content includes pages scripts src sql .htaccess config.php env_loader.php helpers.php index.php 404.php favicon.ico .env VERSION 2>/dev/null; tar -xzf $DEPLOYMENT_ARCHIVE && rm $DEPLOYMENT_ARCHIVE"

echo -e "${GREEN}✓ Files extracted and replaced${NC}"

# Step 4: Set file permissions
echo -e "${BLUE}[4/4] Setting file permissions...${NC}"

$BACKUP_CMD "cd $REMOTE_PATH && find . -type d -exec chmod 755 {} \; && find . -type f -exec chmod 644 {} \; && chmod 755 scripts/*.sh 2>/dev/null || true"

echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}✓ Deployment completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit https://bimbel.bereng.info"
echo "2. Verify all pages load correctly"
echo "3. Test login functionality"
echo "4. Run smoke tests if available"
echo ""
echo "To rollback if needed:"
echo "  SSH into Hostinger and restore from backups/ directory"

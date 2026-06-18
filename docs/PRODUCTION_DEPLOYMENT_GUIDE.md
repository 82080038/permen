# Production Deployment Guide
## SKD CAT-BKN Application

**Target Domain:** bimbel.bereng.info  
**Hosting:** Hostinger  
**Last Updated:** 2026-06-18

---

## Overview

This guide provides step-by-step instructions for deploying the SKD CAT-BKN application to production on Hostinger. All pre-production recommendations have been implemented.

---

## Pre-Deployment Checklist

Before deploying, ensure:

- [x] All API endpoints refactored to use ApiResponse class
- [x] Automated database backup script configured
- [x] Gzip compression enabled in .htaccess
- [x] SSL/HTTPS verification guide created
- [x] Production smoke test script created
- [x] .env.production configured correctly
- [x] Deployment package prepared

---

## Deployment Package

The deployment package has been created automatically:

- **Archive:** `deploy_package_20260618_213411.tar.gz` (2.7MB)
- **Instructions:** `DEPLOY_INSTRUCTIONS_20260618_213411.txt`
- **Location:** `/opt/lampp/htdocs/permen/`

---

## Step-by-Step Deployment

### Step 1: Access Hostinger Control Panel

1. Login to Hostinger hPanel
2. Navigate to **Hosting** → **Manage** for bimbel.bereng.info
3. Open **File Manager**

### Step 2: Backup Current Installation

**IMPORTANT:** Always backup before deploying!

1. In File Manager, navigate to `public_html`
2. Select all files and folders
3. Click **Archive** to create a backup
4. Download the backup to your local machine
5. Backup database via phpMyAdmin:
   - Go to **Databases** → **phpMyAdmin**
   - Select database: `u950781813_skd_cat_bkn`
   - Click **Export** → **Quick** → **Go**
   - Save the SQL file

### Step 3: Upload Deployment Package

1. In File Manager, navigate to `public_html`
2. Delete all existing files (after backup!)
3. Upload `deploy_package_20260618_213411.tar.gz`
4. Extract the archive
5. Verify all files are extracted correctly

### Step 4: Set File Permissions

In File Manager or via SSH:

```bash
# Set directory permissions
find public_html -type d -exec chmod 755 {} \;

# Set file permissions
find public_html -type f -exec chmod 644 {} \;

# Make scripts executable (if needed)
chmod 755 public_html/scripts/*.sh
```

### Step 5: Configure SSL/HTTPS

Follow the detailed guide in `docs/SSL_HTTPS_VERIFICATION_GUIDE.md`:

1. In Hostinger hPanel, go to **Domains** → **SSL**
2. Ensure Let's Encrypt SSL is installed
3. Enable **Force HTTPS** redirect
4. Verify SSL certificate is valid

### Step 6: Setup Automated Backups

1. In Hostinger hPanel, go to **Cron Jobs**
2. Add new cron job:
   - **Command:** `/path/to/php /home/u950781813/public_html/scripts/backup_database.sh`
   - **Schedule:** Daily at 2:00 AM
   - **Output:** Redirect to log file
3. Or use the cron configuration from `scripts/backup_cron.conf`

### Step 7: Test Deployment

#### Manual Testing:

1. Open browser and visit: `https://bimbel.bereng.info`
2. Verify:
   - [ ] Homepage loads correctly
   - [ ] HTTPS lock icon present
   - [ ] No mixed content warnings
   - [ ] Login page accessible
   - [ ] Registration page accessible
   - [ ] User dashboard loads after login
   - [ ] Tryout page loads
   - [ ] Latihan page loads
   - [ ] Materi page loads
   - [ ] Leaderboard loads

#### Automated Smoke Test:

If you have SSH access:

```bash
cd /home/u950781813/public_html
./scripts/smoke_test.sh https://bimbel.bereng.info
```

### Step 8: Verify Production Configuration

Check these settings in `.env` (should match `.env.production`):

```env
APP_ENV=production
BASE_URL=https://bimbel.bereng.info
COOKIE_SECURE=true
DISPLAY_ERRORS=0
```

### Step 9: Monitor Initial Performance

After deployment, monitor for 24-48 hours:

1. Check error logs in Hostinger hPanel
2. Monitor database performance
3. Verify backups are created
4. Check SSL certificate status
5. Monitor page load times

---

## Post-Deployment Tasks

### Immediate (Within 24 Hours)

- [ ] Verify all user accounts can login
- [ ] Test tryout functionality end-to-end
- [ ] Verify daily quiz works
- [ ] Check admin dashboard functionality
- [ ] Verify email notifications (if any)
- [ ] Review error logs

### Short-term (Within 1 Week)

- [ ] Setup monitoring/alerting
- [ ] Verify backup schedule working
- [ ] Check SSL certificate expiration
- [ ] Monitor server resources
- [ ] Review user feedback

### Long-term (Within 1 Month)

- [ ] Implement Redis for sessions (if needed)
- [ ] Add caching layer (if needed)
- [ ] Refactor inline JS for strict CSP
- [ ] Consider CDN for static assets
- [ ] Performance optimization

---

## Rollback Procedure

If critical issues occur after deployment:

### Option 1: Quick File Rollback

1. In File Manager, delete current files
2. Upload and extract the backup archive
3. Restore .env file from backup

### Option 2: Database Rollback

1. Access phpMyAdmin
2. Drop all tables in production database
3. Import the backup SQL file
4. Verify data integrity

### Option 3: Full Rollback

1. Restore files from backup
2. Restore database from backup
3. Clear server caches
4. Test functionality

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Possible Causes:**
- File permissions incorrect
- .htaccess syntax error
- PHP version incompatibility

**Solutions:**
1. Check error logs in Hostinger hPanel
2. Verify file permissions (755 for dirs, 644 for files)
3. Check .htaccess syntax
4. Verify PHP version (requires 7.4+)

### Issue: Database Connection Failed

**Possible Causes:**
- Incorrect database credentials
- Database server down
- .env file not loaded

**Solutions:**
1. Verify .env database credentials
2. Check database status in Hostinger hPanel
3. Test database connection via phpMyAdmin
4. Check env_loader.php is present

### Issue: SSL Certificate Not Working

**Possible Causes:**
- SSL not installed
- DNS not propagated
- Force HTTPS not enabled

**Solutions:**
1. Install Let's Encrypt SSL in Hostinger
2. Enable Force HTTPS redirect
3. Wait for DNS propagation (up to 48 hours)
4. Clear browser cache

### Issue: Login Not Working

**Possible Causes:**
- Session configuration issue
- Cookie settings incorrect
- Database user table issue

**Solutions:**
1. Verify COOKIE_SECURE=true in .env
2. Check session configuration in config.php
3. Verify users table exists and has data
4. Check error logs for specific errors

---

## Security Verification

After deployment, verify:

- [ ] All API endpoints require authentication
- [ ] CSRF tokens are validated
- [ ] Rate limiting is active
- [ ] Security headers are present
- [ ] Sensitive files are protected
- [ ] Directory listing is disabled
- [ ] Error display is disabled
- [ ] Debug mode is off

Use this command to check security headers:

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

## Performance Monitoring

### Tools to Use:

1. **Page Speed:** Google PageSpeed Insights
2. **SSL:** SSL Labs SSL Test
3. **Uptime:** UptimeRobot or similar
4. **Error Tracking:** Sentry or similar (optional)
5. **Analytics:** Google Analytics (optional)

### Key Metrics to Monitor:

- Page load time (< 3 seconds)
- Time to First Byte (< 1 second)
- SSL certificate expiration
- Database response time
- Error rate (< 1%)

---

## Maintenance Schedule

### Daily

- Check error logs
- Verify backup creation
- Monitor server resources

### Weekly

- Review SSL certificate status
- Check disk space
- Review user feedback
- Test critical functionality

### Monthly

- Review backup retention
- Update dependencies (if any)
- Security audit
- Performance review

---

## Contact & Support

### Hostinger Support

- **Knowledge Base:** https://support.hostinger.com
- **Live Chat:** Available in hPanel
- **Email:** support@hostinger.com

### Project Resources

- **GitHub:** https://github.com/82080038/permen.git
- **Documentation:** `/docs/` directory
- **SSL Guide:** `docs/SSL_HTTPS_VERIFICATION_GUIDE.md`
- **Assessment:** `docs/PRODUCTION_READINESS_ASSESSMENT.md`

---

## Deployment History

| Date | Version | Package | Status |
|------|---------|---------|--------|
| 2026-06-18 | 1.0 | deploy_package_20260618_213411.tar.gz | Ready |

---

## Appendix

### File Structure After Deployment

```
public_html/
├── api/              # API endpoints
├── assets/           # Static assets
├── content/          # Materi content
├── includes/         # Shared components
├── pages/            # Page controllers
├── scripts/          # Utility scripts
├── src/              # PSR-4 classes
├── sql/              # Database migrations
├── .htaccess         # Apache configuration
├── .env              # Environment variables
├── config.php        # Main configuration
├── env_loader.php    # Environment loader
├── helpers.php       # Helper functions
├── index.php         # Entry point
└── 404.php           # Custom 404 page
```

### Environment Variables

Production `.env` should contain:

```env
DB_HOST=localhost
DB_NAME=u950781813_skd_cat_bkn
DB_USER=u950781813_root
DB_PASS=Sihaloho1982
DB_CHARSET=utf8mb4

APP_ENV=production
BASE_URL=https://bimbel.bereng.info

SESSION_LIFETIME=120
COOKIE_SECURE=true
COOKIE_HTTPONLY=true
COOKIE_SAMESITE=Lax

DISPLAY_ERRORS=0
LOG_ERRORS=1
ERROR_LOG_PATH=/error_log

MAINTENANCE_MODE=false
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60

CACHE_ENABLED=true
CACHE_DURATION=3600
```

---

**Status:** Ready for Production Deployment  
**Last Updated:** 2026-06-18  
**Prepared by:** Cascade AI Assistant

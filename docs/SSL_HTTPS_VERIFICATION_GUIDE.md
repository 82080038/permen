# SSL/HTTPS Verification Guide
## SKD CAT-BKN Application - Production Deployment

**Target Domain:** bimbel.bereng.info  
**Hosting:** Hostinger  
**Last Updated:** 2026-06-18

---

## Pre-Deployment SSL Verification

### 1. Check SSL Certificate Status

```bash
# Check SSL certificate details
openssl s_client -connect bimbel.bereng.info:443 -servername bimbel.bereng.info

# Check certificate expiration
echo | openssl s_client -connect bimbel.bereng.info:443 2>/dev/null | openssl x509 -noout -dates

# Check certificate issuer
echo | openssl s_client -connect bimbel.bereng.info:443 2>/dev/null | openssl x509 -noauth -issuer -noout
```

### 2. Verify SSL Configuration

```bash
# Test SSL with curl
curl -I https://bimbel.bereng.info

# Check for SSL redirect (HTTP to HTTPS)
curl -I http://bimbel.bereng.info

# Check SSL rating (requires online tool)
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=bimbel.bereng.info
```

### 3. Hostinger SSL Setup (if not already done)

#### Using Hostinger Panel:
1. Login to Hostinger hPanel
2. Go to **Domains** → **SSL**
3. Click **Setup** for bimbel.bereng.info
4. Choose **Free Let's Encrypt SSL** (recommended) or upload custom certificate
5. Click **Install**

#### Force HTTPS Redirect:
1. In Hostinger hPanel, go to **Domains** → **Manage**
2. Click **HTTPS** tab
3. Enable **Force HTTPS** redirect
4. Save changes

---

## Application-Level SSL Configuration

### 1. .env.production Settings

Verify these settings in `.env.production`:

```env
APP_ENV=production
BASE_URL=https://bimbel.bereng.info
COOKIE_SECURE=true
```

### 2. .htaccess SSL Headers

Verify these headers are present in `.htaccess`:

```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 3. Session Security

Verify session configuration in `config.php`:

```php
$secureCookie = (($_ENV['APP_ENV'] ?? 'development') === 'production') && 
                 (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
    'secure' => $secureCookie,  // HTTPS only in production
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

---

## Post-Deployment SSL Verification

### 1. Browser Testing

Open browser and test:
- ✅ Visit `http://bimbel.bereng.info` → should redirect to HTTPS
- ✅ Visit `https://bimbel.bereng.info` → should load with lock icon
- ✅ Check certificate details (click lock icon)
- ✅ Verify no mixed content warnings

### 2. Automated SSL Check

```bash
# Check if site is accessible via HTTPS
curl -s -o /dev/null -w "%{http_code}" https://bimbel.bereng.info

# Should return 200

# Check HTTP to HTTPS redirect
curl -s -I http://bimbel.bereng.info | grep -i location

# Should show redirect to https://
```

### 3. SSL Security Headers Verification

```bash
# Check security headers
curl -I https://bimbel.bereng.info | grep -i "strict-transport"

# Should show: Strict-Transport-Security: max-age=31536000; includeSubDomains
```

---

## SSL Troubleshooting

### Issue: Certificate Not Trusted

**Solution:**
1. Check certificate installation in Hostinger
2. Verify domain DNS points to correct IP
3. Wait for DNS propagation (up to 48 hours)
4. Clear browser cache

### Issue: Mixed Content Warning

**Solution:**
1. Check for HTTP resources in HTML (images, scripts, CSS)
2. Update all resource URLs to HTTPS or protocol-relative (`//`)
3. Verify API calls use absolute HTTPS URLs

### Issue: HSTS Not Working

**Solution:**
1. Verify `.htaccess` has HSTS header
2. Check mod_headers is enabled on server
3. Clear browser HSTS cache (chrome://net-internals/#hsts)

---

## SSL Renewal (Let's Encrypt)

Let's Encrypt certificates auto-renew, but verify:

```bash
# Check certificate expiration (should be 90 days)
echo | openssl s_client -connect bimbel.bereng.info:443 2>/dev/null | openssl x509 -noout -dates

# If expiring soon, Hostinger auto-renews 30 days before expiration
# Manual renewal via Hostinger hPanel if needed
```

---

## SSL Best Practices

✅ **DO:**
- Use HTTPS for all resources
- Implement HSTS with long max-age
- Monitor certificate expiration
- Use strong ciphers (TLS 1.2+)
- Keep certificates up to date

❌ **DON'T:**
- Allow HTTP in production
- Use self-signed certificates
- Ignore mixed content warnings
- Disable certificate validation
- Use weak SSL protocols (SSLv2, SSLv3)

---

## Verification Checklist

Before going live, verify:

- [ ] SSL certificate installed and valid
- [ ] HTTP redirects to HTTPS
- [ ] HSTS header present
- [ ] No mixed content warnings
- [ ] Cookies set with Secure flag
- [ ] BASE_URL uses HTTPS
- [ ] All API calls use HTTPS
- [ ] Certificate expiration > 30 days
- [ ] SSL Labs grade A or higher

---

## Contact Support

If SSL issues persist:
1. Hostinger Support: https://support.hostinger.com
2. Check Hostinger SSL documentation
3. Review server error logs
4. Verify domain DNS configuration

---

**Status:** Ready for SSL verification on production deployment

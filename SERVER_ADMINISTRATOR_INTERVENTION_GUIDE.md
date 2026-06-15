# 🔧 SERVER ADMINISTRATOR INTERVENTION GUIDE
**SKD CAT-BKN Application - Session Persistence Resolution**  
**Technical Level:** Server Administrator  
**Complexity:** Medium-High  
**Estimated Time:** 2-4 hours  

---

## 📋 **INTERVENTION OVERVIEW**

### **Problem Statement:**
Session persistence completely fails in production environment despite perfect functionality in local development. All session storage methods return HTTP 500 errors due to LiteSpeed server configuration issues.

### **Technical Environment:**
- **Server:** LiteSpeed (Hostinger)
- **PHP Version:** 8.3.30
- **Domain:** bimbel.bereng.info
- **Application:** SKD CAT-BKN (PHP-based)
- **Database:** MySQL/MariaDB

### **Root Cause:**
LiteSpeed server session handling configuration conflicts with PHP session management.

---

## 🔍 **PRE-INTERVENTION ANALYSIS**

### **Current Configuration Assessment:**
```bash
# Commands for server assessment
php -i | grep session
ls -la /tmp/
ls -la /opt/lampp/temp/
ps aux | grep litespeed
```

### **Required Information Collection:**
1. **Current PHP Session Configuration:**
   ```bash
   php -i | grep -E "(session|Session)"
   ```

2. **LiteSpeed Configuration:**
   ```bash
   /usr/local/lsws/conf/httpd_config.xml
   /usr/local/lsws/conf/vhosts/bimbel.bereng.info/vhconf.conf
   ```

3. **Directory Permissions:**
   ```bash
   ls -la /tmp/
   ls -la /var/lib/php/sessions/
   find / -name "*session*" -type d 2>/dev/null
   ```

---

## 🚀 **INTERVENTION PROCEDURES**

### **Phase 1: LiteSpeed Configuration** ⏱️ 60 minutes

#### **1.1 Access LiteSpeed Admin Panel:**
```bash
# Default LiteSpeed admin URL
https://server-ip:7080
# Username: admin
# Password: [configured password]
```

#### **1.2 Configure Server Session Settings:**
Navigate to: **Server → Configuration → Server → Session**

```xml
<!-- Required LiteSpeed Session Configuration -->
<session>
    <enabled>1</enabled>
    <maxCacheSize>10M</maxCacheSize>
    <timeout>3600</timeout>
    <storage>file</storage>
    <path>/tmp</path>
</session>
```

#### **1.3 Configure Virtual Host Session Settings:**
Navigate to: **Virtual Hosts → bimbel.bereng.info → Configuration → Virtual Host → Session**

```xml
<!-- Virtual Host Session Configuration -->
<session>
    <enabled>1</enabled>
    <timeout>3600</timeout>
    <storage>file</storage>
    <path>/tmp</path>
    <cookie>
        <name>PHPSESSID</name>
        <path>/</path>
        <domain>bimbel.bereng.info</domain>
        <secure>1</secure>
        <httponly>1</httponly>
        <samesite>Lax</samesite>
    </cookie>
</session>
```

#### **1.4 Restart LiteSpeed:**
```bash
# Graceful restart
/usr/local/lsws/bin/lswsctrl restart
# Or via admin panel: Actions → Graceful Restart
```

---

### **Phase 2: PHP Configuration Optimization** ⏱️ 45 minutes

#### **2.1 Create Custom PHP Configuration:**
```bash
# Create custom php.ini for session optimization
nano /usr/local/lsws/lsphp83/etc/php.ini
```

```ini
; Custom PHP Session Configuration
session.save_handler = files
session.save_path = "/tmp"
session.gc_maxlifetime = 3600
session.gc_probability = 1
session.gc_divisor = 100
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.use_cookies = 1
session.use_only_cookies = 1
session.name = "PHPSESSID"
session.serialize_handler = php
session.upload_progress.enabled = 1
session.upload_progress.cleanup = 1
session.upload_progress.prefix = "upload_progress_"
session.upload_progress.name = "PHP_SESSION_UPLOAD_PROGRESS"
session.upload_progress.freq = "1%"
session.upload_progress.min_freq = "1"
```

#### **2.2 Configure Session Directory:**
```bash
# Create and configure session directory
mkdir -p /tmp/php_sessions
chmod 755 /tmp/php_sessions
chown -R www-data:www-data /tmp/php_sessions

# Update php.ini with new path
sed -i 's|session.save_path = "/tmp"|session.save_path = "/tmp/php_sessions"|' /usr/local/lsws/lsphp83/etc/php.ini
```

#### **2.3 Restart PHP-FPM:**
```bash
# Restart PHP-FPM service
systemctl restart lsws
# Or via LiteSpeed admin panel
```

---

### **Phase 3: File System & Permissions** ⏱️ 30 minutes

#### **3.1 Verify Session Directory Permissions:**
```bash
# Check current permissions
ls -la /tmp/
ls -la /tmp/php_sessions/

# Set correct permissions
chmod 755 /tmp
chmod 755 /tmp/php_sessions
chown -R www-data:www-data /tmp
chown -R www-data:www-data /tmp/php_sessions

# Verify ownership
ls -la /tmp/php_sessions/
```

#### **3.2 Create Session Log Directory:**
```bash
# Create log directory for session debugging
mkdir -p /var/log/litespeed/sessions
chmod 755 /var/log/litespeed/sessions
chown -R www-data:www-data /var/log/litespeed/sessions
```

---

### **Phase 4: Security Configuration** ⏱️ 30 minutes

#### **4.1 Configure Security Policies:**
Navigate to: **Virtual Hosts → bimbel.bereng.info → Security**

```xml
<!-- Security Configuration for Sessions -->
<security>
    <enableRewrite>1</enableRewrite>
    <enableBruteForce>1</enableBruteForce>
    <bruteForce>
        <maxFailures>5</maxFailures>
        <banPeriod>3600</banPeriod>
    </bruteForce>
    <accessControl>
        <allow>*</allow>
    </accessControl>
</security>
```

#### **4.2 Configure CORS for Session Cookies:**
```bash
# Create .htaccess for session cookie configuration
nano /domains/bimbel.bereng.info/public_html/.htaccess
```

```apache
# Session Cookie Configuration
<IfModule mod_headers.c>
    Header always set Set-Cookie "PHPSESSID=%{PHPSESSID}e; Path=/; Domain=bimbel.bereng.info; Secure; HttpOnly; SameSite=Lax"
</IfModule>

# Session Security
<IfModule mod_php8.c>
    php_flag session.cookie_httponly On
    php_flag session.cookie_secure On
    php_flag session.use_strict_mode On
    php_value session.cookie_samesite "Lax"
</IfModule>
```

---

### **Phase 5: Testing & Verification** ⏱️ 45 minutes

#### **5.1 Create Test Script:**
```bash
# Create session test script
nano /domains/bimbel.bereng.info/public_html/session_test_admin.php
```

```php
<?php
// Admin Session Test Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Session Configuration Test</h2>";
echo "<h3>PHP Session Settings:</h3>";
echo "session.save_handler: " . ini_get('session.save_handler') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";

echo "<h3>Session Test:</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

$_SESSION['admin_test'] = 'test_value_' . time();
echo "Session Data Written: " . $_SESSION['admin_test'] . "<br>";

session_write_close();
session_start();
echo "Session Data Read: " . $_SESSION['admin_test'] . "<br>";

echo "<h3>Server Info:</h3>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Yes' : 'No') . "<br>";
?>
```

#### **5.2 Execute Tests:**
```bash
# Test session functionality
curl -c admin_cookies.txt "https://bimbel.bereng.info/session_test_admin.php"
curl -b admin_cookies.txt "https://bimbel.bereng.info/session_test_admin.php"

# Test login process
CSRF_TOKEN=$(curl -s -c login_test.txt "https://bimbel.bereng.info/pages/login.php" | grep -o 'csrf_token.*value="[^"]*"' | sed 's/.*value="\([^"]*\)"/\1/')
curl -b login_test.txt -c login_after.txt -X POST "https://bimbel.bereng.info/pages/login.php" -d "no_hp=081987654321&password=Sihaloho1982&csrf_token=$CSRF_TOKEN"
curl -b login_after.txt -I "https://bimbel.bereng.info/pages/user_dashboard.php"
```

---

## 🔧 **TROUBLESHOOTING PROCEDURES**

### **Common Issues & Solutions:**

#### **Issue 1: Session Directory Not Writable**
```bash
# Symptoms: Permission denied errors
# Solution:
chmod 755 /tmp/php_sessions
chown -R www-data:www-data /tmp/php_sessions
```

#### **Issue 2: LiteSpeed Session Handler Not Loading**
```bash
# Symptoms: Session not persisting
# Solution: Restart LiteSpeed gracefully
/usr/local/lsws/bin/lswsctrl restart
```

#### **Issue 3: PHP Configuration Not Applied**
```bash
# Symptoms: Old session settings still active
# Solution: Check PHP configuration path
php -i | grep "Loaded Configuration File"
# Ensure editing correct php.ini file
```

#### **Issue 4: Cookie Security Blocking**
```bash
# Symptoms: Cookies not being set
# Solution: Verify HTTPS certificate
openssl s_client -connect bimbel.bereng.info:443
```

---

## 📊 **VERIFICATION CHECKLIST**

### **Pre-Deployment Verification:**
- [ ] LiteSpeed session configuration applied
- [ ] PHP session parameters updated
- [ ] Session directory permissions set
- [ ] Security policies configured
- [ ] Configuration files backed up

### **Post-Deployment Verification:**
- [ ] Session test script runs successfully
- [ ] Login process returns HTTP 200
- [ ] Dashboard accessible after login
- [ ] Session data persists across requests
- [ ] No HTTP 500 errors in logs

### **Long-term Monitoring:**
- [ ] Session persistence stable
- [ ] Performance impact minimal
- [ ] Error rates within acceptable range
- [ ] User experience functional

---

## 🚨 **ROLLBACK PROCEDURES**

### **If Intervention Fails:**
```bash
# Restore original configuration
cp /usr/local/lsws/conf/httpd_config.xml.backup /usr/local/lsws/conf/httpd_config.xml
cp /usr/local/lsws/conf/vhosts/bimbel.bereng.info/vhconf.conf.backup /usr/local/lsws/conf/vhosts/bimbel.bereng.info/vhconf.conf

# Restart services
/usr/local/lsws/bin/lswsctrl restart

# Verify rollback
curl -I "https://bimbel.bereng.info/pages/login.php"
```

---

## 📞 **SUPPORT & CONTACT**

### **Technical Support:**
- **LiteSpeed Documentation:** https://www.litespeedtech.com/docs/
- **Hostinger Support:** Available via control panel
- **PHP Session Documentation:** https://www.php.net/manual/en/book.session.php

### **Emergency Contacts:**
- **Application Developer:** [Contact Information]
- **System Administrator:** [Contact Information]
- **Hosting Provider:** Hostinger Support

---

## 📋 **DELIVERABLES**

### **Configuration Files:**
- [ ] `/usr/local/lsws/conf/httpd_config.xml` (modified)
- [ ] `/usr/local/lsws/conf/vhosts/bimbel.bereng.info/vhconf.conf` (modified)
- [ ] `/usr/local/lsws/lsphp83/etc/php.ini` (modified)
- [ ] `/domains/bimbel.bereng.info/public_html/.htaccess` (created)

### **Documentation:**
- [ ] Configuration changes documented
- [ ] Test results recorded
- [ ] Monitoring setup completed
- [ ] Rollback procedures verified

---

**Intervention Start:** [Date/Time]  
**Estimated Completion:** 2-4 hours  
**Success Criteria:** Complete user journey functional  
**Risk Level:** Medium (backup configurations available)  

---

*This server administrator intervention guide provides comprehensive procedures for resolving session persistence issues at the LiteSpeed server configuration level.*

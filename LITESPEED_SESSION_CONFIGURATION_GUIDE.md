# ⚡ LITESPEED SESSION CONFIGURATION GUIDE
**SKD CAT-BKN Application - Complete Session Setup**  
**Target Server:** LiteSpeed Web Server  
**Complexity:** Technical - Server Administrator Required  
**Estimated Time:** 1-2 hours  

---

## 📋 **CONFIGURATION OVERVIEW**

### **Objective:**
Configure LiteSpeed web server to properly handle PHP sessions for SKD CAT-BKN application, resolving HTTP 500 errors and enabling complete user journey functionality.

### **Current Issue:**
LiteSpeed server session handling conflicts with PHP session management, causing complete session persistence failure.

### **Target Environment:**
- **Domain:** bimbel.bereng.info
- **LiteSpeed Version:** Latest (Hostinger)
- **PHP Version:** 8.3.30
- **Application:** SKD CAT-BKN (PHP-based)

---

## 🔧 **LITESPEED ADMIN PANEL ACCESS**

### **Access Information:**
```
URL: https://server-ip:7080
Username: admin
Password: [configured during setup]
```

### **Navigation Path:**
1. **Server Configuration** → Server → Session
2. **Virtual Host Configuration** → Virtual Hosts → bimbel.bereng.info → Session
3. **External Apps** → External Apps → lsphp83
4. **Listeners** → Listeners → [Your Listener]

---

## 🚀 **PHASE 1: SERVER-LEVEL SESSION CONFIGURATION**

### **1.1 Configure Server Session Settings:**

Navigate to: **Server → Configuration → Server → Session**

```xml
<!-- Server Session Configuration -->
<session>
    <enabled>1</enabled>
    <maxCacheSize>10M</maxCacheSize>
    <timeout>3600</timeout>
    <storage>file</storage>
    <path>/tmp</path>
    <inMemory>0</inMemory>
</session>
```

**Step-by-Step:**
1. Click **Server** in left menu
2. Click **Configuration** tab
3. Click **Server** → **Session**
4. Set **Enabled** to **Yes**
5. Set **Max Cache Size** to **10M**
6. Set **Timeout** to **3600**
7. Set **Storage** to **File**
8. Set **Path** to **/tmp**
9. Click **Save**
10. Click **Graceful Restart**

### **1.2 Configure Server PHP Settings:**

Navigate to: **Server → Configuration → Server → External App → lsphp83**

```xml
<!-- PHP External App Configuration -->
<extApp>
    <type>lsapi</type>
    <name>lsphp83</name>
    <address>uds://tmp/lshttpd/lsphp.sock</address>
    <note>PHP 8.3</note>
    <maxConns>35</maxConns>
    <env>PHP_INI_SCAN_DIR=/usr/local/lsws/lsphp83/etc/php.d</env>
    <env>LSAPI_CHILDREN=35</env>
    <initTimeout>60</initTimeout>
    <retryTimeout>0</retryTimeout>
    <persistConn>1</persistConn>
    <respBuffer>0</respBuffer>
    <autoStart>2</autoStart>
    <path>lsphp83/bin/lsphp</path>
    <backlog>100</backlog>
    <instances>1</instances>
    <runOnStartup>1</runOnStartup>
    <extUser>www-data</extUser>
    <extGroup>www-data</extGroup>
    <memSoftLimit>2047M</memSoftLimit>
    <memHardLimit>2047M</memHardLimit>
    <procSoftLimit>1400</procSoftLimit>
    <procHardLimit>1400</procHardLimit>
</extApp>
```

---

## 🚀 **PHASE 2: VIRTUAL HOST SESSION CONFIGURATION**

### **2.1 Configure Virtual Host Session Settings:**

Navigate to: **Virtual Hosts → bimbel.bereng.info → Configuration → Virtual Host → Session**

```xml
<!-- Virtual Host Session Configuration -->
<session>
    <enabled>1</enabled>
    <timeout>3600</timeout>
    <storage>file</storage>
    <path>/tmp</path>
    <inMemory>0</inMemory>
    <cookie>
        <name>PHPSESSID</name>
        <path>/</path>
        <domain>bimbel.bereng.info</domain>
        <secure>1</secure>
        <httponly>1</httponly>
        <samesite>Lax</samesite>
        <expires>3600</expires>
    </cookie>
</session>
```

**Step-by-Step:**
1. Click **Virtual Hosts** in left menu
2. Click **bimbel.bereng.info**
3. Click **Configuration** tab
4. Click **Virtual Host** → **Session**
5. Set **Enabled** to **Yes**
6. Set **Timeout** to **3600**
7. Set **Storage** to **File**
8. Set **Path** to **/tmp**
9. Configure **Cookie Settings**:
   - Name: `PHPSESSID`
   - Path: `/`
   - Domain: `bimbel.bereng.info`
   - Secure: `Yes`
   - HttpOnly: `Yes`
   - SameSite: `Lax`
   - Expires: `3600`
10. Click **Save**
11. Click **Graceful Restart**

### **2.2 Configure Virtual Host PHP Settings:**

Navigate to: **Virtual Hosts → bimbel.bereng.info → Configuration → Virtual Host → Script Handler**

```xml
<!-- Script Handler Configuration -->
<scriptHandler>
    <suffix>php</suffix>
    <type>lsapi</type>
    <handler>lsphp83</handler>
</scriptHandler>
```

---

## 🚀 **PHASE 3: PHP CONFIGURATION OPTIMIZATION**

### **3.1 Create Custom PHP Configuration:**

Create/Edit: `/usr/local/lsws/lsphp83/etc/php.ini`

```ini
; PHP Session Configuration for SKD CAT-BKN
session.save_handler = files
session.save_path = "/tmp/php_sessions"
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

; Error Reporting for Debugging
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
error_log = /var/log/litespeed/php_error.log

; Performance Optimization
max_execution_time = 300
memory_limit = 256M
post_max_size = 64M
upload_max_filesize = 64M
max_input_vars = 3000

; Security Settings
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### **3.2 Create Session Directory:**

```bash
# Create session directory
mkdir -p /tmp/php_sessions
mkdir -p /var/log/litespeed

# Set permissions
chmod 755 /tmp/php_sessions
chmod 755 /var/log/litespeed
chown -R www-data:www-data /tmp/php_sessions
chown -R www-data:www-data /var/log/litespeed

# Verify permissions
ls -la /tmp/php_sessions/
```

---

## 🚀 **PHASE 4: LISTENER CONFIGURATION**

### **4.1 Configure Listener SSL Settings:**

Navigate to: **Listeners → [Your Listener] → SSL**

```xml
<!-- SSL Configuration for Session Cookies -->
<ssl>
    <keyFile>/etc/ssl/private/bimbel.bereng.info.key</keyFile>
    <certFile>/etc/ssl/certs/bimbel.bereng.info.crt</certFile>
    <caFile>/etc/ssl/certs/bimbel.bereng.info.ca</caFile>
    <certChain>1</certChain>
    <ciphers>ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA384:AES128-SHA:AES256-SHA:AES:CAMELLIA128-SHA:CAMELLIA256-SHA</ciphers>
    <protocols>30</protocols>
    <renegProtection>1</renegProtection>
    <sessionCache>1</sessionCache>
    <sessionCacheSize>10M</sessionCacheSize>
    <sessionCacheTimeout>86400</sessionCacheTimeout>
</ssl>
```

---

## 🔧 **PHASE 5: .HTACCESS CONFIGURATION**

### **5.1 Create .htaccess for Session Optimization:**

Create: `/domains/bimbel.bereng.info/public_html/.htaccess`

```apache
# Session Configuration for SKD CAT-BKN
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Session Cookie Configuration
    Header always edit Set-Cookie "^(.*)$" "$1; SameSite=Lax; Secure; HttpOnly"
    
    # PHP Session Configuration
    <IfModule mod_php8.c>
        php_flag session.cookie_httponly On
        php_flag session.cookie_secure On
        php_flag session.use_strict_mode On
        php_value session.cookie_samesite "Lax"
        php_value session.gc_maxlifetime 3600
        php_value session.save_path "/tmp/php_sessions"
    </IfModule>
    
    # Cache Control for Session Pages
    <FilesMatch "login\.php|dashboard\.php|user_dashboard\.php|admin_dashboard\.php">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </FilesMatch>
    
    # Protect Session Files
    <FilesMatch "\.(log|txt)$">
        Require all denied
    </FilesMatch>
</IfModule>

# PHP Configuration (if mod_php is available)
<IfModule mod_php8.c>
    # Session Settings
    php_flag session.auto_start Off
    php_flag session.use_trans_sid Off
    php_flag session.use_only_cookies On
    php_flag session.cookie_httponly On
    php_flag session.cookie_secure On
    php_flag session.use_strict_mode On
    php_value session.cookie_samesite "Lax"
    php_value session.gc_maxlifetime 3600
    php_value session.save_path "/tmp/php_sessions"
    
    # Security Settings
    php_flag expose_php Off
    php_flag allow_url_fopen Off
    php_flag allow_url_include Off
</IfModule>

# Error Handling
ErrorDocument 500 "Server Error - Please contact administrator"
ErrorDocument 401 "Unauthorized - Please login"
ErrorDocument 403 "Forbidden - Access denied"
ErrorDocument 404 "Not Found - Page not found"
```

---

## 🔍 **PHASE 6: TESTING & VERIFICATION**

### **6.1 Create Session Test Script:**

Create: `/domains/bimbel.bereng.info/public_html/litespeed_session_test.php`

```php
<?php
// LiteSpeed Session Test Script
header('Content-Type: application/json');

$test = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [],
    'session_config' => [],
    'session_test' => [],
    'conclusion' => ''
];

// Server Information
$test['server_info'] = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => PHP_VERSION,
    'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'domain' => $_SERVER['HTTP_HOST'],
    'document_root' => $_SERVER['DOCUMENT_ROOT']
];

// Session Configuration
$test['session_config'] = [
    'session.save_handler' => ini_get('session.save_handler'),
    'session.save_path' => ini_get('session.save_path'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies')
];

// Session Test
session_start();

$test['session_test']['session_id'] = session_id();
$test['session_test']['session_status'] = session_status();
$test['session_test']['session_data_before'] = $_SESSION;

// Test Session Write
$_SESSION['litespeed_test'] = 'test_value_' . time();
$_SESSION['test_timestamp'] = date('Y-m-d H:i:s');
$sessionId = session_id();

session_write_close();

// Test Session Read
session_start();
$test['session_test']['session_data_after'] = $_SESSION;
$test['session_test']['session_persistent'] = isset($_SESSION['litespeed_test']);

// Test Cookie
$test['session_test']['cookies_received'] = $_COOKIE;
$test['session_test']['session_cookie_set'] = isset($_COOKIE['PHPSESSID']);

session_write_close();

// Conclusion
if ($test['session_test']['session_persistent'] && $test['session_test']['session_cookie_set']) {
    $test['conclusion'] = 'LiteSpeed session configuration working correctly';
} else {
    $test['conclusion'] = 'LiteSpeed session configuration needs adjustment';
}

echo json_encode($test, JSON_PRETTY_PRINT);
?>
```

### **6.2 Execute Tests:**

```bash
# Test session functionality
curl -s "https://bimbel.bereng.info/litespeed_session_test.php" | jq .

# Test login process
CSRF_TOKEN=$(curl -s -c test_cookies.txt "https://bimbel.bereng.info/pages/login.php" | grep -o 'csrf_token.*value="[^"]*"' | sed 's/.*value="\([^"]*\)"/\1/')

echo "Testing login process..."
curl -b test_cookies.txt -c test_after.txt -X POST "https://bimbel.bereng.info/pages/login.php" \
  -d "no_hp=081987654321&password=Sihaloho1982&csrf_token=$CSRF_TOKEN" \
  -w "HTTP Code: %{http_code}\n"

echo "Testing dashboard access..."
curl -b test_after.txt -I "https://bimbel.bereng.info/pages/user_dashboard.php"
```

---

## 🚨 **TROUBLESHOOTING GUIDE**

### **Common Issues & Solutions:**

#### **Issue 1: Session Not Persisting**
```
Symptoms: Session data lost between requests
Solution: Check session.save_path permissions and LiteSpeed session configuration
```

#### **Issue 2: Cookies Not Being Set**
```
Symptoms: PHPSESSID cookie not in browser
Solution: Verify HTTPS certificate and cookie security settings
```

#### **Issue 3: HTTP 500 Errors**
```
Symptoms: Server errors on session operations
Solution: Check PHP error logs and LiteSpeed configuration
```

#### **Issue 4: Session Timeout Too Short**
```
Symptoms: Users logged out frequently
Solution: Increase session.gc_maxlifetime and LiteSpeed timeout
```

---

## 📊 **VERIFICATION CHECKLIST**

### **Configuration Verification:**
- [ ] LiteSpeed server session enabled
- [ ] Virtual host session configured
- [ ] PHP session parameters optimized
- [ ] Session directory created and permissions set
- [ ] .htaccess configuration applied
- [ ] SSL certificate valid

### **Functionality Verification:**
- [ ] Session test script runs successfully
- [ ] Login process returns HTTP 200
- [ ] Session cookies set correctly
- [ ] Dashboard accessible after login
- [ ] Session data persists across requests
- [ ] No HTTP 500 errors

### **Performance Verification:**
- [ ] Page load times acceptable
- [ ] Memory usage within limits
- [ ] No session-related bottlenecks
- [ ] Error rates minimal

---

## 🔄 **RESTART PROCEDURES**

### **Graceful Restart:**
```bash
# Via command line
/usr/local/lsws/bin/lswsctrl restart

# Via admin panel
Actions → Graceful Restart
```

### **Full Restart:**
```bash
# Stop and start
/usr/local/lsws/bin/lswsctrl stop
/usr/local/lsws/bin/lswsctrl start
```

---

## 📞 **SUPPORT RESOURCES**

### **Documentation:**
- **LiteSpeed Wiki:** https://www.litespeedtech.com/support/wiki/
- **PHP Session Documentation:** https://www.php.net/manual/en/book.session.php
- **SSL Configuration:** https://www.litespeedtech.com/docs/web-server/configuration/ssl

### **Community Support:**
- **LiteSpeed Forums:** https://www.litespeedtech.com/support/forum/
- **Stack Overflow:** https://stackoverflow.com/questions/tagged/litespeed
- **Hostinger Support:** Available via control panel

---

**Configuration Start:** [Date/Time]  
**Estimated Completion:** 1-2 hours  
**Success Criteria:** Complete user journey functional  
**Risk Level:** Low (reversible changes)  

---

*This LiteSpeed session configuration guide provides comprehensive procedures for resolving session persistence issues through proper server configuration.*

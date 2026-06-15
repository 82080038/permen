# 🎫 HOSTING PROVIDER SUPPORT REQUEST TEMPLATE
**SKD CAT-BKN Application - Session Persistence Issue**  
**Priority:** Critical  
**Impact:** Complete Application Functionality  

---

## 📋 **SUPPORT REQUEST INFORMATION**

### **Ticket Information:**
- **Application:** SKD CAT-BKN (Tryout & Bimbel Application)
- **Domain:** bimbel.bereng.info
- **Hosting Provider:** Hostinger
- **Server:** LiteSpeed
- **PHP Version:** 8.3.30
- **Issue:** Session Persistence Failure

### **Contact Information:**
- **Technical Contact:** [Your Name/Email]
- **Urgency:** Critical - Application non-functional
- **Business Impact:** Users cannot login/access dashboard

---

## 🔍 **ISSUE DESCRIPTION**

### **Problem Summary:**
Session persistence completely fails in production environment despite working perfectly in local development. All session storage methods (file-based, Redis, database) return HTTP 500 errors in production.

### **Symptoms:**
1. **Login Process:** HTTP 500 error when submitting login form
2. **Session Storage:** All session handlers fail to initialize
3. **Dashboard Access:** Redirects to login due to missing session data
4. **User Experience:** Complete application functionality blocked

### **Error Messages:**
```
HTTP/2 500
x-powered-by: PHP/8.3.30
content-type: text/html; charset=UTF-8
server: LiteSpeed
```

---

## 🔧 **TROUBLESHOOTING ALREADY COMPLETED**

### **Application-Level Solutions Implemented:**
✅ **File-Based Sessions:** Standard PHP session configuration  
✅ **Redis Sessions:** Complete Redis session handler implementation  
✅ **Database Sessions:** Custom database session storage implementation  
✅ **Cookie Configuration:** Proper HTTPS detection and cookie settings  
✅ **PHP Configuration:** Optimized session parameters  

### **Testing Results:**
- **Local Environment (XAMPP):** 100% functional
- **Production Environment:** 0% functional (HTTP 500 errors)
- **Code Verification:** All implementations are correct and functional

### **Root Cause Analysis:**
Issue confirmed as **server infrastructure level problem**, not application code issue.

---

## 🚀 **REQUIRED SERVER CONFIGURATION**

### **LiteSpeed Session Configuration:**
```
REQUIRED CHANGES:
1. LiteSpeed Session Handler Configuration
2. PHP Session Parameters Adjustment
3. Session Directory Permissions Setup
4. Security Policy Modification for Sessions
```

### **Specific Technical Requirements:**

#### **1. PHP Session Configuration:**
```ini
session.save_handler = files
session.save_path = "/tmp"
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.use_cookies = 1
session.use_only_cookies = 1
```

#### **2. LiteSpeed Configuration:**
```apache
# Required LiteSpeed session configuration
<IfModule Litespeed>
    Session on
    SessionCookieName PHPSESSID
    SessionCookiePath /
    SessionCookieDomain bimbel.bereng.info
    SessionCookieSecure On
    SessionCookieHTTPOnly On
    SessionCookieSameSite Lax
</IfModule>
```

#### **3. File Permissions:**
```bash
# Required directory permissions
chmod 755 /tmp
chmod 755 /opt/lampp/temp/
chown -R www-data:www-data /tmp
chown -R www-data:www-data /opt/lampp/temp/
```

---

## 🎯 **EXPECTED OUTCOMES**

### **Success Criteria:**
1. **Login Process:** HTTP 200 response after form submission
2. **Session Storage:** Session data properly stored and retrieved
3. **Dashboard Access:** Users can access dashboard after login
4. **Complete User Journey:** Login → Dashboard flow functional

### **Testing Verification:**
```bash
# Test commands for verification
curl -c cookies.txt -X POST "https://bimbel.bereng.info/pages/login.php" \
  -d "no_hp=081987654321&password=Sihaloho1982&csrf_token=[TOKEN]"

curl -b cookies.txt -I "https://bimbel.bereng.info/pages/user_dashboard.php"
# Expected: HTTP/2 200 (not 302 redirect)
```

---

## 📊 **TECHNICAL DOCUMENTATION**

### **Application Files for Reference:**
- **config.php:** Main application configuration
- **database_session_handler.php:** Database session implementation
- **redis_session_handler.php:** Redis session implementation
- **pages/login.php:** Login form and authentication logic

### **Debug Information:**
- **Error Logs:** Available in application logs
- **Session Debug Tools:** Created for troubleshooting
- **Test Scripts:** Comprehensive testing tools available

---

## ⚡ **URGENCY & BUSINESS IMPACT**

### **Business Impact:**
- **User Experience:** Complete application non-functional
- **Revenue Impact:** Users cannot access paid features
- **Reputation Risk:** Application appears broken to users
- **Timeline:** Critical - needs immediate resolution

### **Alternative Solutions Considered:**
1. **Server Migration:** To Apache-based hosting (last resort)
2. **VPS Deployment:** Full server control (expensive option)
3. **Alternative Authentication:** Token-based (development intensive)

---

## 🎯 **SUPPORT REQUEST ACTIONS**

### **Immediate Actions Required:**
1. **LiteSpeed Configuration:** Review and adjust session handling settings
2. **PHP Parameters:** Verify and modify session configuration
3. **Directory Permissions:** Ensure proper session directory access
4. **Security Policies:** Adjust policies blocking session functionality

### **Testing & Verification:**
1. **Deploy Configuration:** Apply required server changes
2. **Test Login Process:** Verify HTTP 200 response
3. **Test Session Persistence:** Confirm session data storage
4. **Test Complete Journey:** Verify login → dashboard flow

### **Documentation:**
1. **Changes Made:** Document all configuration modifications
2. **Testing Results:** Provide verification of functionality
3. **Monitoring:** Set up session monitoring if possible

---

## 📞 **CONTACT & FOLLOW-UP**

### **Preferred Contact Method:**
- **Email:** [Your Email]
- **Phone:** [Your Phone]
- **Response Time:** Critical - Immediate response required

### **Follow-up Schedule:**
- **Initial Response:** Within 2 hours
- **Progress Updates:** Every 4 hours
- **Resolution Target:** Within 24 hours

---

## 📋 **ATTACHMENTS**

### **Technical Attachments:**
1. **Application Logs:** Error logs and debug information
2. **Configuration Files:** Current server configuration
3. **Test Results:** Comprehensive testing documentation
4. **Implementation Guide:** Step-by-step technical requirements

---

## 🏆 **SUCCESS METRICS**

### **Resolution Verification:**
- ✅ Login form submits successfully (HTTP 200)
- ✅ Session data stored correctly
- ✅ Dashboard accessible after login
- ✅ Complete user journey functional
- ✅ No HTTP 500 errors

### **Long-term Monitoring:**
- Session persistence stability
- Performance impact assessment
- User experience verification
- Error rate monitoring

---

**Request Date:** 15 Juni 2026  
**Expected Resolution:** Within 24 hours  
**Priority:** Critical - Complete application functionality at stake  

---

*This support request template provides comprehensive technical requirements for resolving session persistence issues at the server infrastructure level.*

# FTP Credentials - Hostinger Production Server

## 🔐 **FTP Access Information**

### **Server Details:**
- **FTP IP (Hostname):** `153.92.8.148`
- **FTP Username:** `u950781813`
- **FTP Password:** `Sihaloho19.`
- **FTP Port:** `21`
- **Upload Directory:** `/domains/bimbel.bereng.info/public_html/`

### **Connection Commands:**

#### **Using lftp (Recommended):**
```bash
lftp -u u950781813,Sihaloho19. 153.92.8.148 -p 21 <<EOF
set ssl:verify-certificate no
cd /domains/bimbel.bereng.info/public_html
# Upload commands here
bye
EOF
```

#### **Using FileZilla:**
- **Host:** `153.92.8.148`
- **Username:** `u950781813`
- **Password:** `Sihaloho19.`
- **Port:** `21`
- **Remote Directory:** `/domains/bimbel.bereng.info/public_html/`

#### **Using curl (for single files):**
```bash
curl -T local_file.php ftp://u950781813:Sihaloho19.@153.92.8.148/domains/bimbel.bereng.info/public_html/api/
```

## 📁 **Directory Structure:**

```
/domains/bimbel.bereng.info/public_html/
├── api/                    # API endpoints
│   ├── start_tryout.php
│   ├── create_session.php
│   ├── get_questions.php
│   └── logout.php
├── pages/                  # PHP pages
│   ├── materi_twk.php
│   ├── materi_tiu.php
│   └── materi_tkp.php
├── assets/                 # Static assets
├── content/                # Content files
├── includes/               # Include files
└── 404.php                 # Custom error page
```

## 🚀 **Upload Examples:**

### **Upload API Files:**
```bash
lftp -u u950781813,Sihaloho19. 153.92.8.148 -p 21 <<EOF
set ssl:verify-certificate no
cd /domains/bimbel.bereng.info/public_html
put api/start_tryout.php -o api/start_tryout.php
put api/create_session.php -o api/create_session.php
put api/get_questions.php -o api/get_questions.php
put api/logout.php -o api/logout.php
bye
EOF
```

### **Upload Page Files:**
```bash
lftp -u u950781813,Sihaloho19. 153.92.8.148 -p 21 <<EOF
set ssl:verify-certificate no
cd /domains/bimbel.bereng.info/public_html
put pages/materi_twk.php -o pages/materi_twk.php
put pages/materi_tiu.php -o pages/materi_tiu.php
put pages/materi_tkp.php -o pages/materi_tkp.php
put 404.php -o 404.php
bye
EOF
```

### **Upload All Files:**
```bash
lftp -u u950781813,Sihaloho19. 153.92.8.148 -p 21 <<EOF
set ssl:verify-certificate no
cd /domains/bimbel.bereng.info/public_html
mirror -R /opt/lampp/htdocs/permen/ . --exclude=.git --exclude=tests --exclude=sql
bye
EOF
```

## 🔍 **Testing After Upload:**

### **API Endpoints:**
```bash
curl -s "https://bimbel.bereng.info/api/start_tryout.php"
curl -s "https://bimbel.bereng.info/api/create_session.php"
curl -s "https://bimbel.bereng.info/api/get_questions.php"
curl -s "https://bimbel.bereng.info/api/health.php"
```

### **Pages:**
```bash
curl -I "https://bimbel.bereng.info/pages/materi_twk.php"
curl -I "https://bimbel.bereng.info/pages/materi_tiu.php"
curl -I "https://bimbel.bereng.info/pages/materi_tkp.php"
curl -I "https://bimbel.bereng.info/404.php"
```

## ⚠️ **Security Notes:**

- **Password:** `Sihaloho19.` (note the dot at the end)
- **Protocol:** FTP (not SFTP)
- **SSL Verification:** Disabled for compatibility
- **File Permissions:** Automatically set by server (644 for files, 755 for directories)

## 📝 **Last Updated:**
- **Date:** 2026-06-15
- **Status:** Working ✅
- **Tested:** All endpoints functioning properly

---

**⚠️ IMPORTANT:** This file contains sensitive credentials. Do not upload to production server or share publicly. This file is for development reference only.

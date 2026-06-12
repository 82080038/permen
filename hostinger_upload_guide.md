# Upload ke Hostinger via FTP/SSH

## Check FTP Access di Hostinger

### Langkah 1: Cek FTP Credentials
```
Login hPanel → Files → FTP Accounts

Lihat informasi:
- FTP Host: (biasanya ftpupload.net atau srv1980-files.hstgr.io)
- FTP Username: (biasanya u950781813)
- FTP Port: 21 atau 22 (SFTP)
- FTP Path: /home/u950781813/
```

### Langkah 2: Buat FTP Password (Jika Belum Ada)
```
hPanel → FTP Accounts → Create FTP Account

Isi:
- Username: (sudah ada, biasanya u950781813)
- Password: Buat password kuat
- Directory: /domains/bimbel.bereng.info/public_html/
```

---

## Opsi A: Upload via FTP Command (Terminal Linux/Mac)

Jika Anda punya terminal dengan FTP client:

```bash
# Install lftp jika belum ada
sudo apt-get install lftp

# Upload file
lftp -u u950781813,password ftpupload.net <<EOF
set ssl:verify-certificate no
mirror -R /opt/lampp/htdocs/permen/ /domains/bimbel.bereng.info/public_html/
bye
EOF
```

---

## Opsi B: Upload via SCP/SFTP (Jika SSH Aktif)

Check SSH access:
```
hPanel → Advanced → SSH Access

Jika SSH aktif:
Host: srv1980.hstgr.io atau IP server
Port: 22
Username: u950781813
```

Command upload:
```bash
scp -r /opt/lampp/htdocs/permen/* u950781813@srv1980.hstgr.io:/domains/bimbel.bereng.info/public_html/
```

---

## Opsi C: Upload via rsync (Jika tersedia)

```bash
rsync -avz --progress /opt/lampp/htdocs/permen/ u950781813@ftpupload.net:/domains/bimbel.bereng.info/public_html/
```

---

## Opsi D: PHP Upload Script (Fallback)

Buat file `upload_helper.php` di lokal, upload ke Hostinger, jalankan via browser.

Tapi ini tetap butuh 1x upload manual dulu.

---

## Rekomendasi Terbaik

### Gunakan FileZilla (GUI FTP Client)

```
1. Download FileZilla: https://filezilla-project.org
2. Install & Buka
3. Quick Connect:
   - Host: ftpupload.net (atau lihat di hPanel)
   - Username: u950781813
   - Password: (buat di hPanel FTP Accounts)
   - Port: 21
4. Connect
5. Drag & drop folder permen ke /public_html/
```

---

## Checklist Setup FTP

- [ ] Buka hPanel → Files → FTP Accounts
- [ ] Catat FTP Hostname
- [ ] Catat FTP Username
- [ ] Create/Buat FTP Password
- [ ] Install FileZilla (Windows/Mac/Linux)
- [ ] Connect ke FTP
- [ ] Upload folder permen ke /public_html/
- [ ] Upload file .env
- [ ] Test website

---

## Troubleshooting

**Error: Connection refused**
→ Coba ganti port 21 → 22 (SFTP)

**Error: Authentication failed**
→ Password salah atau FTP account belum dibuat

**Error: Permission denied**
→ Folder destination salah, cek path di hPanel

**Upload very slow**
→ Gunakan FileZilla dengan setting: Edit → Settings → Transfers → Max simultaneous transfers = 1

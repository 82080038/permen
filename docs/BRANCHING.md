# Git Branching Strategy — SKD CAT-BKN (permen)

## Branches

| Branch | Purpose | Deploy to |
|--------|---------|-----------|
| `main` | Production-ready code | `bimbel.bereng.info` (Hostinger) |
| `dev` | Development & testing | Local XAMPP only |

## Workflow

### Development (komputer lokal / komputer lain)

```bash
# 1. Selalu mulai dari branch dev
git checkout dev
git pull origin dev

# 2. Buat perubahan, test di lokal
# ...koding...
# test di http://localhost/permen

# 3. Commit & push ke dev
git add -A
git commit -m "feat: deskripsi perubahan"
git push origin dev
```

### Deploy ke Production

```bash
# 1. Pastikan dev sudah stabil dan tested
git checkout dev
# jalankan test: npx playwright test

# 2. Merge dev ke main
git checkout main
git pull origin main
git merge dev
git push origin main

# 3. Deploy ke server
ssh -p 65002 u950781813@153.92.8.148
cd /home/u950781813/domains/bimbel.bereng.info/public_html
git pull origin main

# 4. Kembali ke dev untuk development
git checkout dev
```

### Komputer Lain (clone fresh)

```bash
# Clone repo
git clone https://github.com/82080038/permen.git
cd permen

# Checkout dev untuk development
git checkout dev

# Setup local environment
cp .env.example .env
# Edit .env sesuai environment lokal
```

## Rules

1. **JANGAN** push langsung ke `main` kecuali hotfix urgent
2. **Selalu** develop di `dev`, test, lalu merge ke `main`
3. **Production** server selalu pull dari `main`
4. **`.env`** file di-gitignore — setiap environment punya config sendiri

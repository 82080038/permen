# Terminology Standardization Guide

This document defines consistent terminology across the SKD CAT-BKN application to ensure clarity and user understanding.

## Core Terminology

### Test Types
- **Try Out SKD** - Full 110-question simulation (110 minutes)
- **Latihan per Subtes** - Practice by subtest (TWK/TIU/TKP)
- **Daily Quiz** - Daily practice quiz
- **Uji Pemahaman** - Understanding test after studying material

### Subtests
- **TWK** - Tes Wawasan Kebangsaan (Nationalism Test)
- **TIU** - Tes Intelegensia Umum (General Intelligence Test)
- **TKP** - Tes Karakteristik Pribadi (Personal Characteristics Test)

### Scoring
- **Nilai** - Score (numeric value)
- **Passing Grade** - Minimum passing score
- **Ambang Batas** - Passing threshold
- **Rata-rata** - Average score

### User Roles
- **Peserta** - Participant/User
- **Admin** - Administrator
- **Guest** - Unauthenticated visitor

### Navigation
- **Beranda** - Homepage
- **Dashboard** - User dashboard
- **Profil** - User profile
- **Leaderboard** - Ranking/leaderboard
- **Feedback** - User feedback
- **Materi** - Learning materials

### Actions
- **Mulai** - Start
- **Selesai** - Finish/Complete
- **Lanjut** - Continue/Next
- **Kembali** - Back/Previous
- **Simpan** - Save
- **Hapus** - Delete
- **Edit** - Edit/Modify
- **Batal** - Cancel

### Status
- **Berjalan** - In progress
- **Selesai** - Completed
- **Belum Mulai** - Not started
- **Terkunci** - Locked

## Standardized Phrases

### Success Messages
- "Data berhasil disimpan" (Data saved successfully)
- "Try Out berhasil diselesaikan" (Try Out completed successfully)
- "Jawaban berhasil disimpan" (Answer saved successfully)

### Error Messages
- "Terjadi kesalahan saat memuat data" (Error loading data)
- "Koneksi internet tidak stabil" (Unstable internet connection)
- "Session tidak valid" (Invalid session)

### Confirmation Messages
- "Apakah Anda yakin ingin menghapus?" (Are you sure you want to delete?)
- "Apakah Anda yakin ingin menyelesaikan Try Out?" (Are you sure you want to complete the Try Out?)
- "Perubahan tidak akan disimpan. Lanjutkan?" (Changes will not be saved. Continue?)

## Inconsistent Terminology to Fix

### Current Issues
1. "Latihan per Subtes" vs "Latihan Personal" → Use "Latihan per Subtes" consistently
2. "User" vs "Peserta" → Use "Peserta" in UI, "user" in code
3. "Dashboard" vs "User Dashboard" → Use "Dashboard" consistently
4. "Tryout" vs "Try Out" → Use "Try Out" (with space)
5. "Nilai" vs "Score" → Use "Nilai" in UI, "score" in code

## Implementation Guidelines

### In UI (PHP/HTML)
- Use Indonesian terms for user-facing text
- Use consistent capitalization (Title Case for headers, sentence case for body text)
- Use "Try Out" with space, not "Tryout"

### In Code (JavaScript/PHP)
- Use English for variable names and function names
- Use camelCase for variables
- Use PascalCase for classes
- Use snake_case for database columns

### Examples
```php
// UI - Indonesian
echo "Selamat datang, Peserta!";
echo "Nilai TWK Anda: " . $nilai_twk;

// Code - English
$userName = $_SESSION['user_nama'];
$twkScore = $session['nilai_twk'];
```

```javascript
// Code - English
const userName = document.getElementById('user-name').value;
const twkScore = parseInt(document.getElementById('twk-score').value);

// UI - Indonesian
showToast('success', 'Data berhasil disimpan');
```

## Testing Checklist

- [ ] All navigation links use consistent terminology
- [ ] All button labels use consistent terminology
- [ ] All error messages use consistent terminology
- [ ] All success messages use consistent terminology
- [ ] All status indicators use consistent terminology
- [ ] All subtest references use TWK/TIU/TKP consistently
- [ ] All score references use "Nilai" in UI
- [ ] All user references use "Peserta" in UI

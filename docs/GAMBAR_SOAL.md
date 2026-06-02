# Panduan Gambar Soal SKD CAT-BKN

## Cara Menambahkan Gambar ke Soal

### 1. Upload Gambar dari Komputer
1. Masuk ke **Dashboard Admin**
2. Klik tab **Kelola Soal**
3. Di bagian **Upload Gambar Soal**, pilih file dari komputer
4. Klik **Upload**
5. Salin URL yang muncul (misal: `assets/soal/soal_20250602_123456_abcd.jpg`)
6. Edit soal yang ingin ditambahkan gambar → tempel URL ke kolom gambar

### 2. Format Gambar yang Diizinkan
| Format | Ekstensi | Max Size |
|--------|----------|----------|
| JPEG | .jpg, .jpeg | 2 MB |
| PNG | .png | 2 MB |
| GIF | .gif | 2 MB |
| SVG | .svg | 2 MB |
| WebP | .webp | 2 MB |

### 3. Mencari Gambar dari Internet (Free/Legal)

#### Wikipedia / Wikimedia Commons (REKOMENDASI)
Wikimedia Commons memiliki jutaan gambar **bebas hak cipta** (public domain / Creative Commons).

**Langkah:**
1. Kunjungi https://commons.wikimedia.org
2. Cari gambar yang relevan, contoh:
   - "Indonesian flag" → untuk soal TWK (bendera)
   - "Pancasila symbol" → untuk soal TWK (Pancasila)
   - "Indonesian map" → untuk soal TWK (geografi)
   - "Geometric shapes" → untuk soal TIU figural
3. Pastikan lisensi gambar **Public Domain** atau **CC BY-SA**
4. Download gambar
5. Upload ke aplikasi via Dashboard Admin

**Contoh pencarian:**
- Soal TWK Sejarah → cari "Proklamasi Kemerdekaan Indonesia 1945"
- Soal TWK Pilar Negara → cari "Pancasila Indonesia"
- Soal TIU Figural → cari "geometric patterns", "analogies shapes"
- Soal TWK UUD 1945 → cari "Constitution of Indonesia"

#### Sumber Lain (Bebas Hak Cipta)
| Situs | Kegunaan | Lisensi |
|-------|----------|---------|
| https://commons.wikimedia.org | Umum: bendera, peta, sejarah | CC0 / CC BY-SA |
| https://unsplash.com | Foto umum | Unsplash License (free) |
| https://pixabay.com | Ilustrasi, foto | Pixabay License (free) |
| https://www.pexels.com | Foto umum | Pexels License (free) |
| https://openclipart.org | Clipart, ilustrasi | Public Domain |
| https://www.freepik.com | Ilustrasi, icon | Free license |

### 4. Jenis Gambar yang Sering Dibutuhkan

#### TWK (Tes Wawasan Kebangsaan)
- **Bendera Indonesia**: bendera merah putih, bendera daerah
- **Lambang Negara**: Garuda Pancasila
- **Peta Indonesia**: peta pulau, provinsi
- **Foto Sejarah**: Proklamasi, Sumpah Pemuda, Konferensi Asia Afrika
- **Gedung Pemerintah**: DPR/MPR, Istana Negara, Mahkamah Agung
- **Tokoh Nasional**: Soekarno, Hatta, tokoh pahlawan

#### TIU (Tes Intelegensia Umum)
- **Figural**: pola geometri, analogi bentuk, serial, ketidaksamaan
- **Matematika**: diagram, grafik, tabel
- **Logika**: tabel kebenaran, diagram Venn

#### TKP (Tes Karakteristik Pribadi)
- **Situasi**: ilustrasi kantor, kerja sama, pelayanan publik
- **Emoji/Reaksi**: ekspresi wajah, situasi sosial

### 5. Tips Ukuran & Kualitas
- **Resolusi**: 300-600px lebar cukup untuk tampilan soal
- **Rasio**: gunakan 4:3 atau 16:9 agar rapi di layar
- **Kompresi**: gunakan https://tinypng.com untuk mengompres PNG/JPG tanpa kehilangan kualitas
- **SVG**: ideal untuk figural (tetap tajam di semua ukuran)

### 6. Hak Cipta & Legal
⚠️ **JANGAN** menggunakan gambar dari:
- Google Images langsung (banyak berhak cipta)
- Shutterstock, Getty Images (berbayar)
- Buku pelajaran berhak cipta

✅ **BOLEH** menggunakan gambar dari:
- Wikimedia Commons (cek lisensi)
- Situs dengan lisensi CC0 / Public Domain
- Gambar yang Anda buat sendiri

### 7. Cara Edit Gambar yang Sudah Ada
1. Di **Kelola Soal**, klik tombol **Edit** pada soal
2. Di bagian **Gambar Soal**, klik **Choose File**
3. Pilih gambar baru dari komputer
4. Klik **Simpan**

### 8. Menghapus Gambar Lama
Gambar yang tidak lagi digunakan soal mana pun akan otomatis terdeteksi. Admin dapat menjalankan cleanup dari helper `cleanupOrphanedImages()`.

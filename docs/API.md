# Dokumentasi API SKD CAT-BKN

## Base URL
```
http://localhost/permen/api/
```

---

## 1. Materi

### GET `/api/materi.php`
Mengambil semua materi dari ketiga subtes.

**Response**:
```json
{
  "twk": [ { "id": "...", "subtes": "TWK", "judul": "...", "konten": "..." }, ... ],
  "tiu": [ ... ],
  "tkp": [ ... ]
}
```

### GET `/api/materi.php?subtes=TWK`
Mengambil materi untuk satu subtes.

**Parameter**:
| Nama | Tipe | Deskripsi |
|------|------|-----------|
| subtes | string | `TWK`, `TIU`, atau `TKP` |

**Response**:
```json
{
  "subtes": "TWK",
  "materi": [
    { "id": "twk_nasionalisme", "subtes": "TWK", "judul": "Nilai-nilai Nasionalisme", "konten": "<h2>..." }
  ]
}
```

### GET `/api/materi.php?id=twk_nasionalisme`
Mengambil satu materi berdasarkan ID.

**Response**:
```json
{
  "id": "twk_nasionalisme",
  "subtes": "TWK",
  "judul": "Nilai-nilai Nasionalisme",
  "konten": "<h2>..."
}
```

---

## 2. Soal Try Out

### GET `/api/get_soal.php?session_id={id}`
Mengambil daftar soal untuk sesi tryout tertentu. Jika soal belum pernah digenerate, akan dibuat otomatis.

**Parameter**:
| Nama | Tipe | Wajib | Deskripsi |
|------|------|-------|-----------|
| session_id | int | Ya | ID dari tabel tryout_sessions |

**Response Sukses**:
```json
{
  "session": {
    "id": 1,
    "user_id": 1,
    "nama": "Try Out SKD",
    "durasi_tkp": 25,
    "durasi_tiu": 30,
    "durasi_twk": 25,
    "jumlah_tkp": 35,
    "jumlah_tiu": 35,
    "jumlah_twk": 30,
    "waktu_mulai": "2026-06-02 10:00:00",
    "status": "berjalan"
  },
  "soal": [
    {
      "answer_id": 1,
      "jawaban_user": null,
      "id": 5,
      "subtes": "TKP",
      "tipe": null,
      "topik": "Pelayanan Publik",
      "pertanyaan": "Seorang warga datang ke kantor dengan emosi...",
      "pilihan_a": "Menjelaskan bahwa proses memang lambat...",
      "pilihan_b": "Meminta maaf dan menjanjikan penyelesaian...",
      "pilihan_c": "Mengalihkan tanggung jawab ke bagian lain",
      "pilihan_d": "Menyuruhnya menunggu tanpa penjelasan",
      "pilihan_e": "Menyuruhnya mengurus sendiri ke atasan",
      "jawaban_benar": "B",
      "bobot_tkp": 5,
      "pembahasan": "Meminta maaf dan menjanjikan solusi..."
    }
  ]
}
```

**Keamanan**: Endpoint ini memerlukan session PHP aktif. Hanya user yang memiliki session tersebut yang bisa mengakses soal.

**Response Error**:
```json
{ "error": "Session ID diperlukan" }          // HTTP 400
{ "error": "Autentikasi diperlukan" }          // HTTP 401
{ "error": "Session tidak ditemukan atau bukan milik Anda" }  // HTTP 403
```

---

## 3. Submit Jawaban

### POST `/api/submit_jawaban.php`
Mengirim jawaban untuk satu soal.

**Header**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "answer_id": 1,
  "jawaban": "B"
}
```

**Parameter**:
| Nama | Tipe | Wajib | Deskripsi |
|------|------|-------|-----------|
| answer_id | int | Ya | ID dari tabel answers |
| jawaban | string | Ya | Huruf A–E |

**Response Sukses**:
```json
{
  "success": true,
  "skor": 5
}
```

**Keamanan**: Endpoint ini memvalidasi bahwa `answer_id` milik session yang dimiliki user yang sedang login.

**Response Error**:
```json
{ "error": "Data tidak lengkap atau jawaban tidak valid" }     // HTTP 400
{ "error": "Autentikasi diperlukan" }                           // HTTP 401
{ "error": "Soal tidak ditemukan atau bukan milik Anda" }       // HTTP 403
```

---

## 4. Finish Try Out

### POST `/api/finish_tryout.php`
Menyelesaikan sesi tryout dan menghitung nilai akhir.

**Header**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "session_id": 1
}
```

**Response Sukses**:
```json
{
  "success": true,
  "nilai": {
    "TKP": 78,
    "TIU": 65,
    "TWK": 55
  },
  "total": 198
}
```

**Keamanan**: Endpoint ini memvalidasi kepemilikan session — hanya pemilik session yang bisa menyelesaikannya, dan session harus berstatus `berjalan`.

**Response Error**:
```json
{ "error": "Session ID diperlukan" }                                          // HTTP 400
{ "error": "Autentikasi diperlukan" }                                           // HTTP 401
{ "error": "Session tidak ditemukan, sudah selesai, atau bukan milik Anda" }   // HTTP 403
```

---

## Kode Status HTTP

| Kode | Kondisi |
|------|---------|
| 200 | Sukses |
| 400 | Bad request / parameter tidak lengkap |
| 401 | Unauthorized — user belum login |
| 403 | Forbidden — data bukan milik user atau tidak valid |
| 404 | Resource tidak ditemukan |
| 500 | Server error |

---

## Catatan Integrasi Frontend

```javascript
// Contoh: Ambil soal
const res = await fetch('../api/get_soal.php?session_id=123');
const data = await res.json();

// Contoh: Kirim jawaban
await fetch('../api/submit_jawaban.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ answer_id: 5, jawaban: 'C' })
});

// Contoh: Selesai
await fetch('../api/finish_tryout.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ session_id: 123 })
});
```

---

## 5. Generate Soal (Smart Generator Internal)

### GET `/api/generate_soal_smart.php`
Menghasilkan soal baru secara otomatis menggunakan algoritma PHP tanpa API eksternal.

**Parameter**:
| Nama | Tipe | Wajib | Deskripsi |
|------|------|-------|-----------|
| subtes | string | Ya | `TKP`, `TIU`, atau `TWK` |
| tipe | string | Ya | `numerik`, `verbal`, `figural`, atau kosong |
| topik | string | Ya | Contoh: `Deret Angka`, `Pelayanan Publik` |
| jumlah | int | Tidak | Jumlah soal (default: 5, max: 50) |
| kesulitan | string | Tidak | `mudah`, `sedang`, `sulit` |

**Response Sukses**:
```json
{
  "success": true,
  "generator": "smart_internal",
  "no_api_required": true,
  "generated": 10,
  "inserted": 10,
  "soal": [ { ... } ]
}
```

---

## 6. Generate Soal (AI External — Opsional)

### GET `/api/generate_soal_ai.php`
Menghasilkan soal baru menggunakan Gemini 2.0 Flash API. Memerlukan API key di file.

**Parameter**: Sama seperti Smart Generator.

**Response**: Sama seperti Smart Generator, dengan `generator: "gemini"`.

**Catatan**: Jika API key belum diatur, akan mengembalikan error instruksi pengaturan.

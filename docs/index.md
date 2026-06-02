# Dokumentasi SKD CAT-BKN Try Out & Bimbel

Selamat datang di dokumentasi aplikasi. Gunakan indeks di bawah ini untuk navigasi.

---

## Indeks Dokumen

### Untuk Developer
| Dokumen | Isi |
|---------|-----|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Arsitektur sistem, stack teknologi, diagram alur data, skema database, mekanisme skoring, catatan keamanan |
| [API.md](API.md) | Endpoint lengkap: materi, soal, submit jawaban, finish tryout. Contoh request/response JSON |

### Untuk Manajemen Proyek
| Dokumen | Isi |
|---------|-----|
| [ROADMAP.md](ROADMAP.md) | Milestone pengembangan dari v1.0.0 sampai v2.1.0. Status: planned / in progress / done |
| [CHANGELOG.md](../CHANGELOG.md) | Riwayat perubahan per versi (Added, Changed, Fixed, Removed) |

### Untuk Pengguna
| Dokumen | Isi |
|---------|-----|
| [README.md](../README.md) | Instalasi, cara menjalankan, persyaratan, struktur folder, catatan teknis |

---

## Proses Update Dokumentasi (SOP)

Setiap kali ada pengembangan, ikuti urutan berikut:

```
1. Kerjakan fitur / bugfix
2. Update CHANGELOG.md  -> tulis perubahan di [Unreleased] atau versi baru
3. Update ROADMAP.md    -> tandai fitur DONE atau IN PROGRESS
4. Update ARCHITECTURE.md -> jika ada perubahan struktur/flow baru
5. Update API.md         -> jika ada endpoint baru atau perubahan response
6. Commit & push
```

---

## Versi Dokumentasi Terakhir

- **v1.0.0** — 2026-06-02 — Dokumentasi awal lengkap (ARCHITECTURE, API, ROADMAP, CHANGELOG).

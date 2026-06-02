# Stack Reference — Indeks

Folder ini berisi referensi lengkap untuk teknologi yang relevan dengan ekosistem aplikasi SKD CAT-BKN.

---

## Stack Aktual Aplikasi

| Layer | Teknologi | Keterangan |
|-------|-----------|------------|
| **Backend** | PHP 7.4+ (vanilla) | Tanpa framework. PDO prepared statements, native session |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript | Tidak menggunakan jQuery atau Bootstrap di codebase aktual |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ | InnoDB, utf8mb4 |
| **Web Server** | Apache (XAMPP/LAMP) | mod_rewrite, mod_headers |
| **API** | REST-like JSON over HTTP | fetch API (vanilla JS) |

> **Catatan:** Dokumen JQUERY.md dan BOOTSTRAP.md disediakan sebagai **referensi opsional** jika di masa depan ingin mengadopsi library tersebut. Aplikasi saat ini menggunakan JavaScript dan CSS vanilla agar ringan dan tidak bergantung pada library eksternal.

---

## Daftar Referensi

| Dokumen | Teknologi | Versi | Isi |
|---------|-----------|-------|-----|
| [PHP_NATIVE.md](PHP_NATIVE.md) | PHP | 7.4+ | PDO, prepared statements, keamanan, session, JSON API, helper functions |
| [JQUERY.md](JQUERY.md) | jQuery | 3.7+ | *(Referensi opsional)* Selector, DOM manipulation, AJAX |
| [MYSQL.md](MYSQL.md) | MySQL / MariaDB | 5.7+ / 10.3+ | DDL, DML, indexing, transactions, backup, optimization |
| [BOOTSTRAP.md](BOOTSTRAP.md) | Bootstrap | 5.3+ | *(Referensi opsional)* Grid, components, utilities |

---

## Cara Menggunakan Referensi Ini

1. **Sebelum mulai ngoding**: Baca bagian "Best Practices" di masing-masing dokumen.
2. **Saat stuck lupa syntax**: Cari di tabel/kategori yang relevan.
3. **Saat review kode**: Cek apakah kode mengikuti pola yang direkomendasikan.
4. **Saat onboarding anggota tim**: Minta mereka baca semua 4 dokumen ini terlebih dahulu.

---

## Kombinasi Stack (Contoh Nyata dalam Aplikasi)

### Contoh: Render soal dengan Bootstrap + jQuery + PHP

**Backend (PHP)**:
```php
$stmt = $pdo->prepare("SELECT * FROM questions WHERE subtes = ?");
$stmt->execute(['TKP']);
$soal = $stmt->fetchAll();
echo json_encode($soal);
```

**Frontend (jQuery + Bootstrap)**:
```javascript
$.getJSON('../api/get_soal.php', { session_id: 1 }, function(data) {
    let html = '<div class="card">';
    html += '<div class="card-body">';
    html += '<h5 class="card-title">' + data.soal[0].pertanyaan + '</h5>';
    html += '<div class="list-group">';
    ['A','B','C','D','E'].forEach(opt => {
        html += '<button class="list-group-item list-group-item-action">' + opt + '</button>';
    });
    html += '</div></div></div>';
    $('#soalContainer').html(html);
});
```

### Contoh: Form dengan Bootstrap + PHP

```html
<form id="formLogin" class="card p-4">
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" id="email" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" class="form-control" id="password" required>
  </div>
  <button type="submit" class="btn btn-primary w-100">Login</button>
</form>
```

```javascript
$('#formLogin').on('submit', function(e) {
    e.preventDefault();
    $.post('../api/login.php', {
        email: $('#email').val(),
        password: $('#password').val()
    }, function(response) {
        if (response.success) {
            window.location.href = 'dashboard.php';
        } else {
            alert(response.error);
        }
    }, 'json');
});
```

---

## Update Referensi

Saat ada **upgrade versi** teknologi (misal PHP 7.4 → 8.2, Bootstrap 5.3 → 5.4):

1. Buka dokumen yang relevan.
2. Update bagian "Setup & CDN".
3. Tambahkan catatan breaking changes jika ada.
4. Update versi di tabel indeks di atas.
5. Tulis perubahan di `CHANGELOG.md`.

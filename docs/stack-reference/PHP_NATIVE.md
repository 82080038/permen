# PHP Native — Referensi & Best Practices

Stack utama aplikasi ini: **PHP 7.4+ vanilla** (tanpa framework). Dokumen ini adalah acuan pengembangan.

---

## 1. Konfigurasi & Koneksi Database (PDO)

```php
<?php
$host = 'localhost';
$db   = 'skd_cat_bkn';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

session_start();
```

**Aturan**:
- Selalu gunakan `PDO` dengan prepared statements.
- `ERRMODE_EXCEPTION` → tangkap error dengan try-catch.
- `EMULATE_PREPARES false` → prepared statements beneran di DB, bukan di PHP.
- `session_start()` cukup dipanggil sekali di `config.php`.

---

## 2. Query Patterns

### SELECT
```php
$stmt = $pdo->prepare("SELECT * FROM questions WHERE subtes = ? ORDER BY RAND() LIMIT ?");
$stmt->execute(['TKP', 35]);
$rows = $stmt->fetchAll();

// Single row
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(); // associative array or false
```

### INSERT
```php
$stmt = $pdo->prepare("INSERT INTO users (nama, email) VALUES (?, ?)");
$stmt->execute(['Nama', 'email@test.com']);
$lastId = $pdo->lastInsertId();
```

### UPDATE
```php
$stmt = $pdo->prepare("UPDATE answers SET jawaban_user = ?, skor = ? WHERE id = ?");
$stmt->execute(['B', 5, $answerId]);
$affectedRows = $stmt->rowCount();
```

### DELETE
```php
$stmt = $pdo->prepare("DELETE FROM tryout_sessions WHERE status = 'gagal' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
```

### COUNT
```php
$stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE session_id = ?");
$stmt->execute([$sessionId]);
$count = $stmt->fetchColumn(); // returns scalar value
```

---

## 3. Keamanan

### SQL Injection — SELALU Prepared Statements
```php
// ✅ BENAR
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST['email']]);

// ❌ SALAH — JANGAN PERNAH
$email = $_POST['email'];
$rows = $pdo->query("SELECT * FROM users WHERE email = '$email'")->fetchAll();
```

### XSS — Escape Output
```php
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Penggunaan
echo '<p>' . e($user['nama']) . '</p>';
```

### CSRF Token (rekomendasi untuk form)
```php
// Generate token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Di form
$token = $_SESSION['csrf_token'];
echo '<input type="hidden" name="csrf_token" value="' . e($token) . '">';

// Validasi saat submit
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF token invalid');
}
```

### Password Hashing
```php
// Register
$hash = password_hash($password, PASSWORD_BCRYPT);

// Login
if (password_verify($password, $hashFromDb)) {
    // login sukses
}
```

---

## 4. Routing Sederhana

Aplikasi ini menggunakan file-based routing (tidak ada router framework). Polanya:

```
index.php       -> /
pages/materi.php -> /pages/materi.php
api/get_soal.php -> /api/get_soal.php
```

Jika ingin URL bersih, gunakan `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 5. Session Management

```php
// config.php -> session_start() sudah dipanggil

// Simpan data
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_nama'] = $user['nama'];

// Ambil data
$userId = $_SESSION['user_id'] ?? null;

// Hapus data
unset($_SESSION['user_id']);

// Destroy session
session_destroy();
```

---

## 6. JSON API Response Pattern

```php
<?php
require '../config.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['field'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Field diperlukan']);
    exit;
}

try {
    // ... logic ...
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## 7. Helper Functions (simpan di config.php atau helpers.php)

```php
function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    return 'Rp' . number_format($angka, 0, ',', '.');
}
```

---

## 8. Common Pitfalls

| Masalah | Solusi |
|---------|--------|
| `headers already sent` | Jangan echo/print sebelum `header()`. Pastikan tidak ada BOM atau whitespace sebelum `<?php`. |
| `undefined array key` | Gunakan `$_POST['key'] ?? null` atau `isset()` / `array_key_exists()`. |
| `PDOException` | Wrap query dalam try-catch; jangan tampilkan error ke user di production. |
| Session tidak persist | Pastikan `session_start()` dipanggil sebelum output apa pun. |
| Upload file insecure | Validasi MIME type, rename file, simpan di luar web root. |

---

## 9. Referensi

- [PHP Manual](https://www.php.net/manual/en/)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [PHP The Right Way](https://phptherightway.com/)

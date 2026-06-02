# Bootstrap 5 — Referensi & Component Patterns

Aplikasi ini menggunakan **Bootstrap 5.3.x** untuk styling dan layout. Dokumen ini adalah acuan pengembangan UI/UX.

---

## 1. Setup & CDN

```html
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Judul</title>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons (opsional) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<!-- Konten -->

<!-- Bootstrap 5 JS Bundle (Popper included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## 2. Grid System

```html
<div class="container">
  <div class="row">
    <!-- 12 columns total -->
    <div class="col-12 col-md-8">Konten utama</div>
    <div class="col-12 col-md-4">Sidebar</div>
  </div>
</div>

<!-- Auto columns -->
<div class="row">
  <div class="col">Kolom 1</div>
  <div class="col">Kolom 2</div>
  <div class="col">Kolom 3</div>
</div>

<!-- Offset -->
<div class="row">
  <div class="col-md-6 offset-md-3">Centered</div>
</div>
```

**Breakpoints**: `xs` (<576px), `sm` (≥576px), `md` (≥768px), `lg` (≥992px), `xl` (≥1200px), `xxl` (≥1400px)

---

## 3. Typography

```html
<h1 class="display-4">Judul Besar</h1>
<p class="lead">Paragraf penjelasan</p>
<p class="text-muted">Teks abu-abu</p>
<p class="text-primary">Teks biru primary</p>
<p class="fw-bold">Teks bold</p>
<p class="fs-5">Font size 5</p>
<p class="text-center">Teks center</p>
```

---

## 4. Buttons

```html
<!-- Variants -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-info">Info</button>
<button class="btn btn-light">Light</button>
<button class="btn btn-dark">Dark</button>

<!-- Outline -->
<button class="btn btn-outline-primary">Outline Primary</button>

<!-- Sizes -->
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary">Normal</button>
<button class="btn btn-primary btn-lg">Large</button>

<!-- Block / full width -->
<button class="btn btn-primary w-100">Full Width</button>

<!-- Disabled -->
<button class="btn btn-primary" disabled>Disabled</button>
```

---

## 5. Cards

```html
<div class="card">
  <div class="card-header">Header</div>
  <div class="card-body">
    <h5 class="card-title">Judul Card</h5>
    <p class="card-text">Isi konten card.</p>
    <a href="#" class="btn btn-primary">Action</a>
  </div>
  <div class="card-footer text-muted">Footer</div>
</div>

<!-- Card dengan shadow -->
<div class="card shadow-sm">...</div>
<div class="card shadow">...</div>
<div class="card shadow-lg">...</div>
```

---

## 6. Forms

```html
<form>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" placeholder="name@example.com">
    <div class="form-text">Keterangan tambahan</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" class="form-control">
  </div>

  <!-- Select -->
  <div class="mb-3">
    <label class="form-label">Subtes</label>
    <select class="form-select">
      <option selected>Pilih...</option>
      <option value="TWK">TWK</option>
      <option value="TIU">TIU</option>
      <option value="TKP">TKP</option>
    </select>
  </div>

  <!-- Textarea -->
  <div class="mb-3">
    <label class="form-label">Pembahasan</label>
    <textarea class="form-control" rows="5"></textarea>
  </div>

  <!-- Checkbox -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="check1">
    <label class="form-check-label" for="check1">Ragu-ragu</label>
  </div>

  <!-- Radio -->
  <div class="form-check">
    <input class="form-check-input" type="radio" name="jawaban" id="optA">
    <label class="form-check-label" for="optA">A. Pilihan A</label>
  </div>

  <button type="submit" class="btn btn-primary">Submit</button>
</form>
```

---

## 7. Tables

```html
<table class="table table-striped table-hover table-bordered">
  <thead class="table-dark">
    <tr>
      <th>#</th>
      <th>Nama</th>
      <th>Nilai</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>1</td>
      <td>Budi</td>
      <td>85</td>
    </tr>
  </tbody>
</table>

<!-- Responsive table -->
<div class="table-responsive">
  <table class="table">...</table>
</div>
```

---

## 8. Alerts & Badges

```html
<!-- Alert -->
<div class="alert alert-success" role="alert">Sukses!</div>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  Error terjadi!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Badge -->
<span class="badge bg-primary">TKP</span>
<span class="badge bg-success rounded-pill">LULUS</span>
```

---

## 9. Modal

```html
<!-- Trigger -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKonfirmasi">Buka Modal</button>

<!-- Modal -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnYakin">Ya</button>
      </div>
    </div>
  </div>
</div>
```

**Buka modal via JavaScript**:
```javascript
const myModal = new bootstrap.Modal(document.getElementById('modalKonfirmasi'));
myModal.show();
myModal.hide();
```

---

## 10. Navbar

```html
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="#">SKD CAT-BKN</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="#">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Materi</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Try Out</a></li>
      </ul>
    </div>
  </div>
</nav>
```

---

## 11. Progress & Spinner

```html
<!-- Progress bar -->
<div class="progress">
  <div class="progress-bar" role="progressbar" style="width: 50%">50%</div>
</div>
<div class="progress mt-2">
  <div class="progress-bar bg-success" style="width: 75%">75%</div>
</div>

<!-- Spinner -->
<div class="spinner-border text-primary" role="status">
  <span class="visually-hidden">Loading...</span>
</div>
<div class="spinner-grow text-success"></div>
```

---

## 12. Utilities

### Spacing (margin & padding)
```html
<!-- Format: {property}{sides}-{size} -->
<!-- m = margin, p = padding -->
<!-- t = top, b = bottom, s = start (left), e = end (right), x = horizontal, y = vertical -->
<!-- 0-5, auto -->

<div class="m-3">Margin 1rem semua sisi</div>
<div class="mt-2 mb-4">Margin top 0.5rem, bottom 1.5rem</div>
<div class="px-3 py-2">Padding horizontal 1rem, vertical 0.5rem</div>
<div class="mx-auto">Margin horizontal auto (center)</div>
```

### Colors & Background
```html
<div class="bg-primary text-white">Background primary, teks putih</div>
<div class="bg-light border">Background terang dengan border</div>
<div class="text-success">Teks hijau</div>
```

### Display & Flex
```html
<div class="d-flex justify-content-between align-items-center">
  <div>Left</div>
  <div>Right</div>
</div>

<div class="d-none d-md-block">Hidden di mobile, visible di tablet+</div>
<div class="d-block d-md-none">Visible di mobile, hidden di tablet+</div>
```

### Position
```html
<div class="position-relative">
  <div class="position-absolute top-0 end-0">Top Right</div>
</div>
<div class="fixed-top">Fixed navbar</div>
<div class="sticky-top">Sticky header</div>
```

---

## 13. Customization (Custom CSS on top of Bootstrap)

Aplikasi ini menggunakan **custom CSS minimal** di atas Bootstrap. Pola:

```css
/* Override Bootstrap variable */
:root {
  --bs-primary: #1a5276;
  --bs-success: #27ae60;
}

/* Custom class */
.card-skd {
  border-left: 4px solid var(--bs-primary);
}

/* Responsive tweaks */
@media (max-width: 768px) {
  .sidebar { display: none; }
}
```

---

## 14. Icons (Bootstrap Icons)

```html
<i class="bi bi-house"></i>
<i class="bi bi-book"></i>
<i class="bi bi-play-circle"></i>
<i class="bi bi-check-circle-fill text-success"></i>
<i class="bi bi-x-circle-fill text-danger"></i>
<i class="bi bi-clock"></i>
<i class="bi bi-trophy"></i>
```

---

## 15. Dark Mode (Bootstrap 5.3+)

```html
<html lang="id" data-bs-theme="dark">
<!-- atau toggle via JS -->
```

```javascript
document.documentElement.setAttribute('data-bs-theme', 'dark');
```

---

## 16. Referensi

- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [Bootstrap Cheat Sheet](https://getbootstrap.com/docs/5.3/examples/cheatsheet/)

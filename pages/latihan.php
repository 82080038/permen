<?php
require '../config.php';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Ambil daftar instansi dengan passing grade untuk ditampilkan ke user
$instansiList = $pdo->query("SELECT kode, nama, passing_twk, passing_tiu, passing_tkp, passing_total FROM instansi WHERE aktif = 1 ORDER BY passing_total DESC, urutan")->fetchAll();

// Jika subtes dipilih, buat session latihan dan redirect ke tryout
$subtes = $_GET['subtes'] ?? '';
if ($subtes && in_array($subtes, ['TWK','TIU','TKP'])) {
    $nama = "Latihan $subtes";
    $cfg = $pdo->prepare("SELECT durasi_menit, jumlah_soal, passing_grade, urutan FROM subtes_config WHERE subtes = ? AND aktif = 1");
    $cfg->execute([$subtes]);
    $c = $cfg->fetch();
    $durasi = (int)($c['durasi_menit'] ?? ($subtes === 'TWK' ? 30 : ($subtes === 'TIU' ? 35 : 45)));
    $jumlah = (int)($c['jumlah_soal'] ?? ($subtes === 'TWK' ? 30 : ($subtes === 'TIU' ? 35 : 45)));
    $passing = (int)($c['passing_grade'] ?? ($subtes === 'TWK' ? 65 : ($subtes === 'TIU' ? 80 : 126)));
    $urutan = (int)($c['urutan'] ?? ($subtes === 'TWK' ? 1 : ($subtes === 'TIU' ? 2 : 3)));

    // Insert session minimal (tanpa kolom flat berulang)
    $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $nama]);
    $sessionId = $pdo->lastInsertId();

    // Insert ke tabel normalisasi session_subtes
    $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->execute([$sessionId, $subtes, $durasi, $jumlah, $passing, $urutan]);

    header("Location: tryout.php?session_id=$sessionId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Latihan per Subtes — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:900px;margin:1.5rem auto;padding:0 1rem}
.intro{text-align:center;margin-bottom:1.5rem}
.intro h2{color:#1a5276;margin-bottom:.5rem;font-size:1.3rem}
.intro p{color:#555;font-size:.9rem}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.2rem}
.card{background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem;text-align:center;transition:transform .15s}
.card:hover{transform:translateY(-3px)}
.card h3{color:#1a5276;margin-bottom:.5rem;font-size:1.05rem}
.card p{color:#555;font-size:.9rem;margin-bottom:1rem}
.card .meta{color:#777;font-size:.85rem;margin-bottom:1rem}
.card a{display:inline-block;background:#2980b9;color:#fff;padding:.65rem 1.2rem;border-radius:5px;text-decoration:none;font-weight:bold;min-height:44px;min-width:44px}
.card a:hover{background:#1a5276}
.card.twk{border-top:4px solid #e74c3c}
.card.tiu{border-top:4px solid #2980b9}
.card.tkp{border-top:4px solid #27ae60}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:480px){
.intro h2{font-size:1.15rem}
.grid{grid-template-columns:1fr}
.card{padding:1rem}
}
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="header">
<h1>Latihan per Subtes — SKD CAT-BKN</h1>
<div>
<nav role="navigation" aria-label="Page navigation">
<a href="../index.php">Beranda</a>
<a href="materi.php?subtes=TWK">Materi</a>
<a href="tryout.php">Try Out</a>
<?php if (!empty($_SESSION['user_id'])): ?>
<a href="user_dashboard.php">Dashboard</a>
<a href="../api/logout.php">Logout</a>
<?php else: ?>
<a href="login.php">Login</a>
<?php endif; ?>
</nav>
</div>
</div>
<div class="container" id="main-content">
<div class="intro">
<h2>Pilih Subtes Latihan</h2>
<p>Latihan fokus pada satu subtes untuk memperkuat pemahaman Anda. Soal diambil dari bank soal aplikasi dan bisa digenerate otomatis.</p>
</div>
<div class="grid">
<div class="card twk">
<h3>TWK — Wawasan Kebangsaan</h3>
<p>Latihan fokus Pancasila, UUD 1945, nasionalisme, integritas, bela negara, pilar negara, bahasa Indonesia.</p>
<div class="meta">30 soal &middot; 30 menit</div>
<a href="?subtes=TWK">Mulai Latihan TWK</a>
</div>
<div class="card tiu">
<h3>TIU — Intelegensia Umum</h3>
<p>Latihan fokus verbal (analogi, silogisme, analitis), numerik (berhitung, deret, perbandingan, cerita), figural.</p>
<div class="meta">35 soal &middot; 35 menit</div>
<a href="?subtes=TIU">Mulai Latihan TIU</a>
</div>
<div class="card tkp">
<h3>TKP — Karakteristik Pribadi</h3>
<p>Latihan fokus pelayanan publik, jejaring kerja, sosial budaya, teknologi informasi, profesionalisme.</p>
<div class="meta">45 soal &middot; 45 menit</div>
<a href="?subtes=TKP">Mulai Latihan TKP</a>
</div>
</div>

<!-- Passing Grade Instansi -->
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem">
    <h3 style="color:#1a5276;margin-bottom:.8rem;font-size:1.1rem;text-align:center">📊 Passing Grade Instansi Sekolah Kedinasan</h3>
    <p style="color:#555;font-size:.85rem;text-align:center;margin-bottom:1rem">Berikut ranking passing grade SKD untuk berbagai instansi. Hasil tryout Anda akan dibandingkan dengan standar ini.</p>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
    <tr style="background:#1a5276;color:#fff">
    <th style="padding:.6rem;text-align:left;border:1px solid #1a5276">Rank</th>
    <th style="padding:.6rem;text-align:left;border:1px solid #1a5276">Instansi</th>
    <th style="padding:.6rem;text-align:center;border:1px solid #1a5276">TWK</th>
    <th style="padding:.6rem;text-align:center;border:1px solid #1a5276">TIU</th>
    <th style="padding:.6rem;text-align:center;border:1px solid #1a5276">TKP</th>
    <th style="padding:.6rem;text-align:center;border:1px solid #1a5276">Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($instansiList as $idx => $ins): ?>
    <tr style="<?= $idx % 2 === 0 ? 'background:#f8f9fa' : 'background:#fff' ?>">
    <td style="padding:.6rem;border:1px solid #ddd;font-weight:bold"><?= $idx + 1 ?></td>
    <td style="padding:.6rem;border:1px solid #ddd">
    <div style="font-weight:bold;color:#1a5276"><?= e($ins['kode']) ?></div>
    <div style="font-size:.75rem;color:#555"><?= e($ins['nama']) ?></div>
    </td>
    <td style="padding:.6rem;border:1px solid #ddd;text-align:center"><?= $ins['passing_twk'] ?></td>
    <td style="padding:.6rem;border:1px solid #ddd;text-align:center"><?= $ins['passing_tiu'] ?></td>
    <td style="padding:.6rem;border:1px solid #ddd;text-align:center"><?= $ins['passing_tkp'] ?></td>
    <td style="padding:.6rem;border:1px solid #ddd;text-align:center;font-weight:bold;color:#2980b9"><?= $ins['passing_total'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>

<!-- Latihan Personal -->
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem;text-align:center;border:2px solid #8e44ad">
    <h3 style="color:#8e44ad;margin-bottom:.5rem;font-size:1.1rem">Latihan Personal — Generate Soal</h3>
    <p style="color:#555;font-size:.9rem;margin-bottom:1rem">Pilih topik spesifik yang ingin Anda latih. Aplikasi akan generate soal baru otomatis dengan pembahasan, tips & trick, dan link belajar.</p>
    <a href="materi.php?subtes=TWK" style="display:inline-block;background:#8e44ad;color:#fff;padding:.65rem 1.2rem;border-radius:5px;text-decoration:none;font-weight:bold;margin:.3rem">TWK</a>
    <a href="materi.php?subtes=TIU" style="display:inline-block;background:#8e44ad;color:#fff;padding:.65rem 1.2rem;border-radius:5px;text-decoration:none;font-weight:bold;margin:.3rem">TIU</a>
    <a href="materi.php?subtes=TKP" style="display:inline-block;background:#8e44ad;color:#fff;padding:.65rem 1.2rem;border-radius:5px;text-decoration:none;font-weight:bold;margin:.3rem">TKP</a>
</div>
</div>
<div class="footer">
Latihan ini menggunakan skor sesuai ketentuan SKD. TWK & TIU (benar/salah), TKP (bobot 1–5).<br>
Dibangun berdasarkan KepmenPANRB No. 208/2025.
</div>
</body>
</html>

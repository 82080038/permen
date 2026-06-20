<?php
/**
 * API: Export Tryout Result as PDF (printable HTML)
 * Generates a print-optimized HTML page that can be saved as PDF
 * 
 * @param int $_GET['session_id'] - Tryout session ID
 */
require '../config.php';
require '../helpers.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    header('Location: login.php');
    exit;
}

$sessionId = (int)($_GET['session_id'] ?? 0);
if (!$sessionId) {
    die('Session ID diperlukan');
}

// Verify ownership
$stmt = $pdo->prepare("SELECT ts.*, u.nama, u.no_hp, u.sekolah_asal 
    FROM tryout_sessions ts 
    JOIN users u ON ts.user_id = u.id 
    WHERE ts.id = ? AND ts.user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    die('Sesi tidak ditemukan');
}

// Get scores per subtes
$scoreStmt = $pdo->prepare("SELECT q.subtes, 
    SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar,
    SUM(CASE WHEN a.jawaban_user IS NOT NULL AND a.jawaban_user != q.jawaban_benar THEN 1 ELSE 0 END) as salah,
    SUM(CASE WHEN a.jawaban_user IS NULL OR a.jawaban_user = '' THEN 1 ELSE 0 END) as kosong,
    SUM(a.skor) as skor
    FROM answers a 
    JOIN questions q ON a.question_id = q.id 
    WHERE a.session_id = ? 
    GROUP BY q.subtes");
$scoreStmt->execute([$sessionId]);
$scores = $scoreStmt->fetchAll();

// Get per-topic breakdown
$topicStmt = $pdo->prepare("SELECT q.subtes, q.topik,
    COUNT(*) as total,
    SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar
    FROM answers a 
    JOIN questions q ON a.question_id = q.id 
    WHERE a.session_id = ? 
    GROUP BY q.subtes, q.topik
    ORDER BY q.subtes, benar ASC");
$topicStmt->execute([$sessionId]);
$topics = $topicStmt->fetchAll();

$totalSkor = 0;
foreach ($scores as $s) {
    $totalSkor += (int)$s['skor'];
}

$nama = e($session['nama']);
$sekolah = e($session['sekolah_asal'] ?? '-');
$tanggal = date('d F Y', strtotime($session['waktu_mulai']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hasil Try Out - <?= $nama ?></title>
<style>
@page { size: A4; margin: 15mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; color: #222; line-height: 1.6; }
.header { text-align: center; padding: 1.5rem 0; border-bottom: 3px solid #1a5276; margin-bottom: 1.5rem; }
.header h1 { color: #1a5276; font-size: 1.4rem; }
.header p { color: #666; font-size: .85rem; margin-top: .3rem; }
.student-info { display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: .9rem; }
.student-info div { flex: 1; }
.student-info strong { color: #1a5276; }
.score-summary { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.score-card { flex: 1; text-align: center; padding: 1rem; border-radius: 8px; border: 2px solid; }
.score-card.twk { border-color: #e74c3c; }
.score-card.tiu { border-color: #f39c12; }
.score-card.tkp { border-color: #27ae60; }
.score-card .label { font-size: .75rem; color: #666; text-transform: uppercase; }
.score-card .value { font-size: 1.8rem; font-weight: bold; }
.score-card.twk .value { color: #e74c3c; }
.score-card.tiu .value { color: #f39c12; }
.score-card.tkp .value { color: #27ae60; }
.total-score { text-align: center; padding: 1rem; background: #1a5276; color: #fff; border-radius: 8px; margin-bottom: 1.5rem; }
.total-score .label { font-size: .85rem; opacity: .8; }
.total-score .value { font-size: 2rem; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: .85rem; }
th, td { padding: .5rem; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; color: #555; }
.progress-bar { width: 60px; height: 8px; background: #eee; border-radius: 4px; display: inline-block; vertical-align: middle; }
.progress-bar .fill { height: 100%; border-radius: 4px; }
.footer { text-align: center; padding-top: 1rem; border-top: 1px solid #ddd; font-size: .75rem; color: #999; }
@media print { .no-print { display: none; } }
.btn-print { display: inline-block; padding: .6rem 1.5rem; background: #1a5276; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: .9rem; text-decoration: none; }
</style>
</head>
<body>
<div class="no-print" style="text-align:right;margin-bottom:1rem">
    <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>

<div class="header">
    <h1>SKD CAT-BKN — Hasil Try Out</h1>
    <p>bimbel.bereng.info</p>
</div>

<div class="student-info">
    <div><strong>Nama:</strong> <?= $nama ?></div>
    <div><strong>Asal:</strong> <?= $sekolah ?></div>
    <div><strong>Tanggal:</strong> <?= $tanggal ?></div>
</div>

<div class="score-summary">
    <?php foreach ($scores as $s): ?>
    <div class="score-card <?= strtolower($s['subtes']) ?>">
        <div class="label"><?= $s['subtes'] ?></div>
        <div class="value"><?= (int)$s['skor'] ?></div>
        <div style="font-size:.75rem;color:#999;margin-top:.2rem">
            Benar: <?= $s['benar'] ?> | Salah: <?= $s['salah'] ?> | Kosong: <?= $s['kosong'] ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="total-score">
    <div class="label">SKOR TOTAL</div>
    <div class="value"><?= $totalSkor ?></div>
</div>

<h3 style="color:#1a5276;margin-bottom:.5rem">Analisis per Topik</h3>
<table>
<thead>
<tr><th>Subtes</th><th>Topik</th><th>Total Soal</th><th>Benar</th><th>Akurasi</th></tr>
</thead>
<tbody>
<?php foreach ($topics as $t): 
    $acc = $t['total'] > 0 ? round(($t['benar'] / $t['total']) * 100) : 0;
    $color = $acc >= 70 ? '#27ae60' : ($acc >= 50 ? '#f39c12' : '#e74c3c');
?>
<tr>
    <td><?= e($t['subtes']) ?></td>
    <td><?= e($t['topik']) ?></td>
    <td><?= $t['total'] ?></td>
    <td><?= $t['benar'] ?></td>
    <td>
        <span class="progress-bar"><span class="fill" style="width:<?= $acc ?>%;background:<?= $color ?>"></span></span>
        <?= $acc ?>%
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="footer">
    Dokumen ini di-generate otomatis oleh SKD CAT-BKN<br>
    bimbel.bereng.info — <?= date('d/m/Y H:i') ?>
</div>
</body>
</html>

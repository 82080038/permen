<?php
require '../config.php';
$baseUrl = $_ENV['BASE_URL'] ?? '/permen';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Fetch available topics for each subtes
$topicsBySubtes = $pdo->query("SELECT DISTINCT subtes, topik FROM questions WHERE is_active = 1 ORDER BY subtes, topik")->fetchAll(PDO::FETCH_GROUP);

// Fetch practice history
$historyStmt = $pdo->prepare("
    SELECT subtes, topik, jumlah_soal, tingkat_kesulitan, benar, salah, skor, waktu_mulai, waktu_selesai
    FROM personal_practice_sessions
    WHERE user_id = ?
    ORDER BY waktu_mulai DESC
    LIMIT 10
");
$historyStmt->execute([$userId]);
$practiceHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Jika subtes dipilih, buat session latihan dan redirect ke tryout
$subtes = $_GET['subtes'] ?? '';
if ($subtes && in_array($subtes, ['TWK','TIU','TKP'])) {
    $nama = "Latihan $subtes";
    $cfg = $pdo->prepare("SELECT durasi_menit, jumlah_soal, passing_grade FROM subtes_config WHERE subtes = ? AND is_active = 1");
    $cfg->execute([$subtes]);
    $c = $cfg->fetch();
    $durasi = (int)($c['durasi_menit'] ?? ($subtes === 'TWK' ? 30 : ($subtes === 'TIU' ? 35 : 45)));
    $jumlah = (int)($c['jumlah_soal'] ?? ($subtes === 'TWK' ? 30 : ($subtes === 'TIU' ? 35 : 45)));
    $passing = (int)($c['passing_grade'] ?? ($subtes === 'TWK' ? 65 : ($subtes === 'TIU' ? 80 : 126)));

    // Insert session minimal (tanpa kolom flat berulang)
    $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $nama]);
    $sessionId = $pdo->lastInsertId();

    // Insert ke tabel normalisasi session_subtes
    $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$sessionId, $subtes, $durasi, $jumlah, $passing]);

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
<?php $pageTitle = 'Latihan per Subtes — SKD CAT-BKN'; $activePage = 'latihan'; ?>
<?php require '../includes/navigation.php'; ?>
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

<!-- Passing Grade Standar BKN -->
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem">
    <h3 style="color:#1a5276;margin-bottom:.8rem;font-size:1.1rem;text-align:center">📊 Passing Grade Standar BKN 2024</h3>
    <p style="color:#555;font-size:.9rem;text-align:center;margin-bottom:1rem">
        Semua sekolah kedinasan menggunakan <strong>passing grade yang sama</strong> sesuai standar resmi BKN:
    </p>
    <div style="display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <div style="text-align:center;padding:1rem;background:#f8f9fa;border-radius:6px;min-width:100px">
            <div style="font-size:1.5rem;font-weight:bold;color:#e74c3c">TWK</div>
            <div style="font-size:1.2rem;color:#1a5276">65</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8f9fa;border-radius:6px;min-width:100px">
            <div style="font-size:1.5rem;font-weight:bold;color:#2980b9">TIU</div>
            <div style="font-size:1.2rem;color:#1a5276">80</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8f9fa;border-radius:6px;min-width:100px">
            <div style="font-size:1.5rem;font-weight:bold;color:#27ae60">TKP</div>
            <div style="font-size:1.2rem;color:#1a5276">156</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#eaf2f8;border-radius:6px;min-width:100px;border:2px solid #2980b9">
            <div style="font-size:1.5rem;font-weight:bold;color:#1a5276">Total</div>
            <div style="font-size:1.2rem;color:#1a5276">301</div>
        </div>
    </div>
    <p style="color:#777;font-size:.8rem;text-align:center">
        Sumber: BKN (Badan Kepegawaian Negara) - Seleksi Sekolah Kedinasan 2024
    </p>
</div>

<!-- Latihan Personal -->
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem;border:2px solid #8e44ad">
    <h3 style="color:#8e44ad;margin-bottom:.5rem;font-size:1.1rem;text-align:center">Latihan Personal — Generate Soal</h3>
    <p style="color:#555;font-size:.9rem;margin-bottom:1rem;text-align:center">Pilih topik spesifik, jumlah soal, dan tingkat kesulitan untuk latihan yang lebih terarah.</p>
    
    <form id="personalPracticeForm" style="max-width:500px;margin:0 auto;padding:1rem;background:#f8f9fa;border-radius:6px">
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:bold;margin-bottom:.3rem;font-size:.9rem">Subtes</label>
            <select id="practiceSubtes" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px" onchange="updateTopics()">
                <option value="">Pilih Subtes</option>
                <option value="TWK">TWK — Wawasan Kebangsaan</option>
                <option value="TIU">TIU — Intelegensia Umum</option>
                <option value="TKP">TKP — Karakteristik Pribadi</option>
            </select>
        </div>
        
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:bold;margin-bottom:.3rem;font-size:.9rem">Topik</label>
            <select id="practiceTopic" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
                <option value="">-- Pilih Subtes Terlebih Dahulu --</option>
            </select>
        </div>
        
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:bold;margin-bottom:.3rem;font-size:.9rem">Jumlah Soal</label>
            <select id="practiceCount" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
                <option value="5">5 soal</option>
                <option value="10" selected>10 soal</option>
                <option value="15">15 soal</option>
                <option value="20">20 soal</option>
                <option value="30">30 soal</option>
                <option value="50">50 soal</option>
            </select>
        </div>
        
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:bold;margin-bottom:.3rem;font-size:.9rem">Tingkat Kesulitan</label>
            <select id="practiceDifficulty" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
                <option value="mudah">Mudah</option>
                <option value="sedang" selected>Sedang</option>
                <option value="sulit">Sulit</option>
            </select>
        </div>
        
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:bold;margin-bottom:.3rem;font-size:.9rem">Timer</label>
            <select id="practiceTimer" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px">
                <option value="none">Tanpa Timer</option>
                <option value="30">30 detik per soal</option>
                <option value="60" selected>60 detik per soal</option>
                <option value="90">90 detik per soal</option>
                <option value="120">120 detik per soal</option>
                <option value="total">Total Timer (10 menit)</option>
                <option value="total15">Total Timer (15 menit)</option>
                <option value="total20">Total Timer (20 menit)</option>
            </select>
        </div>
        
        <button type="button" onclick="startPersonalPractice()" style="width:100%;background:#8e44ad;color:#fff;border:none;padding:.65rem 1.2rem;border-radius:5px;font-weight:bold;cursor:pointer;font-size:.9rem">Mulai Latihan Personal</button>
    </form>
    
    <div id="practiceResult" style="display:none;margin-top:1rem"></div>
</div>

<!-- Adaptive Learning Recommendations -->
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem;border:2px solid #f39c12">
    <h3 style="color:#f39c12;margin-bottom:.5rem;font-size:1.1rem;text-align:center">🎯 Rekomendasi Adaptive Learning</h3>
    <p style="color:#555;font-size:.9rem;margin-bottom:1rem;text-align:center">Sistem menganalisis performa Anda dan merekomendasikan latihan yang tepat.</p>
    
    <div id="adaptiveRecommendations" style="text-align:center">
        <p style="color:#666;font-size:.9rem">Memuat rekomendasi...</p>
    </div>
</div>

<!-- Riwayat Latihan Personal -->
<?php if (!empty($practiceHistory)): ?>
<div style="margin-top:1.5rem;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:1.2rem">
    <h3 style="color:#1a5276;margin-bottom:.8rem;font-size:1.1rem">📊 Riwayat Latihan Personal</h3>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.85rem">
            <thead>
                <tr style="background:#f8f9fa">
                    <th style="padding:.5rem;text-align:left">Subtes</th>
                    <th style="padding:.5rem;text-align:left">Topik</th>
                    <th style="padding:.5rem;text-align:center">Jumlah</th>
                    <th style="padding:.5rem;text-align:center">Kesulitan</th>
                    <th style="padding:.5rem;text-align:center">Benar</th>
                    <th style="padding:.5rem;text-align:center">Salah</th>
                    <th style="padding:.5rem;text-align:center">Skor</th>
                    <th style="padding:.5rem;text-align:left">Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($practiceHistory as $h): ?>
                <tr>
                    <td style="padding:.5rem"><?= e($h['subtes']) ?></td>
                    <td style="padding:.5rem"><?= e($h['topik'] ?? '-') ?></td>
                    <td style="padding:.5rem;text-align:center"><?= $h['jumlah_soal'] ?></td>
                    <td style="padding:.5rem;text-align:center"><?= e($h['tingkat_kesulitan']) ?></td>
                    <td style="padding:.5rem;text-align:center;color:#27ae60"><?= $h['benar'] ?></td>
                    <td style="padding:.5rem;text-align:center;color:#e74c3c"><?= $h['salah'] ?></td>
                    <td style="padding:.5rem;text-align:center;font-weight:bold"><?= $h['skor'] ?></td>
                    <td style="padding:.5rem;font-size:.75rem;color:#777"><?= date('d/m H:i', strtotime($h['waktu_mulai'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>
<div class="footer">
Latihan ini menggunakan skor sesuai ketentuan SKD. TWK & TIU (benar/salah), TKP (bobot 1–5).<br>
Dibangun berdasarkan KepmenPANRB No. 208/2025.
</div>
<script>
const topicsData = <?= json_encode($topicsBySubtes ?? []) ?>;

// Load adaptive recommendations on page load
document.addEventListener('DOMContentLoaded', loadAdaptiveRecommendations);

async function loadAdaptiveRecommendations() {
    const container = document.getElementById('adaptiveRecommendations');
    
    try {
        const response = await fetch('/api/get_adaptive_recommendations.php');
        const data = await response.json();
        
        if (!data.success) {
            container.innerHTML = '<p style="color:#e74c3c;font-size:.9rem">Gagal memuat rekomendasi.</p>';
            return;
        }
        
        const result = data.data;
        let html = '<p style="color:#555;font-size:.85rem;margin-bottom:1rem">' + result.message + '</p>';
        
        if (result.has_data && !empty(result.weak_topics)) {
            html += '<div style="text-align:left;margin-bottom:1rem">';
            html += '<h4 style="color:#e74c3c;font-size:.9rem;margin-bottom:.5rem">⚠️ Topik yang Perlu Diperbaiki:</h4>';
            result.weak_topics.forEach(t => {
                html += '<div style="padding:.5rem;background:#fff3cd;border-left:3px solid #f39c12;margin-bottom:.3rem;border-radius:4px">';
                html += '<strong>' + t.subtes + ' — ' + t.topik + '</strong><br>';
                html += '<span style="font-size:.8rem;color:#666">Akurasi: ' + t.accuracy + '% (' + t.total_attempts + ' soal)</span>';
                html += '</div>';
            });
            html += '</div>';
        }
        
        html += '<h4 style="color:#27ae60;font-size:.9rem;margin-bottom:.5rem">💡 Rekomendasi Latihan:</h4>';
        html += '<div style="display:flex;flex-direction:column;gap:.5rem">';
        
        result.recommendations.forEach((rec, i) => {
            const priorityColor = rec.priority === 'high' ? '#e74c3c' : '#27ae60';
            const priorityLabel = rec.priority === 'high' ? 'Prioritas Tinggi' : 'Prioritas Sedang';
            
            html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem;background:#f8f9fa;border-radius:4px;border-left:4px solid ' + priorityColor + '">';
            html += '<div style="text-align:left">';
            html += '<strong>' + rec.subtes;
            if (rec.topik) html += ' — ' + rec.topik;
            html += '</strong>';
            if (rec.accuracy !== null) {
                html += '<br><span style="font-size:.75rem;color:#666">Akurasi: ' + rec.accuracy + '%</span>';
            }
            html += '</div>';
            html += '<button onclick="startAdaptivePractice(\'' + rec.subtes + '\', \'' + (rec.topik || '') + '\')" style="background:#f39c12;color:#fff;border:none;padding:.4rem .8rem;border-radius:4px;cursor:pointer;font-size:.8rem">Latih</button>';
            html += '</div>';
        });
        
        html += '</div>';
        
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p style="color:#e74c3c;font-size:.9rem">Error: ' + e.message + '</p>';
    }
}

async function startAdaptivePractice(subtes, topik) {
    const resultDiv = document.getElementById('practiceResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<p style="color:#666;text-align:center">Memuat soal adaptif...</p>';
    
    // Set form values
    document.getElementById('practiceSubtes').value = subtes;
    updateTopics();
    if (topik) {
        document.getElementById('practiceTopic').value = topik;
    }
    document.getElementById('practiceCount').value = '10';
    document.getElementById('practiceDifficulty').value = 'sedang';
    
    // Start practice
    await startPersonalPractice();
}

function updateTopics() {
    const subtes = document.getElementById('practiceSubtes').value;
    const topicSelect = document.getElementById('practiceTopic');
    
    topicSelect.innerHTML = '<option value="">-- Pilih Topik --</option>';
    
    if (subtes && topicsData[subtes]) {
        topicsData[subtes].forEach(topic => {
            const opt = document.createElement('option');
            opt.value = topic.topik;
            opt.textContent = topic.topik;
            topicSelect.appendChild(opt);
        });
    }
}

async function startPersonalPractice() {
    const subtes = document.getElementById('practiceSubtes').value;
    const topik = document.getElementById('practiceTopic').value;
    const jumlah = document.getElementById('practiceCount').value;
    const kesulitan = document.getElementById('practiceDifficulty').value;
    const timer = document.getElementById('practiceTimer').value;
    const resultDiv = document.getElementById('practiceResult');
    
    if (!subtes) {
        alert('Pilih subtes terlebih dahulu');
        return;
    }
    
    if (!topik) {
        alert('Pilih topik terlebih dahulu');
        return;
    }
    
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<p style="color:#666;text-align:center">Memuat soal...</p>';
    
    try {
        const response = await fetch('/api/generate_user_soal.php?subtes=' + encodeURIComponent(subtes) + '&topik=' + encodeURIComponent(topik) + '&jumlah=' + encodeURIComponent(jumlah));
        const data = await response.json();
        
        if (data.error) {
            resultDiv.innerHTML = '<p style="color:#e74c3c;text-align:center">' + data.error + '</p>';
            return;
        }
        
        const result = data.data || data;
        let html = '<h4 style="color:#8e44ad;margin-bottom:.8rem">Latihan Personal — ' + subtes + ' (' + topik + ')</h4>';
        html += '<div style="font-size:.85rem;color:#666;margin-bottom:1rem">' + result.jumlah + ' soal, tingkat: ' + kesulitan;
        
        if (timer !== 'none') {
            html += ', timer: ' + getTimerLabel(timer);
        }
        html += '</div>';
        
        // Timer display
        if (timer !== 'none') {
            html += '<div id="timerDisplay" style="background:#eaf2f8;padding:.5rem 1rem;border-radius:4px;margin-bottom:1rem;text-align:center;font-weight:bold;color:#1a5276;font-size:1.1rem">⏱️ Timer: --:--</div>';
        }
        
        html += '<form id="personalPracticeForm">';
        
        result.soal.forEach((q, i) => {
            html += '<div class="question-container" data-index="' + i + '" style="border:1px solid #ddd;border-radius:6px;padding:.8rem;margin-bottom:.6rem;background:#fff">';
            html += '<strong>Soal ' + (i + 1) + '</strong><div style="margin:.3rem 0 .5rem;font-size:.9rem">' + escapeHtml(q.pertanyaan) + '</div>';
            ['A', 'B', 'C', 'D', 'E'].forEach(opt => {
                const val = q['pilihan_' + opt.toLowerCase()];
                html += '<label style="display:block;font-size:.85rem;margin:.2rem 0;cursor:pointer">';
                html += '<input type="radio" name="personal_soal_' + i + '" value="' + opt + '" data-key="' + q.jawaban_benar + '" style="margin-right:.3rem" onchange="onQuestionAnswered(' + i + ')">';
                html += '<strong>' + opt + '.</strong> ' + escapeHtml(val);
                html += '</label>';
            });
            html += '<div class="personal-pembahasan-' + i + '" style="display:none;margin-top:.5rem;padding:.5rem;background:#fffbea;border-left:3px solid #f1c40f;border-radius:4px;font-size:.85rem">';
            html += '<strong>Pembahasan:</strong> ' + escapeHtml(q.pembahasan) + '<br>';
            html += '<strong style="color:#1e8449">Tips & Trick:</strong> ' + escapeHtml(q.tips_trick);
            if (q.related_links && q.related_links.length > 0) {
                html += '<br><strong style="color:#1a5276">Pelajari lebih lanjut:</strong> ';
                q.related_links.forEach(l => {
                    html += '<a href="' + escapeHtml(l.url) + '" target="_blank" style="color:#2980b9;text-decoration:none;background:#eaf2f8;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem;font-size:.8rem">' + escapeHtml(l.label) + '</a>';
                });
            }
            html += '</div>';
            html += '</div>';
        });
        
        html += '<button type="button" onclick="checkPersonalPractice(\'' + subtes + '\', \'' + topik + '\', ' + jumlah + ', \'' + kesulitan + '\')" style="background:#8e44ad;color:#fff;border:none;padding:.6rem 1.2rem;border-radius:5px;cursor:pointer;font-size:.9rem" aria-label="Periksa jawaban latihan personal">Periksa Jawaban</button>';
        html += '</form>';
        
        resultDiv.innerHTML = html;
        
        // Initialize timer if enabled
        if (timer !== 'none') {
            initTimer(timer, result.jumlah);
        }
    } catch (e) {
        resultDiv.innerHTML = '<p style="color:#e74c3c;text-align:center">Error: ' + e.message + '</p>';
    }
}

function getTimerLabel(timer) {
    const labels = {
        '30': '30 detik/soal',
        '60': '60 detik/soal',
        '90': '90 detik/soal',
        '120': '120 detik/soal',
        'total': '10 menit total',
        'total15': '15 menit total',
        'total20': '20 menit total'
    };
    return labels[timer] || timer;
}

let timerInterval = null;
let currentQuestionIndex = 0;
let timerSeconds = 0;
let totalTimerSeconds = 0;
let timerMode = '';

function initTimer(timer, totalQuestions) {
    const timerDisplay = document.getElementById('timerDisplay');
    if (!timerDisplay) return;
    
    timerMode = timer;
    
    if (timer.startsWith('total')) {
        // Total timer mode
        const minutes = timer === 'total' ? 10 : (timer === 'total15' ? 15 : 20);
        totalTimerSeconds = minutes * 60;
        updateTimerDisplay(totalTimerSeconds);
        
        timerInterval = setInterval(() => {
            totalTimerSeconds--;
            updateTimerDisplay(totalTimerSeconds);
            
            if (totalTimerSeconds <= 0) {
                clearInterval(timerInterval);
                timerDisplay.style.background = '#f8d7da';
                timerDisplay.style.color = '#721c24';
                timerDisplay.textContent = '⏱️ Waktu Habis!';
                disableAllQuestions();
                checkPersonalPractice(document.getElementById('practiceSubtes').value, document.getElementById('practiceTopic').value, document.getElementById('practiceCount').value, document.getElementById('practiceDifficulty').value);
            }
        }, 1000);
    } else {
        // Per-question timer mode
        timerSeconds = parseInt(timer);
        currentQuestionIndex = 0;
        updateTimerDisplay(timerSeconds);
        
        timerInterval = setInterval(() => {
            timerSeconds--;
            updateTimerDisplay(timerSeconds);
            
            if (timerSeconds <= 0) {
                // Move to next question
                moveToNextQuestion();
            }
        }, 1000);
    }
}

function updateTimerDisplay(seconds) {
    const timerDisplay = document.getElementById('timerDisplay');
    if (!timerDisplay) return;
    
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    const display = mins + ':' + (secs < 10 ? '0' : '') + secs;
    
    if (timerMode.startsWith('total')) {
        timerDisplay.textContent = '⏱️ Total Timer: ' + display;
    } else {
        timerDisplay.textContent = '⏱️ Soal ' + (currentQuestionIndex + 1) + ': ' + display;
    }
    
    // Warning color
    if (seconds <= 10) {
        timerDisplay.style.background = '#f8d7da';
        timerDisplay.style.color = '#721c24';
    } else if (seconds <= 30) {
        timerDisplay.style.background = '#fff3cd';
        timerDisplay.style.color = '#856404';
    }
}

function onQuestionAnswered(index) {
    if (timerMode.startsWith('total')) {
        // In total timer mode, just continue
        return;
    }
    
    // In per-question mode, move to next question after answering
    if (index === currentQuestionIndex) {
        moveToNextQuestion();
    }
}

function moveToNextQuestion() {
    const questions = document.querySelectorAll('.question-container');
    
    // Hide current question
    if (currentQuestionIndex < questions.length) {
        questions[currentQuestionIndex].style.display = 'none';
    }
    
    // Move to next
    currentQuestionIndex++;
    
    if (currentQuestionIndex >= questions.length) {
        // All questions done
        clearInterval(timerInterval);
        const timerDisplay = document.getElementById('timerDisplay');
        if (timerDisplay) {
            timerDisplay.textContent = '⏱️ Semua soal selesai!';
            timerDisplay.style.background = '#d4edda';
            timerDisplay.style.color = '#155724';
        }
        return;
    }
    
    // Show next question
    questions[currentQuestionIndex].style.display = 'block';
    
    // Reset timer
    timerSeconds = parseInt(timerMode);
    updateTimerDisplay(timerSeconds);
}

function disableAllQuestions() {
    const inputs = document.querySelectorAll('#personalPracticeForm input[type="radio"]');
    inputs.forEach(input => input.disabled = true);
}

async function checkPersonalPractice(subtes, topik, jumlah, kesulitan) {
    // Stop timer if running
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    
    const form = document.getElementById('personalPracticeForm');
    const inputs = form.querySelectorAll('input[type="radio"]:checked');
    let benar = 0, total = 0;
    
    form.querySelectorAll('input[type="radio"]').forEach(r => {
        const name = r.name;
        const idx = parseInt(name.replace('personal_soal_', ''));
        const key = r.getAttribute('data-key');
        const pembDiv = form.querySelector('.personal-pembahasan-' + idx);
        if (pembDiv) pembDiv.style.display = 'block';
        if (r.checked) {
            total++;
            if (r.value === key) {
                benar++;
                r.parentElement.style.color = '#27ae60';
                r.parentElement.style.fontWeight = 'bold';
            } else {
                r.parentElement.style.color = '#e74c3c';
            }
        }
    });
    
    form.querySelectorAll('input[type="radio"]').forEach(r => r.disabled = true);
    
    const salah = total - benar;
    const skor = benar * 5; // Simple scoring: 5 points per correct answer
    
    // Calculate time used
    const timer = document.getElementById('practiceTimer').value;
    let timerUsed = 0;
    if (timerMode.startsWith('total')) {
        const totalSeconds = timer === 'total' ? 600 : (timer === 'total15' ? 900 : 1200);
        timerUsed = totalSeconds - totalTimerSeconds;
    } else if (timerMode !== 'none' && timerMode !== '') {
        // Per-question mode: calculate based on questions answered
        timerUsed = (currentQuestionIndex * parseInt(timerMode)) + (parseInt(timerMode) - timerSeconds);
    }
    
    // Save to server
    const formData = new FormData();
    formData.append('subtes', subtes);
    formData.append('topik', topik);
    formData.append('jumlah_soal', jumlah);
    formData.append('tingkat_kesulitan', kesulitan);
    formData.append('benar', benar);
    formData.append('salah', salah);
    formData.append('skor', skor);
    formData.append('timer_mode', timerMode || 'none');
    formData.append('timer_used', timerUsed);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    
    try {
        await fetch('/api/save_practice_session.php', {
            method: 'POST',
            body: formData
        });
    } catch (e) {
        console.error('Failed to save practice session:', e);
    }
    
    const resultDiv = document.createElement('div');
    resultDiv.style.cssText = 'background:#eaf2f8;padding:.8rem;border-radius:6px;margin-top:1rem;font-size:.9rem';
    resultDiv.innerHTML = '<strong>Hasil:</strong> Benar ' + benar + ' / ' + total + ' dijawab. Skor: ' + skor + '.';
    if (timerMode !== 'none') {
        const mins = Math.floor(timerUsed / 60);
        const secs = timerUsed % 60;
        resultDiv.innerHTML += ' Waktu: ' + mins + 'm ' + secs + 's.';
    }
    if (benar === total && total > 0) resultDiv.innerHTML += '<span style="color:#27ae60;font-weight:bold"> Sempurna! 🎉</span>';
    else if (benar >= total / 2) resultDiv.innerHTML += '<span style="color:#f39c12"> Bagus, tingkatkan lagi!</span>';
    else resultDiv.innerHTML += '<span style="color:#e74c3c"> Perlu latihan lebih banyak.</span>';
    form.appendChild(resultDiv);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
</body>
</html>

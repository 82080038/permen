<?php
require '../config.php';
require '../helpers.php';
$subtes = $_GET['subtes'] ?? 'TWK';
$subtes = strtoupper($subtes);
$valid = ['TWK','TIU','TKP'];
if (!in_array($subtes, $valid)) $subtes = 'TWK';
$file = "../content/materi_" . strtolower($subtes) . ".php";
$materi = file_exists($file) ? require $file : [];

// Get user's bookmarked materials and progress
$userId = $_SESSION['user_id'] ?? null;
$bookmarkedIds = [];
$progressData = [];

if ($userId) {
    $stmt = $pdo->prepare("SELECT materi_id FROM materi_bookmarks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $bookmarkedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT materi_id, progress_percent FROM materi_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $progressData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Materi <?= $subtes ?> — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.nav{background:#2980b9;display:flex;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;padding:.7rem 1rem;display:block;font-size:.9rem;min-height:44px;display:flex;align-items:center}
.nav a:hover,.nav a.active{background:#1a5276;font-weight:bold}
.container{max-width:1000px;margin:1.5rem auto;padding:0 1rem}
.card{background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;overflow:hidden}
.card-header{background:#eaf2f8;padding:.9rem 1rem;font-weight:bold;font-size:1rem;color:#1a5276;cursor:pointer;display:flex;justify-content:space-between;align-items:center;min-height:44px}
.card-body{padding:1rem;display:none}.card-body.active{display:block}
.card-body h2{color:#1a5276;font-size:1.05rem;margin:.8rem 0 .4rem}
.card-body h3{color:#2c3e50;font-size:.95rem;margin:.6rem 0 .3rem}
.card-body ul{margin-left:1.2rem;margin-bottom:.6rem}
.card-body li{margin:.2rem 0}
.card-body table{border-collapse:collapse;width:100%;margin:.6rem 0;font-size:.9rem;display:block;overflow-x:auto;white-space:nowrap}
.card-body th,.card-body td{border:1px solid #ddd;padding:.4rem .5rem;text-align:left}
.card-body th{background:#eaf2f8}
.toggle-icon{font-size:1.1rem}
.bookmark-btn{background:none;border:none;cursor:pointer;font-size:1.2rem;padding:.3rem;min-width:44px;min-height:44px;color:#999;transition:color .2s}
.bookmark-btn:hover{color:#e74c3c}
.bookmark-btn.bookmarked{color:#e74c3c}
.progress-bar{height:4px;background:#e9ecef;border-radius:2px;margin-top:.5rem;overflow:hidden}
.progress-fill{height:100%;background:#27ae60;transition:width .3s ease}
@media(max-width:480px){
.container{margin:1rem auto;padding:0 .8rem}
.card-header{font-size:.95rem;padding:.8rem}
.card-body{padding:.8rem}
}
.skip-link:focus{top:0}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Materi Pembelajaran SKD'; $activePage = 'latihan'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="nav">
<a href="?subtes=TWK" class="<?= $subtes=='TWK'?'active':'' ?>">TWK</a>
<a href="?subtes=TIU" class="<?= $subtes=='TIU'?'active':'' ?>">TIU</a>
<a href="?subtes=TKP" class="<?= $subtes=='TKP'?'active':'' ?>">TKP</a>
</div>
<div class="container" id="main-content">
<div style="margin-bottom:1rem">
<input type="text" id="searchMateri" placeholder="Cari materi..." style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:5px;font-size:.9rem" oninput="filterMateri()">
</div>
<div id="materiContainer">
<?php foreach ($materi as $index => $item): 
    $materiId = $index + 1; // Simple ID based on array index
    $isBookmarked = in_array($materiId, $bookmarkedIds);
    $progress = $progressData[$materiId] ?? 0;
?>
<div class="card" data-judul="<?= htmlspecialchars(strtolower($item['judul'])) ?>" data-materi-id="<?= $materiId ?>">
<div class="card-header" onclick="toggle(this)">
<span><?= htmlspecialchars($item['judul']) ?></span>
<div style="display:flex;align-items:center;gap:.5rem">
    <?php if ($userId): ?>
    <button class="bookmark-btn <?= $isBookmarked ? 'bookmarked' : '' ?>" onclick="event.stopPropagation(); toggleBookmark(<?= $materiId ?>, this)" aria-label="Bookmark materi" title="Bookmark materi">
        <?= $isBookmarked ? '❤️' : '🤍' ?>
    </button>
    <?php endif; ?>
    <span class="toggle-icon">+</span>
</div>
</div>
<div class="card-body" onscroll="trackProgress(this, <?= $materiId ?>)">
    <?= $item['konten'] /* Konten dari file internal aplikasi, dipercaya */ ?>
    <?php if ($userId): ?>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
    </div>
    <div style="font-size:.75rem;color:#777;margin-top:.3rem">Progress: <?= $progress ?>%</div>
    
    <!-- Mini Quiz Section -->
    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #e9ecef">
        <button class="btn" style="background:#27ae60;font-size:.85rem;padding:.5rem 1rem" onclick="startMiniQuiz('<?= htmlspecialchars($item['id'] ?? '') ?>', '<?= $subtes ?>', this)" aria-label="Mulai mini quiz">
            📝 Mini Quiz: Uji Pemahaman
        </button>
        <div id="quizContainer-<?= $materiId ?>" style="display:none;margin-top:1rem"></div>
    </div>
    <?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<div id="noResults" style="display:none;text-align:center;padding:2rem;color:#777">
Tidak ada materi yang cocok dengan pencarian.
</div>
</div>

<script>
function filterMateri() {
    const searchTerm = document.getElementById('searchMateri').value.toLowerCase();
    const cards = document.querySelectorAll('#materiContainer .card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const judul = card.getAttribute('data-judul') || '';
        if (judul.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

async function toggleBookmark(materiId, button) {
    const formData = new FormData();
    formData.append('materi_id', materiId);
    formData.append('csrf_token', '<?= csrfToken() ?>');
    
    try {
        const response = await fetch('/api/toggle_bookmark.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            button.classList.toggle('bookmarked');
            button.textContent = result.bookmarked ? '❤️' : '🤍';
        }
    } catch (error) {
        console.error('Failed to toggle bookmark:', error);
    }
}

let progressTimeouts = {};

function trackProgress(cardBody, materiId) {
    clearTimeout(progressTimeouts[materiId]);
    
    progressTimeouts[materiId] = setTimeout(() => {
        const scrollTop = cardBody.scrollTop;
        const scrollHeight = cardBody.scrollHeight;
        const clientHeight = cardBody.clientHeight;
        
        const progressPercent = Math.min(100, Math.round((scrollTop / (scrollHeight - clientHeight)) * 100));
        
        // Update progress bar visually
        const progressFill = cardBody.querySelector('.progress-fill');
        const progressText = cardBody.querySelector('.progress-bar + div');
        if (progressFill) progressFill.style.width = progressPercent + '%';
        if (progressText) progressText.textContent = 'Progress: ' + progressPercent + '%';
        
        // Save to server
        const formData = new FormData();
        formData.append('materi_id', materiId);
        formData.append('progress_percent', progressPercent);
        formData.append('last_position', scrollTop);
        formData.append('csrf_token', '<?= csrfToken() ?>');
        
        fetch('/api/update_materi_progress.php', {
            method: 'POST',
            body: formData
        }).catch(error => console.error('Failed to update progress:', error));
    }, 500);
}

// Topic mapping for mini quiz
const topicMapping = {
    'twk_nasionalisme': 'Nasionalisme',
    'twk_integritas': 'Integritas',
    'twk_bela_negara': 'Bela Negara',
    'twk_pilar_negara': 'Pilar Negara',
    'twk_bahasa_indonesia': 'Bahasa Indonesia',
    'tiu_analogi': 'Analogi',
    'tiu_silogisme': 'Silogisme',
    'tiu_analitis': 'Analitis',
    'tiu_berhitung': 'Berhitung',
    'tiu_deret_angka': 'Deret Angka',
    'tiu_perbandingan': 'Perbandingan',
    'tiu_soal_cerita': 'Soal Cerita',
    'tkp_pelayanan_publik': 'Pelayanan Publik',
    'tkp_jejaring_kerja': 'Jejaring Kerja',
    'tkp_sosial_budaya': 'Sosial Budaya',
    'tkp_teknologi_informasi': 'Teknologi Informasi',
    'tkp_profesionalisme': 'Profesionalisme'
};

async function startMiniQuiz(materiId, subtes, button) {
    const container = document.getElementById('quizContainer-' + materiId.replace(/^[^-]+-/, ''));
    if (!container) return;
    
    const topik = topicMapping[materiId] || materiId;
    
    button.disabled = true;
    button.textContent = '⏳ Memuat...';
    container.style.display = 'block';
    container.innerHTML = '<p style="color:#666;font-size:.9rem">Memuat soal...</p>';
    
    try {
        const response = await fetch('/api/get_mini_quiz.php?subtes=' + encodeURIComponent(subtes) + '&topik=' + encodeURIComponent(topik) + '&jumlah=3');
        const data = await response.json();
        
        if (!data.success) {
            container.innerHTML = '<p style="color:#e74c3c;font-size:.9rem">' + (data.error || 'Gagal memuat soal') + '</p>';
            button.disabled = false;
            button.textContent = '📝 Mini Quiz: Uji Pemahaman';
            return;
        }
        
        renderMiniQuiz(data.data, container, button);
    } catch (error) {
        container.innerHTML = '<p style="color:#e74c3c;font-size:.9rem">Error: ' + error.message + '</p>';
        button.disabled = false;
        button.textContent = '📝 Mini Quiz: Uji Pemahaman';
    }
}

function renderMiniQuiz(quizData, container, button) {
    let html = '<h4 style="color:#1a5276;margin-bottom:.8rem">Mini Quiz — ' + quizData.topik + '</h4>';
    html += '<div style="font-size:.85rem;color:#666;margin-bottom:1rem">' + quizData.jumlah + ' soal. Jawab untuk menguji pemahaman Anda.</div>';
    html += '<form id="miniQuizForm">';
    
    quizData.soal.forEach((q, i) => {
        html += '<div style="border:1px solid #ddd;border-radius:6px;padding:.8rem;margin-bottom:.8rem;background:#fff">';
        html += '<strong>Soal ' + (i + 1) + '</strong>';
        html += '<div style="margin:.3rem 0 .5rem;font-size:.9rem">' + escapeHtml(q.pertanyaan) + '</div>';
        
        ['A', 'B', 'C', 'D', 'E'].forEach(opt => {
            const val = q['pilihan_' + opt.toLowerCase()];
            html += '<label style="display:block;font-size:.85rem;margin:.2rem 0;cursor:pointer;padding:.2rem;border-radius:4px;transition:background .2s">';
            html += '<input type="radio" name="mini_soal_' + i + '" value="' + opt + '" data-correct="' + q.jawaban_benar + '" style="margin-right:.3rem">';
            html += '<strong>' + opt + '.</strong> ' + escapeHtml(val);
            html += '</label>';
        });
        
        html += '<div class="mini-pembahasan-' + i + '" style="display:none;margin-top:.5rem;padding:.5rem;background:#fffbea;border-left:3px solid #f1c40f;border-radius:4px;font-size:.85rem">';
        html += '<strong>Pembahasan:</strong> ' + escapeHtml(q.pembahasan) + '<br>';
        html += '<strong style="color:#1e8449">Tips:</strong> ' + escapeHtml(q.tips_trick);
        if (q.related_links && q.related_links.length > 0) {
            html += '<br><strong style="color:#1a5276">Pelajari lebih lanjut:</strong> ';
            q.related_links.forEach(l => {
                html += '<a href="' + escapeHtml(l.url) + '" target="_blank" style="color:#2980b9;text-decoration:none;background:#eaf2f8;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem;font-size:.8rem">' + escapeHtml(l.label) + '</a>';
            });
        }
        html += '</div>';
        html += '</div>';
    });
    
    html += '<button type="button" onclick="checkMiniQuiz()" style="background:#2980b9;color:#fff;border:none;padding:.6rem 1.2rem;border-radius:5px;cursor:pointer;font-size:.9rem;margin-top:.5rem" aria-label="Periksa jawaban mini quiz">Periksa Jawaban</button>';
    html += '</form>';
    
    container.innerHTML = html;
}

function checkMiniQuiz() {
    const form = document.getElementById('miniQuizForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input[type="radio"]:checked');
    let benar = 0, total = 0;
    
    form.querySelectorAll('input[type="radio"]').forEach(r => {
        const name = r.name;
        const idx = parseInt(name.replace('mini_soal_', ''));
        const correct = r.getAttribute('data-correct');
        const pembDiv = form.querySelector('.mini-pembahasan-' + idx);
        
        if (pembDiv) pembDiv.style.display = 'block';
        
        if (r.checked) {
            total++;
            if (r.value === correct) {
                benar++;
                r.parentElement.style.background = '#d4edda';
                r.parentElement.style.color = '#155724';
                r.parentElement.style.fontWeight = 'bold';
            } else {
                r.parentElement.style.background = '#f8d7da';
                r.parentElement.style.color = '#721c24';
            }
        }
    });
    
    // Disable all radios after checking
    form.querySelectorAll('input[type="radio"]').forEach(r => r.disabled = true);
    
    // Add result summary
    const resultDiv = document.createElement('div');
    resultDiv.style.cssText = 'background:#eaf2f8;padding:.8rem;border-radius:6px;margin-top:1rem;font-size:.9rem';
    resultDiv.innerHTML = '<strong>Hasil:</strong> Benar ' + benar + ' / ' + total + ' dijawab. ';
    
    if (benar === total && total > 0) {
        resultDiv.innerHTML += '<span style="color:#27ae60;font-weight:bold">Sempurna! 🎉</span>';
    } else if (benar >= total / 2) {
        resultDiv.innerHTML += '<span style="color:#f39c12">Bagus, tingkatkan lagi!</span>';
    } else {
        resultDiv.innerHTML += '<span style="color:#e74c3c">Perlu baca materi lagi.</span>';
    }
    
    form.appendChild(resultDiv);
}
</script>

<!-- UJI PEMAHAMAN -->
<div class="card" style="margin-top:1.5rem;border:2px solid #27ae60">
    <div class="card-header" style="background:#d4edda;color:#155724" onclick="toggle(this)">
        <span>Uji Pemahaman — Generate Soal Latihan</span>
        <span class="toggle-icon">+</span>
    </div>
    <div class="card-body" id="ujiPemahamanBody">
        <p style="font-size:.9rem;color:#555;margin-bottom:.8rem">Pilih topik yang ingin Anda latih. Aplikasi akan generate soal baru secara otomatis untuk menguji pemahaman Anda.</p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.8rem">
            <select id="latihTopik" style="padding:.5rem;border:1px solid #ddd;border-radius:5px;flex:1;min-width:150px">
                <option value="">Pilih topik...</option>
            </select>
            <select id="latihJumlah" style="padding:.5rem;border:1px solid #ddd;border-radius:5px;width:100px">
                <option value="5">5 soal</option>
                <option value="10">10 soal</option>
                <option value="15">15 soal</option>
            </select>
            <button class="btn" style="background:#27ae60;color:#fff;border:none;padding:.5rem 1rem;border-radius:5px;cursor:pointer;font-size:.9rem" onclick="generateLatihan()" aria-label="Generate soal latihan">Generate Soal</button>
        </div>
        <div id="latihanContainer" style="display:none;margin-top:1rem"></div>
    </div>
</div>

<base href="/">
<script src="assets/app.js"></script>
<script>
const topikBySubtes = {
    'TWK': ['Nasionalisme','Integritas','Bela Negara','Pilar Negara','Bahasa Indonesia'],
    'TIU': ['Analogi','Silogisme','Analitis','Berhitung','Deret Angka','Perbandingan','Soal Cerita'],
    'TKP': ['Pelayanan Publik','Jejaring Kerja','Sosial Budaya','Teknologi Informasi','Profesionalisme']
};

// Make subtes available globally for generateLatihan function
const subtes = '<?= $subtes ?>';

// Wait for DOM to be ready before populating dropdown
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('latihTopik');
    topikBySubtes[subtes].forEach((t, idx) => {
        const opt = document.createElement('option');
        opt.value = t; opt.textContent = t;
        if (idx === 0) opt.selected = true; // Auto-select first topik
        sel.appendChild(opt);
    });
});

async function generateLatihan(){
    const topik = document.getElementById('latihTopik').value;
    const jumlah = document.getElementById('latihJumlah').value;
    const container = document.getElementById('latihanContainer');
    if(!topik){ alert('Pilih topik terlebih dahulu'); return; }

    container.style.display = 'block';
    container.innerHTML = '<p style="color:#666">Generating soal...</p>';

    try {
        const res = await fetch('/api/generate_user_soal.php?subtes=' + encodeURIComponent(subtes)
            + '&topik=' + encodeURIComponent(topik)
            + '&jumlah=' + encodeURIComponent(jumlah));
        const data = await res.json();

        if(data.error){
            container.innerHTML = '<p style="color:#e74c3c">' + data.error + '</p>';
            return;
        }

        const result = data.data || data;
        let html = '<h3 style="color:#1a5276;margin-bottom:.8rem">Latihan ' + subtes + ' — ' + topik + '</h3>';
        html += '<div style="font-size:.85rem;color:#666;margin-bottom:.8rem">' + result.jumlah + ' soal generated. Pilih jawaban, lalu klik "Periksa Jawaban".</div>';
        html += '<form id="latihanForm">';
        result.soal.forEach((q,i) => {
            html += '<div style="border:1px solid #ddd;border-radius:6px;padding:.8rem;margin-bottom:.6rem;background:#fff">';
            html += '<strong>Soal ' + (i+1) + '</strong><div style="margin:.3rem 0 .5rem;font-size:.9rem">' + escapeHtml(q.pertanyaan) + '</div>';
            ['A','B','C','D','E'].forEach(opt => {
                const val = q['pilihan_' + opt.toLowerCase()];
                html += '<label style="display:block;font-size:.85rem;margin:.2rem 0;cursor:pointer">';
                html += '<input type="radio" name="soal_' + i + '" value="' + opt + '" data-key="' + q.jawaban_benar + '" style="margin-right:.3rem">';
                html += '<strong>' + opt + '.</strong> ' + escapeHtml(val);
                html += '</label>';
            });
            html += '<div class="pembahasan-' + i + '" style="display:none;margin-top:.5rem;padding:.5rem;background:#fffbea;border-left:3px solid #f1c40f;border-radius:4px;font-size:.85rem">';
            html += '<strong>Pembahasan:</strong> ' + escapeHtml(q.pembahasan) + '<br>';
            html += '<strong style="color:#1e8449">Tips & Trick:</strong> ' + escapeHtml(q.tips_trick) + '<br>';
            if(q.related_links && q.related_links.length > 0){
                html += '<strong style="color:#1a5276">Pelajari lebih lanjut:</strong> ';
                q.related_links.forEach(l => {
                    html += '<a href="' + escapeHtml(l.url) + '" target="_blank" style="color:#2980b9;text-decoration:none;background:#eaf2f8;padding:.1rem .3rem;border-radius:3px;margin-right:.2rem;font-size:.8rem">' + escapeHtml(l.label) + '</a>';
                });
            }
            html += '</div>';
            html += '</div>';
        });
        html += '<button type="button" onclick="periksaJawaban()" style="background:#2980b9;color:#fff;border:none;padding:.6rem 1.2rem;border-radius:5px;cursor:pointer;font-size:.9rem" aria-label="Periksa jawaban latihan">Periksa Jawaban</button>';
        html += '</form>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<p style="color:#e74c3c">Error: ' + e.message + '</p>';
    }
}

function periksaJawaban(){
    const form = document.getElementById('latihanForm');
    const inputs = form.querySelectorAll('input[type="radio"]:checked');
    let benar = 0, total = 0;
    form.querySelectorAll('input[type="radio"]').forEach(r => {
        const name = r.name;
        const idx = parseInt(name.replace('soal_',''));
        const key = r.getAttribute('data-key');
        const pembDiv = document.querySelector('.pembahasan-' + idx);
        if(pembDiv) pembDiv.style.display = 'block';
        if(r.checked) {
            total++;
            if(r.value === key) {
                benar++;
                r.parentElement.style.color = '#27ae60';
                r.parentElement.style.fontWeight = 'bold';
            } else {
                r.parentElement.style.color = '#e74c3c';
            }
        }
    });
    // Disable all radios after checking
    form.querySelectorAll('input[type="radio"]').forEach(r => r.disabled = true);

    const container = document.getElementById('latihanContainer');
    const resultDiv = document.createElement('div');
    resultDiv.style.cssText = 'background:#eaf2f8;padding:.8rem;border-radius:6px;margin-top:1rem;font-size:.9rem';
    resultDiv.innerHTML = '<strong>Hasil:</strong> Benar ' + benar + ' / ' + total + ' dijawab (dari ' + form.querySelectorAll('input[type="radio"]').length/5 + ' soal). ';
    if(benar === total && total > 0) resultDiv.innerHTML += '<span style="color:#27ae60;font-weight:bold">Sempurna!</span>';
    else if(benar >= total/2) resultDiv.innerHTML += '<span style="color:#f39c12">Bagus, tingkatkan lagi!</span>';
    else resultDiv.innerHTML += '<span style="color:#e74c3c">Perlu latihan lebih banyak.</span>';
    container.appendChild(resultDiv);
}

function escapeHtml(text){
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
</body>
</html>

<?php
require '../config.php';
require '../helpers.php';

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');

// Cek apakah sudah ada sesi hari ini
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_sessions WHERE user_id = ? AND quiz_date = ?");
$stmt->execute([$userId, $today]);
$session = $stmt->fetch();
$hasCompleted = $session && $session['status'] === 'selesai';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Quiz - SKD CAT-BKN</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
.quiz-container{max-width:900px;margin:2rem auto;padding:0 1rem}
.quiz-header{background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;padding:1.5rem;border-radius:8px;margin-bottom:1.5rem;text-align:center}
.quiz-header h1{font-size:1.3rem;margin-bottom:.5rem}
.quiz-header p{opacity:.9;font-size:.9rem}
.quiz-info{display:flex;justify-content:center;gap:2rem;margin-top:1rem;flex-wrap:wrap}
.quiz-info span{background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem}
.soal-box{background:#fff;border-radius:8px;padding:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:1rem}
.soal-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:2px solid #f0f0f0;flex-wrap:wrap;gap:.5rem}
.soal-nav h2{font-size:1rem;color:#1a5276}
.nav-grid{display:flex;gap:.4rem;flex-wrap:wrap}
.nav-btn{width:36px;height:36px;border:2px solid #ddd;background:#fff;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:bold;transition:all .2s}
.nav-btn:hover{border-color:#2980b9}
.nav-btn.answered{background:#27ae60;border-color:#27ae60;color:#fff}
.nav-btn.ragu{background:#f39c12;border-color:#f39c12;color:#fff}
.nav-btn.active{border-color:#2980b9;background:#2980b9;color:#fff}
.pertanyaan{font-size:1.1rem;margin-bottom:1.5rem;line-height:1.6;color:#333}
.pertanyaan img{max-width:100%;height:auto;border-radius:4px;margin:1rem 0}
.options{display:flex;flex-direction:column;gap:.8rem}
.option{display:flex;align-items:center;gap:.8rem;padding:1rem;background:#f8f9fa;border:2px solid #e9ecef;border-radius:8px;cursor:pointer;transition:all .2s;min-height:44px}
.option:hover{background:#e3f2fd;border-color:#2980b9}
.option.selected{background:#d4edda;border-color:#27ae60}
.option input[type=radio]{width:20px;height:20px;cursor:pointer}
.option-label{font-weight:bold;color:#2980b9;min-width:24px}
.option-text{flex:1}
.ragu-btn{background:#fff;border:2px solid #f39c12;color:#f39c12;padding:.6rem 1.2rem;border-radius:6px;cursor:pointer;font-size:.9rem;transition:all .2s}
.ragu-btn:hover{background:#f39c12;color:#fff}
.ragu-btn.active{background:#f39c12;color:#fff}
.action-btns{display:flex;justify-content:space-between;margin-top:1.5rem;gap:1rem;flex-wrap:wrap}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.8rem 1.5rem;border-radius:6px;border:none;cursor:pointer;font-size:.9rem;text-decoration:none;transition:all .2s;min-height:44px}
.btn:hover{background:#1a5276}
.btn.success{background:#27ae60}
.btn.success:hover{background:#1e8449}
.btn:disabled{background:#95a5a6;cursor:not-allowed}
.timer{font-size:1.2rem;font-weight:bold;color:#fff;background:rgba(0,0,0,.2);padding:.5rem 1rem;border-radius:6px}
.completed-box{background:#d4edda;border:2px solid #27ae60;border-radius:8px;padding:2rem;text-align:center}
.completed-box h2{color:#155724;margin-bottom:1rem}
.completed-box .score{font-size:3rem;font-weight:bold;color:#27ae60;margin:1rem 0}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1rem;margin:1.5rem 0}
.stat-item{background:#fff;padding:1rem;border-radius:6px;text-align:center}
.stat-label{font-size:.85rem;color:#666;margin-bottom:.3rem}
.stat-value{font-size:1.5rem;font-weight:bold;color:#1a5276}
@media(max-width:600px){
.quiz-info{gap:1rem}
.nav-btn{width:32px;height:32px;font-size:.8rem}
.option{padding:.8rem}
}
</style>
</head>
<body>

<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>

<?php $pageTitle = 'Daily Quiz — SKD CAT-BKN'; $activePage = 'daily_quiz'; ?>
<?php require '../includes/navigation.php'; ?>

<div class="quiz-header">
<h1>Daily Quiz Hari Ini</h1>
<p>10 soal pilihan ganda (4 TWK, 3 TIU, 3 TKP)</p>
<div class="quiz-info">
<span id="timerDisplay" class="timer">--:--</span>
<span>Soal <strong id="currentNum">1</strong>/10</span>
<span id="progressText">0/10 dijawab</span>
</div>
</div>

<div class="quiz-container">

<?php if ($hasCompleted): ?>
<!-- Sudah Selesai Hari Ini -->
<div class="completed-box">
<h2>Anda sudah menyelesaikan Daily Quiz hari ini!</h2>
<div class="score"><?= (int)$session['nilai_total'] ?></div>
<p>Nilai Total</p>
<div class="stats-grid">
<div class="stat-item">
<div class="stat-label">Benar</div>
<div class="stat-value" style="color:#27ae60"><?= (int)$session['benar'] ?></div>
</div>
<div class="stat-item">
<div class="stat-label">Salah</div>
<div class="stat-value" style="color:#e74c3c"><?= (int)$session['salah'] ?></div>
</div>
<div class="stat-item">
<div class="stat-label">Kosong</div>
<div class="stat-value" style="color:#95a5a6"><?= (int)$session['kosong'] ?></div>
</div>
</div>
<a href="user_dashboard.php" class="btn">Kembali ke Dashboard</a>
</div>

<?php else: ?>
<!-- Quiz Berjalan -->
<div id="quizArea">
<div class="soal-nav">
<h2>Navigasi Soal</h2>
<div class="nav-grid" id="navGrid"></div>
</div>

<div class="soal-box">
<div class="pertanyaan" id="pertanyaan">Memuat soal...</div>
<div class="options" id="options"></div>

<div style="margin-top:1rem">
<button class="ragu-btn" id="btnRagu" onclick="toggleRagu()">Tandai Ragu-ragu</button>
</div>
</div>

<div class="action-btns">
<button class="btn" id="btnPrev" onclick="prevSoal()" disabled>← Sebelumnya</button>
<button class="btn success" id="btnNext" onclick="nextSoal()">Berikutnya →</button>
<button class="btn success" id="btnFinish" onclick="finishQuiz()" style="display:none;background:#e74c3c">✓ Selesai</button>
</div>
</div>

<!-- Hasil Quiz -->
<div id="resultArea" style="display:none">
<div class="completed-box">
<h2>Daily Quiz Selesai!</h2>
<div class="score" id="resultScore">0</div>
<p>Nilai Total</p>
<div class="stats-grid" id="resultStats"></div>
<a href="user_dashboard.php" class="btn">Kembali ke Dashboard</a>
<a href="daily_quiz.php" class="btn" style="margin-left:.5rem;background:#f39c12">Coba Lagi Besok</a>
</div>
</div>
<?php endif; ?>

</div>

<script>
<?php if (!$hasCompleted): ?>
let soal = [];
let currentIndex = 0;
let sessionId = 0;
let jawaban = {}; // {question_id: {jawaban, ragu}}
let startTime = Date.now();

// Load soal saat halaman dimuat
async function loadQuiz() {
    try {
        const res = await fetch('../api/get_daily_quiz.php');
        const data = await res.json();
        
        if (!data.success) {
            alert(data.error || 'Gagal memuat soal');
            return;
        }
        
        sessionId = data.session.id;
        soal = data.soal;
        
        // Restore jawaban yang sudah tersimpan
        soal.forEach(s => {
            if (s.jawaban_user) {
                jawaban[s.question_id] = { jawaban: s.jawaban_user, ragu: s.is_ragu };
            }
        });
        
        renderNav();
        renderSoal();
        updateProgress();
        startTimer();
    } catch (e) {
        alert('Gagal memuat soal. Silakan refresh halaman.');
    }
}

function renderNav() {
    const nav = document.getElementById('navGrid');
    nav.innerHTML = soal.map((s, i) => {
        const j = jawaban[s.question_id];
        let cls = 'nav-btn';
        if (i === currentIndex) cls += ' active';
        else if (j?.ragu) cls += ' ragu';
        else if (j?.jawaban) cls += ' answered';
        return `<button class="${cls}" onclick="goToSoal(${i})">${i+1}</button>`;
    }).join('');
}

function renderSoal() {
    const s = soal[currentIndex];
    document.getElementById('currentNum').textContent = currentIndex + 1;
    
    let html = `<strong>Soal ${currentIndex + 1}</strong> (${s.subtes})<br><br>${s.pertanyaan}`;
    if (s.image_url) {
        html += `<br><img src="${s.image_url}" alt="Gambar soal">`;
    }
    document.getElementById('pertanyaan').innerHTML = html;
    
    const options = ['A', 'B', 'C', 'D', 'E'];
    const currentJawaban = jawaban[s.question_id]?.jawaban;
    
    document.getElementById('options').innerHTML = options.map(opt => {
        const text = s[`pilihan_${opt.toLowerCase()}`];
        const selected = currentJawaban === opt ? 'selected' : '';
        return `
            <label class="option ${selected}" onclick="selectJawaban('${opt}')">
                <input type="radio" name="jawaban" value="${opt}" ${selected ? 'checked' : ''}>
                <span class="option-label">${opt}.</span>
                <span class="option-text">${text}</span>
            </label>
        `;
    }).join('');
    
    // Update ragu button
    const isRagu = jawaban[s.question_id]?.ragu || false;
    document.getElementById('btnRagu').classList.toggle('active', isRagu);
    
    // Update nav buttons
    renderNav();
    
    // Update action buttons
    document.getElementById('btnPrev').disabled = currentIndex === 0;
    const isLast = currentIndex === soal.length - 1;
    document.getElementById('btnNext').style.display = isLast ? 'none' : 'inline-block';
    document.getElementById('btnFinish').style.display = isLast ? 'inline-block' : 'none';
}

function selectJawaban(opt) {
    const s = soal[currentIndex];
    const isRagu = jawaban[s.question_id]?.ragu || false;
    jawaban[s.question_id] = { jawaban: opt, ragu: isRagu };
    
    // Submit ke server
    submitAnswer(s.question_id, opt, isRagu);
    
    renderSoal();
    updateProgress();
    
    // Auto advance untuk soal non-TKP
    if (s.subtes !== 'TKP' && currentIndex < soal.length - 1) {
        setTimeout(() => nextSoal(), 300);
    }
}

async function submitAnswer(qid, jawab, ragu) {
    try {
        await fetch('../api/submit_daily_answer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                question_id: qid,
                jawaban: jawab,
                is_ragu: ragu ? 1 : 0
            })
        });
    } catch (e) {
        console.error('Gagal submit:', e);
    }
}

function toggleRagu() {
    const s = soal[currentIndex];
    const current = jawaban[s.question_id] || {};
    const newRagu = !current.ragu;
    jawaban[s.question_id] = { jawaban: current.jawaban || null, ragu: newRagu };
    
    submitAnswer(s.question_id, current.jawaban || '', newRagu);
    renderSoal();
}

function goToSoal(index) {
    currentIndex = index;
    renderSoal();
}

function nextSoal() {
    if (currentIndex < soal.length - 1) {
        currentIndex++;
        renderSoal();
    }
}

function prevSoal() {
    if (currentIndex > 0) {
        currentIndex--;
        renderSoal();
    }
}

function updateProgress() {
    const answered = Object.values(jawaban).filter(j => j.jawaban).length;
    document.getElementById('progressText').textContent = `${answered}/${soal.length} dijawab`;
}

function startTimer() {
    const timerDisplay = document.getElementById('timerDisplay');
    setInterval(() => {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const secs = (elapsed % 60).toString().padStart(2, '0');
        timerDisplay.textContent = `${mins}:${secs}`;
    }, 1000);
}

async function finishQuiz() {
    if (!confirm('Yakin ingin menyelesaikan Daily Quiz?')) return;
    
    try {
        const res = await fetch('../api/finish_daily_quiz.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId })
        });
        const data = await res.json();
        
        if (data.success) {
            showResults(data.hasil);
        } else {
            alert(data.error || 'Gagal menyelesaikan quiz');
        }
    } catch (e) {
        alert('Gagal menyelesaikan quiz');
    }
}

function showResults(hasil) {
    document.getElementById('quizArea').style.display = 'none';
    document.getElementById('resultArea').style.display = 'block';
    document.querySelector('.quiz-header h1').textContent = 'Daily Quiz Selesai!';
    
    document.getElementById('resultScore').textContent = hasil.nilai_total;
    document.getElementById('resultStats').innerHTML = `
        <div class="stat-item"><div class="stat-label">Benar</div><div class="stat-value" style="color:#27ae60">${hasil.benar}</div></div>
        <div class="stat-item"><div class="stat-label">Salah</div><div class="stat-value" style="color:#e74c3c">${hasil.salah}</div></div>
        <div class="stat-item"><div class="stat-label">Kosong</div><div class="stat-value" style="color:#95a5a6">${hasil.kosong}</div></div>
        <div class="stat-item"><div class="stat-label">TWK</div><div class="stat-value">${hasil.nilai_twk}</div></div>
        <div class="stat-item"><div class="stat-label">TIU</div><div class="stat-value">${hasil.nilai_tiu}</div></div>
        <div class="stat-item"><div class="stat-label">TKP</div><div class="stat-value">${hasil.nilai_tkp}</div></div>
    `;
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (document.getElementById('quizArea').style.display === 'none') return;
    
    const key = e.key.toUpperCase();
    if (['A', 'B', 'C', 'D', 'E'].includes(key)) {
        selectJawaban(key);
    } else if (key === 'ARROWLEFT') {
        prevSoal();
    } else if (key === 'ARROWRIGHT') {
        nextSoal();
    } else if (key === 'M') {
        toggleRagu();
    }
});

// Load quiz
loadQuiz();
<?php endif; ?>
</script>

</body>
</html>

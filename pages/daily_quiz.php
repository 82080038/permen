<?php
require '../config.php';
require '../helpers.php';

// Guard: user harus login (peserta only)
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');

// Fetch user streak info
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$streakInfo = $stmt->fetch();
if (!$streakInfo) {
    $streakInfo = ['current_streak' => 0, 'longest_streak' => 0, 'total_quizzes' => 0];
}

// Fetch user achievements
$stmt = $pdo->prepare("SELECT achievement_type, achievement_name FROM user_achievements WHERE user_id = ? ORDER BY achieved_at DESC");
$stmt->execute([$userId]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's current difficulty
$stmt = $pdo->prepare("SELECT current_difficulty, consecutive_high_scores, consecutive_low_scores FROM user_quiz_difficulty WHERE user_id = ?");
$stmt->execute([$userId]);
$userDifficulty = $stmt->fetch();
if (!$userDifficulty) {
    $userDifficulty = ['current_difficulty' => 'sedang', 'consecutive_high_scores' => 0, 'consecutive_low_scores' => 0];
}

// Fetch today's scheduled topic
$dayOfWeek = (int)date('w');
$stmt = $pdo->prepare("SELECT * FROM daily_quiz_topic_schedule WHERE day_of_week = ?");
$stmt->execute([$dayOfWeek]);
$todaySchedule = $stmt->fetch();

// Fetch full weekly schedule
$weeklySchedule = $pdo->query("SELECT * FROM daily_quiz_topic_schedule ORDER BY day_of_week")->fetchAll(PDO::FETCH_ASSOC);

$dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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
<base href="<?php echo $baseUrl ?? '/'; ?>">
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/'; ?>/assets/style.css">
<style>
.quiz-container{max-width:900px;margin:2rem auto;padding:0 1rem}
.quiz-header{background:linear-gradient(135deg,#1a5276,#2980b9);color:#fff;padding:1.5rem;border-radius:8px;margin-bottom:1.5rem;text-align:center}
.quiz-header h1{font-size:1.3rem;margin-bottom:.5rem}
#imgZoomModal.show{display:flex}
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
<?php if ($todaySchedule): ?>
<p>📅 Topik Hari Ini: <strong><?= e($todaySchedule['subtes']) ?> — <?= e($todaySchedule['topik']) ?></strong></p>
<p style="font-size:.85rem;opacity:.8"><?= e($todaySchedule['description']) ?></p>
<?php else: ?>
<p>10 soal pilihan ganda (4 TWK, 3 TIU, 3 TKP)</p>
<?php endif; ?>
<div class="quiz-info">
<span id="timerDisplay" class="timer">--:--</span>
<span>Soal <strong id="currentNum">1</strong>/10</span>
<span id="progressText">0/10 dijawab</span>
</div>
<div style="margin-top:1rem;display:flex;justify-content:center;gap:1rem;flex-wrap:wrap">
    <span style="background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem">
        🔥 Streak: <strong><?= $streakInfo['current_streak'] ?></strong> hari
    </span>
    <span style="background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem">
        🏆 Terbaik: <strong><?= $streakInfo['longest_streak'] ?></strong> hari
    </span>
    <span style="background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem">
        📊 Total: <strong><?= $streakInfo['total_quizzes'] ?></strong> quiz
    </span>
    <span style="background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.85rem">
        ⚡ Tingkat: <strong><?= ucfirst($userDifficulty['current_difficulty']) ?></strong>
    </span>
</div>
<?php if ($userDifficulty['consecutive_high_scores'] > 0 || $userDifficulty['consecutive_low_scores'] > 0): ?>
<div style="margin-top:.5rem;text-align:center;font-size:.75rem;color:rgba(255,255,255,.7)">
    <?php if ($userDifficulty['consecutive_high_scores'] > 0): ?>
    <?= $userDifficulty['consecutive_high_scores'] ?>x skor tinggi berturut-turut → Tingkat akan naik
    <?php elseif ($userDifficulty['consecutive_low_scores'] > 0): ?>
    <?= $userDifficulty['consecutive_low_scores'] ?>x skor rendah berturut-turut → Tingkat akan turun
    <?php endif; ?>
</div>
<?php endif; ?>
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

<?php if (!empty($achievements)): ?>
<div style="margin-top:1.5rem">
<h3 style="color:#1a5276;margin-bottom:.8rem;text-align:center">🏅 Achievement Anda</h3>
<div style="display:flex;justify-content:center;gap:.5rem;flex-wrap:wrap">
    <?php foreach ($achievements as $ach): ?>
    <span style="background:#fff3cd;padding:.5rem 1rem;border-radius:20px;font-size:.85rem;border:1px solid #f39c12">
        <?= e($ach['achievement_name']) ?>
    </span>
    <?php endforeach; ?>
</div>
</div>
<?php endif; ?>

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

<!-- Daily Quiz Leaderboard -->
<div style="margin-top:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:1.5rem">
    <h3 style="color:#1a5276;margin-bottom:1rem;text-align:center">🏆 Leaderboard Daily Quiz</h3>
    <div style="display:flex;gap:1rem;justify-content:center;margin-bottom:1rem">
        <button onclick="loadLeaderboard('streak')" id="btnStreak" style="background:#2980b9;color:#fff;border:none;padding:.5rem 1rem;border-radius:4px;cursor:pointer;font-size:.85rem">Streak Tertinggi</button>
        <button onclick="loadLeaderboard('total')" id="btnTotal" style="background:#95a5a6;color:#fff;border:none;padding:.5rem 1rem;border-radius:4px;cursor:pointer;font-size:.85rem">Total Quiz Terbanyak</button>
    </div>
    <div id="leaderboardContent" style="overflow-x:auto">
        <p style="text-align:center;color:#666;font-size:.9rem">Memuat leaderboard...</p>
    </div>
</div>

<!-- Weekly Topic Schedule -->
<div style="margin-top:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:1.5rem">
    <h3 style="color:#1a5276;margin-bottom:1rem;text-align:center">📅 Jadwal Topik Mingguan</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
        <?php foreach ($weeklySchedule as $schedule): ?>
        <div style="padding:1rem;background:#f8f9fa;border-radius:6px;border-left:4px solid <?= $schedule['day_of_week'] === $dayOfWeek ? '#27ae60' : '#95a5a6' ?>">
            <div style="font-weight:bold;color:#1a5276"><?= $dayNames[$schedule['day_of_week']] ?></div>
            <div style="font-size:.9rem;margin-top:.3rem">
                <strong><?= e($schedule['subtes']) ?></strong> — <?= e($schedule['topik']) ?>
            </div>
            <div style="font-size:.8rem;color:#666;margin-top:.2rem"><?= e($schedule['description']) ?></div>
            <?php if ($schedule['day_of_week'] === $dayOfWeek): ?>
            <div style="margin-top:.5rem;color:#27ae60;font-size:.8rem;font-weight:bold">📍 Hari Ini</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div>

<script>
const BASE_URL = '<?php echo $baseUrl ?? '/permen'; ?>';
<?php if (!$hasCompleted): ?>
let soal = [];
let currentIndex = 0;
let sessionId = 0;
let jawaban = {}; // {question_id: {jawaban, ragu}}
let startTime = Date.now();

// XSS protection function
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load soal saat halaman dimuat
async function loadQuiz() {
    try {
        const res = await fetch(BASE_URL + '/api/get_daily_quiz.php');
        const data = await res.json();
        
        if (!data.success) {
            alert(data.error || 'Gagal memuat soal');
            return;
        }
        
        sessionId = data.session.id;
        soal = data.soal;
        
        // Restore jawaban yang sudah tersimpan
        soal.forEach(s => {
            if (s.jawaban) {
                jawaban[s.question_id] = { jawaban: s.jawaban, ragu: s.is_ragu };
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
        await fetch(BASE_URL + '/api/submit_daily_answer.php', {
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
        const res = await fetch(BASE_URL + '/api/finish_daily_quiz.php', {
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
document.addEventListener('DOMContentLoaded', loadQuiz);
<?php endif; ?>

// Load leaderboard (always available)
document.addEventListener('DOMContentLoaded', loadLeaderboard('streak'));

async function loadLeaderboard(type) {
    const container = document.getElementById('leaderboardContent');
    const btnStreak = document.getElementById('btnStreak');
    const btnTotal = document.getElementById('btnTotal');
    
    // Update button styles
    if (type === 'streak') {
        btnStreak.style.background = '#2980b9';
        btnTotal.style.background = '#95a5a6';
    } else {
        btnStreak.style.background = '#95a5a6';
        btnTotal.style.background = '#2980b9';
    }
    
    container.innerHTML = '<p style="text-align:center;color:#666;font-size:.9rem">Memuat leaderboard...</p>';
    
    try {
        const res = await fetch(BASE_URL + '/api/get_daily_quiz_leaderboard.php');
        const data = await res.json();
        
        if (!data.success) {
            container.innerHTML = '<p style="text-align:center;color:#e74c3c;font-size:.9rem">Gagal memuat leaderboard</p>';
            return;
        }
        
        const leaderboard = type === 'streak' ? data.data.streak_leaderboard : data.data.total_leaderboard;
        const userRank = data.data.user_rank;
        
        let html = '<table style="width:100%;border-collapse:collapse;font-size:.85rem">';
        html += '<thead><tr style="background:#f8f9fa">';
        html += '<th style="padding:.5rem;text-align:center">#</th>';
        html += '<th style="padding:.5rem;text-align:left">Nama</th>';
        html += '<th style="padding:.5rem;text-align:center">Streak</th>';
        html += '<th style="padding:.5rem;text-align:center">Terbaik</th>';
        html += '<th style="padding:.5rem;text-align:center">Total Quiz</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        leaderboard.forEach((user, i) => {
            const rank = i + 1;
            const rankStyle = rank === 1 ? 'background:#fff3cd;font-weight:bold' : (rank === 2 ? 'background:#e9ecef;font-weight:bold' : (rank === 3 ? 'background:#dee2e6;font-weight:bold' : ''));
            html += '<tr style="' + rankStyle + '">';
            html += '<td style="padding:.5rem;text-align:center">' + rank + '</td>';
            html += '<td style="padding:.5rem">' + escapeHtml(user.nama) + '</td>';
            html += '<td style="padding:.5rem;text-align:center">🔥 ' + user.current_streak + '</td>';
            html += '<td style="padding:.5rem;text-align:center">🏆 ' + user.longest_streak + '</td>';
            html += '<td style="padding:.5rem;text-align:center">📊 ' + user.total_quizzes + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        if (userRank) {
            html += '<div style="margin-top:1rem;padding:.8rem;background:#eaf2f8;border-radius:4px;text-align:center;font-size:.85rem">';
            html += '<strong>Peringkat Anda:</strong> #' + userRank;
            html += '</div>';
        }
        
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p style="text-align:center;color:#e74c3c;font-size:.9rem">Error: ' + e.message + '</p>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<!-- Image Zoom Modal -->
<div id="imgZoomModal" onclick="closeZoom()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.85);z-index:2000;justify-content:center;align-items:center;flex-direction:column;padding:1rem">
    <button class="zoom-close" onclick="event.stopPropagation();closeZoom()" style="position:absolute;top:1rem;right:1rem;background:#fff;color:#333;border:none;padding:.5rem 1rem;border-radius:4px;cursor:pointer;font-size:1rem">Tutup</button>
    <img id="zoomImg" src="" alt="Zoomed" style="max-width:95%;max-height:80vh;object-fit:contain">
    <div class="zoom-hint" style="color:#fff;font-size:.85rem;margin-top:.5rem">Ketik gambar atau di luar area untuk menutup</div>
</div>

<script>
function openZoom(src){
    const modal = document.getElementById('imgZoomModal');
    const img = document.getElementById('zoomImg');
    img.src = src;
    modal.style.display = 'flex';
}
function closeZoom(){
    document.getElementById('imgZoomModal').style.display = 'none';
}
</script>

</body>
</html>

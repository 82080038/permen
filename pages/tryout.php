<?php
require '../config.php';
require '../helpers.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

// Cek session aktif user, jika tidak ada buat baru
// Jika session_id dari GET (mode latihan), gunakan session tersebut
$sessionId = 0;
if (!empty($_GET['session_id'])) {
    $sessionId = (int)$_GET['session_id'];
    // Validasi kepemilikan
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$sessionId, $userId]);
    if (!$stmt->fetchColumn()) {
        $sessionId = 0; // invalid, buat baru
    }
}

if (!$sessionId) {
    $stmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id = ? AND status = 'berjalan' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $sessionId = $existing;
    } else {
        $stmt = $pdo->prepare("INSERT INTO tryout_sessions (user_id, nama, waktu_mulai) VALUES (?, 'Try Out SKD', NOW())");
        $stmt->execute([$userId]);
        $sessionId = $pdo->lastInsertId();
        // Insert ke tabel normalisasi session_subtes dari konfigurasi global
        $cfg = $pdo->query("SELECT subtes, durasi_menit, jumlah_soal, passing_grade, urutan FROM subtes_config WHERE aktif = 1 ORDER BY urutan");
        $ins = $pdo->prepare("INSERT INTO session_subtes (session_id, subtes, durasi_menit, jumlah_soal, passing_grade, urutan) VALUES (?,?,?,?,?,?)");
        foreach ($cfg as $c) {
            $ins->execute([$sessionId, $c['subtes'], $c['durasi_menit'], $c['jumlah_soal'], $c['passing_grade'], $c['urutan']]);
        }
    }
}

// Ambil data session untuk timer dari session_subtes (normalisasi)
$stmt = $pdo->prepare("SELECT subtes, durasi_menit FROM session_subtes WHERE session_id = ? ORDER BY urutan");
$stmt->execute([$sessionId]);
$subtesRows = $stmt->fetchAll();
$durasiMap = ['TWK'=>30,'TIU'=>35,'TKP'=>45];
foreach ($subtesRows as $row) {
    $durasiMap[$row['subtes']] = (int)$row['durasi_menit'];
}
$totalDuration = array_sum($durasiMap);

// Ambil waktu mulai per subtes untuk timer per subtes
$stmt = $pdo->prepare("SELECT subtes, UNIX_TIMESTAMP(waktu_mulai_subtes) as start_ts, durasi_menit FROM session_subtes WHERE session_id = ? ORDER BY urutan");
$stmt->execute([$sessionId]);
$subtesTimers = [];
$currentSubtes = '';
foreach ($stmt as $row) {
    $sub = $row['subtes'];
    $dur = (int)$row['durasi_menit'];
    $start = $row['start_ts'] ? (int)$row['start_ts'] : 0;
    $elapsed = $start ? time() - $start : 0;
    $remaining = max(0, $dur * 60 - $elapsed);
    $subtesTimers[$sub] = ['durasi'=>$dur, 'start'=>$start, 'remaining'=>$remaining];
    if (!$currentSubtes && $start) $currentSubtes = $sub; // subtes yang sudah dimulai = aktif
    if (!$currentSubtes) $currentSubtes = $sub; // fallback ke subtes pertama
}
// Kalau tidak ada subtes yang dimulai, set subtes pertama
$firstSubtes = array_key_first($subtesTimers) ?: 'TWK';
if (!$currentSubtes) $currentSubtes = $firstSubtes;

// Ensure subtesTimers is never empty (fallback to defaults)
if (empty($subtesTimers)) {
    $subtesTimers = [
        'TWK' => ['durasi' => 30, 'start' => 0, 'remaining' => 1800],
        'TIU' => ['durasi' => 35, 'start' => 0, 'remaining' => 2100],
        'TKP' => ['durasi' => 45, 'start' => 0, 'remaining' => 2700]
    ];
    $currentSubtes = 'TWK';
}

// Debug: log variables before rendering (commented out to prevent any output)
// error_log("tryout.php: sessionId=$sessionId, currentSubtes=$currentSubtes, subtesTimers=" . json_encode($subtesTimers));

// Hitung total remaining untuk display utama (sum semua subtes yang belum/belum selesai)
$remainingSeconds = 0;
foreach ($subtesTimers as $sub => $t) {
    $remainingSeconds += $t['remaining'];
}
$timerM = str_pad(floor($remainingSeconds / 60), 2, '0', STR_PAD_LEFT);
$timerS = str_pad($remainingSeconds % 60, 2, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Aplikasi Try Out SKD CAT-BKN untuk persiapan masuk Sekolah Kedinasan.">
<meta name="theme-color" content="#1a5276">
<title>Try Out SKD — CAT BKN</title>
<style>
:root{--bg-body:#f0f2f5;--bg-sidebar:#fff;--bg-content:#fff;--bg-passage:#f0f7ff;--bg-pembahasan:#fffbea;--bg-question:#fafafa;--bg-option-hover:#eaf2f8;--bg-option-selected:#d4edda;--bg-number:#f8f9fa;--text-main:#222;--text-muted:#555;--text-passage:#333;--text-heading:#1a5276;--text-info:#555;--img-bg:#fff;--img-border:#ddd;--border-light:#ddd;--border-passage:#b8d4f0;--header-bg:#1a5276;--nav-bg:#2980b9;--timer-bg:#e74c3c}
[data-theme="dark"]{--bg-body:#1a1a2e;--bg-sidebar:#16213e;--bg-content:#16213e;--bg-passage:#1a1a3e;--bg-pembahasan:#2a2a4e;--bg-question:#1e1e3f;--bg-option-hover:#1a5276;--bg-option-selected:#27ae60;--bg-number:#16213e;--text-main:#f0f0f0;--text-muted:#b0b0b0;--text-passage:#e0e0e0;--text-heading:#74b9ff;--text-info:#b0b0b0;--img-bg:#1a1a2e;--img-border:#555;--border-light:#555;--border-passage:#555;--header-bg:#0f3460;--nav-bg:#1a5276;--timer-bg:#c0392b}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg-body);color:var(--text-main);-webkit-text-size-adjust:100%;transition:background .2s,color .2s}
.topbar{background:var(--header-bg);color:#fff;padding:.5rem 1rem;font-size:.8rem;display:flex;flex-wrap:wrap;gap:.4rem .6rem;align-items:center}
.topbar a{color:#fff;text-decoration:none;margin-right:.6rem;min-height:44px;display:flex;align-items:center;font-size:.8rem}
.header{background:var(--header-bg);color:#fff;padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1rem}.timer{font-size:1rem;font-weight:bold;background:var(--timer-bg);padding:.25rem .6rem;border-radius:4px;white-space:nowrap}
.nav{background:var(--nav-bg);padding:.4rem 1rem;color:#fff;font-size:.85rem}
.main{display:flex;max-width:1200px;margin:.8rem auto;gap:.8rem;padding:0 .8rem;flex-wrap:wrap}
.sidebar{width:240px;background:var(--bg-sidebar);border-radius:6px;padding:.8rem;box-shadow:0 1px 4px rgba(0,0,0,.08);height:fit-content}
.sidebar h3{font-size:.9rem;margin-bottom:.5rem;color:var(--text-heading)}
.number-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.35rem}
.number-grid button{border:1px solid var(--border-light);background:var(--bg-number);color:var(--text-main);padding:.35rem;border-radius:4px;cursor:pointer;font-size:.75rem;min-height:36px}
.number-grid button.answered{background:#27ae60;color:#fff;border-color:#27ae60}
.number-grid button.marked{background:#f1c40f;color:#222;border-color:#f1c40f}
.number-grid button.active{background:#2980b9;color:#fff;border-color:#2980b9}
.content{flex:1;background:var(--bg-content);border-radius:6px;padding:1rem;box-shadow:0 1px 4px rgba(0,0,0,.08);min-height:300px}
.question{font-size:1rem;margin-bottom:1rem;line-height:1.6}
.question-scrollable{max-height:220px;overflow-y:auto;padding-right:.5rem;border:1px solid var(--border-light);border-radius:4px;padding:.6rem;background:var(--bg-question)}
.question-image{display:block;margin:.6rem 0;max-width:100%;max-height:200px;border:1px solid var(--img-border);border-radius:4px;padding:.3rem;background:var(--img-bg)}
.passage-box{background:var(--bg-passage);border:1px solid var(--border-passage);border-radius:6px;padding:.8rem;margin-bottom:1rem}
.passage-judul{font-weight:bold;color:var(--text-heading);font-size:.95rem;margin-bottom:.4rem}
.passage-bacaan{font-size:.9rem;color:var(--text-passage);line-height:1.7;max-height:250px;overflow-y:auto;padding-right:.5rem}
.passage-info{font-size:.8rem;color:var(--text-info);margin-bottom:.5rem;font-style:italic}
.options label{display:block;padding:.9rem 1rem;border:1px solid var(--border-light);border-radius:6px;margin-bottom:.6rem;cursor:pointer;transition:.2s;font-size:.95rem;min-height:44px}
.options label:hover{background:var(--bg-option-hover)}
.options input{margin-right:.6rem;min-width:18px;min-height:18px}
.options label.selected{background:var(--bg-option-selected);border-color:#27ae60}
.options input{min-width:20px;min-height:20px}
.btn-group{margin-top:1.2rem;display:flex;gap:.5rem;flex-wrap:wrap}
.btn{background:#2980b9;color:#fff;border:none;padding:.65rem 1rem;border-radius:5px;cursor:pointer;font-size:.9rem;min-height:44px;min-width:44px}
.btn.finish{background:#e74c3c}.btn:disabled{opacity:.5;cursor:not-allowed}
.pembahasan{margin-top:1rem;padding:.8rem;background:var(--bg-pembahasan);border-left:4px solid #f1c40f;border-radius:4px;font-size:.9rem}
#sidebarToggle{display:none}
.theme-toggle{background:transparent;border:1px solid rgba(255,255,255,.4);color:#fff;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:.8rem;margin-left:.5rem}
.font-toggle{background:transparent;border:1px solid rgba(255,255,255,.4);color:#fff;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:.8rem;margin-left:.3rem}
/* Font sizes */
[data-font-size="small"] .question{font-size:.85rem}
[data-font-size="small"] .options label{font-size:.82rem}
[data-font-size="large"] .question{font-size:1.15rem}
[data-font-size="large"] .options label{font-size:1.1rem}
/* Image zoom modal */
#imgZoomModal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.85);z-index:2000;justify-content:center;align-items:center;flex-direction:column;padding:1rem}
#imgZoomModal.show{display:flex}
#imgZoomModal img{max-width:95%;max-height:80vh;object-fit:contain}
#imgZoomModal .zoom-close{position:absolute;top:1rem;right:1rem;background:#fff;color:#333;border:none;padding:.5rem 1rem;border-radius:4px;cursor:pointer;font-size:1rem}
#imgZoomModal .zoom-hint{color:#fff;font-size:.85rem;margin-top:.5rem}
@media(max-width:600px){
.main{flex-direction:column;padding:0 .6rem}
.sidebar{width:100%;position:relative}
.sidebar.collapsed .number-grid{display:none}
#sidebarToggle{display:block;background:#1a5276;color:#fff;border:none;padding:.4rem .8rem;border-radius:4px;font-size:.8rem;margin-bottom:.5rem;cursor:pointer}
.content{padding:.8rem}
.question{font-size:.95rem}
.options label{padding:1.1rem 1rem;font-size:1rem}
.options input{min-width:22px;min-height:22px}
.number-grid button{min-height:44px;font-size:.85rem}
.btn-group{flex-direction:column}
.btn{width:100%;margin-bottom:.3rem;font-size:1rem}
.passage-bacaan{max-height:200px}
.question-scrollable{max-height:180px}
}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="topbar">
<a href="../index.php">Beranda</a>
<a href="latihan.php">Latihan</a>
<a href="materi.php?subtes=TWK">Materi</a>
<a href="daily_quiz.php" style="background:#e74c3c;color:#fff;padding:.1rem .4rem;border-radius:4px">Daily Quiz</a>
<a href="leaderboard.php">Leaderboard</a>
<?php if (!empty($_SESSION['user_id'])): ?>
<a href="user_dashboard.php">Dashboard</a>
<a href="../api/logout.php">Logout</a>
<?php else: ?>
<a href="login.php">Login</a>
<?php endif; ?>
</div>
<div class="header">
<h1>Try Out SKD CAT-BKN</h1>
<div style="display:flex;align-items:center;gap:.3rem">
    <button class="theme-toggle" onclick="toggleTheme()" title="Dark/Light Mode" aria-label="Toggle dark/light mode">🌙</button>
    <button class="font-toggle" onclick="cycleFontSize()" title="Ukuran Font" aria-label="Ubah ukuran font">Aa</button>
    <div class="timer" id="timer" aria-live="polite" aria-atomic="true"><?= htmlspecialchars($timerM) ?>:<?= htmlspecialchars($timerS) ?></div>
</div>
</div>
<div class="nav">
<span id="subtes-info">Memuat soal...</span>
</div>
<div class="main" id="main-content">
<div class="sidebar" id="sidebar">
<button id="sidebarToggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')" aria-label="Toggle sidebar navigation">Sembunyikan/Tampilkan Navigasi</button>
<h3>Navigasi Soal</h3>
<div id="navStatus" style="font-size:.8rem;color:var(--text-muted);margin-bottom:.4rem"></div>
<div class="number-grid" id="numberGrid"></div>
<div style="margin-top:1rem;font-size:.85rem;color:var(--text-muted)">
<div><span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:2px;margin-right:4px"></span> Sudah dijawab</div>
<div><span style="display:inline-block;width:12px;height:12px;background:#f1c40f;border-radius:2px;margin-right:4px"></span> Ragu-ragu</div>
<div><span style="display:inline-block;width:12px;height:12px;background:#2980b9;border-radius:2px;margin-right:4px"></span> Soal aktif</div>
<div><span style="display:inline-block;width:12px;height:12px;background:var(--bg-number);border:1px solid var(--border-light);border-radius:2px;margin-right:4px"></span> Belum dijawab</div>
</div>
</div>
<div class="content">
<div id="loadingIndicator" style="text-align:center;padding:3rem;color:#777">
<div style="font-size:2rem;margin-bottom:1rem">⏳</div>
<div>Memuat soal...</div>
</div>
<div class="passage-box" id="passageBox" style="display:none">
    <div class="passage-judul" id="passageJudul"></div>
    <div class="passage-bacaan" id="passageBacaan"></div>
</div>
<div id="soalContainer">
<p style="color:var(--text-muted)">Memuat soal, mohon tunggu...</p>
</div>
<div class="btn-group">
<button class="btn" id="btnPrev" onclick="prevSoal()" aria-label="Soal sebelumnya">Sebelumnya</button>
<button class="btn" id="btnNext" onclick="nextSoal()" aria-label="Soal selanjutnya">Selanjutnya</button>
<button class="btn" style="background:#f1c40f;color:#333" id="btnMark" onclick="toggleMark()" aria-label="Tandai ragu-ragu">Ragu (M)</button>
<button class="btn" style="background:#9b59b6;color:#fff" id="btnBookmark" onclick="toggleBookmark()" aria-label="Simpan ke favorit">⭐ Favorit</button>
<button class="btn finish" onclick="finishTryout()" aria-label="Selesaikan tryout">Selesai</button>
</div>
<div class="pembahasan" id="pembahasanBox"></div>
</div>
</div>
<script>
const sessionId = <?= json_encode($sessionId) ?>;
const csrfToken = <?= json_encode(getCsrfTokenForApi()) ?>;
let soal = [];
let passages = {}; // passage_id => {judul, bacaan}
let currentIdx = 0;
let answers = {}; // answer_id => jawaban
let marked = {};  // answer_id => boolean (ragu-ragu)
let bookmarked = {}; // question_id => boolean (favorit)
let totalSeconds = <?= json_encode($remainingSeconds) ?>; // sisa waktu total dari server
let timerInterval;
const LS_KEY = 'cat_answers_' + sessionId;

// Per-subtes timer data from server (PHP renders this as JSON)
// Format: { TWK: {durasi:30, start:timestamp, remaining:seconds}, ... }
const subtesTimers = <?= json_encode($subtesTimers) ?>;
let currentSubtes = <?= json_encode($currentSubtes) ?>;
let subtesOrder = Object.keys(subtesTimers); // e.g. ['TWK','TIU','TKP']
let subtesRemaining = {}; // remaining seconds per subtes (client-side countdown)
let activeSubtesIdx = subtesOrder.indexOf(currentSubtes);

// Initialize per-subtes remaining time
subtesOrder.forEach(sub => {
    subtesRemaining[sub] = subtesTimers[sub]?.remaining || subtesTimers[sub]?.durasi * 60 || 1800;
});

/**
 * Load questions for this session from server.
 * If questions haven't been generated yet, API auto-generates them.
 * Then restore any saved answers from localStorage (survive page refresh).
 */
async function loadSoal(){
    try {
        const res = await fetch('../api/get_soal.php?session_id='+sessionId, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        
        const text = await res.text();
        
        // Check if response is HTML (error page) instead of JSON
        if (text.trim().startsWith('<') || text.trim().startsWith('<!DOCTYPE')) {
            throw new Error('Server returned error page instead of JSON');
        }
        
        const data = JSON.parse(text);
        if(data.error){
            if(data.error.includes('Session sudah selesai') || data.error.includes('tidak aktif')){
                alert('Sesi tryout Anda telah berakhir atau tidak aktif. Anda akan diarahkan ke halaman hasil.');
                window.location.href = 'hasil.php?session_id='+sessionId;
                return;
            }
            alert(data.error);
            return;
        }
        // Handle new standardized format: {success: true, data: {session, soal, passages}}
        // Legacy format: {session, soal, passages}
        const responseData = data.data || data;
        soal = responseData.soal;
        passages = responseData.passages || {};
        // Restore answers from localStorage if available
        restoreLocalAnswers();
        document.getElementById('loadingIndicator').style.display = 'none';
        renderNumberGrid();
        renderSoal(0);
        startTimer();
    } catch (e) {
        alert('Gagal memuat soal: ' + e.message + '. Silakan refresh halaman atau periksa koneksi internet Anda.');
    }
}

/**
 * Start countdown timer for the entire tryout.
 * Runs every second. Decrements both total time and per-subtes time.
 * Auto-finishes when time runs out or auto-advances subtes.
 * FIXED: Added session expiry warning 5 minutes before expiry
 */
function startTimer(){
    let warningShown = false;
    timerInterval = setInterval(()=>{
        // Update per-subtes timer
        if (currentSubtes && subtesRemaining[currentSubtes] > 0) {
            subtesRemaining[currentSubtes]--;
        }
        totalSeconds--;
        
        // Show warning 5 minutes (300 seconds) before expiry
        if (!warningShown && totalSeconds === 300) {
            warningShown = true;
            alert('PERINGATAN: Sesi Anda akan berakhir dalam 5 menit. Jawaban Anda akan otomatis disimpan.');
            saveLocalAnswers(); // Force save before expiry
        }
        
        if(totalSeconds<=0){clearInterval(timerInterval);finishTryout();return;}

        // Check if current subtes time is up
        if (currentSubtes && subtesRemaining[currentSubtes] <= 0) {
            // Auto-advance to next subtes or finish
            const nextIdx = activeSubtesIdx + 1;
            if (nextIdx < subtesOrder.length) {
                alert('Waktu subtes ' + currentSubtes + ' habis! Anda akan dipindahkan ke subtes berikutnya.');
                advanceToNextSubtes();
            } else {
                clearInterval(timerInterval);
                finishTryout();
            }
            return;
        }

        const m = Math.floor(totalSeconds/60).toString().padStart(2,'0');
        const s = (totalSeconds%60).toString().padStart(2,'0');
        document.getElementById('timer').textContent = m+':'+s;
    },1000);
}

/**
 * Render the sidebar navigation grid showing all question numbers.
 * Each button shows status: active (blue), answered (green), marked (yellow), or unanswered.
 * Also updates the status text showing answered/unanswered/marked counts.
 */
function renderNumberGrid(){
    const grid = document.getElementById('numberGrid');
    grid.innerHTML = '';
    let answeredCount = 0, markedCount = 0;
    soal.forEach((s,i)=>{
        const btn = document.createElement('button');
        btn.textContent = i+1;
        btn.onclick = ()=>renderSoal(i);
        if(i===currentIdx) btn.classList.add('active');
        if(answers[s.answer_id]){ btn.classList.add('answered'); answeredCount++; }
        if(marked[s.answer_id]){ btn.classList.add('marked'); markedCount++; }
        grid.appendChild(btn);
    });
    const status = document.getElementById('navStatus');
    if(status){
        const total = soal.length;
        const text = '<strong style="color:#27ae60">' + answeredCount + '</strong> dijawab, '
            + '<strong style="color:#999">' + (total - answeredCount) + '</strong> belum'
            + (markedCount > 0 ? ' (<strong style="color:#f39c12">' + markedCount + '</strong> ragu)' : '');
        status.innerHTML = text;
    }
    // Scroll active button into view
    const activeBtn = grid.querySelector('button.active');
    if(activeBtn) activeBtn.scrollIntoView({behavior:'smooth', block:'nearest', inline:'nearest'});
}

/**
 * Auto-advance to the next subtes when time runs out.
 * Called by startTimer() when current subtes time hits 0.
 * Records transition via API and jumps to first question of next subtes.
 */
function advanceToNextSubtes(){
    const nextIdx = activeSubtesIdx + 1;
    if (nextIdx >= subtesOrder.length) {
        finishTryout();
        return;
    }
    const nextSub = subtesOrder[nextIdx];
    const currentSub = subtesOrder[activeSubtesIdx];

    // Call API to record subtes transition
    fetch('../api/next_subtes.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-Token': csrfToken
        },
        body:JSON.stringify({session_id:sessionId, current_subtes:currentSub, next_subtes:nextSub})
    });

    currentSubtes = nextSub;
    activeSubtesIdx = nextIdx;

    // Find first soal of next subtes
    const firstIdx = soal.findIndex(q => q.subtes === nextSub);
    if (firstIdx >= 0) {
        renderSoal(firstIdx);
    }
}

/**
 * Render a single question (and its options) into the main content area.
 * Also handles passage display, image rendering, and subtes change detection.
 * @param {number} idx - Index in the soal array
 */
function renderSoal(idx){
    currentIdx = idx;
    const s = soal[idx];

    // Detect subtes change: confirm before allowing forward navigation
    if (s.subtes !== currentSubtes) {
        const prevSubIdx = subtesOrder.indexOf(currentSubtes);
        const newSubIdx = subtesOrder.indexOf(s.subtes);
        // Only allow forward navigation, not backward
        if (newSubIdx > prevSubIdx) {
            // Count unanswered in current subtes
            const currentSubSoal = soal.filter(q => q.subtes === currentSubtes);
            const unanswered = currentSubSoal.filter(q => !answers[q.answer_id]).length;
            const msg = 'Anda akan pindah ke subtes ' + s.subtes + '.\n' +
                        'Soal ' + currentSubtes + ' yang belum dijawab: ' + unanswered + '\n' +
                        'Waktu ' + currentSubtes + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n' +
                        'Yakin ingin lanjut?';
            if (!confirm(msg)) {
                // Go back to last soal of current subtes
                const lastIdx = soal.map(q => q.subtes).lastIndexOf(currentSubtes);
                if (lastIdx >= 0) { currentIdx = lastIdx; }
                return;
            }
            // Call API to record transition
            fetch('../api/next_subtes.php',{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body:JSON.stringify({session_id:sessionId, current_subtes:currentSubtes, next_subtes:s.subtes})
            });
            currentSubtes = s.subtes;
            activeSubtesIdx = newSubIdx;
        }
    }

    document.getElementById('subtes-info').textContent = s.subtes + ' — Soal ' + (idx+1) + ' dari ' + soal.length;

    // Handle passage box: show if soal has passage_id
    const passageBox = document.getElementById('passageBox');
    const passageJudul = document.getElementById('passageJudul');
    const passageBacaan = document.getElementById('passageBacaan');

    if (s.passage_id && passages[s.passage_id]) {
        const p = passages[s.passage_id];
        // Count total soal in this passage
        const totalInPassage = soal.filter(q => q.passage_id == s.passage_id).length;
        const orderInPassage = soal.filter((q,i) => q.passage_id == s.passage_id && i <= idx).length;

        passageBox.style.display = 'block';
        passageJudul.textContent = p.judul ? escapeHtml(p.judul) : 'Bacaan';
        passageBacaan.innerHTML = '<div class="passage-info">Soal ' + orderInPassage + ' dari ' + totalInPassage + ' dalam bacaan ini</div>' + escapeHtml(p.bacaan);
    } else {
        passageBox.style.display = 'none';
        passageJudul.textContent = '';
        passageBacaan.innerHTML = '';
    }

    // Build question HTML: add scrollable class if question text is very long
    const qText = escapeHtml(s.pertanyaan);
    const scrollClass = (s.pertanyaan.length > 300) ? 'question-scrollable' : '';
    let html = '<div class="question ' + scrollClass + '"><strong>' + (idx+1) + '.</strong> ' + qText + '</div>';

    // Show image if present (tap to zoom)
    if (s.image_url) {
        html += '<img src="' + escapeHtml(s.image_url) + '" class="question-image" alt="Gambar soal" onerror="this.style.display=\'none\'" onclick="openZoom(this.src)" style="cursor:zoom-in">';
    }

    html += '<div class="options">';
    ['A','B','C','D','E'].forEach(opt=>{
        const selected = answers[s.answer_id] === opt ? 'selected' : '';
        html += '<label class="'+selected+'"><input type="radio" name="jawaban" value="'+opt+'" '+(selected?'checked':'')+' onchange="pilihJawaban('+s.answer_id+',\''+opt+'\',this)"> ' + opt + '. ' + escapeHtml(s['pilihan_'+opt.toLowerCase()]) + '</label>';
    });
    html += '</div>';
    html += '<div class="pembahasan" id="pembahasanBox" style="display:block">' + escapeHtml(s.pembahasan) + '</div>';
    document.getElementById('soalContainer').innerHTML = html;
    renderNumberGrid();
    // Scroll to question content, not nav grid
    document.getElementById('soalContainer').scrollIntoView({behavior:'smooth', block:'start'});
}

/**
 * Handle user selecting an answer (A-E).
 * Saves to server + localStorage, updates UI, then auto-advances to next question.
 * If last question, shows completion alert and redirects to results.
 */
function pilihJawaban(answerId, opt, el){
    answers[answerId] = opt;
    // visual select
    document.querySelectorAll('.options label').forEach(l=>l.classList.remove('selected'));
    el.closest('label').classList.add('selected');
    renderNumberGrid();
    // save to localStorage
    saveLocalAnswers();
    // submit ke server
    fetch('../api/submit_jawaban.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-Token': csrfToken
        },
        body:JSON.stringify({answer_id:answerId,jawaban:opt})
    });
    // Auto-advance to next question
    setTimeout(()=>{
        if(currentIdx >= soal.length - 1){
            // Last question answered
            const answeredCount = Object.keys(answers).length;
            const totalCount = soal.length;
            const raguCount = Object.values(marked).filter(Boolean).length;
            let msg = 'Selamat! Anda telah menjawab soal terakhir.\n\n';
            msg += 'Soal dijawab: ' + answeredCount + ' / ' + totalCount + '\n';
            if(raguCount > 0) msg += 'Ragu-ragu: ' + raguCount + '\n\n';
            msg += 'Klik OK untuk melihat hasil tryout.';
            alert(msg);
            finishTryout();
            return;
        }
        // Advance to next
        const nextIdx = currentIdx + 1;
        const currentSub = soal[currentIdx].subtes;
        const nextSub = soal[nextIdx].subtes;
        if (currentSub !== nextSub) {
            const currentSubSoal = soal.filter(q => q.subtes === currentSub);
            const unanswered = currentSubSoal.filter(q => !answers[q.answer_id]).length;
            const msg = 'Anda akan pindah ke subtes ' + nextSub + '.\n' +
                        'Soal ' + currentSub + ' yang belum dijawab: ' + unanswered + '\n' +
                        'Waktu ' + currentSub + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n' +
                        'Yakin ingin lanjut?';
            if (!confirm(msg)) return;
            fetch('../api/next_subtes.php',{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body:JSON.stringify({session_id:sessionId, current_subtes:currentSub, next_subtes:nextSub})
            });
            currentSubtes = nextSub;
            activeSubtesIdx = subtesOrder.indexOf(nextSub);
        }
        renderSoal(nextIdx);
    }, 400); // 400ms delay so user sees their selection
}

/** Navigate to previous question */
function prevSoal(){if(currentIdx>0)renderSoal(currentIdx-1);}
/** Navigate to next question (with subtes change confirmation if applicable) */
function nextSoal(){
    if(currentIdx>=soal.length-1) return;
    const nextIdx = currentIdx + 1;
    const currentSub = soal[currentIdx].subtes;
    const nextSub = soal[nextIdx].subtes;
    if (currentSub !== nextSub) {
        // Count unanswered in current subtes
        const currentSubSoal = soal.filter(q => q.subtes === currentSub);
        const unanswered = currentSubSoal.filter(q => !answers[q.answer_id]).length;
        const msg = 'Anda akan pindah ke subtes ' + nextSub + '.\n' +
                    'Soal ' + currentSub + ' yang belum dijawab: ' + unanswered + '\n' +
                    'Waktu ' + currentSub + ' yang tersisa tidak bisa digunakan untuk subtes lain.\n\n' +
                    'Yakin ingin lanjut?';
        if (!confirm(msg)) return;
        // Record transition via API
        fetch('../api/next_subtes.php',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-Token': csrfToken
            },
            body:JSON.stringify({session_id:sessionId, current_subtes:currentSub, next_subtes:nextSub})
        });
        currentSubtes = nextSub;
        activeSubtesIdx = subtesOrder.indexOf(nextSub);
    }
    renderSoal(nextIdx);
}

/** ================================
 * LOCALSTORAGE PERSISTENCE
 * Survives page refresh / browser crash
 * Key format: cat_answers_<sessionId>
 * FIXED: Added localStorage quota exceeded handling
 * ================================ */
function saveLocalAnswers(){
    try {
        const data = JSON.stringify({answers: answers, marked: marked, savedAt: Date.now()});
        localStorage.setItem(LS_KEY, data);
    } catch (e) {
        if (e.name === 'QuotaExceededError') {
            console.error('LocalStorage quota exceeded. Attempting to clear old data...');
            // Try to clear old sessions to free space
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('cat_answers_') && key !== LS_KEY) {
                    localStorage.removeItem(key);
                    console.log('Cleared old session:', key);
                    // Try saving again
                    try {
                        const data = JSON.stringify({answers: answers, marked: marked, savedAt: Date.now()});
                        localStorage.setItem(LS_KEY, data);
                        console.log('Successfully saved after clearing old data');
                        return;
                    } catch (retryError) {
                        console.error('Still cannot save after clearing old data');
                    }
                }
            }
            // If still failed, alert user
            alert('Peringatan: Penyimpanan browser penuh. Jawaban Anda tetap disimpan ke server, tapi tidak dapat disimpan secara lokal untuk recovery.');
        } else {
            console.error('Error saving to localStorage:', e);
        }
    }
}
function restoreLocalAnswers(){
    const saved = localStorage.getItem(LS_KEY);
    if(saved){
        try{
            const data = JSON.parse(saved);
            if(data.answers) Object.assign(answers, data.answers);
            if(data.marked) Object.assign(marked, data.marked);
        }catch(e){
            console.error('Error restoring from localStorage:', e);
        }
    }
}
function clearLocalAnswers(){
    try {
        localStorage.removeItem(LS_KEY);
    } catch (e) {
        console.error('Error clearing localStorage:', e);
    }
}

// --- MARK/RAGU-RAGU + NEEDS REVISION ---
function toggleMark(){
    const s = soal[currentIdx];
    marked[s.answer_id] = !marked[s.answer_id];
    saveLocalAnswers();
    renderNumberGrid();
    // Send revision flag to server
    fetch('../api/mark_revision.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-Token': csrfToken
        },
        body:JSON.stringify({question_id:s.question_id, needs_revision:marked[s.answer_id]?1:0})
    });
}

// --- BOOKMARK/FAVORIT ---
async function toggleBookmark(){
    const s = soal[currentIdx];
    const isBookmarked = bookmarked[s.question_id];
    const action = isBookmarked ? 'remove' : 'add';
    
    const formData = new FormData();
    formData.append('question_id', s.question_id);
    formData.append('action', action);
    
    try {
        const res = await fetch('../api/bookmark_question.php', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            bookmarked[s.question_id] = !isBookmarked;
            const btn = document.getElementById('btnBookmark');
            if (btn) {
                btn.style.background = bookmarked[s.question_id] ? '#f39c12' : '#9b59b6';
                btn.textContent = bookmarked[s.question_id] ? '⭐ Tersimpan' : '⭐ Favorit';
            }
            showToast(bookmarked[s.question_id] ? 'Soal disimpan ke favorit' : 'Soal dihapus dari favorit', 'success');
        } else {
            showToast(data.error || 'Gagal menyimpan favorit', 'error');
        }
    } catch (e) {
        showToast('Gagal menyimpan favorit', 'error');
    }
}

// --- ANTI-CHEATING ---
// Disable right-click
document.addEventListener('contextmenu', e=>e.preventDefault());
// Disable copy
document.addEventListener('copy', e=>{ if(e.target.closest('.passage-bacaan, .question')) e.preventDefault(); });
// Disable cut
document.addEventListener('cut', e=>{ if(e.target.closest('.passage-bacaan, .question')) e.preventDefault(); });
// Detect window blur (user switches tab/app)
let blurCount = 0;
window.addEventListener('blur', ()=>{
    blurCount++;
    if(blurCount >= 3){
        alert('Peringatan: Anda telah meninggalkan halaman tryout terlalu sering. Integritas tes akan dievaluasi.');
    }
});
// Prevent back button navigation
document.addEventListener('keydown', e=>{
    if(e.key==='F5' || (e.ctrlKey && e.key==='r')){ e.preventDefault(); alert('Refresh tidak diizinkan selama tryout.'); }
    if(e.ctrlKey && e.key==='u'){ e.preventDefault(); } // view source
    if(e.ctrlKey && e.shiftKey && e.key==='I'){ e.preventDefault(); } // devtools
    if(e.key==='F12'){ e.preventDefault(); } // devtools
});
history.pushState(null, '', location.href);
window.addEventListener('popstate', ()=>{
    history.pushState(null, '', location.href);
    alert('Navigasi back tidak diizinkan selama tryout.');
});

/**
 * ================================
 * SWIPE NAVIGATION (Touch devices)
 * Swipe left  = next question
 * Swipe right = previous question
 * Threshold: 50px horizontal
 * ================================ */
let touchStartX = 0;
let touchStartY = 0;
const SWIPE_THRESHOLD = 50;
document.addEventListener('touchstart', e=>{
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
}, {passive:true});
document.addEventListener('touchend', e=>{
    if(!soal.length) return;
    const touchEndX = e.changedTouches[0].screenX;
    const touchEndY = e.changedTouches[0].screenY;
    const dx = touchEndX - touchStartX;
    const dy = touchEndY - touchStartY;
    // Only handle horizontal swipes (abs(dx) > abs(dy))
    if(Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > SWIPE_THRESHOLD){
        if(dx < 0){ nextSoal(); } // swipe left = next
        else { prevSoal(); } // swipe right = previous
    }
}, {passive:true});

/**
 * ================================
 * KEYBOARD SHORTCUTS
 * A-E     : Select answer
 * ←/↑     : Previous question
 * →/↓     : Next question
 * M       : Mark as uncertain
 * F5/Ctrl+R : Blocked (anti-cheating)
 * ================================ */
document.addEventListener('keydown', e=>{
    if(!soal.length) return;
    const key = e.key.toUpperCase();
    // A-E: select answer
    if(['A','B','C','D','E'].includes(key)){
        const radios = document.querySelectorAll('input[name="jawaban"]');
        radios.forEach(r=>{ if(r.value===key){ r.checked=true; r.dispatchEvent(new Event('change')); } });
    }
    // Arrow keys: navigate
    if(key==='ARROWLEFT' || key==='ARROWUP'){ prevSoal(); }
    if(key==='ARROWRIGHT' || key==='ARROWDOWN'){ nextSoal(); }
    // M: mark/ragu
    if(key==='M'){ toggleMark(); }
});

function finishTryout(){
    const answeredCount = Object.keys(answers).length;
    const totalCount = soal.length;
    const msg = 'Yakin ingin menyelesaikan try out?\n\n' +
                'Soal dijawab: ' + answeredCount + ' / ' + totalCount + '\n' +
                'Ragu-ragu: ' + Object.values(marked).filter(Boolean).length;
    if(!confirm(msg)) return;
    clearInterval(timerInterval);
    clearLocalAnswers();
    fetch('../api/finish_tryout.php',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-Token': csrfToken
        },
        body:JSON.stringify({session_id:sessionId})
    }).then(r=>r.json()).then(data=>{
        if(data.success) window.location.href = 'hasil.php?session_id='+sessionId;
        else alert(data.error);
    });
}

</script>

<!-- Image Zoom Modal -->
<div id="imgZoomModal" onclick="closeZoom()">
    <button class="zoom-close" onclick="event.stopPropagation();closeZoom()">Tutup</button>
    <img id="zoomImg" src="" alt="Zoomed">
    <div class="zoom-hint">Ketuk gambar atau di luar area untuk menutup</div>
</div>

<script src="../assets/app.js"></script>
<script>
/**
 * ================================
 * ACCESSIBILITY: DARK MODE & FONT SIZE
 * Persist user preference to localStorage
 * ================================ */
// --- THEME / DARK MODE ---
const savedTheme = localStorage.getItem('cat_theme') || 'light';
if(savedTheme==='dark') document.documentElement.setAttribute('data-theme','dark');
function toggleTheme(){
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'light';
    const next = current==='dark'?'light':'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('cat_theme', next);
}

// --- FONT SIZE ---
const fontSizes = ['small','medium','large'];
let fontIdx = 1; // medium default
const savedFont = localStorage.getItem('cat_font');
if(savedFont){ const idx = fontSizes.indexOf(savedFont); if(idx>=0){ fontIdx=idx; document.documentElement.setAttribute('data-font-size',savedFont); } }
function cycleFontSize(){
    fontIdx = (fontIdx + 1) % fontSizes.length;
    const size = fontSizes[fontIdx];
    document.documentElement.setAttribute('data-font-size', size);
    localStorage.setItem('cat_font', size);
}

/**
 * Open image in fullscreen zoom overlay modal.
 * Tap anywhere or click "Tutup" to close.
 */
function openZoom(src){
    const modal = document.getElementById('imgZoomModal');
    const img = document.getElementById('zoomImg');
    img.src = src;
    modal.classList.add('show');
}
function closeZoom(){
    document.getElementById('imgZoomModal').classList.remove('show');
}

loadSoal();
</script>
</body>
</html>

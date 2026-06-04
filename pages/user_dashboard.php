<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');
$userRole = $_SESSION['user_role'] ?? 'user';

// Fetch user info with instansi
$stmt = $pdo->prepare("SELECT u.nama, u.no_hp, u.email, u.sekolah_asal, u.tahun_tamat, u.instansi, u.instansi_id, i.kode as instansi_kode, i.nama as instansi_nama, i.deskripsi as instansi_desk 
    FROM users u LEFT JOIN instansi i ON u.instansi_id = i.id WHERE u.id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();

// Fetch riwayat tryout
$stmt = $pdo->prepare("SELECT * FROM tryout_sessions WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$riwayat = $stmt->fetchAll();

// Calculate stats
$totalTryout = count($riwayat);
$selesai = array_filter($riwayat, fn($r) => $r['status'] === 'selesai');
$rataNilai = 0;
$bestScore = 0;
$subtesTerlemah = '-';

if (count($selesai) > 0) {
    $totalNilai = array_sum(array_column($selesai, 'total_nilai'));
    $rataNilai = round($totalNilai / count($selesai));
    $bestScore = max(array_column($selesai, 'total_nilai'));

    // Cari subtes terlemah (rata-rata nilai per subtes)
    $tkpScores = array_column($selesai, 'nilai_tkp');
    $tiuScores = array_column($selesai, 'nilai_tiu');
    $twkScores = array_column($selesai, 'nilai_twk');
    $avgTkp = count($tkpScores) ? round(array_sum($tkpScores) / count($tkpScores)) : 0;
    $avgTiu = count($tiuScores) ? round(array_sum($tiuScores) / count($tiuScores)) : 0;
    $avgTwk = count($twkScores) ? round(array_sum($twkScores) / count($twkScores)) : 0;
    $min = min($avgTkp, $avgTiu, $avgTwk);
    if ($min === $avgTkp && $min > 0) $subtesTerlemah = 'TKP';
    elseif ($min === $avgTiu && $min > 0) $subtesTerlemah = 'TIU';
    elseif ($min === $avgTwk && $min > 0) $subtesTerlemah = 'TWK';
}

// Passing grades dari subtes_config (dinamis)
$passingMap = $pdo->query("SELECT subtes, passing_grade FROM subtes_config WHERE aktif = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
$passingTkp = (int)($passingMap['TKP'] ?? 126);
$passingTiu = (int)($passingMap['TIU'] ?? 80);
$passingTwk = (int)($passingMap['TWK'] ?? 65);
$passingTotal = (int)($passingMap['TKP'] ?? 126) + (int)($passingMap['TIU'] ?? 80) + (int)($passingMap['TWK'] ?? 65);

// Ambil semua instansi untuk rekomendasi
$instansiList = $pdo->query("SELECT * FROM instansi WHERE aktif = 1 ORDER BY urutan")->fetchAll();

// Ambil rekomendasi materi
$rekomendasiMateri = $pdo->query("SELECT * FROM rekomendasi_materi WHERE aktif = 1 ORDER BY urutan")->fetchAll();

// Analisis akurasi per topik (butuh minimal 1 jawaban)
$akurasiTopik = $pdo->prepare("
    SELECT 
        q.subtes,
        q.topik,
        COUNT(*) as total,
        SUM(CASE WHEN a.jawaban_user = q.jawaban_benar THEN 1 ELSE 0 END) as benar
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    JOIN tryout_sessions ts ON a.session_id = ts.id
    WHERE ts.user_id = ? AND a.jawaban_user IS NOT NULL AND a.jawaban_user != ''
    GROUP BY q.subtes, q.topik
    HAVING COUNT(*) >= 3
    ORDER BY subtes, benar ASC, total DESC
    LIMIT 10
");
$akurasiTopik->execute([$userId]);
$topikStats = $akurasiTopik->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Dashboard Peserta — SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6;-webkit-text-size-adjust:100%}
.header{background:#1a5276;color:#fff;padding:.8rem 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.4rem}
.header h1{font-size:1.1rem;white-space:nowrap}.header div{display:flex;flex-wrap:wrap;gap:.4rem .8rem;align-items:center}
.header a{color:#fff;text-decoration:none;font-size:.85rem;white-space:nowrap;min-height:44px;display:flex;align-items:center}
.container{max-width:1000px;margin:1.5rem auto;padding:0 1rem}
.welcome{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.5rem}
.welcome h2{color:#1a5276;font-size:1.15rem;margin-bottom:.3rem}
.welcome p{color:#555;font-size:.9rem}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:8px;padding:.9rem;box-shadow:0 2px 6px rgba(0,0,0,.08);text-align:center}
.stat .num{font-size:1.6rem;font-weight:bold;color:#2980b9}
.stat .label{color:#555;font-size:.85rem;margin-top:.3rem}
.stat .sub{color:#777;font-size:.75rem}
.actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;overflow-x:auto;-webkit-overflow-scrolling:touch}
.btn{display:inline-block;background:#2980b9;color:#fff;padding:.55rem .9rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:.9rem;min-height:44px;min-width:44px;white-space:nowrap}
.btn:hover{background:#1a5276}
.btn.success{background:#27ae60}
.btn.warning{background:#e67e22}
.btn.danger{background:#e74c3c}
.section{background:#fff;border-radius:8px;padding:1.2rem;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.2rem;overflow:hidden}
.section h2{color:#1a5276;font-size:1.05rem;margin-bottom:.8rem;border-bottom:2px solid #eaf2f8;padding-bottom:.4rem}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:500px}
th,td{border:1px solid #eee;padding:.4rem .5rem;text-align:left}
th{background:#f8f9fa;color:#555}
tr:hover{background:#f8f9fa}
.badge{display:inline-block;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold}
.badge.lulus{background:#d4edda;color:#155724}
.badge.gagal{background:#f8d7da;color:#721c24}
.badge.berjalan{background:#fff3cd;color:#856404}
.empty{color:#777;font-style:italic;text-align:center;padding:2rem}
.rekomendasi{background:#eaf2f8;border-left:4px solid #2980b9;padding:1rem;border-radius:0 6px 6px 0;margin-top:1rem}
.rekomendasi h3{color:#1a5276;font-size:1rem;margin-bottom:.3rem}
.rekomendasi p{color:#444;font-size:.9rem}
.footer{text-align:center;padding:1.2rem;color:#777;font-size:.85rem;margin-top:1.5rem}
@media(max-width:600px){
.stats{grid-template-columns:repeat(2,1fr)}
.actions{flex-wrap:nowrap}
.section{padding:1rem}
}
@media(max-width:380px){
.stats{grid-template-columns:1fr}
}
.skip-link:focus{top:0}
.topic-bar{margin-bottom:.8rem}
.topic-bar-header{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.2rem}
.topic-bar-track{background:#e9ecef;border-radius:10px;height:20px;overflow:hidden}
.topic-bar-fill{height:100%;border-radius:10px;transition:width .3s ease}
.topic-bar-fill.high{background:#27ae60}.topic-bar-fill.mid{background:#f39c12}.topic-bar-fill.low{background:#e74c3c}
.topic-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<div class="header">
<h1>Dashboard Peserta — SKD CAT-BKN</h1>
<div style="display:flex;align-items:center;gap:.4rem .8rem;flex-wrap:wrap">
<div style="position:relative">
<button onclick="toggleNotifications()" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:.4rem;min-width:44px;min-height:44px" aria-label="Notifikasi">
🔔
<span id="notifBadge" style="position:absolute;top:0;right:0;background:#e74c3c;color:#fff;font-size:.7rem;padding:.1rem .4rem;border-radius:10px;display:none">0</span>
</button>
<div id="notifDropdown" style="display:none;position:absolute;top:100%;right:0;background:#fff;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);min-width:300px;max-height:400px;overflow-y:auto;z-index:1000">
<div id="notifList" style="padding:1rem">
<p style="color:#666;font-size:.85rem">Memuat notifikasi...</p>
</div>
</div>
</div>
<a href="../index.php">Beranda</a>
<a href="latihan.php">Latihan</a>
<a href="tryout.php">Try Out</a>
<a href="leaderboard.php">Leaderboard</a>
<a href="feedback.php">Feedback</a>
<?php if ($userRole === 'admin'): ?>
<a href="admin_dashboard.php">Admin</a>
<?php endif; ?>
<a href="../api/logout.php">Logout</a>
</div>
</div>

<div class="container" id="main-content">
<div class="welcome">
<h2>Selamat datang, <?= $userName ?>!</h2>
<p>
<strong>Nomor HP:</strong> <?= e($userInfo['no_hp'] ?? '-') ?><br>
<?php if ($userInfo['sekolah_asal']): ?>
<strong>Sekolah Asal:</strong> <?= e($userInfo['sekolah_asal']) ?> (<?= e($userInfo['tahun_tamat'] ?? '-') ?>)<br>
<?php endif; ?>
<?php if ($userInfo['instansi_nama']): ?>
<strong>Instansi Pilihan:</strong> <?= e($userInfo['instansi_kode']) ?> — <?= e($userInfo['instansi_nama']) ?><br>
<small style="color:#666"><?= e($userInfo['instansi_desk'] ?? '') ?></small>
<?php else: ?>
<strong>Belum memilih instansi.</strong> <a href="register.php" style="color:#2980b9">Pilih instansi di profil</a> untuk mendapatkan rekomendasi.
<?php endif; ?>
</p>
</div>

<div class="actions">
<a href="tryout.php" class="btn danger">Mulai Try Out Penuh</a>
<a href="latihan.php" class="btn success">Latihan per Subtes</a>
<a href="riwayat_soal.php" class="btn" style="background:#8e44ad">Riwayat Soal</a>
<a href="materi.php?subtes=TWK" class="btn warning">Materi TWK</a>
<a href="materi.php?subtes=TIU" class="btn warning">Materi TIU</a>
<a href="materi.php?subtes=TKP" class="btn warning">Materi TKP</a>
</div>

<div class="stats">
<div class="stat"><div class="num"><?= $totalTryout ?></div><div class="label">Total Tryout</div></div>
<div class="stat"><div class="num"><?= count($selesai) ?></div><div class="label">Selesai</div></div>
<div class="stat"><div class="num"><?= $rataNilai ?></div><div class="label">Rata-rata Nilai</div></div>
<div class="stat"><div class="num"><?= $bestScore ?></div><div class="label">Nilai Tertinggi</div></div>
<div class="stat"><div class="num" style="color:#e74c3c"><?= $subtesTerlemah ?></div><div class="label">Subtes Terlemah</div></div>
</div>

<?php if (empty($selesai)): ?>
<!-- Empty State -->
<div class="section" style="text-align:center;padding:3rem 1rem">
<div style="font-size:3rem;margin-bottom:1rem">📊</div>
<h3 style="color:#555;margin-bottom:.5rem">Belum ada data tryout</h3>
<p style="color:#777;font-size:.9rem;margin-bottom:1.5rem">Mulai tryout pertama Anda untuk melihat grafik progress dan analisis performa.</p>
<a href="tryout.php" class="btn" style="background:#2980b9;color:#fff;text-decoration:none;padding:.75rem 1.5rem;border-radius:5px;display:inline-block">Mulai Try Out</a>
</div>
<?php else: ?>
<!-- Progress Chart -->
<div class="section">
<h2>Grafik Progress</h2>
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
    <button class="btn" style="font-size:.8rem" onclick="drawChart('total')" aria-label="Tampilkan grafik total skor">Total</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('tkp')" aria-label="Tampilkan grafik skor TKP">TKP</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('tiu')" aria-label="Tampilkan grafik skor TIU">TIU</button>
    <button class="btn" style="font-size:.8rem" onclick="drawChart('twk')" aria-label="Tampilkan grafik skor TWK">TWK</button>
</div>
<canvas id="progressChart" width="900" height="300" style="max-width:100%;height:auto"></canvas>
</div>

<?php if (!empty($selesai)): ?>
<!-- Subtes Distribution Pie Chart -->
<div class="section">
<h2>Distribusi Skor Subtes (Tryout Terakhir)</h2>
<canvas id="pieChart" width="400" height="400" style="max-width:100%;height:auto;margin:0 auto;display:block"></canvas>
<div style="display:flex;justify-content:center;gap:1.5rem;margin-top:1rem;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:0.5rem"><span style="width:16px;height:16px;background:#2980b9;border-radius:3px"></span><span style="font-size:.9rem">TWK</span></div>
    <div style="display:flex;align-items:center;gap:0.5rem"><span style="width:16px;height:16px;background:#e67e22;border-radius:3px"></span><span style="font-size:.9rem">TIU</span></div>
    <div style="display:flex;align-items:center;gap:0.5rem"><span style="width:16px;height:16px;background:#27ae60;border-radius:3px"></span><span style="font-size:.9rem">TKP</span></div>
</div>
</div>

<script>
// Notification System
let notifDropdownOpen = false;

async function loadNotifications() {
    try {
        const res = await fetch('../api/get_notifications.php?limit=10');
        const data = await res.json();
        
        if (data.success) {
            renderNotifications(data.notifications);
            updateNotifBadge(data.unread_count);
        }
    } catch (e) {
        console.error('Failed to load notifications:', e);
    }
}

function renderNotifications(notifications) {
    const list = document.getElementById('notifList');
    
    if (notifications.length === 0) {
        list.innerHTML = '<p style="color:#777;font-size:.85rem;text-align:center;padding:1rem">Tidak ada notifikasi</p>';
        return;
    }
    
    const typeColors = {
        'info': '#2980b9',
        'success': '#27ae60',
        'warning': '#f39c12',
        'error': '#e74c3c'
    };
    
    let html = '';
    notifications.forEach(n => {
        const bgColor = n.is_read ? '#f8f9fa' : '#eaf2f8';
        html += `
        <div style="background:${bgColor};padding:.8rem;border-bottom:1px solid #eee;cursor:pointer" onclick="openNotification(${n.id}, '${n.link || ''}')" class="notif-item" data-id="${n.id}">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                <span style="width:8px;height:8px;border-radius:50%;background:${typeColors[n.type]}"></span>
                <span style="font-weight:bold;font-size:.85rem;color:#333">${escapeHtml(n.title)}</span>
                ${!n.is_read ? '<span style="background:#2980b9;color:#fff;font-size:.65rem;padding:.1rem .3rem;border-radius:4px">Baru</span>' : ''}
            </div>
            <div style="font-size:.8rem;color:#555;margin-bottom:.3rem">${escapeHtml(n.message)}</div>
            <div style="font-size:.7rem;color:#999">${new Date(n.created_at).toLocaleString('id-ID')}</div>
        </div>
        `;
    });
    
    html += '<div style="padding:.5rem;text-align:center"><a href="#" onclick="markAllRead();return false" style="font-size:.8rem;color:#2980b9;text-decoration:none">Tandai semua sudah dibaca</a></div>';
    list.innerHTML = html;
}

function updateNotifBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (count > 0) {
        badge.textContent = count > 9 ? '9+' : count;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    notifDropdownOpen = !notifDropdownOpen;
    dropdown.style.display = notifDropdownOpen ? 'block' : 'none';
    
    if (notifDropdownOpen) {
        loadNotifications();
    }
}

async function openNotification(id, link) {
    // Mark as read
    try {
        const formData = new FormData();
        formData.append('notification_id', id);
        await fetch('../api/mark_notification_read.php', { method: 'POST', body: formData });
    } catch (e) {}
    
    // Navigate if link exists
    if (link) {
        window.location.href = link;
    }
    
    // Reload notifications
    loadNotifications();
}

async function markAllRead() {
    try {
        const res = await fetch('../api/get_notifications.php?unread_only=true');
        const data = await res.json();
        
        if (data.success && data.notifications.length > 0) {
            for (const n of data.notifications) {
                const formData = new FormData();
                formData.append('notification_id', n.id);
                await fetch('../api/mark_notification_read.php', { method: 'POST', body: formData });
            }
            loadNotifications();
        }
    } catch (e) {}
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('notifDropdown');
    const button = e.target.closest('button[onclick="toggleNotifications()"]');
    if (!button && notifDropdownOpen && !dropdown.contains(e.target)) {
        notifDropdownOpen = false;
        dropdown.style.display = 'none';
    }
});

// Load notifications on page load
document.addEventListener('DOMContentLoaded', loadNotifications);

// Pie Chart
const latestScore = end($selesai);
const pieData = {
    twk: (int)($latestScore['nilai_twk'] ?? 0),
    tiu: (int)($latestScore['nilai_tiu'] ?? 0),
    tkp: (int)($latestScore['nilai_tkp'] ?? 0)
};

function drawPieChart(){
    const canvas = document.getElementById('pieChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width, h = canvas.height;
    const centerX = w/2, centerY = h/2, radius = Math.min(w,h)/2 - 40;
    
    const total = pieData.twk + pieData.tiu + pieData.tkp;
    if (total === 0) return;
    
    const data = [
        { label: 'TWK', value: pieData.twk, color: '#2980b9' },
        { label: 'TIU', value: pieData.tiu, color: '#e67e22' },
        { label: 'TKP', value: pieData.tkp, color: '#27ae60' }
    ];
    
    let startAngle = -Math.PI/2;
    
    data.forEach(item => {
        const sliceAngle = (item.value / total) * 2 * Math.PI;
        
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = item.color;
        ctx.fill();
        
        // Draw label
        const midAngle = startAngle + sliceAngle/2;
        const labelX = centerX + Math.cos(midAngle) * (radius * 0.7);
        const labelY = centerY + Math.sin(midAngle) * (radius * 0.7);
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 14px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        if (item.value > 0) {
            ctx.fillText(item.label, labelX, labelY - 8);
            ctx.font = '12px Arial';
            ctx.fillText(item.value, labelX, labelY + 8);
        }
        
        startAngle += sliceAngle;
    });
    
    // Draw center circle (donut chart)
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius * 0.4, 0, 2 * Math.PI);
    ctx.fillStyle = '#fff';
    ctx.fill();
    
    // Draw total in center
    ctx.fillStyle = '#333';
    ctx.font = 'bold 16px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Total', centerX, centerY - 10);
    ctx.font = 'bold 24px Arial';
    ctx.fillText(total, centerX, centerY + 15);
}

drawPieChart();
</script>
<?php endif; ?>

<script>
const chartData = <?= json_encode(array_map(fn($r)=>[
    'date'=>date('d M',strtotime($r['waktu_mulai'])),
    'total'=>(int)($r['total_nilai']??0),
    'tkp'=>(int)($r['nilai_tkp']??0),
    'tiu'=>(int)($r['nilai_tiu']??0),
    'twk'=>(int)($r['nilai_twk']??0)
], array_reverse(array_slice($selesai,-10)))) ?>;

function drawChart(mode){
    const canvas = document.getElementById('progressChart');
    const ctx = canvas.getContext('2d');
    const w = canvas.width, h = canvas.height;
    const pad = {top:30,right:30,bottom:50,left:50};
    ctx.clearRect(0,0,w,h);

    const labels = chartData.map(d=>d.date);
    const values = chartData.map(d=>d[mode]);
    const maxVal = Math.max(...values, 300);
    const minVal = 0;

    // Grid & axes
    ctx.strokeStyle='#eee';ctx.lineWidth=1;
    for(let i=0;i<=5;i++){
        const y=pad.top+(h-pad.top-pad.bottom)*(1-i/5);
        ctx.beginPath();ctx.moveTo(pad.left,y);ctx.lineTo(w-pad.right,y);ctx.stroke();
        ctx.fillStyle='#999';ctx.font='11px Arial';ctx.textAlign='right';
        ctx.fillText(Math.round(maxVal*i/5),pad.left-8,y+4);
    }

    // Bars
    const barW = (w-pad.left-pad.right)/values.length * 0.6;
    const gap = (w-pad.left-pad.right)/values.length;
    const color = mode==='total'?'#2980b9':(mode==='tkp'?'#27ae60':(mode==='tiu'?'#e67e22':'#8e44ad'));

    values.forEach((v,i)=>{
        const x = pad.left + gap*i + gap*0.2;
        const barH = (v/maxVal)*(h-pad.top-pad.bottom);
        const y = h-pad.bottom-barH;
        ctx.fillStyle=color;
        ctx.fillRect(x,y,barW,barH);
        // Value label
        ctx.fillStyle='#333';ctx.font='bold 11px Arial';ctx.textAlign='center';
        ctx.fillText(v, x+barW/2, y-5);
        // Date label
        ctx.save();
        ctx.translate(x+barW/2, h-pad.bottom+15);
        ctx.rotate(-Math.PI/6);
        ctx.fillStyle='#666';ctx.font='10px Arial';
        ctx.fillText(labels[i],0,0);
        ctx.restore();
    });

    // Axis labels
    ctx.fillStyle='#333';ctx.font='bold 12px Arial';ctx.textAlign='center';
    ctx.fillText('Tanggal', w/2, h-5);
    ctx.save();ctx.translate(15,h/2);ctx.rotate(-Math.PI/2);
    ctx.fillText('Nilai ' + mode.toUpperCase(), 0, 0);
    ctx.restore();
}
drawChart('total');
</script>
<?php endif; ?>

<?php if (empty($topikStats)): ?>
<!-- Empty State for Topik Stats -->
<div class="section" style="text-align:center;padding:2rem 1rem">
<div style="font-size:2rem;margin-bottom:.5rem">📚</div>
<p style="color:#777;font-size:.9rem">Selesaikan lebih banyak soal untuk melihat analisis akurasi per topik.</p>
</div>
<?php else: ?>
<!-- Analisis Akurasi per Topik -->
<div class="section">
<h2>Analisis Akurasi per Topik</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">Persentase jawaban benar per topik (minimal 3 soal dikerjakan). Semakin tinggi semakin baik.</p>
<div class="topic-grid">
<?php foreach ($topikStats as $ts):
    $pct = round(($ts['benar'] / $ts['total']) * 100);
    $fillClass = $pct >= 70 ? 'high' : ($pct >= 40 ? 'mid' : 'low');
    $badgeColor = $pct >= 70 ? '#27ae60' : ($pct >= 40 ? '#f39c12' : '#e74c3c');
?>
<div style="background:#f8f9fa;border-radius:6px;padding:.8rem">
    <div class="topic-bar">
        <div class="topic-bar-header">
            <span><strong><?= $ts['subtes'] ?></strong> — <?= e($ts['topik']) ?></span>
            <span style="color:<?= $badgeColor ?>;font-weight:bold"><?= $pct ?>% (<?= $ts['benar'] ?>/<?= $ts['total'] ?>)</span>
        </div>
        <div class="topic-bar-track">
            <div class="topic-bar-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
    <div style="text-align:right;margin-top:.3rem">
        <a href="materi.php?subtes=<?= $ts['subtes'] ?>" style="font-size:.8rem;color:#2980b9;text-decoration:none">Latih Topik Ini &rarr;</a>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<?php if ($subtesTerlemah !== '-'): ?>
<div class="rekomendasi">
<h3>Analisis Kelemahan & Rekomendasi</h3>
<p>Subtes <strong><?= $subtesTerlemah ?></strong> adalah nilai rata-rata terendah Anda (rata-rata: <?= $subtesTerlemah === 'TKP' ? $avgTkp : ($subtesTerlemah === 'TIU' ? $avgTiu : $avgTwk) ?>).</p>
<?php
// Filter rekomendasi materi berdasarkan subtes terlemah
$rekomSub = array_filter($rekomendasiMateri, fn($r) => $r['subtes'] === $subtesTerlemah);
if (!empty($rekomSub)):
?>
<ul style="margin-top:.5rem;padding-left:1.2rem;font-size:.9rem;color:#444">
<?php foreach (array_slice($rekomSub, 0, 3) as $r): ?>
<li><a href="<?= $r['materi_url'] ?>" style="color:#2980b9;text-decoration:none"><?= e($r['topik']) ?></a> — <?= e($r['pesan']) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($selesai)): 
    $latest = reset($selesai); // Tryout terakhir yang selesai
    $latestTkp = $latest['nilai_tkp'] ?? 0;
    $latestTiu = $latest['nilai_tiu'] ?? 0;
    $latestTwk = $latest['nilai_twk'] ?? 0;
    $latestTotal = $latest['total_nilai'] ?? 0;
?>
<div class="section" style="margin-top:1.2rem">
<h2>Kelayakan Instansi</h2>
<p style="font-size:.9rem;color:#555;margin-bottom:1rem">Berdasarkan nilai tryout terakhir Anda (TKP: <?= $latestTkp ?>, TIU: <?= $latestTiu ?>, TWK: <?= $latestTwk ?>, Total: <?= $latestTotal ?>):</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem">
<?php foreach ($instansiList as $ins): 
    $lulusTkp = $latestTkp >= $ins['passing_tkp'];
    $lulusTiu = $latestTiu >= $ins['passing_tiu'];
    $lulusTwk = $latestTwk >= $ins['passing_twk'];
    $lulusTotal = $latestTotal >= $ins['passing_total'];
    $lulusSemua = $lulusTkp && $lulusTiu && $lulusTwk && $lulusTotal;
    $cardColor = $lulusSemua ? '#d4edda' : '#fff3cd';
    $borderColor = $lulusSemua ? '#155724' : '#856404';
?>
<div style="background:<?= $cardColor ?>;border:1px solid <?= $borderColor ?>;border-radius:6px;padding:.8rem;font-size:.9rem">
<div style="font-weight:bold;color:#1a5276"><?= e($ins['kode']) ?></div>
<div style="font-size:.8rem;color:#555;margin-bottom:.3rem"><?= e($ins['nama']) ?></div>
<?php if ($lulusSemua): ?>
<div style="color:#155724;font-weight:bold;font-size:.85rem">✅ Memenuhi syarat SKD</div>
<?php else: ?>
<div style="color:#856404;font-size:.8rem">
<?php if (!$lulusTkp) echo 'TKP kurang ' . ($ins['passing_tkp'] - $latestTkp) . '<br>'; ?>
<?php if (!$lulusTiu) echo 'TIU kurang ' . ($ins['passing_tiu'] - $latestTiu) . '<br>'; ?>
<?php if (!$lulusTwk) echo 'TWK kurang ' . ($ins['passing_twk'] - $latestTwk) . '<br>'; ?>
<?php if (!$lulusTotal) echo 'Total kurang ' . ($ins['passing_total'] - $latestTotal) . '<br>'; ?>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<div class="section">
<h2>Riwayat Tryout</h2>
<?php if (empty($riwayat)): ?>
<div class="empty">Belum ada riwayat tryout. Yuk mulai latihan!</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr><th>Nama</th><th>Status</th><th>TKP</th><th>TIU</th><th>TWK</th><th>Total</th><th>Waktu</th></tr>
</thead>
<tbody>
<?php foreach ($riwayat as $r):
    $tkpStatus = ($r['nilai_tkp'] ?? 0) >= $passingTkp ? 'lulus' : 'gagal';
    $tiuStatus = ($r['nilai_tiu'] ?? 0) >= $passingTiu ? 'lulus' : 'gagal';
    $twkStatus = ($r['nilai_twk'] ?? 0) >= $passingTwk ? 'lulus' : 'gagal';
?>
<tr style="cursor:pointer" onclick="window.location.href='hasil.php?session_id=<?= $r['id'] ?>'">
<td><?= e($r['nama']) ?></td>
<td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
<td><span class="badge <?= $tkpStatus ?>"><?= $r['nilai_tkp'] ?? 0 ?></span></td>
<td><span class="badge <?= $tiuStatus ?>"><?= $r['nilai_tiu'] ?? 0 ?></span></td>
<td><span class="badge <?= $twkStatus ?>"><?= $r['nilai_twk'] ?? 0 ?></span></td>
<td><?= $r['total_nilai'] ?? 0 ?></td>
<td><?= date('d M Y H:i', strtotime($r['waktu_mulai'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

</div>

<div class="footer">
Dashboard Peserta SKD CAT-BKN | Latihan persiapan Sekolah Kedinasan
</div>
</body>
</html>

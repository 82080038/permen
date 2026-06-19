<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in peserta (not admin)
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$userName = e($_SESSION['user_nama'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Feedback — SKD CAT-BKN</title>
<base href="<?php echo $baseUrl ?? '/permen'; ?>">
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/form.css">
<link rel="stylesheet" href="<?php echo $baseUrl ?? '/permen'; ?>/assets/style.css">
<style>
.feedback-container{max-width:600px;margin:2rem auto;padding:0 1rem}
.category-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:.5rem;margin-bottom:1rem}
.category-btn{padding:.6rem;border:1px solid #ddd;border-radius:5px;background:#fff;cursor:pointer;font-size:.85rem;transition:all .2s}
.category-btn:hover{background:#f8f9fa}
.category-btn.selected{background:#2980b9;color:#fff;border-color:#2980b9}
textarea{min-height:120px;resize:vertical}
.char-count{text-align:right;font-size:.75rem;color:#777;margin-top:.3rem}
.status-badge{display:inline-block;padding:.25rem .5rem;border-radius:10px;font-size:.75rem;font-weight:bold}
.status-pending{background:#fff3cd;color:#856404}
.status-dilihat{background:#d1ecf1;color:#0c5460}
.status-diproses{background:#fff3cd;color:#856404}
.status-selesai{background:#d4edda;color:#155724}
.status-ditolak{background:#f8d7da;color:#721c24}
@media(max-width:480px){
.category-grid{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Feedback'; $activePage = 'feedback'; ?>
<?php require '../includes/navigation.php'; ?>

<div class="feedback-container" id="main-content">
<div class="card">
<h2>Kirim Feedback</h2>
<p style="font-size:.9rem;color:#666;margin-bottom:1rem">Saran, kritik, atau laporan bug Anda sangat berharga untuk meningkatkan kualitas aplikasi.</p>

<form id="feedbackForm" onsubmit="submitFeedback(event)">
<div class="form-group">
<label>Kategori *</label>
<div class="category-grid">
<button type="button" class="category-btn" data-category="saran" onclick="selectCategory(this)">💡 Saran</button>
<button type="button" class="category-btn" data-category="kritik" onclick="selectCategory(this)">📝 Kritik</button>
<button type="button" class="category-btn" data-category="bug" onclick="selectCategory(this)">🐛 Bug</button>
<button type="button" class="category-btn" data-category="fitur" onclick="selectCategory(this)">✨ Fitur</button>
<button type="button" class="category-btn" data-category="lainnya" onclick="selectCategory(this)">📌 Lainnya</button>
</div>
<input type="hidden" id="category" name="category" value="lainnya">
</div>

<div class="form-group">
<label for="message">Pesan Feedback *</label>
<textarea id="message" name="message" required minlength="10" maxlength="1000" placeholder="Jelaskan detail feedback Anda..." oninput="updateCharCount()"></textarea>
<div class="char-count"><span id="charCount">0</span>/1000 karakter</div>
</div>

<button type="submit" class="btn" style="width:100%;padding:.8rem">Kirim Feedback</button>
</form>
</div>

<!-- My Feedback History -->
<div class="card" style="margin-top:1.5rem">
<h2>Riwayat Feedback Anda</h2>
<div id="feedbackHistory">
<p style="color:#666">Memuat riwayat feedback...</p>
</div>
</div>
</div>

<base href="<?php echo $baseUrl ?? '/permen'; ?>">
<script src="<?php echo $baseUrl ?? '/permen'; ?>/assets/app.js"></script>
<script>
let selectedCategory = 'lainnya';

function selectCategory(btn) {
    document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedCategory = btn.getAttribute('data-category');
    document.getElementById('category').value = selectedCategory;
}

function updateCharCount() {
    const message = document.getElementById('message').value;
    document.getElementById('charCount').textContent = message.length;
}

async function submitFeedback(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('category', selectedCategory);
    formData.append('message', document.getElementById('message').value);
    
    try {
        const res = await fetch('/api/submit_feedback.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            document.getElementById('feedbackForm').reset();
            selectCategory(document.querySelector('[data-category="lainnya"]'));
            updateCharCount();
            loadFeedbackHistory();
            // Tampilkan opsi redirect setelah 2 detik
            setTimeout(() => {
                if (confirm('Feedback berhasil dikirim! Terima kasih atas kontribusi Anda.\n\nKlik OK untuk kembali ke Beranda, atau Cancel untuk tetap di halaman ini.')) {
                    window.location.href = '/index.php';
                }
            }, 1500);
        } else {
            showToast(data.error || 'Gagal mengirim feedback', 'error');
        }
    } catch (err) {
        showToast('Gagal mengirim feedback', 'error');
    }
}

async function loadFeedbackHistory() {
    try {
        const res = await fetch('/api/get_my_feedback.php');
        const data = await res.json();
        
        if (data.success) {
            renderFeedbackHistory(data.feedback);
        } else {
            document.getElementById('feedbackHistory').innerHTML = '<p style="color:#e74c3c">' + (data.error || 'Gagal memuat riwayat') + '</p>';
        }
    } catch (err) {
        document.getElementById('feedbackHistory').innerHTML = '<p style="color:#e74c3c">Gagal memuat riwayat</p>';
    }
}

function renderFeedbackHistory(feedback) {
    if (feedback.length === 0) {
        document.getElementById('feedbackHistory').innerHTML = '<p style="color:#777">Belum ada feedback yang dikirim.</p>';
        return;
    }
    
    const statusLabels = {
        'pending': 'Pending',
        'dilihat': 'Dilihat',
        'diproses': 'Diproses',
        'selesai': 'Selesai',
        'ditolak': 'Ditolak'
    };
    
    let html = '<div style="display:flex;flex-direction:column;gap:.8rem">';
    feedback.forEach(f => {
        html += `
        <div style="background:#f8f9fa;padding:1rem;border-radius:6px;border-left:4px solid #2980b9">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <span style="font-weight:bold;color:#1a5276">${f.category.toUpperCase()}</span>
                <span class="status-badge status-${f.status}">${statusLabels[f.status]}</span>
            </div>
            <p style="color:#333;font-size:.9rem;margin-bottom:.5rem">${escapeHtml(f.message)}</p>
            <div style="font-size:.75rem;color:#777">
                ${new Date(f.created_at).toLocaleString('id-ID')}
            </div>
            ${f.admin_response ? `
            <div style="margin-top:.5rem;padding:.5rem;background:#e8f5e9;border-radius:4px">
                <div style="font-size:.75rem;color:#155724;font-weight:bold;margin-bottom:.2rem">📢 Admin:</div>
                <div style="font-size:.85rem;color:#333">${escapeHtml(f.admin_response)}</div>
            </div>
            ` : ''}
        </div>
        `;
    });
    html += '</div>';
    document.getElementById('feedbackHistory').innerHTML = html;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    selectCategory(document.querySelector('[data-category="lainnya"]'));
    loadFeedbackHistory();
});
</script>
</body>
</html>

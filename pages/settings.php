<?php
require '../config.php';
require '../helpers.php';

// Guard: only logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = e($_SESSION['user_nama'] ?? 'Peserta');

$error = '';
$success = '';

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $notificationPref = $_POST['notification_preference'] ?? 'browser';
        $language = $_POST['language'] ?? 'id';
        $theme = $_POST['theme'] ?? 'light';
        $fontSize = $_POST['font_size'] ?? 'medium';

        // Validasi
        $validNotifications = ['push', 'browser', 'both', 'none'];
        $validLanguages = ['id', 'en'];
        $validThemes = ['light', 'dark'];
        $validFontSizes = ['small', 'medium', 'large'];

        if (!in_array($notificationPref, $validNotifications)) {
            $error = 'Preferensi notifikasi tidak valid.';
        } elseif (!in_array($language, $validLanguages)) {
            $error = 'Bahasa tidak valid.';
        } elseif (!in_array($theme, $validThemes)) {
            $error = 'Tema tidak valid.';
        } elseif (!in_array($fontSize, $validFontSizes)) {
            $error = 'Ukuran font tidak valid.';
        } else {
            // Update settings
            $stmt = $pdo->prepare("UPDATE users SET notification_preference = ?, language = ?, theme = ?, font_size = ? WHERE id = ?");
            $stmt->execute([$notificationPref, $language, $theme, $fontSize, $userId]);
            
            $success = 'Pengaturan berhasil disimpan!';
            
            // Refresh data user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<base href="/permen/">
<title>Pengaturan — SKD CAT-BKN</title>
<link rel="stylesheet" href="assets/form.css">
<link rel="stylesheet" href="assets/style.css">
<style>
.settings-section {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}
.settings-section h3 {
    color: #1a5276;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #eaf2f8;
    padding-bottom: 0.5rem;
}
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}
.setting-item:last-child {
    border-bottom: none;
}
.setting-label {
    flex: 1;
}
.setting-label h4 {
    color: #333;
    font-size: 1rem;
    margin-bottom: 0.3rem;
}
.setting-label p {
    color: #777;
    font-size: 0.85rem;
    margin: 0;
}
.setting-control {
    min-width: 200px;
}
.radio-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.radio-option input[type="radio"] {
    cursor: pointer;
}
.font-preview {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 0.5rem;
}
.font-small { font-size: 14px; }
.font-medium { font-size: 16px; }
.font-large { font-size: 18px; }
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000;transition:top 0.3s">Lanjut ke konten utama</a>
<?php $pageTitle = 'Pengaturan — SKD CAT-BKN'; $activePage = 'settings'; ?>
<?php require '../includes/navigation.php'; ?>
<div class="container">
<div class="card">
<h2>Pengaturan Pengguna</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

<!-- Notification Preferences -->
<div class="settings-section">
    <h3>🔔 Preferensi Notifikasi</h3>
    <div class="setting-item">
        <div class="setting-label">
            <h4>Jenis Notifikasi</h4>
            <p>Pilih bagaimana Anda ingin menerima notifikasi</p>
        </div>
        <div class="setting-control">
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="notification_preference" value="browser" <?= ($user['notification_preference'] ?? 'browser') === 'browser' ? 'checked' : '' ?>>
                    <span>Browser</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="notification_preference" value="push" <?= ($user['notification_preference'] ?? 'browser') === 'push' ? 'checked' : '' ?>>
                    <span>Push</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="notification_preference" value="both" <?= ($user['notification_preference'] ?? 'browser') === 'both' ? 'checked' : '' ?>>
                    <span>Keduanya</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="notification_preference" value="none" <?= ($user['notification_preference'] ?? 'browser') === 'none' ? 'checked' : '' ?>>
                    <span>Nonaktif</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Push Notification Toggle -->
    <div class="setting-item" id="pushNotificationToggle" style="display:none">
        <div class="setting-label">
            <h4>Aktifkan Push Notifications</h4>
            <p>Terima notifikasi langsung di perangkat Anda</p>
        </div>
        <div class="setting-control">
            <button type="button" id="togglePushBtn" class="btn" onclick="togglePushNotifications()">Aktifkan Push</button>
        </div>
    </div>
    
    <!-- Detailed Notification Preferences -->
    <div class="setting-item" id="detailedNotificationPrefs" style="display:none">
        <div class="setting-label">
            <h4>Kategori Notifikasi</h4>
            <p>Pilih notifikasi yang ingin Anda terima</p>
        </div>
        <div class="setting-control">
            <div style="display:flex;flex-direction:column;gap:.5rem">
                <label class="radio-option">
                    <input type="checkbox" id="prefDailyQuiz" name="daily_quiz_reminder" value="1" checked>
                    <span>📝 Daily Quiz Reminder</span>
                </label>
                <label class="radio-option">
                    <input type="checkbox" id="prefLiveClass" name="live_class_starting" value="1" checked>
                    <span>🎥 Live Class Starting</span>
                </label>
                <label class="radio-option">
                    <input type="checkbox" id="prefNewMateri" name="new_materi_available" value="1" checked>
                    <span>📚 New Materi Available</span>
                </label>
                <label class="radio-option">
                    <input type="checkbox" id="prefTryoutResult" name="tryout_result_ready" value="1" checked>
                    <span>📊 Tryout Result Ready</span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Language Preference -->
<div class="settings-section">
    <h3>🌐 Bahasa</h3>
    <div class="setting-item">
        <div class="setting-label">
            <h4>Bahasa Antarmuka</h4>
            <p>Pilih bahasa untuk tampilan aplikasi</p>
        </div>
        <div class="setting-control">
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="language" value="id" <?= ($user['language'] ?? 'id') === 'id' ? 'checked' : '' ?>>
                    <span>Indonesia</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="language" value="en" <?= ($user['language'] ?? 'id') === 'en' ? 'checked' : '' ?>>
                    <span>English</span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Theme Preference -->
<div class="settings-section">
    <h3>🎨 Tema</h3>
    <div class="setting-item">
        <div class="setting-label">
            <h4>Tema Tampilan</h4>
            <p>Pilih tema light atau dark mode</p>
        </div>
        <div class="setting-control">
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="theme" value="light" <?= ($user['theme'] ?? 'light') === 'light' ? 'checked' : '' ?>>
                    <span>☀️ Light</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="theme" value="dark" <?= ($user['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
                    <span>🌙 Dark</span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Font Size Preference -->
<div class="settings-section">
    <h3>🔤 Ukuran Font</h3>
    <div class="setting-item">
        <div class="setting-label">
            <h4>Ukuran Teks</h4>
            <p>Atur ukuran font untuk kenyamanan membaca</p>
        </div>
        <div class="setting-control">
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="font_size" value="small" <?= ($user['font_size'] ?? 'medium') === 'small' ? 'checked' : '' ?>>
                    <span>Kecil</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="font_size" value="medium" <?= ($user['font_size'] ?? 'medium') === 'medium' ? 'checked' : '' ?>>
                    <span>Sedang</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="font_size" value="large" <?= ($user['font_size'] ?? 'medium') === 'large' ? 'checked' : '' ?>>
                    <span>Besar</span>
                </label>
            </div>
        </div>
    </div>
    <div class="font-preview font-<?= $user['font_size'] ?? 'medium' ?>">
        <strong>Preview:</strong> Ini adalah contoh teks dengan ukuran font yang dipilih.
    </div>
</div>

<button type="submit" class="btn" aria-label="Simpan pengaturan">Simpan Pengaturan</button>
</form>

<p style="text-align:center;margin-top:1rem;font-size:.9rem">
<a href="user_dashboard.php" class="link">Kembali ke Dashboard</a>
</p>
</div>
</div>
<div class="footer">SKD CAT-BKN Try Out & Bimbel</div>
<script src="assets/app.js"></script>
<script>
// Apply theme preview
document.querySelectorAll('input[name="theme"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    });
});

// Apply font size preview
document.querySelectorAll('input[name="font_size"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const preview = document.querySelector('.font-preview');
        preview.className = 'font-preview font-' + this.value;
    });
});

// Handle notification preference change
document.querySelectorAll('input[name="notification_preference"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const value = this.value;
        const pushToggle = document.getElementById('pushNotificationToggle');
        const detailedPrefs = document.getElementById('detailedNotificationPrefs');
        
        if (value === 'push' || value === 'both') {
            pushToggle.style.display = 'flex';
            detailedPrefs.style.display = 'flex';
            initPushNotificationSettings();
        } else {
            pushToggle.style.display = 'none';
            detailedPrefs.style.display = 'none';
        }
    });
});

// Initialize push notification settings
async function initPushNotificationSettings() {
    const isSubscribed = await initPushNotifications();
    const toggleBtn = document.getElementById('togglePushBtn');
    
    if (isSubscribed) {
        toggleBtn.textContent = 'Nonaktifkan Push';
        toggleBtn.style.background = '#e74c3c';
    } else {
        toggleBtn.textContent = 'Aktifkan Push';
        toggleBtn.style.background = '#27ae60';
    }
    
    // Load detailed preferences
    const prefs = await getNotificationPreferences();
    if (prefs) {
        document.getElementById('prefDailyQuiz').checked = prefs.daily_quiz_reminder;
        document.getElementById('prefLiveClass').checked = prefs.live_class_starting;
        document.getElementById('prefNewMateri').checked = prefs.new_materi_available;
        document.getElementById('prefTryoutResult').checked = prefs.tryout_result_ready;
    }
}

// Toggle push notifications
async function togglePushNotifications() {
    const isSubscribed = await initPushNotifications();
    const toggleBtn = document.getElementById('togglePushBtn');
    
    if (isSubscribed) {
        const success = await unsubscribeFromPushNotifications();
        if (success) {
            toggleBtn.textContent = 'Aktifkan Push';
            toggleBtn.style.background = '#27ae60';
        }
    } else {
        const success = await subscribeToPushNotifications();
        if (success) {
            toggleBtn.textContent = 'Nonaktifkan Push';
            toggleBtn.style.background = '#e74c3c';
        }
    }
}

// Save detailed notification preferences
async function saveNotificationPreferences() {
    const preferences = {
        daily_quiz_reminder: document.getElementById('prefDailyQuiz').checked ? 1 : 0,
        live_class_starting: document.getElementById('prefLiveClass').checked ? 1 : 0,
        new_materi_available: document.getElementById('prefNewMateri').checked ? 1 : 0,
        tryout_result_ready: document.getElementById('prefTryoutResult').checked ? 1 : 0
    };
    
    await updateNotificationPreferences(preferences);
}

// Add event listeners for detailed preferences
document.querySelectorAll('#detailedNotificationPrefs input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', saveNotificationPreferences);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const notificationPref = document.querySelector('input[name="notification_preference"]:checked');
    if (notificationPref && (notificationPref.value === 'push' || notificationPref.value === 'both')) {
        initPushNotificationSettings();
    }
});


// Load current theme on page load
const currentTheme = '<?= $user['theme'] ?? 'light' ?>';
if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
}
</script>
</body>
</html>

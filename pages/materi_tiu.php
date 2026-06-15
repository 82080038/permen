<?php
require '../config.php';
require '../helpers.php';

// Guard: user harus login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load materi TIU content
$file = "../content/materi_tiu.php";
$materi = file_exists($file) ? require $file : [];

// Get user's bookmarked materials and progress
$userId = $_SESSION['user_id'];
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

$pageTitle = 'Materi TIU - Tes Intelegensia Umum';
$activePage = 'materi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<link rel="stylesheet" href="/assets/style.css">
<title><?php echo $pageTitle; ?></title>
</head>
<body>
<?php require '../includes/navigation.php'; ?>

<div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
    <div class="card">
        <div class="card-header">
            <h1>🧠 Materi Tes Intelegensia Umum (TIU)</h1>
            <p>Kemampuan verbal, numerik, dan figural sesuai standar CAT BKN</p>
        </div>
        
        <div class="card-body">
            <div class="materi-navigation">
                <a href="materi.php?subtes=TIU" class="btn btn-primary">← Kembali ke Materi</a>
                <div class="subtest-tabs">
                    <a href="materi_twk.php" class="btn btn-secondary">TWK</a>
                    <a href="materi_tiu.php" class="btn btn-primary active">TIU</a>
                    <a href="materi_tkp.php" class="btn btn-secondary">TKP</a>
                </div>
            </div>

            <?php if (empty($materi)): ?>
                <div class="alert alert-warning">
                    <p>📝 Materi TIU sedang dalam pengembangan. Silakan kembali lagi nanti.</p>
                </div>
            <?php else: ?>
                <?php foreach ($materi as $item): ?>
                    <div class="materi-section" id="<?php echo $item['id']; ?>">
                        <div class="materi-header">
                            <h2><?php echo $item['judul']; ?></h2>
                            <div class="materi-actions">
                                <button class="btn btn-sm btn-outline" onclick="toggleBookmark('<?php echo $item['id']; ?>')">
                                    <span id="bookmark-<?php echo $item['id']; ?>">
                                        <?php echo in_array($item['id'], $bookmarkedIds) ? '❤️' : '🤍'; ?>
                                    </span>
                                </button>
                                <button class="btn btn-sm btn-outline" onclick="markProgress('<?php echo $item['id']; ?>')">
                                    ✓ Tandai Selesai
                                </button>
                            </div>
                        </div>
                        
                        <div class="materi-content">
                            <?php echo $item['konten']; ?>
                        </div>
                        
                        <?php if (!empty($item['quiz'])): ?>
                            <div class="materi-quiz">
                                <h3>📝 Uji Pemahaman</h3>
                                <button class="btn btn-primary" onclick="startQuiz('<?php echo $item['id']; ?>')">
                                    Mulai Quiz
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleBookmark(materiId) {
    fetch('/api/bookmark_question.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({materi_id: materiId})
    })
    .then(response => response.json())
    .then(data => {
        const bookmarkEl = document.getElementById('bookmark-' + materiId);
        bookmarkEl.textContent = data.bookmarked ? '❤️' : '🤍';
    });
}

function markProgress(materiId) {
    fetch('/api/update_materi_progress.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({materi_id: materiId, progress: 100})
    })
    .then(response => response.json())
    .then(data => {
        alert('Progress berhasil disimpan!');
    });
}

function startQuiz(materiId) {
    window.location.href = `tryout.php?materi=${materiId}&quiz=true`;
}
</script>

</body>
</html>

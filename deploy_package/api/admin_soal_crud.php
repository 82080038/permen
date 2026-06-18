<?php
require '../config.php';
require '../helpers.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    ApiResponse::forbidden('Unauthorized');
}

$action = $_POST['action'] ?? '';

if ($action === 'add_soal') {
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $tipe = sanitizeInput($_POST['tipe'] ?? '');
    $topik = sanitizeInput($_POST['topik'] ?? '');
    $pertanyaan = sanitizeInput($_POST['pertanyaan'] ?? '');
    $pilihanA = sanitizeInput($_POST['pilihan_a'] ?? '');
    $pilihanB = sanitizeInput($_POST['pilihan_b'] ?? '');
    $pilihanC = sanitizeInput($_POST['pilihan_c'] ?? '');
    $pilihanD = sanitizeInput($_POST['pilihan_d'] ?? '');
    $pilihanE = sanitizeInput($_POST['pilihan_e'] ?? '');
    $jawabanBenar = strtoupper($_POST['jawaban_benar'] ?? '');
    $pembahasan = sanitizeInput($_POST['pembahasan'] ?? '');
    $tips = sanitizeInput($_POST['tips'] ?? '');
    $relatedLinks = sanitizeInput($_POST['related_links'] ?? '');
    $materi = sanitizeInput($_POST['materi'] ?? '');
    $bobotTkp = (int)($_POST['bobot_tkp'] ?? 0);
    $tags = $_POST['tags'] ?? [];
    
    if (!$subtes || !$pertanyaan || !$jawabanBenar) {
        echo json_encode(['error' => 'Subtes, pertanyaan, dan jawaban benar wajib diisi']);
        exit;
    }
    
    if (!in_array($jawabanBenar, ['A', 'B', 'C', 'D', 'E'])) {
        echo json_encode(['error' => 'Jawaban benar harus A, B, C, D, atau E']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO questions 
        (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, 
         jawaban_benar, pembahasan, tips, related_links, materi, bobot_tkp, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $subtes, $tipe ?: null, $topik ?: null, $pertanyaan, 
        $pilihanA ?: null, $pilihanB ?: null, $pilihanC ?: null, $pilihanD ?: null, $pilihanE ?: null,
        $jawabanBenar, $pembahasan ?: null, $tips ?: null, $relatedLinks ?: null, $materi ?: null, $bobotTkp ?: null
    ]);
    
    $soalId = $pdo->lastInsertId();
    
    // Add tags
    if (!empty($tags)) {
        foreach ($tags as $tagName) {
            // Get or create tag
            $stmt = $pdo->prepare("SELECT id FROM soal_tags WHERE tag_name = ?");
            $stmt->execute([$tagName]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                $stmt = $pdo->prepare("INSERT INTO soal_tags (tag_name) VALUES (?)");
                $stmt->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            } else {
                $tagId = $tag['id'];
            }
            
            // Add relation
            $stmt = $pdo->prepare("INSERT INTO soal_tag_relations (soal_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$soalId, $tagId]);
        }
    }
    
    ApiResponse::success(['soal_id' => $soalId], 'Soal berhasil ditambahkan');
    
} elseif ($action === 'edit_soal') {
    $soalId = (int)($_POST['soal_id'] ?? 0);
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $tipe = sanitizeInput($_POST['tipe'] ?? '');
    $topik = sanitizeInput($_POST['topik'] ?? '');
    $pertanyaan = sanitizeInput($_POST['pertanyaan'] ?? '');
    $pilihanA = sanitizeInput($_POST['pilihan_a'] ?? '');
    $pilihanB = sanitizeInput($_POST['pilihan_b'] ?? '');
    $pilihanC = sanitizeInput($_POST['pilihan_c'] ?? '');
    $pilihanD = sanitizeInput($_POST['pilihan_d'] ?? '');
    $pilihanE = sanitizeInput($_POST['pilihan_e'] ?? '');
    $jawabanBenar = strtoupper($_POST['jawaban_benar'] ?? '');
    $pembahasan = sanitizeInput($_POST['pembahasan'] ?? '');
    $tips = sanitizeInput($_POST['tips'] ?? '');
    $relatedLinks = sanitizeInput($_POST['related_links'] ?? '');
    $materi = sanitizeInput($_POST['materi'] ?? '');
    $bobotTkp = (int)($_POST['bobot_tkp'] ?? 0);
    $tags = $_POST['tags'] ?? [];
    
    if (!$soalId || !$subtes || !$pertanyaan || !$jawabanBenar) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    // Get current version
    $stmt = $pdo->prepare("SELECT MAX(version) as max_version FROM soal_versions WHERE soal_id = ?");
    $stmt->execute([$soalId]);
    $result = $stmt->fetch();
    $newVersion = ($result['max_version'] ?? 0) + 1;
    
    // Get current soal data for versioning
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$soalId]);
    $currentSoal = $stmt->fetch();
    
    // Save current version
    $stmt = $pdo->prepare("
        INSERT INTO soal_versions 
        (soal_id, version, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, 
         jawaban_benar, pembahasan, tips, related_links, materi, image_url, bobot_tkp, edited_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $soalId, $newVersion, $currentSoal['pertanyaan'], $currentSoal['pilihan_a'], $currentSoal['pilihan_b'],
        $currentSoal['pilihan_c'], $currentSoal['pilihan_d'], $currentSoal['pilihan_e'], $currentSoal['jawaban_benar'],
        $currentSoal['pembahasan'], $currentSoal['tips'], $currentSoal['related_links'], $currentSoal['materi'],
        $currentSoal['image_url'], $currentSoal['bobot_tkp'], $_SESSION['user_id']
    ]);
    
    // Update soal
    $stmt = $pdo->prepare("
        UPDATE questions 
        SET subtes = ?, tipe = ?, topik = ?, pertanyaan = ?, pilihan_a = ?, pilihan_b = ?, 
            pilihan_c = ?, pilihan_d = ?, pilihan_e = ?, jawaban_benar = ?, pembahasan = ?, 
            tips = ?, related_links = ?, materi = ?, bobot_tkp = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $subtes, $tipe ?: null, $topik ?: null, $pertanyaan, 
        $pilihanA ?: null, $pilihanB ?: null, $pilihanC ?: null, $pilihanD ?: null, $pilihanE ?: null,
        $jawabanBenar, $pembahasan ?: null, $tips ?: null, $relatedLinks ?: null, $materi ?: null, 
        $bobotTkp ?: null, $soalId
    ]);
    
    // Update tags
    $stmt = $pdo->prepare("DELETE FROM soal_tag_relations WHERE soal_id = ?");
    $stmt->execute([$soalId]);
    
    if (!empty($tags)) {
        foreach ($tags as $tagName) {
            $stmt = $pdo->prepare("SELECT id FROM soal_tags WHERE tag_name = ?");
            $stmt->execute([$tagName]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                $stmt = $pdo->prepare("INSERT INTO soal_tags (tag_name) VALUES (?)");
                $stmt->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            } else {
                $tagId = $tag['id'];
            }
            
            $stmt = $pdo->prepare("INSERT INTO soal_tag_relations (soal_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$soalId, $tagId]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Soal berhasil diupdate (versi ' . $newVersion . ' tersimpan)']);
    
} elseif ($action === 'delete_soal') {
    $soalId = (int)($_POST['soal_id'] ?? 0);
    
    if (!$soalId) {
        ApiResponse::validationError(['soal_id' => 'Invalid soal ID'], 'Invalid soal ID');
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE questions SET is_active = 0 WHERE id = ?");
    $stmt->execute([$soalId]);
    
    ApiResponse::success([], 'Soal berhasil dihapus (soft delete)');
    
} elseif ($action === 'restore_version') {
    $soalId = (int)($_POST['soal_id'] ?? 0);
    $version = (int)($_POST['version'] ?? 0);
    
    if (!$soalId || !$version) {
        ApiResponse::validationError(['soal_id' => 'Invalid parameters', 'version' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    // Get version data
    $stmt = $pdo->prepare("SELECT * FROM soal_versions WHERE soal_id = ? AND version = ?");
    $stmt->execute([$soalId, $version]);
    $versionData = $stmt->fetch();
    
    if (!$versionData) {
        ApiResponse::notFound('Version not found');
    }
    
    // Save current version first
    $stmt = $pdo->prepare("SELECT MAX(version) as max_version FROM soal_versions WHERE soal_id = ?");
    $stmt->execute([$soalId]);
    $result = $stmt->fetch();
    $newVersion = ($result['max_version'] ?? 0) + 1;
    
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$soalId]);
    $currentSoal = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        INSERT INTO soal_versions 
        (soal_id, version, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, 
         jawaban_benar, pembahasan, tips, related_links, materi, image_url, bobot_tkp, edited_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $soalId, $newVersion, $currentSoal['pertanyaan'], $currentSoal['pilihan_a'], $currentSoal['pilihan_b'],
        $currentSoal['pilihan_c'], $currentSoal['pilihan_d'], $currentSoal['pilihan_e'], $currentSoal['jawaban_benar'],
        $currentSoal['pembahasan'], $currentSoal['tips'], $currentSoal['related_links'], $currentSoal['materi'],
        $currentSoal['image_url'], $currentSoal['bobot_tkp'], $_SESSION['user_id']
    ]);
    
    // Restore from version
    $stmt = $pdo->prepare("
        UPDATE questions 
        SET pertanyaan = ?, pilihan_a = ?, pilihan_b = ?, pilihan_c = ?, pilihan_d = ?, pilihan_e = ?, 
            jawaban_benar = ?, pembahasan = ?, tips = ?, related_links = ?, materi = ?, image_url = ?, bobot_tkp = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $versionData['pertanyaan'], $versionData['pilihan_a'], $versionData['pilihan_b'],
        $versionData['pilihan_c'], $versionData['pilihan_d'], $versionData['pilihan_e'],
        $versionData['jawaban_benar'], $versionData['pembahasan'], $versionData['tips'],
        $versionData['related_links'], $versionData['materi'], $versionData['image_url'],
        $versionData['bobot_tkp'], $soalId
    ]);
    
    ApiResponse::success([], 'Versi berhasil direstore');
    
} elseif ($_GET['action'] === 'get_soal_versions') {
    $soalId = (int)($_GET['soal_id'] ?? 0);
    
    if (!$soalId) {
        ApiResponse::validationError(['soal_id' => 'Invalid soal ID'], 'Invalid soal ID');
    }
    
    $stmt = $pdo->prepare("
        SELECT sv.*, u.nama as editor_name
        FROM soal_versions sv
        LEFT JOIN users u ON sv.edited_by = u.id
        WHERE sv.soal_id = ?
        ORDER BY sv.version DESC
    ");
    $stmt->execute([$soalId]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['versions' => $versions], 'Versions retrieved');
    
} elseif ($_GET['action'] === 'get_all_tags') {
    $stmt = $pdo->query("SELECT * FROM soal_tags ORDER BY tag_name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ApiResponse::success(['tags' => $tags], 'Tags retrieved');
    
} elseif ($_GET['action'] === 'get_soal_tags') {
    $soalId = (int)($_GET['soal_id'] ?? 0);
    
    if (!$soalId) {
        echo json_encode(['error' => 'Invalid soal ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT st.* 
        FROM soal_tags st
        JOIN soal_tag_relations str ON st.id = str.tag_id
        WHERE str.soal_id = ?
    ");
    $stmt->execute([$soalId]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['tags' => $tags], 'Soal tags retrieved');
    
} elseif ($_GET['action'] === 'get_version_diff') {
    $soalId = (int)($_GET['soal_id'] ?? 0);
    $version1 = (int)($_GET['version1'] ?? 0);
    $version2 = (int)($_GET['version2'] ?? 0);
    
    if (!$soalId || !$version1 || !$version2) {
        ApiResponse::validationError(['soal_id' => 'Invalid parameters', 'version1' => 'Invalid parameters', 'version2' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM soal_versions WHERE soal_id = ? AND version = ?");
    $stmt->execute([$soalId, $version1]);
    $v1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt->execute([$soalId, $version2]);
    $v2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$v1 || !$v2) {
        ApiResponse::notFound('Version not found');
    }
    
    // Calculate diff
    $diff = [];
    $fields = ['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'jawaban_benar', 'pembahasan', 'tips', 'related_links', 'materi', 'bobot_tkp'];
    
    foreach ($fields as $field) {
        if ($v1[$field] != $v2[$field]) {
            $diff[] = [
                'field' => $field,
                'old' => $v1[$field],
                'new' => $v2[$field]
            ];
        }
    }
    
    ApiResponse::success(['diff' => $diff, 'version1' => $v1, 'version2' => $v2], 'Version diff retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}

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

if ($action === 'add_tips') {
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $trik = sanitizeInput($_POST['trik'] ?? '');
    $akronim = sanitizeInput($_POST['akronim'] ?? '');
    $langkah = $_POST['langkah'] ?? '';
    $contohSoal = $_POST['contoh_soal'] ?? '';
    $penjelasan = $_POST['penjelasan'] ?? '';
    $topik = sanitizeInput($_POST['topik'] ?? '');
    
    if (!$subtes || !$trik) {
        echo json_encode(['error' => 'Subtes dan trik wajib diisi']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO tips_tricks (subtes, trik, akronim, langkah, contoh_soal, penjelasan, aktif)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$subtes, $trik, $akronim ?: null, $langkah ?: null, $contohSoal ?: null, $penjelasan ?: null]);
    
    echo json_encode(['success' => true, 'message' => 'Tips berhasil ditambahkan']);
    
} elseif ($action === 'edit_tips') {
    $tipsId = (int)($_POST['tips_id'] ?? 0);
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $trik = sanitizeInput($_POST['trik'] ?? '');
    $akronim = sanitizeInput($_POST['akronim'] ?? '');
    $langkah = $_POST['langkah'] ?? '';
    $contohSoal = $_POST['contoh_soal'] ?? '';
    $penjelasan = $_POST['penjelasan'] ?? '';
    $topik = sanitizeInput($_POST['topik'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$tipsId || !$subtes || !$trik) {
        ApiResponse::validationError(['tips_id' => 'Invalid parameters', 'subtes' => 'Invalid parameters', 'trik' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    $stmt = $pdo->prepare("
        UPDATE tips_tricks 
        SET subtes = ?, trik = ?, akronim = ?, langkah = ?, contoh_soal = ?, penjelasan = ?, aktif = ?
        WHERE id = ?
    ");
    $stmt->execute([$subtes, $trik, $akronim ?: null, $langkah ?: null, $contohSoal ?: null, $penjelasan ?: null, $isActive, $tipsId]);
    
    ApiResponse::success([], 'Tips berhasil diupdate');
    
} elseif ($action === 'delete_tips') {
    $tipsId = (int)($_POST['tips_id'] ?? 0);
    
    if (!$tipsId) {
        ApiResponse::validationError(['tips_id' => 'Invalid tips ID'], 'Invalid tips ID');
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE tips_tricks SET aktif = 0 WHERE id = ?");
    $stmt->execute([$tipsId]);
    
    ApiResponse::success([], 'Tips berhasil dihapus (soft delete)');
    
} elseif ($_GET['action'] === 'get_tips_list') {
    $subtes = $_GET['subtes'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($subtes) {
        $whereClause .= " AND subtes = ?";
        $params[] = $subtes;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM tips_tricks $whereClause ORDER BY id DESC");
    $stmt->execute($params);
    $tipsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['tips' => $tipsList], 'Tips list retrieved');
    
} elseif ($_GET['action'] === 'get_tips_detail') {
    $tipsId = (int)($_GET['tips_id'] ?? 0);
    
    if (!$tipsId) {
        ApiResponse::validationError(['tips_id' => 'Invalid tips ID'], 'Invalid tips ID');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM tips_tricks WHERE id = ?");
    $stmt->execute([$tipsId]);
    $tips = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tips) {
        ApiResponse::notFound('Tips not found');
    }
    
    ApiResponse::success(['tips' => $tips], 'Tips detail retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}

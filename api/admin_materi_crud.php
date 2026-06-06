<?php
require '../config.php';
require '../helpers.php';

header('Content-Type: application/json');

// Guard: admin only
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_materi') {
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $tipe = sanitizeInput($_POST['tipe'] ?? '');
    $judul = sanitizeInput($_POST['judul'] ?? '');
    $konten = $_POST['konten'] ?? '';
    $url = sanitizeInput($_POST['url'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 0);
    
    if (!$subtes || !$judul || !$konten) {
        echo json_encode(['error' => 'Subtes, judul, dan konten wajib diisi']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO materi (subtes, tipe, judul, konten, url, urutan, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?)
    ");
    $stmt->execute([$subtes, $tipe ?: null, $judul, $konten, $url ?: null, $urutan, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Materi berhasil ditambahkan']);
    
} elseif ($action === 'edit_materi') {
    $materiId = (int)($_POST['materi_id'] ?? 0);
    $subtes = sanitizeInput($_POST['subtes'] ?? '');
    $tipe = sanitizeInput($_POST['tipe'] ?? '');
    $judul = sanitizeInput($_POST['judul'] ?? '');
    $konten = $_POST['konten'] ?? '';
    $url = sanitizeInput($_POST['url'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$materiId || !$subtes || !$judul || !$konten) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE materi 
        SET subtes = ?, tipe = ?, judul = ?, konten = ?, url = ?, urutan = ?, is_active = ?, updated_by = ?
        WHERE id = ?
    ");
    $stmt->execute([$subtes, $tipe ?: null, $judul, $konten, $url ?: null, $urutan, $isActive, $_SESSION['user_id'], $materiId]);
    
    echo json_encode(['success' => true, 'message' => 'Materi berhasil diupdate']);
    
} elseif ($action === 'delete_materi') {
    $materiId = (int)($_POST['materi_id'] ?? 0);
    
    if (!$materiId) {
        echo json_encode(['error' => 'Invalid materi ID']);
        exit;
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE materi SET is_active = 0 WHERE id = ?");
    $stmt->execute([$materiId]);
    
    echo json_encode(['success' => true, 'message' => 'Materi berhasil dihapus (soft delete)']);
    
} elseif ($action === 'reorder_materi') {
    $orders = $_POST['orders'] ?? [];
    
    if (empty($orders)) {
        echo json_encode(['error' => 'No orders provided']);
        exit;
    }
    
    foreach ($orders as $order) {
        $materiId = (int)($order['id'] ?? 0);
        $newOrder = (int)($order['urutan'] ?? 0);
        
        if ($materiId) {
            $stmt = $pdo->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
            $stmt->execute([$newOrder, $materiId]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Urutan materi berhasil diupdate']);
    
} elseif ($_GET['action'] === 'get_materi_list') {
    $subtes = $_GET['subtes'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($subtes) {
        $whereClause .= " AND subtes = ?";
        $params[] = $subtes;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM materi $whereClause ORDER BY urutan ASC, id ASC");
    $stmt->execute($params);
    $materiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'materi' => $materiList]);
    
} elseif ($_GET['action'] === 'get_materi_detail') {
    $materiId = (int)($_GET['materi_id'] ?? 0);
    
    if (!$materiId) {
        echo json_encode(['error' => 'Invalid materi ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
    $stmt->execute([$materiId]);
    $materi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$materi) {
        echo json_encode(['error' => 'Materi not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'materi' => $materi]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}

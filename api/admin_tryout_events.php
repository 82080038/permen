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

if ($action === 'create_event') {
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
    $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
    $paketSoalId = (int)($_POST['paket_soal_id'] ?? 0);
    $passingGrade = (int)($_POST['passing_grade'] ?? 0);
    $maxParticipants = (int)($_POST['max_participants'] ?? 0);
    
    if (!$nama || !$tanggalMulai || !$tanggalSelesai) {
        echo json_encode(['error' => 'Nama, tanggal mulai, dan tanggal selesai wajib diisi']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO tryout_events 
        (nama, deskripsi, tanggal_mulai, tanggal_selesai, paket_soal_id, passing_grade_custom, max_participants)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nama, $deskripsi ?: null, $tanggalMulai, $tanggalSelesai, $paketSoalId ?: null, $passingGrade ?: null, $maxParticipants ?: null]);
    
    echo json_encode(['success' => true, 'message' => 'Event berhasil dibuat']);
    
} elseif ($action === 'update_event') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
    $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
    $paketSoalId = (int)($_POST['paket_soal_id'] ?? 0);
    $passingGrade = (int)($_POST['passing_grade'] ?? 0);
    $maxParticipants = (int)($_POST['max_participants'] ?? 0);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    
    if (!$eventId || !$nama) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE tryout_events 
        SET nama = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, 
            paket_soal_id = ?, passing_grade_custom = ?, max_participants = ?, aktif = ?
        WHERE id = ?
    ");
    $stmt->execute([$nama, $deskripsi ?: null, $tanggalMulai ?: null, $tanggalSelesai ?: null, $paketSoalId ?: null, $passingGrade ?: null, $maxParticipants ?: null, $aktif, $eventId]);
    
    echo json_encode(['success' => true, 'message' => 'Event berhasil diupdate']);
    
} elseif ($action === 'delete_event') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    
    if (!$eventId) {
        echo json_encode(['error' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM tryout_events WHERE id = ?");
    $stmt->execute([$eventId]);
    
    echo json_encode(['success' => true, 'message' => 'Event berhasil dihapus']);
    
} elseif ($action === 'register_user') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$eventId || !$userId) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tryout_event_registrations (event_id, user_id) VALUES (?, ?)");
        $stmt->execute([$eventId, $userId]);
        echo json_encode(['success' => true, 'message' => 'User berhasil diregistrasi']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['error' => 'User sudah terdaftar di event ini']);
        } else {
            echo json_encode(['error' => 'Gagal registrasi user']);
        }
    }
    
} elseif ($action === 'unregister_user') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$eventId || !$userId) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM tryout_event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'User berhasil di-unregistrasi']);
    
} elseif ($_GET['action'] === 'get_events') {
    $stmt = $pdo->query("
        SELECT e.*, tp.nama as paket_nama, 
               (SELECT COUNT(*) FROM tryout_event_registrations WHERE event_id = e.id) as participant_count
        FROM tryout_events e
        LEFT JOIN tryout_packages tp ON e.paket_soal_id = tp.id
        ORDER BY e.tanggal_mulai DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'events' => $events]);
    
} elseif ($_GET['action'] === 'get_event_participants') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    
    if (!$eventId) {
        echo json_encode(['error' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.nama, u.no_hp, u.instansi
        FROM tryout_event_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ?
        ORDER BY r.registered_at DESC
    ");
    $stmt->execute([$eventId]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'participants' => $participants]);
    
} elseif ($_GET['action'] === 'get_event_results') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    
    if (!$eventId) {
        echo json_encode(['error' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT ts.*, u.nama as peserta, u.instansi
        FROM tryout_sessions ts
        JOIN users u ON ts.user_id = u.id
        JOIN tryout_event_registrations r ON r.user_id = u.id
        WHERE r.event_id = ? AND ts.status = 'selesai'
        ORDER BY ts.total_nilai DESC
    ");
    $stmt->execute([$eventId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'results' => $results]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}

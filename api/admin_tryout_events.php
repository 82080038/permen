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

if ($action === 'create_event') {
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
    $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
    $paketSoalId = (int)($_POST['paket_soal_id'] ?? 0);
    $passingGrade = (int)($_POST['passing_grade'] ?? 0);
    $maxParticipants = (int)($_POST['max_participants'] ?? 0);
    
    if (!$nama || !$tanggalMulai || !$tanggalSelesai) {
        ApiResponse::validationError(['nama' => 'Nama, tanggal mulai, dan tanggal selesai wajib diisi', 'tanggal_mulai' => 'Nama, tanggal mulai, dan tanggal selesai wajib diisi', 'tanggal_selesai' => 'Nama, tanggal mulai, dan tanggal selesai wajib diisi'], 'Nama, tanggal mulai, dan tanggal selesai wajib diisi');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO tryout_events 
        (nama, deskripsi, tanggal_mulai, tanggal_selesai, paket_soal_id, passing_grade_custom, max_participants)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nama, $deskripsi ?: null, $tanggalMulai, $tanggalSelesai, $paketSoalId ?: null, $passingGrade ?: null, $maxParticipants ?: null]);
    
    ApiResponse::success([], 'Event berhasil dibuat');
    
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
        ApiResponse::validationError(['event_id' => 'Invalid parameters', 'nama' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    $stmt = $pdo->prepare("
        UPDATE tryout_events 
        SET nama = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, 
            paket_soal_id = ?, passing_grade_custom = ?, max_participants = ?, aktif = ?
        WHERE id = ?
    ");
    $stmt->execute([$nama, $deskripsi ?: null, $tanggalMulai ?: null, $tanggalSelesai ?: null, $paketSoalId ?: null, $passingGrade ?: null, $maxParticipants ?: null, $aktif, $eventId]);
    
    ApiResponse::success([], 'Event berhasil diupdate');
    
} elseif ($action === 'delete_event') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    
    if (!$eventId) {
        ApiResponse::validationError(['event_id' => 'Invalid event ID'], 'Invalid event ID');
    }
    
    $stmt = $pdo->prepare("DELETE FROM tryout_events WHERE id = ?");
    $stmt->execute([$eventId]);
    
    ApiResponse::success([], 'Event berhasil dihapus');
    
} elseif ($action === 'register_user') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$eventId || !$userId) {
        ApiResponse::validationError(['event_id' => 'Invalid parameters', 'user_id' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tryout_event_registrations (event_id, user_id) VALUES (?, ?)");
        $stmt->execute([$eventId, $userId]);
        ApiResponse::success([], 'User berhasil diregistrasi');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            ApiResponse::validationError(['user' => 'User sudah terdaftar di event ini'], 'User sudah terdaftar di event ini');
        } else {
            ApiResponse::serverError('Gagal registrasi user');
        }
    }
    
} elseif ($action === 'unregister_user') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$eventId || !$userId) {
        ApiResponse::validationError(['event_id' => 'Invalid parameters', 'user_id' => 'Invalid parameters'], 'Invalid parameters');
    }
    
    $stmt = $pdo->prepare("DELETE FROM tryout_event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);
    
    ApiResponse::success([], 'User berhasil di-unregistrasi');
    
} elseif ($_GET['action'] === 'get_events') {
    $stmt = $pdo->query("
        SELECT e.*, tp.nama as paket_nama, 
               (SELECT COUNT(*) FROM tryout_event_registrations WHERE event_id = e.id) as participant_count
        FROM tryout_events e
        LEFT JOIN tryout_packages tp ON e.paket_soal_id = tp.id
        ORDER BY e.tanggal_mulai DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ApiResponse::success(['events' => $events], 'Events retrieved');
    
} elseif ($_GET['action'] === 'get_event_participants') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    
    if (!$eventId) {
        ApiResponse::validationError(['event_id' => 'Invalid event ID'], 'Invalid event ID');
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
    
    ApiResponse::success(['participants' => $participants], 'Participants retrieved');
    
} elseif ($_GET['action'] === 'get_event_results') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    
    if (!$eventId) {
        ApiResponse::validationError(['event_id' => 'Invalid event ID'], 'Invalid event ID');
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
    
    ApiResponse::success(['results' => $results], 'Results retrieved');
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}

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

if ($action === 'add_to_queue') {
    $soalId = (int)($_POST['soal_id'] ?? 0);
    $priority = sanitizeInput($_POST['priority'] ?? 'medium');
    $reason = $_POST['reason'] ?? '';
    
    if (!$soalId) {
        echo json_encode(['error' => 'Invalid soal ID']);
        exit;
    }
    
    // Check if already in queue
    $stmt = $pdo->prepare("SELECT id FROM revision_queue WHERE soal_id = ? AND status IN ('pending', 'assigned', 'in_progress')");
    $stmt->execute([$soalId]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Soal already in revision queue']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO revision_queue (soal_id, priority, reason, assigned_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$soalId, $priority, $reason ?: null, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Soal added to revision queue']);
    
} elseif ($action === 'assign_revision') {
    $queueId = (int)($_POST['queue_id'] ?? 0);
    $assignedTo = (int)($_POST['assigned_to'] ?? 0);
    
    if (!$queueId || !$assignedTo) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE revision_queue 
        SET status = 'assigned', assigned_to = ?, assigned_by = ?, assigned_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$assignedTo, $_SESSION['user_id'], $queueId]);
    
    echo json_encode(['success' => true, 'message' => 'Revision assigned']);
    
} elseif ($action === 'update_status') {
    $queueId = (int)($_POST['queue_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    if (!$queueId || !$status) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    $validStatuses = ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE revision_queue 
        SET status = ?, admin_notes = ?, updated_at = NOW()
        " . ($status === 'completed' ? ', completed_at = NOW()' : '') . "
        WHERE id = ?
    ");
    $stmt->execute([$status, $adminNotes ?: null, $queueId]);
    
    echo json_encode(['success' => true, 'message' => 'Status updated']);
    
} elseif ($action === 'update_priority') {
    $queueId = (int)($_POST['queue_id'] ?? 0);
    $priority = sanitizeInput($_POST['priority'] ?? 'medium');
    
    if (!$queueId) {
        echo json_encode(['error' => 'Invalid queue ID']);
        exit;
    }
    
    $validPriorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        echo json_encode(['error' => 'Invalid priority']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE revision_queue SET priority = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$priority, $queueId]);
    
    echo json_encode(['success' => true, 'message' => 'Priority updated']);
    
} elseif ($action === 'remove_from_queue') {
    $queueId = (int)($_POST['queue_id'] ?? 0);
    
    if (!$queueId) {
        echo json_encode(['error' => 'Invalid queue ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE revision_queue SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$queueId]);
    
    echo json_encode(['success' => true, 'message' => 'Removed from queue']);
    
} elseif ($_GET['action'] === 'get_queue') {
    $status = $_GET['status'] ?? '';
    $priority = $_GET['priority'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($status) {
        $whereClause .= " AND rq.status = ?";
        $params[] = $status;
    }
    if ($priority) {
        $whereClause .= " AND rq.priority = ?";
        $params[] = $priority;
    }
    
    $stmt = $pdo->prepare("
        SELECT rq.*, q.pertanyaan, q.subtes, q.tipe, q.topik,
               assigned.nama as assigned_to_name, assigned_by.nama as assigned_by_name
        FROM revision_queue rq
        JOIN questions q ON rq.soal_id = q.id
        LEFT JOIN users assigned ON rq.assigned_to = assigned.id
        LEFT JOIN users assigned_by_user ON rq.assigned_by = assigned_by_user.id
        $whereClause
        ORDER BY 
            FIELD(rq.priority, 'urgent', 'high', 'medium', 'low'),
            rq.created_at ASC
    ");
    $stmt->execute($params);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'queue' => $queue]);
    
} elseif ($_GET['action'] === 'get_revision_stats') {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM revision_queue
        WHERE status != 'cancelled'
    ");
    $stats = $stmt->fetch();
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}

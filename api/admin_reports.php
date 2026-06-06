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

if ($action === 'generate_report') {
    $reportType = sanitizeInput($_POST['report_type'] ?? '');
    $filters = $_POST['filters'] ?? [];
    $filtersJson = !empty($filters) ? json_encode($filters) : null;
    
    $validReportTypes = ['user_activity', 'tryout_results', 'content_performance', 'revenue'];
    if (!in_array($reportType, $validReportTypes)) {
        echo json_encode(['error' => 'Invalid report type']);
        exit;
    }
    
    // Generate report data
    $reportData = generateReportData($reportType, $filters);
    
    // Generate CSV file
    $filename = generateCSVReport($reportType, $reportData);
    
    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO admin_reports (report_type, title, description, filters, file_path, file_type, generated_by, generated_at)
        VALUES (?, ?, ?, ?, ?, 'csv', ?, NOW())
    ");
    $stmt->execute([
        $reportType,
        ucfirst(str_replace('_', ' ', $reportType)) . ' Report',
        'Generated on ' . date('Y-m-d H:i:s'),
        $filtersJson,
        $filename,
        $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Report generated', 'file' => $filename]);
    
} elseif ($_GET['action'] === 'get_reports') {
    $reportType = $_GET['report_type'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($reportType) {
        $whereClause .= " AND report_type = ?";
        $params[] = $reportType;
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, u.nama as generated_by_name
        FROM admin_reports r
        LEFT JOIN users u ON r.generated_by = u.id
        $whereClause
        ORDER BY r.generated_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'reports' => $reports]);
    
} elseif ($_GET['action'] === 'get_schedules') {
    $stmt = $pdo->query("
        SELECT s.*, u.nama as created_by_name
        FROM report_schedules s
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.is_active DESC, s.next_run_at ASC
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    
} elseif ($action === 'create_schedule') {
    $reportType = sanitizeInput($_POST['report_type'] ?? '');
    $title = sanitizeInput($_POST['title'] ?? '');
    $scheduleType = sanitizeInput($_POST['schedule_type'] ?? 'daily');
    $scheduleDay = (int)($_POST['schedule_day'] ?? 0) ?: null;
    $scheduleTime = $_POST['schedule_time'] ?? '00:00';
    $filters = $_POST['filters'] ?? [];
    $filtersJson = !empty($filters) ? json_encode($filters) : null;
    
    $validReportTypes = ['user_activity', 'tryout_results', 'content_performance', 'revenue'];
    $validScheduleTypes = ['daily', 'weekly', 'monthly'];
    
    if (!in_array($reportType, $validReportTypes)) {
        echo json_encode(['error' => 'Invalid report type']);
        exit;
    }
    if (!in_array($scheduleType, $validScheduleTypes)) {
        echo json_encode(['error' => 'Invalid schedule type']);
        exit;
    }
    
    // Calculate next run time
    $nextRunAt = calculateNextRunTime($scheduleType, $scheduleDay, $scheduleTime);
    
    $stmt = $pdo->prepare("
        INSERT INTO report_schedules (report_type, title, schedule_type, schedule_day, schedule_time, filters, next_run_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $reportType, $title, $scheduleType, $scheduleDay, $scheduleTime, $filtersJson, $nextRunAt, $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Schedule created']);
    
} elseif ($action === 'toggle_schedule') {
    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$scheduleId) {
        echo json_encode(['error' => 'Invalid schedule ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE report_schedules SET is_active = ? WHERE id = ?");
    $stmt->execute([$isActive, $scheduleId]);
    
    echo json_encode(['success' => true, 'message' => 'Schedule updated']);
    
} elseif ($action === 'delete_schedule') {
    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    
    if (!$scheduleId) {
        echo json_encode(['error' => 'Invalid schedule ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM report_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    
    echo json_encode(['success' => true, 'message' => 'Schedule deleted']);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}

function generateReportData($reportType, $filters) {
    global $pdo;
    
    $data = [];
    
    switch ($reportType) {
        case 'user_activity':
            $stmt = $pdo->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT user_id) as active_users,
                    COUNT(*) as total_events,
                    SUM(time_spent_seconds) as total_time_spent
                FROM learning_analytics
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'tryout_results':
            $stmt = $pdo->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_tryouts,
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score
                FROM tryout_results
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'content_performance':
            $stmt = $pdo->query("
                SELECT 
                    subtes,
                    COUNT(DISTINCT soal_id) as total_soal,
                    COUNT(*) as total_views,
                    AVG(CASE WHEN event_type = 'soal_answer' THEN 1 ELSE 0 END) as avg_attempts
                FROM learning_analytics
                WHERE subtes IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY subtes
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'revenue':
            // Placeholder for revenue tracking
            $data = [
                ['date' => date('Y-m-d'), 'revenue' => 0, 'users' => 0]
            ];
            break;
    }
    
    return $data;
}

function generateCSVReport($reportType, $data) {
    $filename = 'reports/' . $reportType . '_' . date('Y-m-d_His') . '.csv';
    $filepath = __DIR__ . '/../' . $filename;
    
    // Create reports directory if not exists
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $file = fopen($filepath, 'w');
    
    if (!empty($data)) {
        // Write header
        fputcsv($file, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
    }
    
    fclose($file);
    
    return '/' . $filename;
}

function calculateNextRunTime($scheduleType, $scheduleDay, $scheduleTime) {
    $now = new DateTime();
    $time = explode(':', $scheduleTime);
    $hour = (int)$time[0];
    $minute = (int)$time[1];
    
    $nextRun = clone $now;
    $nextRun->setTime($hour, $minute, 0);
    
    switch ($scheduleType) {
        case 'daily':
            if ($nextRun <= $now) {
                $nextRun->modify('+1 day');
            }
            break;
            
        case 'weekly':
            $nextRun->modify('next ' . getDayName($scheduleDay));
            if ($nextRun <= $now) {
                $nextRun->modify('+1 week');
            }
            break;
            
        case 'monthly':
            $nextRun->setDate((int)$nextRun->format('Y'), (int)$nextRun->format('m'), $scheduleDay);
            if ($nextRun <= $now) {
                $nextRun->modify('+1 month');
            }
            break;
    }
    
    return $nextRun->format('Y-m-d H:i:s');
}

function getDayName($dayNumber) {
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    return $days[$dayNumber] ?? 'Monday';
}

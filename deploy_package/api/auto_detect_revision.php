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

$action = $_GET['action'] ?? '';

if ($action === 'detect_revision_candidates') {
    $candidates = [];
    
    // 1. Soal with answer rate < 20% (might be ambiguous)
    $stmt = $pdo->query("
        SELECT q.id, q.pertanyaan, q.subtes, q.tipe,
            COUNT(DISTINCT a.user_id) as total_attempts,
            SUM(CASE WHEN a.jawaban = q.jawaban_benar THEN 1 ELSE 0 END) as correct_answers
        FROM questions q
        LEFT JOIN answers a ON q.id = a.soal_id
        WHERE q.is_active = 1
        GROUP BY q.id
        HAVING total_attempts > 10 AND (correct_answers / total_attempts) < 0.2
    ");
    $lowAnswerRate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lowAnswerRate as $soal) {
        $candidates[] = [
            'soal_id' => $soal['id'],
            'pertanyaan' => $soal['pertanyaan'],
            'subtes' => $soal['subtes'],
            'tipe' => $soal['tipe'],
            'reason' => 'Low answer rate: ' . round(($soal['correct_answers'] / $soal['total_attempts']) * 100, 1) . '% correct',
            'priority' => 'high',
            'metric' => 'answer_rate',
            'value' => round(($soal['correct_answers'] / $soal['total_attempts']) * 100, 1)
        ];
    }
    
    // 2. Soal with many "ragu-ragu" flags
    $stmt = $pdo->query("
        SELECT q.id, q.pertanyaan, q.subtes, q.tipe,
            COUNT(DISTINCT a.user_id) as total_attempts,
            SUM(CASE WHEN a.jawaban = 'M' THEN 1 ELSE 0 END) as ragu_count
        FROM questions q
        LEFT JOIN answers a ON q.id = a.soal_id
        WHERE q.is_active = 1
        GROUP BY q.id
        HAVING total_attempts > 10 AND (ragu_count / total_attempts) > 0.3
    ");
    $manyRagu = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($manyRagu as $soal) {
        $raguPercent = round(($soal['ragu_count'] / $soal['total_attempts']) * 100, 1);
        $candidates[] = [
            'soal_id' => $soal['id'],
            'pertanyaan' => $soal['pertanyaan'],
            'subtes' => $soal['subtes'],
            'tipe' => $soal['tipe'],
            'reason' => "High 'ragu-ragu' rate: {$raguPercent}%",
            'priority' => 'medium',
            'metric' => 'ragu_rate',
            'value' => $raguPercent
        ];
    }
    
    // 3. Soal not revised for > 6 months
    $stmt = $pdo->query("
        SELECT q.id, q.pertanyaan, q.subtes, q.tipe,
            MAX(sv.edited_at) as last_revision,
            q.created_at as created_at
        FROM questions q
        LEFT JOIN soal_versions sv ON q.id = sv.soal_id
        WHERE q.is_active = 1
        GROUP BY q.id
        HAVING 
            (last_revision IS NULL AND q.created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH))
            OR
            (last_revision IS NOT NULL AND last_revision < DATE_SUB(NOW(), INTERVAL 6 MONTH))
    ");
    $oldSoal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($oldSoal as $soal) {
        $lastEdit = $soal['last_revision'] ?? $soal['created_at'];
        $monthsAgo = round((time() - strtotime($lastEdit)) / (30 * 24 * 60 * 60), 1);
        $candidates[] = [
            'soal_id' => $soal['id'],
            'pertanyaan' => $soal['pertanyaan'],
            'subtes' => $soal['subtes'],
            'tipe' => $soal['tipe'],
            'reason' => "Not revised for {$monthsAgo} months",
            'priority' => 'low',
            'metric' => 'age_months',
            'value' => $monthsAgo
        ];
    }
    
    // Remove duplicates (same soal_id)
    $uniqueCandidates = [];
    $seenIds = [];
    foreach ($candidates as $c) {
        if (!in_array($c['soal_id'], $seenIds)) {
            $uniqueCandidates[] = $c;
            $seenIds[] = $c['soal_id'];
        }
    }
    
    echo json_encode(['success' => true, 'candidates' => $uniqueCandidates]);
    
} elseif ($action === 'add_candidate_to_queue') {
    $soalId = (int)($_GET['soal_id'] ?? 0);
    $priority = sanitizeInput($_GET['priority'] ?? 'medium');
    $reason = $_GET['reason'] ?? '';
    
    if (!$soalId) {
        ApiResponse::validationError(['soal_id' => 'Invalid soal ID'], 'Invalid soal ID');
    }
    
    // Check if already in queue
    $stmt = $pdo->prepare("SELECT id FROM revision_queue WHERE soal_id = ? AND status IN ('pending', 'assigned', 'in_progress')");
    $stmt->execute([$soalId]);
    if ($stmt->fetch()) {
        ApiResponse::validationError(['soal_id' => 'Soal already in revision queue'], 'Soal already in revision queue');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO revision_queue (soal_id, priority, reason, assigned_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$soalId, $priority, $reason ?: null, $_SESSION['user_id']]);
    
    ApiResponse::success([], 'Soal added to revision queue');
    
} elseif ($action === 'add_all_candidates') {
    $priority = sanitizeInput($_GET['priority'] ?? 'medium');
    
    // Get candidates
    $stmt = $pdo->query("
        SELECT q.id, q.pertanyaan, q.subtes, q.tipe,
            COUNT(DISTINCT a.user_id) as total_attempts,
            SUM(CASE WHEN a.jawaban = q.jawaban_benar THEN 1 ELSE 0 END) as correct_answers,
            SUM(CASE WHEN a.jawaban = 'M' THEN 1 ELSE 0 END) as ragu_count,
            MAX(sv.edited_at) as last_revision,
            q.created_at as created_at
        FROM questions q
        LEFT JOIN answers a ON q.id = a.soal_id
        LEFT JOIN soal_versions sv ON q.id = sv.soal_id
        WHERE q.is_active = 1
        GROUP BY q.id
    ");
    $allSoal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $addedCount = 0;
    $skippedCount = 0;
    
    foreach ($allSoal as $soal) {
        $soalId = $soal['id'];
        $reasons = [];
        $detectedPriority = $priority;
        
        // Check criteria
        if ($soal['total_attempts'] > 10) {
            $answerRate = $soal['correct_answers'] / $soal['total_attempts'];
            if ($answerRate < 0.2) {
                $reasons[] = 'Low answer rate: ' . round($answerRate * 100, 1) . '%';
                $detectedPriority = 'high';
            }
            
            $raguRate = $soal['ragu_count'] / $soal['total_attempts'];
            if ($raguRate > 0.3) {
                $reasons[] = 'High ragu rate: ' . round($raguRate * 100, 1) . '%';
                if ($detectedPriority !== 'high') $detectedPriority = 'medium';
            }
        }
        
        $lastEdit = $soal['last_revision'] ?? $soal['created_at'];
        $monthsAgo = (time() - strtotime($lastEdit)) / (30 * 24 * 60 * 60);
        if ($monthsAgo > 6) {
            $reasons[] = 'Not revised for ' . round($monthsAgo, 1) . ' months';
            if ($detectedPriority === 'low') $detectedPriority = 'medium';
        }
        
        if (empty($reasons)) continue;
        
        // Check if already in queue
        $checkStmt = $pdo->prepare("SELECT id FROM revision_queue WHERE soal_id = ? AND status IN ('pending', 'assigned', 'in_progress')");
        $checkStmt->execute([$soalId]);
        if ($checkStmt->fetch()) {
            $skippedCount++;
            continue;
        }
        
        // Add to queue
        $insertStmt = $pdo->prepare("
            INSERT INTO revision_queue (soal_id, priority, reason, assigned_by)
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$soalId, $detectedPriority, implode('; ', $reasons), $_SESSION['user_id']]);
        $addedCount++;
    }
    
    ApiResponse::success(['added_count' => $addedCount, 'skipped_count' => $skippedCount], "Added {$addedCount} soal to queue, skipped {$skippedCount} already in queue");
    
} else {
    ApiResponse::validationError(['action' => 'Invalid action'], 'Invalid action');
}

<?php
/**
 * Simulation Setup Script
 * - Update admin credentials
 * - Hapus semua data user non-admin beserta relasi
 * 
 * HANYA untuk diakses dari localhost
 */

// Guard: hanya dari localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require __DIR__ . '/../config.php';
require __DIR__ . '/../helpers.php';

header('Content-Type: application/json; charset=utf-8');

$results = [];

try {
    // ─────────────────────────────────────────
    // 1. Update admin credentials
    // ─────────────────────────────────────────
    $newHp   = '081265511982';
    $newPass = '82080038';
    $hash    = password_hash($newPass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE users SET no_hp = ?, password_hash = ?, failed_attempts = 0, lockout_until = NULL WHERE role = 'admin' LIMIT 1");
    $stmt->execute([$newHp, $hash]);
    $affected = $stmt->rowCount();
    $results['admin_update'] = [
        'status' => 'ok',
        'rows_affected' => $affected,
        'new_hp' => $newHp
    ];

    // ─────────────────────────────────────────
    // 2. Ambil semua user non-admin (kecuali test users)
    // ─────────────────────────────────────────
    $testPhones = ['081987654321', '081234567890'];
    $placeholders = implode(',', array_fill(0, count($testPhones), '?'));
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role != 'admin' AND no_hp NOT IN ($placeholders)");
    $stmt->execute($testPhones);
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $results['users_to_delete'] = count($userIds);

    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        // Hapus data terkait (urutan dari child ke parent)
        $tables = [
            // child tables dengan FK ke users atau tryout_sessions
            'daily_quiz_sessions'       => 'user_id',
            'user_achievements'         => 'user_id',
            'user_quiz_difficulty'      => 'user_id',
            'leaderboard_history'       => 'user_id',
            'user_progress'             => 'user_id',
            'user_question_history'     => 'user_id',
            'user_materi_progress'      => 'user_id',
            'notifications'             => 'user_id',
            'feedback'                  => 'user_id',
            'bookmarks'                 => 'user_id',
            'user_audit_logs'           => 'user_id',
            'rate_limits'               => null,          // by IP, skip
            'password_reset_requests'   => 'user_id',
            'scheduled_tryout_registrations' => 'user_id',
        ];

        foreach ($tables as $table => $col) {
            if ($col === null) continue;
            // cek tabel ada
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetchColumn();
            if (!$check) {
                $results["delete_$table"] = 'table not found, skipped';
                continue;
            }
            $del = $pdo->prepare("DELETE FROM `$table` WHERE `$col` IN ($placeholders)");
            $del->execute($userIds);
            $results["delete_$table"] = $del->rowCount() . ' rows deleted';
        }

        // Hapus answers via tryout_sessions
        $checkTS = $pdo->query("SHOW TABLES LIKE 'tryout_sessions'")->fetchColumn();
        if ($checkTS) {
            // Ambil session ids milik user
            $sessStmt = $pdo->prepare("SELECT id FROM tryout_sessions WHERE user_id IN ($placeholders)");
            $sessStmt->execute($userIds);
            $sessIds = $sessStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($sessIds)) {
                $sp = implode(',', array_fill(0, count($sessIds), '?'));
                // answers
                $checkAns = $pdo->query("SHOW TABLES LIKE 'answers'")->fetchColumn();
                if ($checkAns) {
                    $del = $pdo->prepare("DELETE FROM answers WHERE session_id IN ($sp)");
                    $del->execute($sessIds);
                    $results['delete_answers'] = $del->rowCount() . ' rows deleted';
                }
                // session_subtes
                $checkSS = $pdo->query("SHOW TABLES LIKE 'session_subtes'")->fetchColumn();
                if ($checkSS) {
                    $del = $pdo->prepare("DELETE FROM session_subtes WHERE session_id IN ($sp)");
                    $del->execute($sessIds);
                    $results['delete_session_subtes'] = $del->rowCount() . ' rows deleted';
                }
            }

            // Hapus tryout_sessions
            $del = $pdo->prepare("DELETE FROM tryout_sessions WHERE user_id IN ($placeholders)");
            $del->execute($userIds);
            $results['delete_tryout_sessions'] = $del->rowCount() . ' rows deleted';
        }

        // Hapus daily_quiz_sessions (jika ada answers di dalamnya)
        $checkDQA = $pdo->query("SHOW TABLES LIKE 'daily_quiz_answers'")->fetchColumn();
        if ($checkDQA) {
            // Ambil daily session ids
            $dqStmt = $pdo->prepare("SELECT id FROM daily_quiz_sessions WHERE user_id IN ($placeholders)");
            $dqStmt->execute($userIds);
            $dqIds = $dqStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($dqIds)) {
                $dp = implode(',', array_fill(0, count($dqIds), '?'));
                $del = $pdo->prepare("DELETE FROM daily_quiz_answers WHERE session_id IN ($dp)");
                $del->execute($dqIds);
                $results['delete_daily_quiz_answers'] = $del->rowCount() . ' rows deleted';
            }
        }

        // Hapus users
        $del = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $del->execute($userIds);
        $results['delete_users'] = $del->rowCount() . ' rows deleted';
    }

    // ─────────────────────────────────────────
    // 3. Re-create test users (untuk testing)
    // ─────────────────────────────────────────
    $testUsers = [
        [
            'no_hp' => '081987654321',
            'nama' => 'User Test',
            'password' => 'password',
            'role' => 'user'
        ],
        [
            'no_hp' => '081234567890',
            'nama' => 'Admin Test',
            'password' => 'password',
            'role' => 'admin'
        ]
    ];

    foreach ($testUsers as $tu) {
        $hash = password_hash($tu['password'], PASSWORD_BCRYPT);
        $check = $pdo->prepare("SELECT id FROM users WHERE no_hp = ?");
        $check->execute([$tu['no_hp']]);
        $exists = $check->fetch();

        if ($exists) {
            // Update password hash
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE no_hp = ?");
            $upd->execute([$hash, $tu['no_hp']]);
            $results["update_test_user_{$tu['no_hp']}"] = 'password updated';
        } else {
            // Insert new
            $ins = $pdo->prepare("INSERT INTO users (no_hp, nama, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $ins->execute([$tu['no_hp'], $tu['nama'], $hash, $tu['role']]);
            $results["create_test_user_{$tu['no_hp']}"] = 'created';
        }
    }

    $results['status'] = 'success';
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'partial_results' => $results
    ], JSON_PRETTY_PRINT);
}

<?php
// Comprehensive Session Monitoring System
header('Content-Type: application/json');

$monitor = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_status' => [],
    'database_sessions' => [],
    'file_sessions' => [],
    'performance_metrics' => [],
    'user_journey_test' => [],
    'system_health' => []
];

// 1. Current Session Status
function getCurrentSessionStatus() {
    session_start();
    
    return [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'session_data' => $_SESSION,
        'cookie_data' => $_COOKIE,
        'session_handler' => ini_get('session.save_handler'),
        'session_path' => ini_get('session.save_path'),
        'session_lifetime' => ini_get('session.gc_maxlifetime'),
        'user_authenticated' => !empty($_SESSION['user_id']),
        'user_data' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_nama' => $_SESSION['user_nama'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ]
    ];
}

$monitor['session_status'] = getCurrentSessionStatus();

// 2. Database Sessions Analysis
function analyzeDatabaseSessions() {
    global $pdo;
    
    if (!isset($pdo)) {
        return ['error' => 'Database not available'];
    }
    
    try {
        // Check if session table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_sessions'");
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            return ['error' => 'Session table does not exist'];
        }
        
        // Analyze active sessions
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN timestamp > ? THEN 1 END) as active FROM user_sessions");
        $stmt->execute([time() - 3600]);
        $sessionStats = $stmt->fetch();
        
        // Get recent sessions
        $stmt = $pdo->prepare("SELECT id, user_id, ip_address, user_agent, created_at, updated_at FROM user_sessions ORDER BY updated_at DESC LIMIT 5");
        $stmt->execute();
        $recentSessions = $stmt->fetchAll();
        
        return [
            'table_exists' => true,
            'total_sessions' => $sessionStats['total'],
            'active_sessions' => $sessionStats['active'],
            'recent_sessions' => $recentSessions,
            'session_storage_working' => true
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

$monitor['database_sessions'] = analyzeDatabaseSessions();

// 3. File Sessions Analysis
function analyzeFileSessions() {
    $sessionPath = ini_get('session.save_path');
    
    if (!is_dir($sessionPath)) {
        return ['error' => 'Session path not accessible'];
    }
    
    try {
        $files = glob($sessionPath . '/sess_*');
        $totalFiles = count($files);
        $activeFiles = 0;
        $fileDetails = [];
        
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime > (time() - 3600)) {
                $activeFiles++;
            }
            
            $fileDetails[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', $fileTime),
                'is_active' => $fileTime > (time() - 3600)
            ];
        }
        
        return [
            'session_path' => $sessionPath,
            'total_files' => $totalFiles,
            'active_files' => $activeFiles,
            'file_details' => array_slice($fileDetails, 0, 5),
            'file_storage_working' => true
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

$monitor['file_sessions'] = analyzeFileSessions();

// 4. Performance Metrics
function getPerformanceMetrics() {
    $startTime = microtime(true);
    
    // Test session read/write performance
    session_start();
    $_SESSION['performance_test'] = microtime(true);
    session_write_close();
    
    session_start();
    $readTime = microtime(true) - $_SESSION['performance_test'];
    session_write_close();
    
    // Memory usage
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    
    return [
        'session_read_write_time' => round($readTime * 1000, 2) . 'ms',
        'memory_usage' => round($memoryUsage / 1024 / 1024, 2) . 'MB',
        'memory_peak' => round($memoryPeak / 1024 / 1024, 2) . 'MB',
        'session_handler' => ini_get('session.save_handler'),
        'performance_score' => ($readTime < 0.01) ? 'excellent' : (($readTime < 0.05) ? 'good' : 'needs_improvement')
    ];
}

$monitor['performance_metrics'] = getPerformanceMetrics();

// 5. Complete User Journey Test
function testUserJourney() {
    $journey = [];
    
    // Step 1: Get login page
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://bimbel.bereng.info/pages/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/journey_cookies.txt');
    $loginPage = curl_exec($ch);
    $journey['login_page_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Extract CSRF token
    preg_match('/csrf_token.*value="([^"]*)"/', $loginPage, $matches);
    $csrfToken = $matches[1] ?? '';
    $journey['csrf_token_extracted'] = !empty($csrfToken);
    
    // Step 2: Perform login
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://bimbel.bereng.info/pages/login.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'no_hp' => '081987654321',
        'password' => 'Sihaloho1982',
        'csrf_token' => $csrfToken
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/journey_cookies.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $loginResponse = curl_exec($ch);
    $journey['login_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Step 3: Access dashboard
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://bimbel.bereng.info/pages/user_dashboard.php');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/journey_cookies.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $dashboardResponse = curl_exec($ch);
    $journey['dashboard_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for redirect
    if (preg_match('/Location:\s*(.*)/i', $dashboardResponse, $matches)) {
        $journey['dashboard_redirect'] = trim($matches[1]);
    } else {
        $journey['dashboard_redirect'] = 'none';
    }
    
    curl_close($ch);
    
    // Step 4: Overall assessment
    $journey['overall_success'] = (
        $journey['login_page_status'] === 200 &&
        $journey['login_status'] === 200 &&
        $journey['dashboard_status'] === 200 &&
        $journey['dashboard_redirect'] === 'none'
    );
    
    return $journey;
}

$monitor['user_journey_test'] = testUserJourney();

// 6. System Health Assessment
function getSystemHealth() {
    $health = [
        'session_handler' => ini_get('session.save_handler'),
        'session_path_writable' => is_writable(ini_get('session.save_path')),
        'database_available' => isset($GLOBALS['pdo']),
        'memory_usage_percent' => round(memory_get_usage(true) / (1024 * 1024 * 1024) * 100, 2),
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    // Calculate health score
    $score = 100;
    if (!$health['session_path_writable']) $score -= 25;
    if (!$health['database_available']) $score -= 25;
    if ($health['memory_usage_percent'] > 80) $score -= 20;
    
    $health['health_score'] = max(0, $score);
    $health['health_status'] = ($score >= 80) ? 'excellent' : (($score >= 60) ? 'good' : 'needs_attention');
    
    return $health;
}

$monitor['system_health'] = getSystemHealth();

// 7. Overall Assessment
$monitor['overall_assessment'] = [
    'session_persistence_working' => $monitor['user_journey_test']['overall_success'],
    'database_sessions_active' => isset($monitor['database_sessions']['active_sessions']) && $monitor['database_sessions']['active_sessions'] > 0,
    'performance_acceptable' => $monitor['performance_metrics']['performance_score'] !== 'needs_improvement',
    'system_healthy' => $monitor['system_health']['health_score'] >= 80,
    'production_ready' => $monitor['user_journey_test']['overall_success'] && $monitor['system_health']['health_score'] >= 80
];

echo json_encode($monitor, JSON_PRETTY_PRINT);
?>

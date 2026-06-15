<?php
// Browser vs Curl Session Analysis Tool
header('Content-Type: application/json');

$analysis = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request_analysis' => [],
    'session_differences' => [],
    'browser_specific_issues' => [],
    'findings' => [],
    'recommendations' => []
];

// 1. Analyze current request characteristics
$analysis['request_analysis'] = [
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No User Agent',
    'accept_headers' => [
        'accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Not Set',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Not Set',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Not Set',
    ],
    'connection' => $_SERVER['HTTP_CONNECTION'] ?? 'Not Set',
    'upgrade_insecure_requests' => $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? 'Not Set',
    'sec_fetch_dest' => $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'Not Set',
    'sec_fetch_mode' => $_SERVER['HTTP_SEC_FETCH_MODE'] ?? 'Not Set',
    'sec_fetch_site' => $_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'Not Set',
    'sec_fetch_user' => $_SERVER['HTTP_SEC_FETCH_USER'] ?? 'Not Set',
    'cookies_received' => $_COOKIE,
    'session_data' => $_SESSION ?? [],
    'session_id' => session_id() ?? 'No Session'
];

// 2. Session differences analysis
function analyzeSessionDifferences() {
    $differences = [];
    
    // Check session handler
    $currentHandler = ini_get('session.save_handler');
    $differences['session_handler'] = $currentHandler;
    
    // Check session configuration
    $differences['session_config'] = [
        'save_path' => ini_get('session.save_path'),
        'cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'cookie_path' => ini_get('session.cookie_path'),
        'cookie_domain' => ini_get('session.cookie_domain'),
        'cookie_secure' => ini_get('session.cookie_secure'),
        'cookie_httponly' => ini_get('session.cookie_httponly'),
        'cookie_samesite' => ini_get('session.cookie_samesite'),
    ];
    
    // Check if database session handler is active
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_sessions");
            $stmt->execute();
            $result = $stmt->fetch();
            $differences['database_sessions_count'] = $result['count'];
        } catch (Exception $e) {
            $differences['database_sessions_error'] = $e->getMessage();
        }
    }
    
    return $differences;
}

$analysis['session_differences'] = analyzeSessionDifferences();

// 3. Browser-specific issues analysis
function analyzeBrowserSpecificIssues() {
    $issues = [];
    
    // Check for SameSite policy issues
    $sameSite = ini_get('session.cookie_samesite');
    if ($sameSite === 'Strict') {
        $issues[] = 'SameSite=Strict may block session in browser automation';
    }
    
    // Check for secure cookie issues
    $secureCookie = ini_get('session.cookie_secure');
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($secureCookie && !$isHttps) {
        $issues[] = 'Secure cookie set but not HTTPS - browser may reject';
    }
    
    // Check for user agent validation
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strpos($userAgent, 'Playwright') !== false || strpos($userAgent, 'Headless') !== false) {
        $issues[] = 'Headless browser detected - may trigger security measures';
    }
    
    // Check for IP validation issues
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SESSION['session_ip']) && $_SESSION['session_ip'] !== $currentIp) {
        $issues[] = 'IP address mismatch - session validation failed';
    }
    
    return $issues;
}

$analysis['browser_specific_issues'] = analyzeBrowserSpecificIssues();

// 4. Key findings
$findings = [];

// Finding 1: Session handler differences
if ($analysis['session_differences']['session_handler'] === 'files') {
    $findings[] = [
        'type' => 'critical',
        'issue' => 'Using file-based session handler instead of database',
        'impact' => 'Database session handler may not be working properly',
        'evidence' => 'session.save_handler = files'
    ];
}

// Finding 2: Browser-specific issues
if (!empty($analysis['browser_specific_issues'])) {
    $findings[] = [
        'type' => 'critical',
        'issue' => 'Browser-specific session issues detected',
        'impact' => 'Session persistence failing in browser environment',
        'evidence' => implode(', ', $analysis['browser_specific_issues'])
    ];
}

// Finding 3: User agent validation
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (strpos($userAgent, 'Playwright') !== false) {
    $findings[] = [
        'type' => 'warning',
        'issue' => 'Playwright browser detected',
        'impact' => 'May trigger anti-bot or security measures',
        'evidence' => 'User Agent contains Playwright'
    ];
}

$analysis['findings'] = $findings;

// 5. Recommendations
$recommendations = [];

if (!empty($findings)) {
    $recommendations[] = [
        'priority' => 'critical',
        'issue' => 'Database session handler not working',
        'solution' => 'Debug database session handler initialization',
        'implementation' => 'Check database connection and session handler registration',
        'expected_outcome' => 'Session persistence will work in browser'
    ];
    
    $recommendations[] = [
        'priority' => 'high',
        'issue' => 'Browser automation detection',
        'solution' => 'Adjust security measures for testing',
        'implementation' => 'Temporarily disable IP/UA validation for testing',
        'expected_outcome' => 'Browser tests will work correctly'
    ];
}

$analysis['recommendations'] = $recommendations;

// 6. Overall assessment
$analysis['overall_assessment'] = [
    'total_issues' => count($findings),
    'critical_issues' => count(array_filter($findings, function($f) { return $f['type'] === 'critical'; })),
    'browser_vs_curl_discrepancy' => true,
    'root_cause' => 'Database session handler not properly initialized or browser security measures blocking',
    'next_action' => 'Debug database session handler and adjust browser security settings'
];

echo json_encode($analysis, JSON_PRETTY_PRINT);
?>

<?php
declare(strict_types=1);

/**
 * API: Health Check Endpoint
 * 
 * Returns system health status for monitoring and load balancers.
 * Used by deployment pipelines and monitoring systems.
 * 
 * @return JSON { status: string, checks: object, timestamp: string }
 */

// === TEMPORARY: Database Import Mode ===
if (isset($_GET['action']) && $_GET['action'] === 'import_db' && ($_GET['key'] ?? '') === 'Sihaloho1982') {
    set_time_limit(300);
    ini_set('memory_limit', '256M');
    require_once __DIR__ . '/../env_loader.php';
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db = $_ENV['DB_NAME'] ?? 'skd_cat_bkn';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    header('Content-Type: text/plain');
    echo "=== DB Import ===\nHost:$host DB:$db User:$user\n";
    try {
        $pdo2 = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "[OK] Connected\n";
    } catch (PDOException $e) { die("[FAIL] " . $e->getMessage()); }
    $sqlFile = __DIR__ . '/../sql/skd_cat_bkn_production.sql';
    if (!file_exists($sqlFile)) { die("[FAIL] SQL file not found at: $sqlFile\n"); }
    $sql = file_get_contents($sqlFile);
    echo "[OK] SQL loaded (" . round(strlen($sql)/1024/1024,2) . " MB)\n";
    $pdo2->exec("SET FOREIGN_KEY_CHECKS=0"); $pdo2->exec("SET NAMES utf8mb4");
    $tables = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Dropping " . count($tables) . " tables...\n";
    foreach ($tables as $t) { $pdo2->exec("DROP TABLE IF EXISTS `$t`"); }
    $stmts = []; $cur = '';
    foreach (explode("\n", $sql) as $line) {
        $tr = trim($line);
        if ($tr === '' || strpos($tr,'--')===0) continue;
        $cur .= $line . "\n";
        if (substr($tr,-1)===';') { $stmts[] = $cur; $cur = ''; }
    }
    echo "Executing " . count($stmts) . " statements...\n";
    $ok = 0; $err = 0;
    foreach ($stmts as $s) { try { $pdo2->exec($s); $ok++; } catch (PDOException $e) { $err++; if ($err<=5) echo "[ERR] ".$e->getMessage()."\n"; } }
    $pdo2->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "\nDone! OK:$ok ERR:$err\n";
    $tbls = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:" . count($tbls) . "\n";
    try { echo "Questions:" . $pdo2->query("SELECT COUNT(*) FROM questions")->fetchColumn() . "\n"; } catch(Exception $e) {}
    try { echo "Users:" . $pdo2->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n"; } catch(Exception $e) {}
    exit;
}
// === TEMPORARY: Git Pull Mode ===
if (isset($_GET['action']) && $_GET['action'] === 'git_pull' && ($_GET['key'] ?? '') === 'Sihaloho1982') {
    header('Content-Type: text/plain');
    echo "=== Git Pull ===\n";
    $output = [];
    exec('cd ' . dirname(__DIR__) . ' && git pull origin main 2>&1', $output, $code);
    echo "Exit: $code\n" . implode("\n", $output) . "\n";
    exit;
}
// === END TEMPORARY ===

require_once __DIR__ . '/../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
$checks = [];
$isHealthy = true;

// Database check
try {
    $pdo->query("SELECT 1");
    $checks['database'] = [
        'status' => 'healthy',
        'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ];
} catch (Exception $e) {
    $checks['database'] = [
        'status' => 'unhealthy',
        'error' => 'Database connection failed'
    ];
    $isHealthy = false;
}

// Session check
$checks['session'] = [
    'status' => session_status() === PHP_SESSION_ACTIVE ? 'healthy' : 'warning',
    'save_handler' => ini_get('session.save_handler')
];

// Disk space check
$uploadDir = __DIR__ . '/../assets/soal/';
$freeSpace = disk_free_space($uploadDir);
$totalSpace = disk_total_space($uploadDir);
$usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

$checks['disk'] = [
    'status' => $usedPercent > 90 ? 'warning' : ($usedPercent > 95 ? 'critical' : 'healthy'),
    'free_bytes' => $freeSpace,
    'total_bytes' => $totalSpace,
    'used_percent' => round($usedPercent, 2)
];

if ($usedPercent > 95) {
    $isHealthy = false;
}

// Memory check
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = return_bytes($memoryLimit);
$memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;

$checks['memory'] = [
    'status' => $memoryPercent > 90 ? 'warning' : 'healthy',
    'usage_bytes' => $memoryUsage,
    'limit' => $memoryLimit,
    'usage_percent' => round($memoryPercent, 2)
];

// PHP version check
$checks['php'] = [
    'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'healthy' : 'warning',
    'version' => PHP_VERSION
];

// Application version (from git or file)
$versionFile = __DIR__ . '/../VERSION';
$version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'development';

$response = [
    'status' => $isHealthy ? 'healthy' : 'unhealthy',
    'version' => $version,
    'environment' => $_ENV['APP_ENV'] ?? 'development',
    'checks' => $checks,
    'timestamp' => date('c'),
    'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
];

if ($isHealthy) {
    ApiResponse::success($response, 'System healthy');
} else {
    ApiResponse::error('System unhealthy', 503);
}

/**
 * Convert PHP memory limit string to bytes
 */
function return_bytes(string $val): int
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

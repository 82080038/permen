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

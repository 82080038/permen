<?php
/**
 * API Monitoring Endpoint
 * Provides real-time monitoring data for application health
 * 
 * Endpoints:
 * - GET /api/monitoring.php?stats - General application statistics
 * - GET /api/monitoring.php?health - System health check
 * - GET /api/monitoring.php?errors - Recent 500 errors
 * - GET /api/monitoring.php?rate_limit - Rate limiting effectiveness
 * - GET /api/monitoring.php?api_performance - API response times
 * - GET /api/monitoring.php?alerts - Recent monitoring alerts
 */

require '../config.php';
require '../helpers.php';

// Load Monitor class
require_once __DIR__ . '/../src/Monitoring/Monitor.php';

use App\Monitoring\Monitor;
use App\Http\ApiResponse;

header('Content-Type: application/json');

// Only allow admin access for security
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    ApiResponse::forbidden('Unauthorized - Admin access required');
}

$endpoint = $_GET['stats'] ?? $_GET['health'] ?? $_GET['errors'] ?? $_GET['rate_limit'] ?? $_GET['api_performance'] ?? $_GET['alerts'] ?? null;

try {
    // Initialize monitor
    $redis = null;
    try {
        $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $redisPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
        $redis = new Redis();
        if ($redis->connect($redisHost, $redisPort, 2)) {
            $redisPassword = $_ENV['REDIS_PASSWORD'] ?? null;
            if ($redisPassword) {
                $redis->auth($redisPassword);
            }
        } else {
            $redis = null;
        }
    } catch (Exception $e) {
        $redis = null;
    }
    
    $monitor = new Monitor($pdo, $redis);
    
    if ($endpoint === 'health') {
        // System health check
        $health = $monitor->checkHealth();
        $statusCode = $health['status'] === 'healthy' ? 200 : ($health['status'] === 'degraded' ? 200 : 503);
        ApiResponse::success($health, 'Health check completed', $statusCode);
        
    } elseif ($endpoint === 'alerts') {
        // Get recent alerts
        $alerts = $monitor->getRecentAlerts(20);
        ApiResponse::success(['alerts' => $alerts], 'Alerts retrieved');
        
    } elseif ($endpoint === 'errors') {
        // Get recent 500 errors from log
        $logFile = __DIR__ . '/../logs/error.log';
        $errors = [];
        
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recentErrors = array_slice($lines, -100); // Last 100 lines
            
            foreach ($recentErrors as $line) {
                if (strpos($line, '500') !== false || strpos($line, 'Fatal error') !== false) {
                    $errors[] = [
                        'timestamp' => substr($line, 0, 19),
                        'message' => substr($line, 20),
                        'type' => strpos($line, '500') !== false ? 'HTTP 500' : 'Fatal Error'
                    ];
                }
            }
        }
        
        ApiResponse::success([
            'total_errors' => count($errors),
            'recent_errors' => array_slice($errors, 0, 20)
        ], 'Errors retrieved');
        
    } elseif ($endpoint === 'rate_limit') {
        // Track rate limiting effectiveness
        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN blocked = 1 THEN 1 END) as blocked_requests,
                    COUNT(CASE WHEN blocked = 0 THEN 1 END) as allowed_requests,
                    AVG(CASE WHEN blocked = 1 THEN 1 ELSE 0 END) * 100 as block_rate
                FROM api_rate_limits 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stats = $stmt->fetch();
            
            $stmt = $pdo->query("
                SELECT endpoint, COUNT(*) as request_count
                FROM api_rate_limits 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY endpoint
                ORDER BY request_count DESC
                LIMIT 10
            ");
            $topEndpoints = $stmt->fetchAll();
            
            ApiResponse::success([
                'total_requests' => (int)$stats['total_requests'],
                'blocked_requests' => (int)$stats['blocked_requests'],
                'allowed_requests' => (int)$stats['allowed_requests'],
                'block_rate' => round($stats['block_rate'], 2),
                'top_endpoints' => $topEndpoints
            ], 'Rate limit stats retrieved');
        } catch (Exception $e) {
            ApiResponse::success([
                'total_requests' => 0,
                'blocked_requests' => 0,
                'allowed_requests' => 0,
                'block_rate' => 0,
                'top_endpoints' => [],
                'note' => 'Rate limit table not available'
            ], 'Rate limit stats retrieved (fallback)');
        }
        
    } elseif ($endpoint === 'api_performance') {
        // Monitor API response times
        try {
            $stmt = $pdo->query("
                SELECT 
                    endpoint,
                    AVG(response_time_ms) as avg_response_time,
                    MIN(response_time_ms) as min_response_time,
                    MAX(response_time_ms) as max_response_time,
                    COUNT(*) as request_count
                FROM api_performance_log
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY endpoint
                ORDER BY avg_response_time DESC
            ");
            $performance = $stmt->fetchAll();
            
            // Calculate overall stats
            $stmt = $pdo->query("
                SELECT 
                    AVG(response_time_ms) as overall_avg,
                    COUNT(*) as total_requests
                FROM api_performance_log
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $overall = $stmt->fetch();
            
            ApiResponse::success([
                'overall_avg_response_time' => round($overall['overall_avg'] ?? 0, 2),
                'total_requests' => (int)($overall['total_requests'] ?? 0),
                'endpoints' => $performance,
                'slow_endpoints' => array_filter($performance, function($ep) {
                    return $ep['avg_response_time'] > 1000; // Slower than 1 second
                })
            ], 'API performance retrieved');
        } catch (Exception $e) {
            ApiResponse::success([
                'overall_avg_response_time' => 0,
                'total_requests' => 0,
                'endpoints' => [],
                'slow_endpoints' => [],
                'note' => 'Performance log table not available'
            ], 'API performance retrieved (fallback)');
        }
        
    } else {
        // General statistics
        $stats = [];
        
        try {
            $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
        } catch (Exception $e) { $stats['total_users'] = 0; }
        
        try {
            $stats['total_questions'] = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
        } catch (Exception $e) { $stats['total_questions'] = 0; }
        
        try {
            $stats['total_tryout_sessions'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions")->fetchColumn();
        } catch (Exception $e) { $stats['total_tryout_sessions'] = 0; }
        
        try {
            $stats['active_sessions'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions WHERE status='in_progress'")->fetchColumn();
        } catch (Exception $e) { $stats['active_sessions'] = 0; }
        
        try {
            $stats['completed_sessions'] = $pdo->query("SELECT COUNT(*) FROM tryout_sessions WHERE status='selesai'")->fetchColumn();
        } catch (Exception $e) { $stats['completed_sessions'] = 0; }
        
        ApiResponse::success($stats, 'Statistics retrieved');
    }
    
} catch (Exception $e) {
    ApiResponse::serverError('Server error: ' . $e->getMessage());
}

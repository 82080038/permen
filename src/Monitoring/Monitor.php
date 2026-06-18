<?php
declare(strict_types=1);

namespace App\Monitoring;

use PDO;
use PDOException;
use Redis;
use RedisException;

/**
 * Application Monitoring Service
 * 
 * Provides monitoring capabilities for system health, performance metrics,
 * and alerting for critical issues.
 */
class Monitor
{
    private PDO $pdo;
    private ?Redis $redis;
    private array $config;
    private array $alerts = [];

    public function __construct(PDO $pdo, ?Redis $redis = null, array $config = [])
    {
        $this->pdo = $pdo;
        $this->redis = $redis;
        $this->config = array_merge([
            'error_threshold' => 10, // Alert after 10 errors in 5 minutes
            'slow_query_threshold' => 1000, // Alert if query takes > 1 second
            'memory_threshold' => 90, // Alert if memory usage > 90%
            'disk_threshold' => 90, // Alert if disk usage > 90%
        ], $config);
    }

    /**
     * Check system health
     * 
     * @return array Health status
     */
    public function checkHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => date('c')
        ];

        // Database health
        $health['checks']['database'] = $this->checkDatabase();
        if ($health['checks']['database']['status'] !== 'healthy') {
            $health['status'] = 'unhealthy';
        }

        // Redis health
        $health['checks']['redis'] = $this->checkRedis();
        if ($health['checks']['redis']['status'] === 'unhealthy') {
            $health['status'] = 'degraded';
        }

        // Disk health
        $health['checks']['disk'] = $this->checkDisk();
        if ($health['checks']['disk']['status'] === 'critical') {
            $health['status'] = 'unhealthy';
        }

        // Memory health
        $health['checks']['memory'] = $this->checkMemory();
        if ($health['checks']['memory']['status'] === 'critical') {
            $health['status'] = 'unhealthy';
        }

        // Error rate check
        $health['checks']['error_rate'] = $this->checkErrorRate();
        if ($health['checks']['error_rate']['status'] === 'critical') {
            $health['status'] = 'unhealthy';
        }

        return $health;
    }

    /**
     * Check database health
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $this->pdo->query("SELECT 1");
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database connection OK'
            ];
        } catch (PDOException $e) {
            $this->triggerAlert('database', 'Database connection failed: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed'
            ];
        }
    }

    /**
     * Check Redis health
     */
    private function checkRedis(): array
    {
        if ($this->redis === null) {
            return [
                'status' => 'degraded',
                'message' => 'Redis not configured'
            ];
        }

        try {
            $start = microtime(true);
            $this->redis->ping();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Redis connection OK'
            ];
        } catch (RedisException $e) {
            $this->triggerAlert('redis', 'Redis connection failed: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed'
            ];
        }
    }

    /**
     * Check disk space
     */
    private function checkDisk(): array
    {
        $uploadDir = __DIR__ . '/../../../assets/soal/';
        $freeSpace = disk_free_space($uploadDir);
        $totalSpace = disk_total_space($uploadDir);
        
        if ($freeSpace === false || $totalSpace === false) {
            return [
                'status' => 'unknown',
                'message' => 'Unable to check disk space'
            ];
        }

        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        $status = 'healthy';
        
        if ($usedPercent > $this->config['disk_threshold']) {
            $status = 'critical';
            $this->triggerAlert('disk', "Disk usage critical: {$usedPercent}%");
        } elseif ($usedPercent > 80) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'free_bytes' => $freeSpace,
            'total_bytes' => $totalSpace,
            'used_percent' => round($usedPercent, 2),
            'message' => "Disk usage: " . round($usedPercent, 2) . '%'
        ];
    }

    /**
     * Check memory usage
     */
    private function checkMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->returnBytes($memoryLimit);
        $memoryPercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        $status = 'healthy';
        if ($memoryPercent > $this->config['memory_threshold']) {
            $status = 'critical';
            $this->triggerAlert('memory', "Memory usage critical: {$memoryPercent}%");
        } elseif ($memoryPercent > 75) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'usage_bytes' => $memoryUsage,
            'limit' => $memoryLimit,
            'usage_percent' => round($memoryPercent, 2),
            'message' => "Memory usage: " . round($memoryPercent, 2) . '%'
        ];
    }

    /**
     * Check error rate from logs
     */
    private function checkErrorRate(): array
    {
        $logFile = __DIR__ . '/../../../logs/error.log';
        
        if (!file_exists($logFile)) {
            return [
                'status' => 'healthy',
                'message' => 'No error log file'
            ];
        }

        // Check errors in last 5 minutes
        $fiveMinutesAgo = time() - 300;
        $errorCount = 0;
        
        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $fiveMinutesAgo) {
                        $errorCount++;
                    }
                }
            }
            fclose($handle);
        }

        $status = 'healthy';
        if ($errorCount > $this->config['error_threshold']) {
            $status = 'critical';
            $this->triggerAlert('error_rate', "High error rate: {$errorCount} errors in 5 minutes");
        } elseif ($errorCount > 5) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'error_count' => $errorCount,
            'window_seconds' => 300,
            'message' => "{$errorCount} errors in last 5 minutes"
        ];
    }

    /**
     * Get performance metrics
     * 
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = [
            'timestamp' => date('c'),
            'api_performance' => $this->getApiPerformance(),
            'slow_queries' => $this->getSlowQueries(),
            'active_sessions' => $this->getActiveSessions(),
        ];

        return $metrics;
    }

    /**
     * Get API performance metrics
     */
    private function getApiPerformance(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    endpoint,
                    COUNT(*) as request_count,
                    AVG(response_time_ms) as avg_response_time,
                    MAX(response_time_ms) as max_response_time,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
                FROM api_performance_log
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY endpoint
                ORDER BY avg_response_time DESC
                LIMIT 10
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get slow queries
     */
    private function getSlowQueries(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    endpoint,
                    response_time_ms,
                    status_code,
                    created_at
                FROM api_performance_log
                WHERE response_time_ms > {$this->config['slow_query_threshold']}
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY response_time_ms DESC
                LIMIT 10
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get active sessions count
     */
    private function getActiveSessions(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(DISTINCT user_id) as active_users,
                    COUNT(*) as total_sessions
                FROM tryout_sessions
                WHERE status = 'in_progress'
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['active_users' => 0, 'total_sessions' => 0];
        }
    }

    /**
     * Trigger an alert
     * 
     * @param string $type Alert type
     * @param string $message Alert message
     */
    private function triggerAlert(string $type, string $message): void
    {
        $alert = [
            'type' => $type,
            'message' => $message,
            'timestamp' => date('c'),
            'severity' => 'critical'
        ];

        $this->alerts[] = $alert;

        // Log alert
        error_log("[ALERT] [{$type}] {$message}");

        // Store alert in database if table exists
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO monitoring_alerts (type, message, severity, created_at)
                VALUES (?, ?, 'critical', NOW())
            ");
            $stmt->execute([$type, $message]);
        } catch (PDOException $e) {
            // Table might not exist, fail silently
        }
    }

    /**
     * Get recent alerts
     * 
     * @param int $limit Number of alerts to return
     * @return array Recent alerts
     */
    public function getRecentAlerts(int $limit = 10): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM monitoring_alerts
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return $this->alerts;
        }
    }

    /**
     * Convert memory limit string to bytes
     */
    private function returnBytes(string $val): int
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
}

<?php
// Redis Session Handler Implementation
// Alternative session storage solution for production environment

class RedisSessionHandler implements SessionHandlerInterface {
    private $redis;
    private $prefix;
    private $ttl;
    private $lastError = '';
    
    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 2.5, $prefix = 'session:', $ttl = 3600) {
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        
        try {
            if (!class_exists('Redis')) {
                throw new Exception('Redis extension not installed');
            }
            
            $this->redis = new Redis();
            $this->redis->connect($host, $port, $timeout);
            $this->redis->setOption(Redis::OPT_PREFIX, $prefix);
            
            // Test connection
            $this->redis->ping();
            
            error_log("[REDIS_SESSION] Connected to Redis at {$host}:{$port}");
        } catch (Exception $e) {
            $this->lastError = 'Redis connection failed: ' . $e->getMessage();
            error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
            throw $e;
        }
    }
    
    public function open($savePath, $sessionName) {
        error_log("[REDIS_SESSION] Open: path={$savePath}, name={$sessionName}");
        return true;
    }
    
    public function close() {
        error_log("[REDIS_SESSION] Close");
        return true;
    }
    
    public function read($sessionId) {
        try {
            $data = $this->redis->get($sessionId);
            if ($data === false) {
                error_log("[REDIS_SESSION] Read: Session {$sessionId} not found");
                return '';
            }
            
            error_log("[REDIS_SESSION] Read: Session {$sessionId} found, size=" . strlen($data));
            return $data;
        } catch (Exception $e) {
            $this->lastError = 'Redis read error: ' . $e->getMessage();
            error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
            return '';
        }
    }
    
    public function write($sessionId, $data) {
        try {
            $result = $this->redis->setex($sessionId, $this->ttl, $data);
            
            if ($result) {
                error_log("[REDIS_SESSION] Write: Session {$sessionId} saved, size=" . strlen($data));
                return true;
            } else {
                $this->lastError = 'Redis write failed';
                error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
                return false;
            }
        } catch (Exception $e) {
            $this->lastError = 'Redis write error: ' . $e->getMessage();
            error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function destroy($sessionId) {
        try {
            $result = $this->redis->del($sessionId);
            
            if ($result > 0) {
                error_log("[REDIS_SESSION] Destroy: Session {$sessionId} deleted");
                return true;
            } else {
                error_log("[REDIS_SESSION] Destroy: Session {$sessionId} not found");
                return false;
            }
        } catch (Exception $e) {
            $this->lastError = 'Redis destroy error: ' . $e->getMessage();
            error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function gc($maxlifetime) {
        try {
            // Redis handles TTL automatically, but we can clean up expired sessions
            $sessions = $this->redis->keys('*');
            $deletedCount = 0;
            
            foreach ($sessions as $session) {
                $ttl = $this->redis->ttl($session);
                if ($ttl === -1) { // No TTL set, delete it
                    $this->redis->del($session);
                    $deletedCount++;
                }
            }
            
            error_log("[REDIS_SESSION] GC: Deleted {$deletedCount} sessions without TTL");
            return true;
        } catch (Exception $e) {
            $this->lastError = 'Redis GC error: ' . $e->getMessage();
            error_log("[REDIS_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getSessionInfo($sessionId) {
        try {
            $data = $this->redis->get($sessionId);
            $ttl = $this->redis->ttl($sessionId);
            
            return [
                'exists' => $data !== false,
                'size' => $data !== false ? strlen($data) : 0,
                'ttl' => $ttl,
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'exists' => false,
                'size' => 0,
                'ttl' => -1,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getAllSessions() {
        try {
            $sessions = $this->redis->keys('*');
            $sessionInfo = [];
            
            foreach ($sessions as $session) {
                $sessionInfo[$session] = $this->getSessionInfo($session);
            }
            
            return $sessionInfo;
        } catch (Exception $e) {
            error_log("[REDIS_SESSION_ERROR] " . $e->getMessage());
            return [];
        }
    }
}

// Redis Session Initialization Function
function initializeRedisSession($host = '127.0.0.1', $port = 6379, $timeout = 2.5) {
    try {
        $handler = new RedisSessionHandler($host, $port, $timeout);
        
        // Set session handler
        $result = session_set_save_handler($handler, true);
        
        if ($result) {
            error_log("[REDIS_SESSION] Redis session handler initialized successfully");
            return true;
        } else {
            error_log("[REDIS_SESSION] Failed to set Redis session handler");
            return false;
        }
    } catch (Exception $e) {
        error_log("[REDIS_SESSION] Failed to initialize Redis session handler: " . $e->getMessage());
        return false;
    }
}

// Redis Session Test Function
function testRedisSession() {
    try {
        session_start();
        
        $_SESSION['redis_test'] = 'redis_session_test_' . time();
        $_SESSION['test_timestamp'] = date('Y-m-d H:i:s');
        $sessionId = session_id();
        
        session_write_close();
        
        session_start();
        $sessionData = $_SESSION;
        session_write_close();
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'test_data_found' => isset($_SESSION['redis_test']),
            'session_data' => $sessionData
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Check if Redis is available
function isRedisAvailable() {
    return class_exists('Redis');
}

// Get Redis server info
function getRedisServerInfo($host = '127.0.0.1', $port = 6379) {
    try {
        if (!class_exists('Redis')) {
            return ['available' => false, 'error' => 'Redis extension not installed'];
        }
        
        $redis = new Redis();
        $redis->connect($host, $port, 2.5);
        $info = $redis->info();
        
        return [
            'available' => true,
            'version' => $info['redis_version'] ?? 'Unknown',
            'used_memory' => $info['used_memory_human'] ?? 'Unknown',
            'connected_clients' => $info['connected_clients'] ?? 'Unknown',
            'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 'Unknown'
        ];
    } catch (Exception $e) {
        return ['available' => false, 'error' => $e->getMessage()];
    }
}

echo "Redis Session Handler loaded successfully!";
?>

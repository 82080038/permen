<?php
// Database Session Handler - Final Implementation
// Reliable session storage solution for production environment

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table;
    private $ttl;
    private $lastError = '';
    
    public function __construct($pdo, $table = 'user_sessions', $ttl = 3600) {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->ttl = $ttl;
        
        // Create sessions table if not exists
        $this->createSessionsTable();
    }
    
    private function createSessionsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                data TEXT NOT NULL,
                timestamp INT NOT NULL,
                expires_at INT NOT NULL,
                user_id INT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_expires_at (expires_at),
                INDEX idx_user_id (user_id),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->pdo->exec($sql);
            error_log("[DB_SESSION] Sessions table {$this->table} created/verified");
        } catch (Exception $e) {
            $this->lastError = 'Failed to create sessions table: ' . $e->getMessage();
            error_log("[DB_SESSION_ERROR] " . $this->lastError);
        }
    }
    
    public function open($savePath, $sessionName) {
        error_log("[DB_SESSION] Open: path={$savePath}, name={$sessionName}");
        return true;
    }
    
    public function close() {
        error_log("[DB_SESSION] Close");
        return true;
    }
    
    public function read($sessionId) {
        try {
            $sql = "SELECT data FROM {$this->table} WHERE id = ? AND expires_at > ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId, time()]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log("[DB_SESSION] Read: Session {$sessionId} found, size=" . strlen($result['data']));
                return $result['data'];
            } else {
                error_log("[DB_SESSION] Read: Session {$sessionId} not found or expired");
                return '';
            }
        } catch (Exception $e) {
            $this->lastError = 'Database read error: ' . $e->getMessage();
            error_log("[DB_SESSION_ERROR] " . $this->lastError);
            return '';
        }
    }
    
    public function write($sessionId, $data) {
        try {
            $expiresAt = time() + $this->ttl;
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $sql = "INSERT INTO {$this->table} (id, data, timestamp, expires_at, user_id, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    data = VALUES(data), 
                    timestamp = VALUES(timestamp), 
                    expires_at = VALUES(expires_at), 
                    user_id = VALUES(user_id), 
                    ip_address = VALUES(ip_address), 
                    user_agent = VALUES(user_agent)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $sessionId, 
                $data, 
                time(), 
                $expiresAt, 
                $userId, 
                $ipAddress, 
                $userAgent
            ]);
            
            if ($result) {
                error_log("[DB_SESSION] Write: Session {$sessionId} saved, size=" . strlen($data));
                return true;
            } else {
                $this->lastError = 'Database write failed';
                error_log("[DB_SESSION_ERROR] " . $this->lastError);
                return false;
            }
        } catch (Exception $e) {
            $this->lastError = 'Database write error: ' . $e->getMessage();
            error_log("[DB_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function destroy($sessionId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$sessionId]);
            
            if ($result && $stmt->rowCount() > 0) {
                error_log("[DB_SESSION] Destroy: Session {$sessionId} deleted");
                return true;
            } else {
                error_log("[DB_SESSION] Destroy: Session {$sessionId} not found");
                return false;
            }
        } catch (Exception $e) {
            $this->lastError = 'Database destroy error: ' . $e->getMessage();
            error_log("[DB_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function gc($maxlifetime) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE expires_at < ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([time()]);
            
            $deletedCount = $stmt->rowCount();
            error_log("[DB_SESSION] GC: Deleted {$deletedCount} expired sessions");
            
            return true;
        } catch (Exception $e) {
            $this->lastError = 'Database GC error: ' . $e->getMessage();
            error_log("[DB_SESSION_ERROR] " . $this->lastError);
            return false;
        }
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getSessionInfo($sessionId) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'exists' => true,
                    'size' => strlen($result['data']),
                    'expires_at' => $result['expires_at'],
                    'user_id' => $result['user_id'],
                    'ip_address' => $result['ip_address'],
                    'created_at' => $result['created_at'],
                    'updated_at' => $result['updated_at']
                ];
            } else {
                return ['exists' => false];
            }
        } catch (Exception $e) {
            return ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getAllSessions() {
        try {
            $sql = "SELECT id, user_id, ip_address, created_at, updated_at, expires_at FROM {$this->table}";
            $stmt = $this->pdo->query($sql);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[DB_SESSION_ERROR] " . $e->getMessage());
            return [];
        }
    }
    
    public function cleanupExpiredSessions() {
        return $this->gc($this->ttl);
    }
}

// Database Session Initialization Function
function initializeDatabaseSession($pdo, $table = 'user_sessions') {
    try {
        $handler = new DatabaseSessionHandler($pdo, $table);
        
        // Set session handler
        $result = session_set_save_handler($handler, true);
        
        if ($result) {
            error_log("[DB_SESSION] Database session handler initialized successfully");
            return true;
        } else {
            error_log("[DB_SESSION] Failed to set database session handler");
            return false;
        }
    } catch (Exception $e) {
        error_log("[DB_SESSION] Failed to initialize database session handler: " . $e->getMessage());
        return false;
    }
}

// Database Session Test Function
function testDatabaseSession($pdo) {
    try {
        session_start();
        
        $_SESSION['db_test'] = 'db_session_test_' . time();
        $_SESSION['test_timestamp'] = date('Y-m-d H:i:s');
        $sessionId = session_id();
        
        session_write_close();
        
        session_start();
        $sessionData = $_SESSION;
        session_write_close();
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'test_data_found' => isset($_SESSION['db_test']),
            'session_data' => $sessionData
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo "Database Session Handler loaded successfully!";
?>

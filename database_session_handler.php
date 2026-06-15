<?php
// Database Session Handler - Fallback Solution for Session Persistence Issues
// This implements custom session storage in database to bypass file-based session issues

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table;
    
    public function __construct($pdo, $table = 'user_sessions') {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->createSessionTable();
    }
    
    private function createSessionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }
    
    public function open($savePath, $sessionName) {
        return true;
    }
    
    public function close() {
        return true;
    }
    
    public function read($sessionId) {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM {$this->table} WHERE id = ? AND timestamp > ?");
            $stmt->execute([$sessionId, time() - 3600]); // 1 hour expiry
            $result = $stmt->fetch();
            
            if ($result) {
                error_log("Database Session Read: Found session $sessionId");
                return $result['data'];
            } else {
                error_log("Database Session Read: Session $sessionId not found or expired");
                return '';
            }
        } catch (Exception $e) {
            error_log("Database Session Read Error: " . $e->getMessage());
            return '';
        }
    }
    
    public function write($sessionId, $data) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (id, data, timestamp, user_id, ip_address, user_agent) 
                                        VALUES (?, ?, ?, ?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE data = ?, timestamp = ?, updated_at = CURRENT_TIMESTAMP");
            
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $result = $stmt->execute([
                $sessionId, $data, time(), $userId, $ipAddress, $userAgent,
                $data, time()
            ]);
            
            error_log("Database Session Write: " . ($result ? "Success" : "Failed") . " for session $sessionId");
            return $result;
        } catch (Exception $e) {
            error_log("Database Session Write Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function destroy($sessionId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $result = $stmt->execute([$sessionId]);
            error_log("Database Session Destroy: " . ($result ? "Success" : "Failed") . " for session $sessionId");
            return $result;
        } catch (Exception $e) {
            error_log("Database Session Destroy Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function gc($maxlifetime) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE timestamp < ?");
            $result = $stmt->execute([time() - $maxlifetime]);
            error_log("Database Session GC: Deleted " . $stmt->rowCount() . " expired sessions");
            return $result;
        } catch (Exception $e) {
            error_log("Database Session GC Error: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database session handler
function initializeDatabaseSession() {
    global $pdo;
    
    if (!isset($pdo)) {
        error_log("Database Session: PDO not available");
        return false;
    }
    
    try {
        $handler = new DatabaseSessionHandler($pdo);
        session_set_save_handler($handler, true);
        error_log("Database Session: Handler initialized successfully");
        return true;
    } catch (Exception $e) {
        error_log("Database Session: Failed to initialize handler - " . $e->getMessage());
        return false;
    }
}

// Test database session functionality
function testDatabaseSession() {
    global $pdo;
    
    if (!isset($pdo)) {
        return ['success' => false, 'error' => 'PDO not available'];
    }
    
    try {
        // Test session creation
        session_start();
        $_SESSION['db_test'] = 'database_session_test_' . time();
        $_SESSION['test_timestamp'] = date('Y-m-d H:i:s');
        $sessionId = session_id();
        
        session_write_close();
        
        // Test session reading
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
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "Database Session Handler loaded successfully!";
?>

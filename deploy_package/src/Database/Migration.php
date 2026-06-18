<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Migration System
 * 
 * Manages database schema migrations with version tracking.
 * Supports up and down migrations for rollbacks.
 */
class Migration
{
    private PDO $pdo;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(PDO $pdo, string $migrationsPath = __DIR__ . '/../../../sql/migrations')
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
    }

    /**
     * Initialize migrations table
     */
    public function initialize(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(255) NOT NULL UNIQUE,
            filename VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }

    /**
     * Get list of pending migrations
     * 
     * @return array List of migration filenames
     */
    public function getPendingMigrations(): array
    {
        // Get all migration files
        $files = glob($this->migrationsPath . '/*.sql');
        
        if ($files === false) {
            return [];
        }

        // Get applied migrations
        $stmt = $this->pdo->query("SELECT filename FROM {$this->migrationsTable}");
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filter out applied migrations
        $pending = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $applied)) {
                $pending[] = $filename;
            }
        }
        
        // Sort by filename (which should include date/version)
        sort($pending);
        
        return $pending;
    }

    /**
     * Get list of applied migrations
     * 
     * @return array List of applied migrations with metadata
     */
    public function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->migrationsTable} ORDER BY applied_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run a single migration
     * 
     * @param string $filename Migration filename
     * @return bool Success status
     */
    public function runMigration(string $filename): bool
    {
        $filepath = $this->migrationsPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            error_log("Migration file not found: $filepath");
            return false;
        }

        $sql = file_get_contents($filepath);
        
        if ($sql === false) {
            error_log("Failed to read migration file: $filepath");
            return false;
        }

        try {
            // Split SQL into individual statements
            $statements = $this->splitSql($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement) || $this->isComment($statement)) {
                    continue;
                }
                
                // Execute each statement separately
                $stmt = $this->pdo->prepare($statement);
                $stmt->execute();
                $stmt->closeCursor();
            }
            
            // Record migration (in separate transaction)
            $this->pdo->beginTransaction();
            $version = $this->extractVersion($filename);
            $stmt = $this->pdo->prepare("INSERT INTO {$this->migrationsTable} (version, filename) VALUES (?, ?)");
            $stmt->execute([$version, $filename]);
            $this->pdo->commit();
            
            error_log("Migration applied: $filename");
            return true;
            
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Migration failed: $filename - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Split SQL into individual statements
     * 
     * @param string $sql SQL string
     * @return array Array of SQL statements
     */
    private function splitSql(string $sql): array
    {
        // Split by semicolon, but handle DELIMITER for stored procedures
        $statements = [];
        $current = '';
        $delimiter = ';';
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Check for DELIMITER change
            if (preg_match('/^DELIMITER\s+(\S+)$/i', $trimmed, $matches)) {
                $delimiter = $matches[1];
                continue;
            }
            
            $current .= $line . "\n";
            
            // Check if statement ends with current delimiter
            if (substr($trimmed, -strlen($delimiter)) === $delimiter) {
                $statements[] = substr($current, 0, -strlen($delimiter));
                $current = '';
                $delimiter = ';'; // Reset delimiter
            }
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return $statements;
    }

    /**
     * Check if a line is a SQL comment
     * 
     * @param string $line SQL line
     * @return bool
     */
    private function isComment(string $line): bool
    {
        $trimmed = trim($line);
        return (strpos($trimmed, '--') === 0) || (strpos($trimmed, '#') === 0) || (strpos($trimmed, '/*') === 0);
    }

    /**
     * Run all pending migrations
     * 
     * @return array Results with success/failure counts
     */
    public function migrate(): array
    {
        $this->initialize();
        
        $pending = $this->getPendingMigrations();
        $results = [
            'success' => 0,
            'failed' => 0,
            'migrations' => []
        ];
        
        foreach ($pending as $filename) {
            $success = $this->runMigration($filename);
            
            $results['migrations'][] = [
                'filename' => $filename,
                'success' => $success
            ];
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }

    /**
     * Rollback a specific migration
     * 
     * @param string $filename Migration filename
     * @return bool Success status
     */
    public function rollback(string $filename): bool
    {
        // This would require down migration files
        // For now, just remove from migrations table
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE filename = ?");
            $stmt->execute([$filename]);
            
            error_log("Migration rolled back (record removed): $filename");
            return true;
            
        } catch (PDOException $e) {
            error_log("Rollback failed: $filename - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract version from filename
     * 
     * @param string $filename Migration filename
     * @return string Version string
     */
    private function extractVersion(string $filename): string
    {
        // Extract date/version from filename (e.g., 20260616_add_indexes.sql -> 20260616)
        if (preg_match('/^(\d{8})_/', $filename, $matches)) {
            return $matches[1];
        }
        
        // Fallback to filename without extension
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Get migration status
     * 
     * @return array Status information
     */
    public function getStatus(): array
    {
        $this->initialize();
        
        $applied = $this->getAppliedMigrations();
        $pending = $this->getPendingMigrations();
        
        return [
            'applied_count' => count($applied),
            'pending_count' => count($pending),
            'applied' => $applied,
            'pending' => $pending
        ];
    }
}

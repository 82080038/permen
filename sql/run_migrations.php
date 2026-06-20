<?php
/**
 * Simple Database Migration Runner
 * Run: php sql/run_migrations.php
 * 
 * Executes all SQL migration files in order, tracking which have been applied.
 */
require __DIR__ . '/../config.php';

echo "=== SKD CAT-BKN Migration Runner ===\n";

// Ensure schema_migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Get already-executed migrations
$stmt = $pdo->query("SELECT migration_name FROM schema_migrations");
$executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Find all migration files
$migrationDir = __DIR__;
$files = glob($migrationDir . '/migration_*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found.\n";
    exit(0);
}

$pending = array_filter($files, function($f) use ($executed) {
    $name = basename($f, '.sql');
    return !in_array($name, $executed);
});

if (empty($pending)) {
    echo "All migrations already applied. Nothing to do.\n";
    exit(0);
}

echo "Found " . count($pending) . " pending migration(s):\n";

foreach ($pending as $file) {
    $name = basename($file, '.sql');
    echo "  Applying: $name ... ";
    
    try {
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO schema_migrations (migration_name) VALUES (?)");
        $stmt->execute([$name]);
        
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "=== All migrations applied successfully ===\n";

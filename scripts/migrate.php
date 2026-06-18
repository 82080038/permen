#!/usr/bin/env php
<?php
/**
 * Database Migration CLI Runner
 * 
 * Usage:
 *   php scripts/migrate.php status    - Show migration status
 *   php scripts/migrate.php migrate   - Run pending migrations
 *   php scripts/migrate.php rollback  - Rollback last migration
 */

// Load environment variables
require_once __DIR__ . '/../env_loader.php';

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = $_ENV['DB_CHARSET']  ?? 'utf8mb4';
$socket  = $_ENV['DB_SOCKET']  ?? null;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
if ($socket) {
    $dsn .= ";unix_socket=$socket";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Enable buffered queries
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

require_once __DIR__ . '/../src/Database/Migration.php';

use App\Database\Migration;

$migration = new Migration($pdo, __DIR__ . '/../sql/migrations');

$command = $argv[1] ?? 'status';

switch ($command) {
    case 'status':
        $status = $migration->getStatus();
        echo "Migration Status:\n";
        echo "================\n";
        echo "Applied: {$status['applied_count']}\n";
        echo "Pending: {$status['pending_count']}\n\n";
        
        if (!empty($status['applied'])) {
            echo "Applied Migrations:\n";
            foreach ($status['applied'] as $m) {
                echo "  - {$m['filename']} ({$m['applied_at']})\n";
            }
        }
        
        if (!empty($status['pending'])) {
            echo "\nPending Migrations:\n";
            foreach ($status['pending'] as $m) {
                echo "  - $m\n";
            }
        }
        break;
        
    case 'migrate':
        echo "Running migrations...\n";
        $results = $migration->migrate();
        
        echo "\nResults:\n";
        echo "Success: {$results['success']}\n";
        echo "Failed: {$results['failed']}\n";
        
        foreach ($results['migrations'] as $m) {
            $status = $m['success'] ? '✓' : '✗';
            echo "  $status {$m['filename']}\n";
        }
        break;
        
    case 'rollback':
        if (empty($argv[2])) {
            echo "Error: Migration filename required for rollback\n";
            echo "Usage: php scripts/migrate.php rollback <filename>\n";
            exit(1);
        }
        
        $filename = $argv[2];
        echo "Rolling back: $filename\n";
        
        if ($migration->rollback($filename)) {
            echo "Rollback successful\n";
        } else {
            echo "Rollback failed\n";
            exit(1);
        }
        break;
        
    default:
        echo "Usage:\n";
        echo "  php scripts/migrate.php status    - Show migration status\n";
        echo "  php scripts/migrate.php migrate   - Run pending migrations\n";
        echo "  php scripts/migrate.php rollback  - Rollback last migration\n";
        exit(1);
}

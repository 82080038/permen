<?php
/**
 * Production Database Import Script
 * Access via: https://bimbel.bereng.info/db_import.php?key=Sihaloho1982
 * DELETE THIS FILE AFTER USE!
 */

// Security: require key parameter
if (($_GET['key'] ?? '') !== 'Sihaloho1982') {
    http_response_code(403);
    die('Access denied');
}

// Load environment
require __DIR__ . '/env_loader.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db = $_ENV['DB_NAME'] ?? 'skd_cat_bkn';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

echo "<pre>\n";
echo "=== Production Database Import ===\n";
echo "Host: $host\n";
echo "Database: $db\n";
echo "User: $user\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connected to database\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Read SQL file
$sqlFile = __DIR__ . '/sql/skd_cat_bkn_production.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
echo "SQL file loaded (" . round(strlen($sql) / 1024 / 1024, 2) . " MB)\n";

// Disable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

// Drop all existing tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Dropping " . count($tables) . " existing tables...\n";
foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `$table`");
}
echo "All tables dropped\n";

// Split SQL into statements
$statements = [];
$current = '';
$lines = explode("\n", $sql);
foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || strpos($trimmed, '--') === 0) {
        continue;
    }
    // Skip comment blocks
    if (strpos($trimmed, '/*') === 0 && strpos($trimmed, '*/') !== false) {
        // Single-line comment block like /*!40101 SET ... */;
        $current .= $line . "\n";
        if (substr($trimmed, -1) === ';') {
            $statements[] = $current;
            $current = '';
        }
        continue;
    }
    $current .= $line . "\n";
    if (substr($trimmed, -1) === ';') {
        $statements[] = $current;
        $current = '';
    }
}

$total = count($statements);
echo "Executing $total statements...\n";

$errors = 0;
$success = 0;
foreach ($statements as $i => $stmt) {
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        $errors++;
        if ($errors <= 5) {
            echo "Error #$errors at stmt " . ($i + 1) . ": " . $e->getMessage() . "\n";
            echo "  SQL: " . substr(trim($stmt), 0, 80) . "...\n";
        }
    }
}

// Re-enable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n=== RESULT ===\n";
echo "Success: $success\n";
echo "Errors: $errors\n";

// Verify
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . count($tables) . "\n";

try {
    $q = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    echo "Questions: $q\n";
} catch (Exception $e) {
    echo "Questions: error\n";
}

try {
    $u = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $u\n";
} catch (Exception $e) {
    echo "Users: error\n";
}

echo "\nDELETE THIS FILE NOW!\n";
echo "</pre>";

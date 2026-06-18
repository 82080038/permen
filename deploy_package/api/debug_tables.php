<?php
/**
 * Debug Database Tables for Tryout Sessions
 */

require '../env_loader.php';

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$db      = $_ENV['DB_NAME']    ?? 'skd_cat_bkn';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Check tryout_sessions table structure
    $stmt = $pdo->query("DESCRIBE tryout_sessions");
    $tryout_sessions = $stmt->fetchAll();
    
    // Check session_subtes table structure  
    $stmt = $pdo->query("DESCRIBE session_subtes");
    $session_subtes = $stmt->fetchAll();
    
    // Check subtes_config table structure
    $stmt = $pdo->query("DESCRIBE subtes_config");
    $subtes_config = $stmt->fetchAll();
    
    // Get sample data
    $stmt = $pdo->query("SELECT * FROM tryout_sessions LIMIT 1");
    $sample_tryout = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT * FROM session_subtes LIMIT 1");
    $sample_session = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT * FROM subtes_config LIMIT 1");
    $sample_config = $stmt->fetch();
    
    echo json_encode([
        'tryout_sessions_structure' => $tryout_sessions,
        'session_subtes_structure' => $session_subtes,
        'subtes_config_structure' => $subtes_config,
        'sample_tryout' => $sample_tryout,
        'sample_session' => $sample_session,
        'sample_config' => $sample_config
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

<?php
/**
 * Debug Database Structure
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
    
    // Check questions table structure
    $stmt = $pdo->query("DESCRIBE questions");
    $questions_structure = $stmt->fetchAll();
    
    // Check question_options table structure  
    $stmt = $pdo->query("DESCRIBE question_options");
    $options_structure = $stmt->fetchAll();
    
    // Get sample data
    $stmt = $pdo->query("SELECT * FROM questions LIMIT 1");
    $sample_question = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT * FROM question_options LIMIT 1");
    $sample_option = $stmt->fetch();
    
    echo json_encode([
        'questions_structure' => $questions_structure,
        'options_structure' => $options_structure,
        'sample_question' => $sample_question,
        'sample_option' => $sample_option
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

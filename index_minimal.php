<?php
// Minimal index.php to isolate HTTP 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Debug: Starting index.php\n";
    
    // Test config.php
    if (file_exists('config.php')) {
        echo "Debug: config.php exists\n";
        require_once 'config.php';
        echo "Debug: config.php loaded\n";
    } else {
        echo "Error: config.php not found\n";
        die("Config file missing");
    }
    
    // Test helpers.php
    if (file_exists('helpers.php')) {
        echo "Debug: helpers.php exists\n";
        require_once 'helpers.php';
        echo "Debug: helpers.php loaded\n";
    } else {
        echo "Error: helpers.php not found\n";
        die("Helpers file missing");
    }
    
    // Test database constants
    echo "Debug: DB_HOST = " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
    echo "Debug: DB_NAME = " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "Debug: DB_USER = " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
    
    // Test getLandingStats function
    if (function_exists('getLandingStats')) {
        echo "Debug: getLandingStats function exists\n";
        $stats = getLandingStats();
        echo "Debug: getLandingStats executed\n";
        echo "Debug: Stats = " . json_encode($stats) . "\n";
    } else {
        echo "Error: getLandingStats function not found\n";
        die("Function missing");
    }
    
    echo "Debug: All tests passed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "Debug: End of index.php\n";
?>

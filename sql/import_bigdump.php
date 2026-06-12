<?php
/**
 * BigDump Import Tool for Free Hosting
 * Import large SQL files in chunks
 * 
 * Upload this file to your hosting and run via browser
 */

// Configuration
$db_server   = 'sqlXXX.epizy.com';  // GANTI dengan MySQL Hostname Anda
$db_name     = 'if0_42138385_skd_cat_bkn';  // GANTI dengan Database Name
$db_username = 'if0_42138385_XXXXX';  // GANTI dengan DB User
$db_password = 'password_anda';  // GANTI dengan DB Password

$file_path   = 'deploy_final.sql';  // Nama file SQL (upload ke folder yang sama)
$chunk_size  = 300;  // Lines per chunk (jangan ubah jika tidak perlu)
$delay_ms    = 100;  // Delay antar chunk dalam ms (untuk hindari timeout)

// START
?>
<!DOCTYPE html>
<html>
<head>
    <title>BigDump - Import Database</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .box { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #333; color: #fff; padding: 10px; overflow-x: auto; }
        .progress { background: #4CAF50; height: 30px; line-height: 30px; color: white; text-align: center; }
    </style>
</head>
<body>
    <h1>BigDump - Database Import Tool</h1>
    
    <?php
    if (!isset($_GET['start']) && !isset($_GET['continue'])) {
        // Show start form
        ?>
        <div class="box">
            <h2>Konfigurasi Database</h2>
            <p>Edit file <code>import_bigdump.php</code> dan ubah:</p>
            <ul>
                <li><strong>db_server:</strong> <?php echo $db_server; ?></li>
                <li><strong>db_name:</strong> <?php echo $db_name; ?></li>
                <li><strong>db_username:</strong> <?php echo $db_username; ?></li>
                <li><strong>db_password:</strong> ******</li>
                <li><strong>file_path:</strong> <?php echo $file_path; ?></li>
            </ul>
            
            <h3>Cara Penggunaan:</h3>
            <ol>
                <li>Upload file SQL (<?php echo $file_path; ?>) ke folder yang sama dengan file ini</li>
                <li>Pastikan konfigurasi database sudah benar</li>
                <li>Klik tombol di bawah untuk mulai import</li>
            </ol>
            
            <p><a href="?start=1" style="background: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; display: inline-block; border-radius: 5px;">🚀 MULAI IMPORT</a></p>
        </div>
        <?php
    } else {
        // Process import
        $start = isset($_GET['continue']) ? intval($_GET['continue']) : 0;
        
        // Connect to database
        $connection = new mysqli($db_server, $db_username, $db_password, $db_name);
        
        if ($connection->connect_error) {
            die('<div class="error">Connection failed: ' . $connection->connect_error . '</div>');
        }
        
        echo '<div class="box">';
        echo '<h2>Status Import</h2>';
        
        // Check if file exists
        if (!file_exists($file_path)) {
            die('<div class="error">File tidak ditemukan: ' . $file_path . '</div>');
        }
        
        $file_size = filesize($file_path);
        echo '<p class="info">File: ' . $file_path . ' (' . number_format($file_size / 1024 / 1024, 2) . ' MB)</p>';
        
        // Read file in chunks
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            die('<div class="error">Tidak bisa membuka file</div>');
        }
        
        // Skip to start position
        $current_line = 0;
        while ($current_line < $start && !feof($handle)) {
            fgets($handle);
            $current_line++;
        }
        
        echo '<p>Memulai dari baris: ' . number_format($start) . '</p>';
        
        // Process chunk
        $query = '';
        $lines_processed = 0;
        $queries_executed = 0;
        $errors = [];
        
        while ($lines_processed < $chunk_size && !feof($handle)) {
            $line = fgets($handle);
            $current_line++;
            
            if (trim($line) == '' || substr($line, 0, 2) == '--' || substr($line, 0, 2) == '/*') {
                continue; // Skip comments and empty lines
            }
            
            $query .= $line;
            
            // If line ends with semicolon, execute query
            if (substr(trim($line), -1) == ';') {
                $query = trim($query);
                
                // Skip problematic statements
                if (strpos($query, 'CREATE VIEW') !== false ||
                    strpos($query, 'CHECK (json_valid') !== false ||
                    strpos($query, 'DEFINER=') !== false) {
                    echo '<span class="info">[SKIP]</span> ' . substr($query, 0, 50) . '...<br>';
                    $query = '';
                    continue;
                }
                
                // Execute query
                if (!empty($query)) {
                    if ($connection->query($query)) {
                        $queries_executed++;
                    } else {
                        $error_msg = $connection->error;
                        // Only show first 100 chars of error
                        $errors[] = substr($error_msg, 0, 100);
                        echo '<span class="error">[ERROR]</span> ' . substr($query, 0, 50) . '...<br>';
                    }
                }
                
                $query = '';
            }
            
            $lines_processed++;
        }
        
        fclose($handle);
        $connection->close();
        
        // Calculate progress
        $total_lines = count(file($file_path));
        $progress = min(100, round(($current_line / $total_lines) * 100));
        
        echo '<div class="progress" style="width: ' . $progress . '%;">' . $progress . '%</div>';
        echo '<p>Baris diproses: ' . number_format($lines_processed) . '</p>';
        echo '<p>Query berhasil: ' . number_format($queries_executed) . '</p>';
        
        if (!empty($errors)) {
            echo '<p class="error">Error terjadi: ' . count($errors) . '</p>';
            echo '<pre>' . implode("\n", array_slice($errors, 0, 5)) . '</pre>';
        }
        
        // Continue button or finish
        if ($current_line < $total_lines) {
            echo '<p><a href="?continue=' . $current_line . '" style="background: #2196F3; color: white; padding: 15px 30px; text-decoration: none; display: inline-block; border-radius: 5px;">➡️ LANJUTKAN (Baris ' . number_format($current_line) . ')</a></p>';
            echo '<p><small>Atau tunggu 3 detik untuk auto-continue...</small></p>';
            echo '<script>setTimeout(function() { window.location.href = "?continue=' . $current_line . '" ; }, 3000);</script>';
        } else {
            echo '<h2 class="success">✅ IMPORT SELESAI!</h2>';
            echo '<p>Total baris diproses: ' . number_format($current_line) . '</p>';
            echo '<p><a href="/" style="background: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; display: inline-block; border-radius: 5px;">🏠 KE HALAMAN UTAMA</a></p>';
        }
        
        echo '</div>';
    }
    ?>
    
    <div class="box" style="margin-top: 30px; font-size: 12px; color: #666;">
        <p>BigDump for SKD CAT-BKN | Free Hosting Compatible</p>
    </div>
</body>
</html>

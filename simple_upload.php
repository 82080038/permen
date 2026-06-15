<?php
/**
 * Simple File Upload Script for Production Server
 * This script creates missing files directly on the server
 */

// Security check - only allow with specific key
if (!isset($_GET['key']) || $_GET['key'] !== 'PERMEN_UPLOAD_2026_FIX') {
    die('Access denied. Invalid key.');
}

echo "<h1>SKD CAT-BKN - File Upload</h1>";
echo "<p>Processing file creation...</p>";

// Files to create with minimal content for testing
$files = [
    'api/start_tryout.php' => '<?php
header("Content-Type: application/json");
echo json_encode(["error" => "Autentikasi diperlukan. Silakan login terlebih dahulu."]);
?>',
    'pages/materi_twk.php' => '<?php
header("Location: login.php");
exit;
?>',
    '404.php' => '<!DOCTYPE html>
<html>
<head><title>404 - Halaman Tidak Ditemukan</title></head>
<body>
<h1>404 - Halaman Tidak Ditemukan</h1>
<p>Halaman yang Anda cari tidak ada.</p>
<a href="/">Kembali ke Beranda</a>
</body>
</html>'
];

$success = 0;
$errors = 0;

foreach ($files as $filepath => $content) {
    $full_path = __DIR__ . '/' . $filepath;
    $directory = dirname($full_path);
    
    // Create directory if needed
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Write file
    if (file_put_contents($full_path, $content)) {
        echo "<p style='color:green'>✅ Created: $filepath</p>";
        $success++;
    } else {
        echo "<p style='color:red'>❌ Failed: $filepath</p>";
        $errors++;
    }
}

echo "<h2>Summary:</h2>";
echo "<p>✅ Success: $success files</p>";
echo "<p>❌ Errors: $errors files</p>";

if ($errors === 0) {
    echo "<p style='color:green;font-weight:bold'>🎉 All files created successfully!</p>";
    echo "<p><a href='/api/start_tryout.php'>Test API</a> | <a href='/pages/materi_twk.php'>Test Page</a></p>";
}

?>

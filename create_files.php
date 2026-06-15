<?php
/**
 * Create Missing Files Script
 * This script will create the missing API and page files
 */

// Security - only allow with specific parameter
if (!isset($_GET['create']) || $_GET['create'] !== 'PERMEN_2026') {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

header('Content-Type: text/plain');

echo "Creating missing files...\n";

// Define files to create
$files = [
    'api/start_tryout.php' => '<?php
header("Content-Type: application/json");
echo json_encode(["error" => "Autentikasi diperlukan. Silakan login terlebih dahulu."]);
?>',
    'api/create_session.php' => '<?php
header("Content-Type: application/json");
echo json_encode(["error" => "Autentikasi diperlukan"]);
?>',
    'api/get_questions.php' => '<?php
header("Content-Type: application/json");
echo json_encode(["error" => "Autentikasi diperlukan"]);
?>',
    'pages/materi_twk.php' => '<?php
header("Location: login.php");
exit;
?>',
    'pages/materi_tiu.php' => '<?php
header("Location: login.php");
exit;
?>',
    'pages/materi_tkp.php' => '<?php
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
        if (mkdir($directory, 0755, true)) {
            echo "Created directory: $directory\n";
        } else {
            echo "Failed to create directory: $directory\n";
            $errors++;
            continue;
        }
    }
    
    // Write file
    if (file_put_contents($full_path, $content)) {
        echo "SUCCESS: Created $filepath\n";
        $success++;
    } else {
        echo "ERROR: Failed to create $filepath\n";
        $errors++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Success: $success files\n";
echo "Errors: $errors files\n";

if ($errors === 0) {
    echo "ALL FILES CREATED SUCCESSFULLY!\n";
} else {
    echo "Some files failed to create.\n";
}

?>

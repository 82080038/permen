<?php
/**
 * Export Database via mysqldump ke folder sql/
 * Hanya dari localhost
 */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'])) {
    http_response_code(403); echo 'Forbidden'; exit;
}

require __DIR__ . '/../env_loader.php';

$host   = $_ENV['DB_HOST']   ?? 'localhost';
$dbName = $_ENV['DB_NAME']   ?? 'skd_cat_bkn';
$user   = $_ENV['DB_USER']   ?? 'root';
$pass   = $_ENV['DB_PASS']   ?? '';

$outFile = __DIR__ . '/../sql/skd_cat_bkn_latest.sql';
$date    = date('Y-m-d H:i:s');

// Coba mysqldump dengan password, fallback tanpa password
$passFlag = $pass !== '' ? "-p" . escapeshellarg($pass) : '';
// Cari mysqldump di lokasi XAMPP atau PATH
$mysqldumpPaths = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'C:\\xampp64\\mysql\\bin\\mysqldump.exe',
    'mysqldump',
];
$mysqldump = 'mysqldump';
foreach ($mysqldumpPaths as $p) {
    if (file_exists($p)) { $mysqldump = $p; break; }
}

$cmd = escapeshellarg($mysqldump) . " --host=" . escapeshellarg($host)
     . " --user=" . escapeshellarg($user)
     . ($pass !== '' ? " --password=" . escapeshellarg($pass) : '')
     . " --single-transaction --routines --triggers --events"
     . " --add-drop-table --complete-insert --extended-insert"
     . " " . escapeshellarg($dbName)
     . " 2>&1";

$output = shell_exec($cmd);

// Validasi output: harus diawali komentar MariaDB/MySQL dump
$isValid = $output && strlen($output) > 500 && (
    strpos($output, 'MariaDB dump') !== false ||
    strpos($output, 'MySQL dump') !== false ||
    strpos($output, 'CREATE TABLE') !== false
);
if (!$isValid) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'mysqldump gagal atau output tidak valid',
        'output'  => substr($output ?? '', 0, 500)
    ]);
    exit;
}

// Tambahkan header komentar
$header = "-- SKD CAT-BKN Database Export\n"
        . "-- Generated: $date\n"
        . "-- Host: $host  Database: $dbName\n"
        . "-- Note: User data fresh after simulation\n\n";

file_put_contents($outFile, $header . $output);
$size = filesize($outFile);

header('Content-Type: application/json');
echo json_encode([
    'status'  => 'success',
    'file'    => 'sql/skd_cat_bkn_latest.sql',
    'size'    => number_format($size) . ' bytes',
    'date'    => $date
]);

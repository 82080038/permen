<?php
session_start();
$_SESSION['user_id'] = 275;
$_SESSION['user_nama'] = 'User Test';
$_SESSION['user_role'] = 'user';

$sessionId = 517; // Use a new session ID
$url = "http://localhost/permen/api/get_soal.php?session_id=$sessionId";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body:\n$body\n";

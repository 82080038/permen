<?php
/**
 * WhatsApp Notification Helper — SKD CAT-BKN
 * Uses Fonnte API for sending WhatsApp messages
 * 
 * Setup: Set FONNTE_API_KEY in .env
 * Get API key at https://fonnte.com
 */

function sendWhatsApp(string $phone, string $message): array
{
    $apiKey = $_ENV['FONNTE_API_KEY'] ?? '';
    
    if (!$apiKey) {
        error_log('[WhatsApp] FONNTE_API_KEY not set in .env');
        return ['success' => false, 'error' => 'WhatsApp API not configured'];
    }
    
    // Normalize phone number (Indonesian format)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }
    
    $data = [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62',
    ];
    
    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("[WhatsApp] cURL error: $error");
        return ['success' => false, 'error' => $error];
    }
    
    $result = json_decode($response, true);
    if ($httpCode === 200 && ($result['status'] ?? false) === true) {
        return ['success' => true, 'data' => $result];
    }
    
    error_log("[WhatsApp] API error: $response");
    return ['success' => false, 'error' => $result['message'] ?? 'Unknown error'];
}

/**
 * Send tryout result notification via WhatsApp
 */
function sendTryoutResultWhatsApp(string $phone, string $nama, array $scores): array
{
    $total = $scores['TWK'] + $scores['TIU'] + $scores['TKP'];
    $msg = "*SKD CAT-BKN — Hasil Try Out*\n\n";
    $msg .= "Halo *$nama*,\n\n";
    $msg .= "Hasil try out Anda:\n";
    $msg .= "• TWK: {$scores['TWK']}\n";
    $msg .= "• TIU: {$scores['TIU']}\n";
    $msg .= "• TKP: {$scores['TKP']}\n";
    $msg .= "• *Total: $total*\n\n";
    $msg .= "Selamat belajar! 💪\n";
    $msg .= "_bimbel.bereng.info_";
    
    return sendWhatsApp($phone, $msg);
}

/**
 * Send OTP for password reset via WhatsApp
 */
function sendOtpWhatsApp(string $phone, string $otp): array
{
    $msg = "*SKD CAT-BKN — Reset Password*\n\n";
    $msg .= "Kode OTP Anda: *$otp*\n\n";
    $msg .= "Kode ini berlaku 5 menit.\n";
    $msg .= "Jangan bagikan kode ini kepada siapapun.\n";
    $msg .= "_bimbel.bereng.info_";
    
    return sendWhatsApp($phone, $msg);
}

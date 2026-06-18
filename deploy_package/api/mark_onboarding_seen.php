<?php
require '../config.php';

// Load ApiResponse class
require_once __DIR__ . '/../src/Http/ApiResponse.php';

use App\Http\ApiResponse;

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    ApiResponse::unauthorized('Autentikasi diperlukan');
}

$_SESSION['onboarding_seen'] = true;
ApiResponse::success([], 'Onboarding marked as seen');

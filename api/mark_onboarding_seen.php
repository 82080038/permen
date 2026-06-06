<?php
session_start();
$_SESSION['onboarding_seen'] = true;
echo json_encode(['success' => true]);

<?php
// This script should be run by a cron job periodically to reset expired lockouts
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/functions.php';

// Reset user account lockouts that have expired
$stmt = $pdo->prepare("
    UPDATE users 
    SET failed_login_attempts = 0, lockout_until = NULL 
    WHERE lockout_until IS NOT NULL 
    AND lockout_until <= NOW()
");
$stmt->execute();
$userLocksReset = $stmt->rowCount();

// Remove IP rate limits that have expired
$stmt = $pdo->prepare("
    DELETE FROM ip_rate_limits 
    WHERE blocked_until IS NOT NULL 
    AND blocked_until <= NOW()
");
$stmt->execute();
$ipLocksReset = $stmt->rowCount();

// Log the results
$total = $userLocksReset + $ipLocksReset;
echo "Reset completed: {$userLocksReset} user lockouts and {$ipLocksReset} IP blocks cleared ({$total} total)\n";

// Create a log file
$logFile = __DIR__ . '/logs/reset_lockouts.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . "\n", FILE_APPEND);
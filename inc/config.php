<?php
// Configuration settings for the application

// Suppress warnings
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flight_booking');

// API settings for AviationStack
define('FLIGHT_API_URL', 'https://api.aviationstack.com/v1');

// Multiple API keys - when one is capped/fails, the system will try the next one
define('FLIGHT_API_KEYS', json_encode([
    'd4463db3778d557c53f81d34a0ff6fa3', // Primary key
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94',
    '16cebcc9041492c90f3eb7ff50e66d94'
]));
define('OPENROUTER_API_KEYS', json_encode([
    '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' // Primary key
    // You can add backup keys here if needed
]));
// For backward compatibility
define('FLIGHT_API_KEY', json_decode(FLIGHT_API_KEYS, true)[0]);

// Site settings
define('SITE_NAME', 'Flight Booking Website');

// Create dynamic site URL instead of hardcoded value
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = '';

// Check if we're in a subdirectory installation
if (strpos($_SERVER['SCRIPT_NAME'], 'INF1005-P2-6-Proj') !== false) {
    $basePath = '/INF1005-P2-6-Proj';
}

define('SITE_URL', $protocol . $host . $basePath);
?>
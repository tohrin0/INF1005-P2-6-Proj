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
define('FLIGHT_API_KEY', '5b80cd8ed0bbd919d3c05c5ce5b1331e');

// Site settings
define('SITE_NAME', 'Flight Booking Website');
define('SITE_URL', 'http://localhost:8000');
?>
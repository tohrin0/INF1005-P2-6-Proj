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
define('FLIGHT_API_KEY', 'b1e954e7e3175e7f9bd74dea31b836d3');

// Site settings
define('SITE_NAME', 'Flight Booking Website');
define('SITE_URL', 'http://localhost:8000');
?>
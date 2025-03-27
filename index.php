<?php
// Include session management first - this handles all session security
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// For debugging - show detailed errors
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Define the base path for views
define('VIEWS_PATH', __DIR__ . '/Views/');

// Get the requested path from URL
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query string if present
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove base directory from path if needed
$base_dir = '/INF1005-P2-6-Proj/';  // Change this if your app is in a subdirectory
$request_path = str_replace($base_dir, '', $request_path);

// Default to home page if no path is specified
if (empty($request_path) || $request_path === '/') {
    $request_path = 'index';
}

// Debug: Show what path was requested
// echo "Requested path: " . $request_path . "<br>";

// Clean the path to prevent directory traversal attacks
$request_path = str_replace('../', '', $request_path);
$request_path = rtrim($request_path, '/');
$request_path = str_replace('.php', '', $request_path);

// Map routes to view files
$routes = [
    'home' => 'home.php',
    'index' => 'index.php',
    'unsubscribe' => 'unsubscribe.php',
    'about' => 'about.php',
    'account' => 'account.php',
    'confirmation' => 'confirmation.php',
    'contact' => 'contact.php',
    'faq' => 'faq.php',
    'globe' => 'globe.php',
    'login' => 'login.php',
    'logout' => 'logout.php',
    'manage-passengers' => 'manage-passengers.php',
    'my-bookings' => 'my-bookings.php',
    'booking' => 'booking.php',
    'confirmbooking' => 'confirmbooking.php',
    'cancel-booking' => 'cancel-booking.php',
    'payment' => 'payment.php',
    'payments' => 'payments.php',
    'register' => 'register.php',
    'reset-password' => 'reset-password.php',
    'search2' => 'search2.php',
    'services' => 'services.php',
    'terms' => 'terms.php',
    'privacy-policy' => 'privacy-policy.php',
    'membership' => 'membership.php'
];


// Check if the route exists
if (array_key_exists($request_path, $routes)) {
    $view_file = VIEWS_PATH . $routes[$request_path];

    // Debug: Show file path being checked
    // echo "Checking file: " . $view_file . "<br>";
    // echo "File exists: " . (file_exists($view_file) ? "Yes" : "No") . "<br>";

    // Check if the file exists
    if (file_exists($view_file)) {
        // Include the view file
        include $view_file;
        exit;
    }
}

// If we get here, the route doesn't exist or the file is missing
// Display a 404 error page
header("HTTP/1.0 404 Not Found");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>
    <?php include 'Views/components/404.php'; ?>
    <?php include 'templates/footer.php'; ?>
</body>

</html>
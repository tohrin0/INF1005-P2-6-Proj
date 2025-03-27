<?php
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/functions.php';

// Log the logout action before destroying the session
if (isset($_SESSION['user_id'])) {
    error_log("User ID: {$_SESSION['user_id']} logged out");
}

// Use the centralized session destruction function
destroySession();

// Start a new secure session
secureSessionStart();

// Set a message for the login page
$_SESSION['login_message'] = "You have been successfully logged out.";
$_SESSION['login_message_type'] = "success";

// Redirect to homepage
header("Location: login.php");
exit();
?>
<?php
session_start();
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Clear all session variables
$_SESSION = array();

// If a session cookie is used, unset it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit();
?>
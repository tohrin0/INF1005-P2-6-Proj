<?php

/**
 * Include this at the top of any page that requires authentication
 */
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

// Verify user is authenticated
verifyAuthenticatedSession();

// Get user data 
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// If user data couldn't be retrieved, there's a problem
if (!$user) {
    destroySession();
    $_SESSION['login_message'] = "Your session has been terminated due to a security concern.";
    $_SESSION['login_message_type'] = "error";
    header('Location: login.php');
    exit();
}
?>
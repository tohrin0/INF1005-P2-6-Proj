<?php
session_start();
require 'inc/config.php';
require 'inc/db.php';
require 'inc/functions.php';
require 'inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50));
    
    if (emailExists($email)) {
        storeToken($email, $token);
        sendPasswordResetEmail($email, $token);
        $message = "A password reset link has been sent to your email.";
    } else {
        $message = "Email address not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css">
    <title>Reset Password</title>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        <form action="" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</body>
</html>
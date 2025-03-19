<?php
session_start();
require 'inc/config.php';
require 'inc/db.php';
require 'inc/functions.php';
require 'inc/auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "error";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $token = bin2hex(random_bytes(50));
        
        if (emailExists($email)) {
            storeToken($email, $token);
            sendPasswordResetEmail($email, $token);
            $message = "A password reset link has been sent to your email.";
            $messageType = "success";
        } else {
            $message = "Email address not found.";
            $messageType = "error";
        }
    }
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Flight Booking</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <p>Enter your email to receive a reset link</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="<?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?>">
                    <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <button type="submit" class="auth-btn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
                
                <div class="auth-footer">
                    <div class="auth-links">
                        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                        <a href="register.php">Create new account</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
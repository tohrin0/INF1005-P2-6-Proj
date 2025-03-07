<?php
session_start(); // Add session_start() at the beginning
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php'; // Add this
require_once 'inc/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
// After including db.php, add:
if (!isset($pdo) || $pdo === null) {
    die("Database connection failed. Check your db.php file and database settings.");
}
// Move this error variable declaration here
$error = '';

if (isset($_POST['username']) || isset($_POST['email'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Register user
        if (registerUser($username, $password, $email)) {
            header("Location: login.php?success=Registration successful. Please log in.");
            exit;
        } else {
            $error = "Registration failed. Email or username may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Flight Booking</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join us to start booking flights</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST">
                <input type="hidden" name="debug" value="1">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" id="username" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                </div>
                <button type="submit" class="auth-btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Log in</a></p>
                </div>
            </form>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
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
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <title>Register</title>
    <style>
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        
        form div {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], 
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .error {
            color: #f44336;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Add debugging output -->
        <?php 
        if (isset($_POST['username'])) {
            echo "<!-- Form submitted with: " . 
                "username=" . htmlspecialchars($username ?? '') . 
                ", email=" . htmlspecialchars($email ?? '') . 
                " -->";
        }
        ?>
        
        <form action="register.php" method="POST">
            <input type="hidden" name="debug" value="1">
            <div>
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <div>
                <button type="submit">Register</button>
            </div>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <title>Account Management</title>
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <h1>Account Management</h1>
        <form action="update-account.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <button type="submit">Update Account</button>
        </form>

        <h2>Change Password</h2>
        <form action="change-password.php" method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <button type="submit">Change Password</button>
        </form>

        <h2>Delete Account</h2>
        <form action="delete-account.php" method="POST">
            <button type="submit" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">Delete Account</button>
        </form>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
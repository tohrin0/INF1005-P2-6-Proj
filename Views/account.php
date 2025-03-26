<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/User.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);
$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle profile update
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Validate input
        if (empty($username) || empty($email)) {
            $message = "All fields are required.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
            $messageType = "error";
        } else {
            // Update profile
            $userObj = new User($pdo);
            if ($userObj->updateProfile($_SESSION['user_id'], $username, $email)) {
                $message = "Profile updated successfully.";
                $messageType = "success";
                // Refresh user data
                $user = getUserById($_SESSION['user_id']);
            } else {
                $message = "Failed to update profile.";
                $messageType = "error";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Handle password change
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = "All password fields are required.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match.";
            $messageType = "error";
        } else {
            // Validate password strength
            list($isValid, $validationMessage) = validatePasswordStrength($newPassword);
            if (!$isValid) {
                $message = $validationMessage;
                $messageType = "error";
            } else {
                // Change password
                $userObj = new User($pdo);
                $result = $userObj->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                
                if ($result['success']) {
                    $message = $result['message'];
                    $messageType = "success";
                } else {
                    $message = $result['message'];
                    $messageType = "error";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Flight Booking</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <?php include 'templates/header.php'; ?>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">My Account</h1>
        
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded <?php echo $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Profile update form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Profile Information</h2>
                <form action="account.php" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 mb-2">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <button type="submit" name="update_profile" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Update Profile</button>
                </form>
            </div>
            
            <!-- Password change form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Change Password</h2>
                <form action="account.php" method="POST">
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-700 mb-2">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-700 mb-2">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border rounded-md">
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters with 1 uppercase letter and 1 special character</p>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded-md">
                    </div>
                    <button type="submit" name="change_password" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
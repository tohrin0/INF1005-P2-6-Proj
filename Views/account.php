<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
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

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$message = '';
$messageType = '';

// Check if user has requested deletion
$deletionRequested = false;
$deletionTime = null;
$remainingTime = null;

if (!empty($user['deletion_requested'])) {
    $deletionRequested = true;
    $deletionTime = strtotime($user['deletion_requested']);
    $remainingTime = $deletionTime + (24 * 60 * 60) - time(); // 24 hours in seconds
    
    // If time expired, account should be deleted by cron job
    // This is just a safety check
    if ($remainingTime <= 0) {
        $message = "Your account is scheduled for deletion. It will be removed shortly.";
        $messageType = "warning";
    }
}

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
    } elseif (isset($_POST['request_deletion'])) {
        // Handle account deletion request
        $userObj = new User($pdo);
        $token = bin2hex(random_bytes(32));
        $deletionTime = date('Y-m-d H:i:s');
        
        $result = $userObj->requestDeletion($userId, $deletionTime, $token);
        
        if ($result) {
            $message = "Your account is scheduled for deletion in 24 hours.";
            $messageType = "warning";
            $deletionRequested = true;
            $deletionTime = strtotime($deletionTime);
            $remainingTime = $deletionTime + (24 * 60 * 60) - time();
        } else {
            $message = "Failed to schedule account deletion. Please try again.";
            $messageType = "error";
        }
    } elseif (isset($_POST['cancel_deletion'])) {
        // Handle cancellation of deletion request
        $userObj = new User($pdo);
        $result = $userObj->cancelDeletion($userId);
        
        if ($result) {
            $message = "Account deletion has been cancelled.";
            $messageType = "success";
            $deletionRequested = false;
            $deletionTime = null;
            $remainingTime = null;
        } else {
            $message = "Failed to cancel account deletion. Please try again.";
            $messageType = "error";
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
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Account</h1>
            <div class="text-sm text-gray-500">
                Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg shadow-sm <?php echo $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : ($messageType === 'warning' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 'bg-green-50 text-green-700 border border-green-200'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile update form -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="rounded-full bg-blue-100 p-3 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800">Profile Information</h2>
                </div>
                
                <form action="account.php" method="POST" class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="update_profile" 
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Update Profile
                    </button>
                </form>
            </div>
            
            <!-- Password change form -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="rounded-full bg-indigo-100 p-3 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800">Security</h2>
                </div>
                
                <form action="account.php" method="POST" class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters with 1 uppercase letter and 1 special character</p>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <button type="submit" name="change_password" 
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        Change Password
                    </button>
                </form>
            </div>
            
            <!-- Account Management -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-4">
                    <div class="rounded-full bg-red-100 p-3 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800">Account Management</h2>
                </div>
                
                <?php if ($deletionRequested && $remainingTime > 0): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <h3 class="font-medium text-red-800 mb-2">Account Scheduled for Deletion</h3>
                        <p class="text-red-700 mb-3">Your account will be permanently deleted in:</p>
                        <div id="countdown-timer" class="text-2xl font-mono text-center font-bold text-red-800 mb-4">
                            --:--:--
                        </div>
                        <form action="account.php" method="POST">
                            <button type="submit" name="cancel_deletion" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                Cancel Deletion
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <p class="text-gray-600">Delete your account and all associated data. This action cannot be undone after the 24-hour waiting period.</p>
                        
                        <details class="bg-gray-50 p-4 rounded-lg">
                            <summary class="font-medium text-gray-700 cursor-pointer">What happens when I delete my account?</summary>
                            <div class="mt-3 text-gray-600 text-sm">
                                <p>When you request account deletion:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>Your account will be marked for deletion</li>
                                    <li>You'll have 24 hours to cancel this request</li>
                                    <li>After 24 hours, all your personal data will be permanently deleted</li>
                                    <li>Your bookings history and personal information cannot be recovered</li>
                                </ul>
                            </div>
                        </details>
                        
                        <form action="account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? You will have 24 hours to cancel this request.');">
                            <button type="submit" name="request_deletion" class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                Delete Account
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($deletionRequested && $remainingTime > 0): ?>
    <script>
        // Countdown timer for account deletion
        const countdownTimer = document.getElementById('countdown-timer');
        let remainingSeconds = <?php echo max(0, $remainingTime); ?>;
        
        function updateTimer() {
            if (remainingSeconds <= 0) {
                countdownTimer.textContent = "00:00:00";
                return;
            }
            
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            
            countdownTimer.textContent = 
                (hours < 10 ? '0' : '') + hours + ':' +
                (minutes < 10 ? '0' : '') + minutes + ':' +
                (seconds < 10 ? '0' : '') + seconds;
                
            remainingSeconds--;
            
            setTimeout(updateTimer, 1000);
        }
        
        // Start the timer when page loads
        document.addEventListener('DOMContentLoaded', updateTimer);
    </script>
    <?php endif; ?>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
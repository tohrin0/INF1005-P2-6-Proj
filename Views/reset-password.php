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
require_once 'vendor/autoload.php';
require_once 'classes/PasswordReset.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$passwordReset = new PasswordReset($pdo);
$message = '';
$messageType = '';
$showOtpForm = false;
$showNewPasswordForm = false;
$isAdminReset = false;

// Check for admin reset token in URL
if (isset($_GET['token']) && isset($_GET['admin_reset'])) {
    $token = $_GET['token'];
    $user = $passwordReset->verifyAdminResetToken($token);
    
    if ($user) {
        $isAdminReset = true;
        $showNewPasswordForm = true;
        $message = "This is an administrator-requested password reset. Please create a new password.";
        $messageType = "success";
    } else {
        $message = "Invalid or expired reset token. Please contact an administrator.";
        $messageType = "error";
    }
}

// Regular OTP request flow
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_otp'])) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "error";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        // Check if email exists in the database
        if ($passwordReset->emailExists($email)) {
            // Generate a 6-digit OTP
            $otp = $passwordReset->generateOTP();
            
            // Store OTP in session for verification
            $passwordReset->storeOTP($email, $otp);
            
            // Send OTP via email
            if ($passwordReset->sendOTPEmail($email, $otp)) {
                $message = "An OTP has been sent to your email. Please check and enter below.";
                $messageType = "success";
                $showOtpForm = true;
            } else {
                $message = "Failed to send OTP. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Email address not found.";
            $messageType = "error";
        }
    }
}

// Verify OTP step
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'] ?? '';
    
    if (empty($entered_otp)) {
        $message = "Please enter the OTP sent to your email.";
        $messageType = "error";
        $showOtpForm = true;
    } else {
        // Check if OTP is valid and not expired
        if ($passwordReset->verifyOTP($entered_otp)) {
            $message = "OTP verified successfully. You can now reset your password.";
            $messageType = "success";
            $showNewPasswordForm = true;
            // Reset OTP form flag to prevent showing it again
            $showOtpForm = false;
        } else {
            $message = "Invalid or expired OTP. Please try again.";
            $messageType = "error";
            $showOtpForm = true;
        }
    }
}

// Reset Password step (works for both regular and admin resets)
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_reset = isset($_POST['admin_reset']) && $_POST['admin_reset'] === '1';
    
    if (empty($password) || empty($confirm_password)) {
        $message = "Please enter and confirm your new password.";
        $messageType = "error";
        $showNewPasswordForm = true;
        if ($admin_reset) $isAdminReset = true;
    } else if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "error";
        $showNewPasswordForm = true;
        if ($admin_reset) $isAdminReset = true;
    } else {
        // Replace the minimum length check with a full policy validation
        list($isValidPassword, $passwordMessage) = validatePasswordStrength($password);
        if (!$isValidPassword) {
            $message = $passwordMessage;
            $messageType = "error";
            $showNewPasswordForm = true;
            if ($admin_reset) $isAdminReset = true;
        } else {
            // Make sure we have the user's email from session
            if (isset($_SESSION['reset_email'])) {
                $email = $_SESSION['reset_email'];
                
                // Use the updated resetPassword method that checks history
                $result = $passwordReset->resetPassword($email, $password);
                
                if ($result['success']) {
                    // Clear all form flags
                    $showOtpForm = false;
                    $showNewPasswordForm = false;
                    
                    // If this was an admin reset, clear the token
                    if ($admin_reset) {
                        $passwordReset->clearAdminResetToken($email);
                    }
                    
                    // Add success message to session to display on login page
                    $_SESSION['login_message'] = $result['message'];
                    $_SESSION['login_message_type'] = "success";
                    
                    // Redirect to login immediately
                    header("Location: login.php");
                    exit(); // Stop script execution to ensure redirect
                } else {
                    $message = $result['message'];
                    $messageType = "error";
                    $showNewPasswordForm = true;
                    if ($admin_reset) $isAdminReset = true;
                }
            } else {
                $message = "Session expired. Please restart the password reset process.";
                $messageType = "error";
            }
        }
    }
}

// If we've made it this far, we need to include the header and show the form
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
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-xl">
            <div class="md:flex">
                <div class="p-8 w-full">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold mb-1">Security</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo $isAdminReset ? 'Administrator-Initiated Password Reset' : ($showNewPasswordForm ? 'Create New Password' : ($showOtpForm ? 'Enter Verification Code' : 'Reset Your Password')); ?>
                    </h2>
                    <p class="text-gray-600 mb-6">
                        <?php echo $isAdminReset ? 'An administrator has requested you to reset your password' : ($showNewPasswordForm ? 'Choose a strong password for your account' : ($showOtpForm ? 'Enter the 6-digit code sent to your email' : 'Enter your email to receive a verification code')); ?>
                    </p>
                    
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?> w-5 h-5"></i>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($message); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($showNewPasswordForm): ?>
                        <!-- New Password Form -->
                        <form action="" method="POST" class="space-y-6">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter new password" required>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters with 1 uppercase letter and 1 special character</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Confirm new password" required>
                                </div>
                            </div>
                            
                            <?php if ($isAdminReset): ?>
                                <input type="hidden" name="admin_reset" value="1">
                            <?php endif; ?>
                            
                            <div>
                                <button type="submit" name="reset_password" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Reset Password
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($showOtpForm): ?>
                        <!-- OTP Verification Form -->
                        <form action="" method="POST" class="space-y-6">
                            <div>
                                <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <input type="text" id="otp" name="otp" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 letter-spacing-wide text-center tracking-widest" placeholder="000000" maxlength="6" required>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Enter the 6-digit code sent to your email</p>
                            </div>
                            
                            <div>
                                <button type="submit" name="verify_otp" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Verify Code
                                </button>
                            </div>
                            
                            <div class="text-sm text-center">
                                <a href="reset-password.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Try with a different email
                                </a>
                            </div>
                        </form>
                        
                    <?php elseif (!$isAdminReset): ?>
                        <!-- Email Request Form (only show if not admin reset) -->
                        <form action="" method="POST" class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" id="email" name="email" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" name="request_otp" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Send Verification Code
                                </button>
                            </div>
                            
                            <div class="text-sm text-center">
                                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Back to Login
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Security Tips Section -->
        <div class="max-w-md mx-auto mt-8 bg-white rounded-xl shadow-sm overflow-hidden md:max-w-xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Security Tips</h3>
                <div class="space-y-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shield-alt text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Create a strong password using a combination of letters, numbers, and symbols.
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-fingerprint text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Never share your password or verification codes with anyone.
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-sync-alt text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Change your password regularly and avoid reusing old passwords.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <script>
        // Auto focus on OTP input and format it nicely
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.focus();
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>
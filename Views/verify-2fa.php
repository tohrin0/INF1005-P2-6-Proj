<?php
ob_start(); // Start output buffering to prevent premature output

require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/User.php';
require_once 'classes/TwoFactorAuth.php';

// If user is already fully logged in, redirect to index.php
if (isset($_SESSION['user_id']) && !isset($_SESSION['2fa_user_id'])) {
    header("Location: index.php");
    exit();
}

// If user hasn't started the login process, redirect to login page
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_email'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$userId = $_SESSION['2fa_user_id'];
$email = $_SESSION['2fa_email'];

// Create objects
$user = new User($pdo);
$twoFactorAuth = new TwoFactorAuth($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $code = $_POST['code'] ?? '';
        
        if (empty($code)) {
            $error = "Please enter the verification code.";
        } else {
            // Get user's 2FA secret
            $secret = $twoFactorAuth->getUserSecret($userId);
            
            if ($secret && $twoFactorAuth->verifyCode($secret, $code)) {
                // Code is valid, complete login
                $userData = $user->getUserById($userId);
                
                // Clear 2FA verification data
                unset($_SESSION['2fa_email']);
                unset($_SESSION['2fa_user_id']);
                
                // Set session variables for authenticated user
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['login_time'] = time();
                
                // Regenerate session ID to prevent session fixation
                regenerateSessionId();
                
                // Redirect to intended page or home
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                $error = "Invalid verification code. Please try again.";
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
    <title>Verify Two-Factor Authentication - Sky International Travels</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>
    
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">Two-Factor Authentication</h2>
                <p class="mt-2 text-gray-600">Enter the verification code from your authenticator app</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="p-4 bg-red-50 border border-red-200 rounded-md text-red-700">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="verify-2fa.php" class="space-y-6 mt-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Authentication Code</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="text" id="code" name="code" 
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-center tracking-widest" 
                            placeholder="Enter 6-digit code" required autocomplete="off" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Enter the 6-digit code from your authenticator app</p>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Verify and Login
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="login.php?cancel=1" class="text-sm text-indigo-600 hover:text-indigo-500">
                        Cancel and return to login
                    </a>
                </div>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-700">Having trouble?</h3>
                <p class="mt-1 text-sm text-gray-500">Make sure your authenticator app is showing the correct time and that you're entering the most recent code.</p>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
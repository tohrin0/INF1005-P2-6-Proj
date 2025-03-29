<?php
// Replace direct session management with centralized session handling
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/User.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'inc/accessibility.php'; 

// If user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$isLocked = false;
$isRateLimited = false;
$needs2FA = false;
$email = '';

// Create user object
$user = new User($pdo);
$twoFactorAuth = new TwoFactorAuth($pdo);

// Get messages from session
if (isset($_SESSION['login_message'])) {
    $success = $_SESSION['login_message'];
    $messageType = $_SESSION['login_message_type'] ?? 'success';
    // Clear the session variables
    unset($_SESSION['login_message']);
    unset($_SESSION['login_message_type']);
}

// Step 2: Verify 2FA code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $code = $_POST['code'] ?? '';
        $email = $_SESSION['2fa_email'] ?? '';
        $userId = $_SESSION['2fa_user_id'] ?? 0;
        
        if (empty($code)) {
            $error = "Please enter the verification code.";
            $needs2FA = true;
        } else {
            // Get user's 2FA secret
            $secret = $twoFactorAuth->getUserSecret($userId);
            
            if ($secret && $twoFactorAuth->verifyCode($secret, $code)) {
                // Code is valid, complete login
                unset($_SESSION['2fa_email']);
                unset($_SESSION['2fa_user_id']);
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $userData = $user->getUserById($userId);
                $_SESSION['username'] = $userData['username'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['login_time'] = time();
                
                // Regenerate session ID to prevent session fixation
                regenerateSessionId();
                
                // Reset failed login attempts
                $user->resetFailedAttempts($userId);
                
                // Redirect to intended page or home
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                $error = "Invalid verification code. Please try again.";
                $needs2FA = true;
            }
        }
    }
}
// Step 1: Initial login with email/password
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            // Attempt to login
            $result = $user->login($email, $password);
            
            if ($result['success']) {
                // No 2FA, proceed with login
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['username'] = $result['username'];
                $_SESSION['role'] = $result['role'];
                $_SESSION['login_time'] = time();
                
                // Redirect to home page after successful login
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                // Check if 2FA is required
                if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                    // Store email and user ID in session for 2FA verification
                    $_SESSION['2fa_email'] = $email;
                    $_SESSION['2fa_user_id'] = $result['user_id'];
                    
                    // Redirect to dedicated 2FA verification page
                    header("Location: verify-2fa.php");
                    exit();
                } else {
                    // Regular login failure
                    $error = $result['message'];
                    $isLocked = isset($result['locked']) && $result['locked'];
                    $isRateLimited = isset($result['rate_limited']) && $result['rate_limited'];
                }
            }
        }
    }
}

// Save the intended URL if it's passed as a parameter
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Flight Booking</title>
    
    
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
            <div class="md:flex">
                <div class="p-8 w-full">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold mb-1">Account</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                    <p class="text-gray-600 mb-6">Sign in to access your account</p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="mb-6 p-4 <?php echo ($isLocked || $isRateLimited) ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-red-50 text-red-700 border border-red-200'; ?> rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="text-red-600 text-xl">✕</span>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($error); ?></p>
                                    <?php if ($isLocked): ?>
                                        <p class="mt-2">
                                            <a href="reset-password.php" class="text-orange-800 underline">Reset your password</a> to unlock your account immediately.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success) && !empty($success)): ?>
                        <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="text-green-600 text-xl">✓</span>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($success); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($needs2FA): ?>
                        <!-- 2FA Verification Form -->
                        <form method="POST" action="login.php" class="space-y-6" <?php echo ($isRateLimited) ? 'hidden' : ''; ?>>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="verify_2fa" value="1">
                            
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400">🔑</span>
                                    </div>
                                    <input type="text" id="code" name="code" 
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" 
                                        placeholder="Enter 6-digit code" required autocomplete="off" inputmode="numeric" pattern="[0-9]*" maxlength="6">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Enter the 6-digit code from your authenticator app</p>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Verify and Sign in
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Regular Login Form -->
                        <form method="POST" action="login.php" class="space-y-6" <?php echo ($isRateLimited) ? 'hidden' : ''; ?>>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-at text-gray-400"></i>
                                    </div>
                                    <input type="email" id="email" name="email" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" name="login" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Sign In
                                </button>
                            </div>
                            
                            <div class="flex items-center justify-center">
                                <div class="text-sm">
                                    <a href="reset-password.php" class="inline-block w-full py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                                        <i class="fas fa-key mr-2"></i> Forgot your password?
                                    </a>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-sm">
                                    Don't have an account?
                                    <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                        Create Account
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($isRateLimited): ?>
                        <div class="text-center mt-6">
                            <p class="text-gray-600 mb-4">Too many login attempts from your IP address.</p>
                            <p class="text-gray-600">Please try again later or contact support.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
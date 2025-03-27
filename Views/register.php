<?php
// Replace direct session management with centralized session handling
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/User.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'vendor/autoload.php';
require_once 'inc/accessibility.php';

// If user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$showQRCode = false;
$secret = '';
$qrCode = '';
$userId = 0;

// Create user object
$user = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        // Handle 2FA setup confirmation
        if (isset($_POST['setup_2fa']) && isset($_SESSION['temp_user_id']) && isset($_POST['verification_code'])) {
            $userId = $_SESSION['temp_user_id'];
            $secret = $_SESSION['temp_2fa_secret'];
            $code = $_POST['verification_code'];
            
            // Verify the code
            $twoFactorAuth = new TwoFactorAuth($pdo);
            if ($twoFactorAuth->verifyCode($secret, $code)) {
                // Code is valid, enable 2FA for the user
                if ($twoFactorAuth->enable2FA($userId, $secret)) {
                    // Clear temporary session data
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['temp_2fa_secret']);
                    
                    // Redirect to login page with success message
                    $_SESSION['login_message'] = "Registration successful! Your account is now secured with two-factor authentication.";
                    $_SESSION['login_message_type'] = "success";
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Failed to enable two-factor authentication. Please try again.";
                }
            } else {
                $error = "Invalid verification code. Please try again.";
                // Regenerate QR code for another attempt
                $twoFactorAuth = new TwoFactorAuth($pdo);
                $email = $_SESSION['temp_user_email'];
                $qrCode = $twoFactorAuth->getQRCode($email, $secret);
                $showQRCode = true;
            }
        }
        // Existing registration code
        else if (isset($_POST['username'])) {
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
                // Validate password strength
                list($isValidPassword, $passwordMessage) = validatePasswordStrength($password);
                if (!$isValidPassword) {
                    $error = $passwordMessage;
                } else {
                    // Register user
                    if ($user->register($username, $password, $email)) {
                        // Get the user ID for the newly registered user
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        $userId = $stmt->fetchColumn();
                        
                        if ($userId) {
                            // Generate 2FA secret
                            $twoFactorAuth = new TwoFactorAuth($pdo);
                            $secret = $twoFactorAuth->generateSecret();
                            
                            // Store in session temporarily
                            $_SESSION['temp_user_id'] = $userId;
                            $_SESSION['temp_2fa_secret'] = $secret;
                            $_SESSION['temp_user_email'] = $email;
                            
                            // Redirect to dedicated 2FA setup page
                            header("Location: setup-2fa.php");
                            exit();
                        } else {
                            $error = "Registration successful but failed to set up two-factor authentication.";
                        }
                    } else {
                        $error = "Registration failed. Email or username may already exist.";
                    }
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
    <title>Register - Flight Booking</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/accessibility.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
            <div class="md:flex">
                <div class="p-8 w-full">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold mb-1">Account</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2"><?php echo $showQRCode ? 'Set Up Two-Factor Authentication' : 'Create Account'; ?></h2>
                    <p class="text-gray-600 mb-6"><?php echo $showQRCode ? 'Enhance your account security' : 'Join us to start booking flights'; ?></p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle w-5 h-5"></i>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($showQRCode): ?>
                        <!-- 2FA Setup Form -->
                        <div class="bg-white p-6 rounded-xl shadow-md">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Scan this QR code with your authenticator app</h3>
                            
                            <div class="mb-6 flex justify-center">
                                <?php echo $qrCode; ?>
                            </div>
                            
                            <div class="mb-6">
                                <p class="text-sm text-gray-600 mb-2">If you can't scan the QR code, enter this code manually:</p>
                                <div class="bg-gray-100 p-3 rounded text-center font-mono select-all">
                                    <?php echo htmlspecialchars($secret); ?>
                                </div>
                            </div>
                            
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="setup_2fa" value="1">
                                
                                <div>
                                    <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                                    <input type="text" id="verification_code" name="verification_code" 
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" 
                                        placeholder="Enter 6-digit code" required autocomplete="off" inputmode="numeric" pattern="[0-9]*" maxlength="6">
                                </div>
                                
                                <div>
                                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Verify and Complete Registration
                                    </button>
                                </div>
                                
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Download an authenticator app</h4>
                                    <div class="flex space-x-4 justify-center">
                                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                            Google Authenticator (Android)
                                        </a>
                                        <a href="https://apps.apple.com/us/app/google-authenticator/id388497605" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                            Google Authenticator (iOS)
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Regular Registration Form -->
                        <form method="POST" action="register.php" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" id="username" name="username" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Choose a username" required>
                                </div>
                            </div>
                            
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
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Create a password" required>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Must be at least 12 characters with uppercase & lowercase letters, numbers, and special characters</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Confirm your password" required>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Create Account
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-sm">
                                    Already have an account?
                                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                                        Sign In
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Benefits Section -->
        <div class="max-w-md mx-auto mt-8 bg-white rounded-xl shadow-sm overflow-hidden md:max-w-2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Account Benefits</h3>
                <div class="space-y-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-plane-departure text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Save your favorite flights and travel preferences
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Quick booking process with saved information
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tag text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            Access to exclusive deals and promotions
                        </p>
                    </div>
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-history text-indigo-500"></i>
                        </div>
                        <p class="ml-3 text-sm text-gray-600">
                            View and manage your booking history
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
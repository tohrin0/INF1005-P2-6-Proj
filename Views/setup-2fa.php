<?php
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'classes/TwoFactorAuth.php';
require_once 'vendor/autoload.php';

$error = '';
$adminInitiated = false;
$userId = null;
$email = '';
$secret = '';

// If the reset is admin-initiated via token
if (isset($_GET['admin_reset']) && $_GET['admin_reset'] == 1 && isset($_GET['token'])) {
    $token = $_GET['token'];
    // Query the database using dedicated 2FA reset fields
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE twofa_reset_token = ? AND twofa_reset_expiry > NOW() AND admin_2fa_reset = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $adminInitiated = true;
        $userId = $user['id'];
        $email = $user['email'];
        
        // Generate a new 2FA secret for re-setup
        $twoFactorAuth = new TwoFactorAuth($pdo);
        $secret = $twoFactorAuth->generateSecret();
        
        // Set temporary session variables for the 2FA setup process
        $_SESSION['temp_user_id']    = $userId;
        $_SESSION['temp_user_email'] = $email;
        $_SESSION['temp_2fa_secret'] = $secret;
    } else {
        $error = "Invalid or expired token.";
    }
}

// If not admin initiated, use standard flow that requires temp session data
if (!$adminInitiated) {
    if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_user_email']) || !isset($_SESSION['temp_2fa_secret'])) {
        header("Location: login.php");
        exit();
    }
    $userId = $_SESSION['temp_user_id'];
    $email  = $_SESSION['temp_user_email'];
    $secret = $_SESSION['temp_2fa_secret'];
}

// Handle form submission to verify the OTP and complete the 2FA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $code = $_POST['verification_code'] ?? '';
        if (empty($code)) {
            $error = "Please enter the verification code.";
        } else {
            $twoFactorAuth = new TwoFactorAuth($pdo);
            if ($twoFactorAuth->verifyCode($secret, $code)) {
                // Enable 2FA for the user
                if ($twoFactorAuth->enable2FA($userId, $secret)) {
                    // Clear temporary session variables
                    unset($_SESSION['temp_user_id'], $_SESSION['temp_user_email'], $_SESSION['temp_2fa_secret']);
                    
                    // Optionally clear the reset token and admin_reset flag
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL, admin_reset = 0 WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    // Redirect to login (or account page) with a success message
                    $_SESSION['login_message'] = "Two-factor authentication has been set up successfully.";
                    $_SESSION['login_message_type'] = "success";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Failed to enable two-factor authentication. Please try again.";
                }
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
    <title>Set Up Two-Factor Authentication - Sky International Travels</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>
    
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-md">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900">Set Up Two-Factor Authentication</h2>
                <p class="mt-2 text-gray-600">Enhance your account security with 2FA</p>
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
            
            <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-md text-yellow-800 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="font-medium">Important Warning</h3>
                        <p class="mt-1 text-sm">If you lose access to your authenticator app or device, you may be locked out of your account. Please save your recovery code or keep a backup of your authenticator app data.</p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6 flex flex-col items-center">
                <p class="text-gray-700 mb-4">Scan this QR code with your authenticator app:</p>
                <div class="bg-white p-4 border border-gray-200 rounded-lg">
                    <?php
                    // Generate the QR code (using BaconQrCode)
                    $twoFactorAuth = new TwoFactorAuth($pdo);
                    $qrCode = $twoFactorAuth->getQRCode($email, $secret);
                    echo $qrCode;
                    ?>
                </div>
            </div>
            
            <div class="mb-6">
                <p class="text-sm text-gray-600 mb-2">Or enter this code manually in your app:</p>
                <div class="bg-gray-100 p-3 rounded text-center font-mono select-all">
                    <?php echo htmlspecialchars($secret); ?>
                </div>
            </div>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="verification_code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="text" id="verification_code" name="verification_code" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-center tracking-widest" 
                            placeholder="Enter 6-digit code" required autocomplete="off" inputmode="numeric" pattern="[0-9]*" maxlength="6">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Verify and Complete Setup
                    </button>
                </div>
            </form>
            
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
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
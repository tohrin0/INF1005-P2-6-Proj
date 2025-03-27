<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../vendor/autoload.php';
require_once '../classes/TwoFactorAuth.php';

// Ensure the requester is an admin
if (!isAdmin()) {
    header("Location: edit-user.php?status=error&message=Unauthorized+access");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $email  = $_POST['email'] ?? null;
    
    if (empty($userId) || empty($email)) {
        header("Location: edit-user.php?status=error&message=Missing+required+parameters");
        exit;
    }
    
    try {
        // Generate a unique token for the 2FA reset with dedicated fields
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update the user's record to clear 2FA (force re-setup) and store the reset token
        $stmt = $pdo->prepare("UPDATE users SET twofa_reset_token = ?, twofa_reset_expiry = ?, two_factor_secret = NULL, two_factor_enabled = 0, admin_2fa_reset = 1 WHERE id = ? AND email = ?");
        if (!$stmt->execute([$token, $expiry, $userId, $email])) {
            throw new Exception("Failed to update user for 2FA reset");
        }
        
        // Dynamically determine the base URL from the current request
        $protocol    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host        = $_SERVER['HTTP_HOST'];
        $currentPath = $_SERVER['REQUEST_URI'];
        $adminPos    = strpos($currentPath, '/admin/');
        $basePath    = ($adminPos !== false) ? substr($currentPath, 0, $adminPos) : '';
        $siteUrl     = $protocol . $host . $basePath;
        
        // Create the 2FA reset link; direct users to the setup-2fa.php page.
        $resetLink = $siteUrl . "/setup-2fa.php?token=" . $token . "&admin_reset=1";
        
        // Prepare email using PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'augmenso.to@gmail.com';
        $mail->Password   = 'vjks aktz vheu arse'; // use environment variables in production
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('augmenso.to@gmail.com', 'Sky International Travels Admin');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject  = 'Two-Factor Authentication Reset Requested by Administrator';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                    <h2 style='color: #3366cc;'>Administrator Requested 2FA Reset</h2>
                    <p>An administrator has requested that you reset your two-factor authentication settings.</p>
                    <p>Click the link below to set up a new 2FA method for your account:</p>
                    <p><a href='{$resetLink}' style='display: inline-block; background-color: #3366cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Two-Factor Authentication</a></p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you did not request this reset, please contact support immediately.</p>
                </div>
            </body>
            </html>
        ";
        
        if (!$mail->send()) {
            throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = '2FA reset link sent!';
        header("Location: edit-user.php?id={$userId}");
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        header("Location: edit-user.php?id={$userId}");
        exit;
    }
} else {
    header("Location: edit-user.php?status=error&message=Method+not+allowed");
    exit;
}
?>
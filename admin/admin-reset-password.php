<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../vendor/autoload.php';
require_once '../classes/PasswordReset.php';

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
        $passwordReset = new PasswordReset($pdo);
        
        // Generate a unique token for the admin reset
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store token in the database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ?, admin_reset = 1 WHERE id = ? AND email = ?");
        if (!$stmt->execute([$token, $expiry, $userId, $email])) {
            throw new Exception("Failed to update user with reset token");
        }
        
        // Dynamically determine the base URL from the current request
        $protocol    = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host        = $_SERVER['HTTP_HOST'];
        $currentPath = $_SERVER['REQUEST_URI'];
        $adminPos    = strpos($currentPath, '/admin/');
        $basePath    = ($adminPos !== false) ? substr($currentPath, 0, $adminPos) : '';
        $siteUrl     = $protocol . $host . $basePath;
        
        // Create the reset link
        $resetLink = $siteUrl . "/reset-password.php?token=" . $token . "&admin_reset=1";
        
        // Use PHPMailer to send the email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'augmenso.to@gmail.com';
        $mail->Password   = 'vjks aktz vheu arse'; // Use environment variables in production
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('augmenso.to@gmail.com', 'Sky International Travels Admin');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject  = 'Password Reset Requested by Administrator';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                    <h2 style='color: #3366cc;'>Administrator Requested Password Reset</h2>
                    <p>An administrator has requested a password reset for your account.</p>
                    <p>Click the link below to set a new password:</p>
                    <p><a href='{$resetLink}' style='display: inline-block; background-color: #3366cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
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
        $_SESSION['flash_message'] = 'Password reset link sent!';
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
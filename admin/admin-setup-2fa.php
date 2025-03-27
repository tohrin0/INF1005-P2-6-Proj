<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../inc/session.php';
require_once '../classes/User.php';
require_once '../classes/TwoFactorAuth.php';
require_once '../inc/mail.php';

// Verify admin privileges
verifyAdminSession();

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get user ID and email from the request
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$email = isset($_POST['email']) ? $_POST['email'] : '';

// Validate the input
if (empty($userId) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Check if user exists
    $userObj = new User($pdo);
    $user = $userObj->getUserById($userId);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Generate a token for 2FA setup
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token valid for 24 hours
    
    // Store token in database
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ?, admin_reset = 1 WHERE id = ?");
    if (!$stmt->execute([$token, $expiry, $userId])) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate setup token']);
        exit;
    }
    
    // Prepare email content
    $siteName = SITE_NAME;
    $setupLink = SITE_URL . "/setup-2fa.php?token=$token&email=" . urlencode($email);
    
    $subject = "$siteName - Set Up Two-Factor Authentication";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
            .content { padding: 20px; }
            .button { display: inline-block; background-color: #4a6cf7; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Set Up Two-Factor Authentication</h2>
            </div>
            <div class='content'>
                <p>Hello {$user['username']},</p>
                <p>An administrator has requested that you set up two-factor authentication for your account.</p>
                <p>Two-factor authentication adds an extra layer of security to your account by requiring both your password and a verification code.</p>
                <p>Please click the button below to set up 2FA for your account:</p>
                <p><a href='$setupLink' class='button'>Set Up 2FA</a></p>
                <p>Or copy and paste this link into your browser:</p>
                <p>$setupLink</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't request this, please contact support immediately.</p>
                <p>Thank you,<br>$siteName Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email
    $emailSent = sendEmail($email, $subject, $message);
    
    if ($emailSent) {
        // Log the action
        error_log("Admin {$_SESSION['username']} (ID: {$_SESSION['user_id']}) sent 2FA setup email to user {$user['username']} (ID: $userId)");
        
        echo json_encode(['success' => true, 'message' => '2FA setup email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
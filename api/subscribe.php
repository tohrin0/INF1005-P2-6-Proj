<?php
// Start session for all requests
session_start();

// Include core files
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../classes/EmailNotification.php';

// Prevent PHP warnings/notices from corrupting the JSON output
error_reporting(E_ERROR);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the email from the POST data
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING) ?? '';

// Validate email
if (!$email) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    // Generate unsubscribe token
    $unsubscribeToken = bin2hex(random_bytes(32));
    
    // Check if the email is already in the subscribers list
    $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscriber) {
        // If already unsubscribed, resubscribe
        if ($subscriber['status'] === 'unsubscribed') {
            $updateStmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'subscribed', updated_at = NOW() WHERE email = ?");
            $updateStmt->execute([$email]);
            $message = 'Welcome back! You have been resubscribed to our newsletter.';
            $unsubscribeToken = $subscriber['unsubscribe_token']; // Use existing token
        } else {
            // Already subscribed
            echo json_encode(['success' => true, 'message' => 'You are already subscribed to our newsletter.']);
            exit;
        }
    } else {
        // New subscriber, insert into database
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, first_name, unsubscribe_token) VALUES (?, ?, ?)");
        $stmt->execute([$email, $firstName, $unsubscribeToken]);
        $message = 'Thank you for subscribing to our newsletter!';
    }
    
    // Send confirmation email
    $emailer = new EmailNotification();
    $subject = 'Welcome to the Sky International Travels Newsletter';
    
    // Generate unsubscribe URL using the same method as admin-reset-password.php
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Determine the base directory path
    $currentPath = $_SERVER['REQUEST_URI'];
    $apiPos = strpos($currentPath, '/api/');
    $basePath = '';
    if ($apiPos !== false) {
        $basePath = substr($currentPath, 0, $apiPos);
    }
    
    // Construct the full site URL
    $siteUrl = $protocol . $host . $basePath;
    
    // Create the unsubscribe link
    $unsubscribeUrl = $siteUrl . "/unsubscribe.php?token=" . $unsubscribeToken . "&email=" . urlencode($email);
    
    // Log the URL for debugging
    error_log("Generated unsubscribe URL: " . $unsubscribeUrl);
    
    // Prepare email body with unsubscribe link
    $emailBody = "
        <html>
        <head>
            <title>Welcome to Sky International Travels Newsletter</title>
        </head>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                <h2 style='color: #007bff;'>Thank You for Subscribing!</h2>
                <p>Dear " . ($firstName ? htmlspecialchars($firstName) : 'Traveler') . ",</p>
                <p>Thank you for subscribing to the Sky International Travels newsletter. You'll now receive our latest news, exclusive deals, and travel promotions directly to your inbox.</p>
                <p>We're excited to have you join our community of travelers!</p>
                <div style='margin: 30px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0;'><strong>Stay connected with us:</strong></p>
                    <p style='margin: 10px 0 0;'>Follow us on social media for more travel inspiration and updates.</p>
                </div>
                <p>Happy travels,<br>The Sky International Travels Team</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    If you wish to unsubscribe from our newsletter, <a href='{$unsubscribeUrl}' style='color: #007bff;'>click here</a>.
                </p>
            </div>
        </body>
        </html>
    ";
    
    // Send the email
    $emailSent = $emailer->sendCustomEmail($email, $subject, $emailBody);
    
    // Always return success if the database operation worked, even if email sending fails
    $response = ['success' => true, 'message' => $message];
    
    // Add a warning if email sending failed, but don't change success status
    if (!$emailSent) {
        $response['warning'] = true;
        $response['message'] .= ' However, there was a problem sending the confirmation email. Please check your inbox later.';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred during subscription: ' . $e->getMessage()]);
}
?>
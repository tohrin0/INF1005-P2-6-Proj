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

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$message = '';
$status = '';

// Validate inputs
if (empty($token) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Invalid unsubscribe request. Please check the link and try again.';
    $status = 'error';
} else {
    try {
        // Check if the token and email match a subscriber
        $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE email = ? AND unsubscribe_token = ?");
        $stmt->execute([$email, $token]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subscriber) {
            $message = 'Invalid unsubscribe request. Please check the link and try again.';
            $status = 'error';
        } else if ($subscriber['status'] === 'unsubscribed') {
            $message = 'You have already unsubscribed from our newsletter.';
            $status = 'info';
        } else {
            // Update subscriber status
            $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', updated_at = NOW() WHERE email = ?");
            $stmt->execute([$email]);
            
            $message = 'You have been successfully unsubscribed from our newsletter.';
            $status = 'success';
        }
    } catch (Exception $e) {
        $message = 'An error occurred while processing your request.';
        $status = 'error';
    }
}

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Newsletter Unsubscribe</h1>
            
            <?php if ($status === 'success'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php elseif ($status === 'error'): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php else: ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>
            
            <p class="mt-4 text-gray-600">
                If you've changed your mind, you can always subscribe again on our homepage.
            </p>
            
            <div class="mt-6">
                <a href="index.php" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../inc/session.php';

verifyAdminSession();

// Process newsletter sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $recipient_type = $_POST['recipient_type'] ?? 'all';
    
    $errors = [];
    
    // Validate inputs
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($errors)) {
        // Prepare query based on recipient type
        $query = "SELECT email, unsubscribe_token FROM newsletter_subscribers WHERE status = 'subscribed'";
        if ($recipient_type === 'active') {
            $query .= " AND last_activity > DATE_SUB(NOW(), INTERVAL 90 DAY)";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_recipients = count($recipients);
        
        if ($total_recipients > 0) {
            // Initialize email notification service
            require_once '../classes/EmailNotification.php';
            $emailer = new EmailNotification();
            
            // Determine site URL for unsubscribe links
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $basePath = str_replace('/admin', '', dirname($_SERVER['REQUEST_URI']));
            $siteUrl = $protocol . $host . $basePath;
            
            // Track successful emails
            $success_count = 0;
            
            // Send email to each recipient
            foreach ($recipients as $recipient) {
                $email = $recipient['email'];
                $unsubscribeToken = $recipient['unsubscribe_token'];
                
                // Generate unsubscribe URL
                $unsubscribeUrl = $siteUrl . "/unsubscribe.php?token=" . $unsubscribeToken . "&email=" . urlencode($email);
                
                // Prepare email body with content from the form and add unsubscribe link
                $emailBody = "
                    <html>
                    <head>
                        <title>" . htmlspecialchars($subject) . "</title>
                    </head>
                    <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
                            " . $content . "
                            
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #666;'>
                                If you wish to unsubscribe from our newsletter, <a href='" . $unsubscribeUrl . "' style='color: #007bff;'>click here</a>.
                            </p>
                        </div>
                    </body>
                    </html>
                ";
                
                // Send the email
                if ($emailer->sendCustomEmail($email, $subject, $emailBody)) {
                    $success_count++;
                }
            }
            
            // Log the newsletter sending
            try {
                $logStmt = $pdo->prepare("INSERT INTO newsletter_log (subject, recipient_count, sent_by) VALUES (?, ?, ?)");
                $logStmt->execute([$subject, $total_recipients, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                // Just log the error but continue - don't let logging failure stop the process
                error_log("Failed to log newsletter: " . $e->getMessage());
                // You could also create the table here if it doesn't exist, but that's usually better done in a migration script
            }
            
            $_SESSION['admin_message'] = "Newsletter sent successfully to {$success_count} of {$total_recipients} subscribers.";
            header('Location: subscribers.php');
            exit();
        } else {
            $errors[] = "No recipients found matching the selected criteria.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Send Newsletter</h1>
        <a href="subscribers.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Subscribers
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form method="POST" action="send-newsletter.php">
            <div class="mb-6">
                <label for="recipient_type" class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                <select name="recipient_type" id="recipient_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All Subscribers</option>
                    <option value="active">Active Subscribers (active in last 90 days)</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject Line</label>
                <input type="text" name="subject" id="subject" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                       value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
            </div>
            
            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Newsletter Content</label>
                <textarea name="content" id="content" rows="15" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
                <p class="mt-2 text-sm text-gray-500">You can use HTML for formatting.</p>
            </div>
            
            <div class="flex items-center justify-between">
                <a href="subscribers.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i> Send Newsletter
                </button>
            </div>
        </form>
    </div>
    
    <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Important Notes</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Newsletters are sent based on user subscription preferences.</li>
                        <li>Please test newsletters before sending to all subscribers.</li>
                        <li>Do not send more than one newsletter per week to avoid unsubscribes.</li>
                        <li>All newsletters must comply with anti-spam laws and include an unsubscribe link.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
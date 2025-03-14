<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
        $message = "All fields are required.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
            if ($stmt->execute([$name, $email, $subject, $messageText])) {
                $message = "Your message has been sent successfully. We'll get back to you soon!";
                $messageType = "success";
            } else {
                $message = "Failed to send message. Please try again.";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "An error occurred. Please try again later.";
            $messageType = "error";
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Flight Booking Website</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Have questions or feedback? We'd love to hear from you</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <div class="row" style="display: flex; flex-wrap: wrap; margin: -15px;">
                <div class="col" style="flex: 1; min-width: 300px; padding: 15px;">
                    <h2>Get in Touch</h2>
                    <p>Our team is here to help you with any questions or concerns you may have about our services. Please fill out the form, and we'll get back to you as soon as possible.</p>
                    
                    <div style="margin-top: 30px;">
                        <div style="display: flex; align-items: center; margin-bottom: 20px;">
                            <div style="background: #3498db; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 5px; color: #2c3e50;">Email</h4>
                                <p style="margin: 0; color: #555;">support@flightbooking.com</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; margin-bottom: 20px;">
                            <div style="background: #3498db; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 5px; color: #2c3e50;">Phone</h4>
                                <p style="margin: 0; color: #555;">+1 (555) 123-4567</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center;">
                            <div style="background: #3498db; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 5px; color: #2c3e50;">Address</h4>
                                <p style="margin: 0; color: #555;">123 Travel Street, Suite 456<br>San Francisco, CA 94107</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col" style="flex: 1; min-width: 300px; padding: 15px;">
                    <form action="contact.php" method="POST" class="contact-form">
                        <?php if ($message): ?>
                            <div class="<?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-heading"></i> Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Enter message subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message"><i class="fas fa-comment-alt"></i> Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="Type your message here..." required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <h2>Frequently Asked Questions</h2>
            <p>Before contacting us, you might find an answer to your question in our FAQ section.</p>
            <div style="text-align: center; margin-top: 20px;">
                <a href="faq.php" class="submit-btn">Visit FAQ Page</a>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
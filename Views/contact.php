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
        <div class="container mx-auto px-4">
            <h1 class="page-title">Contact Us</h1>
            <p class="page-subtitle">Have questions or feedback? We'd love to hear from you</p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="mb-12">
            <div class="flex flex-wrap -mx-4">
                <div class="w-full lg:w-1/2 px-4 mb-8 lg:mb-0">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Get in Touch</h2>
                    <p class="text-gray-600 mb-8">Our team is here to help you with any questions or concerns you may have about our services. Please fill out the form, and we'll get back to you as soon as possible.</p>

                    <div class="space-y-6">
                        <div class="flex items-center">
                            <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Email</h4>
                                <p class="text-gray-600">support@flightbooking.com</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Phone</h4>
                                <p class="text-gray-600">+1 (555) 123-4567</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Address</h4>
                                <p class="text-gray-600">123 Travel Street, Suite 456<br>San Francisco, CA 94107</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 px-4">
                    <form action="contact.php" method="POST" class="bg-white p-8 rounded-lg shadow-sm">
                        <?php if ($message): ?>
                            <div class="<?php echo $messageType === 'success' ? 'alert alert-success' : 'alert alert-danger'; ?> mb-6">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name" class="form-label"><i class="fas fa-user mr-2"></i>Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label"><i class="fas fa-envelope mr-2"></i>Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="subject" class="form-label"><i class="fas fa-heading mr-2"></i>Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Enter message subject" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="message" class="form-label"><i class="fas fa-comment-alt mr-2"></i>Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="Type your message here..." required class="form-input"></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full">
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-8 mb-12 text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h2>
            <p class="text-gray-600 mb-6">Before contacting us, you might find an answer to your question in our FAQ section.</p>
            <a href="faq.php" class="btn-primary">Visit FAQ Page</a>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
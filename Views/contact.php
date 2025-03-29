<?php
require_once 'inc/session.php';
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'classes/ContactMessage.php';
require_once 'inc/accessibility.php';

include 'templates/header.php';

$successMessage = '';
$errorMessage = '';
$name = '';
$email = '';
$message = '';
$subject = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : 'Contact Message';

    // Create a new ContactMessage object
    $contactMessage = new ContactMessage();
    $contactMessage->setFromForm([
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'subject' => $subject
    ]);

    // Validate the form data
    list($isValid, $errorMessage) = $contactMessage->validate();

    if ($isValid) {
        // Save the message to the database
        if ($contactMessage->save()) {
            $successMessage = 'Thank you! Your message has been sent successfully. We will get back to you soon.';
            // Clear form data after successful submission
            $name = $email = $message = $subject = '';
        } else {
            $errorMessage = 'Sorry, there was a problem sending your message. Please try again later.';
        }
    }
}
?>

<div class="bg-blue-600 text-white py-16 px-4 mb-8">
    <div class="container mx-auto">
        <h1 class="text-4xl font-bold mb-4">Contact Us</h1>
        <p class="text-xl">We'd love to hear from you. Get in touch with our team.</p>
    </div>
</div>

<div class="container mx-auto px-4 mb-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div class="bg-white rounded-xl shadow-md p-8 border border-gray-100">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Send Us a Message</h2>

            <?php if ($successMessage): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject (Optional)</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Your Message</label>
                    <textarea id="message" name="message" rows="5" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"><?php echo htmlspecialchars($message); ?></textarea>
                </div>

                <button type="submit" name="submit_contact"
                    class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-6 rounded-md transition-colors">
                    Send Message
                </button>
            </form>
        </div>

        <div>
            <div class="bg-white rounded-xl shadow-md p-8 mb-8 border border-gray-100">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Contact Information</h2>
                <div class="space-y-6">
                    <div class="flex items-center">
                        <div class="bg-sky-100 text-sky-600 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Address</h4>
                            <p class="text-gray-600">123 Travel Street, Singapore</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="bg-sky-100 text-sky-600 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Email</h4>
                            <p class="text-gray-600">support@flightbooking.com</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="bg-sky-100 text-sky-600 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Phone</h4>
                            <p class="text-gray-600">+1 (555) 123-4567</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="bg-sky-100 text-sky-600 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Office Hours</h4>
                            <p class="text-gray-600">Monday to Friday: 9am - 5pm</p>
                            <p class="text-gray-600">Saturday: 10am - 2pm</p>
                            <p class="text-gray-600">Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-8 border border-gray-100">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Follow Us</h2>
                <div class="flex space-x-4">
                    <a href="#" class="bg-gray-100 hover:bg-sky-100 text-gray-500 hover:text-sky-600 p-3 rounded-full transition-colors">
                        <i class="fab fa-facebook-f text-xl"></i>
                    </a>
                    <a href="#" class="bg-gray-100 hover:bg-sky-100 text-gray-500 hover:text-sky-600 p-3 rounded-full transition-colors">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="bg-gray-100 hover:bg-sky-100 text-gray-500 hover:text-sky-600 p-3 rounded-full transition-colors">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="bg-gray-100 hover:bg-sky-100 text-gray-500 hover:text-sky-600 p-3 rounded-full transition-colors">
                        <i class="fab fa-linkedin-in text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
</body>

</html>
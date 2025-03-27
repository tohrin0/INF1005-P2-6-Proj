<?php
require_once 'inc/session.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Terms and Conditions</h1>
        <p class="text-gray-600 mb-8">Welcome to our flight booking website. By using our services, you agree to the following terms and conditions:</p>

        <div class="space-y-8">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">1. Acceptance of Terms</h2>
                <p class="text-gray-600">By accessing and using our website, you accept and agree to be bound by these terms. If you do not agree, please do not use our services.</p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">2. User Responsibilities</h2>
                <p class="text-gray-600">Users are responsible for maintaining the confidentiality of their account information and for all activities that occur under their account.</p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">3. Booking Policies</h2>
                <p class="text-gray-600">All bookings are subject to availability and confirmation. Please review our booking policies for more details.</p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">4. Payment Terms</h2>
                <p class="text-gray-600">Payments must be made at the time of booking. We accept various payment methods as outlined on our payment page.</p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">5. Changes to Terms</h2>
                <p class="text-gray-600">We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on our website.</p>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-3">6. Contact Information</h2>
                <p class="text-gray-600">If you have any questions about these terms, please contact us through our contact page.</p>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
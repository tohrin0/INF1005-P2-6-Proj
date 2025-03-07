<?php
// Start session for consistent header/footer behavior
session_start();
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

    <div class="container">
        <h1>Terms and Conditions</h1>
        <p>Welcome to our flight booking website. By using our services, you agree to the following terms and conditions:</p>
        
        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using our website, you accept and agree to be bound by these terms. If you do not agree, please do not use our services.</p>
        
        <h2>2. User Responsibilities</h2>
        <p>Users are responsible for maintaining the confidentiality of their account information and for all activities that occur under their account.</p>
        
        <h2>3. Booking Policies</h2>
        <p>All bookings are subject to availability and confirmation. Please review our booking policies for more details.</p>
        
        <h2>4. Payment Terms</h2>
        <p>Payments must be made at the time of booking. We accept various payment methods as outlined on our payment page.</p>
        
        <h2>5. Changes to Terms</h2>
        <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on our website.</p>
        
        <h2>6. Contact Information</h2>
        <p>If you have any questions about these terms, please contact us through our contact page.</p>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Flight Booking Website</title>
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
            <h1 class="page-title">About Us</h1>
            <p class="page-subtitle">Learn about our story, mission, and the team behind Flight Booking</p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Story</h2>
            <p class="text-gray-600 mb-4">Welcome to our Flight Booking Website! Founded in 2020, we started with a simple mission: to make travel accessible and enjoyable for everyone. What began as a small startup has grown into a trusted platform that connects thousands of travelers with flights around the world.</p>

            <p class="text-gray-600">We believe that travel should be seamless, affordable, and stress-free. That's why we've built a platform that offers transparent pricing, user-friendly booking processes, and reliable customer support.</p>
        </div>

        <div class="mb-16">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Mission</h2>
            <p class="text-gray-600 mb-8">At Flight Booking, our mission is to revolutionize the way people book their travel. We strive to:</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card">
                    <div class="text-4xl mb-4 text-blue-600">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Connect the World</h3>
                    <p class="text-gray-600">We aim to make travel accessible to everyone by providing competitive prices and a wide range of flight options.</p>
                </div>

                <div class="card">
                    <div class="text-4xl mb-4 text-blue-600">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Ensure Safety</h3>
                    <p class="text-gray-600">Safety is our top priority. We partner only with reliable airlines that meet strict safety standards.</p>
                </div>

                <div class="card">
                    <div class="text-4xl mb-4 text-blue-600">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Provide Support</h3>
                    <p class="text-gray-600">Our dedicated customer support team is always ready to assist you with any questions or concerns.</p>
                </div>
            </div>
        </div>

        <div class="mb-16">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Our Team</h2>
            <p class="text-gray-600">Behind Flight Booking is a team of passionate travel enthusiasts and industry experts dedicated to creating the best booking experience possible.</p>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
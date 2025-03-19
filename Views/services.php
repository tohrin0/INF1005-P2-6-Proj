<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Flight Booking Website</title>
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
            <h1 class="page-title">Our Services</h1>
            <p class="page-subtitle">Discover the range of flight booking services we offer to make your travel experience seamless</p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Comprehensive Flight Booking</h2>
            <p class="text-gray-600">Welcome to our flight booking website! We offer a variety of services designed to make your travel planning and booking experience as smooth and enjoyable as possible.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-plane"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Flight Booking</h3>
                <p class="text-gray-600">Book your flights easily through our user-friendly interface. Search for available flights, compare prices, and secure your tickets in just a few clicks.</p>
            </div>

            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Real-Time Flight Schedule API</h3>
                <p class="text-gray-600">We utilize a reliable flight schedule API to provide you with real-time information on flight availability, timings, and prices.</p>
            </div>

            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">User Membership</h3>
                <p class="text-gray-600">Join our membership program to enjoy exclusive benefits, including discounts on bookings, priority customer support, and personalized travel recommendations.</p>
            </div>

            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Data Management</h3>
                <p class="text-gray-600">Manage your bookings and personal information easily through your account dashboard. Access your booking history, update your details, and more.</p>
            </div>

            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Customer Support</h3>
                <p class="text-gray-600">Our dedicated customer support team is available to assist you with any questions or issues you may encounter during the booking process or your travels.</p>
            </div>

            <div class="card">
                <div class="text-4xl mb-4 text-blue-600">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Flight Alerts</h3>
                <p class="text-gray-600">Receive timely notifications about flight status updates, gate changes, special offers, and other important information related to your travel plans.</p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-8 mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Premium Services</h2>
            <p class="text-gray-600 mb-6">For travelers looking for an enhanced booking experience, we offer additional premium services that provide extra comfort and convenience:</p>

            <ul class="space-y-2 text-gray-600 mb-8 list-disc pl-5">
                <li>Priority boarding options</li>
                <li>Seat selection and upgrades</li>
                <li>Special meal arrangements</li>
                <li>Additional baggage allowance</li>
                <li>Airport lounge access</li>
                <li>Travel insurance packages</li>
            </ul>

            <p class="text-gray-600 mb-6">To learn more about our premium services or to customize your booking with additional options, please contact our customer support team.</p>

            <a href="contact.php" class="btn-primary">Contact Us</a>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>

</html>
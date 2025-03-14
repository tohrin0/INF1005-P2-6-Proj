php
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
            <h1>Our Services</h1>
            <p>Discover the range of flight booking services we offer to make your travel experience seamless</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <h2>Comprehensive Flight Booking</h2>
            <p>Welcome to our flight booking website! We offer a variety of services designed to make your travel planning and booking experience as smooth and enjoyable as possible.</p>
        </div>

        <div class="card-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-plane"></i>
                </div>
                <div class="card-content">
                    <h3>Flight Booking</h3>
                    <p>Book your flights easily through our user-friendly interface. Search for available flights, compare prices, and secure your tickets in just a few clicks.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <h3>Real-Time Flight Schedule API</h3>
                    <p>We utilize a reliable flight schedule API to provide you with real-time information on flight availability, timings, and prices.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="card-content">
                    <h3>User Membership</h3>
                    <p>Join our membership program to enjoy exclusive benefits, including discounts on bookings, priority customer support, and personalized travel recommendations.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="card-content">
                    <h3>Data Management</h3>
                    <p>Manage your bookings and personal information easily through your account dashboard. Access your booking history, update your details, and more.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="card-content">
                    <h3>Customer Support</h3>
                    <p>Our dedicated customer support team is available to assist you with any questions or issues you may encounter during the booking process or your travels.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="card-content">
                    <h3>Flight Alerts</h3>
                    <p>Receive timely notifications about flight status updates, gate changes, special offers, and other important information related to your travel plans.</p>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2>Premium Services</h2>
            <p>For travelers looking for an enhanced booking experience, we offer additional premium services that provide extra comfort and convenience:</p>
            
            <ul style="line-height: 1.7; color: #555; margin-bottom: 20px; padding-left: 20px;">
                <li>Priority boarding options</li>
                <li>Seat selection and upgrades</li>
                <li>Special meal arrangements</li>
                <li>Additional baggage allowance</li>
                <li>Airport lounge access</li>
                <li>Travel insurance packages</li>
            </ul>
            
            <p>To learn more about our premium services or to customize your booking with additional options, please contact our customer support team.</p>
            
            <a href="contact.php" class="submit-btn">Contact Us</a>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
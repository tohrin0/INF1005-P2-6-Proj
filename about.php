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
        <div class="container">
            <h1>About Us</h1>
            <p>Learn about our story, mission, and the team behind Flight Booking</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <h2>Our Story</h2>
            <p>Welcome to our Flight Booking Website! Founded in 2020, we started with a simple mission: to make travel accessible and enjoyable for everyone. What began as a small startup has grown into a trusted platform that connects thousands of travelers with flights around the world.</p>
            
            <p>We believe that travel should be seamless, affordable, and stress-free. That's why we've built a platform that offers transparent pricing, user-friendly booking processes, and reliable customer support.</p>
        </div>

        <div class="content-section">
            <h2>Our Mission</h2>
            <p>At Flight Booking, our mission is to revolutionize the way people book their travel. We strive to:</p>
            
            <div class="card-grid">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="card-content">
                        <h3>Connect the World</h3>
                        <p>We aim to make travel accessible to everyone by providing competitive prices and a wide range of flight options.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="card-content">
                        <h3>Ensure Safety</h3>
                        <p>Safety is our top priority. We partner only with reliable airlines that meet strict safety standards.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="card-content">
                        <h3>Provide Support</h3>
                        <p>Our dedicated customer support team is always ready to assist you with any questions or concerns.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section team-section">
            <h2>Our Team</h2>
            <p>Behind Flight Booking is a team of passionate travel enthusiasts and industry experts dedicated to creating the best booking experience possible.</p>
            
            <!--<div class="team-grid">
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h3>John Smith</h3>
                        <div class="role">CEO & Founder</div>
                        <p>With over 15 years in the travel industry, John leads our company with a focus on innovation and customer satisfaction.</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h3>Sarah Johnson</h3>
                        <div class="role">Chief Technology Officer</div>
                        <p>Sarah oversees our technical operations, ensuring a seamless booking experience across our platform.</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-info">
                        <h3>Michael Chen</h3>
                        <div class="role">Customer Relations</div>
                        <p>Michael leads our customer support team, dedicated to resolving issues and ensuring traveler satisfaction.</p>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
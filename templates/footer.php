<?php
// Footer section for the flight booking website
?>

<footer class="site-footer">
    <div class="footer-content container">
        <div class="footer-main">
            <div class="footer-brand">
                <h3>Flight Booking</h3>
                <p class="tagline">Your journey begins with us</p>
            </div>
            <div class="footer-nav">
                <h4>Navigation</h4>
                <ul class="footer-links">
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="terms.php">Terms and Conditions</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Travel Street, Singapore</p>
                <p><i class="fas fa-phone"></i> +65 12345678</p>
                <p><i class="fas fa-envelope"></i> info@flightbooking.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Flight Booking Website. All rights reserved.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Compact footer styling */
    .site-footer {
        background-color: #1a2b49;
        color: #fff;
        padding: 30px 0 15px;
        margin-top: 30px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px;
    }
    
    .footer-content {
        max-width: 900px !important; /* Reduced from default container width */
        margin: 0 auto;
        padding: 0 15px;
    }
    
    .footer-main {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-bottom: 20px;
        gap: 20px;
    }
    
    .footer-brand {
        flex: 0 0 220px;
    }
    
    .footer-brand h3 {
        font-size: 20px;
        margin: 0 0 8px;
        font-weight: 600;
    }
    
    .footer-brand .tagline {
        color: #b3c0d1;
        margin: 0;
        font-size: 13px;
    }
    
    .footer-nav {
        flex: 0 0 220px;
    }
    
    .footer-contact {
        flex: 0 0 220px;
    }
    
    .footer-contact p {
        margin: 5px 0;
        color: #b3c0d1;
        font-size: 13px;
    }
    
    .footer-contact i {
        width: 15px;
        margin-right: 5px;
        color: #4CAF50;
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 5px;
    }
    
    .footer-links a {
        color: #b3c0d1;
        text-decoration: none;
        transition: color 0.3s;
        font-size: 13px;
    }
    
    .footer-links a:hover {
        color: #4CAF50;
    }
    
    h4 {
        font-size: 16px;
        margin-top: 0;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .footer-bottom {
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    
    .footer-bottom p {
        margin: 0;
        font-size: 12px;
        color: #b3c0d1;
    }
    
    .social-links {
        display: flex;
    }
    
    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        margin-left: 8px;
        transition: background-color 0.3s;
        text-decoration: none;
    }
    
    .social-links a:hover {
        background-color: #4CAF50;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .footer-main {
            justify-content: flex-start;
        }
        
        .footer-brand, .footer-nav, .footer-contact {
            flex: 0 0 100%;
            margin-bottom: 15px;
        }
        
        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }
        
        .social-links {
            margin-top: 10px;
            justify-content: center;
        }
        
        .social-links a {
            margin: 0 4px;
        }
    }
</style>

<script src="assets/js/main.js"></script>
<script src="assets/js/api-client.js"></script>
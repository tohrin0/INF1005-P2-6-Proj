<?php
// Footer section for the flight booking website
?>

<footer>
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Flight Booking Website. All rights reserved.</p>
        <ul class="footer-links">
            <li><a href="about.php">About Us</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="terms.php">Terms and Conditions</a></li>
        </ul>
    </div>
</footer>

<script src="assets/js/main.js"></script>
<script src="assets/js/api-client.js"></script>
<script src="assets/js/booking-form.js"></script>
<script src="assets/js/payment.js"></script>

<style>
    /* Footer styling updates */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 15px 0 0 0;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .footer-links li {
        margin: 0 15px;
        padding: 0;
    }
    
    .footer-links a {
        color: #ecf0f1;
        text-decoration: none;
    }
    
    .footer-links a:hover {
        text-decoration: underline;
    }
</style>
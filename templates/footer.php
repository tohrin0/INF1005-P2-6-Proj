<?php
// Footer section for the flight booking website
?>

<footer class="site-footer">
    <div class="footer-content container">
        <div class="footer-main">
            <div class="footer-brand">
                <h3>Sky International Travels</h3>
                <p class="tagline">Your journey begins with us</p>
                <div class="footer-clock">
                    <div id="clock" class="clock">00:00:00</div>
                    <div class="timezone">UTC (Flight Times)</div>
                </div>
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
                <p><i class="fas fa-envelope"></i> info@skyinternationaltravels.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Sky International Travels. All rights reserved.</p>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/main.js"></script>
<script src="assets/js/api-client.js"></script>

<!-- Add clock script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        updateUTCClock();
    });
    
    function updateUTCClock() {
        // Get current UTC time (used by AviationStack API)
        const now = new Date();
        const utcHours = String(now.getUTCHours()).padStart(2, '0');
        const utcMinutes = String(now.getUTCMinutes()).padStart(2, '0');
        const utcSeconds = String(now.getUTCSeconds()).padStart(2, '0');
        
        // Format time as HH:MM:SS
        const timeString = `${utcHours}:${utcMinutes}:${utcSeconds}`;
        
        // Update the clock element with UTC time
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
        
        // Update every second
        setTimeout(updateUTCClock, 1000);
    }
</script>
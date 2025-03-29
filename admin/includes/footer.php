<?php
// Footer section for the admin pages
?>

</main>

<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-company">
            <h3>Flight Booking Admin</h3>
            <p>&copy; <?php echo date("Y"); ?> Sky International Travels. All rights reserved.</p>
        </div>
        <div class="footer-links">
            <div class="footer-links-column">
                <h4>Quick Navigation</h4>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="bookings.php">Manage Bookings</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="flights.php">Manage Flights</a></li>
                </ul>
            </div>
            <div class="footer-links-column">
                <h4>Front End</h4>
                <ul>
                    <li><a href="../index.php" class="site-link"><i class="fas fa-home"></i> Return to Website</a></li>
                    <li><a href="../about.php">About Us</a></li>
                    <li><a href="../services.php">Services</a></li>
                    <li><a href="../contact.php">Contact</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>Powered by SIT Systems | <a href="../terms.php">Terms & Conditions</a></p>
    </div>
</footer>

<style>
    .admin-footer {
        background-color: #343a40;
        color: #f8f9fa;
        padding: 30px 20px 15px;
        margin-top: 40px;
        border-top: 1px solid #4b545c;
    }
    
    .footer-content {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .footer-company {
        flex: 1;
        min-width: 250px;
        margin-bottom: 20px;
    }
    
    .footer-company h3 {
        color: #fff;
        font-size: 18px;
        margin: 0 0 10px;
    }
    
    .footer-company p {
        font-size: 14px;
        color: #adb5bd;
    }
    
    .footer-links {
        display: flex;
        flex-wrap: wrap;
        flex: 2;
    }
    
    .footer-links-column {
        flex: 1;
        min-width: 150px;
        margin-bottom: 20px;
        padding: 0 15px;
    }
    
    .footer-links-column h4 {
        color: #fff;
        font-size: 16px;
        margin: 0 0 15px;
        position: relative;
        padding-bottom: 8px;
    }
    
    .footer-links-column h4:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background-color: #007bff;
    }
    
    .footer-links-column ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links-column ul li {
        margin-bottom: 10px;
    }
    
    .footer-links-column ul li a {
        color: #adb5bd;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s;
    }
    
    .footer-links-column ul li a:hover {
        color: #ffffff;
    }
    
    .site-link {
        background-color: rgba(40, 167, 69, 0.2);
        padding: 5px 10px;
        border-radius: 4px;
        display: inline-block;
        color: #28a745 !important;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    
    .site-link:hover {
        background-color: rgba(40, 167, 69, 0.3);
        color: #ffffff !important;
    }
    
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 15px;
        margin-top: 15px;
        text-align: center;
        font-size: 13px;
        color: #868e96;
    }
    
    .footer-bottom a {
        color: #adb5bd;
        text-decoration: none;
    }
    
    .footer-bottom a:hover {
        color: #ffffff;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
        }
        
        .footer-links {
            flex-direction: column;
        }
        
        .footer-links-column {
            padding: 0;
        }
    }
</style>

        </div> <!-- This single closing div is correct to match the content-wrapper -->
    </div> <!-- This single closing div is correct to match the admin-wrapper -->
    
    
</body>
</html>
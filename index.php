<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Fetch flight data from API if user is logged in
$flights = [];
if ($isLoggedIn) {
    try {
        $apiClient = new ApiClient();
        $flights = $apiClient->getFlightSchedules();
        
        // Debug the API response
        error_log("API getFlightSchedules returned: " . json_encode(array_slice($flights, 0, 2)));
    } catch (Exception $e) {
        error_log("Error fetching flights: " . $e->getMessage());
    }
}

include 'templates/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Discover the World with Flight Booking</h1>
        <p>Your journey begins with a single search. Find the best flights at competitive prices.</p>
        <a href="search.php" class="btn btn-primary">Start Your Journey</a>
    </div>
</div>

<div class="container">
    <div class="features-section">
        <div class="feature">
            <div class="feature-icon">✈️</div>
            <h3>Global Destinations</h3>
            <p>Access flights to over 10,000 destinations worldwide with our comprehensive flight database.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">💰</div>
            <h3>Best Prices</h3>
            <p>Compare prices across multiple airlines to ensure you always get the best deal available.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">🔒</div>
            <h3>Secure Booking</h3>
            <p>Book with confidence using our secure payment system and receive instant confirmation.</p>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
        <div class="personal-section">
            <h2>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Welcome back to our flight booking platform. Ready to plan your next adventure?</p>
            <div class="action-buttons">
                <a href="search.php" class="btn">Search Flights</a>
                <a href="account.php" class="btn">My Account</a>
            </div>
        </div>

        <?php if (!empty($flights)): ?>
            <div class="popular-flights">
                <h2>Featured Flights</h2>
                <div class="flight-grid">
                    <?php 
                    $count = 0;
                    foreach ($flights as $flight): 
                        if ($count >= 3) break; // Only show up to 3 flights
                        $count++;
                    ?>
                        <div class="flight-card">
                            <div class="flight-header">
                                <span class="flight-number"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                                <span class="flight-airline"><?php echo htmlspecialchars($flight['airline']); ?></span>
                            </div>
                            <div class="flight-route">
                                <p><strong><?php echo htmlspecialchars($flight['departure']); ?></strong> to <strong><?php echo htmlspecialchars($flight['arrival']); ?></strong></p>
                                <p class="flight-date"><?php echo htmlspecialchars($flight['date']); ?> | <?php echo htmlspecialchars($flight['time']); ?></p>
                            </div>
                            <div class="flight-price">
                                <span>$<?php echo number_format($flight['price'], 2); ?></span>
                            </div>
                            <a href="booking.php?flight_id=<?php echo htmlspecialchars($flight['id']); ?>" class="btn book-btn">Book Now</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="cta-section">
            <h2>Join Our Community of Travelers</h2>
            <p>Create an account to book flights, save your favorite routes, and receive personalized travel recommendations.</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn">Log In</a>
                <a href="register.php" class="btn btn-primary">Create Account</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonial-grid">
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"I found an amazing deal to Europe that saved me over $300. The booking process was so simple!"</p>
                </div>
                <div class="testimonial-author">- Sarah T.</div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"The customer service was exceptional when I needed to change my flight dates at the last minute."</p>
                </div>
                <div class="testimonial-author">- Michael P.</div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"I've been using Flight Booking for all my business trips. Reliable, fast, and always the best prices."</p>
                </div>
                <div class="testimonial-author">- Jennifer R.</div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hero section styling */
    .hero-section {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('assets/images/hero-bg.jpg');
        background-color: #2c3e50; /* Fallback color */
        background-size: cover;
        background-position: center;
        color: white;
        padding: 80px 20px;
        text-align: center;
        margin-bottom: 40px;
    }
    
    .hero-content {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: white;
    }
    
    .hero-content p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    
    .btn-primary {
        background-color: #e74c3c;
    }
    
    .btn-primary:hover {
        background-color: #c0392b;
    }
    
    /* Features section */
    .features-section {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin: 40px 0;
    }
    
    .feature {
        flex: 1;
        min-width: 250px;
        padding: 20px;
        text-align: center;
        margin: 10px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
        font-size: 2rem;
        margin-bottom: 15px;
    }
    
    /* Personal section for logged-in users */
    .personal-section {
        background-color: #f1f9f1;
        padding: 30px;
        border-radius: 8px;
        margin: 30px 0;
    }
    
    /* Popular flights section */
    .popular-flights {
        margin: 40px 0;
    }
    
    .flight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .flight-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        padding: 20px;
        transition: transform 0.3s;
    }
    
    .flight-card:hover {
        transform: translateY(-5px);
    }
    
    .flight-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .flight-route {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .flight-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .flight-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #4CAF50;
        text-align: center;
        margin-bottom: 15px;
    }
    
    .book-btn {
        display: block;
        text-align: center;
    }
    
    /* CTA section for non-logged-in users */
    .cta-section {
        background-color: #e3f2fd;
        padding: 30px;
        border-radius: 8px;
        margin: 30px 0;
        text-align: center;
    }
    
    .cta-buttons {
        margin-top: 20px;
    }
    
    /* Testimonials section */
    .testimonials {
        margin: 50px 0;
        text-align: center;
    }
    
    .testimonial-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }
    
    .testimonial {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .testimonial-content {
        margin-bottom: 15px;
        font-style: italic;
    }
    
    .testimonial-author {
        font-weight: bold;
        color: #555;
    }
    
    /* Responsive design adjustments */
    @media (max-width: 768px) {
        .features-section {
            flex-direction: column;
        }
        
        .feature {
            margin: 10px 0;
        }
        
        .hero-content h1 {
            font-size: 2rem;
        }
    }
</style>

<?php include 'templates/footer.php'; ?>